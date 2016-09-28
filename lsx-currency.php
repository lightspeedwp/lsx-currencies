<?php
/*
 * Plugin Name: Tour Operator Currencies 
 * Plugin URI:  https://www.lsdev.biz/product/tour-operator-currencies/
 * Description: The Tour Operator Currencies extension adds currency selection functionality to sites, allowing users to view your products in whatever currencies you choose to sell in. 
 * Version:     1.0
 * Author:      LightSpeed WordPress Development
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

define('LSX_CURRENCY_PATH',  plugin_dir_path( __FILE__ ) );
define('LSX_CURRENCY_CORE',  __FILE__ );
define('LSX_CURRENCY_URL',  plugin_dir_url( __FILE__ ) );
define('LSX_CURRENCY_VER',  '1.0.0' );

if(!class_exists('LSX_API_Manager')){
	require_once('vendor/lsx-api-manager-class.php');
}

/** 
 *	Grabs the email and api key from the LSX Currency Settings.
 */ 
function lsx_currency_options_pages_filter($pages){
	$pages[] = 'lsx-lsx-settings';
	return $pages;
}
add_filter('lsx_api_manager_options_pages','lsx_currency_options_pages_filter',10,1);

function lsx_currency_api_admin_init(){
	$options = get_option('_lsx_lsx-settings',false);
	$data = array('api_key'=>'','email'=>'');

	if(false !== $options && isset($options['general'])){
		if(isset($options['general']['lsx-currency_api_key']) && '' !== $options['general']['lsx-currency_api_key']){
			$data['api_key'] = $options['general']['lsx-currency_api_key'];
		}
		if(isset($options['general']['lsx-currency_email']) && '' !== $options['general']['lsx-currency_email']){
			$data['email'] = $options['general']['lsx-currency_email'];
		}		
	}

	$api_array = array(
		'product_id'	=>		'LSX Currency',
		'version'		=>		'1.0.0',
		'instance'		=>		get_option('lsx_api_instance',false),
		'email'			=>		$data['email'],
		'api_key'		=>		$data['api_key'],
		'file'			=>		'lsx-currency.php'
	);
	$lsx_to_api_manager = new LSX_API_Manager($api_array);
}
add_action('admin_init','lsx_currency_api_admin_init');

/**
 * Run when the plugin is active, and generate a unique password for the site instance.
 */
function lsx_currency_activate_plugin() {
    $lsx_to_password = get_option('lsx_api_instance',false);
    if(false === $lsx_to_password){
    	update_option('lsx_api_instance',LSX_API_Manager::generatePassword());
    }
}
register_activation_hook( __FILE__, 'lsx_currency_activate_plugin' );

/* ======================= Below is the Plugin Class init ========================= */

require_once( LSX_CURRENCY_PATH . '/classes/class-currency.php' );