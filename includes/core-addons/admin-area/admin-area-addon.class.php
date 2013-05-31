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

require_once( CUAR_INCLUDES_DIR . '/addon.class.php' );

if (!class_exists('CUAR_AdminAreaAddOn')) :

/**
 * Add-on to organise the administration area (menus, ...) 
*
* @author Vincent Prat @ MarvinLabs
*/
class CUAR_AdminAreaAddOn extends CUAR_AddOn {
	
	public function __construct() {
		parent::__construct( 'admin-area', __( 'Administration Area', 'cuar' ), '2.0.0' );
	}

	public function run_addon( $plugin ) {
		$this->plugin = $plugin;
		
		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'build_admin_menu' ) );
			add_action( 'cuar_version_upgraded', array( &$this, 'plugin_version_upgrade' ), 10, 2 );
			add_filter( 'cuar_configurable_capability_groups', array( &$this, 'declare_configurable_capabilities' ) );			
		} 
	}

	/**
	 * Configurable capabilities
	 *  
	 * @param array $groups
	 * @return number
	 */
	public function declare_configurable_capabilities( $groups ) {
		$group = array(
				'group_name' => __( 'Administration Area', 'cuar' ), 
				'capabilities' => array( 
						'view-customer-area-menu' => __( 'View the menu', 'cuar' ) 
					) 
			);
		
		array_unshift( $groups, $group );
		return $groups;
	}

	/**
	 * Build the administration menu
	 */
	public function build_admin_menu() {
	    // Add the top-level admin menu
	    $page_title = __( 'Customer Area', 'cuar' );
	    $menu_title = __( 'Customer Area', 'cuar' );
	    $menu_slug = 'customer-area';
	    $capability = 'view-customer-area-menu';
	    $function = array( &$this, 'print_customer_area_dashboard' );
	    $icon = "";
	    $position = '2.1.cuar';
	    
	    $this->pagehook = add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon, $position );
	    
	    // Now add the submenu pages from add-ons
	    $submenu_items = apply_filters( 'cuar_admin_submenu_pages', array() );
	    
	    foreach ( $submenu_items as $item ) {
		    $submenu_page_title = $item[ 'page_title' ];
		    $submenu_title 		= $item[ 'title' ];
		    $submenu_slug 		= $item[ 'slug' ];
		    $submenu_function 	= $item[ 'function' ];
		    $submenu_capability = $item[ 'capability' ];
		    
		    add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, 
		    		$submenu_capability, $submenu_slug, $submenu_function);
	    }
	}

	public function print_customer_area_dashboard() {
		include( dirname( __FILE__ ) . '/templates/customer-area-dashboard.template.php' );
	}
	
	/**
	 * When the plugin is upgraded
	 * 
	 * @param unknown $from_version
	 * @param unknown $to_version
	 */
	public function plugin_version_upgrade( $from_version, $to_version ) {
		// If upgrading from before 1.5.0 we must add some caps to admin & editors
		if ( $from_version<'1.5.0' ) {
			$admin_role = get_role( 'administrator' );
			if ( $admin_role ) {
				$admin_role->add_cap( 'view-customer-area-menu' );
			}
			$editor_role = get_role( 'editor' );
			if ( $editor_role ) {
				$editor_role->add_cap( 'view-customer-area-menu' );
			}
		}
	}
	
	/** @var CUAR_Plugin */
	private $plugin;
	
	/** @var string */
	private $pagehook;
}

// Make sure the addon is loaded
global $cuar_admin_area_addon;
$cuar_admin_area_addon = new CUAR_AdminAreaAddOn();

endif; // if (!class_exists('CUAR_AdminAreaAddOn')) 
