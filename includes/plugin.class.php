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
		add_action( 'admin_init', array( &$this, 'check_versions' ) );
		
		if ( is_admin() ) {		
			add_action( 'admin_notices', array( &$this, 'print_admin_notices' ));
		} else {
		}
	}

	/*------- MAIN HOOKS INTO WP ------------------------------------------------------------------------------------*/
	
	/**
	 * Load the translation file for current language. Checks in wp-content/languages first
	 * and then the customer-area/languages.
	 *
	 * Edits to translation files inside customer-area/languages will be lost with an update
	 * **If you're creating custom translation files, please use the global language folder.**
	 */
	public function load_textdomain( $domain = 'cuar', $plugin_name = 'customer-area' ) {
		if ( empty( $domain ) ) $domain = 'cuar';
		if ( empty( $plugin_name ) ) $plugin_name = 'customer-area';
	
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			
		$mofile = $domain . '-' . $locale . '.mo';

		/* Check the global language folder */
		$files = array( WP_LANG_DIR . '/' . $plugin_name . '/' . $mofile, WP_LANG_DIR . '/' . $mofile );
		foreach ( $files as $file ){
			if( file_exists( $file ) ) return load_textdomain( $domain, $file );
		}

		// If we got this far, fallback to the plug-in language folder.
		// We could use load_textdomain - but this avoids touching any more constants.
		load_plugin_textdomain( $domain, false, $plugin_name . '/languages' );
	}

	/**
	 * Loads the required javascript files (only when not in admin area)
	 */
	public function load_scripts() {
		if ( is_admin() ) return;
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
	 * 
	 */
	public function check_versions() {
		$plugin_data = get_plugin_data( WP_CONTENT_DIR . '/plugins/' . CUAR_PLUGIN_FILE, false, false );
		$current_version = $plugin_data[ 'Version' ];
		$active_version = $this->get_option( CUAR_Settings::$OPTION_CURRENT_VERSION );
		if ( !isset( $active_version ) ) $active_version = '1.4.0';
		
		if ( $active_version != $current_version ) {
			do_action( 'cuar_version_upgraded', $active_version, $current_version );
			$this->settings->update_option( CUAR_Settings::$OPTION_CURRENT_VERSION, $current_version );
		}		
	}
	
	/**
	 * Initialise some defaults for the plugin (add basic capabilities, ...)
	 */
	public function load_defaults() {	
		// Start a session when we save a post in order to store error logs
		if (!session_id()) session_start();
	}

	/*------- TEMPLATING & THEMING ----------------------------------------------------------------------------------*/

	/**
	 * This function offers a way for addons to do their stuff after this plugin is loaded
	 */
	public function get_admin_theme_url() {
		return apply_filters( 'cuar_admin_theme_url', $this->get_option( CUAR_Settings::$OPTION_ADMIN_THEME_URL ) );
	}
	
	/**
	 * This function offers a way for addons to do their stuff after this plugin is loaded
	 */
	public function get_frontend_theme_url() {
		return apply_filters( 'cuar_frontend_theme_url', $this->get_option( CUAR_Settings::$OPTION_FRONTEND_THEME_URL ) );
	}
	
	/**
	 * Takes a default template file as parameter. It will look in the theme's directory to see if the user has
	 * customized the template. If so, it returns the path to the customized file. Else, it returns the default
	 * passed as parameter.
	 * 
	 * Order of preference is:
	 * 1. user-directory/filename
	 * 2. user-directory/fallback-filename
	 * 3. default-directory/filename
	 * 4. default-directory/fallback-filename
	 * 
	 * @param string $default_path
	 */
	public function get_template_file_path( $default_root, $filename, $sub_directory = '', $fallback_filename = '' ) {		
		$relative_path = ( !empty( $sub_directory ) ) ? trailingslashit( $sub_directory ) . $filename : $filename;
		
		$possible_locations = apply_filters( 'cuar_available_template_file_locations', 
				array(
					get_stylesheet_directory() . '/customer-area',
					get_stylesheet_directory() ) );
		
		// Look for the preferred file first
		foreach ( $possible_locations as $dir ) {
			$path =  trailingslashit( $dir ) . $relative_path;
			if ( file_exists( $path ) ) return $path;
		}
		
		// Then for the fallback alternative if any
		if ( !empty( $fallback_filename ) ) {
			$fallback_relative_path = ( !empty( $sub_directory ) ) 
											? trailingslashit( $sub_directory ) . $fallback_filename 
											: $fallback_filename;
		
			foreach ( $possible_locations as $dir ) {
				$path =  trailingslashit( $dir ) . $fallback_relative_path;
				if ( file_exists( $path ) ) return $path;
			}
		}
		
		// Then from default directory
		$path =  trailingslashit( $default_root ) . $relative_path;
		if ( file_exists( $path ) ) return $path;

		if ( !empty( $fallback_filename ) ) {
			$path =  trailingslashit( $default_root ) . $fallback_relative_path;
			if ( file_exists( $path ) ) return $path;
		}
		
		return '';
	}

	/*------- SETTINGS ----------------------------------------------------------------------------------------------*/
	
	/**
	 * Access to the settings (delegated to our settings class instance)
	 * @param unknown $option_id
	 */
	public function get_option( $option_id ) {
		return $this->settings->get_option( $option_id );
	}
	
	/** @var CUAR_Settings */
	private $settings;

	/*------- ADD-ONS -----------------------------------------------------------------------------------------------*/

	/**
	 * This function offers a way for addons to do their stuff after this plugin is loaded
	 */
	public function load_addons() {
		do_action( 'cuar_before_addons_init', $this );
		do_action( 'cuar_addons_init', $this );
		do_action( 'cuar_after_addons_init', $this );
	}
	
	/**
	 * Register an add-on in the plugin
	 * @param CUAR_AddOn $addon
	 */
	public function register_addon( $addon ) {
		$this->registered_addons[] = $addon;
	}
	
	/** @var array */
	private $registered_addons = array();

	/*------- ADMIN NOTICES -----------------------------------------------------------------------------------------*/
	
	/**
	 * Print the eventual errors that occured during a post save/update
	 */
	public function print_admin_notices() {
		$notices = $this->get_admin_notices();
		if ( $notices ) {
			foreach ( $notices as $n ) {
				echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $n['type'] ), esc_html( $n['msg'] ) );
			}
		}
		$this->clear_admin_notices();
	}
	
	/**
	 * Remove the notices stored in the session for save posts
	 */
	private function clear_admin_notices() {
		if ( isset( $_SESSION['cuar_admin_notices'] ) ) {
			unset( $_SESSION['cuar_admin_notices'] ); 
		}
	}

	/**
	 * Remove the stored notices
	 */
	private function get_admin_notices() {
		return empty( $_SESSION[ 'cuar_admin_notices' ] ) ? false : $_SESSION['cuar_admin_notices'];
	}
	
	/**
	 * Add an admin notice (useful when in a save post function for example)
	 * 
	 * @param string $msg
	 * @param string $type error or updated
	 */
	public function add_admin_notice( $msg, $type = 'error' ) {
		if ( empty( $_SESSION[ 'cuar_admin_notices' ] ) ) {
			$_SESSION[ 'cuar_admin_notices' ] = array();
	 	}
	 	$_SESSION[ 'cuar_admin_notices' ][] = array(
				'type' 	=> $type,
				'msg' 	=> $msg 
	 		);
	}
	
	/*------- OTHER FUNCTIONS ---------------------------------------------------------------------------------------*/
	
	/**
	 * Tells which post types are private (shown on the customer area page)
	 * @return array
	 */
	public function get_private_post_types() {
		return apply_filters('cuar_private_post_types', array());
	}	
	
}

endif; // if (!class_exists('CUAR_Plugin')) :