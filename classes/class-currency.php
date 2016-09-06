<?php
if (!class_exists( 'LSX_Currency' ) ) {
	/**
	 * LSX Currency Main Class
	 */
	class LSX_Currency {
		
		/** @var string */
		public $plugin_slug = 'lsx-currency';

		/** @var array */
		public $options = false;	

		/** @var array string */
		public $base_currency = 'USD';		

		/** @var array */
		public $additional_currencies = array();			

		/** @var array */
		public $available_currencies = array();	

		/** @var boolean */
		public $multi_prices = false;	

		/** @var obj  */
		public $rates = false;

		/** @var string  */
		public $current_currency = false;		

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->set_defaults();
			require_once(LSX_CURRENCY_PATH . '/classes/class-currency-admin.php');
			require_once(LSX_CURRENCY_PATH . '/classes/class-currency-frontend.php');
		}
		/**
		 * Get the options
		 */
		public function set_defaults() {
			$this->available_currencies = array(
				'AUD'	=> __('Australian Dollar',$this->plugin_slug),
				'BRL'	=> __('Brazilian Real',$this->plugin_slug),
				'GBP'	=> __('British Pound Sterling',$this->plugin_slug),
				'BWP'	=> __('Botswana Pula',$this->plugin_slug),
				'CAD'	=> __('Canadian Dollar',$this->plugin_slug),
				'CNY'	=> __('Chinese Yuan',$this->plugin_slug),
				'EUR'	=> __('Euro',$this->plugin_slug),
				'HKD'	=> __('Hong Kong Dollar',$this->plugin_slug),
				'INR'	=> __('Indian Rupee',$this->plugin_slug),
				'IDR'	=> __('Indonesia Rupiah',$this->plugin_slug),
				'ILS'	=> __('Israeli Shekel',$this->plugin_slug),
				'JPY'	=> __('Japanese Yen',$this->plugin_slug),
				'KES'	=> __('Kenyan shilling',$this->plugin_slug),
				'LAK'	=> __('Laos Kip',$this->plugin_slug),
				'MWK'	=> __('Malawian Kwacha',$this->plugin_slug),
				'MYR'	=> __('Malaysia Ringgit',$this->plugin_slug),
				'MZN'	=> __('Mozambique Metical',$this->plugin_slug),				
				'NAD'	=> __('Namibian Dollar',$this->plugin_slug),
				'NZD'	=> __('New Zealand Dollar',$this->plugin_slug),
				'NOK'	=> __('Norwegian Krone',$this->plugin_slug),
				'NZD'	=> __('New Zealand Dollar',$this->plugin_slug),
				'RUB'	=> __('Russian Ruble',$this->plugin_slug),				
				'SGD'	=> __('Singapore Dollar',$this->plugin_slug),
				'ZAR'	=> __('South African Rand',$this->plugin_slug),
				'SEK'	=> __('Swedish Krona',$this->plugin_slug),
				'CHF'	=> __('Swiss Franc',$this->plugin_slug),
				'TZS'	=> __('Tanzania Shilling',$this->plugin_slug),				
				'USD'	=> __('United States Dollar',$this->plugin_slug),
				'AED'	=> __('United Arab Emirates Dirham',$this->plugin_slug),
				'ZMW'	=> __('Zambian Kwacha',$this->plugin_slug),
				'ZWL'	=> __('Zimbabwean Dollar',$this->plugin_slug)
			);			

			$options = get_option('_lsx_lsx-settings',false);
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

				if(isset($this->options['general']['openexchange_api']) && '' !== $this->options['general']['openexchange_api']){
					$this->app_id = $this->options['general']['openexchange_api'];
				}else{
					$this->app_id = '756634695a6344e78adae48a6ba25a9d';
				}

				if ( false === ( $this->rates = get_transient( 'lsx_currency_rates' ) ) ) {
					$rates = wp_remote_retrieve_body( wp_safe_remote_get( 'http://openexchangerates.org/api/latest.json?app_id=' . $this->app_id ) );
					$decoded_rates = json_decode( $rates );	

					if ( is_wp_error( $rates ) || ! empty( $decoded_rates->error ) || empty( $rates ) ) {
						if ( 401 == $decoded_rates->status ) {
							$this->message = __('Your API key is incorrect.',$this->plugin_slug);
						}
					} else {
						set_transient( 'lsx_currency_rates', $rates, 60 * 60 * 2 );
						$this->rates = $decoded_rates->rates;
					}
				}

				print_r(json_decode( $this->rates ));	
				die();						
			}
			$this->current_currency = isset( $_COOKIE['lsx_currency_choice'] ) ? $_COOKIE['lsx_currency_choice'] : $this->base_currency;

		}
	}
	new LSX_Currency();
}