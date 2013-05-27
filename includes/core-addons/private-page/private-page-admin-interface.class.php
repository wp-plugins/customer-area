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

if (!class_exists('CUAR_PrivatePageAdminInterface')) :

/**
 * Administation area for private files
 * 
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_PrivatePageAdminInterface {
	
	public function __construct( $plugin, $private_page_addon ) {
		$this->plugin = $plugin;
		$this->private_page_addon = $private_page_addon;

		// Settings
		add_filter( 'cuar_addon_settings_tabs', array( &$this, 'add_settings_tab' ), 10, 1 );
		add_action( 'cuar_addon_print_settings_cuar_private_pages', array( &$this, 'print_settings' ), 10, 2 );
		add_filter( 'cuar_addon_validate_options_cuar_private_pages', array( &$this, 'validate_options' ), 10, 3 );
		
		if ( $plugin->get_option( self::$OPTION_ENABLE_ADDON ) ) {
			// Admin menu
			add_action('cuar_admin_submenu_pages', array( &$this, 'add_menu_items' ), 11 );			
			add_action( "admin_footer", array( &$this, 'highlight_menu_item' ) );
			
			// Page listing
			add_filter( 'manage_edit-cuar_private_page_columns', array( &$this, 'user_column_register' ));
			add_action( 'manage_cuar_private_page_posts_custom_column', array( &$this, 'user_column_display'), 10, 2 );
			add_filter( 'manage_edit-cuar_private_page_sortable_columns', array( &$this, 'user_column_register_sortable' ));
			add_filter( 'request', array( &$this, 'user_column_orderby' ));
	
			// Page edit page
			add_action( 'admin_menu', array( &$this, 'register_edit_page_meta_boxes' ));
			add_action( 'save_post', array( &$this, 'do_save_post' ));
			add_action( 'admin_notices', array( &$this, 'print_save_post_messages' ));
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
					'page_title'	=> __( 'Private Pages', 'cuar' ),
					'title'			=> $separator . __( 'Private Pages', 'cuar' ),
					'slug'			=> "edit.php?post_type=cuar_private_page",
					'function' 		=> null,
					'capability'	=> 'cuar_pp_edit'
				),
				array(
					'page_title'	=> __( 'New Private Page', 'cuar' ),
					'title'			=> __( 'New Private Page', 'cuar' ),
					'slug'			=> "post-new.php?post_type=cuar_private_page",
					'function' 		=> null,
					'capability'	=> 'cuar_pp_edit'
				),
			); 
	
		foreach ( $my_submenus as $submenu ) {
			$submenus[] = $submenu;
		}
	
		return $submenus;
	}
			
	/**
	 * Highlight the proper menu item in the customer area
	 */
	public function highlight_menu_item() {
		global $post;
		
		// For posts
		if ( isset( $post ) && get_post_type( $post )=='cuar_private_page' ) {		
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
	
		$owner_id = $this->private_page_addon->get_page_owner_id( $post_id );
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
		$columns['cuar_owner'] = 'cuar_owner';
	
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
	 * Register some additional boxes on the page to edit the files
	 */
	public function register_edit_page_meta_boxes() {		
		add_meta_box( 
				'cuar_private_page_owner', 
				__('Owner', 'cuar'), 
				array( &$this, 'print_owner_meta_box'), 
				'cuar_private_page', 
				'normal', 'high');
	}

	/**
	 * Print the metabox to select the owner of the file
	 */
	public function print_owner_meta_box() {
		global $post;
		wp_nonce_field( plugin_basename(__FILE__), 'wp_cuar_nonce_owner' );
	
		$current_uid = $this->private_page_addon->get_page_owner_id( $post->ID );		
		$all_users = get_users();
		
		do_action( "cuar_private_page_owner_meta_box_header" );
?>
		<div id="cuar-owner" class="metabox-row">
			<span class="label"><label for="cuar_owner"><?php _e('Select the owner of this page', 'cuar');?></label></span> 	
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
		do_action( "cuar_private_page_owner_meta_box_footer" );
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
		if ( isset( $_SESSION['cuar_private_page_save_post_notices'] ) ) {
			unset( $_SESSION['cuar_private_page_save_post_notices'] ); 
		}
	}

	/**
	 * Remove the stored notices
	 */
	private function get_save_post_notices() {
		return empty( $_SESSION[ 'cuar_private_page_save_post_notices' ] ) 
				? false 
				: $_SESSION['cuar_private_page_save_post_notices'];
	}
	
	public function add_save_post_notice( $msg, $type = 'error' ) {
		if ( empty( $_SESSION[ 'cuar_private_page_save_post_notices' ] ) ) {
			$_SESSION[ 'cuar_private_page_save_post_notices' ] = array();
	 	}
	 	$_SESSION[ 'cuar_private_page_save_post_notices' ][] = array(
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
		if ( !$post || get_post_type( $post->ID )!='cuar_private_page' ) return;
	
		// Other addons can do something before we save
		do_action( "cuar_private_page_before_do_save_post" );
		
		// Save the owner details
		if ( !wp_verify_nonce( $_POST['wp_cuar_nonce_owner'], plugin_basename(__FILE__) ) ) return $post_id;

		$previous_owner_id = $this->private_page_addon->get_page_owner_id( $post_id );
		$new_owner_id = $_POST['cuar_owner'];
		update_post_meta( $post_id, 'cuar_owner', $new_owner_id );
		
		// Other addons can do something after we save
		do_action( "cuar_private_page_after_do_save_post", $post_id, $this->private_page_addon, $this );
	}

	/*------- CUSTOMISATION OF THE PLUGIN SETTINGS PAGE --------------------------------------------------------------*/

	public function add_settings_tab( $tabs ) {
		$tabs[ 'cuar_private_pages' ] = __( 'Private Pages', 'cuar' );
		return $tabs;
	}
	
	/**
	 * Add our fields to the settings page
	 * 
	 * @param CUAR_Settings $cuar_settings The settings class
	 */
	public function print_settings( $cuar_settings, $options_group ) {
		add_settings_section(
				'cuar_private_pages_addon_general',
				__('General settings', 'cuar'),
				array( &$this, 'print_frontend_section_info' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG
			);

		add_settings_field(
				self::$OPTION_ENABLE_ADDON,
				__('Enable add-on', 'cuar'),
				array( &$cuar_settings, 'print_input_field' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_private_pages_addon_general',
				array(
					'option_id' => self::$OPTION_ENABLE_ADDON,
					'type' 		=> 'checkbox',
					'after'		=> 
						__( 'Check this to enable the private pages add-on.', 'cuar' ) )
			);
/*		
		add_settings_section(
				'cuar_private_pages_addon_frontend',
				__('Frontend Integration', 'cuar'),
				array( &$this, 'print_frontend_section_info' ),
				CUAR_Settings::$OPTIONS_PAGE_SLUG
			);
		
		add_settings_field(
				self::$OPTION_FILE_LIST_MODE, 
				__('Page list', 'cuar'),
				array( &$cuar_settings, 'print_select_field' ), 
				CUAR_Settings::$OPTIONS_PAGE_SLUG,
				'cuar_private_pages_addon_frontend',
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
*/
	}
	
	/**
	 * Validate our options
	 * 
	 * @param CUAR_Settings $cuar_settings
	 * @param array $input
	 * @param array $validated
	 */
	public function validate_options( $validated, $cuar_settings, $input ) {		
		$cuar_settings->validate_boolean( $input, $validated, self::$OPTION_ENABLE_ADDON );
//		$cuar_settings->validate_enum( $input, $validated, self::$OPTION_FILE_LIST_MODE, 
//				array( 'plain', 'year', 'category' ) );
		
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
		// $defaults[ self::$OPTION_FILE_LIST_MODE ] = 'year';

		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'cuar_pp_edit' );
			$admin_role->add_cap( 'cuar_pp_read' );
		}
		
		return $defaults;
	}
	
	/**
	 * Print some info about the section
	 */
	public function print_frontend_section_info() {
		// echo '<p>' . __( 'Options for the private files add-on.', 'cuar' ) . '</p>';
	}

	// General options
	public static $OPTION_ENABLE_ADDON					= 'enable_private_pages';

	// Frontend options
	// public static $OPTION_FILE_LIST_MODE				= 'frontend_page_list_mode';
		
	/** @var CUAR_Plugin */
	private $plugin;

	/** @var CUAR_PrivatePageAddOn */
	private $private_page_addon;
}
	
// This filter needs to be executed too early to be registered in the constructor
add_filter( 'cuar_default_options', array( 'CUAR_PrivatePageAdminInterface', 'set_default_options' ) );

endif; // if (!class_exists('CUAR_PrivatePageAdminInterface')) :