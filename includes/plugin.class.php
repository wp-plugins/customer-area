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

require_once( CUAR_INCLUDES_DIR . '/settings.class.php' );
	
if (!class_exists('CUAR_Plugin')) :

/**
 * The main plugin class
 * 
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_Plugin {
	
	public function __construct() {
	}
	
	public function run() {		
		$this->settings = new CUAR_Settings( $this );

		add_action( 'init', array( &$this, 'load_textdomain' ) );
		add_action( 'init', array( &$this, 'load_scripts' ) );
		add_action( 'init', array( &$this, 'load_styles' ) );		
		add_action( 'init', array( &$this, 'load_defaults' ) );
		add_action( 'plugins_loaded', array( &$this, 'load_addons' ) );
		
		if ( is_admin() ) {		
		} else {
		}
	}
	
	/**
	 * Load the translation file for current language. Checks in wp-content/languages first
	 * and then the customer-area/languages.
	 *
	 * Edits to translation files inside customer-area/languages will be lost with an update
	 * **If you're creating custom translation files, please use the global language folder.**
	 */
	public function load_textdomain() {
		$domain = 'cuar';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			
		$mofile = $domain . '-' . $locale . '.mo';

		/* Check the global language folder */
		$files = array( WP_LANG_DIR . '/customer-area/' . $mofile, WP_LANG_DIR . '/' . $mofile );
		foreach ( $files as $file ){
			if( file_exists( $file ) ) return load_textdomain( $domain, $file );
		}

		// If we got this far, fallback to the plug-in language folder.
		// We could use load_textdomain - but this avoids touching any more constants.
		load_plugin_textdomain( 'cuar', false, CUAR_LANGUAGE_DIR );
	}

	/**
	 * Loads the required javascript files (only when not in admin area)
	 */
	public function load_scripts() {
		if ( is_admin() ) return;
		
// 		wp_enqueue_script(
// 			'jquery.bxslider',
// 			CUAR_SCRIPTS_URL . '/jquery.bxslider.min.js', 
// 			array( 'jquery' ) );
	}

	/**
	 * Loads the required css (only when not in admin area)
	 */
	public function load_styles() {
		if ( is_admin() ) {
			wp_enqueue_style(
				'cuar.admin',
				$this->get_admin_theme_url() . '/style.css' );
		} else if ( $this->get_option( CUAR_Settings::$OPTION_INCLUDE_CSS ) ) {
			wp_enqueue_style(
				'cuar.frontend',
				$this->get_frontend_theme_url() . '/style.css' );
		}
	}
	
	/**
	 * This function offers a way for addons to do their stuff after this plugin is loaded
	 */
	public function get_admin_theme_url() {
		return apply_filters( 'cuar_admin_theme_url', CUAR_ADMIN_THEME_URL );
	}
	
	/**
	 * This function offers a way for addons to do their stuff after this plugin is loaded
	 */
	public function get_frontend_theme_url() {
		return apply_filters( 'cuar_frontend_theme_url', CUAR_FRONTEND_THEME_URL );
	}
	
	/**
	 * This function offers a way for addons to do their stuff after this plugin is loaded
	 */
	public function load_addons() {
		do_action( 'cuar_addons_init', $this );
	}
	
	/**
	 * Initialise some defaults for the plugin (add basic capabilities, ...)
	 */
	public function load_defaults() {	
		// Start a session when we save a post in order to store error logs
		if (!session_id()) session_start();
		
		$admin_role = get_role( 'administrator' );
		$admin_role->add_cap( 'cuar_editor' );
	}
	
	/**
	 * This is the base directory where we will store the user files
	 * 
	 * @return string
	 */
	public function get_base_upload_directory() {
		return WP_CONTENT_DIR . '/customer-area';
	}
	
	/**
	 * This is the base URL where we can access the user files directly (should be protected to forbid direct
	 * downloads)
	 * 
	 * @return string
	 */
	public function get_base_upload_url() {
		return WP_CONTENT_URL . '/customer-area';
	}
	
	/**
	 * Get the absolute path to a user file.
	 * 
	 * @param int $user_id
	 * @param string $filename
	 * @param boolean $create_dirs
	 * @return boolean|string
	 */
	public function get_user_file_path( $user_id, $filename, $create_dirs = false ) {
		if ( empty( $user_id ) || empty( $filename ) ) return false;
		
		$dir = $this->get_base_upload_directory() . '/' . $this->get_user_storage_directory( $user_id );		
		if ( $create_dirs && !file_exists( $dir ) ) mkdir( $dir, '0777', true );
		
		return $dir . '/' . $filename;
	}
	
	/**
	 * Get a user's private storage directory. This directory is relative to the main upload directory
	 * 
	 * @param int $user_id The id of the user, or null to get the base directory
	 */
	public function get_user_storage_directory( $user_id ) {
		if ( empty( $user_id ) ) return false;
		
		$dir = get_user_meta($user_id, 'cuar_directory', true);
		if (empty($dir)) {
			$dir = uniqid( $user_id . '_' );
			add_user_meta( $user_id, 'cuar_directory', $dir );
		}
		
		return $dir;
	}
	
	/**
	 * Takes a default template file as parameter. It will look in the theme's directory to see if the user has
	 * customized the template. If so, it returns the path to the customized file. Else, it returns the default
	 * passed as parameter.
	 * 
	 * @param string $default_path
	 */
	public function get_template_file_path( $default_root, $filename, $sub_directory = '' ) {		
		$relative_path = ( !empty( $sub_directory ) ) ? trailingslashit( $sub_directory ) . $filename : $filename;
		
		$possible_locations = apply_filters( 'cuar_available_template_file_locations', 
				array(
					get_stylesheet_directory() . '/customer-area',
					get_stylesheet_directory() ) );
		
		foreach ( $possible_locations as $dir ) {
			$path =  trailingslashit( $dir ) . $relative_path;
			if ( file_exists( $path ) ) return $path;
		}
		
		return trailingslashit( $default_root ) . $relative_path;
	}
	
	/**
	 * Access to the settings (delegated to our settings class instance)
	 * @param unknown $option_id
	 */
	public function get_option( $option_id ) {
		return $this->settings->get_option( $option_id );
	}
	
	/** @var CUAR_Settings */
	private $settings;
}

endif; // if (!class_exists('CUAR_Plugin')) :