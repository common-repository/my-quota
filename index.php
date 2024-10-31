<?php
/*
  Plugin Name: My Quota
  Plugin URI: https://www.dropstr.com/my-quota
  Description: Create and manage quota's for your authors. Add/remove policies for meeting such quota's and view reports of who is writing what and when.
  Author: Dropstr Inc
  Version: 1.0.8
  Text Domain: myquota
  Author URI: https://www.dropstr.com
  Copyright: 2021, Dropstr Inc
  */

/*
 * Db Version Control
 */
global $myquota_db_version;

$myquota_db_version = '1.1';

//Quota
require_once( dirname( __FILE__ ) . '/inc/quota_class.php' );


/**
 * Notification once activated for Help and upgrades
 */
function myquota_plugin_notice()
{

    // Only show for admins
    if ( current_user_can( 'manage_options' ) )
    {
        $files = array("dashboard", "plugins", "edit-post");

        $currentScreen = get_current_screen();


        if( in_array($currentScreen->id, $files))
        {
            global $wpdb;

            $site_notifications = [];

            $get_notifications = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'myquota_notifications'));

            if(isset($get_notifications) && !empty($get_notifications))
            {
                foreach ($get_notifications as $notification)
                {

                    $site_notifications = json_decode($notification->option_value, true);

                }
            }


            if(isset($site_notifications))
            {
                include 'inc/notifications.php';
            }

        }


    }
}

add_action( 'admin_notices', 'myquota_plugin_notice' );


/*
 *
 * Quota Settings Page
 */
function myquota_dashboard_menu()
    {
        // Roster Page
        add_menu_page('My Quota - Roster View', 'My Quota', 'edit_posts', 'myquota', 'myquota', plugins_url( 'my-quota/images/icon.png' ));

        // My Quota Roster Groups List
        add_submenu_page('myquota', 'My Quota - Groups', 'Groups', 'manage_options', 'myquota-editor', 'myquota_editor');

        // My Quota Settings
        add_submenu_page('myquota', 'My Quota - Settings', 'Settings', 'manage_options', 'myquota-settings', 'myquota_settings');


    }

