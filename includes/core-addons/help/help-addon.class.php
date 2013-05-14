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

if (!class_exists('CUAR_HelpAddOn')) :

/**
 * Add-on to put private files in the customer area
*
* @author Vincent Prat @ MarvinLabs
*/
class CUAR_HelpAddOn extends CUAR_AddOn {

	public function run_addon( $plugin ) {
		$this->plugin = $plugin;
		
		// We only do something within the admin interface
		if ( is_admin() ) {
			add_filter( 'cuar_addon_settings_tabs', array( &$this, 'add_settings_tab' ), 1000, 1 );
			add_filter( 'cuar_before_settings_side', array( &$this, 'print_addons_sidebox' ) );
			add_filter( 'cuar_before_settings_cuar_addons', array( &$this, 'print_addons' ) );
		} 
	}	

	/*------- CUSTOMISATION OF THE PLUGIN SETTINGS PAGE --------------------------------------------------------------*/
	
	public function add_settings_tab( $tabs ) {
		// $tabs[ 'cuar_help' ] = __( 'Help', 'cuar' );
		$tabs[ 'cuar_addons' ] = __( 'Add-ons', 'cuar' );
		return $tabs;
	}
	
	/**
	 * @param CUAR_Settings $cuar_settings
	 */
	public function print_addons( $cuar_settings ) {
		include( dirname( __FILE__ ) . '/templates/list-addons.template.php' );
	}
	
	/**
	 * @param CUAR_Settings $cuar_settings
	 */
	public function print_addons_sidebox( $cuar_settings ) {		
		$content = sprintf( '<p>%s</p><p><a href="%s" class="button-primary" target="_blank">%s</a></p>', 
						__( '&laquo Customer Area &raquo; is a very modular plugin. We have built it so that it can be ' 
							. 'extended in many ways. Some add-ons are presented in this page by selecting the '
							. '&laquo Add-ons &raquo; tab. You can also view all extensions we have by clicking the '
							. 'link below.' , 'cuar' ),
						"http://www.marvinlabs.com/shop/",
						__( 'Browse all extensions', 'cuar' ) );
		
		$cuar_settings->print_sidebox( __( 'Enhance your customer area', 'cuar' ), $content );
	}
	
	/** @var CUAR_Plugin */
	private $plugin;
}

// Make sure the addon is loaded
global $cuar_he_addon;
$cuar_he_addon = new CUAR_HelpAddOn();

endif; // if (!class_exists('CUAR_HelpAddOn')) 
