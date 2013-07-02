<div class="cuar-private-file-container">

<h3><?php echo apply_filters( 'cuar_private_files_section_title', __( 'Your files', 'cuar' ) ); ?></h3>

<?php 
global $cuar_po_addon;
$current_user_id = get_current_user_id();

// Get user files
$args = array(
		'post_type' 		=> 'cuar_private_file',
		'posts_per_page' 	=> -1,
		'orderby' 			=> 'date',
		'order' 			=> 'DESC',
		'meta_query' 		=> $cuar_po_addon->get_meta_query_post_owned_by( $current_user_id )
);

$files_query = new WP_Query( apply_filters( 'cuar_user_files_query_parameters', $args ) );
?>

<?php if ( $files_query->have_posts() ) : ?>

<?php 
	$item_template = $this->plugin->get_template_file_path(
			CUAR_INCLUDES_DIR . '/core-addons/private-file',
			"private-file-customer_area_user_file_item-{$display_mode}.template.php",
			'templates',
			"private-file-customer_area_user_file_item.template.php" ); 

	$current_year = '';
?>

<div class="accordion-container">		
	
<?php 	while ( $files_query->have_posts() ) : $files_query->the_post(); global $post; ?>

<?php 		if ( empty( $current_year ) ) : 
				$current_year = get_the_date( 'Y' ); ?>
				
<h4 title="<?php printf( __( 'Clic to show the files published in %s', 'cuar' ), $current_year );?>">
	<?php echo $current_year; ?>
</h4>
<div class="accordion-section-content"><table class="cuar-private-file-list"><tbody>
	
<?php 		elseif ( $current_year!=get_the_date( 'Y' ) ) : 
				$current_year = get_the_date( 'Y' ); ?>
				
</tbody></table></div>
<h4 title="<?php printf( __( 'Clic to show the files published in %s', 'cuar' ), $current_year );?>">
	<?php echo $current_year; ?>
</h4>
<div class="accordion-section-content"><table class="cuar-private-file-list"><tbody>

<?php 		endif; ?>

	<tr class="cuar-private-file"><?php	include( $item_template ); ?></tr>
		
<?php 	endwhile; ?>

</tbody></table></div>

</div>

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

<?php else : ?>
<?php 	include( $this->plugin->get_template_file_path(
					CUAR_INCLUDES_DIR . '/core-addons/private-file',
					'private-file-customer_area_no_user_files.template.php',
					'templates' ));	?>
<?php endif; ?>

<?php wp_reset_postdata(); ?>

</div>