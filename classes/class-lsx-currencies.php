<?php
if (!class_exists( 'LSX_Currencies' ) ) {
	/**
	 * LSX Currency Main Class
	 */
	class LSX_Currencies {
		
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
			$this->set_defaults();
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		}

		/**
		 * After active plugins and pluggable functions are loaded
		 */
		public function plugins_loaded() {
			require_once( LSX_CURRENCY_PATH . 'classes/class-lsx-currencies-admin.php' );
			if ( class_exists( 'LSX_Currencies_Admin' ) ) {
				$this->admin = new LSX_Currencies_Admin();
			}

			require_once( LSX_CURRENCY_PATH . 'classes/class-lsx-currencies-frontend.php' );
			if ( class_exists( 'LSX_Currencies_Frontend' ) ) {
				$this->frontend = new LSX_Currencies_Frontend();
			}
		}

		/**
		 * Get the options
		 */
		public function set_defaults() {
			$this->available_currencies = array(
				'AUD'	=> esc_html__('Australian Dollar','lsx-currencies'),
				'BRL'	=> esc_html__('Brazilian Real','lsx-currencies'),
				'GBP'	=> esc_html__('British Pound Sterling','lsx-currencies'),
				'BWP'	=> esc_html__('Botswana Pula','lsx-currencies'),
				'CAD'	=> esc_html__('Canadian Dollar','lsx-currencies'),
				'CNY'	=> esc_html__('Chinese Yuan','lsx-currencies'),
				'EUR'	=> esc_html__('Euro','lsx-currencies'),
				'HKD'	=> esc_html__('Hong Kong Dollar','lsx-currencies'),
				'INR'	=> esc_html__('Indian Rupee','lsx-currencies'),
				'IDR'	=> esc_html__('Indonesia Rupiah','lsx-currencies'),
				'ILS'	=> esc_html__('Israeli Shekel','lsx-currencies'),
				'JPY'	=> esc_html__('Japanese Yen','lsx-currencies'),
				'KES'	=> esc_html__('Kenyan Shilling','lsx-currencies'),
				'LAK'	=> esc_html__('Laos Kip','lsx-currencies'),
				'MWK'	=> esc_html__('Malawian Kwacha','lsx-currencies'),
				'MYR'	=> esc_html__('Malaysia Ringgit','lsx-currencies'),
				'MZN'	=> esc_html__('Mozambique Metical','lsx-currencies'),				
				'NAD'	=> esc_html__('Namibian Dollar','lsx-currencies'),
				'NOK'	=> esc_html__('Norwegian Krone','lsx-currencies'),
				'NZD'	=> esc_html__('New Zealand Dollar','lsx-currencies'),
				'RUB'	=> esc_html__('Russian Ruble','lsx-currencies'),				
				'SGD'	=> esc_html__('Singapore Dollar','lsx-currencies'),
				'ZAR'	=> esc_html__('South African Rand','lsx-currencies'),
				'SEK'	=> esc_html__('Swedish Krona','lsx-currencies'),
				'CHF'	=> esc_html__('Swiss Franc','lsx-currencies'),
				'TZS'	=> esc_html__('Tanzania Shilling','lsx-currencies'),				
				'USD'	=> esc_html__('United States Dollar','lsx-currencies'),
				'AED'	=> esc_html__('United Arab Emirates Dirham','lsx-currencies'),
				'ZMW'	=> esc_html__('Zambian Kwacha','lsx-currencies'),
				'ZWL'	=> esc_html__('Zimbabwean Dollar','lsx-currencies')
			);	
			$this->flag_relations = array(
				'AUD'	=> 'au',
				'BRL'	=> 'br',
				'GBP'	=> 'gb',
				'BWP'	=> 'bw',
				'CAD'	=> 'ca',
				'CNY'	=> 'cn',
				'EUR'	=> 'eu',
				'HKD'	=> 'hk',
				'INR'	=> 'in',
				'IDR'	=> 'id',
				'ILS'	=> 'il',
				'JPY'	=> 'jp',
				'KES'	=> 'ke',
				'LAK'	=> 'la',
				'MWK'	=> 'mw',
				'MYR'	=> 'my',
				'MZN'	=> 'mz',				
				'NAD'	=> 'na',
				'NZD'	=> 'nz',
				'NOK'	=> 'no',
				'RUB'	=> 'ru',				
				'SGD'	=> 'sg',
				'ZAR'	=> 'za',
				'SEK'	=> 'se',
				'CHF'	=> 'ch',
				'TZS'	=> 'tz',				
				'USD'	=> 'us',
				'AED'	=> 'ae',
				'ZMW'	=> 'zm',
				'ZWL'	=> 'zw'			
			);					

			if ( class_exists( 'Tour_Operator' ) ) {
				$options = get_option('_to_settings',false);
			} else {
				$options = get_option('_lsx_settings',false);
				if ( false === $options ) {
					$options = get_option('_lsx_lsx-settings',false);
				}
			}
			
			if(false !== $options){
				$this->options = $options;	

				if(isset($this->options['general']) && isset($this->options['general']['currency'])){
					$this->base_currency = $this->options['general']['currency'];
				}

				if(isset($this->options['general']['additional_currencies']) && is_array($this->options['general']['additional_currencies']) && !empty($this->options['general']['additional_currencies'])){
					$this->additional_currencies = $this->options['general']['additional_currencies'];
				}

				if(isset($this->options['general']['multi_price']) && 'on' === $this->options['general']['multi_price']){
					$this->multi_prices = true;
				}	

				if(isset($this->options['api']['openexchange_api']) && '' !== $this->options['api']['openexchange_api']){
					$this->app_id = $this->options['api']['openexchange_api'];
				}

				//Currency Switcher Options
				
				if(isset($this->options['display']['currency_menu_switcher']) && is_array($this->options['display']['currency_menu_switcher']) && !empty($this->options['display']['currency_menu_switcher'])){
					$this->menus = $this->options['display']['currency_menu_switcher'];
				}

				if(isset($this->options['display']['display_flags']) && 'on' === $this->options['display']['display_flags']){
					$this->display_flags = true;
				}

				if(isset($this->options['display']['flag_position']) && 'on' === $this->options['display']['flag_position']){
					$this->flag_position = 'right';
				}

				if(isset($this->options['display']['currency_switcher_position']) && 'on' === $this->options['display']['currency_switcher_position']){
					$this->switcher_symbol_position = 'left';
				}							
			}
		}

		/**
		 * Returns Currency Flag for currency code provided
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		public function get_currency_flag($key='USD') {
			return '<span class="flag-icon flag-icon-'.$this->flag_relations[$key].'"></span> ';
		}		
	}
}