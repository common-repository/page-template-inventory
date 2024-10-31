<?php
/*
Plugin Name: Page Template Inventory
Description: Take stock of the page templates that your theme has: what is being used and what is not.
Version: 0.2
License: GPL
Author: mitcho (Michael Yoshitaka Erlewine)
Author URI: http://mitcho.com/
Plugin URI: http://mitcho.com/code/
Donate link: http://tinyurl.com/donatetomitcho
*/

class Page_Template_Inventory {
	static $instance;

	public function __construct() {
		self::$instance = $this;
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	public function register_page() {
		add_submenu_page( 'themes.php', 'Page Template Inventory', 'Template Inventory', 'edit_themes', 'page_template_inventory', array( $this, 'page' ) );
	}
	
	public function page() {
		global $wpdb;
		
		$delete_success = false;
		$delete_file = null;
		if ( isset( $_REQUEST['delete'] ) ) {
			$delete_file = $_REQUEST['delete'];
			$delete_theme = $_REQUEST['theme'];
			check_admin_referer( 'delete_' . $delete_file );
			$delete_success = $this->delete( $delete_file, $delete_theme );
		}
		
		$templates = get_page_templates();
		$themes = get_themes();
		$theme = get_current_theme();
		
		echo "<div class='wrap'>";
		screen_icon();
		echo "<h2>Page Template Inventory: " . $themes[$theme]['Title'] . "</h2>";
		echo '<div>
  <div style="float:right" id="badges">
    <a target="_new" href="http://tinyurl.com/donatetomitcho"><img style="padding-left: 10px;" title="Donate to mitcho (Michael Yoshitaka Erlewine) for this plugin via PayPal" alt="Donate to mitcho (Michael Yoshitaka Erlewine) for this plugin via PayPal" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif"></a>
  </div>

	<small>by <a href="http://mitcho.com/">mitcho (Michael 芳貴 Erlewine)</a>.</small>

  </div>';
		
		if ( $delete_success ) {
			echo "<div id='message' class='updated'><p>The page template <code>{$delete_file}</code> has been deleted.</p></div>";
		}
		
		$template_dir = $themes[$theme]['Stylesheet Dir'];
		
		$active_templates = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_page_template'" );
		$active_templates = array_count_values( $active_templates );
		
		if ( file_exists( $template_dir . DIRECTORY_SEPARATOR . 'page.php' ) )
			$templates[ __( 'Default Template' ) ] = 'default';
		ksort( $templates );
		echo "<table class='widefat' style='width:auto'><thead><tr><th>Page template</th><th>Use count</th></tr></thead><tbody>";
		foreach ( $templates as $template => $template_file ) {
			if ( isset($active_templates[$template_file]) )
				$count = $active_templates[$template_file];
			else
				$count = 0;

			$template_edit_filename = _get_template_edit_filename(
				$template_dir . DIRECTORY_SEPARATOR . ( $template_file == 'default' ? 'page.php' : $template_file ),
				$template_dir);
			$edit_url = "theme-editor.php?file={$template_edit_filename}&amp;theme=" . urlencode($theme) . "&amp;dir=theme";
			echo "<tr><td>" . $template . " <span class='row-actions'><span class='edit'><a href='$edit_url'>edit</a></span>";
			if ( !$count ) {
				$delete_url = wp_nonce_url( "themes.php?page=page_template_inventory&delete={$template_file}&theme=" . urlencode($theme), "delete_{$template_file}" );
				echo " | <span class='delete'><a href='{$delete_url}'>delete</a></span>";
			}
			echo "</span></td><td>{$count}</td></tr>";
		}
		echo "</tbody></table>";
		
		echo "</div>";
	}
	
	private function delete($filename, $theme = false) {
		global $wp_filesystem;
		
		// ensure that the filesystem is connected...
		if ( !is_object($wp_filesystem) )
			WP_Filesystem();

		if ( !is_object($wp_filesystem) )
			wp_die( __('Could not access filesystem.') );

		if ( !$theme )
			$theme = get_current_theme();

		$themes = get_themes();
		$template_dir = $themes[$theme]['Stylesheet Dir'];

		$filename = $template_dir . DIRECTORY_SEPARATOR . $filename;
		if ( !file_exists( $filename ) )
			wp_die( 'The file did not exist.' );

		$wp_filesystem->delete( $filename, false );

		if ( file_exists( $filename ) )
			wp_die( 'The file could not be deleted.' );
			
		return true;
	}
}
new Page_Template_Inventory;
