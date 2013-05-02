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

<div class="wrap">
	<?php screen_icon(); ?>
	
	<h2 class="nav-tab-wrapper">
<?php foreach ( $this->tabs as $tab_id => $tab_label) : ?>
			<?php  printf( '<a href="?page=%s&cuar_tab=%s" class="nav-tab %s">%s</a>',
						CUAR_Settings::$OPTIONS_PAGE_SLUG,
						$tab_id,
						( $this->current_tab == $tab_id ? 'nav-tab-active' : '' ),
						esc_html( $tab_label ) ); ?>
<?php endforeach; ?>
	</h2>
	
<?php do_action( 'cuar_before_settings' ); ?>
<?php do_action( 'cuar_before_settings_' . $this->current_tab ); ?>
	
	<form method="post" action="options.php"> 	
		<input type="hidden" id="cuar_tab" name="cuar_tab" value="<?php echo $this->current_tab; ?>" />
	
	<?php 
		settings_fields( CUAR_Settings::$OPTIONS_GROUP . '_' . $this->current_tab ); 
		do_settings_sections( CUAR_Settings::$OPTIONS_PAGE_SLUG ); 
	?>
	
	<?php submit_button(); ?>
	</form>
	
<?php do_action( 'cuar_after_settings_' . $this->current_tab ); ?>
<?php do_action( 'cuar_after_settings' ); ?>
</div>