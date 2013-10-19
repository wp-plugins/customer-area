<div class="cuar-private-file-container">
<h4><?php echo apply_filters( 'cuar_private_files_after_content_title', __( 'Associated file', 'cuar' ) ); ?></h4>

<table class="cuar-private-file-list">
  <tbody>
		<tr class="cuar-private-file">
			<td class="title">
				<?php CUAR_PrivateFileThemeUtils::the_file_name( get_the_ID() ); ?>
			</td>
			<td class="links download-link">
				<a href="<?php CUAR_PrivateFileThemeUtils::the_file_link( get_the_ID(), 'download' ); ?>" title="<?php esc_attr_e( 'Download', 'cuar' ); ?>">
					<?php _e( 'Download', 'cuar' ); ?></a>
			</td>
		</tr>
	</tbody>
</table>
</div>