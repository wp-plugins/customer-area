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

if (!class_exists('CUAR_PostOwnerAddOn')) :

/**
 * Add-on to provide all the stuff required to set an owner on a post type and include that post type in the 
 * customer area.
 *
 * @author Vincent Prat @ MarvinLabs
 */
class CUAR_PostOwnerAddOn extends CUAR_AddOn {
	
	public function __construct() {
		parent::__construct( 'post-owner', __( 'Post Owner', 'cuar' ), '2.0.0' );
	}

	public function run_addon( $plugin ) {
		$this->plugin = $plugin;

		// Init the admin interface if needed
		if ( is_admin() ) {
			add_action( 'cuar_version_upgraded', array( &$this, 'plugin_version_upgrade' ), 10, 2 );

			add_action('cuar_after_addons_init', array( &$this, 'customize_post_edit_pages'));
			add_action('cuar_after_addons_init', array( &$this, 'customize_post_list_pages'));
			
			add_action('cuar_print_select_options_for_type_user', 
					array( &$this, 'print_select_options_for_type_user'), 10, 3);
		} else {
			add_action( 'template_redirect', array( &$this, 'protect_single_post_access' ) );
		}
	}	
	
	/*------- QUERY FUNCTIONS ---------------------------------------------------------------------------------------*/
	
	/**
	 * Builds the meta query to check if a user owns a post 
	 * @param int $user_id The user ID of the owner
	 * @return array See the meta query documentation on WP codex
	 */
	public function get_meta_query_post_owned_by( $user_id ) {
		$base_meta_query = array(
				'relation' => 'OR',
				array(
						'key' 		=> self::$META_OWNER_QUERYABLE,
						'value' 	=> 'user_' . $user_id,
						'compare' 	=> '='
					)
			);
		
		return apply_filters( 'cuar_meta_query_post_owned_by', $base_meta_query, $user_id );
	}

	/*------- PRIVATE FILE STORAGE DIRECTORIES ----------------------------------------------------------------------*/

	/**
	 * This is the base directory where we will store the user files
	 *
	 * @return string
	 */
	public function get_base_private_storage_directory( $create_dirs = false ) {
		$dir = WP_CONTENT_DIR . '/customer-area';	
		if ( $create_dirs && !file_exists( $dir ) ) mkdir( $dir, 0775, true );	
		return $dir;
	}
	
	/**
	 * This is the base URL where we can access the user files directly (should be protected to forbid direct
	 * downloads)
	 *
	 * @return string
	 */
	public function get_base_private_storage_url() {
		return WP_CONTENT_URL . '/customer-area';
	}
	
	/**
	 * Get the absolute path to a private file.
	 *
	 * @param int $post_id
	 * @param string $filename
	 * @param boolean $create_dirs
	 * @return boolean|string
	 */
	public function get_private_file_path( $filename, $post_id, $create_dirs = false ) {
		if ( empty( $post_id ) || empty( $filename ) ) return false;
	
		$dir = $this->get_base_private_storage_directory() . '/' . $this->get_private_storage_directory( $post_id );
		if ( $create_dirs && !file_exists( $dir ) ) mkdir( $dir, 0775, true );
	
		return $dir . '/' . $filename;
	}
	
	/**
	 * Get the absolute path to a private file.
	 *
	 * @param int $post_id
	 * @param string $filename
	 * @param boolean $create_dirs
	 * @return boolean|string
	 */
	public function get_owner_file_path( $filename, $owner_id, $owner_type, $create_dirs = false ) {
		if ( empty( $owner_id ) || empty( $owner_type ) || empty( $filename ) ) return false;
	
		$dir = $this->get_base_private_storage_directory() . '/' . $this->get_owner_storage_directory( $owner_id, $owner_type );
		if ( $create_dirs && !file_exists( $dir ) ) mkdir( $dir, 0775, true );
	
		return $dir . '/' . $filename;
	}
	
	/**
	 * Get a user's private storage directory. This directory is relative to the main upload directory
	 *
	 * @param int $user_id The id of the user, or null to get the base directory
	 */
	public function get_private_storage_directory( $post_id, $absolute = false, $create_dirs = false ) {
		if ( empty( $post_id ) ) return false;
	
		$owner_id = $this->get_post_owner_id( $post_id );
		$owner_type = $this->get_post_owner_type( $post_id );		
		
		return $this->get_owner_storage_directory($owner_id, $owner_type, $absolute, $create_dirs );
	}
	
