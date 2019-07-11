<?php
/**
 * LSX Currency Main Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2019 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * The main class
 */
class Currencies {

	/**
	 * Holds instance of the class
	 *
	 * @var object \lsx\currencies\classes\Currencies()
	 */
	private static $instance;

	/**
	 * Holds the admin instance
	 *
	 * @var object \lsx\currencies\classes\Admin()
	 */
	public $admin;

	/**
	 * Holds the frontend instance
	 *
	 * @var object \lsx\currencies\classes\Frontedn()
	 */
	public $frontend;

	/**
	 * Holds the woocommerce instance
	 *
	 * @var object \lsx\currencies\classes\WooCommerce()
	 */
	public $woocommerce;

	/**
	 * This hold the URL, it defaults to the free exchange rates.
	 *
	 * @var string
	 */
	public $api_url = 'https://api.exchangeratesapi.io/latest?base=USD';

	/**
	 * General Parameters
	 */
	/** @var string */
	public $plugin_slug = 'lsx-currencies';

	/** @var array */
	public $options = false;

	/** @var string */
	public $base_currency = 'USD';

	/** @var array */
	public $additional_currencies = array();

	/** @var array */
	public $available_currencies = array();

	/** @var array */
	public $flag_relations = array();

	/** @var array */
	public $currency_symbols = array();

	/** @var boolean */
	public $multi_prices = false;

	/** @var boolean */
	public $app_id = false;

	/*  Currency Switcher Options */
	/** @var array */
	public $menus = false;

	/** @var boolean */
	public $display_flags = false;

	/** @var string */
	public $flag_position = 'left';

	/** @var string */
	public $switcher_symbol_position = 'right';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'set_defaults' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
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
	 * After active plugins and pluggable functions are loaded
	 */
	public function plugins_loaded() {
		require_once LSX_CURRENCIES_PATH . 'classes/class-admin.php';
		$this->admin = \lsx\currencies\classes\Admin::init();

		require_once LSX_CURRENCIES_PATH . 'classes/class-frontend.php';
		$this->frontend = \lsx\currencies\classes\Frontend::init();

		if ( class_exists( 'WooCommerce' ) ) {
			require_once LSX_CURRENCIES_PATH . 'classes/class-woocommerce.php';
			$this->woocommerce = \lsx\currencies\classes\WooCommerce::init();
			$this->woocommerce->set_currency( $this->base_currency );
		}
	}

	/**
	 * Get the options
	 */
	public function set_defaults() {
		if ( function_exists( 'tour_operator' ) ) {
			$options = get_option( '_lsx-to_settings', false );
		} else {
			$options = get_option( '_lsx_settings', false );

			if ( false === $options ) {
				$options = get_option( '_lsx_lsx-settings', false );
			}
		}

		if ( false !== $options ) {
			$this->options = $options;
			$this->migration_uix_to_customize();

			if ( isset( $this->options['general'] ) && isset( $this->options['general']['currency'] ) ) {
				$this->base_currency = apply_filters( 'lsx_currencies_base_currency', $this->options['general']['currency'], $this );
			}

			if ( isset( $this->options['general']['additional_currencies'] ) && is_array( $this->options['general']['additional_currencies'] ) && ! empty( $this->options['general']['additional_currencies'] ) ) {
				$this->additional_currencies = $this->options['general']['additional_currencies'];
			}

			if ( isset( $this->options['general']['multi_price'] ) && 'on' === $this->options['general']['multi_price'] ) {
				$this->multi_prices = true;
			}

			if ( isset( $this->options['api']['openexchange_api'] ) && '' !== $this->options['api']['openexchange_api'] ) {
				$this->app_id = $this->options['api']['openexchange_api'];
				$this->api_url = 'http://openexchangerates.org/api/latest.json?app_id=' . $this->app_id;
			}

			// Currency Switcher Options.
			$this->menus = get_theme_mod( 'lsx_currencies_currency_menu_position', false );

			if ( get_theme_mod( 'lsx_currencies_display_flags', false ) ) {
				$this->display_flags = true;
			}

			if ( get_theme_mod( 'lsx_currencies_flag_position', false ) ) {
				$this->flag_position = 'right';
			}

			if ( get_theme_mod( 'lsx_currencies_currency_switcher_position', false ) ) {
				$this->switcher_symbol_position = 'left';
			}
		}
		$this->available_currencies = $this->get_available_currencies();
		$this->flag_relations = $this->get_flag_relations();
		$this->currency_symbols = $this->get_currency_symbols();		
	}

