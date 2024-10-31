<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 3/3/21
 * Time: 12:57 PM
 */

// Updated versions
$notify_versions = array("0.1.7", "0.1.8", "1.0.0");

// Updates for Add-ons
if(in_array($site_notifications, $notify_versions))
{

    _e('<div class="notice notice-info myquota-close"><p><b>My Quota</b> - We\'ve updated the settings page, check out the settings page for changes <a href="admin.php?page=myquota-settings">Settings page</a> .</p></div>');

}
else
    {
        if(empty($site_notifications))
        {
            // First time user

            _e('<div class="notice notice-info myquota-close"><p>Welcome to My Quota, please visit the <a href="admin.php?page=myquota-settings">Settings page</a> to learn how to setup your quotas and get started.</p></div>');

        }

    }