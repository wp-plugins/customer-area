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

if (!class_exists('CUAR_PrivateFileAdminInterface')) :

/**
 * Administation area for private files
 * 
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_PrivateFileAdminInterface {
	
	public function __construct( $plugin, $private_file_addon ) {
		$this->plugin = $plugin;
		$this->private_file_addon = $private_file_addon;

		add_action('cuar_admin_submenu_pages', array( &$this, 'add_settings_menu_item' ), 10 );
		
		// File listing
		add_filter( 'manage_edit-cuar_private_file_columns', array( &$this, 'user_column_register' ));
		add_action( 'manage_cuar_private_file_posts_custom_column', array( &$this, 'user_column_display'), 10, 2 );
		add_filter( 'manage_edit-cuar_private_file_sortable_columns', array( &$this, 'user_column_register_sortable' ));
		add_filter( 'request', array( &$this, 'user_column_orderby' ));

		// File edit page
		add_action( 'admin_menu', array( &$this, 'register_edit_page_meta_boxes' ));
		add_action( 'save_post', array( &$this, 'do_save_post' ));
		add_action( 'admin_notices', array( &$this, 'print_save_post_messages' ));
		add_filter( 'upload_dir', array( &$this, 'custom_upload_dir' ));
		add_action( 'post_edit_form_tag' , array( &$this, 'post_edit_form_tag' ));
		
		// Settings
		add_filter( 'cuar_addon_settings_tabs', array( &$this, 'add_settings_tab' ), 10, 1 );
		add_action( 'cuar_addon_print_settings_cuar_private_files', array( &$this, 'print_settings' ), 10, 2 );
		add_filter( 'cuar_addon_validate_options_cuar_private_files', array( &$this, 'validate_options' ), 10, 3 );
	}

	/**
	 * Add the menu item
	 */
	public function add_settings_menu_item( $submenus ) {
		$my_submenus = array(
				array(
					'page_title'	=> __( 'Private Files', 'cuar' ),
					'title'			=> __( 'Private Files', 'cuar' ),
					'slug'			=> "edit.php?post_type=cuar_private_file",
					'function' 		=> null,
					'capability'	=> 'cuar_pf_edit'
				),
				array(
					'page_title'	=> __( 'New Private File', 'cuar' ),
					'title'			=> __( 'New Private File', 'cuar' ),
					'slug'			=> "post-new.php?post_type=cuar_private_file",
					'function' 		=> null,
					'capability'	=> 'cuar_pf_edit'
				),
				array(
					'page_title'	=> __( 'Private File Categories', 'cuar' ),
					'title'			=> __( 'Private File Categories', 'cuar' ),
					'slug'			=> "edit-tags.php?taxonomy=cuar_private_file_category",
					'function' 		=> null,
					'capability'	=> 'cuar_pf_edit'
				)
			); 
	
		foreach ( $my_submenus as $submenu ) {
			$submenus[] = $submenu;
		}
	
		return $submenus;
	}
	
	/*------- CUSTOMISATION OF THE LISTING OF PRIVATE FILES ----------------------------------------------------------*/
	
	/**
	 * Register the column
	 */
	public function user_column_register( $columns ) {
		$columns['cuar_owner'] = __( 'Owner', 'cuar' );
		return $columns;
	}
	
	/**
	 * Display the column content
	 */
	public function user_column_display( $column_name, $post_id ) {
		if ( 'cuar_owner' != $column_name )
			return;
	
		$owner_id = $this->private_file_addon->get_file_owner_id( $post_id );
		if ( $owner_id ) {
			$owner = new WP_User( $owner_id );
			echo $owner->display_name;
		} else {
			_e( 'Nobody', 'cuar' ); 
		}
	}
	
	/**
	 * Register the column as sortable
	 */
	public function user_column_register_sortable( $columns ) {
		$columns['cuar_owner'] = 'cuar_customer';
	
		return $columns;
	}
	
	/**
	 * Handle sorting of data
	 */
	public function user_column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'cuar_owner' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
					'meta_key' 	=> 'cuar_owner',
					'orderby' 	=> 'meta_value'
				) );
		}
	
		return $vars;
	}
	
	/*------- CUSTOMISATION OF THE EDIT PAGE OF A PRIVATE FILES ------------------------------------------------------*/

	/**
	 * Alter the edit form tag to say we have files to upload
	 */
	public function post_edit_form_tag() {
		global $post;
		if ( !$post || get_post_type($post->ID)!='cuar_private_file' ) return;
		echo ' enctype="multipart/form-data" autocomplete="off"';
	}
	
	/**
	 * Register some additional boxes on the page to edit the files
	 */
	public function register_edit_page_meta_boxes() {
		add_meta_box( 
				'cuar_private_file_upload', 
				__('File', 'cuar'), 
				array( &$this, 'print_upload_meta_box'), 
				'cuar_private_file', 
				'normal', 'high');
		
		add_meta_box( 
				'cuar_private_file_owner', 
				__('Owner', 'cuar'), 
				array( &$this, 'print_owner_meta_box'), 
				'cuar_private_file', 
				'normal', 'high');
	}

	/**
	 * Print the metabox to upload a file
	 */
	public function print_upload_meta_box() {
		global $post;
		wp_nonce_field( plugin_basename(__FILE__), 'wp_cuar_nonce_file' );
	
		$current_file = get_post_meta( $post->ID, 'cuar_private_file_file', true );

		do_action( "cuar_private_file_upload_meta_box_header" );
?>
		
<?php	if ( !empty( $current_file ) && isset( $current_file['url'] ) ) : ?>
		<div id="cuar-current-file" class="metabox-row">
			<p><?php _e('Current file:', 'cuar');?> 
				<a href="<?php CUAR_PrivateFileThemeUtils::the_file_link( $post->ID, 'view' ); ?>" target="_blank">
					<?php echo basename($current_file['file']); ?></a>
			</p>
		</div>		
<?php 	endif; ?> 

		<div id="cuar-upload-file" class="metabox-row">
			<span class="label"><label for="cuar_private_file_file"><?php _e('Upload a file', 'cuar');?></label></span> 	
			<span class="field"><input type="file" name="cuar_private_file_file" id="cuar_private_file_file" /></span>
		</div>
				
<?php 
		do_action( "cuar_private_file_upload_meta_box_footer" );
	}

	/**
	 * Print the metabox to select the owner of the file
	 */
	public function print_owner_meta_box() {
		global $post;
		wp_nonce_field( plugin_basename(__FILE__), 'wp_cuar_nonce_owner' );
	
		$current_uid = $this->private_file_addon->get_file_owner_id( $post->ID );		
		$all_users = get_users();
		
		do_action( "cuar_private_file_owner_meta_box_header" );
?>
		<div id="cuar-owner" class="metabox-row">
			<span class="label"><label for="cuar_owner"><?php _e('Select the owner of this file', 'cuar');?></label></span> 	
			<span class="field">
				<select name="cuar_owner" id="cuar_owner">
<?php 			foreach ( $all_users as $u ) :
					$selected =  ( $current_uid!=$u->ID ? '' : ' selected="selected"' );
?>
					<option value="<?php echo $u->ID;?>"<?php echo $selected; ?>><?php echo $u->display_name; ?>
					</option>
<?php 			endforeach; ?>				
				</select>
			</span>
		</div>
<?php
		do_action( "cuar_private_file_owner_meta_box_footer" );
	}
	
	/**
	 * Print the eventual errors that occured during a post save/update
	 */
	public function print_save_post_messages() {
		$notices = $this->get_save_post_notices();
		if ( $notices ) {
			foreach ( $notices as $n ) {
				echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $n['type'] ), esc_html( $n['msg'] ) );
			}
		}
		$this->clear_save_post_notices();
	}
	
	/**
	 * Remove the notices stored in the session for save posts
	 */
	private function clear_save_post_notices() {
		if ( isset( $_SESSION['cuar_private_file_save_post_notices'] ) ) {
			unset( $_SESSION['cuar_private_file_save_post_notices'] ); 
		}
	}

	/**
	 * Remove the stored notices
	 */
	private function get_save_post_notices() {
		return empty( $_SESSION[ 'cuar_private_file_save_post_notices' ] ) 
				? false 
				: $_SESSION['cuar_private_file_save_post_notices'];
	}
	
	public function add_save_post_notice( $msg, $type = 'error' ) {
		if ( empty( $_SESSION[ 'cuar_private_file_save_post_notices' ] ) ) {
			$_SESSION[ 'cuar_private_file_save_post_notices' ] = array();
	 	}
	 	$_SESSION[ 'cuar_private_file_save_post_notices' ][] = array(
				'type' 	=> $type,
				'msg' 	=> $msg );
	}
	
	/**
	 * Callback to handle saving a post
	 *  
	 * @param int $post_id
	 * @param string $post
	 * @return void|unknown
	 */
	public function do_save_post( $post_id, $post = null ) {
		global $post;
		
		// When auto-saving, we don't do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
	
		// Only take care of our own post type
		if ( !$post || get_post_type( $post->ID )!='cuar_private_file' ) return;
	
		// Other addons can do something before we save
		do_action( "cuar_private_file_before_do_save_post" );
		
		// Save the owner details
		if ( !wp_verify_nonce( $_POST['wp_cuar_nonce_owner'], plugin_basename(__FILE__) ) ) return $post_id;

		$previous_owner_id = $this->private_file_addon->get_file_owner_id( $post_id );
		$new_owner_id = $_POST['cuar_owner'];
		update_post_meta( $post_id, 'cuar_owner', $new_owner_id );

		// Save the file
		if ( !wp_verify_nonce( $_POST['wp_cuar_nonce_file'], plugin_basename(__FILE__) ) ) return $post_id;

		// If nothing to upload but owner changed, we'll simply move the file
		$previous_file = get_post_meta( $post_id, 'cuar_private_file_file', true );		
		
		if ( empty( $_FILES['cuar_private_file_file']['name'] ) ) {
			if ( $previous_file ) {
				$previous_file['path'] = $this->plugin->get_user_file_path( 
						$previous_owner_id, $previous_file['file'], true );
	
				if ( $previous_owner_id!=$new_owner_id 
						&& file_exists( $previous_file['path'] ) ) {
	
					$new_file_path = $this->plugin->get_user_file_path( 
							$new_owner_id, $previous_file['file'], true );
					if ( copy( $previous_file['path'], $new_file_path ) ) unlink( $previous_file['path'] );
	
					$new_file = $previous_file;
					$new_file['path'] = $new_file_path;
					update_post_meta( $post_id, 'cuar_private_file_file', $previous_file );
					
					cuar_log_debug( 'moved private file from ' . $previous_file['path'] . ' to ' . $new_file_path);
				}
			}
			
			// Other addons can do something after we save
			do_action( "cuar_private_file_after_do_save_post", $post_id, $this->private_file_addon, $this );
		
			return $post_id;
		}

		// Do some file type checking on the uploaded file if needed
		$new_file_name = $_FILES['cuar_private_file_file']['name']; 
		$supported_types = apply_filters( 'cuar_private_file_supported_types', null );
		if ( $supported_types!=null ) {
			$arr_file_type = wp_check_filetype( basename( $_FILES['cuar_private_file_file']['name'] ) );
			$uploaded_type = $arr_file_type['type'];
			
			if ( !in_array( $uploaded_type, $supported_types ) ) {
				$msg =  sprintf( __("This file type is not allowed. You can only upload: %s", 'cuar',
							implode( ', ', $supported_types ) ) );
				cuar_log_debug( $msg );
				
				$this->add_save_post_notice( $msg );
				return;
			}
		}
		
		// Delete the existing file if any
		if ( $previous_file ) {
			$previous_file['path'] = $this->plugin->get_user_file_path( 
					$previous_owner_id, $previous_file['file'], true );

			if ( $previous_file['path'] && file_exists( $previous_file['path'] ) ) {
				unlink( $previous_file['path'] );
				cuar_log_debug( 'deleted old private file from ' . $previous_file['path'] );
			}
		}
		
		// Use the WordPress API to upload the file
		$upload = wp_handle_upload( $_FILES['cuar_private_file_file'], array( 'test_form' => false ) );
		
		if ( empty( $upload ) ) {
			$msg = sprintf( __( 'An unknown error happened while uploading your file.', 'cuar' ) );
			cuar_log_debug( $msg );
			$this->add_save_post_notice( $msg );
		} else if ( isset( $upload['error'] ) ) {
			$msg = sprintf( __( 'An error happened while uploading your file: %s', 'cuar' ), $upload['error'] );
			cuar_log_debug( $msg );
			$this->add_save_post_notice( $msg );
		} else {
			$upload['file'] = basename( $upload['file'] );
			update_post_meta( $post_id, 'cuar_private_file_file', $upload );
			cuar_log_debug( 'Uploaded new private file: ' . print_r( $upload, true ) );

			do_action( "cuar_private_file_after_new_upload" );
		}
		
		// Other addons can do something after we save
		do_action( "cuar_private_file_after_do_save_post", $post_id, $this->private_file_addon, $this );
	}

	public function custom_upload_dir( $default_dir ) {
		if ( ! isset( $_POST['post_ID'] ) || $_POST['post_ID'] < 0 ) return $default_dir;	
		if ( $_POST['post_type'] != 'cuar_private_file' ) return $default_dir;
		if ( ! isset( $_POST['cuar_owner'] ) ) return $default_dir;	
	
		$dir = $this->plugin->get_base_upload_directory();
		$url = $this->plugin->get_base_upload_url();
	
		$bdir = $dir;
		$burl = $url;
	
		$subdir = '/' . $this->plugin->get_user_storage_directory( $_POST[ 'cuar_owner' ] );
		
		$dir .= $subdir;
		$url .= $subdir;
	
		$custom_dir = array( 
			'path'    => $dir,
			'url'     => $url, 
			'subdir'  => $subdir, 
			'basedir' => $bdir, 
			'baseurl' => $burl,
			'error'   => false, 
		);
	
		return $custom_dir;
	}

	/*------- CUSTOMISATION OF THE PLUGIN SETTINGS PAGE --------------------------------------------------------------*/

	public function add_settings_tab( $tabs ) {
		$tabs[ 'cuar_private_files' ] = __( 'Customer Files', 'cuar' );
		return $tabs;
	}
	
	/**
	 * Add our fields to the settings page
	 * 
	 * @param CUAR_Settings $cuar_settings The settings class
	 */
	public function print_settings( $cuar_settings, $options_group ) {
// TODO OUTPUT ALLOWED FILE TYPES

		add_settings_section(
				'cuar_private_files_addon_frontend',
				__('Frontend Integration', 'cuar'),
				array( &$this, 'print_frontend_section_info' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG
			);

		add_settings_field(
				self::$OPTION_SHOW_AFTER_POST_CONTENT,
				__('Show after post', 'cuar'),
				array( &$cuar_settings, 'print_input_field' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_private_files_addon_frontend',
				array(
					'option_id' => self::$OPTION_SHOW_AFTER_POST_CONTENT,
					'type' 		=> 'checkbox',
					'after'		=> 
						__( 'Show the view and download links below the post content for a customer file.', 'cuar' ) )
			);
		
		add_settings_field(
				self::$OPTION_FILE_LIST_MODE, 
				__('File list', 'cuar'),
				array( &$cuar_settings, 'print_select_field' ), 
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_private_files_addon_frontend',
				array( 
					'option_id' => self::$OPTION_FILE_LIST_MODE, 
					'options'	=> array( 
						'plain' 	=> __( "Don't group files", 'cuar' ),
						'year' 		=> __( 'Group by year', 'cuar' ),
						'category' 	=> __( 'Group by category', 'cuar' ) ),
	    			'after'	=> '<p class="description">'
	    				. __( 'You can choose how files will be organized by default in the customer area.', 'cuar' )
	    				. '</p>' )
			);	

		add_settings_field(
				self::$OPTION_HIDE_EMPTY_CATEGORIES,
				__('Empty categories', 'cuar'),
				array( &$cuar_settings, 'print_input_field' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_private_files_addon_frontend',
				array(
					'option_id' => self::$OPTION_HIDE_EMPTY_CATEGORIES,
					'type' 		=> 'checkbox',
					'after'		=> 
						__( 'When listing files by category, empty categories will be hidden if you check this.', 
							'cuar' ) )
			);

		add_settings_section(
				'cuar_private_files_addon_storage',
				__('File Storage', 'cuar'),
				array( &$this, 'print_storage_section_info' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG
			);
	}
	
	/**
	 * Validate our options
	 * 
	 * @param CUAR_Settings $cuar_settings
	 * @param array $input
	 * @param array $validated
	 */
	public function validate_options( $validated, $cuar_settings, $input ) {
		// TODO OUTPUT ALLOWED FILE TYPES
		
		$cuar_settings->validate_boolean( $input, $validated, self::$OPTION_SHOW_AFTER_POST_CONTENT );
		$cuar_settings->validate_enum( $input, $validated, self::$OPTION_FILE_LIST_MODE, 
				array( 'plain', 'year', 'category' ) );
		$cuar_settings->validate_boolean( $input, $validated, self::$OPTION_HIDE_EMPTY_CATEGORIES );
		
		return $validated;
	}
	
	/**
	 * Set the default values for the options
	 * 
	 * @param array $defaults
	 * @return array
	 */
	public static function set_default_options( $defaults ) {
		$defaults[ self::$OPTION_SHOW_AFTER_POST_CONTENT ] = true;
		$defaults[ self::$OPTION_FILE_LIST_MODE ] = 'year';
		$defaults[ self::$OPTION_HIDE_EMPTY_CATEGORIES ] = true;

		$admin_role = get_role( 'administrator' );
		$admin_role->add_cap( 'cuar_pf_edit' );
		$admin_role->add_cap( 'cuar_pf_read' );
		
		return $defaults;
	}
	
	/**
	 * Print some info about the section
	 */
	public function print_frontend_section_info() {
		// echo '<p>' . __( 'Options for the private files add-on.', 'cuar' ) . '</p>';
	}
	
	/**
	 * Print some info about the section
	 */
	public function print_storage_section_info() {
		$storage_dir = $this->plugin->get_base_upload_directory();
		$sample_storage_dir = $storage_dir . '/' . $this->plugin->get_user_storage_directory( get_current_user_id() );
		
		$required_perms = '775';
		$current_perms = substr( sprintf('%o', fileperms( $storage_dir ) ), -3);
		
		echo '<p>' 
				. sprintf( __( 'The files will be stored in the following directory: <code>%s</code>.', 'cuar' ),
						$storage_dir ) 
				. '</p>';

		echo '<p>'
				. sprintf( __( 'Each user has his own sub-directory. For instance, yours is: <code>%s</code>.', 'cuar' ),
						$sample_storage_dir )
				. '</p>';

		if ( $required_perms > $current_perms ) {
			echo '<p style="color: red;">' 
				. sprintf( __('That directory should at least have the permissions set to 775. Currently it is '
						. '%s. You should adjust that directory permissions as upload or download might not work ' 
						. 'properly.', 'cuar' ), $current_perms ) 
				. '</p>';
		}
	}

	// Frontend options
	public static $OPTION_SHOW_AFTER_POST_CONTENT		= 'frontend_show_after_post_content';
	public static $OPTION_FILE_LIST_MODE				= 'frontend_file_list_mode';
	public static $OPTION_HIDE_EMPTY_CATEGORIES			= 'frontend_hide_empty_file_categories';
		
	/** @var CUAR_Plugin */
	private $plugin;

	/** @var CUAR_PrivateFileAddOn */
	private $private_file_addon;
}
	
// This filter needs to be executed too early to be registered in the constructor
add_filter( 'cuar_default_options', array( 'CUAR_PrivateFileAdminInterface', 'set_default_options' ) );

endif; // if (!class_exists('CUAR_PrivateFileAdminInterface')) :