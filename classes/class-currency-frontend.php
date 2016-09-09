<?php
/**
 * LSX Currency Frontend Main Class
 */
class LSX_Currency_Frontend extends LSX_Currency{	

	/** @var obj  */
	public $rates = false;

	/** @var string  */
	public $current_currency = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();
		if(false !== $this->app_id){
			add_filter('lsx_custom_field_query',array($this,'price_filter'),20,5);
			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
			add_filter( 'wp_nav_menu_items', array( $this, 'wp_nav_menu_items_filter' ), 10, 2 );
		}
	}

	/**
	 * Constructor
	 */
	public function set_defaults() {
		parent::set_defaults();
		if(false !== $this->app_id && '' !== $this->app_id){
			//if ( false === ( $this->rates = get_transient( 'lsx_currency_rates' ) ) ) {
				$rates = wp_remote_retrieve_body( wp_safe_remote_get( 'http://openexchangerates.org/api/latest.json?app_id=' . $this->app_id ) );
				$decoded_rates = json_decode( $rates );	

				if ( is_wp_error( $rates ) || ! empty( $decoded_rates->error ) || empty( $rates ) ) {
					if ( 401 == $decoded_rates->status ) {
						$this->message = __('Your API key is incorrect.',$this->plugin_slug);
					}
				} else {
					set_transient( 'lsx_currency_rates', $decoded_rates->rates, 60 * 60 * 2 );
					$this->rates = $decoded_rates->rates;
				}
			//}	
		}
		$this->current_currency = isset( $_COOKIE['lsx_currency_choice'] ) ? $_COOKIE['lsx_currency_choice'] : $this->base_currency;	
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
		wp_enqueue_script( 'lsx-jquery-cookie', LSX_CURRENCY_URL.'/assets/js/cookie'.$min.'.js', array( 'jquery' ), '2.1.3', true );
		wp_enqueue_script( 'lsx_currency', LSX_CURRENCY_URL.'/assets/js/lsx-currency'.$min.'.js', array(
			'jquery',
			'lsx-moneyjs',
			'lsx-accountingjs',
			'lsx-jquery-cookie'
		), '1.0.0', true );

		$params = apply_filters( 'lsx_currency_js_params', array(
			'current_currency'       => $this->current_currency,
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
	 * Filter on the 'wp_nav_menu_items' hook, that potentially adds a currency switcher to the item of some menus.
	 *
	 * @param string $items
	 * @param object $args
	 *
	 * @return string
	 */
	function wp_nav_menu_items_filter( $items, $args ) {
		if ( false !== $this->options['general'] && false !== $this->menus && array_key_exists($args->theme_location,$this->menus) ) {
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
		$items .= '<a class="current symbol-'.$this->switcher_symbol_position.'" href="#'.strtolower($this->current_currency).'">';
		$items .= isset( $args->link_before ) ? $args->link_before : '';

		if(true === $this->display_flags && 'left' === $this->flag_position){$items .= $this->get_currency_flag($this->current_currency);}
		if('left' === $this->switcher_symbol_position){ $items .= '<span class="currency-icon '.strtolower($this->current_currency).'"></span>'; }

		$items .= $this->current_currency;

		if('right' === $this->switcher_symbol_position){ $items .= '<span class="currency-icon '.strtolower($this->current_currency).'"></span>'; }
		if(true === $this->display_flags && 'right' === $this->flag_position){$items .= $this->get_currency_flag($this->current_currency);}

		$items .= isset( $args->link_after ) ? $args->link_after : '';
		$items .= '<span class="caret"></span></a>';
		$items .= isset( $args->after ) ? $args->after : '';
		//unset( $languages[ $current_language ] );
		//
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
			$class='';
			if($this->current_currency === $key){
				$hidden='style="display:none";';
				$class='hidden';
			}

			$sub_items .= '<li '.$hidden.' class="menu-item menu-item-currency '.$this->switcher_symbol_position.'">';

			$sub_items .= '<a class=" symbol-'.$this->switcher_symbol_position.'" href="#'.strtolower($key).'">';
			if(true === $this->display_flags && 'left' === $this->flag_position){$sub_items .= $this->get_currency_flag($key);}
			if('left' === $this->switcher_symbol_position){ $sub_items .= '<span class="currency-icon '.strtolower($this->current_currency).'"></span>'; }		
			$sub_items .= ucwords($key);
			if('right' === $this->switcher_symbol_position){ $sub_items .= '<span class="currency-icon '.strtolower($this->current_currency).'"></span>'; }
			if(true === $this->display_flags && 'right' === $this->flag_position){$sub_items .= $this->get_currency_flag($key);}
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
	public function get_currency_flag($key='USD') {
		if(true === $this->display_flags){
			return '<span class="flag-icon flag-icon-'.$this->flag_relations[$key].'"></span> ';
		}
	}
}
new LSX_Currency_Frontend();