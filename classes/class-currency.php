<?php
if (!class_exists( 'LSX_Currency' ) ) {
	/**
	 * LSX Currency Main Class
	 */
	class LSX_Currency {
		
		/** @var array Rates */
		public $plugin_slug = 'lsx-currency';

		/** @var array Rates */
		public $options = false;	

		/** @var array Rates */
		public $available_currencies = array(
			'USD'	=> __('USD (united states dollar)',$this->plugin_slug),
			'GBP'	=> __('GBP (british pound)',$this->plugin_slug),
			'ZAR'	=> __('ZAR (south african rand)',$this->plugin_slug),
			'NAD'	=> __('NAD (namibian dollar)',$this->plugin_slug),
			'CAD'	=> __('CAD (canadian dollar)',$this->plugin_slug),
			'EUR'	=> __('EUR (euro)',$this->plugin_slug),
			'HKD'	=> __('HKD (hong kong dollar)',$this->plugin_slug),
			'SGD'	=> __('SGD (singapore dollar)',$this->plugin_slug),
			'NZD'	=> __('NZD (new zealand dollar)',$this->plugin_slug),
			'AUD'	=> __('AUD (australian dollar)',$this->plugin_slug)
		);			

		/**
		 * Constructor
		 */
		public function __construct() {
			require_once(LSX_CURRENCY_PATH . '/classes/class-currency-admin.php');
		}

		/**
		 * Get the options
		 */
		public function get_options() {
			if(false === $this->options){
				$options = get_option('_lsx_lsx-settings',false);
				if(false !== $options)){
					$this->options = $options;	
				}	
				return $this->options;		
			}else{
				return $this->options;
			}
		}

		/**
		 * get the active currencies
		 */
		public function get_active_currencies() {
			$active_currencies = false;
			if(false !== $this->options && isset($this->options['general']) && isset($this->options['general']['currency'])){
				$base_currency = $this->options['general']['currency'];
			}
			else{
				$active_currencies = array('USD');
			}
			return $active_currencies;
		}		

	}
	new LSX_Currency();
}