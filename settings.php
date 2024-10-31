<?php
/*
 *
 * My Quota Settings
 *
 *
 *
 */


$quota = new my_quota_dropstr_quota_core();

// Get Notifications/Updates
$updates = $quota->get_version_updates();

// Notification Setting
$notification = '1.0.5';

// update notification
$quota->update_notification_setting($notification);

// Add-ons
$add_ons = new my_quota_add_ons();

// API Tokens
$api_tokens = $quota->get_myquota_api();

// Get Settings
$settings = $quota->get_myquota_settings();

// Get API Validation information
$validate_info = $quota->validate_api();

// cmGauge JS
echo '<script src="'.plugins_url( '/js/cmGauge.js', __FILE__ ).'"></script>';

?>
    <div class="wrap">
        <h1 class="wp-heading-inline">My Quota Settings</h1>
        <?php
        if(isset($_GET["tab"]) && !empty($_GET["tab"]))
        {
        ?>
        <a href="?page=myquota-settings" class="page-title-action">Back</a>
        <?php } ?>
        <hr class="wp-header-end">

        <form id="quota-groups" class="form-horizontal" method="GET" action="admin.php">

<?php
// Get Settings Page tab

if(isset($_GET["tab"]) && !empty($_GET["tab"]))
{

    if($_GET["tab"] == 'add-ons')
    {

        if(isset($_GET["deactivate"]))
        {
            // Deactivate Add-on
            if( wp_verify_nonce($_GET['deactivate'], 'deactivate_addon') && current_user_can('administrator'))
            {

                $add_on = sanitize_text_field($_GET["add-on"]);

                $core = new my_quota_dropstr_quota_core();

                // Deactivate Add-on
                $core->update_snap($add_on);

            }
        }

        if(isset($_GET["activate"]))
        {
            // Deactivate Add-on
            if( wp_verify_nonce($_GET['activate'], 'activate_addon') && current_user_can('administrator'))
            {

                $add_on = sanitize_text_field($_GET["add-on"]);

                $core = new my_quota_dropstr_quota_core();

                // Deactivate Add-on
                $core->update_snap($add_on);

            }
        }


        ?>
        <input type="hidden" name="page" value="myquota-settings">
        <input type="hidden" name="tab" value="add-ons">

        <?php print $add_ons->get_add_on_table(); ?>

<?php
    }

    // Notifications
    if($_GET["tab"] == 'notifications')
    {

        //Get Notifications
        $core = new my_quota_dropstr_quota_core();

        $commands = array("get_notifications_log");
        $notifications = $core->dropstr_api_call($commands);


        $myWindow = '<table class="wp-list-table widefat fixed striped">
  <thead>
    <tr>
      <th class="manage-column" scope="col">Status</th>
      <th class="manage-column" scope="col">Email</th>
      <th class="manage-column" scope="col">Subject</th>
      <th class="manage-column" scope="col">Time</th>
    </tr>
  </thead>
  <tbody>';


        if(isset($notifications["get_notifications_log"]) && !empty($notifications["get_notifications_log"]))
        {

            foreach ($notifications["get_notifications_log"] as $notification)
            {

                if($notification["sent"] == "1")
                {
                    $notification_status_icon = '<span class="dashicons dashicons-marker"></span> In Queue for Submission';
                    $notification_status = 'active';
                }

                elseif($notification["sent"] == "2")
                {
                    $notification_status_icon = '<span class="dashicons dashicons-yes-alt"></span> Notification Sent';
                    $notification_status = 'success';
                }

                elseif ($notification["sent"] == "5")
                {
                    $notification_status_icon = '<span class="dashicons dashicons-dismiss"></span> You have exceeded your monthly quota, email held. ';
                    $notification_status = 'warning';
                }
                else
                {
                    $notification_status_icon = '<span class="dashicons dashicons-warning" style="color: red;"></span> An error has occurred.';
                    $notification_status = 'info';
                }


                $myWindow .= '<tr class="table-'.$notification_status.'">
      <th scope="row">'.$notification_status_icon.'</th>
      <td>'.$notification["email"]["email_address"].'
</td>
      <td>'.$notification["email"]["subject"].'</td>
      <td>'.$notification["time"].'</td>
    </tr>';
            }

        }
        else{

            // No notifications log
            $myWindow .= '<tr><td colspan="4"> There are no notifications logs found.</td></tr>';
        }

        $myWindow .= '      
  </tbody>
</table>';

        print $myWindow;

    }

    // Terms of Service
    if($_GET["tab"] == 'tos')
    {
        include 'tos.php';
    }




}
else {

// Main Setting Page
    ?>
            <input type="hidden" name="page" value="myquota-settings">

            <div class="meta-box-sortables" style="margin: 0 8px 20px;">

                <div class="postbox-container">

                    <div class="postbox">
                        <div class="postbox-header"><h3><span class="dashicons dashicons-admin-comments"></span> Notifications</h3></div>
                        <div class="inside" style="width: 650px; height: 350px; overflow-y: scroll;">
                            <p><?php print $updates["updates"]["my-quota"]; ?></p>
                             </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h3><span class="dashicons dashicons-cloud"></span> Dropstr API
                                Settings</h3></div>
                        <div class="inside">
                            <table>
                                <thead>
                                <th></th>
                                <th></th>
                                <tbody>
                                <tr>
                                    <td align="right"><b>API Key:</b></td>
                                    <td><input type="text" name="api_key"
                                               value="<?php if (isset($api_tokens) && !empty($api_tokens)) echo $api_tokens["api_key"]; ?>" <?php if (isset($api_tokens["activated"])) echo 'disabled="disabled"'; ?>>
                                    </td>
                                </tr>
                                <tr>
                                    <td><b>Authentication Token:</b></td>
                                    <td><input type="text" name="auth_token"
                                               value="<?php if (isset($api_tokens) && !empty($api_tokens)) echo $api_tokens["auth_token"]; ?>" <?php if (isset($api_tokens["activated"])) echo 'disabled="disabled"'; ?>>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right"><b>Secret Token:</b></td>
                                    <td><input type="text" name="sec_token"
                                               value="<?php if (isset($api_tokens) && !empty($api_tokens)) echo $api_tokens["sec_token"]; ?>" <?php if (isset($api_tokens["activated"])) echo 'disabled="disabled"'; ?>>
                                    </td>
                                </tr>
                                </tbody>
                                </thead></table>
                            <p align="center"><?php

                                if(isset($validate_info) && !empty($validate_info))
                                {
                                    // Get API Plan
                                    $sent_emails = $validate_info["notifications"]/$validate_info["notification_limit"];
                                    $sent_emails = $sent_emails*100;

                                    if($validate_info["notifications"] == 0)
                                    {
                                        $sent_emails = 0;
                                    }

                                    echo '<div align="left"><div style="float:left;padding: 5px;"><div id="gaugeDemo" class="gauge gauge-big gauge-green" ><div class="gauge-arrow" data-percentage="'.round($sent_emails).'" style="transform: rotate(0deg);height:117px;"></div></div></div><h2>API Service Details</h2><div>Plan: <b>'.$validate_info["plan"].'</b><br>Monthly Notification Limit: <b>'.$validate_info["notification_limit"].'</b><br>Notifications Sent: <b>'.$validate_info["notifications"].'</b>';
                                    // Records
                                    if(isset($validate_info["plan"]) && $validate_info["plan"] == 'Free')
                                        echo '<br>Quota Record Limit: <b>5</b> per person.';
                                    else
                                        echo '<br>Quota Record Limit: <b>Unlimited</b> per person.';
                                    echo '</div></div><script>jQuery("#gaugeDemo .gauge-arrow").cmGauge();
        </script></p></div>';

                                }


                                if (isset($validate_info["plan"]) && $validate_info["plan"] == "Free")
                                {
                                ?><div style="margin-left: 10px;"><hr><p>Want unlimited records and notifications?<br/>
                            <p><b>Start your 14 day trial, no payment required.</b> - <a
                                        href="https://dropstr.com/pricing">See details</a>
                            </p><?php } ?></p></div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h3><span class="dashicons dashicons-block-default"></span> Add-ons
                            </h3></div>
                        <div class="inside">Check out all the add-ons for My Quota<br><br> <a
                                    href="?page=myquota-settings&tab=add-ons" class="button button-primary button-hero">Add-ons</a>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h3><span class="dashicons dashicons-database"></span> Notifications Log
                            </h3></div>
                        <div class="inside">See all notifications sent and any errors.<br><br> <a
                                    href="?page=myquota-settings&tab=notifications" class="button button-primary button-hero">Log</a>
                        </div>
                    </div>

                </div>
            </div>

            <div class="meta-box-sortables">
                <div class="postbox-container" style="margin: 0 8px 20px;">

                    <div class="postbox">
                        <div class="postbox-header"><h3><span class="dashicons dashicons-admin-comments"></span> Get
                                Dropstr's Suite Service</h3></div>
                        <div class="inside"><h3>Get These Additional Benefits</h3>
                            <ul>
                                <li><b>Unlimited Records</b> - Keep unlimited amount of quota records.</li>
                                <li><b>Increase Email Notifications</b> - Send instant notifications using our email services.
                                </li>
                                <li><b>Dashboard</b> - Connect all your sites together to view at one location.</li>
                                <li><b>Add-ons</b> - Get additional mods for My Quota.</li>
                            </ul>
                            <b>Start a 14 day trial, no payment required.</b> <br/><br/><a
                                    href="https://dropstr.com/pricing" target="_blank"> See Pricing</a></p></div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h3><span class="dashicons dashicons-welcome-learn-more"></span>
                                Tutorials / Information</h3></div>
                        <div class="inside"><h3>Getting Started</h3>
                            <ol>
                                <li><a href="admin.php?page=myquota-settings&tab=add-ons">Enable Add-ons</a></li>
                                <li><a href="admin.php?page=myquota-editor">Create Quota Group(s)</a></li>
                                <li><a href="admin.php?page=myquota">View Quota Roster</a></li>
                            </ol>
                            <p>
                            <h3>Documentation</h3>
                            <ol>
                                <li><a href="https://dropstr.com/support/my-quota" target="_blank"> Online
                                        Support</a></li>
                                <?php //<li><a href="?page=myquota-settings&tab=tos">GDPR Compliance and ToS</a></li> ?></ol></p></div>

                    </div>

                </div>
            </div>

    <?php
}
?>
        </form>

    </div>