	/**
	 * Returns Currency Flag for currency code provided
	 *
	 * @param $key string
	 * @return string
	 */
	public function get_currency_flag( $key = 'USD' ) {
		return '<span class="flag-icon flag-icon-' . $this->flag_relations[ $key ] . '"></span> ';
	}

	/**
	 * Get Currency symbol.
	 *
	 * @param string $currency
	 * @return string
	 */
	public function get_currency_symbol( $currency = '' ) {
		if ( ! $currency ) {
			$currency = $this->base_currency;
		}
		$currency_symbol = isset( $this->currency_symbols[ $currency ] ) ? $this->currency_symbols[ $currency ] : '';
		return $currency_symbol;
	}

	/**
	 * Returns an array of the available currencies
	 *
	 * @return array
	 */
	public function get_available_currencies() {

		$paid_currencies = array(
			'BWP' => esc_html__( 'Botswana Pula', 'lsx-currencies' ),
			'KES' => esc_html__( 'Kenyan Shilling', 'lsx-currencies' ),
			'LAK' => esc_html__( 'Laos Kip', 'lsx-currencies' ),
			'MWK' => esc_html__( 'Malawian Kwacha', 'lsx-currencies' ),
			'MZN' => esc_html__( 'Mozambique Metical', 'lsx-currencies' ),
			'NAD' => esc_html__( 'Namibian Dollar', 'lsx-currencies' ),
			'TZS' => esc_html__( 'Tanzania Shilling', 'lsx-currencies' ),
			'AED' => esc_html__( 'United Arab Emirates Dirham', 'lsx-currencies' ),
			'ZMW' => esc_html__( 'Zambian Kwacha', 'lsx-currencies' ),
			'ZWL' => esc_html__( 'Zimbabwean Dollar', 'lsx-currencies' ),
		);
		$free_currencies = array(
			'AUD' => esc_html__( 'Australian Dollar', 'lsx-currencies' ),
			'BRL' => esc_html__( 'Brazilian Real', 'lsx-currencies' ),
			'GBP' => esc_html__( 'British Pound Sterling', 'lsx-currencies' ),
			'CAD' => esc_html__( 'Canadian Dollar', 'lsx-currencies' ),
			'CNY' => esc_html__( 'Chinese Yuan', 'lsx-currencies' ),
			'EUR' => esc_html__( 'Euro', 'lsx-currencies' ),
			'HKD' => esc_html__( 'Hong Kong Dollar', 'lsx-currencies' ),
			'INR' => esc_html__( 'Indian Rupee', 'lsx-currencies' ),
			'IDR' => esc_html__( 'Indonesia Rupiah', 'lsx-currencies' ),
			'ILS' => esc_html__( 'Israeli Shekel', 'lsx-currencies' ),
			'JPY' => esc_html__( 'Japanese Yen', 'lsx-currencies' ),
			'MYR' => esc_html__( 'Malaysia Ringgit', 'lsx-currencies' ),
			'NOK' => esc_html__( 'Norwegian Krone', 'lsx-currencies' ),
			'NZD' => esc_html__( 'New Zealand Dollar', 'lsx-currencies' ),
			'RUB' => esc_html__( 'Russian Ruble', 'lsx-currencies' ),
			'SGD' => esc_html__( 'Singapore Dollar', 'lsx-currencies' ),
			'ZAR' => esc_html__( 'South African Rand', 'lsx-currencies' ),
			'SEK' => esc_html__( 'Swedish Krona', 'lsx-currencies' ),
			'CHF' => esc_html__( 'Swiss Franc', 'lsx-currencies' ),
			'USD' => esc_html__( 'United States Dollar', 'lsx-currencies' ),
		);

		if ( false !== $this->app_id ) {
			$free_currencies = array_merge( $free_currencies, $paid_currencies );
			asort( $free_currencies );
		}

		return $free_currencies;
	}

