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

if (!class_exists('CUAR_PrivateFileFrontendInterface')) :

/**
 * Frontend interface for private files
 * 
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_PrivateFileFrontendInterface {
	
	public function __construct( $cuar_plugin, $private_file_addon ) {
		$this->plugin = $cuar_plugin;
		$this->private_file_addon = $private_file_addon;

		// Optionally output the file links in the post footer area
		if ( CUAR_PrivateFileAdminInterface::$OPTION_SHOW_AFTER_POST_CONTENT ) {
			add_filter( 'the_content', array( &$this, 'after_post_content' ), 3000 );
		}		
		
		add_action( 'cuar_customer_area_content', array( &$this, 'print_customer_area_content' ), 10 );

		add_filter( "get_previous_post_where", array( &$this, 'disable_single_post_navigation' ), 1, 3 );
		add_filter( "get_next_post_where", array( &$this, 'disable_single_post_navigation' ), 1, 3 );

		add_action( 'init', array( &$this, 'load_scripts' ) );
	}

	/*------- FUNCTIONS TO PRINT IN THE FRONTEND ---------------------------------------------------------------------*/
	
	public function after_post_content( $content ) {
		// If not on a matching post type, we do nothing
		if ( !is_singular('cuar_private_file') ) return $content;		

		ob_start();
		include( $this->plugin->get_template_file_path(
				CUAR_INCLUDES_DIR . '/core-addons/private-file',
				'private-file-after_post_content.template.php',
				'templates' ));	
  		$out = ob_get_contents();
  		ob_end_clean(); 
  		
  		return $content . $out;
	}

	public function print_customer_area_content() {
		include( $this->plugin->get_template_file_path(
				CUAR_INCLUDES_DIR . '/core-addons/private-file',
				'private-file-customer_area_user_files.template.php',
				'templates' ));			
	}

	/**
	 * Disable the navigation on the single page templates for private files
	 */
	// TODO improve this by getting the proper previous/next file for the same owner
	public function disable_single_post_navigation( $where, $in_same_cat, $excluded_categories ) {
		if ( get_post_type()=='cuar_private_file' )	return "WHERE 1=0";		
		return $where;
	}

	/**
	 * Loads the required javascript files (only when not in admin area)
	 */
	// TODO Load only on the customer area page
	public function load_scripts() {
		if ( is_admin() ) return;
		
		wp_enqueue_script( 'jquery-ui-accordion' );
	}
	
	/** @var CUAR_Plugin */
	private $plugin;

	/** @var CUAR_PrivateFileAddOn */
	private $private_file_addon;
}
	
endif; // if (!class_exists('CUAR_PrivateFileFrontendInterface')) :