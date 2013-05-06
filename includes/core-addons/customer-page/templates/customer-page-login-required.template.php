<ul>
	<li><a href="<?php echo wp_login_url( get_permalink() ); ?>"><?php _e( 'Login', 'cuar' ); ?></a></li>
<?php if ( get_option( 'users_can_register' ) ) : ?>
	<li><a href="<?php echo wp_registration_url(); ?>"><?php _e( 'Register', 'cuar' ); ?></a></li>
<?php endif; ?>
</ul>
