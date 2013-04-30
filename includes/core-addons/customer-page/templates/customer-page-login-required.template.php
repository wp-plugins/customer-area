<h2><?php _e('Hello', 'cuar'); ?></h2>

<p>
<?php _e( 'You must login to access your own customer area.', 'cuar' ); ?>
<?php _e( 'If you do not have an account yet, please register or contact us so that we can create it.', 'cuar' ); ?>
</p>

<ul>
	<li><a href="<?php echo wp_login_url( get_permalink() ); ?>"><?php _e( 'Login', 'cuar' ); ?></a></li>
	<li><a href="<?php echo wp_registration_url(); ?>"><?php _e( 'Register', 'cuar' ); ?></a></li>
</ul>
