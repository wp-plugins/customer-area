<div class="cuar-private-file-container">

<h3><?php _e( 'Your files', 'cuar' ); ?></h3>

<?php 
$current_user_id = get_current_user_id();

// Get user files
$args = array(
		'post_type' 		=> 'cuar_private_file',
		'posts_per_page' 	=> -1,
		'orderby' 			=> 'date',
		'order' 			=> 'DESC',
		'meta_query' 		=> array(
				array(
						'key' 		=> 'cuar_owner',
						'value' 	=> $current_user_id,
						'compare' 	=> '='
				)
		)
);

$files_query = new WP_Query( apply_filters( 'cuar_user_files_query_parameters', $args ) );
?>

<?php if ( $files_query->have_posts() ) : ?>

<ul class="filter-options">
    <li data-group="all" class="active">All</li>
    <li data-group="1">Wallpapers</li>
    <li data-group="2">Graphic Design</li>
    <li data-group="3">Photography</li>
</ul>

<table class="cuar-private-file-list">
  <tbody>
<?php 	while ( $files_query->have_posts() ) : $files_query->the_post(); global $post; ?>

<?php 	$i = rand(0, 4); ?>

		<tr class="cuar-private-file" data-groups='["<?php echo $i; ?>"]'>
		<td><?php echo $i; ?></td>
<?php 		include( $this->plugin->get_template_file_path(
					CUAR_INCLUDES_DIR . '/core-addons/private-file',
					'private-file-customer_area_user_file_item.template.php',
					'templates' ));	?>
		</tr>
		
<?php 	endwhile; ?>

<?php 	while ( $files_query->have_posts() ) : $files_query->the_post(); global $post; ?>

<?php 	$i = rand(0, 4); ?>

		<tr class="cuar-private-file" data-groups='["<?php echo $i; ?>"]'>
		<td><?php echo $i; ?></td>
<?php 		include( $this->plugin->get_template_file_path(
					CUAR_INCLUDES_DIR . '/core-addons/private-file',
					'private-file-customer_area_user_file_item.template.php',
					'templates' ));	?>
		</tr>
		
<?php 	endwhile; ?>

<?php 	while ( $files_query->have_posts() ) : $files_query->the_post(); global $post; ?>

<?php 	$i = rand(0, 4); ?>

		<tr class="cuar-private-file" data-groups='["<?php echo $i; ?>"]'>
		<td><?php echo $i; ?></td>
<?php 		include( $this->plugin->get_template_file_path(
					CUAR_INCLUDES_DIR . '/core-addons/private-file',
					'private-file-customer_area_user_file_item.template.php',
					'templates' ));	?>
		</tr>
		
<?php 	endwhile; ?>

<?php 	while ( $files_query->have_posts() ) : $files_query->the_post(); global $post; ?>

<?php 	$i = rand(0, 4); ?>

		<tr class="cuar-private-file" data-groups='["<?php echo $i; ?>"]'>
		<td><?php echo $i; ?></td>
<?php 		include( $this->plugin->get_template_file_path(
					CUAR_INCLUDES_DIR . '/core-addons/private-file',
					'private-file-customer_area_user_file_item.template.php',
					'templates' ));	?>
		</tr>
		
<?php 	endwhile; ?>

	</tbody>
</table>

<script type="text/javascript">
<!--
jQuery(document).ready(function($) {
    // Set up button clicks
    $('.filter-options li').on('click', function() {
        var $this = $(this),
            $grid = $('table.cuar-private-file-list tbody');

        // Hide current label, show current label in title
        $('.filter-options .active').removeClass('active');
        $this.addClass('active');

        // Filter elements
        $grid.shuffle($this.data('group'));
    });
	
	var options = {
	    group : 'all', // Which category to show
	    speed : 800, // Speed of the transition (in milliseconds). 800 = .8 seconds
	    easing : 'ease-out' // css easing function to use
	};
	$('table.cuar-private-file-list tbody').shuffle(options);
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