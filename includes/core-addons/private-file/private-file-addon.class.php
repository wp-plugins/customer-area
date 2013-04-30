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

require_once( __DIR__ . '/private-file-admin-interface.class.php' );
require_once( __DIR__ . '/private-file-frontend-interface.class.php' );
require_once( __DIR__ . '/private-file-theme-utils.class.php' );

if (!class_exists('CUAR_PrivateFileAddOn')) :

/**
 * Add-on to put private files in the customer area
*
* @author Vincent Prat @ MarvinLabs
*/
class CUAR_PrivateFileAddOn extends CUAR_AddOn {

	public function run_addon( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'init', array( &$this, 'register_custom_types' ) );
		add_action( 'init', array( &$this, 'add_post_type_rewrites' ) );
		add_filter( 'post_type_link', array( &$this, 'built_post_type_permalink' ), 1, 3);
		
		add_filter( 'query_vars', array( &$this, 'add_query_vars' ) );
		add_action( 'init', array( &$this, 'add_rewrite_endpoints' ) );
		add_action( 'template_redirect', array( &$this, 'handle_file_actions' ) );
		add_action( 'template_redirect', array( &$this, 'protect_access' ) );
		add_filter( 'request', array( &$this, 'ensure_endpoint_queryvar_values' ) );
		