	/**
	 * Get a user's private storage directory. This directory is relative to the main upload directory
	 *
	 * @param int $user_id The id of the user, or null to get the base directory
	 */
	public function get_owner_storage_directory( $owner_id, $owner_type, $absolute = false, $create_dirs = false ) {
		if ( empty( $owner_id ) || empty( $owner_type ) ) return false;
	
		$dir = md5( $owner_type . '_' . $owner_id );
	
		if ( $absolute ) $dir = $this->get_base_private_storage_directory() . "/" . $dir;
		if ( $create_dirs && !file_exists( $dir ) ) mkdir( $dir, 0775, true );
	
		return $dir;
	}
	
	/*------- ACCESS TO OWNER INFO ----------------------------------------------------------------------------------*/
	
	/**
	 * Returns all the possible owner types in the form of an associative array. The key is the owner type (should
	 * remain constant) and the value is a string to be displayed in various places (should be internationalised). 
	 * 
	 * @return mixed
	 */
	public function get_owner_types() {
		if ($this->owner_types==null) {
			$this->owner_types = apply_filters('cuar_post_owner_types', array( 'user' => __('User', 'cuar') ) );
		}
		return $this->owner_types;
	}

	
	/**
	 * Check if a user is an owner of the given post. 
	 * 
	 * @param int $post_id
	 * @param int $user_id
	 */
	public function is_user_owner_of_post( $post_id, $user_id ) {
		$result = false;
		
		// We take care of the single user ownership
		$owner_type = $this->get_post_owner_type( $post_id );
		if ( $owner_type=='user' ) {
			$owner_id = $this->get_post_owner_id( $post_id );
			$result = ($owner_id==$user_id);
		} else {
			$result = false;
		}

		return apply_filters( 'cuar_is_user_owner_of_post', $result, $post_id, $user_id, $owner_type, $owner_id );
	}
	
	/**
	 * Get the owner id of the post
	 *
	 * @param int $post_id The post ID
	 * @return int 0 if no owner is set
	 */
	public function get_post_owner_id( $post_id ) {
		$owner_id = get_post_meta( $post_id, self::$META_OWNER_ID, true );
		if ( !$owner_id || empty( $owner_id ) ) $owner_id = 0;
		return $owner_id;
	}

	/**
	 * Get the owner type of the post (user, role, ...)
	 *
	 * @param int $post_id The post ID
	 * @return string the type of ownership (defaults to 'user')
	 */
	public function get_post_owner_type( $post_id ) {
		$owner_type = get_post_meta( $post_id, self::$META_OWNER_TYPE, true );
		if ( !$owner_type || empty( $owner_type ) ) $owner_type = 'user';
		return $owner_type;
	}

	/**
	 * Get the name to be displayed 
	 *
	 * @param int $post_id The post ID
	 * @return string the type of ownership (defaults to 'user')
	 */
	public function get_post_owner_displayname( $post_id, $prefix_with_type=false ) {
		if ($prefix_with_type) {
			$name = get_post_meta( $post_id, self::$META_OWNER_SORTABLE_DISPLAYNAME, true );
			if ( !$name || empty( $name ) ) $name = __( 'Unknown', 'cuar' );
			return apply_filters( 'cuar_get_post_owner_sortable_displayname', $name );
		} else {
			$name = get_post_meta( $post_id, self::$META_OWNER_DISPLAYNAME, true );
			if ( !$name || empty( $name ) ) $name = __( 'Unknown', 'cuar' );
			return apply_filters( 'cuar_get_post_owner_displayname', $name );
		}
	}
	
	/**
	 * Get the owner details (id and type) from post metadata
	 *
	 * @return NULL|array associative array with keys 'id' and 'type'
	 */
	public function get_post_owner( $post_id ) {
		return array(
				'id' 	=> $this->get_post_owner_id( $post_id ),
				'type'	=> $this->get_post_owner_type( $post_id )
			);
	}

	/**
	 * Get the real user ids behind the logical owner of the post
	 *
	 * @return array User ids 
	 */
	public function get_post_owner_user_ids( $post_id ) {
		$owner_id = $this->get_post_owner_id( $post_id );
		$owner_type = $this->get_post_owner_type( $post_id );
		
		// If the owner is already a user, no worries
		if ($owner_type=='user') {
			return array( $owner_id );
		}
		
		// Let other add-ons return what they want
		return apply_filters('cuar_get_post_owner_user_ids_from_' . $owner_type, array(), $owner_id);
	}
	
