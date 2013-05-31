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


if (!class_exists('CUAR_Settings')) :

/**
 * Creates the UI to change the plugin settings in the admin area. Also used to access the plugin settings
* stored in the DB (@see CUAR_Plugin::get_option)
*/
class CUAR_Settings {

	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->setup();
		$this->reload_options();
	}

	/**
	 * Get the value of a particular plugin option
	 *
	 * @param string $option_id the ID of the option to get
	 * @return mixed the value
	 */
	public function get_option( $option_id ) {
		return isset( $this->options[ $option_id ] ) ? $this->options[ $option_id ] : null;
	}

	/**
	 * Setup the WordPress hooks we need
	 */
	public function setup() {
		if ( is_admin() ) {
			add_action('cuar_admin_submenu_pages', array( &$this, 'add_settings_menu_item' ), 100 );
			add_action('admin_init', array( &$this, 'page_init' ) );
			
			// Links under the plugin name
			$plugin_file = 'customer-area/customer-area.php';
			add_filter( "plugin_action_links_{$plugin_file}", array( &$this, 'print_plugin_action_links' ), 10, 2 );

			// We have some core settings to take care of too
			add_filter( 'cuar_addon_settings_tabs', array( &$this, 'add_core_settings_tab' ), 10, 1 );
			add_action( 'cuar_addon_print_settings_cuar_core', array( &$this, 'print_core_settings' ), 10, 2 );
			add_filter( 'cuar_addon_validate_options_cuar_core', array( &$this, 'validate_core_settings' ), 10, 3 );
		}
	}

	/**
	 * Add the menu item
	 */
	public function add_settings_menu_item( $submenus ) {
		$separator = '<span style="display:block;  
				        margin: 3px 5px 6px -5px; 
				        padding:0; 
				        height:1px; 
				        line-height:1px; 
				        background:#ddd;"></span>';
		
		$submenu = array(
				'page_title'	=> __( 'Settings', 'cuar' ),
				'title'			=> $separator . __( 'Settings', 'cuar' ),
				'slug'			=> self::$OPTIONS_PAGE_SLUG,
				'function' 		=> array( &$this, 'print_settings_page' ),
				'capability'	=> 'manage_options'
			);
		
		$submenus[] = $submenu;
		
		return $submenus;
	}
	
	public function print_plugin_action_links( $links, $file ) {
		$link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=' .  self::$OPTIONS_PAGE_SLUG . '">'
				. __( 'Settings', 'cuar' ) . '</a>';
		array_unshift( $links, $link );	
		return $links;
	}

	/**
	 * Output the settings page
	 */
	public function print_settings_page() {
		include( CUAR_INCLUDES_DIR . '/settings.view.php' );
	}

	/**
	 * Register the settings
	 */
	public function page_init() {		
		$this->setup_tabs();
		
		// Register the main settings and for the current tab too
		register_setting( self::$OPTIONS_GROUP, self::$OPTIONS_GROUP, array( &$this, 'validate_options' ) );	
		register_setting( self::$OPTIONS_GROUP . '_' . $this->current_tab, 
			self::$OPTIONS_GROUP, 
			array( &$this, 'validate_options' ) );
	
		// Let the current tab add its own settings to the page
		do_action( "cuar_addon_print_settings_{$this->current_tab}", 
			$this, 
			self::$OPTIONS_GROUP . '_' . $this->current_tab );
	}

	/**
	 * Create the tabs to show
	 */
	public function setup_tabs() {
		$this->tabs = apply_filters( 'cuar_addon_settings_tabs', array() );
		
		// Get current tab from GET or POST params or default to first in list
		$this->current_tab = isset( $_GET[ 'cuar_tab' ] ) ? $_GET[ 'cuar_tab' ] : '';		
		if ( !isset( $this->tabs[ $this->current_tab ] ) ) {
			$this->current_tab = isset( $_POST[ 'cuar_tab' ] ) ? $_POST[ 'cuar_tab' ] : '';
		}
		if ( !isset( $this->tabs[ $this->current_tab ] ) ) {
			reset( $this->tabs );
			$this->current_tab = key( $this->tabs );
		}
	}

	/**
	 * Save the plugin settings
	 * @param array $input The new option values
	 * @return
	 */
	public function validate_options( $input ) {
		$validated = array();

		// Allow addons to validate their settings here
		$validated = apply_filters( 'cuar_addon_validate_options_' . $this->current_tab, $validated, $this, $input );

		$this->options = array_merge( $this->options, $validated );
		
		// Also flush rewrite rules
		global $wp_rewrite;  
		$wp_rewrite->flush_rules();
		
		return $this->options;
	}

	/* ------------ CORE SETTINGS ----------------------------------------------------------------------------------- */

	/**
	 * Add a tab
	 * @param array $tabs
	 * @return array
	 */
	public function add_core_settings_tab( $tabs ) {
		$tabs[ 'cuar_core' ] = __( 'General', 'cuar' );
		return $tabs;
	}
	
	/**
	 * Add our fields to the settings page
	 * 
	 * @param CUAR_Settings $cuar_settings The settings class
	 */
	public function print_core_settings( $cuar_settings, $options_group ) {		
		// General settings
		add_settings_section(
				'cuar_general_settings',
				__('General Settings', 'cuar'),
				array( &$cuar_settings, 'print_core_settings_general_section_info' ),
				self::$OPTIONS_PAGE_SLUG
			);
			
		add_settings_field(
				self::$OPTION_INCLUDE_CSS,
				__('Include CSS', 'cuar'),
				array( &$cuar_settings, 'print_input_field' ),
				self::$OPTIONS_PAGE_SLUG,
				'cuar_general_settings',
				array(
						'option_id' => self::$OPTION_INCLUDE_CSS,
						'type' 		=> 'checkbox',
						'after'		=> __( 'Include the default stylesheet.', 'cuar' )
										. '<p class="description">'
										. __( 'If not, you should style the plugin yourself in your theme.', 'cuar' )
										. '</p>' 
					)
			);
			
		add_settings_field(
				self::$OPTION_ADMIN_THEME_URL,
				__('Admin theme', 'cuar'),
				array( &$cuar_settings, 'print_theme_select_field' ),
				self::$OPTIONS_PAGE_SLUG,
				'cuar_general_settings',
				array(
						'option_id' 	=> self::$OPTION_ADMIN_THEME_URL,
						'theme_type'	=> 'admin'
					)
			);
			
		add_settings_field(
				self::$OPTION_FRONTEND_THEME_URL,
				__('Frontend theme', 'cuar'),
				array( &$cuar_settings, 'print_theme_select_field' ),
				self::$OPTIONS_PAGE_SLUG,
				'cuar_general_settings',
				array(
						'option_id' 	=> self::$OPTION_FRONTEND_THEME_URL,
						'theme_type'	=> 'frontend'
					)
			);
	}
	
	public function print_core_settings_general_section_info() {
		// echo '<p>' . __( 'General plugin options.', 'cuar' ) . '</p>';
	}

	/**
	 * Validate core options
	 *
	 * @param CUAR_Settings $cuar_settings
	 * @param array $input
	 * @param array $validated
	 */
	public function validate_core_settings( $validated, $cuar_settings, $input ) {
		$cuar_settings->validate_boolean( $input, $validated, self::$OPTION_INCLUDE_CSS );
		$cuar_settings->validate_not_empty( $input, $validated, self::$OPTION_ADMIN_THEME_URL );
		$cuar_settings->validate_not_empty( $input, $validated, self::$OPTION_FRONTEND_THEME_URL );
		
		return $validated;
	}
	
	/**
	 * Set the default values for the core options
	 * 
	 * @param array $defaults
	 * @return array
	 */
	public static function set_default_core_options( $defaults ) {
		$defaults[ self::$OPTION_INCLUDE_CSS ] = true;
		$defaults[ self::$OPTION_ADMIN_THEME_URL ] = CUAR_ADMIN_THEME_URL;
		$defaults[ self::$OPTION_FRONTEND_THEME_URL ] = CUAR_FRONTEND_THEME_URL;
				
		return $defaults;
	}

	/* ------------ VALIDATION HELPERS ------------------------------------------------------------------------------ */

	/**
	 * Validate a boolean value within an array
	 *
	 * @param array $input Input array
	 * @param array $validated Output array
	 * @param string $option_id Key of the value to check in the input array
	 */
	public function validate_boolean( $input, &$validated, $option_id ) {
		$validated[ $option_id ] = isset( $input[ $option_id ] ) ? true : false;
	}
	
	/**
	 * Validate a value in any case
	 *
	 * @param array $input Input array
	 * @param array $validated Output array
	 * @param string $option_id Key of the value to check in the input array
	 */
	public function validate_license_key( $input, &$validated, $option_id, $store_url, $product_name ) {
		$validated[ $option_id ] = trim( $input[ $option_id ] );

		// Only validate on license change or every N days
		$last_check = $this->get_option( $option_id . '_lastcheck' );
		
		if ( $last_check ) { 
			$days_since_lastcheck = ( time() - intval( $last_check ) ) / ( 24 * 60 * 60 ) ;
		} else {
			$days_since_lastcheck = 99999;
		}
		
		if ( $days_since_lastcheck > 2 
				|| $input[ $option_id ]!=$this->get_option( $option_id ) ) {
			// data to send in our API request
			$api_params = array(
					'edd_action'	=> 'activate_license',
					'license' 		=> $validated[ $option_id ],
					'item_name' 	=> urlencode( $product_name ) 
			);
			
			// Call the custom API.
			$response = wp_remote_get( add_query_arg( $api_params, $store_url ), 
					array( 'timeout' => 15, 'sslverify' => false ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) return false;
			
			// decode the license data
			// $license_data->license will be either "active" or "inactive"
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
	
			$validated[ $option_id . '_status' ] = $license_data->license;		
			$validated[ $option_id . '_lastcheck' ] = time();		
		}
	}
	
	/**
	 * Validate a value which should simply be not empty 
	 *
	 * @param array $input Input array
	 * @param array $validated Output array
	 * @param string $option_id Key of the value to check in the input array
	 */
	public function validate_not_empty( $input, &$validated, $option_id ) {
		if ( isset( $input[ $option_id ] ) && !empty( $input[ $option_id ] ) ) {
			$validated[ $option_id ] = $input[ $option_id ];
		} else {
			add_settings_error( $option_id, 'settings-errors',
				$option_id . ': ' . $input[ $option_id ] . __( ' cannot be empty', 'cuar' ), 'error' );
			
			$validated[ $option_id ] = $this->default_options[ $option_id ];
		}
	}
	
	/**
	 * Validate an email address 
	 *
	 * @param array $input Input array
	 * @param array $validated Output array
	 * @param string $option_id Key of the value to check in the input array
	 */
	public function validate_email( $input, &$validated, $option_id ) {
		if ( isset( $input[ $option_id ] ) && is_email( $input[ $option_id ] ) ) {
			$validated[ $option_id ] = $input[ $option_id ];
		} else {
			add_settings_error( $option_id, 'settings-errors',
				$option_id . ': ' . $input[ $option_id ] . __( ' is not a valid email', 'cuar' ), 'error' );
			
			$validated[ $option_id ] = $this->default_options[ $option_id ];
		}
	}

	/**
	 * Validate an enum value within an array
	 *
	 * @param array $input Input array
	 * @param array $validated Output array
	 * @param string $option_id Key of the value to check in the input array
	 * @param array $enum_values Array of possible values
	 */
	public function validate_enum( $input, &$validated, $option_id, $enum_values ) {
		if ( !in_array( $input[ $option_id ], $enum_values ) ) {
			add_settings_error( $option_id, 'settings-errors',
				$option_id . ': ' . $input[ $option_id ] . __( ' is not a valid value', 'cuar' ), 'error' );

			$validated[ $option_id ] = $this->default_options[ $option_id ];
			return;
		}
		 
		$validated[ $option_id ] = $input[ $option_id ];
	}

	/**
	 * Validate an integer value within an array
	 *
	 * @param array $input Input array
	 * @param array $validated Output array
	 * @param string $option_id Key of the value to check in the input array
	 * @param int $min Min value for the int (set to null to ignore check)
	 * @param int $max Max value for the int (set to null to ignore check)
	 */
	public function validate_int( $input, &$validated, $option_id, $min = null, $max = null ) {
		// Must be an int
		if ( !is_int( intval( $input[ $option_id ] ) ) ) {
			add_settings_error( $option_id, 'settings-errors',
			$option_id . ': ' . __( 'must be an integer', 'cuar' ), 'error' );

			$validated[ $option_id ] = $this->default_options[ $option_id ];
			return;
		}
		 
		// Must be > min
		if ( $min!==null && $input[ $option_id ] < $min ) {
			add_settings_error( $option_id, 'settings-errors',
			$option_id . ': ' . sprintf( __( 'must be greater than %s', 'cuar' ), $min ), 'error' );

			$validated[ $option_id ] = $this->default_options[ $option_id ];
			return;
		}
		 
		// Must be < max
		if ( $max!==null && $input[ $option_id ] > $max ) {
			add_settings_error( $option_id, 'settings-errors',
			$option_id . ': ' . sprintf( __( 'must be lower than %s', 'cuar' ), $max ), 'error' );

			$validated[ $option_id ] = $this->default_options[ $option_id ];
			return;
		}
		 
		// All good
		$validated[ $option_id ] = intval( $input[ $option_id ] );
	}

	/* ------------ FIELDS OUTPUT ----------------------------------------------------------------------------------- */

	/**
	 * Output a text field for a license key
	 *
	 * @param string $option_id
	 */
	public function print_license_key_field( $args ) {
		extract( $args );

		if ( isset( $before ) ) echo $before;
			
		echo sprintf( '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
				esc_attr( $option_id ),
				self::$OPTIONS_GROUP,
				esc_attr( $option_id ),
				esc_attr( $this->options[ $option_id ] )
		);
			
		$status = $this->get_option( $option_id . '_status' );
		if ( isset( $status ) && $status!==false ) {
			if ( $status=="active" ) {
				printf( '&nbsp;&nbsp;<span class="status" style="color: green;">%s</span>',
					__( 'Your license is active!', 'cuar' ) );
			} else if ( $status=="invalid" ) {
				printf( '&nbsp;&nbsp;<span class="status" style="color: red;">%s</span>',
					__( 'Your license key is invalid!', 'cuar' ) );
			} else if ( $status=="expired" ) {
				printf( '&nbsp;&nbsp;<span class="status" style="color: orange;">%s</span>',
					__( 'Your license key is expired!', 'cuar' ) );
			} 
		}
		
		$last_check = $this->get_option( $option_id . '_lastcheck' );
		if ( isset( $last_check ) ) {
			printf( '&nbsp;&nbsp;<span>(%s: %s)</span>',
				__( 'Last check', 'cuar' ),
				date( __( 'd/m/Y', 'cuar' ), $last_check ) );
		}
		
		printf( '<p class="description">%s</p>', __( 'The license key you have received for this add-on. '
	    		. 'This is required in order to get automatic plugin updates.', 'cuarlf' ) );
		
		if ( isset( $after ) ) echo $after;
	}
	
	/**
	 * Output a text field for a setting
	 *
	 * @param string $option_id
	 * @param string $type
	 * @param string $caption
	 */
	public function print_input_field( $args ) {
		extract( $args );
		 
		if ( $type=='checkbox' ) {
			if ( isset( $before ) ) echo $before;
			
			echo sprintf( '<input type="%s" id="%s" name="%s[%s]" value="open" %s />&nbsp;',
					esc_attr( $type ),
					esc_attr( $option_id ),
					self::$OPTIONS_GROUP,
					esc_attr( $option_id ),
					( $this->options[ $option_id ]!=0 ) ? 'checked="checked" ' : ''
			);

			if ( isset( $after ) ) echo $after;
		} else if ( $type=='textarea' ) {
			if ( isset( $before ) ) echo $before;
			
			echo sprintf( '<textarea id="%s" name="%s[%s]" class="large-text">%s</textarea>',
					esc_attr( $option_id ),
					self::$OPTIONS_GROUP,
					esc_attr( $option_id ),
					$content
			);

			if ( isset( $after ) ) echo $after;
		} else if ( $type=='editor' ) {
			if ( !isset( $editor_settings ) ) $editor_settings = array();			
			$editor_settings[ 'textarea_name' ] = self::$OPTIONS_GROUP . "[" . $option_id . "]";
			
			wp_editor( $this->options[ $option_id ], $option_id, $editor_settings ); 
		} else {
			$extra_class = isset( $is_large ) && $is_large==true ? 'large-text' : 'regular-text';

			if ( isset( $before ) ) echo $before;
			
			echo sprintf( '<input type="%s" id="%s" name="%s[%s]" value="%s" class="%s" />',
					esc_attr( $type ),
					esc_attr( $option_id ),
					self::$OPTIONS_GROUP,
					esc_attr( $option_id ),
					esc_attr( $this->options[ $option_id ] ),
					esc_attr( $extra_class )
			);
			
			if ( isset( $after ) ) echo $after;
		}
	}

	/**
	 * Output a select field for a setting
	 *
	 * @param string $option_id
	 * @param array  $options
	 * @param string $caption
	 */
	public function print_select_field( $args ) {
		extract( $args );
		
		if ( isset( $before ) ) echo $before;
		 
		echo sprintf( '<select id="%s" name="%s[%s]">',
				esc_attr( $option_id ),
				self::$OPTIONS_GROUP,
				esc_attr( $option_id ) );
		 
		foreach ( $options as $value => $label ) {
			$selected = ( $this->options[ $option_id ] == $value ) ? 'selected="selected"' : '';

			echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), $selected, $label );
		}
		 
		echo '</select>';
			
		if ( isset( $after ) ) echo $after;
	}

	/**
	 * Output a select field for a theme
	 *
	 * @param string $option_id
	 * @param array  $options
	 * @param string $caption
	 */
	public function print_theme_select_field( $args ) {
		extract( $args );
		
		if ( isset( $before ) ) echo $before;
		 
		echo sprintf( '<select id="%s" name="%s[%s]">',
				esc_attr( $option_id ),
				self::$OPTIONS_GROUP,
				esc_attr( $option_id ) );
		
		$theme_locations = apply_filters( 'cuar_theme_locations', array(
				array(
						'dir'	=> CUAR_PLUGIN_DIR . '/themes/' . $theme_type,
						'url'	=> CUAR_PLUGIN_URL . 'themes/' . $theme_type,
						'label'	=> __( 'Main plugin folder', 'cuar' )
					),
				array(
						'dir'	=> get_stylesheet_directory() . '/customer-area/themes/' . $theme_type,
						'url'	=> get_stylesheet_directory_uri() . '/customer-area/themes/' . $theme_type,
						'label'	=> __( 'Current theme folder', 'cuar' )
				),
				array(
						'dir'	=> WP_CONTENT_DIR . '/customer-area/themes/' . $theme_type,
						'url'	=> WP_CONTENT_URL . '/customer-area/themes/' . $theme_type,
						'label'	=> __( 'WordPress content folder', 'cuar' )
				)
			) );
		
		foreach ( $theme_locations as $theme_location ) {
			$subfolders = array_filter( glob( $theme_location['dir'] . '/*' ), 'is_dir' );

			foreach ( $subfolders as $s ) {
				$theme_name = basename( $s );				
				$label = $theme_location['label'] . ' - ' . $theme_name;
				$value = esc_attr( $theme_location['url'] . '/' . $theme_name );
 				$selected = ( $this->options[ $option_id ] == $value ) ? 'selected="selected"' : '';

 				var_dump($this->options[ $option_id ]);
 				var_dump($value);
 				
 				echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), $selected, $label );
			}
		}
		 
 		echo '</select>';
		
		if ( isset( $after ) ) echo $after;
	}

	/* ------------ OTHER FUNCTIONS --------------------------------------------------------------------------------- */

	/**
	 * Prints a sidebox on the side of the settings screen
	 * 
	 * @param string $title
	 * @param string $content
	 */
	public function print_sidebox( $title, $content ) {
		echo '<div class="cuar-sidebox">';
		echo '<h2 class="cuar-sidebox-title">' . $title . '</h2>';
		echo '<div class="cuar-sidebox-content">' . $content . '</div>';
		echo '</div>';
	}
	
	/**
	 * Update an option and persist to DB if asked to
	 *  
	 * @param string $option_id
	 * @param mixed $new_value
	 * @param boolean $commit
	 */
	public function update_option( $option_id, $new_value, $commit = true ) {
		$this->options[ $option_id ] = $new_value;
		if ( $commit ) $this->save_options();
	}
	
	/**
	 * Persist the current plugin options to DB
	 */
	public function save_options() {
		update_option( CUAR_Settings::$OPTIONS_GROUP, $this->options );
	}
	
	/**
	 * Persist the current plugin options to DB
	 */
	public function get_options() {
		return $this->options;
	}
	
	/**
	 * Load the options (and defaults if the options do not exist yet
	 */
	private function reload_options() {
		$current_options = get_option( CUAR_Settings::$OPTIONS_GROUP );
		
		$this->default_options = apply_filters( 'cuar_default_options', array() );
		
		if ( ! is_array( $current_options ) ) $current_options = array();
		$this->options = array_merge( $this->default_options, $current_options );
		
		do_action( 'cuar_options_loaded', $this->options );
	}

	public static $OPTIONS_PAGE_SLUG = 'cuar-settings';
	public static $OPTIONS_GROUP = 'cuar_options';

	// Core options
	public static $OPTION_CURRENT_VERSION		= 'cuar_current_version';
	public static $OPTION_INCLUDE_CSS			= 'cuar_include_css';
	public static $OPTION_ADMIN_THEME_URL 		= 'cuar_admin_theme_url';
	public static $OPTION_FRONTEND_THEME_URL 	= 'cuar_frontend_theme_url';

	/** @var CUAR_Plugin The plugin instance */
	private $plugin;

	/** @var array */
	private $default_options;

	/** @var array */
	private $tabs;

	/** @var string */
	private $current_tab;
}
	
// This filter needs to be executed too early to be registered in the constructor
add_filter( 'cuar_default_options', array( 'CUAR_Settings', 'set_default_core_options' ) );

endif; // if (!class_exists('CUAR_Settings')) :
