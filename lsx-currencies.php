<?php
/**
 * Plugin Name: LSX Currencies
 * Plugin URI:  https://www.lsdev.biz/product/lsx-currencies
 * Description: Currency switcher for Tour Operator — lets visitors choose their preferred display currency. Requires WordPress 7.0+.
 * Version:     2.0.0
 * Author:      LightSpeed
 * Author URI:  https://www.lsdev.biz/
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: lsx-currencies
 * Domain Path: /languages
 * Requires at least: 7.0
 * Requires PHP: 8.0
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'LSX_CURRENCIES_PATH', plugin_dir_path( __FILE__ ) );
define( 'LSX_CURRENCIES_CORE', __FILE__ );
define( 'LSX_CURRENCIES_URL', plugin_dir_url( __FILE__ ) );
define( 'LSX_CURRENCIES_VER', '2.0.0' );

require_once LSX_CURRENCIES_PATH . 'classes/class-currencies.php';

/**
 * Returns the main plugin instance (singleton).
 *
 * @return \lsx\currencies\classes\Currencies
 */
function lsx_currencies() {
	return \lsx\currencies\classes\Currencies::init();
}

lsx_currencies();
