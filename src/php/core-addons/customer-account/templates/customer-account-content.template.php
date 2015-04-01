<?php /** Template version: 1.1.0

-= 1.1.0 =-
- Added updated message
 
-= 1.0.0 =-
- Initial version

*/
?>
		
<h3><?php 
	$current_user = $this->get_current_user();	
	printf( __('Hello %s,', 'cuar'), $current_user->display_name );
?></h3>

<?php 
if ( isset( $_GET['updated'] ) && $_GET['updated']==1 ) {
	printf( '<p class="alert alert-info">%s</p>', __( 'Your profile has been updated', 'cuar' ) );
}
?>

<p><?php _e('Please find below your account details', 'cuar' ); ?></p>

<?php $this->print_account_fields(); ?>