	/**
	 * Save the owner details for the given post
	 * 
	 * @param int $post_id
	 * @param string $owner_id
	 * @param string $owner_type
	 */
	public function save_post_owner($post_id, $owner_id, $owner_type) {
		$owner_types = $this->get_owner_types();		
		if (!array_key_exists($owner_type, $owner_types)) {
			$this->plugin->add_admin_notice('Invalid owner type, some add-on must be doing something wrong');
			return;
		}
		
		// Some defaults for the owner type 'user' 
		$displayname = '?';
		if ($owner_type=='user') {
			$u = new WP_User($owner_id);
			$displayname = $u->display_name;
		} 
		$displayname = apply_filters('save_post_owner_displayname', $displayname,
				$post_id, $owner_id, $owner_type);
		
		$sortable_displayname = $owner_types[$owner_type] . ' - ' . $displayname;
		$sortable_displayname = apply_filters('save_post_owner_sortable_displayname', $sortable_displayname,
				$post_id, $owner_id, $owner_type, $displayname);

		// Persist data
		update_post_meta($post_id, self::$META_OWNER_ID, $owner_id);
		update_post_meta($post_id, self::$META_OWNER_TYPE, $owner_type);
		update_post_meta($post_id, self::$META_OWNER_QUERYABLE, $owner_type . '_' . $owner_id );	
		update_post_meta($post_id, self::$META_OWNER_DISPLAYNAME, $displayname);
		update_post_meta($post_id, self::$META_OWNER_SORTABLE_DISPLAYNAME, $sortable_displayname);
	}

	/** @var array $owner_types */
	private $owner_types = null;
	
	/*------- CUSTOMISATION OF THE LISTING OF POSTS -----------------------------------------------------------------*/
	
	public function customize_post_list_pages() {
		$post_types = $this->plugin->get_private_post_types();
		foreach ($post_types as $type) {
			add_filter( "manage_edit-{$type}_columns", array( &$this, 'owner_column_register' ));
			add_action( "manage_{$type}_posts_custom_column", array( &$this, 'owner_column_display'), 10, 2 );
			add_filter( "manage_edit-{$type}_sortable_columns", array( &$this, 'owner_column_register_sortable' ));
		}
		add_filter( 'request', array( &$this, 'owner_column_orderby' ));				
	}
	
	/**
	 * Register the owner column
	 */
	public function owner_column_register( $columns ) {
		$columns['cuar_owner'] = __( 'Owner', 'cuar' );
		return $columns;
	}
	
	/**
	 * Display the column content
	 */
	public function owner_column_display( $column_name, $post_id ) {
		if ( 'cuar_owner' != $column_name )
			return;

		echo $this->get_post_owner_displayname( $post_id, true );
	}
	
	/**
	 * Register the column as sortable
	 */
	public function owner_column_register_sortable( $columns ) {
		$columns['cuar_owner'] = 'cuar_owner';	
		return $columns;
	}
	
