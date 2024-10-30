<?php

add_action( 'admin_menu', 'hhomev_holiday_occupation_page' );


// add menu item and check if posts need to be confirmed
function hhomev_holiday_occupation_page() {

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $create = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}holiday_home_occupation` (`id` smallint(5) unsigned NOT NULL AUTO_INCREMENT, `key` varchar(50) NOT NULL,`value` varchar(50) NOT NULL,PRIMARY KEY (`id`)) $charset_collate AUTO_INCREMENT=21;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($create);

    $entries = $wpdb->get_results("SELECT id FROM `{$wpdb->base_prefix}holiday_home_occupation`");

    if (!$entries) {

        $insert = "INSERT INTO `{$wpdb->base_prefix}holiday_home_occupation` (`id`, `key`, `value`) VALUES(1, 'jan', 'January'), (2, 'feb', 'February'), (3, 'mar', 'March'), (4, 'apr', 'April'), (5, 'may', 'May'), (6, 'jun', 'June'), (7, 'jul', 'July'), (8, 'aug', 'August'), (9, 'sep', 'September'), (10, 'oct', 'October'), (11, 'nov', 'November'), (12, 'dec', 'December'), (13, 'mo', 'Mo'), (14, 'tu', 'Tu'), (15, 'we', 'We'), (16, 'th', 'Th'), (17, 'fr', 'Fr'), (18, 'sa', 'Sa'), (19, 'su', 'Su'), (20, 'info', 'The circled days are occupied');";

        $wpdb->query($wpdb->prepare($insert));
    }


    add_submenu_page('edit.php?post_type=calendar_occupation', 'Settings ', 'Settings', 'publish_posts', 
                    'occupation_settings', 'hhomev_holiday_occupation_settings', 'dashicons-chart-bar', 6  );

}


function hhomev_holiday_occupation_settings(){

?>
    <style>
        .postbox-float {
            max-width:600px;
            margin-bottom:0;
        }
        .postbox-float::after {
            display:table;
            content: ' ';
            clear: both;
        }
        .postbox-float > * {
            float:left;
            border:0 !important;
        }
        .postbox-float .hndle { 
            width:140px;
        }
        .postbox-float .inside {
            padding-bottom:5px !important;
        }
        .postbox-float input[type="text"] {
            background:#F9F9F9;
        }
        @media screen and (min-width:800px) {
            .postbox-float .inside {
                min-width:calc(100% - 200px);
            }
        }
    </style>
    
<?php

    if ( isset($_POST['save']) && wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['hhomev_save_setting_nonce'] ) ) , basename(__FILE__)) ) { 
    
        hhomev_updateSettings(); 
    }

    echo '<div class="wrap"><h1>Settings:</h1>';
    echo '<h2>Shortcode for the calendar:</h2><p>[holiday-home-vacancy]</p>';
    echo '<h2>Translations:</h2><form id="poststuff" method="post">';

    global $wpdb;

    $settings = $wpdb->get_results("SELECT * FROM `{$wpdb->base_prefix}holiday_home_occupation`");

    $setArr = array('jan' => 'January', 'feb' => 'February', 'mar' => 'March',
                    'apr' => 'April', 'may' => 'May', 'jun' => 'June',
                    'jul' => 'July', 'aug' => 'August', 'sep' => 'September',
                    'oct' => 'October', 'nov' => 'November', 'dec' => 'December',
                    'mo' => 'Mo', 'tu' => 'Tu', 'we' => 'We', 'th' => 'Th', 'fr' => 'Fr', 'sa' => 'Sa', 'su' => 'Su', 'info' => 'Info'
    );

    foreach ($settings as $key => $setting) { 

        if (array_key_exists($setting->key, $setArr)) { ?>

            <div class="postbox postbox-float">
                <h2 class="hndle ui-sortable-handle"><span><?php echo esc_html($setArr[$setting->key]); ?></span></h2>
                <div class="inside">
                <input type="text" name="<?php echo esc_attr($setting->key); ?>" value="<?php echo esc_attr($setting->value); ?>" class="widefat"></div>
            </div>
<?php

        }

    }

    wp_nonce_field( basename( __FILE__ ), 'hhomev_save_setting_nonce' );
?>
  
    
    <input style="margin-top:1rem;" name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">
    </form></div>




<?php

}





function hhomev_updateSettings() {

    $setArr = array('jan' => 'January', 'feb' => 'February', 'mar' => 'March',
                    'apr' => 'April', 'may' => 'May', 'jun' => 'June',
                    'jul' => 'July', 'aug' => 'August', 'sep' => 'September',
                    'oct' => 'October', 'nov' => 'November', 'dec' => 'December',
                    'mo' => 'Mo', 'tu' => 'Tu', 'we' => 'We', 'th' => 'Th', 'fr' => 'Fr', 'sa' => 'Sa', 'su' => 'Su', 'info' => 'Info'
    );

    global $wpdb;

    if (! wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['hhomev_save_setting_nonce']) ), basename(__FILE__) ) ) return false;

    foreach ($setArr as $key => $value) { 

        if (array_key_exists($key, $setArr)) {  
            
            $wpdb->update("{$wpdb->base_prefix}holiday_home_occupation",  array( 'value' => sanitize_text_field($_POST[$key]) ), array('key' => sanitize_text_field($key) ) );
        }
    }

    echo '<p class="notice notice-success" style="margin-left:2px;padding-top:0.5rem;padding-bottom:0.5rem;font-size:1.25rem;">The settings have been updated.</p>';
    
}