	/**
	 * Returns the ISO 3 code in relation to its 2 code values.
	 *
	 * @return array
	 */
	public function get_flag_relations() {
		return array(
			'AUD' => 'au',
			'BRL' => 'br',
			'GBP' => 'gb',
			'BWP' => 'bw',
			'CAD' => 'ca',
			'CNY' => 'cn',
			'EUR' => 'eu',
			'HKD' => 'hk',
			'INR' => 'in',
			'IDR' => 'id',
			'ILS' => 'il',
			'JPY' => 'jp',
			'KES' => 'ke',
			'LAK' => 'la',
			'MWK' => 'mw',
			'MYR' => 'my',
			'MZN' => 'mz',
			'NAD' => 'na',
			'NZD' => 'nz',
			'NOK' => 'no',
			'RUB' => 'ru',
			'SGD' => 'sg',
			'ZAR' => 'za',
			'SEK' => 'se',
			'CHF' => 'ch',
			'TZS' => 'tz',
			'USD' => 'us',
			'AED' => 'ae',
			'ZMW' => 'zm',
			'ZWL' => 'zw',
		);
	}

	/**
	 * Returns all of the currency symbols.
	 *
	 * @return array
	 */
	public function get_currency_symbols() {
		return apply_filters( 'lsx_currencies_symbols', array(
			'AED' => '&#x62f;.&#x625;',
			'AFN' => '&#x60b;',
			'ALL' => 'L',
			'AMD' => 'AMD',
			'ANG' => '&fnof;',
			'AOA' => 'Kz',
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => 'Afl.',
			'AZN' => 'AZN',
			'BAM' => 'KM',
			'BBD' => '&#36;',
			'BDT' => '&#2547;&nbsp;',
			'BGN' => '&#1083;&#1074;.',
			'BHD' => '.&#x62f;.&#x628;',
			'BIF' => 'Fr',
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => 'Bs.',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTC' => '&#3647;',
			'BTN' => 'Nu.',
			'BWP' => 'P',
			'BYR' => 'Br',
			'BZD' => '&#36;',
			'CAD' => '&#36;',
			'CDF' => 'Fr',
			'CHF' => '&#67;&#72;&#70;',
			'CLP' => '&#36;',
			'CNY' => '&yen;',
			'COP' => '&#36;',
			'CRC' => '&#x20a1;',
			'CUC' => '&#36;',
			'CUP' => '&#36;',
			'CVE' => '&#36;',
			'CZK' => '&#75;&#269;',
			'DJF' => 'Fr',
			'DKK' => 'DKK',
			'DOP' => 'RD&#36;',
			'DZD' => '&#x62f;.&#x62c;',
			'EGP' => 'EGP',
			'ERN' => 'Nfk',
			'ETB' => 'Br',
			'EUR' => '&euro;',
			'FJD' => '&#36;',
			'FKP' => '&pound;',
			'GBP' => '&pound;',
			'GEL' => '&#x10da;',
			'GGP' => '&pound;',
			'GHS' => '&#x20b5;',
			'GIP' => '&pound;',
			'GMD' => 'D',
			'GNF' => 'Fr',
			'GTQ' => 'Q',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => 'L',
			'HRK' => 'Kn',
			'HTG' => 'G',
			'HUF' => '&#70;&#116;',
			'IDR' => 'Rp',
			'ILS' => '&#8362;',
			'IMP' => '&pound;',
			'INR' => '&#8377;',
			'IQD' => '&#x639;.&#x62f;',
			'IRR' => '&#xfdfc;',
			'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
			'ISK' => 'kr.',
			'JEP' => '&pound;',
			'JMD' => '&#36;',
			'JOD' => '&#x62f;.&#x627;',
			'JPY' => '&yen;',
			'KES' => 'KSh',
			'KGS' => '&#x441;&#x43e;&#x43c;',
			'KHR' => '&#x17db;',
			'KMF' => 'Fr',
			'KPW' => '&#x20a9;',
			'KRW' => '&#8361;',
			'KWD' => '&#x62f;.&#x643;',
			'KYD' => '&#36;',
			'KZT' => 'KZT',
			'LAK' => '&#8365;',
			'LBP' => '&#x644;.&#x644;',
			'LKR' => '&#xdbb;&#xdd4;',
			'LRD' => '&#36;',
			'LSL' => 'L',
			'LYD' => '&#x644;.&#x62f;',
			'MAD' => '&#x62f;.&#x645;.',
			'MDL' => 'MDL',
			'MGA' => 'Ar',
			'MKD' => '&#x434;&#x435;&#x43d;',
			'MMK' => 'Ks',
			'MNT' => '&#x20ae;',
			'MOP' => 'P',
			'MRO' => 'UM',
			'MUR' => '&#x20a8;',
			'MVR' => '.&#x783;',
			'MWK' => 'MK',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => 'MT',
			'NAD' => '&#36;',
			'NGN' => '&#8358;',
			'NIO' => 'C&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#x631;.&#x639;.',
			'PAB' => 'B/.',
			'PEN' => 'S/.',
			'PGK' => 'K',
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PRB' => '&#x440;.',
			'PYG' => '&#8370;',
			'QAR' => '&#x631;.&#x642;',
			'RMB' => '&yen;',
			'RON' => 'lei',
			'RSD' => '&#x434;&#x438;&#x43d;.',
			'RUB' => '&#8381;',
			'RWF' => 'Fr',
			'SAR' => '&#x631;.&#x633;',
			'SBD' => '&#36;',
			'SCR' => '&#x20a8;',
			'SDG' => '&#x62c;.&#x633;.',
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&pound;',
			'SLL' => 'Le',
			'SOS' => 'Sh',
			'SRD' => '&#36;',
			'SSP' => '&pound;',
			'STD' => 'Db',
			'SYP' => '&#x644;.&#x633;',
			'SZL' => 'L',
			'THB' => '&#3647;',
			'TJS' => '&#x405;&#x41c;',
			'TMT' => 'm',
			'TND' => '&#x62f;.&#x62a;',
			'TOP' => 'T&#36;',
			'TRY' => '&#8378;',
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => 'Sh',
			'UAH' => '&#8372;',
			'UGX' => 'UGX',
			'USD' => '&#36;',
			'UYU' => '&#36;',
			'UZS' => 'UZS',
			'VEF' => 'Bs F',
			'VND' => '&#8363;',
			'VUV' => 'Vt',
			'WST' => 'T',
			'XAF' => 'Fr',
			'XCD' => '&#36;',
			'XOF' => 'Fr',
			'XPF' => 'Fr',
			'YER' => '&#xfdfc;',
			'ZAR' => '&#82;',
			'ZMW' => 'ZK',
		) );
	}

