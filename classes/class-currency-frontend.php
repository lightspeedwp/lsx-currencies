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
	}

	/**
	 * Adds in the required currency conversion tags
	 */
	public function price_filter($return_html,$meta_key,$value,$before,$after) {

		if('price' === $meta_key){

			$prefix = '<span class="amount" data-base-currency="'.$this->base_currency.'"';

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