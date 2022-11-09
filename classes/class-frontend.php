<?php
/**
 * LSX Currency Frontend Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2019 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * The frontend classes
 */
class Frontend {

	/**
	 * Holds instance of the class
	 *
	 * @var object \lsx\currencies\classes\Admin()
	 */
	private static $instance;

	/**
	 * This will hold the rates with a base currency of USD.
	 *
	 * @var boolean
	 */
	public $rates = false;

	/**
	 * This will hold the rates error message.
	 *
	 * @var boolean
	 */
	public $rates_message = false;

	/**
	 * This is the current currency selected, default to the base currency.
	 *
	 * @var boolean
	 */
	public $current_currency = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			add_action( 'after_setup_theme', array( $this, 'set_defaults' ), 11, 1 );
			add_filter( 'lsx_to_custom_field_query', array( $this, 'price_filter' ), 20, 5 );
			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 5 );
			add_filter( 'wp_nav_menu_items', array( $this, 'wp_nav_menu_items_filter' ), 10, 2 );
			add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );
			add_filter( 'get_post_metadata', array( $this, 'filter_post_meta' ), 100, 4 );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return  object
	 */
	public static function init() {
		// If the single instance hasn't been set, set it now.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function set_defaults() {
		$this->rates_message = esc_html__( 'Error: API key isn\'t set.', 'lsx-currencies' );
		$this->rates = get_transient( 'lsx_currencies_rates' );
		if ( false === $this->rates ) {
			$rates         = wp_remote_retrieve_body( wp_safe_remote_get( lsx_currencies()->api_url ) );
			$decoded_rates = json_decode( $rates );

			if ( is_wp_error( $rates ) ) {
				$this->rates_message = $rates->get_error_message();
			} elseif ( ! empty( $decoded_rates->error ) ) {
				$this->rates_message = $decoded_rates->description;
			} elseif ( empty( $rates ) ) {
				$this->rates_message = esc_html__( 'Error: API response is empty.', 'lsx-currencies' );
			} else {
				$this->rates_message = esc_html__( 'Success (new request).', 'lsx-currencies' );
				set_transient( 'lsx_currencies_rates', $decoded_rates->rates, 60 * 60 * 12 );
				do_action( 'lsx_currencies_rates_refreshed' );
				$this->rates = $decoded_rates->rates;
			}
		} else {
			$this->rates_message = esc_html__( 'Success (from cache).', 'lsx-currencies' );
		}
		$this->current_currency = isset( $_COOKIE['lsx_currencies_choice'] ) ? sanitize_key( $_COOKIE['lsx_currencies_choice'] ) : lsx_currencies()->base_currency;
		$this->current_currency = strtoupper( $this->current_currency );
	}

	/**
	 * Enques the assets
	 */
	public function assets() {
		wp_enqueue_script( 'lsx-moneyjs', LSX_CURRENCIES_URL . 'assets/js/vendor/money.min.js', array( 'jquery' ), LSX_CURRENCIES_VER, true );
		wp_enqueue_script( 'lsx-accountingjs', LSX_CURRENCIES_URL . 'assets/js/vendor/accounting.min.js', array( 'jquery' ), LSX_CURRENCIES_VER, true );
		wp_enqueue_script( 'lsx-jquery-cookie', LSX_CURRENCIES_URL . 'assets/js/vendor/cookie.min.js', array( 'jquery' ), LSX_CURRENCIES_VER, true );

		$prefix = '.min';
		$src = '';
		$script_debug = false;
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) {
			$prefix = '';
			$src = 'src/';
			$script_debug = true;
		}
		wp_enqueue_script( 'lsx-currencies', LSX_CURRENCIES_URL . 'assets/js/' . $src . 'lsx-currencies' . $prefix . '.js', array( 'jquery', 'lsx-moneyjs', 'lsx-accountingjs', 'lsx-jquery-cookie' ), LSX_CURRENCIES_VER, true );

		$base_currency = lsx_currencies()->base_currency;
		$current_currency = $this->current_currency;
		if ( true === lsx_currencies()->convert_to_single ) {
			$current_currency = $base_currency;
		}

		$params = apply_filters( 'lsx_currencies_js_params', array(
			'current_currency'  => $current_currency,
			'currency_symbols'  => $this->get_available_symbols(),
			'rates'             => $this->rates,
			'rates_message'     => $this->rates_message,
			'base'              => $base_currency,
			'flags'             => lsx_currencies()->display_flags,
			'convert_to_single' => lsx_currencies()->convert_to_single,
			'script_debug'      => $script_debug,
			'remove_decimals'   => lsx_currencies()->remove_decimals,
		));

		wp_localize_script( 'lsx-currencies', 'lsx_currencies_params', $params );

		wp_enqueue_style( 'lsx-currencies', LSX_CURRENCIES_URL . 'assets/css/lsx-currencies.css', array(), LSX_CURRENCIES_VER );
		wp_style_add_data( 'lsx-currencies', 'rtl', 'replace' );
	}

	/**
	 * Returns all of the available symbols.
	 *
	 * @return array
	 */
	public function get_available_symbols() {
		$symbols = array();
		if ( false !== lsx_currencies()->additional_currencies && ! empty( lsx_currencies()->additional_currencies ) ) {
			foreach ( lsx_currencies()->additional_currencies as $key => $currency ) {
				$symbols[ $key ] = lsx_currencies()->currency_symbols[ $key ];
			}
		}
		return $symbols;
	}

	/**
	 * Adds in the required currency conversion tags
	 */
	public function price_filter( $return_html, $meta_key, $value, $before, $after ) {
		if ( 'price' === $meta_key ) {
			$additional_html = '';
			$additional_prices = get_post_meta( get_the_ID(), 'additional_prices', false );
			$prefix = '<span class="amount lsx-currencies" ';

			if ( true === lsx_currencies()->multi_prices && ! empty( $additional_prices ) ) {
				foreach ( $additional_prices as $a_price ) {
					$additional_html .= ' data-price-' . $a_price['currency'] . '="' . $a_price['amount'] . '"';
				}
			}

			$value = preg_replace( '/[^0-9.]+/', '', $value );
			$decimals = substr_count( $value, '.' );

			if ( false !== $decimals && $decimals > 1 ) {
				$decimals--;
				$decimals = (int) $decimals;
				$value = preg_replace( '/' . preg_quote( '.', '/' ) . '/', '', $value, $decimals );
			}

			$money_format = 2;
			if ( false !== lsx_currencies()->remove_decimals ) {
				$money_format = 0;
			}

			$prefix .= '>';
			$suffix = '</span>';

			setlocale( LC_MONETARY, 'en_US' );

			// Set the prices to use the base currency set on the tour.
			$currency      = lsx_currencies()->base_currency;
			$tour_currency = get_post_meta( get_the_ID(), 'currency', true );
			if ( false !== $tour_currency && '' !== $tour_currency ) {
				$currency = strtoupper( $tour_currency );
			}

			// Work out the other tags.
			$currency = '<span class="currency-icon ' . mb_strtolower( $currency ) . '">' . $currency . '</span>';

			$value = ltrim( rtrim( $value ) );
			
			$for_value = number_format( (float) $value, $money_format );
			$for_value = str_replace( array( '$', 'USD' ), '', $for_value );
			
			$amount = '<span class="value" data-price-' . $currency . '="' . trim( str_replace( array( '$', 'USD' ), '', $value ) ) . '" ' . $additional_html . '>' . str_replace( array( '$', 'USD' ), '', $for_value ) . '</span>';

			// Check for a price type and add that in.
			$price_type = get_post_meta( get_the_ID(), 'price_type', true );

			switch ( $price_type ) {
				case 'per_person_per_night':
				case 'per_person_sharing':
				case 'per_person_sharing_per_night':
					$amount = $currency . $amount . ' ' . ucwords( str_replace( '_', ' ', $price_type ) );
				    break;

				case 'total_percentage':
					$amount .= '% ' . esc_html__( 'Off', 'lsx-currencies' );
					$before = str_replace( 'from', '', $before );
				    break;

				case 'none':
				default:
					$amount = $currency . $amount;
				    break;
			}

			$return_html = $before . $prefix . $amount . $suffix . $after;
		}

		return $return_html;
	}

	/**
	 * Filter on the 'wp_nav_menu_items' hook, that potentially adds a currency switcher to the item of some menus.
	 *
	 * @param $items string
	 * @param $args object
	 *
	 * @return string
	 */
	public function wp_nav_menu_items_filter( $items, $args ) {
		if ( '' !== lsx_currencies()->menus && lsx_currencies()->menus === $args->theme_location ) {
			if ( 'top-menu' === $args->theme_location ) {
				$items = $this->get_menu_html( $args ) . $items;
			} else {
				$items = $items . $this->get_menu_html( $args );
			}
		}
		return $items;
	}

	/**
	 * Returns the HTML string of the language switcher for a given menu.
	 *
	 * @param $args object
	 *
	 * @return string
	 */
	private function get_menu_html( $args ) {
		if ( empty( lsx_currencies()->additional_currencies ) ) {
			return '';
		}

		$items = '';
		$items .= '<li class="menu-item menu-item-currency menu-item-currency-current menu-item-has-children dropdown">';
		$items .= isset( $args->before ) ? $args->before : '';
		$items .= '<a class="current symbol-' . lsx_currencies()->switcher_symbol_position . '" href="#' . strtolower( $this->current_currency ) . '">';
		$items .= isset( $args->link_before ) ? $args->link_before : '';

		if ( ! empty( lsx_currencies()->display_flags ) && 'left' === lsx_currencies()->flag_position ) {
			$items .= lsx_currencies()->get_currency_flag( $this->current_currency );
		}

		if ( 'left' === lsx_currencies()->switcher_symbol_position ) {
			$items .= '<span class="currency-icon ' . strtolower( $this->current_currency ) . '"></span>';
		}

		$items .= $this->current_currency;

		if ( 'right' === lsx_currencies()->switcher_symbol_position ) {
			$items .= '<span class="currency-icon ' . strtolower( $this->current_currency ) . '"></span>';
		}

		if ( ! empty( lsx_currencies()->display_flags ) && 'right' === lsx_currencies()->flag_position ) {
			$items .= lsx_currencies()->get_currency_flag( $this->current_currency );
		}

		$items .= isset( $args->link_after ) ? $args->link_after : '';
		$items .= '<span class="caret"></span></a>';
		$items .= isset( $args->after ) ? $args->after : '';
		$items .= $this->render_sub_items();
		$items .= '</li>';
		return $items;
	}
	/**
	 * Returns the HTML string of the language switcher for a given menu.
	 *
	 * @param object $args
	 *
	 * @return string
	 */
	private function render_sub_items() {
		$sub_items = '';
		$additional_currencies = apply_filters( 'lsx_currencies_nav_additional_items', lsx_currencies()->additional_currencies );
		foreach ( $additional_currencies as $key => $currency ) {
			$hidden = '';
			$class = '';

			if ( $this->current_currency === $key ) {
				$hidden = 'style="display:none";';
				$class = 'hidden';
			}

			$sub_items .= '<li ' . $hidden . ' class="' . $class . ' menu-item menu-item-currency ' . lsx_currencies()->switcher_symbol_position . '">';
			$sub_items .= '<a class=" symbol-' . lsx_currencies()->switcher_symbol_position . '" href="#' . strtolower( $key ) . '">';

			if ( ! empty( lsx_currencies()->display_flags ) && 'left' === lsx_currencies()->flag_position ) {
				$sub_items .= lsx_currencies()->get_currency_flag( $key );
			}

			if ( 'left' === lsx_currencies()->switcher_symbol_position ) {
				$sub_items .= '<span class="currency-icon ' . strtolower( $key ) . '"></span>';
			}

			$sub_items .= ucwords( $key );

			if ( 'right' === lsx_currencies()->switcher_symbol_position ) {
				$sub_items .= '<span class="currency-icon ' . strtolower( $key ) . '"></span>';
			}

			if ( ! empty( lsx_currencies()->display_flags ) && 'right' === lsx_currencies()->flag_position ) {
				$sub_items .= lsx_currencies()->get_currency_flag( $key );
			}

			$sub_items .= '</a></li>';
		}

		$sub_items = '<ul class="sub-menu submenu-currency dropdown-menu">' . $sub_items . '</ul>';
		return $sub_items;
	}

	/**
	 * Allow data params for Slick slider addon.
	 */
	public function wp_kses_allowed_html( $allowedtags, $context ) {
		if ( ! isset( $allowedtags['span'] ) ) {
			$allowedtags['span'] = array();
		}

		$allowedtags['span']['data-price-AUD'] = true;
		$allowedtags['span']['data-price-BRL'] = true;
		$allowedtags['span']['data-price-GBP'] = true;
		$allowedtags['span']['data-price-BWP'] = true;
		$allowedtags['span']['data-price-CAD'] = true;
		$allowedtags['span']['data-price-CNY'] = true;
		$allowedtags['span']['data-price-EUR'] = true;
		$allowedtags['span']['data-price-HKD'] = true;
		$allowedtags['span']['data-price-INR'] = true;
		$allowedtags['span']['data-price-IDR'] = true;
		$allowedtags['span']['data-price-ILS'] = true;
		$allowedtags['span']['data-price-JPY'] = true;
		$allowedtags['span']['data-price-KES'] = true;
		$allowedtags['span']['data-price-LAK'] = true;
		$allowedtags['span']['data-price-MWK'] = true;
		$allowedtags['span']['data-price-MYR'] = true;
		$allowedtags['span']['data-price-MZN'] = true;
		$allowedtags['span']['data-price-NAD'] = true;
		$allowedtags['span']['data-price-NZD'] = true;
		$allowedtags['span']['data-price-NOK'] = true;
		$allowedtags['span']['data-price-RUB'] = true;
		$allowedtags['span']['data-price-SGD'] = true;
		$allowedtags['span']['data-price-ZAR'] = true;
		$allowedtags['span']['data-price-SEK'] = true;
		$allowedtags['span']['data-price-CHF'] = true;
		$allowedtags['span']['data-price-TZS'] = true;
		$allowedtags['span']['data-price-USD'] = true;
		$allowedtags['span']['data-price-AED'] = true;
		$allowedtags['span']['data-price-ZMW'] = true;
		$allowedtags['span']['data-price-ZWL'] = true;

		return $allowedtags;
	}

	/**
	 * Allow empty prices if the convert to single currency is active.
	 *
	 * @param null $metadata
	 * @param string $object_id
	 * @param string $meta_key
	 * @param boolean $single
	 * @return void
	 */
	public function filter_post_meta( $metadata = null, $object_id, $meta_key, $single ) {
		if ( true === lsx_currencies()->convert_to_single && 'price' === $meta_key ) {
			$meta_cache = wp_cache_get( $object_id, 'post_meta' );

			if ( ! isset( $meta_cache[ $meta_key ] ) || '' === $meta_cache[ $meta_key ] ) {
				$metadata = '0';
			}
		}
		return $metadata;
	}
}
