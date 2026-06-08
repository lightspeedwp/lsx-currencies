<?php
/**
 * LSX Currency Main Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * The main plugin class.
 */
class Currencies {

	/**
	 * Singleton instance.
	 *
	 * @var \lsx\currencies\classes\Currencies
	 */
	private static $instance;

	/** @var \lsx\currencies\classes\Admin */
	public $admin;

	/** @var \lsx\currencies\classes\Frontend */
	public $frontend;

	/** @var \lsx\currencies\classes\Block */
	public $block;

	/** @var \lsx\currencies\classes\WooCommerce */
	public $woocommerce;

	/** @var \lsx\currencies\classes\FacetWP */
	public $facetwp;

	/** @var string */
	public $plugin_slug = 'lsx-currencies';

	/** @var string Exchange rates API endpoint */
	public $api_url = 'https://api.exchangeratesapi.io/latest?base=USD';

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

	/** @var bool */
	public $multi_prices = false;

	/** @var bool */
	public $convert_to_single = false;

	/** @var bool|string OpenExchange API key */
	public $app_id = false;

	/** @var bool */
	public $remove_decimals = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'plugins_loaded', array( $this, 'set_defaults' ) );
	}

	/**
	 * Return singleton instance.
	 *
	 * @return \lsx\currencies\classes\Currencies
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load sub-classes after all plugins are loaded.
	 */
	public function plugins_loaded() {
		require_once LSX_CURRENCIES_PATH . 'classes/class-admin.php';
		$this->admin = Admin::init();

		require_once LSX_CURRENCIES_PATH . 'classes/class-frontend.php';
		$this->frontend = Frontend::init();

		require_once LSX_CURRENCIES_PATH . 'classes/class-block.php';
		$this->block = Block::init();

		if ( class_exists( 'WooCommerce' ) ) {
			require_once LSX_CURRENCIES_PATH . 'classes/class-woocommerce.php';
			$this->woocommerce = WooCommerce::init();
		}

		if ( class_exists( 'FacetWP' ) ) {
			require_once LSX_CURRENCIES_PATH . 'classes/class-facetwp.php';
			$this->facetwp = FacetWP::init();
		}

		require_once LSX_CURRENCIES_PATH . 'includes/template-tags.php';
	}

	/**
	 * Read saved settings from lsx_to_settings (Tour Operator's option) and
	 * populate instance properties. Our fields are registered via
	 * Admin::add_settings_fields() so they are saved there alongside TO's own
	 * settings.
	 */
	public function set_defaults() {
		$options = get_option( 'lsx_to_settings', array() );

		// Base currency — uses the existing Tour Operator 'currency' field.
		if ( ! empty( $options['currency'] ) ) {
			$this->base_currency = apply_filters( 'lsx_currencies_base_currency', sanitize_key( $options['currency'] ), $this );
		}

		if ( defined( 'LSX_BASE_CURRENCY' ) ) {
			$this->base_currency = sanitize_key( \LSX_BASE_CURRENCY );
		}

		// Additional currencies — stored as a comma-separated string by the
		// multi-checkbox field rendered in Admin::currency_settings().
		if ( ! empty( $options['lsx_currencies_additional'] ) ) {
			$raw   = sanitize_text_field( $options['lsx_currencies_additional'] );
			$codes = array_filter( array_map( 'strtoupper', array_map( 'sanitize_key', explode( ',', $raw ) ) ) );
			if ( ! empty( $codes ) ) {
				$this->additional_currencies = array_combine( $codes, $codes );
			}
		}

		if ( ! empty( $options['lsx_currencies_multi_price'] ) ) {
			$this->multi_prices = (bool) $options['lsx_currencies_multi_price'];
		}

		if ( ! empty( $options['lsx_currencies_convert_to_single'] ) ) {
			$this->convert_to_single = (bool) $options['lsx_currencies_convert_to_single'];
		}

		if ( ! empty( $options['lsx_currencies_remove_decimals'] ) ) {
			$this->remove_decimals = (bool) $options['lsx_currencies_remove_decimals'];
		}

		if ( ! empty( $options['lsx_currencies_openexchange_api'] ) ) {
			$this->app_id  = sanitize_text_field( $options['lsx_currencies_openexchange_api'] );
			$this->api_url = esc_url_raw( 'https://openexchangerates.org/api/latest.json?app_id=' . $this->app_id );
		}

		$this->available_currencies = $this->get_available_currencies();
		$this->flag_relations       = $this->get_flag_relations();
		$this->currency_symbols     = $this->get_currency_symbols();
	}

	/**
	 * Returns the flag HTML span for a currency code.
	 *
	 * @param string $key ISO 4217 currency code.
	 * @return string
	 */
	public function get_currency_flag( $key = 'USD' ) {
		$key = strtoupper( sanitize_key( $key ) );
		if ( ! isset( $this->flag_relations[ $key ] ) ) {
			return '';
		}
		return '<span class="flag-icon flag-icon-' . esc_attr( $this->flag_relations[ $key ] ) . '"></span> ';
	}

	/**
	 * Returns the HTML symbol for a currency code.
	 *
	 * @param string $currency ISO 4217 code.
	 * @return string
	 */
	public function get_currency_symbol( $currency = '' ) {
		if ( ! $currency ) {
			$currency = $this->base_currency;
		}
		return isset( $this->currency_symbols[ $currency ] ) ? $this->currency_symbols[ $currency ] : '';
	}

	/**
	 * Returns the available currencies list. Paid currencies only shown when an
	 * OpenExchange API key is configured.
	 *
	 * @return array<string,string>
	 */
	public function get_available_currencies() {
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

		if ( false !== $this->app_id ) {
			$free_currencies = array_merge( $free_currencies, $paid_currencies );
			asort( $free_currencies );
		}

		return $free_currencies;
	}

	/**
	 * Returns ISO 4217 currency code → ISO 3166-1 alpha-2 country code mappings
	 * used for flag icons.
	 *
	 * @return array<string,string>
	 */
	public function get_flag_relations() {
		return array(
			'AED' => 'ae',
			'AUD' => 'au',
			'BRL' => 'br',
			'BWP' => 'bw',
			'CAD' => 'ca',
			'CHF' => 'ch',
			'CNY' => 'cn',
			'EUR' => 'eu',
			'GBP' => 'gb',
			'HKD' => 'hk',
			'IDR' => 'id',
			'ILS' => 'il',
			'INR' => 'in',
			'JPY' => 'jp',
			'KES' => 'ke',
			'LAK' => 'la',
			'MWK' => 'mw',
			'MYR' => 'my',
			'MZN' => 'mz',
			'NAD' => 'na',
			'NOK' => 'no',
			'NZD' => 'nz',
			'RUB' => 'ru',
			'SEK' => 'se',
			'SGD' => 'sg',
			'TZS' => 'tz',
			'USD' => 'us',
			'ZAR' => 'za',
			'ZMW' => 'zm',
			'ZWL' => 'zw',
		);
	}

	/**
	 * Returns all currency symbols as an HTML-entity-keyed array.
	 * Filterable via the `lsx_currencies_symbols` hook.
	 *
	 * @return array<string,string>
	 */
	public function get_currency_symbols() {
		return apply_filters(
			'lsx_currencies_symbols',
			array(
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
				'INR' => '&#8377;',
				'IQD' => '&#x639;.&#x62f;',
				'IRR' => '&#xfdfc;',
				'ISK' => 'kr.',
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
				'PYG' => '&#8370;',
				'QAR' => '&#x631;.&#x642;',
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
			)
		);
	}
}
