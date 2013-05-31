<h1><?php _e( 'Troubleshooting information', 'cuar' ); ?></h1>

<p>&nbsp;</p>
<h2><?php _e( 'Installed add-ons', 'cuar' ); ?></h2>

<table class="widefat">
	<thead>
		<tr>
			<th><?php _e( 'Name', 'cuar' ); ?></th>
			<th><?php _e( 'Required customer area version', 'cuar' ); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
	global $cuar_plugin;
	$addons =  $cuar_plugin->get_registered_addons();
	foreach ( $addons as $id => $addon ) : ?>
		<tr>
			<td><?php echo $addon->addon_name; ?></td>
			<td><?php echo $addon->min_cuar_version; ?></td>
		</tr>
<?php 
	endforeach; ?>
	</tbody>
</table>
<p>&nbsp;</p>

<h2><?php _e( 'Plugin options', 'cuar' ); ?></h2>
<pre><?php echo esc_html( print_r($cuar_settings->get_options(), true )); ?></pre>
<p>&nbsp;</p>