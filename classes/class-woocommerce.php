<?php
/**
 * LSX Currencies WooCommerce Integration
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Injects currency conversion data attributes into WooCommerce price output.
 */
class WooCommerce {

	/**
	 * Singleton instance.
	 *
	 * @var \lsx\currencies\classes\WooCommerce
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wc_price', array( $this, 'price_filter' ), 300, 3 );
		add_filter( 'lsx_currencies_base_currency', array( $this, 'set_base_currency' ), 10, 1 );
	}

	/**
	 * Return singleton instance.
	 *
	 * @return \lsx\currencies\classes\WooCommerce
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Adds a data-price-{CURRENCY} attribute and lsx-currencies class to WooCommerce
	 * price HTML so the frontend JS can perform currency conversion on it.
	 *
	 * @param string $return Rendered price HTML.
	 * @param float  $price  Raw price value.
	 * @param array  $args   wc_price() arguments.
	 * @return string
	 */
	public function price_filter( $return, $price, $args ) {
		if ( '' === (string) $price ) {
			return $return;
		}

		$currency  = esc_attr( lsx_currencies()->base_currency );
		$raw_price = esc_attr( (float) $price );

		// Inject data attribute before the existing class attribute.
		$return = str_replace(
			'class="woocommerce-Price-amount',
			'data-price-' . $currency . '="' . $raw_price . '" class="woocommerce-Price-amount lsx-currencies',
			$return
		);

		return $return;
	}

	/**
	 * Sync the LSX Currencies base currency to the WooCommerce store currency.
	 *
	 * @param string $currency Current base currency code.
	 * @return string
	 */
	public function set_base_currency( $currency ) {
		return get_woocommerce_currency();
	}
}
