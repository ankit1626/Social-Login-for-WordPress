<?php
/**
 * This is the entry point.
 *
 * @package social login
 */

/**
 * Plugin Name: Social Login
 * Description: This plugin allows you to setup sso via google and fb as IDP(Identity Provider).
 * Author: Ankit Parekh
 * Version: 1.0.0
 * Author URI: https://ankitparekh.in/
 * Textdomain: d3v-social-login
 */


define( 'D3V_PLUGIN_VER', '1.0.0' );

/**
 * This function is trigged whenever an object is instantiated.
 *
 * @param string $classname Classname along with namespace.
 */
function my_autoloader( $classname ) {
	$file_name = strtolower( str_replace( '\\', DIRECTORY_SEPARATOR, $classname ) ) . '.php';
	$file_name = str_replace( '_', '-', $file_name );
	$file_path = __DIR__ . '/' . $file_name;
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

spl_autoload_register( 'my_autoloader' );


new Admin\Class_Social_Login_Admin();
new Admin\Class_Social_Signup_Login();
new Admin\Class_Social_Login_Profile();
new Public\Class_Social_Login_Page_Customizer();
