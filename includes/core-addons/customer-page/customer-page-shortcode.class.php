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

if (!class_exists('CUAR_CustomerPageShortcode')) :

/**
 * Handles the [customer-area] shortcode
 * 
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_CustomerPageShortcode {

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->setup();	
	}
	
	/**
	 * Setup the WordPress hooks we need
	 */
	public function setup() {
		if ( is_admin() ) return;		
		add_shortcode( 'customer-area', array( &$this, 'process_shortcode' ) );
	}
	
	/**
	 * Replace the [customer-area] shortcode with a page representing the customer area. The shortcode takes no
	 * parameter and does not accept any content.
	 * 
	 * @param array $attrs
	 * @param string $content
	 */
	public function process_shortcode( $params = array(), $content = null ) {
		$cp_addon = $this->plugin->get_addon('customer-page');
		if ( $cp_addon->get_customer_page_id() <= 0 ) {
			$cp_addon->set_customer_page_id( get_the_ID() );
		}
		
		// If not logged-in, we should do so.
		if ( !is_user_logged_in() ) {
			if ( isset( $_GET['redirect'] ) ) {
				$redirect_to_url = $_GET['redirect']; 
			} else {
				$redirect_to_url = $cp_addon->get_customer_page_url();
			}
			
	  		ob_start();
	  		
	  		do_action( 'cuar_before_login_required_template' );
	  		
	  		include( $this->plugin->get_template_file_path(
	  				CUAR_INCLUDES_DIR . '/core-addons/customer-page',
	  				'customer-page-login-required.template.php',
	  				'templates' ));

	  		do_action( 'cuar_after_login_required_template' );
	  		
	  		$out = ob_get_contents();
	  		ob_end_clean(); 
	  		
			return $out;
		} 
		
		// Build the HTML output for a logged-in user. 
  		ob_start();
	  		
	  	do_action( 'cuar_before_customer_area_template' );
	  		
  		include( $this->plugin->get_template_file_path(
  				CUAR_INCLUDES_DIR . '/core-addons/customer-page',
  				'customer-page.template.php',
  				'templates' ));	  	
	  		
	  	do_action( 'cuar_after_customer_area_template' );
	  	
  		$out = ob_get_contents();
  		ob_end_clean(); 
  		
		return $out;
	}
	
	/** @var CUAR_Plugin The plugin instance */
	private $plugin;
}

endif; // if (!class_exists('CUAR_CustomerPageShortcode')) :