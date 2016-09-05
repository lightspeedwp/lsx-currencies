<?php
/*
 * Plugin Name: LSX Currency
 * Plugin URI: https://www.lsdev.biz/product/lsx-currency-plugin/
 * Description: By integrating with LSX Tourism Operators plugin, LSX Currency brings multi currency support to your WordPress website.
 * Author: LightSpeed
 * Version: 1.0.0
 * Author URI: https://www.lsdev.biz/products/
 * License: GPL2+
 * Text Domain: lsx-currency
 * Domain Path: /languages/
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
 *	Grabs the email and api key from the LSX TO Settings.
 */ 
function lsx_currency_options_pages_filter($pages){
	$pages[] = 'lsx-lsx-settings';
	return $pages;
}
add_filter('lsx_api_manager_options_pages','lsx_currency_options_pages_filter',10,1);

function lsx_currency_get_api_details(){
	$options = get_option('_lsx_lsx-settings',false);
	$data = array('api_key'=>'','email'=>'');

	if(false !== $options && isset($options['general'])){
		if(isset($options['general']['lsx-currency_api_key']) && '' !== $options['general']['lsx-currency_api_key']){
			$data['api_key'] = $options['general']['lsx-tour-operators_api_key'];
		}
		if(isset($options['general']['lsx-currency_email']) && '' !== $options['general']['lsx-currency_email']){
			$data['email'] = $options['general']['lsx-currency_email'];
		}		
	}

	$api_array = array(
		'product_id'	=>		'LSX Currency',
		'version'		=>		'1.0.0',
		'instance'		=>		get_option('lsx_to_api_instance',false),
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
    $lsx_to_password = get_option('lsx_to_api_instance',false);
    if(false === $lsx_to_password){
    	update_option('lsx_to_api_instance',LSX_API_Manager::generatePassword());
    }
}
register_activation_hook( __FILE__, 'lsx_currency_activate_plugin' );

require_once( LSX_CURRENCY_PATH . 'module.php' );