	/**
	 * Sanitize checkbox.
	 *
	 * @param $input html
	 * @return mixed
	 */
	public function sanitize_checkbox( $input ) {
		return ( 1 === absint( $input ) ) ? 1 : 0;
	}

	/**
	 * Sanitize select.
	 *
	 * @param $input html
	 * @return mixed
	 */
	public function sanitize_select( $input ) {
		if ( is_string( $input ) || is_integer( $input ) || is_bool( $input ) ) {
			return $input;
		} else {
			return '';
		}
	}

	/**
	 * Sanitize textarea.
	 *
	 * @param $input html
	 * @return mixed
	 */
	public function sanitize_textarea( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * Migrate the old data (from UIX) to WP Customizer settings.
	 *
	 * @since 1.1.1
	 */
	public function migration_uix_to_customize() {
		$visual_tab_migration = get_theme_mod( 'lsx_currencies_visual_tab_migration', false );

		if ( empty( $visual_tab_migration ) ) {
			if ( isset( $this->options['display'] ) ) {
				if ( isset( $this->options['display']['currency_menu_switcher'] ) && is_array( $this->options['display']['currency_menu_switcher'] ) && ! empty( $this->options['display']['currency_menu_switcher'] ) ) {
					$currency_menu_position = $this->options['display']['currency_menu_switcher'];

					foreach ( $currency_menu_position as $key => $value ) {
						set_theme_mod( 'lsx_currencies_currency_menu_position', $key );
						break;
					}
				}

				if ( isset( $this->options['display']['display_flags'] ) && 'on' === $this->options['display']['display_flags'] ) {
					set_theme_mod( 'lsx_currencies_display_flags', true );
				}

				if ( isset( $this->options['display']['flag_position'] ) && 'on' === $this->options['display']['flag_position'] ) {
					set_theme_mod( 'lsx_currencies_flag_position', 'right' );
				}

				if ( isset( $this->options['display']['currency_switcher_position'] ) && 'on' === $this->options['display']['currency_switcher_position'] ) {
					set_theme_mod( 'lsx_currencies_currency_switcher_position', 'left' );
				}
			}

			set_theme_mod( 'lsx_currencies_visual_tab_migration', true );
		}
	}
}
