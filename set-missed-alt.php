<?php
/**
 * Plugin Name: Set Missed Alt
 * Plugin URI: 
 * Description: Simple, fast and practical Plugin that allows you easily set/change alt tags for images.
 * Version: 1.0.0
 * Author: Prizzrak
 * Author URI: https://github.com/Prizzrakk
 * Donate URI: https://github.com/Prizzrakk
 * Text Domain: set-missed-alt
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Requires PHP: 7.2
 * WC tested: 6.2.2
 *
 * Copyright 2023 Prizzrak
 *
 * This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'SET_MA', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SET_MA_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SET_MA_VER', '1.0' );
define( 'SET_MA_BASENAME', plugin_basename( __FILE__ ) );

if ( ! class_exists( 'Set_Missed_Alt' ) ) {

class Set_Missed_Alt {

    /* Hold the class instance. */
    private static $instance = null;

    /* Slug */
    private $slug = 'set-missed-alt';

	/* Start up */
    public function __construct() {
		$this->init_();
	}

    public static function get_instance() {
        if ( self::$instance == null ) self::$instance = new Set_Missed_Alt();
        return self::$instance;
    }

    public function init_() {
		// Add Support link at plugin description in Plugins page
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		// Set up localisation
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
		//Load admin css and js
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'adm_init' ) );
    }

	/* Loads the plugin text domain for translation */
	public function load_plugin_textdomain() {
		load_textdomain( $this->slug, SET_MA_DIR . 'languages/set-missed-alt-' . determine_locale() . '.mo' );
		load_plugin_textdomain( $this->slug, false, dirname( SET_MA_BASENAME ) . '/languages/' );
	}

    /* Admin init actions, ajax calls */
    public function adm_init() {
		add_action( 'add_attachment', array( $this, 'save_attachment_metadata' ) ); //Save file size as meta-data after Add photo
		add_action( 'edit_attachment', array( $this, 'save_attachment_metadata' ) ); //Save file size as meta-data after Edit photo
		add_action( 'load-upload', array( $this, 'save_attachment_metadata' ) ); //Save file size as meta-data on Load-Upload photo

		add_filter( 'manage_media_columns', array( $this, 'add_media_column' ) ); //Add columns in admin media page
		add_filter( 'manage_media_custom_column', array( $this, 'media_column_add_row' ), 25, 2 ); //Echo data in columns
		add_filter( 'manage_upload_sortable_columns', array( $this, 'media_sortable_column' ) ); // Make columns sortable
		add_action( 'pre_get_posts', array( $this, 'media_orderby_columns' ) ); // Query for sorting columns

		add_action( 'wp_ajax_ajax_check_missed_alt', array( $this, 'ajax_check_missed_alt' ) ); //Check button pressed
		add_action( 'wp_ajax_nopriv_ajax_check_missed_alt', array( $this, 'ajax_check_missed_alt' ) );
		add_action( 'wp_ajax_ajax_add_missed_alt', array( $this, 'ajax_add_missed_alt' ) ); //Add button pressed
		add_action( 'wp_ajax_nopriv_ajax_add_missed_alt', array( $this, 'ajax_add_missed_alt' ) );
		add_action( 'wp_ajax_ajax_set_missed_alt', array( $this, 'ajax_set_missed_alt' ) ); //Set button pressed (selected image)
		add_action( 'wp_ajax_nopriv_ajax_set_missed_alt', array( $this, 'ajax_set_missed_alt' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice__alt_info' ) );
	}

    /* Enqueue Admin Scripts only on upload.php page*/
    public function enqueue_admin_scripts() {
        global $pagenow;
        if ( $pagenow !== 'upload.php' ) return;
		wp_enqueue_style( 'set_missed_alt-css', SET_MA . 'assets/css/admin.css', array('wp-color-picker'), SET_MA_VER );
		wp_enqueue_script( 'set_missed_alt-js', SET_MA . 'assets/js/admin.js', array( 'jquery' ), SET_MA_VER, true );
    }

	/* Ajax call for Check button pressed */
	public function ajax_check_missed_alt() {
		$query_images_args = array(
		   'post_type' => 'attachment',
		   'post_mime_type' =>'image',
		   'post_status' =>'inherit',
		   'posts_per_page' => -1,
		);
		$query_images = new WP_Query( $query_images_args );
		$img_alt = '';
		foreach ( $query_images->posts as $image) { // check if an image w/o alt
		   $alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
		   if (!$alt) $img_alt .= $image->post_title . ' - ' . wp_get_attachment_url( $image->ID ) . '<br />';
		}
		if ($img_alt=='') $img_alt = __('Looks like there are no files without alts... )))',$this->slug);
		$insert = '<div class="missed_alt"><p>'.__('Done!',$this->slug).'<br />'.$img_alt.'</p></div>';
		$returnResponse = array("code" => "success", "insert" => wp_kses_post($insert));
		echo json_encode($returnResponse);
		die();
	}

	/* Ajax call for Add button pressed */
	public function ajax_add_missed_alt() {
		$query_images_args = array(
		   'post_type' => 'attachment',
		   'post_mime_type' =>'image',
		   'post_status' =>'inherit',
		   'posts_per_page' => -1,
		);

		$query_images = new WP_Query( $query_images_args );
		$img_alt = '';
		foreach ( $query_images->posts as $image) { // check if an image w/o alt then set alt as post_title
		   $alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
		   if (!$alt) {
			   $img_alt .= $image->post_title . ' - ' . wp_get_attachment_url( $image->ID ) . '<br />';
			   update_post_meta( $image->ID, '_wp_attachment_image_alt', $image->post_title );
		   }
		}
		if ($img_alt=='') $insert = '<div class="missed_alt"><p>'.__('Looks like there are no files without alts... )))',$this->slug).'</p></div>';
		else $insert = '<div class="missed_alt"><p>'.$img_alt.'<br />'.__('Done! Please reload current page.',$this->slug).'</p></div>';
		$returnResponse = array("code" => "success", "insert" => wp_kses_post($insert));
		echo json_encode($returnResponse);
		die();
	}
	
	/* Ajax call for Set button pressed (selected image) */
	public function ajax_set_missed_alt() {
		if ( isset( $_POST['img_id'] ) && intval( $_POST['img_id'] ) > 0 ) $img_id = intval( $_POST['img_id'] ); else die();
		if ( isset( $_POST['img_alt'] ) ) $img_alt = sanitize_text_field( $_POST['img_alt'] ); 
		if ($img_alt=='') die(); // no empty alt
			update_post_meta( $img_id, '_wp_attachment_image_alt', $img_alt );
		$returnResponse = array("code" => "success");
		echo json_encode($returnResponse);
		die();
	}

    /* Display admin notice */
	public function admin_notice__alt_info() {
        global $pagenow;
        if ( $pagenow == 'upload.php' ) {
			echo '<div class="missed_alt-notice notice notice-info is-dismissible"><p>'.
				'<strong>',__('Set all missed alt tag as post_title.',$this->slug),'</strong> ',__('Don\'t forget to backup your DB first!',$this->slug),'</p>'.
				'<p><em>',__('Thank you for using "Add missed Alt". It would be great if you <a href="https://github.com/Prizzrakk" target="_blank">support</a> the author!',$this->slug),'</em></p>'.
				'<p><button type="submit" name="check_missed_alt_button" class="check_missed_alt_button button">'.__('Check missed ALT',$this->slug).'</button>'.
				'<button type="submit" name="add_missed_alt_button" class="add_missed_alt_button button">'.__('Add missed ALT',$this->slug).'</button>'.
				'<span class="loaderimage"></span>'.
				'<p class="hidden">'.__('Please wait....',$this->slug).'</p>'.
			'</p></div>';
		}
	}

	
	/* Additional columns in admin Media page - Alt tag / Dimensions / FSize */
	public function add_media_column( $columns ) {
		$add_cols= [ 'imgalt' => 'Alt', 'imgsize' => 'Dimensions', 'file_size' => 'Size' ];  // add new column before 2-nd column
		$columns = array_slice( $columns, 0, 2, true ) + $add_cols + array_slice( $columns, 2, NULL, true );
		return $columns;
	}
	 
	public function media_column_add_row( $name, $media_id ){ // Add row content for Alt / Dimention / FSize
		if( $name == 'imgalt' ) 
			echo '<span>'.get_post_meta( $media_id, '_wp_attachment_image_alt', true ).'</span>
					<div class="row-actions alt_'.esc_html($media_id).'">
						<input type="text" class="imgalt_input"> 
						<button type="submit" name="" class="imgalt_button button" value="'.esc_html($media_id).'">'.__('Save',$this->slug).'</button>
						<span class="loaderimage '.esc_html($media_id).'"></span>
					</div>';
		if( $name == 'imgsize' ) { 
			$attachment = get_post_meta($media_id, '_wp_attachment_metadata', true);
			if ( isset( $attachment['width'] ) && isset( $attachment['height'] ) )
				echo esc_html( $attachment['width'] . 'x' . $attachment['height'] );
		}
		if ( $name === 'file_size' ) {
			$file_size = get_post_meta($media_id, '_wp_attachment_filesize', true);
			if (!$file_size) { // If no meta tag with File size then get File size and save meta
				$file_size = filesize(get_attached_file($media_id));
				update_post_meta( $media_id, '_wp_attachment_filesize', $file_size );
			}
			echo esc_html( size_format($file_size, 2) );
		}
	}

	public function media_sortable_column( $columns ){ // Add sortable columns
		$columns[ 'imgalt' ] = 'alt';
		$columns[ 'imgsize' ] = 'size';
		$columns[ 'file_size' ] = 'fsize';
		return $columns;
	}

	public function media_orderby_columns( $query ) { // Sort rule (query) for every new column
		if ( !is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'attachment' ) return;
		$orderby = $query->get( 'orderby');
		if( 'alt' == $orderby ) {
			$query->set( 'meta_key', '_wp_attachment_image_alt' );
			$query->set( 'orderby', 'meta_value' );
		}
		if( 'size' == $orderby ) { // doesn't work as numeric...
			$query->set('orderby', 'meta_value');
			$query->set('meta_key', '_wp_attachment_metadata');
			$query->set( 'meta_query', array(
				'key' => '_wp_attachment_metadata',
				array( 'value' => 'width', 'compare' => 'LIKE', 'meta_type', 'NUMERIC' ),
			) );
		}
		if( 'fsize' == $orderby ) {
			$query->set('meta_key', '_wp_attachment_filesize');
			$query->set('orderby', 'meta_value_num');
		}
	}

	public function save_attachment_metadata( $attachment_id ) { // Save file size as meta_data for sorting
		if( get_post_type($attachment_id) === 'attachment' && wp_attachment_is_image($attachment_id) ) {
			$file_size = get_post_meta($media_id, '_wp_attachment_filesize', true);
			if (!$file_size) {
				$file_size = filesize(get_attached_file($media_id));
				update_post_meta( $media_id, '_wp_attachment_filesize', $file_size );
			}
		}
	}

	/* END Additional columns in admin Media page - alt tag / Dimensions / FSize */


		/* Add links to plugin support link. */
		public function plugin_row_meta( $links, $file ) {
			if ( ! current_user_can( 'install_plugins' ) ) return $links;
			if ( $file == SET_MA_BASENAME )
				return array_merge(	$links,	array (	sprintf( '<a href="https://github.com/Prizzrakk" target="_blank">%s</a>', __( 'Support', $this->slug ) )	) );
			return $links;
		}

} // end class

} // end class check


/* GO! */
Set_Missed_Alt::get_instance();

?>