<tr class="cuar-private-page cuar-item">
	<td class="meta">
		<span class="date"><?php the_modified_time(get_option('date_format')); ?></span>
		<br/>
		<span class="sender"><?php echo CUAR_WordPressHelper::ellipsis( sprintf( __('From: %s', 'cuar' ), get_the_author_meta( 'display_name' ) ), 27 ); ?></span>
	</td>
	<td class="content">
		<span class="title"><a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Added on %s', 'cuar' ), get_the_date() ) ); ?>">
			<?php the_title(); ?></a></span>
		<br/>
		<span class="recipient"><?php echo CUAR_WordPressHelper::ellipsis( sprintf( __('To: %s', 'cuar' ), $po_addon->get_post_owner_displayname( get_the_ID() ) ), 53 ); ?></span>
	</td>
</tr>