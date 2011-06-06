<?php
/*
Plugin Name: Bookings Post Type
Plugin URI: http://www.gailmedia.co.uk/
Description: Creates a Bookings post type, shorcodes and template tags.  This is a bespoke development for Tartan Tourers.
Version: 1.0
Author: Gailmedia
Author URI: http://www.gailmedia.co.uk/wordpress-plugins/
License: GPLv2 or later
*/

class GMBookings {

	var $plugin_dir;
	var $plugin_url;

    public function __construct()
    {
		$this->plugin_dir = WP_PLUGIN_DIR . "/gm-bookings";
		$this->plugin_url = WP_PLUGIN_URL . "/gm-bookings";

		add_action( 'admin_print_styles', array($this, 'add_stylesheets') );
		add_action( 'admin_enqueue_scripts', array($this, 'add_js') );
		add_action( 'admin_head', array($this, 'call_js') );
        
        add_action( 'init', array( &$this, 'create_post_type' ) );
        add_action( 'init', array( &$this, 'create_van_taxonomy' ) );
        add_action( 'add_meta_boxes', array( &$this, 'create_meta_box' ) );
        add_action( 'save_post', array( &$this, 'save_meta_box' ) );
    }
    
    public function create_van_taxonomy() {
		register_taxonomy( 'gm_vans',
			array('gm_bookings'),
			array(
				'hierarchical' => false,
				'labels' => array(
					'name' => __( 'Vans' ),
					'singular_name' => __( 'Van' ),
					'search_items' =>  __( 'Search Vans' ),
					'all_items' => __( 'All Vans' ),
					'parent_item' => __( 'Parent Van' ),
					'parent_item_colon' => __( 'Parent Van:' ),
					'edit_item' => __( 'Edit Van' ), 
					'update_item' => __( 'Update Van' ),
					'add_new_item' => __( 'Add New Van' ),
					'new_item_name' => __( 'New Van' ),
					'menu_name' => __( 'Vans' )
					),
				'rewrite' => true
				)
			);
    }
	
	public function create_post_type() {
		register_post_type( 'gm_bookings',
			array(
			'labels' => array(
				'name' => __( 'Bookings' ),
				'singular_name' => __( 'Booking' ),
				'add_new' => __( 'Add New' ),
				'add_new_item' => __( 'Add New Booking' ),
				'edit' => __( 'Edit' ),
				'edit_item' => __( 'Edit Booking' ),
				'new_item' => __( 'New Booking' ),
				'view' => __( 'View Booking' ),
				'view_item' => __( 'View Booking' ),
				'search_items' => __( 'Search Bookings' ),
				'not_found' => __( 'No bookings found' ),
				'not_found_in_trash' => __( 'No bookings found in Trash' ),
				'parent' => __( 'Parent Booking' ),
				),
			'hierarchical' => false,

			'supports' => array( 'revisions' ),

			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 5,

			'show_in_nav_menus' => false,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'has_archive' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => array('slug' => 'reviews'),
			'capability_type' => 'post'
			)
		);
	}
    
	public function create_meta_box() {
		add_meta_box( 
			'gm_booking_meta',
			__( 'Booking details' ),
			array( &$this, 'meta_box_options' ),
			'gm_bookings',
			'normal'
		);
	}
    
	public function meta_box_options() {
		global $post;
		wp_nonce_field( plugin_basename( __FILE__ ), 'gm_bookings_noncename' );

		// Booking from
		echo '<p><label for="gm_bookings_start_date">';
		echo __( 'Booking from:' );
		echo '</label> <br />';
		echo '<input type="text" id="gm_bookings_start_date" name="gm_bookings_start_date" value="' . get_post_meta($post->ID, 'gm_bookings_start_date', TRUE) . '" size="20" /></p>';

		// Booking to
		echo '<p><label for="gm_bookings_end_date">';
		echo __( 'Booking to:' );
		echo '</label> <br />';
		echo '<input type="text" id="gm_bookings_end_date" name="gm_bookings_end_date" value="' . get_post_meta($post->ID, 'gm_bookings_end_date', TRUE) . '" size="20" /></p>';
	}
    
	public function save_meta_box() {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;
		if ( !isset( $_POST['gm_bookings_noncename'] ) ) {
			return;
		} else {
			if ( !wp_verify_nonce( $_POST['gm_bookings_noncename'], plugin_basename( __FILE__ ) ) )
			return;
		}

		// Check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
			return;
		}
		else {
			if ( !current_user_can( 'edit_post', $post_id ) )
			return;
		}

		// OK, we're authenticated: we need to find and save the data
		$gm_start_date = $_POST['gm_bookings_start_date'];

		// Do something with $gm_start_date 
		// probably using add_post_meta(), update_post_meta(), or 
		// a custom table (see Further Reading section below)

		return $mydata;
	}
	
	public function add_stylesheets() {
		global $post;
		$styleFile = $this->plugin_dir . "/css/smoothness/jquery-ui-1.8.13.custom.css";

		if(file_exists($styleFile) && $post->post_type == 'gm_bookings'){
			wp_register_style('datepicker-css', $this->plugin_url . "/css/smoothness/jquery-ui-1.8.13.custom.css");
			wp_enqueue_style( 'datepicker-css');
		}
	}
	
	public function add_js() {
		global $post;
		$jsFile = $this->plugin_dir . "/js/jquery-ui-1.8.13.custom.min.js";

		if(file_exists($jsFile) && $post->post_type == 'gm_bookings'){
			wp_register_script('datepicker-js', $this->plugin_url . "/js/jquery-ui-1.8.13.custom.min.js");
			wp_enqueue_script( 'datepicker-js');
		}
	}
	
	public function call_js() {
		global $post;
		if($post->post_type == 'gm_bookings'){
			echo "<script>
jQuery(document).ready(function() {
	jQuery('#gm_bookings_start_date').datepicker({ dateFormat: 'dd/mm/yy' });
	jQuery('#gm_bookings_end_date').datepicker({ dateFormat: 'dd/mm/yy' });
});
</script>";
		}
	}
}

global $gmBookings;
$gmBookings = new GMBookings();
?>