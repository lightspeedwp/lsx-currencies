<?php
/**
 * LSX Currency Frontend Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2019 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Holds the WooCommerce Integrations
 */
class WooCommerce {

	/**
	 * Holds instance of the class
	 *
	 * @var object \lsx\currencies\classes\WooCommerce()
	 */
	private static $instance;

	/**
	 * Holds the current currency.
	 *
	 * @var boolean
	 */
	public $currency = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'wc_price', array( $this, 'price_filter' ), 300, 3 );
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
	 * Filter the WooCommerce Price.
	 *
	 * @param $return mixed
	 * @param $price string
	 * @param $args array
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
