<div class="cuar-private-page-container">
<h4><?php echo apply_filters( 'cuar_private_pages_after_content_title', __( 'More information about this page', 'cuar' ) ); ?></h4>

<p class="private-content-information"><?php 
	global $cuar_plugin;
	$po_addon = $cuar_plugin->get_addon( "post-owner" );
	
	$date = sprintf("<em>%s</em>", get_the_date() );
	$author = sprintf("<em>%s</em>", get_the_author_meta( 'display_name' ) );
	$recipients = sprintf("<em>%s</em>", $po_addon->get_post_owner_displayname( get_the_ID() ) );

	printf( __( 'Page created on %1$s by %2$s for %3$s', 'cuar' ), $date, $author, $recipients  ); ?></p>

</div>