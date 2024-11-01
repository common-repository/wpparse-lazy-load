<?php
/*
Plugin Name: WPPARSE Lazy Load Plugin
Plugin URI:  http://www.wpparse.com/
Description: The best lazy load plugin by WPPARSE to load images fast.
Version:     0.1
Author:      WPPARSE
Author URI:  http://www.wpparse.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wpparse-lazy-load
*/
defined( 'ABSPATH' ) or die( 'Where are you going?' );
function wpparse_lazy_load_scripts(){
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'wpparse_lazy_load_js',  plugin_dir_url(__FILE__).'js/jquery.lazyload.min.js', array('jquery'), '1.0', true );
}
add_action('wp_enqueue_scripts','wpparse_lazy_load_scripts');
//Include HTML DOM
require_once('assets/simple_html_dom.php');
// Settings Page
add_action( 'admin_menu', 'wpll_add_admin_menu' );
add_action( 'admin_init', 'wpll_settings_init' );
function wpll_add_admin_menu(  ) { 
	add_menu_page( 'Lazy Load', 'Lazy Load', 'manage_options', 'wpparse_lazy_load', 'wpll_options_page' );
}
function wpll_settings_init(  ) { 
	register_setting( 'wpll_pluginPage', 'wpll_settings' );
	add_settings_section(
		'wpll_pluginPage_section', 
		__( 'General Settings', 'wpparse-lazy-load' ), 
		'wpll_settings_section_callback', 
		'wpll_pluginPage'
	);
	add_settings_field( 
		'LazyLoadOnPosts', 
		__( 'Lazy Load on Posts / Pages', 'wpparse-lazy-load' ), 
		'wpll_checkbox_LazyLoadOnPosts_render', 
		'wpll_pluginPage', 
		'wpll_pluginPage_section' 
	);
	add_settings_field( 
		'LazyLoadOnPostThumbs', 
		__( 'Lazy Load on Featured Images', 'wpparse-lazy-load' ), 
		'wpll_checkbox_LazyLoadOnPostThumbs_render', 
		'wpll_pluginPage', 
		'wpll_pluginPage_section' 
	);
}
function wpll_checkbox_LazyLoadOnPosts_render(  ) { 
	$options = get_option( 'wpll_settings' );
	?>
	<input type='checkbox' name='wpll_settings[LazyLoadOnPosts]' <?php checked( $options['LazyLoadOnPosts'], 1 ); ?> value='1'> Check to enable lazy load on post page images
	<?php
}
function wpll_checkbox_LazyLoadOnPostThumbs_render(  ) { 
	$options = get_option( 'wpll_settings' );
	?>
	<input type='checkbox' name='wpll_settings[LazyLoadOnPostThumbs]' <?php checked( $options['LazyLoadOnPostThumbs'], 1 ); ?> value='1'> Check to enable lazy load on featured images
	<?php
}
function wpll_settings_section_callback(  ) { 
	echo __( 'Load images fast and save bandwidth.', 'wpparse-lazy-load' );
}
function wpll_options_page(  ) { 
	?>
	<form action='options.php' method='post'>
		<h1>WPPARSE Lazy Load</h1>
		<?php
		settings_fields( 'wpll_pluginPage' );
		do_settings_sections( 'wpll_pluginPage' );
		submit_button();
		?>
	</form>
	<?php
}
//Get options
$wpll_options = get_option( 'wpll_settings' );
//Load main lazy load script if 'LazyLoadOnPosts' or 'LazyLoadOnPostThumbs' checkboxes are checked
if(($wpll_options['LazyLoadOnPosts'] == '1') || ($wpll_options['LazyLoadOnPostThumbs'] == '1')) {
	add_action('wp_footer','wpll_activation');
} 
	
//Apply lazy load to images in content
if($wpll_options['LazyLoadOnPosts'] == '1') {
	add_filter( 'the_content', 'wpll_filter_content' );
}
	
//Apply lazy load to featured images or post thumbnails
if($wpll_options['LazyLoadOnPostThumbs'] == '1') {
	add_filter( 'post_thumbnail_html', 'wpll_filter_content');
}
// Calling jQuery script using PHP function
function wpll_activation() {
	?>
    <script type="text/javascript">
		jQuery(function($) {
			$("img.lazy").lazyload({
				effect : "fadeIn"
			});
		});  
    </script>
        <?php
}
//Filter the content
function wpll_filter_content($content) {
	
	if (strlen($content)) {
		$wpll_newcontent = $content;
		// Replace 'src' with 'data-original' on images
		$wpll_newcontent = replace_img_src_tag($wpll_newcontent);
		
		return $wpll_newcontent;
	} else {
		return $content;
	}
}
//Add 'data-originial' attribute and 'lazy' class to image tags
function replace_img_src_tag($content) {
	$html = str_get_html($content, '', '', '', false);
	$placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==';
	foreach ($html->find('img') as $element) {
        // Element class, prepend lazy to it
        $element->class = 'lazy ' . $element->class;
        // 'data-original' attribute, note the bracket syntax cause of the hyphen
        $element->{'data-original'} = $element->src;
        // Placeholder image to the src
        $element->src = $placeholder;
    }
    return $html;
	
}
// Redirect to settings page after activation
register_activation_hook(__FILE__, 'wpparse_lazy_load_plugin_activate');
add_action('admin_init', 'wpparse_lazy_load_plugin_redirect');
function wpparse_lazy_load_plugin_activate() {
    add_option('wpparse_lazy_load_plugin_do_activation_redirect', true);
}
function wpparse_lazy_load_plugin_redirect() {
    if (get_option('wpparse_lazy_load_plugin_do_activation_redirect', false)) {
        delete_option('wpparse_lazy_load_plugin_do_activation_redirect');
        wp_redirect('admin.php?page=wpparse_lazy_load');
    }
}
?>