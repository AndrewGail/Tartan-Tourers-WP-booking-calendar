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

    public function __construct() {
		$this->plugin_dir = WP_PLUGIN_DIR . "/gm-bookings";
		$this->plugin_url = WP_PLUGIN_URL . "/gm-bookings";

		add_action( 'admin_print_styles', array($this, 'add_stylesheets') );
		add_action( 'admin_enqueue_scripts', array($this, 'add_js') );
		add_action( 'admin_head', array($this, 'call_js') );
        
        add_action( 'init', array( &$this, 'create_post_type' ) );
        add_action( 'init', array( &$this, 'create_van_taxonomy' ) );
        add_action( 'add_meta_boxes', array( &$this, 'create_meta_box' ) );
        add_action( 'save_post', array( &$this, 'save_meta_box' ) );
		
		add_filter( "manage_edit-gm_bookings_columns", array(&$this, "edit_columns") );
		add_action( "manage_posts_custom_column", array(&$this, "custom_columns") );
		
		add_shortcode( 'gm-calendar', array(&$this, "draw_calendar") );
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
		setlocale(LC_ALL, 'en_GB'); // Forcing dates to british
		$custom = get_post_meta( $post->ID, 'gm_booking_meta', true );
		
		if( strlen( $custom ) < 1 ) {
			return;
		} else {
			$custom = unserialize( $custom );
		}
		
		switch ( $column ) {
			case "gm_bookings_customer":
				$cust_name = $custom['gm_bookings_first_name'] . ' ' . $custom['gm_bookings_last_name'];
				echo '<strong><a class="row-title" title="Edit booking for &quot;' . $cust_name . '&quot;" href="' . get_admin_url() . 'post.php?post=' . $post->ID . '&action=edit&post_type=gm_bookings">' . $cust_name . '</strong>';
				break;
			case "gm_bookings_date":
				echo $custom["gm_bookings_start_date"];
				break;
			case "gm_bookings_duration":
				$end_date = strtotime(str_replace('/', '-', $custom["gm_bookings_end_date"]));
				$start_date = strtotime(str_replace('/', '-', $custom["gm_bookings_start_date"]));
				//$daycount = round(abs($end_date-$start_date)/60/60/24);
				echo round(abs($end_date-$start_date)/60/60/24)+1 . ' days';
				break;
			case "gm_bookings_charge":
				echo '&pound;' . $custom["gm_bookings_charge"];
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
		$custom = get_post_meta( $post->ID, 'gm_booking_meta', true );
		
		if( strlen( $custom ) < 1 ) {
			$custom = array();
		} else {
			$custom = unserialize( $custom );
		}
		
		echo '<div id="gm_booking_form">';
		
		// Customer details
		echo '<h2>Customer details</h2>';
		
		echo '<p><label for="gm_bookings_first_name">First Name:</label>';
		echo '<input type="text" id="gm_bookings_first_name" name="gm_bookings_first_name" value="'; if( array_key_exists( 'gm_bookings_first_name', $custom ) ) echo $custom['gm_bookings_first_name']; echo '" size="30" /></p>';
		
		echo '<p><label for="gm_bookings_last_name">Last Name:</label>';
		echo '<input type="text" id="gm_bookings_last_name" name="gm_bookings_last_name" value="'; if( array_key_exists( 'gm_bookings_last_name', $custom ) ) echo $custom['gm_bookings_last_name']; echo '" size="30" /></p>';
		
		echo '<p><label for="gm_bookings_dob">Date of birth:</label>';
		echo '<input type="text" id="gm_bookings_dob" name="gm_bookings_dob" value="'; if( array_key_exists( 'gm_bookings_dob', $custom ) ) echo $custom['gm_bookings_dob']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_job">Occupation:</label>';
		echo '<input type="text" id="gm_bookings_job" name="gm_bookings_job" value="'; if( array_key_exists( 'gm_bookings_job', $custom ) ) echo $custom['gm_bookings_job']; echo '" size="30" /></p>';
		
		// Address
		echo '<h2>Customer address</h2>';
		
		echo '<p><label for="gm_bookings_addr">Address:</label><br/>';
		echo '<textarea id="gm_bookings_addr" name="gm_bookings_addr" value="" cols="60" rows="7">'; if( array_key_exists( 'gm_bookings_addr', $custom ) ) echo $custom['gm_bookings_addr']; echo '</textarea></p>';
		
		// Contact details
		echo '<h2>Contact details</h2>';
		
		echo '<p><label for="gm_bookings_teld">Daytime telephone:</label>';
		echo '<input type="text" id="gm_bookings_teld" name="gm_bookings_teld" value="'; if( array_key_exists( 'gm_bookings_teld', $custom ) ) echo $custom['gm_bookings_teld']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_tele">Evening telephone:</label>';
		echo '<input type="text" id="gm_bookings_tele" name="gm_bookings_tele" value="'; if( array_key_exists( 'gm_bookings_tele', $custom ) ) echo $custom['gm_bookings_tele']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_mob">Mobile:</label>';
		echo '<input type="text" id="gm_bookings_mob" name="gm_bookings_mob" value="'; if( array_key_exists( 'gm_bookings_mob', $custom ) ) echo $custom['gm_bookings_mob']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_fax">Fax:</label>';
		echo '<input type="text" id="gm_bookings_fax" name="gm_bookings_fax" value="'; if( array_key_exists( 'gm_bookings_fax', $custom ) ) echo $custom['gm_bookings_fax']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_email">Email:</label>';
		echo '<input type="text" id="gm_bookings_email" name="gm_bookings_email" value="'; if( array_key_exists( 'gm_bookings_email', $custom ) ) echo $custom['gm_bookings_email']; echo '" size="20" /></p>';

		// Hire details
		echo '<h2>Hire details</h2>';
		
		echo '<p><label for="gm_bookings_charge">Charge (&pound;):</label>';
		echo '<input type="text" id="gm_bookings_charge" name="gm_bookings_charge" value="'; if( array_key_exists( 'gm_bookings_charge', $custom ) ) echo $custom['gm_bookings_charge']; echo '" size="10" /></p>';
		
		echo '<p><label for="gm_bookings_start_date">Hire start date:</label>';
		echo '<input type="text" id="gm_bookings_start_date" name="gm_bookings_start_date" value="'; if( array_key_exists( 'gm_bookings_start_date', $custom ) ) echo $custom['gm_bookings_start_date']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_end_date">Hire end date:</label>';
		echo '<input type="text" id="gm_bookings_end_date" name="gm_bookings_end_date" value="'; if( array_key_exists( 'gm_bookings_end_date', $custom ) ) echo $custom['gm_bookings_end_date']; echo '" size="20" /></p>';
		
		echo '<p><label for="gm_bookings_num_adults">Number of adults:</label>';
		echo '<input type="text" id="gm_bookings_num_adults" name="gm_bookings_num_adults" value="'; if( array_key_exists( 'gm_bookings_num_adults', $custom ) ) echo $custom['gm_bookings_num_adults']; echo '" size="10" /></p>';
		
		echo '<p><label for="gm_bookings_num_child">Number of children:</label>';
		echo '<input type="text" id="gm_bookings_num_child" name="gm_bookings_num_child" value="'; if( array_key_exists( 'gm_bookings_num_child', $custom ) ) echo $custom['gm_bookings_num_child']; echo '" size="10" /></p>';
		
		// Notes
		echo '<h2>Notes</h2>';
		
		echo '<p><label for="gm_bookings_note">Any other information:</label><br/>';
		echo '<textarea id="gm_bookings_note" name="gm_bookings_note" value="" cols="60" rows="7">'; if( array_key_exists( 'gm_bookings_note', $custom ) ) echo $custom['gm_bookings_note']; echo '</textarea></p>';
		
		echo '</div>';
	}
    
	public function save_meta_box( $post_ID ) {
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
		$meta_array = array(
			'gm_bookings_first_name' => '',
			'gm_bookings_last_name' => '',
			'gm_bookings_dob' => '',
			'gm_bookings_job' => '',
			'gm_bookings_addr' => '',
			'gm_bookings_teld' => '',
			'gm_bookings_tele' => '',
			'gm_bookings_mob' => '',
			'gm_bookings_fax' => '',
			'gm_bookings_email' => '',
			'gm_bookings_charge' => '',
			'gm_bookings_start_date' => '',
			'gm_bookings_end_date' => '',
			'gm_bookings_num_adults' => '',
			'gm_bookings_num_child' => '',
			'gm_bookings_note' => ''
		);
		
		if( array_key_exists( 'gm_bookings_first_name', $_POST ) ) $meta_array['gm_bookings_first_name'] = $_POST['gm_bookings_first_name'];
		if( array_key_exists( 'gm_bookings_last_name', $_POST ) ) $meta_array['gm_bookings_last_name'] = $_POST['gm_bookings_last_name'];
		if( array_key_exists( 'gm_bookings_dob', $_POST ) ) $meta_array['gm_bookings_dob'] = $_POST['gm_bookings_dob'];
		if( array_key_exists( 'gm_bookings_job', $_POST ) ) $meta_array['gm_bookings_job'] = $_POST['gm_bookings_job'];
		if( array_key_exists( 'gm_bookings_addr', $_POST ) ) $meta_array['gm_bookings_addr'] = $_POST['gm_bookings_addr'];
		if( array_key_exists( 'gm_bookings_teld', $_POST ) ) $meta_array['gm_bookings_teld'] = $_POST['gm_bookings_teld'];
		if( array_key_exists( 'gm_bookings_tele', $_POST ) ) $meta_array['gm_bookings_tele'] = $_POST['gm_bookings_tele'];
		if( array_key_exists( 'gm_bookings_mob', $_POST ) ) $meta_array['gm_bookings_mob'] = $_POST['gm_bookings_mob'];
		if( array_key_exists( 'gm_bookings_fax', $_POST ) ) $meta_array['gm_bookings_fax'] = $_POST['gm_bookings_fax'];
		if( array_key_exists( 'gm_bookings_email', $_POST ) ) $meta_array['gm_bookings_email'] = $_POST['gm_bookings_email'];
		if( array_key_exists( 'gm_bookings_charge', $_POST ) ) $meta_array['gm_bookings_charge'] = $_POST['gm_bookings_charge'];
		if( array_key_exists( 'gm_bookings_start_date', $_POST ) ) $meta_array['gm_bookings_start_date'] = $_POST['gm_bookings_start_date'];
		if( array_key_exists( 'gm_bookings_end_date', $_POST ) ) $meta_array['gm_bookings_end_date'] = $_POST['gm_bookings_end_date'];
		if( array_key_exists( 'gm_bookings_num_adults', $_POST ) ) $meta_array['gm_bookings_num_adults'] = $_POST['gm_bookings_num_adults'];
		if( array_key_exists( 'gm_bookings_num_child', $_POST ) ) $meta_array['gm_bookings_num_child'] = $_POST['gm_bookings_num_child'];
		if( array_key_exists( 'gm_bookings_note', $_POST ) ) $meta_array['gm_bookings_note'] = $_POST['gm_bookings_note'];
		
		update_post_meta( $post_ID, 'gm_booking_meta', serialize( $meta_array ) );

		return $post_ID;
	}
	
	public function draw_calendar() {
		$output = '';
	
		$output .= '<table id="availabilitycalendar" border="0" cellspacing="0" cellpadding="0">'."\n";
		$output .= '<caption>Tartan Tourers availability calendar 2011</caption>'."\n";
		$output .= '<colgroup>'."\n";
		$output .= '<col class="availabilitycalendarh"></col>'."\n";
		$output .= '<col class="availabilitycalendar0"></col>'."\n";
		$output .= '<col class="availabilitycalendar1"></col>'."\n";
		$output .= '<col class="availabilitycalendar2"></col>'."\n";
		$output .= '<col class="availabilitycalendar3"></col>'."\n";
		$output .= '<col class="availabilitycalendar4"></col>'."\n";
		$output .= '<col class="availabilitycalendar5"></col>'."\n";
		$output .= '<col class="availabilitycalendar6"></col>'."\n";
		$output .= '<col class="availabilitycalendar7"></col>'."\n";
		$output .= '<col class="availabilitycalendar8"></col>'."\n";
		$output .= '<col class="availabilitycalendar9"></col>'."\n";
		$output .= '<col class="availabilitycalendar10"></col>'."\n";
		$output .= '<col class="availabilitycalendar11"></col>'."\n";
		$output .= '</colgroup>'."\n";
		$output .= '<thead>'."\n";
		$output .= '<tr>'."\n";
		$output .= '<th></th>'."\n";
		$output .= '<th>Jan</th>'."\n";
		$output .= '<th>Feb</th>'."\n";
		$output .= '<th>Mar</th>'."\n";
		$output .= '<th>Apr</th>'."\n";
		$output .= '<th>May</th>'."\n";
		$output .= '<th>Jun</th>'."\n";
		$output .= '<th>Jul</th>'."\n";
		$output .= '<th>Aug</th>'."\n";
		$output .= '<th>Sep</th>'."\n";
		$output .= '<th>Oct</th>'."\n";
		$output .= '<th>Nov</th>'."\n";
		$output .= '<th>Dec</th>'."\n";
		$output .= '</tr>'."\n";
		$output .= '</thead>'."\n";
		$output .= '<tfoot>'."\n";
		$output .= '<tr>'."\n";
		$output .= '<th></th>'."\n";
		$output .= '<td>Jan</td>'."\n";
		$output .= '<td>Feb</td>'."\n";
		$output .= '<td>Mar</td>'."\n";
		$output .= '<td>Apr</td>'."\n";
		$output .= '<td>May</td>'."\n";
		$output .= '<td>Jun</td>'."\n";
		$output .= '<td>Jul</td>'."\n";
		$output .= '<td>Aug</td>'."\n";
		$output .= '<td>Sep</td>'."\n";
		$output .= '<td>Oct</td>'."\n";
		$output .= '<td>Nov</td>'."\n";
		$output .= '<td>Dec</td>'."\n";
		$output .= '</tr>'."\n";
		$output .= '</tfoot>'."\n";
		$output .= '<tbody>'."\n";
		
		// I'm just working with one Van for a proof of concept.
		$bookings = get_posts( array( 'post_type' => 'gm_bookings' ) );
		$this_year = '2011';
		$i = 1;
		while ( $i < 32 ) {
			$output .= '<tr>'."\n";
			$output .= '<th>'.str_pad( $i, 2, '0', STR_PAD_LEFT ).'</th>'."\n";
			
			$j = 1;
			if( $bookings ) {
				while ( $j < 13 ) {
					foreach( $bookings as $booking ) {
						$custom = get_post_meta( $booking->ID, 'gm_booking_meta', true );
						if( strlen( $custom ) < 1 ) { $custom = array(); } else { $custom = unserialize( $custom ); }
						
						$start_date = '01-01-2000';
						$end_date = '02-01-2000';
						$this_date = str_pad( $i, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $j, 2, '0', STR_PAD_LEFT ) . '-' . $this_year;
						
						if( array_key_exists( 'gm_bookings_start_date', $custom ) ) $start_date = str_replace('/', '-', $custom["gm_bookings_start_date"]);
						if( array_key_exists( 'gm_bookings_end_date', $custom ) ) $end_date = str_replace('/', '-', $custom["gm_bookings_end_date"]);
						
						if( $this->check_date( $start_date, $end_date, $this_date ) ) {
							$output .= '<td class="v1v0" title="Van one: booked Van two: available"><span>Van one: booked<br/>Van two: available</span></td>'."\n";
						} else {
							$output .= '<td class="v0v0" title="Van one: available Van two: available"><span>Van one: available<br/>Van two: available</span></td>'."\n";
						}
					}
					$j++;
				}
			} else {
				while ( $j < 13 ) {
					$output .= '<td class="v0v0"><span>Van one: available<br/>Van two: available</span></td>'."\n";
					$j++;
				}
			}
			
			$output .= '</tr>'."\n";
			$i++;
		}
		
		$output .= '</tbody>'."\n";
		$output .= '</table>'."\n";
		
		return $output;
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

	private function check_date( $start_date, $end_date, $check_date ) {
		// Check whether given date is between start & end
		return ( ( strtotime($check_date) >= strtotime($start_date) ) && ( strtotime($check_date) <= strtotime($end_date) ) );
	}
}

global $gmBookings;
$gmBookings = new GMBookings();
?>