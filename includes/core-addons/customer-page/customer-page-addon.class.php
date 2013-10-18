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
	
	public function get_customer_page_url() {
		// $post_id = $this->cuar_plugin->get_option(  )
		return get_permalink();
	}
	
	
	/** @var CUAR_Plugin */
	private $cuar_plugin;

	/** @var CUAR_CustomerPageShortcode */
	private $customer_page_shortcode;
}

// Make sure the addon is loaded
global $cuar_cp_addon;
$cuar_cp_addon = new CUAR_CustomerPageAddOn();

endif; // if (!class_exists('CUAR_CustomerPageAddOn')) :