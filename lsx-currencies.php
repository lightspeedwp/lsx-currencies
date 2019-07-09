<?php
/*
 * Plugin Name: LSX Currencies
 * Plugin URI:  https://www.lsdev.biz/product/lsx-currencies
 * Description: The LSX Currencies extension adds currency selection functionality to sites, allowing users to view your products in whatever currencies you choose to sell in.
 * Version:     1.2.0
 * Author:      LightSpeed
 * Author URI:  https://www.lsdev.biz/
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: lsx-currencies
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'LSX_CURRENCIES_PATH',  plugin_dir_path( __FILE__ ) );
define( 'LSX_CURRENCIES_CORE',  __FILE__ );
define( 'LSX_CURRENCIES_URL',  plugin_dir_url( __FILE__ ) );
define( 'LSX_CURRENCIES_VER',  '1.2.0' );

require_once( LSX_CURRENCIES_PATH . '/classes/class-lsx-currencies.php' );
