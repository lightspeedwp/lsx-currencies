<?php
/**
 * LSX Currency Frontend Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2016 LightSpeed
 */
class LSX_Currencies_WooCommerce {

	public $currency = false;

	/**
	 * Holds instance of the class
	 */
	private static $instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'wc_price', array( $this, 'price_filter' ), 300, 3 );
		//add_filter( 'woocommerce_cart_subtotal', array( $this, 'cart_subtotal' ), 10, 3 );
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
	 * Set the current base currency
	 * @param $currency
	 */
	public function set_currency( $currency ) {
		$this->currency = $currency;
	}

	/**
	 * @param $return
	 * @param $price
	 * @param $args
	 *
	 * @return mixed
	 */
	public function price_filter( $return, $price, $args ) {
		if ( '' !== $price ) {
			$return = str_replace( 'class', 'data-price-' . $this->currency . '=' . $price . ' class', $return );
			$return = str_replace( 'woocommerce-Price-amount', 'woocommerce-Price-amount lsx-currencies', $return );
		}
		return $return;
	}

	/**
	 * @param $cart_subtotal
	 * @param $compound
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function cart_subtotal( $cart_subtotal, $compound, $obj ) {

		$return = str_replace( 'class', 'data-price-' . $this->currency . '=' . $price . ' class', $return );
		$return = str_replace( 'woocommerce-Price-amount', 'woocommerce-Price-amount lsx-currencies', $return );

		return $cart_subtotal;
	}


}
