<?php
	include( $this->plugin->get_template_file_path(
  			CUAR_INCLUDES_DIR . '/core-addons/customer-page',
  			'customer-page-header.template.php',
  			'templates' ));	  
?>

<?php do_action( 'cuar_before_customer_area_content' ); ?>

<?php 
if ( isset( $top_level_action ) ) {
	do_action( 'cuar_customer_area_content_' . $top_level_action['slug'] ); 
} else if ( !empty( $current_action ) ) {
	do_action( 'cuar_customer_area_content_' . $current_action );
} else{
	do_action( 'cuar_customer_area_content' );
}
?>

<?php do_action( 'cuar_after_customer_area_content' ); ?>