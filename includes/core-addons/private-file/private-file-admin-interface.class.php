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
		global $cuar_po_addon;
		
		$this->plugin = $plugin;
		$this->private_file_addon = $private_file_addon;

		// Settings
		add_filter( 'cuar_addon_settings_tabs', array( &$this, 'add_settings_tab' ), 10, 1 );
		add_action( 'cuar_addon_print_settings_cuar_private_files', array( &$this, 'print_settings' ), 10, 2 );
		add_filter( 'cuar_addon_validate_options_cuar_private_files', array( &$this, 'validate_options' ), 10, 3 );
		
		if ( $plugin->get_option( self::$OPTION_ENABLE_ADDON ) ) {
			// Admin menu
			add_action('cuar_admin_submenu_pages', array( &$this, 'add_menu_items' ), 10 );
			add_action( "admin_footer", array( &$this, 'highlight_menu_item' ) );
			
			// File edit page
			add_action( 'admin_menu', array( &$this, 'register_edit_page_meta_boxes' ) );
			add_action( 'cuar_after_save_post_owner', array( &$this, 'do_save_post' ), 10, 4 );
				
			add_action( 'post_edit_form_tag' , array( &$this, 'post_edit_form_tag' ) );			
		}		
	}
			
	/**
	 * Highlight the proper menu item in the customer area
	 */
	public function highlight_menu_item() {
		global $post;
		
		// For posts
		if ( isset( $_REQUEST['taxonomy'] ) && $_REQUEST['taxonomy']=='cuar_private_file_category' ) {		
			$highlight_top 	= '#toplevel_page_customer-area';
			$unhighligh_top = '#menu-posts';
		} else if ( isset( $post ) && get_post_type( $post )=='cuar_private_file' ) {		
			$highlight_top 	= '#toplevel_page_customer-area';
			$unhighligh_top = '#menu-posts';
		} else {
			$highlight_top 	= null;
			$unhighligh_top = null;
		}
		
		if ( $highlight_top && $unhighligh_top ) {
?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	$('<?php echo $unhighligh_top; ?>')
		.removeClass('wp-has-current-submenu')
		.addClass('wp-not-current-submenu');
	$('<?php echo $highlight_top; ?>')
		.removeClass('wp-not-current-submenu')
		.addClass('wp-has-current-submenu current');
});     
</script>
<?php
		}
	}

	/**
	 * Add the menu item
	 */
	public function add_menu_items( $submenus ) {
		$separator = '<span style="display:block;  
				        margin: 3px 5px 6px -5px; 
				        padding:0; 
				        height:1px; 
				        line-height:1px; 
				        background:#ddd;"></span>';
		
		$my_submenus = array(
				array(
					'page_title'	=> __( 'Private Files', 'cuar' ),
					'title'			=> $separator . __( 'Private Files', 'cuar' ),
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
	 * Callback to handle saving a post
	 *  
	 * @param int $post_id
	 * @param unknown $post
	 * @param array $previous_owner
	 * @param array $new_owner
	 * @return void|unknown
	 */
	public function do_save_post( $post_id, $post, $previous_owner, $new_owner ) {
		global $post;
		
		// When auto-saving, we don't do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
	
		// Only take care of our own post type
		if ( !$post || get_post_type( $post->ID )!='cuar_private_file' ) return;
	
		// Security check
		if ( !wp_verify_nonce( $_POST['wp_cuar_nonce_file'], plugin_basename(__FILE__) ) ) return $post_id;

		// If nothing to upload but owner changed, we'll simply move the file
		$has_owner_changed = false;
		if (    ( $new_owner['type']!=$previous_owner['type'] ) 
			|| !( array_diff( $previous_owner['ids'], $new_owner['ids']) === array_diff( $new_owner['ids'], $previous_owner['ids'] ) ) ) {
			$has_owner_changed = true;
		}
		
		if ( $has_owner_changed && empty( $_FILES['cuar_private_file_file']['name'] ) ) {
			$this->private_file_addon->handle_private_file_owner_changed($post_id, $previous_owner, $new_owner);
			return $post_id;		
		}
		
		if ( !empty( $_FILES['cuar_private_file_file']['name'] ) ) {
			$this->private_file_addon->handle_new_private_file_upload( $post_id, $previous_owner, $new_owner, 
					$_FILES['cuar_private_file_file']);
		}
	}

	/*------- CUSTOMISATION OF THE PLUGIN SETTINGS PAGE --------------------------------------------------------------*/

	public function add_settings_tab( $tabs ) {
		$tabs[ 'cuar_private_files' ] = __( 'Private Files', 'cuar' );
		return $tabs;
	}
	
	/**
	 * Add our fields to the settings page
	 * 
	 * @param CUAR_Settings $cuar_settings The settings class
	 */
	public function print_settings( $cuar_settings, $options_group ) {
		add_settings_section(
				'cuar_private_files_addon_general',
				__('General settings', 'cuar'),
				array( &$this, 'print_frontend_section_info' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG
			);

		add_settings_field(
				self::$OPTION_ENABLE_ADDON,
				__('Enable add-on', 'cuar'),
				array( &$cuar_settings, 'print_input_field' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_private_files_addon_general',
				array(
					'option_id' => self::$OPTION_ENABLE_ADDON,
					'type' 		=> 'checkbox',
					'after'		=> 
						__( 'Check this to enable the private files add-on.', 'cuar' ) )
			);
		
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
		
		$cuar_settings->validate_boolean( $input, $validated, self::$OPTION_ENABLE_ADDON );
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
		$defaults[ self::$OPTION_ENABLE_ADDON ] = true;
		$defaults[ self::$OPTION_SHOW_AFTER_POST_CONTENT ] = true;
		$defaults[ self::$OPTION_FILE_LIST_MODE ] = 'year';
		$defaults[ self::$OPTION_HIDE_EMPTY_CATEGORIES ] = true;

		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'cuar_pf_edit' );
			$admin_role->add_cap( 'cuar_pf_delete' );
			$admin_role->add_cap( 'cuar_pf_read' );
			$admin_role->add_cap( 'cuar_pf_manage_categories' );
			$admin_role->add_cap( 'cuar_pf_edit_categories' );
			$admin_role->add_cap( 'cuar_pf_delete_categories' );
			$admin_role->add_cap( 'cuar_pf_assign_categories' );
		}
		
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
		$po_addon = $this->plugin->get_addon('post-owner');
		$storage_dir = $po_addon->get_base_private_storage_directory( true );
		$sample_storage_dir = $po_addon->get_owner_storage_directory( array( get_current_user_id() ), 'usr', true, true );
		
		$required_perms = '705';
		$current_perms = substr( sprintf('%o', fileperms( $storage_dir ) ), -3);
		
		echo '<div class="cuar-section-description">';
		echo '<p>' 
				. sprintf( __( 'The files will be stored in the following directory: <code>%s</code>.', 'cuar' ),
						$storage_dir ) 
				. '</p>';

		echo '<p>'
				. sprintf( __( 'Each user has his own sub-directory. For instance, yours is: <code>%s</code>.', 'cuar' ),
						$sample_storage_dir )
				. '</p>';

		if ( $required_perms > $current_perms ) {
			echo '<p style="color: orange;">' 
				. sprintf( __('That directory should at least have the permissions set to 705. Currently it is '
						. '%s. You should adjust that directory permissions as upload or download might not work ' 
						. 'properly.', 'cuar' ), $current_perms ) 
				. '</p>';
		}
		echo '</div>';
	}

	// General options
	public static $OPTION_ENABLE_ADDON					= 'enable_private_files';

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