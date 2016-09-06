<?php
/**
 * LSX Currency Frontend Main Class
 */
class LSX_Currency_Frontend extends LSX_Currency{	

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();
		add_filter('lsx_custom_field_query',array($this,'price_filter'),20,5);

		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Enques the assets
	 */
	public function assets() {

		if(defined('WP_DEBUG') && true === WP_DEBUG){
			$min='';
		}else{
			$min = '.min';
		}

		wp_enqueue_script( 'lsx-moneyjs', LSX_CURRENCY_URL.'/assets/js/money'.$min.'.js' , array( 'jquery' ), '0.2.0', true );
		wp_enqueue_script( 'lsx-accountingjs', LSX_CURRENCY_URL.'/assets/js/accounting'.$min.'.js', array( 'jquery' ), '0.4.1', true );
		wp_enqueue_script( 'lsx_currency', LSX_CURRENCY_URL.'/assets/js/lsx-currency'.$min.'.js', array(
			'jquery',
			'lsx-moneyjs',
			'lsx-accountingjs',
			//'jquery-cookie'
		), '1.0.0', true );

		$params = apply_filters( 'lsx_currency_js_params', array(
			'current_currency'       => isset( $_COOKIE['lsx_current_currency'] ) ? $_COOKIE['lsx_current_currency'] : '',
			'rates'                  => $this->rates,
			'base'                   => $this->base_currency
		));
		wp_localize_script( 'lsx_currency', 'lsx_currency_params', $params );		
	}

	/**
	 * Adds in the required currency conversion tags
	 */
	public function price_filter($return_html,$meta_key,$value,$before,$after) {

		if('price' === $meta_key){

			$prefix = '<span class="amount lsx-currency" data-base-currency="'.$this->base_currency.'"';

			if(true === $this->multi_prices && !empty($this->additional_currencies)){

			}

			$prefix .= '>';
			$suffix = '</span>';

			//work out the other tags
			$currency = '<span class="currency-icon '. mb_strtolower( $this->base_currency ) .'">'. $this->base_currency .'</span>';
			$amount = '<span class="value">'.$value.'</span>';

			//Check for a price type and add that in.
			$price_type = get_post_meta(get_the_ID(),'price_type',true);
			switch($price_type){

				case 'per_person_per_night':
				case 'per_person_sharing':
				case 'per_person_sharing_per_night':
					$amount = $currency.$amount.' '.ucwords(str_replace('_',' ',$price_type));
				break;

				case 'total_percentage':
					$amount .= '% '.__('Off',$this->plugin_slug);
					$before = str_replace('from', '', $before);
				break;

				case 'none':
				default:
					$amount = $currency.$amount;
				break;
			}

			$return_html = $before.$prefix.$amount.$suffix.$after;
		}
		return $return_html;
	}	
}
new LSX_Currency_Frontend();