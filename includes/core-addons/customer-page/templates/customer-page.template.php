<?php 
	global $current_user; 

	$current_action = isset( $_GET['action'] ) ? $_GET['action'] : '';
	$actions = apply_filters( 'cuar_customer_page_actions', array() );
	$title = '';
	
	if (!empty($actions)) {
		foreach ($actions as $action) {
			if ( (isset( $action["slug"] ) && $current_action==$action['slug']) ) {
				$title = $action["label"];
			}
		}
	} 
	
	if (empty($title)) {
		$title = sprintf( __('Hello %s,', 'cuar'), $current_user->display_name );
	} 
?>

<div class="cuar-header">
	<h2 class="cuar_page_title"><?php echo $title; ?></h2>
<?php
if (!empty($actions)) :
?>	
	<nav class="cuar-menu">
		<span class="cuar-menu-button">&Xi;</span>
		<ul class="cuar-actions-container" style="display: none;">
<?php 		foreach ($actions as $action) : 
				$li_class = (isset( $action["slug"] ) && $current_action==$action['slug']) ? ' class="current" ' : ''; 
				$href = isset( $action["url"] ) 
						? $action["url"] 
						: trailingslashit( get_permalink() ) . '?action=' . $action["slug"];
?>
			<li <?php echo $li_class; ?>><a href="<?php echo esc_attr( $href ); ?>" 
				   title="<?php echo esc_attr( $action["hint"] ); ?>">
					<span><?php echo esc_html( $action["label"] ); ?></span>
				</a></li>
<?php 		endforeach; ?>
		</ul>
	</nav>
<?php
endif;
?>
</div>


<?php 
if ( !empty( $current_action ) ) {
	do_action( 'cuar_customer_area_content_' . $current_action ); 
} else {
	do_action( 'cuar_customer_area_content' );
}
?>

<script type="text/javascript">
<!--
	jQuery( document ).ready( function($) {
		$( '.cuar-menu .cuar-menu-button' ).click(function() {
			$( '.cuar-menu .cuar-actions-container' ).slideToggle();
		});
	});
//-->
</script>