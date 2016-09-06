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

		/** @var array boolean */
		public $multi_prices = false;						

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->set_defaults();
			require_once(LSX_CURRENCY_PATH . '/classes/class-currency-admin.php');
		}
		/**
		 * Get the options
		 */
		public function set_defaults() {
			$this->available_currencies = array(
				'AUD'	=> __('Australian Dollar',$this->plugin_slug),
				'GBP'	=> __('British Pound',$this->plugin_slug),
				'CAD'	=> __('Canadian Dollar',$this->plugin_slug),
				'EUR'	=> __('Euro',$this->plugin_slug),
				'HKD'	=> __('Hong Kong Dollar',$this->plugin_slug),
				'NAD'	=> __('Namibian Dollar',$this->plugin_slug),
				'NZD'	=> __('New Zealand Dollar',$this->plugin_slug),
				'SGD'	=> __('Singapore Dollar',$this->plugin_slug),
				'ZAR'	=> __('South African Rand',$this->plugin_slug),
				'USD'	=> __('United States Dollar',$this->plugin_slug)
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
			}
		}
	}
	new LSX_Currency();
}