		// Init the admin interface if needed
		if ( is_admin() ) {
			$this->admin_interface = new CUAR_PrivateFileAdminInterface( $plugin, $this );
		} else {
			$this->frontend_interface = new CUAR_PrivateFileFrontendInterface( $plugin, $this );
		}
	}	
	
	/*------- FUNCTIONS TO ACCESS THE POST META ----------------------------------------------------------------------*/

	/**
	 * Get the name of the file associated to the given post
	 *
	 * @param int $post_id
	 * @return boolean|int
	 */
	public static function get_file_owner_id( $post_id ) {
		$owner_id = get_post_meta( $post_id, 'cuar_owner', true );
		if ( !$owner_id || empty( $owner_id ) ) return false;
		return apply_filters( 'cuar_get_file_owner_id', $owner_id );
	}

	/**
	 * Get the name of the file associated to the given post
	 *
	 * @param int $post_id
	 * @return string|mixed
	 */
	public static function get_file_name( $post_id ) {
		$file = get_post_meta( $post_id, 'cuar_private_file_file', true );	
		if ( !$file || empty( $file ) ) return '';	
		return apply_filters( 'cuar_get_file_name', $file['file'] );
	}
	
	/**
	 * Get the type of the file associated to the given post
	 *
	 * @param int $post_id
	 * @return string|mixed
	 */
	public static function get_file_type( $post_id ) {
		$file = get_post_meta( $post_id, 'cuar_private_file_file', true );	
		if ( !$file || empty( $file ) ) return '';	
		return apply_filters( 'cuar_get_file_type', pathinfo( $file['file'], PATHINFO_EXTENSION ) );
	}
	
	/**
	 * Get the permalink to a file for the specified action
	 * @param int $post_id
	 * @param string $action
	 */
	public function get_file_permalink( $post_id, $action = 'download' ) {
		global $wp_rewrite;
		
		$url = get_permalink( $post_id );
		
		cuar_log_debug( $post_id );
		cuar_log_debug( $url );
		
		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $url );
	
			if ( $action=="download" ) {
				$url .= _x( 'download-file', 'URL slug', 'cuar' );
			} else if ( $action=="view" ) {
				$url .= _x( 'view-file', 'URL slug', 'cuar' );
			} else {
				cuar_log_debug( "CUAR_PrivateFileAddOn::get_file_permalink - unknown action" );
			}
		} else {
			if ( $action=="download" ) {
				$url .= '&' . _x( 'download-file', 'URL slug', 'cuar' ) . '=1';
			} else if ( $action=="view" ) {
				$url .= '&' . _x( 'view-file', 'URL slug', 'cuar' ) . '=1';
			} else {
				cuar_log_debug( "CUAR_PrivateFileAddOn::get_file_permalink - unknown action" );
			}
		}
		
		return $url;
	}

	/*------- HANDLE FILE VIEWING AND DOWNLOADING --------------------------------------------------------------------*/
	
	/**
	 * Add query vars to WordPress 
	 */
	public function add_query_vars( $vars ) {
		$vars[] = _x( 'download-file', 'URL slug', 'cuar' );
		$vars[] = _x( 'view-file', 'URL slug', 'cuar' );
		return $vars;
	}
	
	/**
	 * Add rewrite end-points to download or view the files associated to a post
	 */
	public function add_rewrite_endpoints() {
		add_rewrite_endpoint( _x( 'download-file', 'URL slug', 'cuar' ) , EP_PERMALINK );
		add_rewrite_endpoint( _x( 'view-file', 'URL slug', 'cuar' ) , EP_PERMALINK );
	}
	
	/**
	 * 
	 * @param unknown $vars
	 * @return boolean
	 */
	public function ensure_endpoint_queryvar_values( $vars ) {
		if ( isset( $vars[ _x( 'download-file', 'URL slug', 'cuar' ) ] ) ) {
			$vars[ _x( 'download-file', 'URL slug', 'cuar' ) ] = true;
		}
		if ( isset( $vars[ _x( 'view-file', 'URL slug', 'cuar' ) ] ) ) {
			$vars[ _x( 'view-file', 'URL slug', 'cuar' ) ] = true;
		}
		
		return $vars;
	}
	
	/**
	 * Protect access to single pages for private files: only for author and owner.
	 */
	public function protect_access() {		
		// If not on a matching post type, we do nothing
		if ( !is_singular('cuar_private_file') ) return;

		// If not authorized to download the file, we bail	
		$post = get_queried_object();
		$author_id = $post->post_author;

		$current_user_id = get_current_user_id();
		$owner_id = $this->get_file_owner_id( $post->ID );
		
		if ( $owner_id!=$current_user_id && $author_id!=$current_user_id ) {
			wp_die( __( "You are not authorized to view this file", "cuar" ) );
			exit();
		}
	}
	
	/**
	 * Handle the actions on a private file
	 */
	public function handle_file_actions() {		
		// If not on a matching post type, we do nothing
		if ( !is_singular('cuar_private_file') ) return;
		
		// If not a known action, do nothing
		if ( !get_query_var( _x( 'download-file', 'URL slug', 'cuar' ) )
				&& !get_query_var( _x( 'view-file', 'URL slug', 'cuar' ) ) ) {
			return;
		}
		
		// If not logged-in, we ask for details
		if ( !is_user_logged_in() ) {
			wp_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
			exit;
		}

		// If not authorized to download the file, we bail	
		$post = get_queried_object();

		$current_user_id = get_current_user_id();
		$owner_id = $this->get_file_owner_id( $post->ID );
		if ( $owner_id!=$current_user_id ) {
			wp_die( __( "You are not authorized to access this file", "cuar" ) );
			exit();
		}

		if ( !apply_filters( 'cuar_private_file_authorize_user', true ) ) {
			wp_die( __( "You are not authorized to access this file", "cuar" ) );
			exit();
		}
		
		// Seems we are all good, checkout the requested action and do something
		$file_type = $this->get_file_type( $post->ID );
		$file_name = $this->get_file_name( $post->ID );
		$file_path = $this->plugin->get_user_file_path( $current_user_id, $file_name );

		if ( get_query_var( _x( 'download-file', 'URL slug', 'cuar' ) ) ) {
			do_action( 'cuar_private_file_download', $post->ID, $current_user_id );	
					
			$this->output_file( $file_path, $file_name, $file_type, 'download' );
		} else if ( get_query_var( _x( 'view-file', 'URL slug', 'cuar' ) ) ) {
			do_action( 'cuar_private_file_view', $post->ID, $current_user_id );		
			
			$this->output_file( $file_path, $file_name, $file_type, 'view' );
		} else {
			// Do nothing
		}
	}

	/**
	 * Output the content of a file to the http stream
	 *
	 * @param string $file The path to the file
	 * @param string $name The name to give to the file
	 * @param string $mime_type The mime type (determined automatically for well-known file types if blank)
	 * @param string $action view or download the file (hint to the browser)
	 */
	private function output_file( $file, $name, $mime_type='', $action = 'download' ) {
		if ( !is_readable($file) ) {
			die('File not found or inaccessible!<br />'.$file.'<br /> '.$name);
			return;
		}

		$size = filesize( $file );
		$name = rawurldecode( $name );

		$known_mime_types = array(
				"pdf" 	=> "application/pdf",
				"txt" 	=> "text/plain",
				"html" 	=> "text/html",
				"htm" 	=> "text/html",
				"exe" 	=> "application/octet-stream",
				"zip" 	=> "application/zip",
				"doc"	=> "application/msword",
				"xls" 	=> "application/vnd.ms-excel",
				"ppt" 	=> "application/vnd.ms-powerpoint",
				"gif" 	=> "image/gif",
				"png" 	=> "image/png",
				"jpeg"	=> "image/jpg",
				"jpg" 	=> "image/jpg",
				"php" 	=> "text/plain",
				"xml" 	=> "text/xml"
			);

		if ( $mime_type=='' ){
			$file_extension = pathinfo( $file, PATHINFO_EXTENSION );
			if ( array_key_exists( $file_extension, $known_mime_types ) ){
				$mime_type = $known_mime_types[ $file_extension ];
			} else {
				$mime_type = "application/force-download";
			}
		};

		@ob_end_clean(); //turn off output buffering to decrease cpu usage

		// required for IE, otherwise Content-Disposition may be ignored
		if ( ini_get('zlib.output_compression') ) ini_set('zlib.output_compression', 'Off');

		header('Content-Type: ' . $mime_type);
		if ( $action == 'download' ) header('Content-Disposition: attachment; filename="'.$name.'"');
		else header('Content-Disposition: inline; filename="'.$name.'"');
		header("Content-Transfer-Encoding: binary");
		header('Accept-Ranges: bytes');

		/* The three lines below basically make the	download non-cacheable */
		header("Cache-control: private");
		header('Pragma: private');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

		// multipart-download and download resuming support
		if ( isset($_SERVER['HTTP_RANGE']) ) {
			list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
			list($range) = explode(",",$range,2);
			list($range, $range_end) = explode("-", $range);
			$range=intval($range);

			if(!$range_end) {
				$range_end=$size-1;
			} else {
				$range_end=intval($range_end);
			}

			$new_length = $range_end-$range+1;
			header("HTTP/1.1 206 Partial Content");
			header("Content-Length: $new_length");
			header("Content-Range: bytes $range-$range_end/$size");
		} else {
			$new_length=$size;
			header("Content-Length: ".$size);
		}

		/* output the file itself */
		$chunksize = 1*(1024*1024); //you may want to change this
		$bytes_send = 0;
		if ($file = fopen($file, 'r')) {
			if(isset($_SERVER['HTTP_RANGE']))
				fseek($file, $range);

			while(!feof($file) && (!connection_aborted()) && ($bytes_send<$new_length)) {
				$buffer = fread($file, $chunksize);
				print($buffer); //echo($buffer); // is also possible
				flush();
				$bytes_send += strlen($buffer);
			}
			fclose($file);
		}
		else die('Error - can not open file.');

		die();
	}

	/*------- INITIALISATIONS ----------------------------------------------------------------------------------------*/

	/**
	 * Register the custom post type for files and the associated taxonomies
	 */
	public function register_custom_types() {
		$labels = array(
				'name' 				=> _x( 'Customer Files', 'cuar_private_file', 'cuar' ),
				'singular_name' 	=> _x( 'Customer File', 'cuar_private_file', 'cuar' ),
				'add_new' 			=> _x( 'Add New', 'cuar_private_file', 'cuar' ),
				'add_new_item' 		=> _x( 'Add New Customer File', 'cuar_private_file', 'cuar' ),
				'edit_item' 		=> _x( 'Edit Customer File', 'cuar_private_file', 'cuar' ),
				'new_item' 			=> _x( 'New Customer File', 'cuar_private_file', 'cuar' ),
				'view_item' 		=> _x( 'View Customer File', 'cuar_private_file', 'cuar' ),
				'search_items' 		=> _x( 'Search Customer Files', 'cuar_private_file', 'cuar' ),
				'not_found' 		=> _x( 'No customer files found', 'cuar_private_file', 'cuar' ),
				'not_found_in_trash'=> _x( 'No customer files found in Trash', 'cuar_private_file', 'cuar' ),
				'parent_item_colon' => _x( 'Parent Customer File:', 'cuar_private_file', 'cuar' ),
				'menu_name' 		=> _x( 'Customer Files', 'cuar_private_file', 'cuar' ),
		);

		$args = array(
				'labels' 				=> $labels,
				'hierarchical' 			=> false,
				'supports' 				=> array( 'title', 'editor', 'author', 'thumbnail' ),
				'taxonomies' 			=> array( 'cuar_private_file_category' ),
				'public' 				=> true,
				'show_ui' 				=> true,
				'show_in_menu' 			=> true,
				'show_in_nav_menus' 	=> false,
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> true,
				'has_archive' 			=> false,
				'query_var' 			=> 'cuar_private_file',
				'can_export' 			=> false,
				'rewrite' 				=> false,
				'capabilities' 			=> array(
						'edit_post' 			=> 'cuar_editor',
						'edit_posts' 			=> 'cuar_editor',
						'edit_others_posts' 	=> 'cuar_editor',
						'publish_posts' 		=> 'cuar_editor',
						'read_post' 			=> 'cuar_editor',
						'read_private_posts' 	=> 'cuar_editor',
						'delete_post' 			=> 'cuar_editor'
				)
		);

		register_post_type( 'cuar_private_file', apply_filters( 'cuar_private_file_post_type_args', $args ) );

		$labels = array(
				'name' 						=> _x( 'File Categories', 'cuar_private_file_category', 'cuar' ),
				'singular_name' 			=> _x( 'File Category', 'cuar_private_file_category', 'cuar' ),
				'search_items' 				=> _x( 'Search File Categories', 'cuar_private_file_category', 'cuar' ),
				'popular_items' 			=> _x( 'Popular File Categories', 'cuar_private_file_category', 'cuar' ),
				'all_items' 				=> _x( 'All File Categories', 'cuar_private_file_category', 'cuar' ),
				'parent_item' 				=> _x( 'Parent File Category', 'cuar_private_file_category', 'cuar' ),
				'parent_item_colon' 		=> _x( 'Parent File Category:', 'cuar_private_file_category', 'cuar' ),
				'edit_item' 				=> _x( 'Edit File Category', 'cuar_private_file_category', 'cuar' ),
				'update_item' 				=> _x( 'Update File Category', 'cuar_private_file_category', 'cuar' ),
				'add_new_item' 				=> _x( 'Add New File Category', 'cuar_private_file_category', 'cuar' ),
				'new_item_name' 			=> _x( 'New File Category', 'cuar_private_file_category', 'cuar' ),
				'separate_items_with_commas'=> _x( 'Separate file categories with commas', 'cuar_private_file_category',
						'cuar' ),
				'add_or_remove_items' 		=> _x( 'Add or remove file categories', 'cuar_private_file_category', 'cuar' ),
				'choose_from_most_used' 	=> _x( 'Choose from the most used file categories', 'cuar_private_file_category',
						'cuar' ),
				'menu_name' 				=> _x( 'File Categories', 'cuar_private_file_category', 'cuar' ),
		);
	  
		$args = array(
				'labels' 			=> $labels,
				'public' 			=> true,
				'show_in_nav_menus' => false,
				'show_ui' 			=> true,
				'show_tagcloud' 	=> false,
				'hierarchical' 		=> true,
				'query_var' 		=> true,
				'rewrite' 			=> array(
						'slug' 				=> _x( 'private-file-category', 'URL slug', 'cuar' )
					),
		);
	  
		register_taxonomy( 'cuar_private_file_category', array( 'cuar_private_file' ), $args );
	}

	/**
	 * Add the rewrite rule for the private files.  
	 */
	function add_post_type_rewrites() {
		global $wp_rewrite;
		$wp_rewrite->add_rewrite_tag('%cuar_private_file%', '([^/]+)', 'cuar_private_file=');
		$wp_rewrite->add_rewrite_tag('%owner_name%', '([^/]+)', 'cuar_pf_owner_name=');
		$wp_rewrite->add_permastruct( 'cuar_private_file',
				_x( 'private-file', 'URL slug', 'cuar' ) . '/%owner_name%/%year%/%monthnum%/%day%/%cuar_private_file%',
				false);
	}

	/**
	 * Build the permalink for the private files
	 * 
	 * @param unknown $post_link
	 * @param unknown $post
	 * @param unknown $leavename
	 * @return unknown|mixed
	 */
	function built_post_type_permalink( $post_link, $post, $leavename ) {
		// Only change permalinks for private files
		if ( $post->post_type!='cuar_private_file') return $post_link;
	
		// Only change permalinks for published posts
		$draft_or_pending = isset( $post->post_status )
		&& in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );
		if( $draft_or_pending and !$leavename ) return $post_link;
	
		// Change the permalink
		global $wp_rewrite, $cuar_pf_addon;
	
		$permalink = $wp_rewrite->get_extra_permastruct( 'cuar_private_file' );
		$permalink = str_replace( "%cuar_private_file%", $post->post_name, $permalink );
	
		$owner_id = $cuar_pf_addon->get_file_owner_id( $post->ID );
		if ( $owner_id ) {
			$owner = get_userdata( $owner_id );
			$owner = sanitize_title_with_dashes( $owner->user_nicename );
		} else {
			$owner = _x( 'unknown', 'URL slug', 'cuar' );
		}
		$permalink = str_replace( '%owner_name%', $owner, $permalink );
	
		$post_date = strtotime( $post->post_date );
		$permalink = str_replace( "%year%", 	date( "Y", $post_date ), $permalink );
		$permalink = str_replace( "%monthnum%", date( "m", $post_date ), $permalink );
		$permalink = str_replace( "%day%", 		date( "d", $post_date ), $permalink );
	
		$permalink = home_url() . "/" . user_trailingslashit( $permalink );
		$permalink = str_replace( "//", "/", $permalink );
		$permalink = str_replace( ":/", "://", $permalink );
	
		return $permalink;
	}
	
	/** @var CUAR_Plugin */
	private $plugin;

	/** @var CUAR_PrivateFileAdminInterface */
	private $admin_interface;

	/** @var CUAR_PrivateFileFrontendInterface */
	private $frontend_interface;
}

// Make sure the addon is loaded
global $cuar_pf_addon;
$cuar_pf_addon = new CUAR_PrivateFileAddOn();

endif; // if (!class_exists('CUAR_PrivateFileAddOn')) 
