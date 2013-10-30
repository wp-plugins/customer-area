<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CUAR_TemplateFunctions' ) ) :

/**
 * Gathers some helper functions to facilitate some theme customisations
*/
class CUAR_TemplateFunctions {

	public static function print_customer_area_menu() {
		global $cuar_plugin;
		$cp_addon = $cuar_plugin->get_addon( 'customer-page' ); 
		$cp_addon->print_main_menu_on_single_private_content();
	}
}

endif; // class_exists CUAR_TemplateFunctions