	/**
	 * Handle sorting of data
	 */
	public function owner_column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'cuar_owner' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
					'meta_key' 	=> self::$META_OWNER_SORTABLE_DISPLAYNAME,
					'orderby' 	=> 'meta_value'
				) );
		}
	
		return $vars;
	}
	
	/*------- CUSTOMISATION OF THE EDIT PAGE FOR A POST WITH OWNER INFO ---------------------------------------------*/

	public function customize_post_edit_pages() {
		add_action( 'admin_menu', array( &$this, 'register_post_edit_meta_boxes' ));
		add_action( 'save_post', array( &$this, 'do_save_post' ));
	}
	
	/**
	 * Register some additional boxes on the page to edit the files
	 */
	public function register_post_edit_meta_boxes() {
		$post_types = $this->plugin->get_private_post_types();		
		foreach ($post_types as $type) {
			add_meta_box( 
					'cuar_post_owner', 
					__('Owner', 'cuar'), 
					array( &$this, 'print_owner_meta_box'), 
					$type, 
					'normal', 
					'high'
				);
		}
	}

	/**
	 * Print the metabox to select the owner of the file
	 */
	public function print_owner_meta_box() {
		global $post;
		wp_nonce_field( plugin_basename(__FILE__), 'wp_cuar_nonce_owner' );
	
		$current_owner_id = $this->get_post_owner_id( $post->ID );
		$current_owner_type = $this->get_post_owner_type( $post->ID );		
		
		do_action( "cuar_owner_meta_box_header" );
		
		$owner_types = $this->get_owner_types();
		
		if (count($owner_types)==1) {
			reset($owner_types);
?>
		<input type="hidden" name="cuar_owner_type" id="cuar_owner_type" value="<?php echo key($owner_types); ?>" />
<?php 			
		} else {
?>
		<div id="cuar_owner_type_row" class="metabox-row">
			<span class="label"><label for="cuar_owner_type">
				<?php _e('Select the type of owner', 'cuar');?></label></span> 	
			<select name="cuar_owner_type" id="cuar_owner_type" >
<?php 		foreach ( $owner_types as $type_id => $type_label ) : 
				$selected =  ( $current_owner_type!=$type_id ? '' : ' selected="selected"' ); 
?>
				<option value="<?php echo $type_id;?>"<?php echo $selected; ?>><?php echo $type_label; ?></option>
<?php 		endforeach; ?>				
			</select>
		</div>
<?php 						
		}
		
		foreach ( $owner_types as $type_id => $type_label ) { 
			$hidden = ( $current_owner_type==$type_id ? '' : ' style="display: none;"' );  
?>
		<div id="cuar_owner_id_row_<?php echo $type_id; ?>" class="metabox-row owner-id-select-row" <?php echo $hidden; ?>>
			<span class="label"><label for="cuar_owner">
				<?php printf( __('%s owning this content', 'cuar'), $type_label ); ?></label></span> 	
			<span class="field">
				<?php $field_id = 'cuar_owner_' . $type_id . '_id'; ?>
				<select id="<?php echo $field_id; ?>" name="<?php echo $field_id; ?>">
				<?php do_action( 'cuar_print_select_options_for_type_' . $type_id, 
						$current_owner_type, 
						$current_owner_id ); ?>
				</select>
			</span>
		</div>
<?php
		}
?>
		<script type="text/javascript">
		<!--
			jQuery( document ).ready( function($) {
				$( '#cuar_owner_type' ).change(function() {
					var type = $(this).val();
					var newVisibleId = '#cuar_owner_' + type + '_id';

					// Do nothing if already visible
					if ( $(newVisibleId).is(":visible") ) return

					// Hide previous and then show new
					$('.owner-id-select-row:visible').fadeToggle("fast", function () {
						$(newVisibleId).fadeToggle();
					});
				});
			});
		//-->
		</script>
		
<?php 			
		do_action( "cuar_owner_meta_box_footer" );
	}
	
	/**
	 * Print a select field with all users
	 * @param unknown $field_id
	 * @param unknown $current_owner_type
	 * @param unknown $current_owner_id
	 */
	public function print_select_options_for_type_user( $current_owner_type, $current_owner_id ) {
		echo sprintf( '<select id="%1$s" name="%1$s">', $field_id );
		
		$all_users = get_users();
		foreach ( $all_users as $u ) {
			$selected =  ( $current_owner_type=='user' && $current_owner_id==$u->ID ) ? ' selected="selected"' : '';
			echo sprintf('<option value="%1$s" %2$s>%3$s</option>',
					$u->ID,
					$selected,
					$u->display_name
				);
		}
		
		echo '</select>';
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
	
		// Only take care of private post types
		$private_post_types = $this->plugin->get_private_post_types();	
		if ( !$post || !in_array( get_post_type( $post->ID ), $private_post_types ) ) return;
		
		// Save the owner details
		if ( !wp_verify_nonce( $_POST['wp_cuar_nonce_owner'], plugin_basename(__FILE__) ) ) return $post_id;
		
		$previous_owner = $this->get_post_owner( $post_id );
		$new_owner = $this->get_owner_from_post_data();
	
		// Other addons can do something before we save
		do_action( "cuar_before_save_post_owner", $post_id, $previous_owner, $new_owner );
		
		// Save owner details
		$this->save_post_owner( $post_id, $new_owner['id'], $new_owner['type'] );
		
		// Other addons can do something after we save
		do_action( "cuar_after_save_post_owner", $post_id, $post, $previous_owner, $new_owner );
		do_action( "cuar_after_save_private_post", $post_id, $post, $previous_owner, $new_owner );

		return $post_id;
	}
	
	/**
	 * Get the owner details (id and type) from HTTP POST data
	 * 
	 * @return NULL|array associative array with keys 'id' and 'type'
	 */
	public function get_owner_from_post_data() {
		if ( !isset($_POST['cuar_owner_type']) 
				|| !isset($_POST['cuar_owner_' . $_POST['cuar_owner_type'] . '_id']) ) {
			return null;
		}
		
		return array(
				'id' 	=> $_POST['cuar_owner_' . $_POST['cuar_owner_type'] . '_id'],
				'type'	=> $_POST['cuar_owner_type']
			);
	}

	/*------- FRONTEND ----------------------------------------------------------------------------------------------*/

	/**
	 * Protect access to single posts: only for author and owner.
	 */
	public function protect_single_post_access() {
		$private_post_types = $this->plugin->get_private_post_types();
			
		// If not on a matching post type, we do nothing
		if ( !is_singular( $private_post_types ) ) return;
	
		// If not logged-in, we ask for details
		if ( !is_user_logged_in() ) {
			// TODO SHOW LOGIN LINKS INSTEAD!!!
			wp_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
			exit;
		}
	
		// If not authorized to view the page, we bail
		$post = get_queried_object();
		$author_id = $post->post_author;
	
		$current_user_id = get_current_user_id();
	
		$is_current_user_owner = $this->is_user_owner_of_post( $post->ID, $current_user_id );
		if ( !( $is_current_user_owner || $author_id==$current_user_id )) {
			wp_die( __( "You are not authorized to view this page", "cuar" ) );
			exit();
		}
	}
	
	/*------- OTHER FUNCTIONS ---------------------------------------------------------------------------------------*/
	
	/**
	 * When the plugin is upgraded
	 * 
	 * @param unknown $from_version
	 * @param unknown $to_version
	 */
	public function plugin_version_upgrade( $from_version, $to_version ) {
		// If upgrading from before 2.0.0 we must update the post meta fields 
		if ( $from_version<'2.0.0' ) {
			global $wpdb;
			
			// Find all existing owner ids
			$owner_metas = $wpdb->get_results( $wpdb->prepare(
				  		  "SELECT meta_id, post_id, meta_key, meta_value "
						. "FROM $wpdb->postmeta "
						. "WHERE meta_key = %s", 
					'cuar_owner') );
			
			foreach ($owner_metas as $m) {	
				// Before 2.0.0 there was no owner type, so default to 'user'	
				$owner_type = 'user';	
				$owner_id = $m->meta_value;
				
				// Add post meta (owner type, display names)
				$u = new WP_User($owner_id);
				$display_name = $u->display_name;
				$sortable_display_name = sprintf( __('User: %s', 'cuar'), $u->display_name);

				update_post_meta($m->post_id, self::$META_OWNER_TYPE, $owner_type );
				update_post_meta($m->post_id, self::$META_OWNER_DISPLAYNAME, $display_name);	
				update_post_meta($m->post_id, self::$META_OWNER_SORTABLE_DISPLAYNAME, $sortable_display_name);
				update_post_meta($m->post_id, self::$META_OWNER_QUERYABLE, $owner_type . '_' . $owner_id );	
				
				// If owner had a directory, rename that directory into the new naming scheme
				$base_storage_directory = $this->get_base_private_storage_directory(true);
				$new_name = $this->get_owner_storage_directory($owner_id, $owner_type);
				$old_name = get_user_meta($owner_id, 'cuar_directory', true);
				
				if (!empty($old_name)) {
					if (file_exists($base_storage_directory . "/" . $old_name)) {
						rename( $base_storage_directory . "/" . $old_name,  $base_storage_directory . "/" . $new_name );
					}
					delete_user_meta($owner_id, 'cuar_directory');
				}
			}
		}
	}

	public static $META_OWNER_QUERYABLE				= 'cuar_owner_queryable';
	public static $META_OWNER_ID 					= 'cuar_owner';
	public static $META_OWNER_TYPE 					= 'cuar_owner_type';
	public static $META_OWNER_DISPLAYNAME 			= 'cuar_owner_displayname';
	public static $META_OWNER_SORTABLE_DISPLAYNAME 	= 'cuar_owner_sortable_displayname';
	
	/** @var CUAR_Plugin */
	private $plugin;
}

// Make sure the addon is loaded
global $cuar_po_addon;
$cuar_po_addon = new CUAR_PostOwnerAddOn();

endif; // if (!class_exists('CUAR_PrivateFileAddOn')) 
