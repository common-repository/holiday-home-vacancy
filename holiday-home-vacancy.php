<?php 

/*
    Plugin Name: Holiday Home Vacancy
    Description: Calendar for Holiday Home Vacancies
    Version: 1.0.0
    Author: Manuel Harder
    Author URI: https://www.manuelharder.com
    License: GPL v2 or later
    License URI: https://www.gnu.org/licenses/gpl-2.0.html

*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



include_once('appVacancy.php');
include_once('settingsPage.php');

add_action( 'init', 'hhomev_createOccupationPostType' );
add_action( 'save_post', 'hhomev_save_events_meta', 1, 2 );
add_action( 'wp_ajax_hhomev_returnNewCalendar', 'hhomev_returnNewCalendar');
add_action( 'wp_ajax_nopriv_hhomev_returnNewCalendar', 'hhomev_returnNewCalendar');
add_action( 'wp_head', 'hhomev_the_ajax_url');
add_action( 'wp_enqueue_scripts', 'hhomev_holiday_home_occupation_scripts' );
add_shortcode( 'holiday-home-vacancy', 'hhomev_holidayHomeOccupation' );


function hhomev_createOccupationPostType() {
    $labels = array(
        'name'                  => _x( 'Occupied', 'Post Type General Name', 'holiday-home-vacancy' ),
        'singular_name'         => _x( 'Occupied', 'Post Type Singular Name', 'holiday-home-vacancy' ),
        'menu_name'             => __( 'Occupied', 'holiday-home-vacancy' ),
        'name_admin_bar'        => __( 'Occupied', 'holiday-home-vacancy' ),
        'all_items'             => __( 'All Entries', 'holiday-home-vacancy' ),
        'add_new_item'          => __( 'Create Entry', 'holiday-home-vacancy' ),
        'new_item'              => __( 'New Entry', 'holiday-home-vacancy' ),
        'edit_item'             => __( 'Edit Entry', 'holiday-home-vacancy' ),
        'update_item'           => __( 'Update Entry', 'holiday-home-vacancy' ),
        'view_item'             => __( 'View Entry', 'holiday-home-vacancy' ),
        'view_items'            => __( 'View Entries', 'holiday-home-vacancy' ),
        'not_found'             => __( 'Not found', 'holiday-home-vacancy' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'holiday-home-vacancy' )
    );
    $args = array(
        'public' => true,
        'labels'  => $labels,
        'has_archive' => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'supports'              => array( 'title'),
        'register_meta_box_cb' => 'hhomev_add_when_metaboxes'
    );
    register_post_type( 'calendar_occupation', $args );
}




function hhomev_add_when_metaboxes() {
    add_meta_box(
        'hhomev_from_location',
        'From',
        'hhomev_from_location',
        'calendar_occupation',
        'normal',
        'high'
    );
    add_meta_box(
        'hhomev_to_location',
        'To (the last full day)',
        'hhomev_to_location',
        'calendar_occupation',
        'normal',
        'high'
    );
}

function hhomev_from_location() {
    global $post;
    // Nonce field to validate form request came from current site
    wp_nonce_field( basename( __FILE__ ), 'from_to_fields' );
    // Get the location data if it's already been entered
    $hhomev_from_location = get_post_meta( $post->ID, 'hhomev_from_location', true );
    // Output the field
    echo '<input type="date" name="hhomev_from_location" value="' . esc_textarea( $hhomev_from_location )  . '" class="widefat">';
}

function hhomev_to_location() {
    global $post;
    // Nonce field to validate form request came from current site
    //wp_nonce_field( basename( __FILE__ ), 'event_fields' );
    // Get the location data if it's already been entered
    $hhomev_to_location = get_post_meta( $post->ID, 'hhomev_to_location', true );
    // Output the field
    echo '<input type="date" name="hhomev_to_location" value="' . esc_textarea( $hhomev_to_location )  . '" class="widefat">';
}


function hhomev_save_events_meta( $post_id, $post ) {
    // Return if the user doesn't have edit permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }
    // Verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times.
    if ( !isset( $_POST['hhomev_from_location'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['from_to_fields'] ) ), basename(__FILE__) ) ) {
        return $post_id;
    }
    // Now that we're authenticated, time to save the data.
    // This sanitizes the data from the field and saves it into an array $events_meta.
    $events_meta['hhomev_from_location'] = esc_textarea( sanitize_text_field($_POST['hhomev_from_location']) );
    $events_meta['hhomev_to_location'] = esc_textarea( sanitize_text_field($_POST['hhomev_to_location']) );
    // Cycle through the $events_meta array.
    // Note, in this example we just have one item, but this is helpful if you have multiple.
    foreach ( $events_meta as $key => $value ) :
        // Don't store custom data twice
        if ( 'revision' === $post->post_type ) {
            return;
        }
        if ( get_post_meta( $post_id, $key, false ) ) {
            // If the custom field already has a value, update it.
            update_post_meta( $post_id, $key, $value );
        } else {
            // If the custom field doesn't have a value, add it.
            add_post_meta( $post_id, $key, $value);
        }
        if ( ! $value ) {
            // Delete the meta key if there's no value
            delete_post_meta( $post_id, $key );
        }
    endforeach;
}



function hhomev_returnNewCalendar(){

    if ( !check_ajax_referer( 'ajax-nonce', 'nonce', false ) ) {

        wp_send_json_error( 'Invalid security token sent.' );
        wp_die();
    }
    
    include(plugins_url( '/appVacancy.php', __FILE__ ));

    $year = sanitize_text_field($_POST["j"]);
    $month = sanitize_text_field($_POST["m"]);

    $cal = new HHomeV_AppVacancy();
    $cal->setDate("1", $month, $year);

    list($month, $year, $prevMonthDate, $nextMonthDate) = $cal->returnHeaderInfo();

    $return = '<div><div id="calender_occupy">';
                    
    $return .= '<div class="calenderHeader">';
        $return .= '<div id="linkPrev_control">';
        $return .= '<a id="linkPrev" class="updateCal" data-ajax="j='.wp_date("Y", strtotime($prevMonthDate)).'&m='.
                        wp_date("n", strtotime($prevMonthDate)).'" href="#">«</a>';
        $return .= '</div>';
                            
        $return .= '<div id="pnlNow_control"><div id="pnlNow">'.esc_html($month).' '.esc_html($year).'</div></div>';      

        $return .= '<div id="linkNext_control">';
        $return .= '<a id="linkNext" class="updateCal" data-ajax="j='.wp_date("Y", strtotime($nextMonthDate)).'&m='.
                        wp_date("n", strtotime($nextMonthDate)).'" href="#">»</a>';
        $return .= '</div>';
    $return .= '</div>';                      
    $return .=  $cal->showCalender(false);                    
         
    $return .= '</div></div>';                       

    echo wp_kses_post($return);
    die();
}


function hhomev_the_ajax_url() {

   echo '<script type="text/javascript">
           var ajaxurl = "' . esc_html(admin_url('admin-ajax.php')) . '";
         </script>';
}

function hhomev_holiday_home_occupation_scripts() { 

    wp_enqueue_style( 'hov_style', plugins_url( '/assets/css/styles.css', __FILE__ ), array(), '1.0' );

    wp_register_script('hov_sc', plugins_url( '/assets/js/scripts.js', __FILE__  ), array( 'jquery' ), false, true );
    wp_enqueue_script('hov_sc');

    wp_localize_script('hov_sc', 'ajax_var', array(
         'url' => admin_url('admin-ajax.php'),
         'nonce' => wp_create_nonce('ajax-nonce')
    ));
}




function hhomev_holidayHomeOccupation( $atts ) {

    global $wpdb;
    $_info = $wpdb->get_var("SELECT value FROM `{$wpdb->base_prefix}holiday_home_occupation` WHERE `key` = 'info'");

    // Attributes
    $atts = shortcode_atts(array('id' => ''), $atts);

    $cal = new HHomeV_AppVacancy();
    list($month, $year, $prevMonthDate, $nextMonthDate) = $cal->returnHeaderInfo();

    $return = '<div class="holiday-occupation" id="holiday-occupation"><div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div><div id="calender_occupy">';
                    
    $return .= '<div class="calenderHeader">';
        $return .= '<div id="linkPrev_control">';
        $return .= '<a id="linkPrev" class="updateCal" data-ajax="j='.wp_date("Y", strtotime($prevMonthDate)).'&m='.
                        wp_date("n", strtotime($prevMonthDate)).'" href="#">«</a>';
        $return .= '</div>';
                            
        $return .= '<div id="pnlNow_control"><div id="pnlNow">'.esc_html($month).' '.wp_date('Y').'</div></div>';      

        $return .= '<div id="linkNext_control">';
        $return .= '<a id="linkNext" class="updateCal" data-ajax="j='.wp_date("Y", strtotime($nextMonthDate)).'&m='.
                        wp_date("n", strtotime($nextMonthDate)).'" href="#">»</a>';
        $return .= '</div>';
    $return .= '</div>';                      
    $return .=  $cal->showCalender(false);                    
         
    $return .= '</div>';                       
    $return .= '<p class="calendar__info">'.esc_html($_info).'</p></div>';   

    return wp_kses_post($return);
}

