<?php 
	global $current_user, $cuar_plugin;
	$cp_addon = $cuar_plugin->get_addon('customer-page');

	$current_action = isset( $_GET['action'] ) ? $_GET['action'] : '';
	$title = '';
	$actions = apply_filters( 'cuar_customer_page_actions', array() );
	$base_url = trailingslashit( $cp_addon->get_customer_page_url() );
	
	if (!empty($actions)) {
		foreach ($actions as $action) {
			if ( (isset( $action["slug"] ) && $current_action==$action['slug']) ) {
				$title = $action["label"];
				$top_level_action = $action;
				break;
			} else if ( isset( $action['children'] ) && array_key_exists( $current_action, $action['children'] ) ) {
				$href = isset( $action["url"] ) ? $action["url"] : $base_url . '?action=' . $action["slug"];
				$title = sprintf( '<a href="%1$s">%2$s</a> &raquo; %3$s',  
					esc_attr( $href ), $action["label"], $action['children'][ $current_action ]['label'] );
				break;
			}
		}
	} 
	
	if (empty($title)) {
		$title = sprintf( __('Hello %s,', 'cuar'), $current_user->display_name );
	} 
?>
<?php do_action( 'cuar_before_customer_page_header' ); ?>

<div class="cuar-header">

<?php do_action( 'cuar_before_customer_page_actions' ); ?>

<?php
if (!empty($actions)) :
	$is_last = count($actions);
	$separator = apply_filters( 'cuar_customer_page_actions_separator', '&bull;' );
?>	
	<nav class="cuar-menu">
		<ul class="cuar-actions-container"><?php 		
			foreach ($actions as $action) : 
				$li_class = '';
			
				if ( isset( $action['slug'] ) && $current_action==$action['slug'] ) {
					$li_class .= 'current';
					$top_level_action = $action;
				} else if ( isset( $action['children'] ) && array_key_exists( $current_action, $action['children'] ) ) {
					$li_class .= 'current-parent';
				} else if ( isset( $action['slug'] ) && empty( $current_action ) && $action['slug']=='show-dashboard' ) {
					$li_class .= 'current';
				}
				
				$href = isset( $action["url"] ) ? $action["url"] : $base_url . '?action=' . $action["slug"];
				$label = esc_html( $action["label"] ); 
				$hint = esc_attr( $action["hint"] );
			?><li class="<?php echo $li_class; ?>"><a href="<?php echo esc_attr( $href ); ?>" title="<?php echo $hint; ?>">
					<span><?php echo $label; ?></span>
				</a><?php $is_last--; if ( $is_last!=0 ) { echo $separator; } ?></li><?php 		
			endforeach; 
		?></ul>
	</nav>
<?php
endif;
?>

<?php do_action( 'cuar_before_customer_page_title' ); ?>

	<h2 class="cuar_page_title"><?php echo $title; ?></h2>
	
<?php do_action( 'cuar_after_customer_page_title' ); ?>
	
<?php if ( isset( $top_level_action ) && isset( $top_level_action['children'] ) && !empty( $top_level_action['children'] ) ) : ?>
	<p class="cuar-action-children-container">
<?php 	foreach ( $top_level_action['children'] as $action ) :
			$li_class = '';
			if ( isset( $top_level_action['slug'] ) && $top_level_action['slug']==$action['slug'] ) $li_class .= 'current';
			
			$href = isset( $action["url"] ) ? $action["url"] : $base_url . '?action=' . $action["slug"];
			$label = esc_html( $action["label"] );
			$hint = esc_attr( $action["hint"] );
?>
		<a href="<?php echo esc_attr( $href ); ?>" title="<?php echo $hint; ?>" class="cuar-action-child cuar_small_button <?php echo $li_class; ?>">
			<?php echo $label; ?> &raquo;
		</a>
<?php 	endforeach; ?>
	</p>
<?php endif; ?>

<?php do_action( 'cuar_after_customer_page_action_children' ); ?>
	
</div>

<?php do_action( 'cuar_after_customer_page_header' ); ?>