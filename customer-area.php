<?php
/*
Plugin Name: Customer Area
Plugin URI: http://www.marvinlabs.com
Version: 1.0.0
Description: Customer area give your customers the possibility to get a page on your site where they can access private content. 
Author: MarvinLabs
Author URI: http://www.marvinlabs.com
Text Domain: cuar
Domain Path: /languages
*/

/*  Copyright 2013 MarvinLabs (contact@marvinlabs.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

define( 'CUAR_PLUGIN_DIR', 		plugin_dir_path( __FILE__ ) );
define( 'CUAR_LANGUAGE_DIR', 	CUAR_PLUGIN_DIR . '/languages' );
define( 'CUAR_INCLUDES_DIR', 	CUAR_PLUGIN_DIR . '/includes' );

define( 'CUAR_PLUGIN_URL', 			plugin_dir_url( __FILE__ ) );
define( 'CUAR_SCRIPTS_URL', 		CUAR_PLUGIN_URL . '/scripts' );
define( 'CUAR_ADMIN_THEME_URL', 	CUAR_PLUGIN_URL . '/themes/admin/default' );
define( 'CUAR_FRONTEND_THEME_URL', 	CUAR_PLUGIN_URL . '/themes/frontend/default' );


/**
 * A function for debugging purposes
 */
if ( !function_exists( 'cuar_debug' ) ) {
function cuar_log_debug( $message ) {
	if (WP_DEBUG === true){
		if( is_array( $message ) || is_object( $message ) ){
			$msg = "CUAR \t" . print_r( $message, true );
		} else {
			$msg = "CUAR \t" . $message;
		}

		// ChromePhp::log( $msg );
		error_log( $msg );
	}
}
}

// Basic includes
include_once( CUAR_INCLUDES_DIR . '/plugin.class.php' );
include_once( CUAR_INCLUDES_DIR . '/theme-utils.class.php' );

// Core addons
include_once( CUAR_INCLUDES_DIR . '/core-addons/private-file/private-file-addon.class.php' );
include_once( CUAR_INCLUDES_DIR . '/core-addons/customer-page/customer-page-addon.class.php' );

// Start the plugin!
global $bsxg_plugin;
$bsxg_plugin = new CUAR_Plugin();
$bsxg_plugin->run();