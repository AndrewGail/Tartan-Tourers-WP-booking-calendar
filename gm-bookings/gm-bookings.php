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
		
		add_filter("manage_edit-gm_bookings_columns", array(&$this, "edit_columns"));
		add_action("manage_posts_custom_column", array(&$this, "custom_columns"));
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
	
	function edit_columns( $columns ) {
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"gm_bookings_customer" => "Customer",
			"gm_bookings_date" => "Date",
			"gm_bookings_duration" => "Duration",
			"gm_bookings_charge" => "Charge",
			"gm_bookings_van" => "Van"
			);

		return $columns;
	}

	function custom_columns( $column ) {
		global $post;
		$custom = get_post_custom();
		
		switch ( $column ) {
			case "gm_bookings_customer":
				echo $custom['gm_bookings_first_name'][0] . ' ' . $custom['gm_bookings_last_name'][0];
				break;
			case "gm_bookings_date":
				echo $custom["gm_bookings_start_date"][0];
				break;
			case "gm_bookings_duration":
				$end_date = strtotime(str_replace('/', '-', $custom["gm_bookings_end_date"][0]));
				$start_date = strtotime(str_replace('/', '-', $custom["gm_bookings_start_date"][0]));
				$daycount = round(abs($end-$start)/60/60/24);
				echo round(abs($end_date-$start_date)/60/60/24) . ' days';
				break;
			case "gm_bookings_charge":
				echo '&pound;' . $custom["gm_bookings_charge"][0];
				break;
			case "gm_bookings_van":
				$vans = get_the_terms(0, "gm_vans");
				$vans_html = array();
				foreach ($vans as $van)
					array_push($vans_html, '<a href="' . get_term_link($van->slug, "gm_vans") . '">' . $van->name . '</a>');

				echo implode($vans_html, ", ");
				break;
		}
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
		
		echo '<div id="gm_booking_form">';
		
		// Customer details
		echo '<h2>Customer details</h2>';
		
		echo '<p><label for="gm_bookings_first_name">First Name:</label>';
		echo '<input type="text" id="gm_bookings_first_name" name="gm_bookings_first_name" value="' . get_post_meta($post->ID, 'gm_bookings_first_name', TRUE) . '" size="30" /></p>';
		
		echo '<p><label for="gm_bookings_last_name">Last Name:</label>';
		echo '<input type="text" id="gm_bookings_last_name" name="gm_bookings_last_name" value="' . get_post_meta($post->ID, 'gm_bookings_last_name', TRUE) . '" size="30" /></p>';
		
		echo '<p><label for="gm_bookings_dob">Date of birth:</label>';
		echo '<input type="text" id="gm_bookings_dob" name="gm_bookings_dob" value="' . get_post_meta($post->ID, 'gm_bookings_dob', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_job">Occupation:</label>';
		echo '<input type="text" id="gm_bookings_job" name="gm_bookings_job" value="' . get_post_meta($post->ID, 'gm_bookings_job', TRUE) . '" size="30" /></p>';
		
		// Address
		echo '<h2>Customer address</h2>';
		
		echo '<p><label for="gm_bookings_addr">Address:</label><br/>';
		echo '<textarea id="gm_bookings_addr" name="gm_bookings_addr" value="" cols="60" rows="7">' . get_post_meta($post->ID, 'gm_bookings_addr', TRUE) . '</textarea></p>';
		
		// Contact details
		echo '<h2>Contact details</h2>';
		
		echo '<p><label for="gm_bookings_teld">Daytime telephone:</label>';
		echo '<input type="text" id="gm_bookings_teld" name="gm_bookings_teld" value="' . get_post_meta($post->ID, 'gm_bookings_teld', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_tele">Evening telephone:</label>';
		echo '<input type="text" id="gm_bookings_tele" name="gm_bookings_tele" value="' . get_post_meta($post->ID, 'gm_bookings_tele', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_mob">Mobile:</label>';
		echo '<input type="text" id="gm_bookings_mob" name="gm_bookings_mob" value="' . get_post_meta($post->ID, 'gm_bookings_mob', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_fax">Fax:</label>';
		echo '<input type="text" id="gm_bookings_fax" name="gm_bookings_fax" value="' . get_post_meta($post->ID, 'gm_bookings_fax', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_email">Email:</label>';
		echo '<input type="text" id="gm_bookings_email" name="gm_bookings_email" value="' . get_post_meta($post->ID, 'gm_bookings_email', TRUE) . '" size="20" /></p>';

		// Hire details
		echo '<h2>Hire details</h2>';
		
		echo '<p><label for="gm_bookings_charge">Charge (&pound;):</label>';
		echo '<input type="text" id="gm_bookings_charge" name="gm_bookings_charge" value="' . get_post_meta($post->ID, 'gm_bookings_charge', TRUE) . '" size="10" /></p>';
		
		echo '<p><label for="gm_bookings_start_date">Hire start date:</label>';
		echo '<input type="text" id="gm_bookings_start_date" name="gm_bookings_start_date" value="' . get_post_meta($post->ID, 'gm_bookings_start_date', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_end_date">Hire end date:</label>';
		echo '<input type="text" id="gm_bookings_end_date" name="gm_bookings_end_date" value="' . get_post_meta($post->ID, 'gm_bookings_end_date', TRUE) . '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_num_adults">Number of adults:</label>';
		echo '<input type="text" id="gm_bookings_num_adults" name="gm_bookings_num_adults" value="' . get_post_meta($post->ID, 'gm_bookings_num_adults', TRUE) . '" size="10" /></p>';
		
		echo '<p><label for="gm_bookings_num_child">Number of children:</label>';
		echo '<input type="text" id="gm_bookings_num_child" name="gm_bookings_num_child" value="' . get_post_meta($post->ID, 'gm_bookings_num_child', TRUE) . '" size="10" /></p>';
		
		// Notes
		echo '<h2>Notes</h2>';
		
		echo '<p><label for="gm_bookings_note">Any other information:</label><br/>';
		echo '<textarea id="gm_bookings_note" name="gm_bookings_note" value="" cols="60" rows="7">' . get_post_meta($post->ID, 'gm_bookings_note', TRUE) . '</textarea></p>';
		
		echo '</div>';
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
		if( !is_object( $post ) ) return;
		
		$styleFile = $this->plugin_dir . "/css/gm-bookings.css";
		if(file_exists($styleFile) && $post->post_type == 'gm_bookings'){
			wp_register_style('gm-bookings-css', $this->plugin_url . "/css/gm-bookings.css");
			wp_enqueue_style( 'gm-bookings-css');
		}
		
		$pickerFile = $this->plugin_dir . "/css/smoothness/jquery-ui-1.8.13.custom.css";
		if(file_exists($pickerFile) && $post->post_type == 'gm_bookings'){
			wp_register_style('datepicker-css', $this->plugin_url . "/css/smoothness/jquery-ui-1.8.13.custom.css");
			wp_enqueue_style( 'datepicker-css');
		}
	}
	
	public function add_js() {
		global $post;
		if( !is_object( $post ) ) return;
		
		$jsFile = $this->plugin_dir . "/js/jquery-ui-1.8.13.custom.min.js";
		if(file_exists($jsFile) && $post->post_type == 'gm_bookings'){
			wp_register_script('datepicker-js', $this->plugin_url . "/js/jquery-ui-1.8.13.custom.min.js");
			wp_enqueue_script( 'datepicker-js');
		}
	}
	
	public function call_js() {
		global $post;
		if( !is_object( $post ) ) return;
		
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