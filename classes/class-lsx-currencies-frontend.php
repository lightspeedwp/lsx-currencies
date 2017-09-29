<?php
/**
 * LSX Currency Frontend Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2016 LightSpeed
 */
class LSX_Currencies_Frontend extends LSX_Currencies {

	public $rates = false;
	public $rates_message = false;
	public $current_currency = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			$this->set_defaults();

			if ( false !== $this->app_id ) {
				add_filter( 'lsx_to_custom_field_query', array( $this, 'price_filter' ), 20, 5 );
				add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 999 );
				add_filter( 'wp_nav_menu_items', array( $this, 'wp_nav_menu_items_filter' ), 10, 2 );
				add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );
			}
		}
	}

	/**
	 * Constructor
	 */
	public function set_defaults() {
		parent::set_defaults();

		$this->rates_message = esc_html__( 'Error: API key isn\'t set.', 'lsx-currencies' );

		if ( false !== $this->app_id && '' !== $this->app_id ) {
			$this->rates = get_transient( 'lsx_currencies_rates' );

			if ( false === $this->rates ) {
				$rates = wp_remote_retrieve_body( wp_safe_remote_get( 'http://openexchangerates.org/api/latest.json?app_id=' . $this->app_id ) );
				$decoded_rates = json_decode( $rates );

				if ( is_wp_error( $rates ) ) {
					$this->rates_message = $rates->get_error_message();
				} elseif ( ! empty( $decoded_rates->error ) ) {
					$this->rates_message = $decoded_rates->description;
				} elseif ( empty( $rates ) ) {
					$this->rates_message = esc_html__( 'Error: API response is empty.', 'lsx-currencies' );
				} else {
					$this->rates_message = esc_html__( 'Success (new request).', 'lsx-currencies' );
					set_transient( 'lsx_currencies_rates', $decoded_rates->rates, 60 * 60 * 2 );
					$this->rates = $decoded_rates->rates;
				}
			} else {
				$this->rates_message = esc_html__( 'Success (from cache).', 'lsx-currencies' );
			}
		}

		$this->current_currency = isset( $_COOKIE['lsx_currencies_choice'] ) ? $_COOKIE['lsx_currencies_choice'] : $this->base_currency;
	}

	/**
	 * Enques the assets
	 */
	public function assets() {
		wp_enqueue_script( 'lsx-moneyjs', LSX_CURRENCIES_URL . 'assets/js/vendor/money.min.js' , array( 'jquery' ), LSX_CURRENCIES_VER, true );
		wp_enqueue_script( 'lsx-accountingjs', LSX_CURRENCIES_URL . 'assets/js/vendor/accounting.min.js', array( 'jquery' ), LSX_CURRENCIES_VER, true );
		wp_enqueue_script( 'lsx-jquery-cookie', LSX_CURRENCIES_URL . 'assets/js/vendor/cookie.min.js', array( 'jquery' ), LSX_CURRENCIES_VER, true );

		wp_enqueue_script( 'lsx-currencies', LSX_CURRENCIES_URL . 'assets/js/lsx-currencies.min.js', array( 'jquery', 'lsx-moneyjs', 'lsx-accountingjs', 'lsx-jquery-cookie' ), LSX_CURRENCIES_VER, true );

		$params = apply_filters( 'lsx_currencies_js_params', array(
			'current_currency' => $this->current_currency,
			'rates'            => $this->rates,
			'rates_message'    => $this->rates_message,
			'base'             => $this->base_currency,
			'flags'            => $this->display_flags,
		));

		wp_localize_script( 'lsx-currencies', 'lsx_currencies_params', $params );

		wp_enqueue_style( 'lsx-currencies', LSX_CURRENCIES_URL . 'assets/css/lsx-currencies.css', array(), LSX_CURRENCIES_VER );
		wp_style_add_data( 'lsx-currencies', 'rtl', 'replace' );
	}

	/**
	 * Adds in the required currency conversion tags
	 */
	public function price_filter( $return_html, $meta_key, $value, $before, $after ) {
		if ( 'price' === $meta_key ) {
			$additional_html = '';
			$additional_prices = get_post_meta( get_the_ID(), 'additional_prices', false );
			$prefix = '<span class="amount lsx-currencies" ';

			if ( true === $this->multi_prices && ! empty( $additional_prices ) ) {
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

			$prefix .= '>';
			$suffix = '</span>';

			setlocale( LC_MONETARY, 'en_US' );

			// Work out the other tags
			$currency = '<span class="currency-icon ' . mb_strtolower( $this->base_currency ) . '">' . $this->base_currency . '</span>';
			$amount = '<span class="value" data-price-' . $this->base_currency . '="' . $value . '" ' . $additional_html . '>' . str_replace( 'USD', '', money_format( '%i', ltrim( rtrim( $value ) ) ) ) . '</span>';

			// Check for a price type and add that in.
			$price_type = get_post_meta( get_the_ID(), 'price_type', true );

			switch ( $price_type ) {
				case 'per_person_per_night':
				case 'per_person_sharing':
				case 'per_person_sharing_per_night':
					$amount = $currency . $amount . ' ' . ucwords( str_replace( '_', ' ', $price_type ) );
				break;

				case 'total_percentage':
					$amount .= '% ' . esc_html__( 'Off','lsx-currencies' );
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
	 * @param string $items
	 * @param object $args
	 *
	 * @return string
	 */
	function wp_nav_menu_items_filter( $items, $args ) {
		if ( false !== $this->options['general'] && false !== $this->menus && array_key_exists( $args->theme_location,$this->menus ) ) {
			$items .= $this->get_menu_html( $args );
		}

		return $items;
	}

	/**
	 * Returns the HTML string of the language switcher for a given menu.
	 *
	 * @param object $args
	 *
	 * @return string
	 */
	private function get_menu_html( $args ) {
		if ( empty( $this->additional_currencies ) ) {
			return '';
		}

		$items = '';
		$items .= '<li class="menu-item menu-item-currency menu-item-currency-current menu-item-has-children dropdown">';
		$items .= isset( $args->before ) ? $args->before : '';
		$items .= '<a class="current symbol-' . $this->switcher_symbol_position . '" href="#' . strtolower( $this->current_currency ) . '">';
		$items .= isset( $args->link_before ) ? $args->link_before : '';

		if ( true === $this->display_flags && 'left' === $this->flag_position ) {
			$items .= $this->get_currency_flag( $this->current_currency );
		}

		if ( 'left' === $this->switcher_symbol_position ) {
			$items .= '<span class="currency-icon ' . strtolower( $this->current_currency ) . '"></span>';
		}

		$items .= $this->current_currency;

		if ( 'right' === $this->switcher_symbol_position ) {
			$items .= '<span class="currency-icon ' . strtolower( $this->current_currency ) . '"></span>';
		}

		if ( true === $this->display_flags && 'right' === $this->flag_position ) {
			$items .= $this->get_currency_flag( $this->current_currency );
		}

		$items .= isset( $args->link_after ) ? $args->link_after : '';
		$items .= '<span class="caret"></span></a>';
		$items .= isset( $args->after ) ? $args->after : '';
		//unset( $languages[ $current_language ] );
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

		foreach ( $this->additional_currencies as $key => $currency ) {
			$hidden = '';
			$class = '';

			if ( $this->current_currency === $key ) {
				$hidden = 'style="display:none";';
				$class = 'hidden';
			}

			$sub_items .= '<li ' . $hidden . ' class="' . $class . ' menu-item menu-item-currency ' . $this->switcher_symbol_position . '">';
			$sub_items .= '<a class=" symbol-' . $this->switcher_symbol_position . '" href="#' . strtolower( $key ) . '">';

			if ( true === $this->display_flags && 'left' === $this->flag_position ) {
				$sub_items .= $this->get_currency_flag( $key );
			}

			if ( 'left' === $this->switcher_symbol_position ) {
				$sub_items .= '<span class="currency-icon ' . strtolower( $key ) . '"></span>';
			}

			$sub_items .= ucwords( $key );

			if ( 'right' === $this->switcher_symbol_position ) {
				$sub_items .= '<span class="currency-icon ' . strtolower( $key ) . '"></span>';
			}

			if ( true === $this->display_flags && 'right' === $this->flag_position ) {
				$sub_items .= $this->get_currency_flag( $key );
			}

			$sub_items .= '</a></li>';
		}

		$sub_items = '<ul class="sub-menu submenu-currency dropdown-menu">' . $sub_items . '</ul>';
		return $sub_items;
	}

	/**
	 * Returns Currency Flag for currency code provided
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function get_currency_flag( $key = 'USD' ) {
		if ( true === $this->display_flags ) {
			return '<span class="flag-icon flag-icon-' . $this->flag_relations[ $key ] . '"></span> ';
		}
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

}
