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

require_once( dirname(__FILE__) . '/customer-page-shortcode.class.php' );
// require_once( dirname(__FILE__) . '/private-file-frontend-interface.class.php' );
// require_once( dirname(__FILE__) . '/private-file-theme-utils.class.php' );

if (!class_exists('CUAR_CustomerPageAddOn')) :

/**
 * Add-on to show the customer page
*
* @author Vincent Prat @ MarvinLabs
*/
class CUAR_CustomerPageAddOn extends CUAR_AddOn {
	
	public function __construct() {
		parent::__construct( 'customer-page', __( 'Customer Page', 'cuar' ), '2.0.0' );
	}

	public function run_addon( $cuar_plugin ) {
		$this->cuar_plugin = $cuar_plugin;
		$this->customer_page_shortcode = new CUAR_CustomerPageShortcode( $cuar_plugin );

		add_filter( 'cuar_customer_page_actions', array( &$this, 'add_home_action' ), 1 );
		add_filter( 'cuar_customer_page_actions', array( &$this, 'add_logout_action' ), 1000 );

		// Settings
		add_action( 'cuar_addon_print_settings_cuar_core', array( &$this, 'print_settings' ), 50, 2 );
		add_filter( 'cuar_addon_validate_options_cuar_core', array( &$this, 'validate_settings' ), 50, 3 );

		if ( $this->get_customer_page_id() < 0 && !isset( $_GET['run-setup-wizard'] )) {
			$warning = __( 'We could not detect the Customer Area page on your site. This may be because you have not yet setup the plugin, or because you are upgrading from an older version.', 'cuar' );
			$warning .= '<ul><li>&raquo; ';
			$warning .= sprintf( __( 'If you already have this page, just visit the <a href="%s">plugin settings</a> to set it manually.', 'cuar' ), admin_url( 'admin.php?page=' .  CUAR_Settings::$OPTIONS_PAGE_SLUG ) );
			$warning .= '</li><li>&raquo; ';
			$warning .= sprintf( __( 'If you have not yet setup the plugin, we have a <a href="%s">quick setup wizard</a>', 'cuar' ), admin_url( 'admin.php?page=' .  CUAR_Settings::$OPTIONS_PAGE_SLUG . '&run-setup-wizard=1' ) );
			$warning .= '</li></ul>';
			
			$cuar_plugin->add_admin_notice( $warning );
		}
	}	
	
	/*------- INITIALISATIONS ---------------------------------------------------------------------------------------*/
	
	public function add_home_action( $actions ) {
		$actions['show-dashboard'] = apply_filters( 'cuar_home_action', array(
				"slug"		=> 'show-dashboard',
				"url"		=> $this->get_customer_page_url(),
				"label"		=> __( 'Dashboard', 'cuar' ),
				"hint"		=> __( 'Your customer area welcome page', 'cuar' )
			) );
		return $actions;
	}
	
	public function add_logout_action( $actions ) {
		$actions['logout'] = apply_filters( 'cuar_logout_action', array(
				"url"		=> wp_logout_url( $this->get_customer_page_url() ),
				"label"		=> __( 'Logout', 'cuar' ),
				"hint"		=> __( 'Disconnect from your customer area', 'cuar' )
		) );
		return $actions;
	}
	
	public function get_customer_page_id() {
		return $this->cuar_plugin->get_option( self::$OPTION_CUSTOMER_PAGE_POST_ID );
	}
	
	public function set_customer_page_id( $post_id ) {
		return $this->cuar_plugin->update_option( self::$OPTION_CUSTOMER_PAGE_POST_ID, $post_id );
	}
	
	public function get_customer_page_url() {
		return get_permalink( $this->get_customer_page_id() );
	}


	/*------- SETTINGS ----------------------------------------------------------------------------------------------*/

	
	public function print_settings($cuar_settings, $options_group) {
		add_settings_section(
				'cuar_core_frontend',
				__('Frontend Integration', 'cuar'),
				array( &$this, 'print_empty_settings_section_info' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG
			);

		add_settings_field(
				self::$OPTION_CUSTOMER_PAGE_POST_ID,
				__('Customer Page', 'cuar'),
				array( &$cuar_settings, 'print_post_select_field' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_core_frontend',
				array(
					'option_id' => self::$OPTION_CUSTOMER_PAGE_POST_ID,
					'post_type' => 'page',
					'after'		=> 
							'<p class="description">' 
							. __( 'This page is the one where you have inserted the [customer-area] shortcode. This should be set automatically when you first visit that page. '
									. 'If for any reason it is not correct, you can change it though.', 'cuar' )
							. '</p>' )
			);
	}
	
	public function print_empty_settings_section_info() {
	}
	
	public function validate_settings($validated, $cuar_settings, $input) {
		$cuar_settings->validate_post_id( $input, $validated, self::$OPTION_CUSTOMER_PAGE_POST_ID );
			
		return $validated;
	}
	
	public static function set_default_options($defaults) {
		$defaults [self::$OPTION_CUSTOMER_PAGE_POST_ID] = -1;
			
		return $defaults;
	}
	
	// Frontend options
	public static $OPTION_CUSTOMER_PAGE_POST_ID		= 'customer_page_post_id';
	
	/** @var CUAR_Plugin */
	private $cuar_plugin;

	/** @var CUAR_CustomerPageShortcode */
	private $customer_page_shortcode;
}

// Make sure the addon is loaded
global $cuar_cp_addon;
$cuar_cp_addon = new CUAR_CustomerPageAddOn();
	
// This filter needs to be executed too early to be registered in the constructor
add_filter( 'cuar_default_options', array( 'CUAR_CustomerPageAddOn', 'set_default_options' ) );

endif; // if (!class_exists('CUAR_CustomerPageAddOn')) :