<?php
	include_once(ABSPATH . WPINC . '/feed.php');
	
	$feed = fetch_feed('http://www.marvinlabs.com/downloads/category/customer-area/feed/rss/');
?>

<h1><?php _e( 'Enhance your customer area with our add-ons', 'cuar' ); ?></h1>

<?php  	if ( is_wp_error( $feed ) ) : ?>

<p><?php 
	printf( __('There has been an error while getting the list of add-ons, please <a href="%s">visit our shop directly</a>.', 'cuar' ), 
			'http://www.marvinlabs.com/downloads/category/customer-area/' ); ?></p>
<p class="description"><?php echo $feed->get_error_message(); ?></p>
			
<?php 	else : ?>
	
<div class="cuar-add-ons">
<?php 
	$force_line_break = false; 
	foreach ( $feed->get_items() as $item ) : ?>
	<div class="cuar-addon">
		<?php if ( $enclosure = $item->get_enclosure() ) : ?>
		<a href="<?php echo $item->get_permalink(); ?>">
			<img src="<?php echo $enclosure->get_link(); ?>" />
		</a>
		<?php endif; ?>
		<div class="meta">
			<h2><a href="<?php echo $item->get_permalink(); ?>"><?php echo str_replace( 'Customer Area – ', '', $item->get_title() ); ?></a></h2>
			<?php echo $item->get_description(); ?>
		</div>
	</div>	
	
<?php 	if ($force_line_break) echo '<p class="forcelinebreak">&nbsp;</p>'; ?>
	
<?php
		$force_line_break = !$force_line_break;
	endforeach; ?>
</div>

<?php endif; ?>