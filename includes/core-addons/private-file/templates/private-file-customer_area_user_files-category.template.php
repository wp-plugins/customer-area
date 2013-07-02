<div class="cuar-private-file-container">

<h3><?php echo apply_filters( 'cuar_private_files_section_title', __( 'Your files', 'cuar' ) ); ?></h3>

<div class="accordion-container">	

<?php 
function cuar_list_category_files( $category, $item_template, $current_user_id, $breadcrumb_sep, $breadcrumb = "", 
			$parent = null ) {
	if ( !$category ) return;

	// Get posts in the category	
	global $cuar_plugin, $cuar_po_addon;
	$hide_empty_categories = $cuar_plugin->get_option( CUAR_PrivateFileAdminInterface::$OPTION_HIDE_EMPTY_CATEGORIES );
	$args = array(
			'post_type' 		=> 'cuar_private_file',
			'posts_per_page' 	=> -1,
			'orderby' 			=> 'date',
			'order' 			=> 'DESC',
			'tax_query'			=> array( array(
					'taxonomy' 			=> 'cuar_private_file_category',
					'include_children'	=> false,
					'field'				=> 'slug',
					'terms'				=> $category->slug,
					'operator'			=> 'IN'
			) ),
			'meta_query' 		=> $cuar_po_addon->get_meta_query_post_owned_by( $current_user_id )
		);

	$heading = '';
	if ( !empty( $breadcrumb ) ) $heading .= $breadcrumb . $breadcrumb_sep;
	$heading .= $category->name;
	
	$files_query = new WP_Query( apply_filters( 'cuar_user_files_query_parameters', $args ) );
	
	// Print heading
	if ( $files_query->have_posts() || !$hide_empty_categories ) {
?>
	<h4 class="accordion-section-title" title="<?php _e( 'Clic to show the files in this category', 'cuar' );?>"><?php echo $heading; ?></h4>
	<div class="accordion-section-content">
		<table class="cuar-private-file-list"><tbody>
<?php
		if ( $files_query->have_posts() ) {		
			// Print posts
			while ( $files_query->have_posts() ) { 
				$files_query->the_post(); 
				global $post; 
?>
			<tr class="cuar-private-file"><?php	include( $item_template ); ?></tr>
<?php 		
			}
		} else {
?>
			<tr class="cuar-private-file"><td><?php _e( 'No files in this category', 'cuar' ); ?></td></tr>
<?php 		
		}
?>
		</tbody></table>
	</div>
<?php	
	}
	
	// Output children
	$children = get_terms( 'cuar_private_file_category', array(
			'parent'		=> $category->term_id,
			'hide_empty'	=> 0	
		) );	

	if ( empty( $children ) ) return;	 
	
	foreach ( $children as $child ) {
		cuar_list_category_files( $child, $item_template, $current_user_id, $breadcrumb_sep, $heading, $category );
	}
}

$current_user_id = get_current_user_id();
$file_categories = get_terms( 'cuar_private_file_category', array(
		'parent'		=> 0,
		'hide_empty'	=> 0
	) );

$item_template = $this->plugin->get_template_file_path(
		CUAR_INCLUDES_DIR . '/core-addons/private-file',
		"private-file-customer_area_user_file_item-{$display_mode}.template.php",
		'templates',
		"private-file-customer_area_user_file_item.template.php" );


if ( !empty( $file_categories ) ) : 
	foreach ( $file_categories as $category ) : 
		cuar_list_category_files($category, $item_template, $current_user_id, ' &raquo; ' );
	endforeach; 
else : 	
	include( $this->plugin->get_template_file_path(
			CUAR_INCLUDES_DIR . '/core-addons/private-file',
			'private-file-customer_area_no_user_files.template.php',
			'templates' ));	
endif; 

?>
	
<script type="text/javascript">
<!--
jQuery(document).ready(function($) {
	$( "div.accordion-container" ).accordion({
			heightStyle: "content",
			header: "h4",
			animate: 250
		});
});
//-->
</script>

<?php wp_reset_postdata(); ?>

</div>
</div>