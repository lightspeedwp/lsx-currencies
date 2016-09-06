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

		add_filter( 'wp_nav_menu_items', array( $this, 'wp_nav_menu_items_filter' ), 10, 2 );
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

	/**
	 * Filter on the 'wp_nav_menu_items' hook, that potentially adds a language switcher to the item of some menus.
	 *
	 * @param string $items
	 * @param object $args
	 *
	 * @return string
	 */
	function wp_nav_menu_items_filter( $items, $args ) {
		if ( $args->theme_location === 'primary' && false !== $this->options['general'] && isset($this->options['general']['currency_menu_switcher']) ) {
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
		$items .= '<li class="menu-item menu-item-language menu-item-language-current menu-item-has-children">';
		$items .= isset( $args->before ) ? $args->before : '';
		$items .= '<a href="#" onclick="return false">';
		$items .= isset( $args->link_before ) ? $args->link_before : '';
		$items .= $this->base_currency;
		$items .= isset( $args->link_after ) ? $args->link_after : '';
		$items .= '</a>';
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
			$sub_items .= '<li class="menu-item menu-item-currency">';
			$sub_items .= '<a href="#'.$key.'">';
			$sub_items .= ucwords($key);
			$sub_items .= '</a></li>';
		}

		$sub_items = '<ul class="sub-menu submenu-currency dropdown-menu">' . $sub_items . '</ul>';
		return $sub_items;		
	}			
}
new LSX_Currency_Frontend();