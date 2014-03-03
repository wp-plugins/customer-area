<?php
/*  Copyright 2013 MarvinLabs (contact@marvinlabs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
?>

<table class="widefat cuar-status-table">
	<thead>
		<tr>
			<th><?php _e( 'Page', 'cuar' ); ?></th>
			<th><?php _e( 'Slug', 'cuar' ); ?></th>
			<th><?php _e( 'Order', 'cuar' ); ?></th>
			<th><?php _e( 'ID', 'cuar' ); ?></th>
			<th><?php _e( 'Sidebar', 'cuar' ); ?></th>
			<th><?php _e( 'Type', 'cuar' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
	$cp_addon = $this->plugin->get_addon('customer-pages');
	$customer_area_pages = $cp_addon->get_customer_area_pages();
	
	foreach ( $customer_area_pages as $slug => $page ) :
		$page_id = $page->get_page_id();
		$tr_class = $page_id<=0 ? 'cuar-needs-attention' : '';
?>
		<tr class="<?php echo $tr_class; ?>">
			<td><?php echo $page->get_title(); ?></td>
			<td><?php echo $page->get_slug(); ?></td>
			<td><?php echo $page->get_priority(); ?></td>
			<td><?php echo $page_id>0 ? $page_id : '?'; ?></td>
			<td><?php echo $page->has_page_sidebar() ? 'Yes' : ''; ?></td>
			<td><?php echo $page->get_type(); ?></td>
			<td>
<?php 			if ( $page_id>0 ) {
					printf( '<a href="%1$s" class="button">%2$s &raquo;</a>', admin_url('post.php?action=edit&post=' . $page_id), __('Edit', 'cuar') );	
					echo ' ';
					printf( '<a href="%1$s" class="button">%2$s &raquo;</a>', get_permalink( $page_id ), __('View', 'cuar') );			
				}
?>
			</td>
		</tr>	
<?php
	endforeach;
?>
	</tbody>
</table>