function myquota_add_plugin_link( $plugin_actions, $plugin_file ) {

    $new_actions = array();

    if ( in_array( $plugin_file, array(
        'my-quota/index.php'
    ) ) ) {

        $new_actions['myquota_premium'] = sprintf( __( '<b><a href="%s" target="myquota">Get Premium</a></b>', 'get-premium' ), esc_url( 'https://dropstr.com/pricing'  ) );

        $new_actions['myquota_help'] = sprintf( __( '<a href="%s" target="myquota-help">Help</a>', 'online-help' ), esc_url( 'https://dropstr.com/support/my-quota/'  ) );

        $new_actions['myquota_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'settings' ), esc_url( admin_url ('options-general.php?page=myquota-settings')  ) );


    }

    return array_merge( $new_actions, $plugin_actions );
}



/*
 *
 * My Quota Editor/Roster Page
 */
function myquota_editor()
    {

        if ( !current_user_can( 'manage_options' ) )
        {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        else
        {

            include 'editor.php';
        }

    }

/*
 *
 * Quota Settings Page
 */
function myquota_settings()
    {


        if ( !current_user_can( 'manage_options' ) )
        {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        else
            {
                // CSS for Gauge
                wp_register_style( 'cmGauge_css',    plugins_url( 'css/cmGauge.css',    __FILE__ ), false );
                wp_enqueue_style ( 'cmGauge_css' );

                include 'settings.php';
            }


    }

/**
 * My Quota Page
 */
    function myquota()
        {

            if ( !current_user_can( 'edit_posts' ) )
            {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }
            else
            {

                include 'roster.php';
            }
        }


/*
 * Database Install
 */
function myquota_db_install()
    {
        global $wpdb;
        global $myquota_db_version;
        $table_name = $wpdb->prefix . 'quotas';

        // Check if installed
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
            {


                $charset_collate = $wpdb->get_charset_collate();

                $sql = "CREATE TABLE $table_name (id varchar(32) NOT NULL,meta_key varchar(32) NOT NULL,meta_value longtext NULL,KEY (id,meta_key)) $charset_collate;";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );


                // Add db version
                add_option( 'myquota_db_version', $myquota_db_version );
            }

    }

// Activate cron for plugin
function myquota_cron_activation()
    {
        // create workflow pack quota checker hook for cron
        if (!wp_next_scheduled('myquota_check'))
                {
                    // Debug mode (reactivate for debug)
                    wp_schedule_event(time(), 'hourly', 'myquota_check');
                }

        // Remove notification cron
        if (wp_next_scheduled('myquota_notification'))
        {
            wp_clear_scheduled_hook('myquota_notification');

        }

        // Remove Records for older version
        $_myquota_db_version = get_option('myquota_db_version');

        if(isset($_myquota_db_version) && $_myquota_db_version == '1.0')
        {

            global $wpdb;
            global $myquota_db_version;

            $wpdb->query(
                'DELETE  FROM '.$wpdb->prefix.'quotas
               WHERE meta_key = "record"'
            );

            update_option( 'myquota_db_version', $myquota_db_version );

        }



    }


// Deactivate cron for plugin
function myquota_cron_deactivation()
    {
        // My Quota Check
        wp_clear_scheduled_hook('myquota_check');



    }



    // if set, remove cron
/* // Notifications
    wp_clear_scheduled_hook('myquota_notification');*/

/**
 * MyQuota Cronjob
 */
function myquota_cron()
    {

        //Include Core
        $quota = new my_quota_dropstr_quota_core();

        // Check all quotas
        $quota->check_quotas_cron();

    }



// Add User Profile Notification option
add_action( 'show_user_profile', 'myquota_user_profile_fields' );
add_action( 'edit_user_profile', 'myquota_user_profile_fields' );

function myquota_user_profile_fields( $user )
{
    // Check email validation
    $core = new my_quota_dropstr_quota_core();

    $api_data = $core->get_myquota_api();

    $email_hash = md5( $user->user_email . $api_data["api_key"]);

    // Check email validation
    $command =  array("check_email_validation" => array("email_token" => $email_hash) );
    $email_validation = $core->dropstr_api_call($command);


    // Verify user is sending validation email
    if( isset($_GET["email_status"]) && wp_verify_nonce($_GET['email_status'], 'validate'))
    {

        if(isset($_GET["email_validate"]))
        {
            // Send verification email
            $command =  array("send_email_validation" => array("email_address" => $user->user_email) );
            $email_validation_status = $core->dropstr_api_call($command);
        }
    }

    ?>
    <h3><?php _e("Quota Notifications", "blank"); ?></h3>

    <table class="form-table presentation">
        <tr>
            <th><label for="dropstr_email"><?php _e("Email Notification"); ?></label></th>
            <td>
                <label for="myquota_notifications">
                <input id="myquota_notifications" type="checkbox" name="myquota_notifications" value="1" <?php if(esc_attr( get_the_author_meta( 'myquota_email_notification', $user->ID ) == "1")){ echo "checked";} ?>>
                    <?php _e(" Disable email notifications."); ?></label>

            </td>
        </tr>
        <?php if(isset($email_validation) && !empty($email_validation))
                {
                    if(isset($email_validation["check_email_validation"]["valid"]) && $email_validation["check_email_validation"]["valid"] != "1")
                    {?>
        <tr>
            <th><label for="dropstr_email_validation">Email Verification</label></th>
            <td>
                <label for="myquota_validation">
                <?php


                        if(isset($_GET["email_validate"]))
                        {
                            echo "Email sent. Check your inbox.";
                        }
                        else
                            {
                                // Create validation link
                                $sendLink = wp_nonce_url('profile.php?email_validate=send', 'validate', 'email_status');

                                echo '<a href="'.$sendLink.'" class="button button-secondary">Verify Email</a><p class="description">Verify that you own this email address to receive emails from the My Quota plugin.</p>';
                            }


                ?>
                </label>
            </td>
        </tr>
        <?php } }?>
    </table>
<?php }

// Save Profile Notifications Option
add_action( 'personal_options_update', 'save_myquota_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_myquota_user_profile_fields' );

function save_myquota_user_profile_fields( $user_id ) {
    if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
        return;
    }

    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'myquota_email_notification', $_POST['myquota_notifications'] );

}

/*
 * Hooks
 *
 */
add_action('admin_menu', 'myquota_dashboard_menu');
// Add Cron Function
add_action('myquota_check', 'myquota_cron');

// For upgrades
add_action( 'upgrader_process_complete', 'myquota_cron_activation',10, 2);

add_filter( 'plugin_action_links', 'myquota_add_plugin_link', 10, 2 );

// Install DB if not created
register_activation_hook( __FILE__, 'myquota_db_install' );
// Activate Cronjob
register_activation_hook(__FILE__, 'myquota_cron_activation');
// Deactivate Cronjob
register_deactivation_hook(__FILE__, 'myquota_cron_deactivation');

