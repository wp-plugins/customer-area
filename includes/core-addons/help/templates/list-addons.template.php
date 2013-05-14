<?php
	include_once(ABSPATH . WPINC . '/feed.php');
	$feed = fetch_feed('http://www.marvinlabs.com/downloads/category/customer-area/feed/rss/');
?>

<h1><?php _e( 'Enhance your customer area with our add-ons', 'cuar' ); ?></h1>

<div class="cuar-add-ons">

<?php foreach ( $feed->get_items() as $item ) : ?>

	<div class="cuar-addon">
		<?php if ( $enclosure = $item->get_enclosure() ) : ?>
		<a href="<?php echo $item->get_permalink(); ?>">
			<img src="<?php echo $enclosure->get_link(); ?>" />
		</a>
		<?php endif; ?>
		<h2><a href="<?php echo $item->get_permalink(); ?>"><?php echo str_replace( 'Customer Area – ', '', $item->get_title() ); ?></a></h2>
		<p><?php echo $item->get_description(); ?></p>
	</div>
	
<?php endforeach; ?>
</div>