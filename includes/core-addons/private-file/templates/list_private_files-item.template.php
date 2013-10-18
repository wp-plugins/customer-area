<tr class="cuar-private-file">
	<td class="title">
		<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Added on %s', 'cuar' ), get_the_date() ) ); ?>">
			<?php the_title(); ?></a>
	</td>
	<td class="view-link">
		<a href="<?php CUAR_PrivateFileThemeUtils::the_file_link( get_the_ID(), 'view' ); ?>" title="<?php esc_attr_e( 'View', 'cuar' ); ?>">	
			<?php _e( 'View', 'cuar' ); ?></a>
	</td> 
	<td class="download-link">
		<a href="<?php CUAR_PrivateFileThemeUtils::the_file_link( get_the_ID(), 'download' ); ?>" title="<?php esc_attr_e( 'Download', 'cuar' ); ?>">
			<?php _e( 'Download', 'cuar' ); ?></a>
	</td>
</tr>