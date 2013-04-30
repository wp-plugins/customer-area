<?php global $current_user; ?>
<h2><?php printf( __('Hello %s,', 'cuar'), $current_user->display_name ); ?></h2>

<?php do_action( 'cuar_customer_area_content' ); ?>