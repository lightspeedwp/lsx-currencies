<?php
/**
 * LSX Currencies Block Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Handles block registration and frontend asset enqueueing for price conversion.
 */
class Block {

	/**
	 * Singleton instance.
	 *
	 * @var \lsx\currencies\classes\Block
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Return singleton instance.
	 *
	 * @return \lsx\currencies\classes\Block
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register the currency-switcher block type from its block.json.
	 */
	public function register_block() {
		$block_dir = LSX_CURRENCIES_PATH . 'build/blocks/currency-switcher';

		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $block_dir );

		// Pass runtime data to view.js via script module (available after build).
		add_action( 'wp_enqueue_scripts', array( $this, 'pass_params_to_view' ), 20 );
	}

	/**
	 * Localise the JS params that view.js needs for currency conversion.
	 * Hooked at priority 20 so Frontend::set_defaults() has already populated rates.
	 */
	public function pass_params_to_view() {
		$frontend = lsx_currencies()->frontend;

		if ( ! $frontend instanceof Frontend ) {
			return;
		}

		$base_currency    = lsx_currencies()->base_currency;
		$current_currency = $frontend->current_currency ?: $base_currency;

		if ( lsx_currencies()->convert_to_single ) {
			$current_currency = $base_currency;
		}

		$params = apply_filters(
			'lsx_currencies_js_params',
			array(
				'base'              => $base_currency,
				'current'           => $current_currency,
				'rates'             => $frontend->rates ?: new \stdClass(),
				'symbols'           => $frontend->get_available_symbols(),
				'removeDecimals'    => lsx_currencies()->remove_decimals,
				'convertToSingle'   => lsx_currencies()->convert_to_single,
				'ratesMessage'      => $frontend->rates_message,
			)
		);

		// money.js and accounting.js are still needed on the page for price conversion.
		wp_enqueue_script( 'lsx-moneyjs', LSX_CURRENCIES_URL . 'assets/js/vendor/money.min.js', array(), LSX_CURRENCIES_VER, true );
		wp_enqueue_script( 'lsx-accountingjs', LSX_CURRENCIES_URL . 'assets/js/vendor/accounting.min.js', array(), LSX_CURRENCIES_VER, true );

		// Pass params to the block's view script.
		$handle = 'lsx-currencies-currency-switcher-view-script';
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_add_inline_script(
				$handle,
				'var lsx_currencies_params = ' . wp_json_encode( $params ) . ';',
				'before'
			);
		}
	}
}
