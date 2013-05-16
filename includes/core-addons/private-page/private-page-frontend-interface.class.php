<?php
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

if (!class_exists('CUAR_PrivatePageFrontendInterface')) :

/**
 * Frontend interface for private files
 * 
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_PrivatePageFrontendInterface {
	
	public function __construct( $plugin, $private_page_addon ) {
		$this->plugin = $plugin;
		$this->private_page_addon = $private_page_addon;

		if ( $plugin->get_option( CUAR_PrivatePageAdminInterface::$OPTION_ENABLE_ADDON ) ) {			
			add_action( 'cuar_customer_area_content', array( &$this, 'print_customer_area_content' ), 10 );
	
			add_filter( "get_previous_post_where", array( &$this, 'disable_single_post_navigation' ), 1, 3 );
			add_filter( "get_next_post_where", array( &$this, 'disable_single_post_navigation' ), 1, 3 );
		}
	}

	/*------- FUNCTIONS TO PRINT IN THE FRONTEND ---------------------------------------------------------------------*/
	
	public function print_customer_area_content() {		
		include( $this->plugin->get_template_file_path(
				CUAR_INCLUDES_DIR . '/core-addons/private-page',
				"private-page-customer_area_user_pages.template.php",
				'templates' ));
	}

	/**
	 * Disable the navigation on the single page templates for private files
	 */
	// TODO improve this by getting the proper previous/next file for the same owner
	public function disable_single_post_navigation( $where, $in_same_cat, $excluded_categories ) {
		if ( get_post_type()=='cuar_private_page' )	return "WHERE 1=0";		
		return $where;
	}
	
	/** @var CUAR_Plugin */
	private $plugin;

	/** @var CUAR_PrivatePageAddOn */
	private $private_page_addon;
}
	
endif; // if (!class_exists('CUAR_PrivatePageFrontendInterface')) :