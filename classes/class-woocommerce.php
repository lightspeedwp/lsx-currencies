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
		add_filter( 'lsx_currencies_base_currency', array( $this, 'set_base_currency' ), 10, 1 );
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
			$return = str_replace( 'class', 'data-price-' . lsx_currencies()->base_currency . '=' . $price . ' class', $return );
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

	/**
	 * Make sure our base currency is set to the same as woocommerce.
	 *
	 * @param string $currency
	 * @return void
	 */
	public function set_base_currency( $currency ) {
		return get_woocommerce_currency();
	}
}
