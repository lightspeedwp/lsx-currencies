<?php
/**
 * LSX Currencies Frontend Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Handles frontend price-wrapping and exchange-rate fetching.
 * Currency switching UI is now provided by the currency-switcher block (view.js).
 */
class Frontend {

	/**
	 * Singleton instance.
	 *
	 * @var \lsx\currencies\classes\Frontend
	 */
	private static $instance;

	/**
	 * Live exchange rates object fetched from the API (base = USD).
	 *
	 * @var object|false
	 */
	public $rates = false;

	/**
	 * Human-readable status message for the last rate fetch.
	 *
	 * @var string
	 */
	public $rates_message = '';

	/**
	 * Currently selected currency (uppercased ISO 4217 code).
	 *
	 * @var string
	 */
	public $current_currency = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			add_action( 'init', array( $this, 'set_defaults' ), 11 );
			add_filter( 'lsx_to_custom_field_query', array( $this, 'price_filter' ), 20, 5 );
			add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );
			add_filter( 'get_post_metadata', array( $this, 'filter_post_meta' ), 100, 4 );
		}
	}

	/**
	 * Return singleton instance.
	 *
	 * @return \lsx\currencies\classes\Frontend
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Fetch exchange rates (cached for 12 hours) and resolve current currency.
	 */
	public function set_defaults() {
		$this->rates = get_transient( 'lsx_currencies_rates' );

		if ( false === $this->rates ) {
			$api_url  = esc_url_raw( lsx_currencies()->api_url );
			$response = wp_safe_remote_get( $api_url, array( 'timeout' => 10 ) );

			if ( is_wp_error( $response ) ) {
				$this->rates_message = $response->get_error_message();
			} else {
				$body    = wp_remote_retrieve_body( $response );
				$decoded = json_decode( $body );

				if ( ! empty( $decoded->error ) ) {
					$this->rates_message = sanitize_text_field( $decoded->description );
				} elseif ( empty( $body ) ) {
					$this->rates_message = esc_html__( 'Error: API response is empty.', 'lsx-currencies' );
				} elseif ( is_object( $decoded ) && isset( $decoded->rates ) ) {
					$this->rates         = $decoded->rates;
					$this->rates_message = esc_html__( 'Success (new request).', 'lsx-currencies' );
					set_transient( 'lsx_currencies_rates', $this->rates, 12 * HOUR_IN_SECONDS );
					do_action( 'lsx_currencies_rates_refreshed' );
				} else {
					$this->rates_message = esc_html__( 'Error: Invalid API response format.', 'lsx-currencies' );
				}
			}
		} else {
			$this->rates_message = esc_html__( 'Success (from cache).', 'lsx-currencies' );
		}

		// Resolve current currency from cookie.
		$cookie = isset( $_COOKIE['lsx_currencies_choice'] ) ? sanitize_key( $_COOKIE['lsx_currencies_choice'] ) : '';
		if ( '' !== $cookie ) {
			$uppercased         = strtoupper( $cookie );
			$available          = lsx_currencies()->available_currencies;
			$this->current_currency = ( is_array( $available ) && array_key_exists( $uppercased, $available ) )
				? $uppercased
				: lsx_currencies()->base_currency;
		} else {
			$this->current_currency = lsx_currencies()->base_currency;
		}

		if ( lsx_currencies()->convert_to_single ) {
			$this->current_currency = lsx_currencies()->base_currency;
		}
	}

	/**
	 * Returns the symbols array for enabled additional currencies.
	 *
	 * @return array<string,string>
	 */
	public function get_available_symbols() {
		$symbols             = array();
		$additional_currencies = lsx_currencies()->additional_currencies;
		$all_symbols           = lsx_currencies()->currency_symbols;

		if ( ! empty( $additional_currencies ) ) {
			foreach ( $additional_currencies as $code => $label ) {
				$code = strtoupper( sanitize_key( $code ) );
				if ( isset( $all_symbols[ $code ] ) ) {
					$symbols[ $code ] = $all_symbols[ $code ];
				}
			}
		}

		return $symbols;
	}

	/**
	 * Wraps tour `price` field values in currency-conversion markup.
	 * Hooked on `lsx_to_custom_field_query` from the Tour Operator plugin.
	 *
	 * @param string $return_html Existing field HTML.
	 * @param string $meta_key    Meta key being rendered.
	 * @param string $value       Raw meta value.
	 * @param string $before      HTML prepended before the field.
	 * @param string $after       HTML appended after the field.
	 * @return string
	 */
	public function price_filter( $return_html, $meta_key, $value, $before, $after ) {
		if ( 'price' !== $meta_key ) {
			return $return_html;
		}

		// Strip non-numeric characters (except decimal point).
		$value = preg_replace( '/[^0-9.]+/', '', $value );

		// Handle values with multiple decimal points.
		$decimal_count = substr_count( $value, '.' );
		if ( $decimal_count > 1 ) {
			$value = preg_replace( '/' . preg_quote( '.', '/' ) . '/', '', $value, $decimal_count - 1 );
		}

		$money_format = lsx_currencies()->remove_decimals ? 0 : 2;

		// Determine currency for this specific post.
		$currency      = lsx_currencies()->base_currency;
		$tour_currency = get_post_meta( get_the_ID(), 'currency', true );
		if ( ! empty( $tour_currency ) ) {
			$currency = strtoupper( sanitize_key( $tour_currency ) );
		}

		// Build additional data attributes for per-currency prices.
		$additional_html    = '';
		$additional_prices  = get_post_meta( get_the_ID(), 'additional_prices', true );
		if ( lsx_currencies()->multi_prices && is_array( $additional_prices ) && ! empty( $additional_prices ) ) {
			$allowed_codes = array_keys( lsx_currencies()->available_currencies );
			foreach ( $additional_prices as $a_price ) {
				if ( empty( $a_price['currency'] ) || empty( $a_price['amount'] ) ) {
					continue;
				}
				$a_code = strtoupper( sanitize_key( $a_price['currency'] ) );
				if ( ! in_array( $a_code, $allowed_codes, true ) ) {
					continue;
				}
				$a_amount         = (float) $a_price['amount'];
				$additional_html .= ' data-price-' . esc_attr( $a_code ) . '="' . esc_attr( $a_amount ) . '"';
			}
		}

		// Price value span.
		$price_attr = (float) $value;
		$formatted  = number_format( $price_attr, $money_format );

		$currency_span = '<span class="currency-icon ' . esc_attr( strtolower( $currency ) ) . '">' . esc_html( $currency ) . '</span>';

		$amount_span = '<span class="value" data-price-' . esc_attr( $currency ) . '="' . esc_attr( $price_attr ) . '"' . $additional_html . '>'
			. esc_html( $formatted )
			. '</span>';

		// Optionally append price type label.
		$price_type = get_post_meta( get_the_ID(), 'price_type', true );
		switch ( $price_type ) {
			case 'per_person_per_night':
			case 'per_person_sharing':
			case 'per_person_sharing_per_night':
				$amount = $currency_span . $amount_span . ' ' . esc_html( ucwords( str_replace( '_', ' ', $price_type ) ) );
				break;

			case 'total_percentage':
				$amount = $amount_span . '%&nbsp;' . esc_html__( 'Off', 'lsx-currencies' );
				$before = str_replace( 'from', '', $before );
				break;

			case 'none':
			default:
				$amount = $currency_span . $amount_span;
				break;
		}

		$wrapper     = '<span class="amount lsx-currencies">' . $amount . '</span>';
		$return_html = $before . $wrapper . $after;

		return $return_html;
	}

	/**
	 * Extends the wp_kses allowed HTML list to include data-price-* attributes
	 * on span elements so price HTML is not stripped when passed through wp_kses_post().
	 *
	 * @param array        $allowedtags Allowed tags array.
	 * @param string|array $context     Context string or 'post'.
	 * @return array
	 */
	public function wp_kses_allowed_html( $allowedtags, $context ) {
		if ( ! isset( $allowedtags['span'] ) ) {
			$allowedtags['span'] = array();
		}

		$codes = array_keys( lsx_currencies()->available_currencies );
		foreach ( $codes as $code ) {
			$code = strtoupper( sanitize_key( $code ) );
			$allowedtags['span'][ 'data-price-' . $code ] = true;
		}

		return $allowedtags;
	}

	/**
	 * Returns '0' for empty price meta when convert_to_single is enabled,
	 * so that price blocks still render something to convert.
	 *
	 * @param mixed  $metadata  Existing metadata (null = not intercepted).
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether to return a single value.
	 * @return mixed
	 */
	public function filter_post_meta( $metadata, $object_id, $meta_key, $single ) {
		if ( lsx_currencies()->convert_to_single && 'price' === $meta_key ) {
			$meta_cache = wp_cache_get( $object_id, 'post_meta' );
			if ( ! isset( $meta_cache[ $meta_key ] ) || '' === $meta_cache[ $meta_key ] ) {
				return '0';
			}
		}
		return $metadata;
	}
}
