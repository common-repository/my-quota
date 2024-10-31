<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 10/19/20
 * Time: 10:47 AM
 */

// Main Class for Quota Checks
class my_quota_dropstr_quota_core extends my_quota_dropstr_quota_list
{

    /**
     * Dropstr API Version
     * @return string
     */
    private function get_dropstr_api_version()
    {
        return '1';
    }


    private function dropstr_create_tokens()
    {
        // Get API Version
        $version = $this->get_dropstr_api_version();

        $blog_url = get_home_url();

        $users = get_users();

        $user_count = count($users);

        // Generate API token
        $token["api_key"] = md5($blog_url.$user_count.time());

        $token["auth_token"] = md5($token["api_key"].time());

        // Request token and activation
        $myUrl = 'https://api.dropstr.com/v' . $version . '/?api_key=' . $token["api_key"] . '&auth_token=' . $token["auth_token"] . '&c=site_registration';

        $response = wp_remote_get($myUrl, array( 'sslverify' => false ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        }
        else
            {
                $responseData = json_decode(wp_remote_retrieve_body($response), true);

                // Failsafe, return error code
                $response_code = wp_remote_retrieve_response_code($response);


                if ($response_code == 200)
                {
                    $odCard = $responseData;
                    $odCard["status_code"] = $response_code;
                }
                else
                {
                    $odCard["status_code"] = $response_code;
                }

                // Add Debugging info
                $odCard["debug"] = $myUrl;
            }


        if(isset($odCard["site_registration"]) && !empty($odCard["site_registration"]))
        {

            $token["sec_token"] = $odCard["site_registration"]["secret"];

            if(isset($token["sec_token"]) && !empty($token["sec_token"]))
            {
                $saved = $this->save_myquota_api_settings($token);
            }
        }
        // Add tokens to site
       return $token;

    }

    /**
     * Send Dropstr API
     * @param $commands
     * @return mixed|string
     */
    function dropstr_api_call($commands, $data = [])
    {
        // Get API Version
        $version = $this->get_dropstr_api_version();

        // Get API Tokens
        $tokens = $this->get_myquota_api();

        // Site validation
        $data['site_validation'] = array('site' => get_bloginfo( 'name' ), 'url' => get_bloginfo( 'url' ));

        // Blank Data
        $odCard = [];

        // Check if tokens exist, if not, create
        if(isset($tokens) && empty($tokens))
        {

            // Create API tokens
            $tokens = $this->dropstr_create_tokens();
        }

            $url = NUll;
            // Parse Commands
            foreach ($commands as $commandKey => $command)
            {

                if (is_array($command))
                {

                    $url .= '&c[]=' . $commandKey;

                    foreach ($command as $key => $value) {

                        // if just value (no key)
                        if ($key == "string") {

                            $url .= $value;

                        } else {

                            $url .= '&' . $key . '=' . $value;
                        }

                    }

                }
                else
                    {
                        // if not an array, attach the string to url
                        $url .= '&c[]=' . $command;
                    }
            }

            $myUrl = 'https://api.dropstr.com/v' . $version . '/?' . $url . '&api_key=' . $tokens["api_key"] . '&auth_token=' . $tokens["auth_token"] . '';

            // SEND API (Want to allow SSL true but need a toggle)
            //$myCard = wp_remote_get($myUrl, array( 'sslverify' => false ));

            $args = array(
                'method' => 'POST',
                'headers' => "Authorization: Bearer $tokens[sec_token]\r\n" .
                    "Content-Type: application/json\r\n",
                'httpversion' => '1.0',
                'redirection' => 5,
                'timeout' => 60,
                'blocking' => true,
                'body' => json_encode($data)
            );

            $response = wp_remote_post($myUrl, $args);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            }
            else
                {
                    $responseData = json_decode(wp_remote_retrieve_body($response), true);

                    // Failsafe, return error code
                    $response_code = wp_remote_retrieve_response_code($response);


                    if ($response_code == 200)
                    {
                        $odCard = $responseData;
                        $odCard["status_code"] = $response_code;
                    }
                    else
                        {
                            $odCard["status_code"] = $response_code;
                        }

                    // Add Debugging info
                    $odCard["debug"] = $myUrl;
                }



        return $odCard;

    }

    function get_version_updates()
    {
        $message = "Service offline";

        // Get Plugin Version
        $version = '1.0.5';

        // Get update Messages for current version
        $myUrl = 'https://api.dropstr.com/?ver='.$version.'';

        // SEND API (Want to allow SSL true but need a toggle)
        //$myCard = wp_remote_get($myUrl, array( 'sslverify' => false ));

        $args = array(
            'method' => 'POST',
            'headers' => "Content-Type: application/json\r\n",
            'httpversion' => '1.0',
            'redirection' => 5,
            'timeout' => 60,
            'blocking' => true
        );

        $response = wp_remote_post($myUrl, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $message = "Something went wrong: $error_message";
        }
        else
            {
                $responceData = json_decode(wp_remote_retrieve_body($response), true);

                // Failsafe, return error code
                $response_code = wp_remote_retrieve_response_code($response);


                if ($response_code == 200)
                {
                    $odCard = $responceData;
                    $odCard["status_code"] = $response_code;
                }
                else
                {
                    $odCard["status_code"] = $response_code;
                }

                // Add Debugging info
                $odCard["debug"] = $myUrl;
            }

        if(isset($odCard) && !empty($odCard))
        {
            // Check incoming API data
            $message = esc_html($odCard["updates"]["my-quota"]);

            // heading
            $message = str_replace("[h]", "<h3>", $message);
            $message = str_replace("[/h]", "</h3>", $message);

            // Title
            $message = str_replace("[b]", "<b>", $message);
            $message = str_replace("[/b]", "</b>", $message);

            // Paragraph
            $message = str_replace("[p]", "<p>", $message);
            $message = str_replace("[/p]", "</p>", $message);

            // Break
            $message = str_replace("[hr]", "<hr>", $message);

            $odCard["updates"]["my-quota"] = $message;

        }


        return $odCard;

    }

    /**
     * Check API Validation
     * @return bool
     */
    function is_api_validated()
    {

        $validate = false;

        $api_tokens = $this->get_myquota_api();


        if(isset($api_tokens) && !empty($api_tokens))
        {

            if(isset($api_tokens["activated"]) && !empty($api_tokens["activated"]))
            {
                $validate = true;
            }
        }


        return $validate;
    }

     function check_snap($plugin_name, $type)
    {
        global $wpdb;
        $plugins = [];

        // Get saved add-ons

        $get_snaps = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = 0 AND meta_key=%s", 'snaps'));

        if (isset($get_snaps) && !empty($get_snaps))
        {
            foreach ($get_snaps as $snap)
            {

                $plugins = json_decode($snap->meta_value, true);

            }

        }

        // Flag for update/insert DB
        if(isset($plugins) && empty($plugins))
            $new = true;
        else
            $new = false;

            // Check Type

            // Check Plugin Status Only
            if (isset($type) && $type == "check")
                {

                    // Validate plugin
                    if (array_key_exists($plugin_name, $plugins))
                        {

                            if ($plugins[$plugin_name] == 1)
                                return true;
                            else
                                return false;
                        }
                        else
                            return false;

                }

            // Update Plugin Status
            if (isset($type) && $type == "update")
            {

                // Get Plugin(s)

                    // Edit Flow
                    if ($plugin_name == "edit-flow")
                    {
                        // Validate Plugin
                        if (is_plugin_active('edit-flow/edit_flow.php'))
                        {

                            if(isset($plugins[$plugin_name]) && $plugins[$plugin_name] == "1")
                                $plugins[$plugin_name] = "0";
                            else
                                $plugins[$plugin_name] = "1";

                        }
                        else
                            $plugins[$plugin_name] = "0";
                    }

                    // Yoast
                    if ($plugin_name == "wordpress-seo")
                    {
                        // Validate Plugin
                        if (is_plugin_active('wordpress-seo/wp-seo.php'))
                        {
                            if(isset($plugins[$plugin_name]) && $plugins[$plugin_name] == "1")
                                $plugins[$plugin_name] = "0";
                            else
                                $plugins[$plugin_name] = "1";
                        }
                        else
                            $plugins[$plugin_name] = "0";

                    }

                    //PublishPress
                    if ($plugin_name == "publishpress")
                    {
                        // Validate Plugin
                        if (is_plugin_active('publishpress/publishpress.php'))
                        {
                            if(isset($plugins[$plugin_name]) && $plugins[$plugin_name] == "1")
                                $plugins[$plugin_name] = "0";
                            else
                                $plugins[$plugin_name] = "1";
                        }
                        else
                            $plugins[$plugin_name] = "0";

                    }

                // JSON
                $updated_snaps = json_encode($plugins);


                // Update DB
                if($new)
                {

                    // first time
                    $wpdb->insert(
                        $wpdb->prefix . 'quotas',
                        array(
                            'id' => '0',
                            'meta_key' => 'snaps',
                            'meta_value' => $updated_snaps
                        ),
                        array(
                            '%s',
                            '%s',
                            '%s'
                        )
                    );
                }
                else
                    {
                        //Update
                        $wpdb->update(
                            $wpdb->prefix . 'quotas',
                            array(
                                'meta_value' => $updated_snaps
                            ),
                            array('meta_key' => 'snaps', 'id' => '0'),
                            array(
                                '%s'
                            ),
                            array('%s')
                        );
                    }
            }


    }

    /**
     * Get all enabled/set Add-ons
     * @return array|mixed
     */
    function get_snaps()
    {
        global $wpdb;
        $plugins = [];

        // Get saved add-ons

        $get_snaps = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = 0 AND meta_key=%s", 'snaps'));

        if (isset($get_snaps) && !empty($get_snaps))
        {
            foreach ($get_snaps as $snap)
            {

                $plugins = json_decode($snap->meta_value, true);

            }

        }

        return $plugins;

    }

    /**
     * Check if Plugin is active
     * @param $plugin_name
     *
     */
    function is_snap_active($plugin_name)
    {
       $is_active = false;

        // Check value
        $is_active = $this->check_snap($plugin_name, "check");


        return $is_active;
    }

    /**
     * Update Addon Status
     * @param $plugin_name
     */
    function update_snap($plugin_name)
    {

            $this->check_snap($plugin_name, "update");

    }

    /**
     * Hook before Quota Check is fired
     * @param $conditions
     *
     */
    function check_quota_conditions($post_data)
    {

        // Check which add-ons are enabled and ones used for conditions
        $snaps = $this->get_snaps();

        // Yoast SEO
        if(isset($snaps['wordpress-seo']) && !empty($snaps["wordpress-seo"]))
        {
            $yoast = new my_quota_dropstr_wordpress_seo_plugin();

            $yoast_score = $yoast->check_post_SEO_score();

        }


        return $post_data;
    }



    function get_user_ids($quota_users)
    {
        $user_id = [];
        $_quota_users = [];

        // Get Edit Flow Groups

        if(isset($quota_users) && !empty($quota_users))
        {

            // Convert to Array
            if(!is_array($quota_users))
            {
                $_quota_users[] = $quota_users;
            }
            else
                $_quota_users = $quota_users;

                foreach ($_quota_users as $user)
                {
                    $flag = false;

                    // if int, user id
                    if(is_numeric($user))
                    {
                        $user_id[] = $user;
                        $flag = true;
                    }

                    else
                        {
                            // If user is an edit flow group
                            if ( $this->is_snap_active('edit-flow') && $flag == false )
                            {

                                $ef_plugin = new my_quota_dropstr_edit_flow_plugin();

                                // get edit flow groups
                                $ef_groups = $ef_plugin->get_edit_flow_groups();

                                if (isset($ef_groups) && !empty($ef_groups)) {

                                    foreach ($ef_groups as $group) {

                                        $ef_group_names = 'ef-' . $group["name"];

                                        if ($ef_group_names == $user) {
                                            if (isset($group["meta_data"]["users"])) {
                                                foreach ($group["meta_data"]["users"] as $ef_user) {
                                                    $user_id[] = $ef_user;
                                                }

                                            }

                                            $flag = true;
                                        }
                                    }

                                }
                            }

                            // If user is Publish Press Group
                            if ( $this->is_snap_active('publishpress') && $flag == false )
                            {

                                $pp_plugin = new my_quota_dropstr_publishpress_plugin();

                                // get edit flow groups
                                $pp_groups = $pp_plugin->get_publishpress_groups();

                                if (isset($pp_groups) && !empty($pp_groups)) {

                                    foreach ($pp_groups as $group) {

                                        $pp_group_names = 'pp-' . $group["name"];

                                        if ($pp_group_names == $user) {
                                            if (isset($group["meta_data"]["users"])) {
                                                foreach ($group["meta_data"]["users"] as $pp_user) {
                                                    $user_id[] = $pp_user;
                                                }

                                            }

                                            $flag = true;
                                        }
                                    }

                                }
                            }

                            if($flag == false)
                            {
                                // Get all users by WP Role
                                $args = array(
                                    'role'    => $user
                                );
                                $wp_users = get_users( $args );


                                if(isset($wp_users) && !empty($wp_users))
                                {

                                    foreach ($wp_users as $wp_user)
                                    {

                                        $user_id[] = $wp_user->ID;
                                    }

                                }

                            }



                        }

                }


        }

        return $user_id;
    }

    /**
     * Format Email for HTML
     */
    function format_email($email)
    {

        // New Paragraph
        $email = str_replace('[p]', '<p>', $email);

        $email = str_replace('[ep]', '</p>', $email);

        // Bold
        $email = str_replace('[b]','<b>', $email);

        $email = str_replace('[eb]', '</b>', $email);

        // Italic

        $email = str_replace('[i]', '<i>', $email);

        $email = str_replace('[ei]', '</i>', $email);

        // Break

        $email = str_replace('[br]', '<br />', $email);

        return $email;

    }

    /***
     * Add Notification to Queue
     * @param $email
     * @param $subject
     * @param $message
     */

    private function set_notification_list($email, $subject, $message)
    {
        global $wpdb;
        $notifications = [];

        $get_notifications = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = 0 AND meta_key=%s", 'notifications'));
        // Create cron process for email
        if(isset($get_notifications) && !empty($get_notifications))
        {

            foreach ($get_notifications as $notification)
            {
                $notifications = json_decode($notification->meta_value, true);
            }

            // Update list
            $notifications[] = array("email" => $email, "subject" => $subject, "message" => $message);

            $updated_notifications = json_encode($notifications);

            $wpdb->update(
                $wpdb->prefix . 'quotas',
                array(
                    'meta_value' => $updated_notifications
                ),
                array('meta_key' => 'notifications', 'id' => '0'),
                array(
                    '%s'
                ),
                array('%s')
            );

        }
        else
            {

                // more at later date

                $notifications[] = array("email" => $email, "subject" => $subject, "message" => $message);

                $updated_notifications = json_encode($notifications);

                // first time
                $wpdb->insert(
                    $wpdb->prefix . 'quotas',
                    array(
                        'id' => '0',
                        'meta_key' => 'notifications',
                        'meta_value' => $updated_notifications
                    ),
                    array(
                        '%s',
                        '%s',
                        '%s'
                    )
                );

            }

    }


    /**
     * Send Notification Service
     */
    function send_notification($type, $message, $users)
    {

        // Get Site Information
        $bloginfo_name = get_bloginfo('name');
        // Site url
        $bloginfo_url = get_bloginfo('url');


        // Check if user has notifications disabled
        if(null !== esc_attr( get_the_author_meta( 'myquota_email_notification', $users )) &&  esc_attr( get_the_author_meta( 'myquota_email_notification', $users ) == "1"))
        {
            // Send nothing, user disabled message
        }
        else
            {

                // If message type is reminder
                if(isset($type) && $type == "reminder")
                {

                    $subject = "Reminder, You Have a Quota Requirement Due Soon!";

                    if(isset($users) && !empty($users))
                    {

                        $user_id = $users;

                        // Get User data
                        $user = get_user_by( 'id', $user_id );

                        // Get editor email
                        $user_email = $user->user_email;

                        // First Name
                        $user_name = $user->data->display_name;

                        $email_message = "[p]".$user_name.", you have the following quota requirements due in 24 hours:";

                        // Loop messages
                        if(isset($message) && is_array($message))
                        {

                            foreach ($message as $message_data)
                            {

                                if($message_data["quota_type"] == "or")
                                {
                                    $email_message .= "[br] -  (One of) ".$message_data["count"]." ".$message_data["type"]." with ".$message_data["status"]." status";
                                }
                                else
                                    $email_message .= "[br] - ".$message_data["count"]." ".$message_data["type"]." with ".$message_data["status"]." status";

                            }

                        }

                        $email_message .= "[ep][i]This email was sent from ".$bloginfo_name."[ei], ".$bloginfo_url."";

                            //API Call
                            $email = array("user_id" => $user_id, "email_address" => $user_email, "subject" => $subject, "message" => $email_message);
                            //API Call
                            $commands = array("send_user_notification" => array("type" => "email"));
                            $this->dropstr_api_call($commands, $email);



                    }

                }

                // Send Reports for Editors
                if(isset($type) && $type == "report")
                {

                    // Set subject report
                    $subject = "Quota Report";

                    if(isset($users) && !empty($users))
                    {

                        foreach ($users as $editor_id)
                        {

                            //$editor = get_userdata($editor_id);
                            $editor = get_user_by( 'id', $editor_id );

                            // Get editor email
                            $editor_email = $editor->user_email;

                            // First Name
                            $editor_name = $editor->data->display_name;

                            $email_message = "[p]".$editor_name.", the following user(s) Quota Report: [br]";


                            // Messages come in like $user_quota["met"]["req"] = 0;
                            //
                            // $user_quota["posts"]["req"][] = ''.$item["type"].' '.$item["status"].' - '.$item["count"].'/'.$item["amount"];

                            if(isset($message) && !empty($message))
                            {

                                foreach ($message as $user_id => $message_data)
                                {

                                    // Get users name
                                    //$user = get_userdata($user_id);
                                    $user = get_user_by( 'id', $user_id );

                                    $nice_name = $user->data->display_name;

                                    $email_message .= "[br][br][b]".$nice_name."[eb]";

                                    //Posts
                                    if(isset($message_data["posts"]["required"]))
                                    {

                                        foreach ($message_data["posts"]["required"] as $post_key => $posts)
                                        {
                                            if(is_array($posts))
                                            {
                                                if($post_key != "post_ids")
                                                {
                                                    //Remove post ids
                                                    foreach ($posts as $post_data)
                                                    {
                                                        //Remove post ids
                                                        if(!is_array($post_data))
                                                            $email_message .= "[br]".$post_data."";
                                                    }
                                                }

                                            }
                                            else
                                                $email_message .= "[br]".$posts."";

                                        }

                                    }

                                    if(isset($message_data["posts"]["or"]))
                                    {

                                        foreach ($message_data["posts"]["or"] as $post_key => $posts)
                                        {
                                            if(is_array($posts))
                                            {
                                                if($post_key != "post_ids")
                                                {
                                                    foreach ($posts as $post_data)
                                                    {
                                                        //Remove post ids
                                                        if(!is_array($post_data))
                                                            $email_message .= "[br]".$post_data."";
                                                    }
                                                }

                                            }
                                            else
                                                $email_message .= "[br]".$posts."";
                                        }

                                    }


                                }

                            }

                            $email_message .= "[ep][i]This email was sent from ".$bloginfo_name."[ei], ".$bloginfo_url."";

                                //API Call
                                $email = array("user_id" => $editor_id, "email_address" => $editor_email, "subject" => $subject, "message" => $email_message);
                                //API Call
                                $commands = array("send_user_notification" => array("type" => "email"));
                                $this->dropstr_api_call($commands, $email);



                        }


                    }

                }

            }



    }


    /**
     * Get WP Time/Day
     * @return array
     */
    private function get_today()
    {

        // Use the site's UTC time , 1 = enabled
        $today["day"] = current_time( 'Y-m-d', 0 );

        $today["full_day"] = current_time('Y-m-d H:i:s', 0);

        // Get Current Year
        $today["year"] = current_time('Y', 0);

        // Get Current Month
        $today["month"] = current_time('m', 0);

        // Day of the week
        $today["dow"] = current_time('D', 0);


        $today["time"] = current_time('H:i', 0);

        $today["full_time"] = current_time('H:i:s', 0);

        return $today;

    }

    /**
     * Get Days of the Week
     * @return array
     */
    private function get_days_of_week($input)
    {
        $dows = array('mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', ' sun' => 'Sunday');

        if(isset($input) && !empty($input))
        {
            foreach ($dows as $day => $dow)
            {

                if($input == $day)
                {
                    return $dow;
                }

            }
        }


    }

    private function get_quota_posts($option, $args)
    {
        $user_posts = [];


        //Get Option
        if(isset($option) && !empty($option))
        {


            if(isset($args["posts"]) && !empty($args["posts"]))
            {

                // Return Args
                $user_posts["args"] = $args;

                if(is_array($args["posts"]))
                {

                    foreach ($args["posts"] as $post)
                    {
                        // Add post ids (if any)
                        $post_ids = [];

                        $query_args = array(

                            'author'    => $args["author"],                        // author id
                            'post_status' => $post["status"],             // status required
                            'post_type'   => $post["type"],               // post type
                            'date_query' => array(
                                array(
                                    'column'    => 'post_modified',         // Date last modified
                                    'after'     => $args['due_date']['after'].' '.$args["hour"].':'.$args["minute"].':00', // After last's quota
                                    'before'    => $args['due_date']['before'].' '.$args["hour"].':'.$args["minute"].':00'   // This due date
                                    ),

                                    'inclusive' => true,       // And including those days up till time
                                )
                            );


                        //Get posts (if any)
                        $count_posts = new WP_Query( $query_args );

                        // Count posts in array
                        if(!empty($count_posts))
                            $count = $count_posts->found_posts;
                        else
                            $count = 0;


                        if(isset($count_posts) && !empty($count_posts))
                        {

                            // Get posts ids
                            if(isset($count_posts->posts) && !empty($count_posts->posts))
                            {
                                $returned_posts = (array) $count_posts->posts;

                                foreach ($returned_posts as $returned_post)
                                {

                                    $post_ids[] = $returned_post->ID;
                                }

                            }
                        }


                        // Set temp array with returned data
                        $_required[] = array("type" => $post["type"], "status" => $post["status"], "post_ids" => $post_ids, "amount" => $post["amount"], "count" => $count);

                        // reset array
                        unset($post_ids, $count);
                    }

                    // Set returned required/or posts with data
                    $user_posts["posts"][$option] = $_required;
                }


            }


        }


        return $user_posts;

    }

    /**
     * Get Quota Conditions from the user's quota and group policy quota
     * @param $quotas
     * @param $quota_group
     */
    private function get_quota_conditions($quotas, $quota_group)
    {
            // Get Add-ons
            $snaps = $this->get_snaps();

            // Yoast SEO enabled
            if(isset($snaps["wordpress-seo"]) && !empty($snaps["wordpress-seo"]))
            {
                // Get groups conditions for Yoast SEO
                $wordpress_seo = new my_quota_dropstr_wordpress_seo_plugin();

            }

            // Get the posts array (not args)
            if(isset($quotas["posts"]) && !empty($quotas["posts"]))
            {

                // The next array is "or" or "required" quota types

                // Loop type
                foreach ($quotas["posts"] as $type => $quota_type)
                {

                    // Loop quota types
                    if(isset($quota_type) && !empty($quota_type))
                    {

                        // Get Quota type (or/required)
                        foreach ($quota_type as $key => $item)
                        {
                            $i = 0;
                            // Get each quota type
                            foreach ($quota_type as $quota)
                            {

                                // Quota has posts
                                if(isset($quota["count"], $quota["post_ids"]) && !empty($quota["post_ids"]))
                                {

                                    // If plugin is set
                                    if(isset($quota_group["conditions"]["wordpress_seo"]) && !empty($quota_group["conditions"]["wordpress_seo"]))
                                    {


                                        // Post ids
                                        foreach ($quota["post_ids"] as $post_id)
                                        {

                                            $scores = $wordpress_seo->check_post_SEO_score($post_id);

                                            if(isset($scores) && !empty($scores))
                                            {

                                                // Keyword Score
                                                if(isset($quota_group["conditions"]["wordpress_seo"]["keyword_score"]) && $quota_group["conditions"]["wordpress_seo"]["keyword_score"] != '0')
                                                {
                                                    if($scores["keyword_score"] < $quota_group["conditions"]["wordpress_seo"]["keyword_score"])
                                                    {
                                                        // Unset post and count
                                                        //unset($quotas["posts"][$type][$i]["post_ids"][$post_id]);
                                                        if(isset($quotas["posts"][$type][$i]["post_ids"]) && !empty($quotas["posts"][$type][$i]["post_ids"]))
                                                        {

                                                            if (($post_key = array_search($post_id, $quotas["posts"][$type][$i]["post_ids"])) !== false) {
                                                                unset($quotas["posts"][$type][$i]["post_ids"][$post_key]);

                                                                // If not empty, reduce by 1
                                                                if(!empty($quotas["posts"][$type][$i]["count"]))
                                                                {
                                                                    $quotas["posts"][$type][$i]["count"] = $quota["count"]-1;
                                                                    // Reset Quota
                                                                    $quota["count"] = $quota["count"]-1;
                                                                }

                                                            }


                                                        }


                                                    }
                                                }

                                                // Readability Score
                                                if(isset($quota_group["conditions"]["wordpress_seo"]["readability_score"]) && $quota_group["conditions"]["wordpress_seo"]["readability_score"] != '0')
                                                {
                                                    if($scores["readability_score"] < $quota_group["conditions"]["wordpress_seo"]["readability_score"])
                                                    {
                                                        // Unset post and count

                                                        if(isset($quotas["posts"][$type][$i]["post_ids"]) && !empty($quotas["posts"][$type][$i]["post_ids"]))
                                                        {

                                                            if(($post_key = array_search($post_id, $quotas["posts"][$type][$i]["post_ids"])) !== false) {

                                                                unset($quotas["posts"][$type][$i]["post_ids"][$post_key]);

                                                                // if not empty, reduce by 1
                                                                if(!empty($quotas["posts"][$type][$i]["count"]))
                                                                {
                                                                    $quotas["posts"][$type][$i]["count"] = $quota["count"]-1;
                                                                    // Reset Quota
                                                                    $quota["count"] = $quota["count"]-1;
                                                                }


                                                            }


                                                        }



                                                    }
                                                }

                                            }

                                        }


                                    }

                                }

                                $i++;
                            }
                        }


                    }


                    // return modified quotas array
                }
            }


        return $quotas;
    }

    /**
     * Get Quota's Count
     * @param $quotas
     */
    private function get_quota_count($quotas, $type)
    {
        $html = '';
        $data = [];

        if(isset($quotas))
        {

            if(is_array($quotas))
            {

                foreach ($quotas as $quota_type => $quota)
                {


                    // quota is optional, one must meet requirement
                    if($quota_type == "or")
                    {
                        if(isset($type) && $type == 'html')
                            $html .= '<div class="or-quota">';

                        // Check if any are met
                        $or_quota = false;

                        foreach ($quota as $item)
                        {

                            if ($item["count"] >= $item["amount"])
                                $or_quota = true;

                        }

                        foreach ($quota as $item)
                        {
                            if(isset($type) && $type == 'html')
                                $html .=  '<p>';

                            if($or_quota == true)
                            {

                                // If post is the met one
                                if($item["count"] >= $item["amount"])
                                {

                                        $html .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';
                                }
                                else
                                    {
                                            $html .= '<span  class="dashicons dashicons-marker"></span>';
                                    }
                            }

                            else
                                {
                                        $html .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>';
                                }

                            if(isset($type) && $type == 'html')
                                $html .= ' '.$item["type"].' '.$item["status"].' - '.$item["count"].'/'.$item["amount"].'</p>';
                            else
                                $data[] = array("quota_type" => "or", "type" => $item["type"], "status" => $item["status"], "post_ids" =>$item["post_ids"], "count" => $item["count"], "amount" => $item["amount"]);


                        }

                        if(isset($type) && $type == 'html')
                            $html .= "</div>";
                    }

                    // If quota is required (both required and - maybe used)
                    if($quota_type == 'required')
                    {


                        foreach ($quota as $item)
                        {


                                if(isset($type) && $type == 'html')
                                {
                                    $html .=  '<p>';

                                    if($item["count"] >= $item["amount"])
                                        $html .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';
                                    else
                                        $html .= '<span style="color: red;" class="dashicons dashicons-dismiss"></span>';

                                    $html .= ' '.$item["type"].' '.$item["status"].' - '.$item["count"].'/'.$item["amount"].'</p>';
                                }
                                else
                                    $data[] = array("quota_type" => "required", "type" => $item["type"], "status" => $item["status"], "post_ids" =>$item["post_ids"], "count" => $item["count"], "amount" => $item["amount"]);

                        }

                    }

                }

            }
        }


        if(isset($type) && $type == 'html')
            return $html;
        else
            return $data;

    }

    /**
     * Check hour
     */
    private function check_hour($hour, $ampm)
    {

        // If set
        if(isset($hour,  $ampm))
        {

            // Check for PM and use Military time for hour
            if(isset($ampm) && !empty($ampm))
            {
                // If night, use military time
                if($ampm ==  'pm')
                {


                    if($hour != '12')
                        $hour = $hour+12;

                }

            }


        }

        return $hour;

    }


    /***
     * Get User ids by Quota Users field
     * @return array
     */
    function get_users_by_quota($users)
    {


        if(isset($users) && !empty($users))
        {

            // Get site roles
            $_roles = $this->get_site_roles();

            foreach ($_roles as $role)
            {

                $role_slugs[] = $role["slug"];
            }

            // Edit Flow Groups
            if ( $this->is_snap_active( 'edit-flow' ))
            {

                $ef_plugin = new my_quota_dropstr_edit_flow_plugin();

                // get edit flow groups
                $ef_groups = $ef_plugin->get_edit_flow_groups();

                if(isset($ef_groups) && !empty($ef_groups))
                {

                    foreach ($ef_groups as $group)
                    {

                        $ef_group_names[$group["id"]] = 'ef-'.$group["name"];
                    }
                }

            }

            // Publish Press Groups
            if ( $this->is_snap_active( 'publishpress' ))
            {

                $pp_plugin = new my_quota_dropstr_publishpress_plugin();

                // get edit flow groups
                $pp_groups = $pp_plugin->get_publishpress_groups();

                if(isset($pp_groups) && !empty($pp_groups))
                {

                    foreach ($pp_groups as $group)
                    {

                        $pp_group_names[$group["id"]] = 'pp-'.$group["name"];
                    }
                }

            }

            foreach ($users as $user)
            {

                // If not a user ID
                if(!is_numeric($user))
                {

                    // Check if Role
                    if(in_array($user, $role_slugs))
                    {
                        foreach ($role_slugs as $role_slug)
                        {
                            if($role_slug == $user)
                            {
                                // Get All users in WP Role
                                $args = array(
                                    'role'    => $role_slug,
                                    'orderby' => 'user_nicename',
                                    'order'   => 'ASC'
                                );
                                $wp_users = get_users( $args );

                                if(isset($wp_users) && !empty($wp_users))
                                {
                                    foreach ($wp_users as $wp_user)
                                    {
                                        $quota_users[] = $wp_user->ID;
                                    }
                                }

                            }
                        }

                    }


                     // If Edit Flow Enabled, Check if Group
                     if ( $this->is_snap_active('edit-flow'))
                     {

                         if (in_array($user, $ef_group_names)) {

                             foreach ($ef_group_names as $key => $ef_group_name)
                             {

                                 if($ef_group_name == $user)
                                 {

                                     // Get users for Group
                                     $_ef_users = $ef_groups[$key]["meta_data"]["user_ids"];

                                     foreach ($_ef_users as $ef_user)
                                     {
                                         $quota_users[] = $ef_user;

                                     }

                                 }
                             }




                         }
                     }

                     // if Publish Press Enabled
                    if ( $this->is_snap_active('publishpress'))
                    {

                        if (in_array($user, $pp_group_names)) {

                            foreach ($pp_group_names as $key => $pp_group_name)
                            {

                                if($pp_group_name == $user)
                                {

                                    // Get users for Group
                                    $_pp_users = $pp_groups[$key]["meta_data"]["user_ids"];

                                    foreach ($_pp_users as $pp_user)
                                    {
                                        $quota_users[] = $pp_user;

                                    }

                                }
                            }




                        }
                    }





                }
                else
                    {
                        $quota_users[] = $user;
                    }

            }


        }

        return $quota_users;

    }

    /**
     * Get All Group IDs Index
     * @return array
     */
    function get_group_ids()
    {
        global $wpdb;

        $group_ids = [];

        $get_groups = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s AND meta_key=%s", '0','groups'));

        if(isset($get_groups) && !empty($get_groups))
        {
            foreach ($get_groups as $group)
            {

                $group_ids = json_decode($group->meta_value, true);

            }
        }

        return $group_ids;

    }

    /**
     * Get Due Date by Quota Group
     * @return string
     */
    public function get_due_date($quota_groups, $args = NULL)
    {

        // Get Timezone for time match
        $time_zone = get_option('timezone_string');
        if(empty($time_zone))
        {
            $time_zone = 'UTC '.get_option('gmt_offset');
        }

        // Get today's full date
        $today = $this->get_today();

        date_default_timezone_set($time_zone);

        if(isset($quota_groups) && !empty($quota_groups))
        {

            foreach ($quota_groups as $quota_group)
            {

                if(isset($quota_group["timeframe"]))
                    {

                        // Get Arguments
                        if (isset($quota_group["timeframe_args"]) && !empty($quota_group["timeframe_args"]))
                            {

                                // Get WP date
                                $today_day = current_time('l', 0);

                                // Get Hour
                                $hour = $quota_group["time_args"]["hour"];

                                // Get Minute
                                $minute = $quota_group["time_args"]["minute"];
                                // Get am/pm
                                $ampm = $quota_group["time_args"]["ampm"];

                                $M_hour = $this->check_hour($hour, $ampm);

                                $due_time = $M_hour . ":" . $minute . ":00";

                                // Convert times
                                $_todays_time = new DateTime($today["full_time"], new DateTimeZone($time_zone));
                                $todays_time = $_todays_time->format('U');

                                $_due_time = new DateTime($due_time, new DateTimeZone($time_zone));
                                $due_time_stamp = $_due_time->format('U');

                                // if timeframe is a day of the week
                                if($quota_group["timeframe"] == 'day_week')
                                    {

                                        // Get days of the week
                                        $days = array("mon" => "Monday", "tue" => "Tuesday", "wed" => "Wednesday", "thu" => "Thursday", "fri" => "Friday", "sat" => "Saturday", "sun" => "Sunday");

                                        // Get Day
                                        $_day = $quota_group["timeframe_args"]["day_week"];

                                        foreach ($days as $key => $dow)
                                            {
                                                if ($key == $_day)
                                                    $day = $dow;

                                            }


                                        // If Checking Record, get Last X
                                        if(isset($args) && $args == 'record')
                                        {
                                            // If so, get this day until/or today
                                            $last_day_of_week = 'Last '.$day;

                                            $last_dow = date('Y-m-d', strtotime($last_day_of_week));

                                            $date = new DateTime('' . $last_dow . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                            $output = $date->format('Y-m-d');
                                        }
                                        else
                                            {
                                                // If today
                                                if ($day == $today_day)
                                                {

                                                    // Check time
                                                    if($todays_time > $due_time_stamp)
                                                    {
                                                        // If Args for Real Time
                                                        if (isset($args) && $args == "real")
                                                        {

                                                            // If so, get this day until/or today
                                                            $this_day_of_week = 'Next '.$day;

                                                            $this_dow = date('Y-m-d', strtotime($this_day_of_week));

                                                            $date = new DateTime('' . $this_dow . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                                            $output = $date->format('U');

                                                        }
                                                        else
                                                            $output = "Next " . $day;
                                                    }
                                                    else
                                                    {
                                                        // If Args for Real Time
                                                        if (isset($args) && $args == "real")
                                                        {

                                                            $date = new DateTime('' . $today["day"] . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                                            $output = $date->format('U');
                                                        }
                                                        else
                                                            $output = "Today";
                                                    }

                                                }
                                                else
                                                {
                                                    if (isset($args) && $args == "real")
                                                    {
                                                        // If so, get this day until/or today
                                                        $this_day_of_week = 'This '.$day;

                                                        $this_dow = date('Y-m-d', strtotime($this_day_of_week));

                                                        $date = new DateTime('' . $this_dow . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                                        $output = $date->format('U');
                                                    }
                                                    else
                                                        $output = $day;



                                                }

                                            }


                                            // output if no args
                                        if(isset($args) && empty($args))
                                            $output .= ' @ ' . $hour . ':' . $minute . ' ' . $ampm;

                                    }

                                // If timeframe is Day of Month
                                if($quota_group["timeframe"] == 'day_month')
                                    {

                                        // Get Day of Month
                                        $day = $quota_group["timeframe_args"]["day_month"];

                                        // Check if Due Date is int of string
                                        if($day == "end")
                                            {
                                                // Get last day of this month

                                                $date = new DateTime($today["day"], new DateTimeZone($time_zone));
                                                $timestamp = $date->format('U');

                                                $due_date =  date("Y-m-t", $timestamp);

                                            }
                                            else
                                                {

                                                    // Create Date Due
                                                    $due_date = $today["year"]."-".$today["month"]."-".$day;
                                                }

                                        // If Checking Record, get Last X
                                        if(isset($args) && $args == 'record')
                                        {
                                            // If so, get this day until/or today
                                            $last_day_of_week = 'Last '.$day;

                                            $last_dow = date('Y-m-d', strtotime($last_day_of_week));

                                            // Get Last Month
                                            $_last_month = $today["month"];

                                            if($_last_month == '01')
                                                $last_month = '12';
                                            else
                                            {
                                                $_last_month--;

                                                // Add 0 is below 10
                                                if($_last_month < 10)
                                                    $last_month = str_pad($_last_month,2,"0",STR_PAD_LEFT);
                                                else
                                                    $last_month = $_last_month;

                                            }

                                            $_due_date = explode('-', $due_date);
                                            $next_due = $_due_date[0].'-'.$last_month.'-'.$_due_date[2];

                                            $date = new DateTime('' . $next_due . ' ' . $due_time . '', new DateTimeZone($time_zone));
                                            $output = $date->format('Y-m-d');
                                        }
                                        else
                                            {

                                                // Get Next Month
                                                $_next_month = $today["month"];

                                                if($_next_month == '12')
                                                    $next_month = '01';
                                                else
                                                {
                                                    $_next_month++;

                                                    // Add 0 is below 10
                                                    if($_next_month < 10)
                                                        $next_month = str_pad($_next_month,2,"0",STR_PAD_LEFT);
                                                    else
                                                        $next_month = $_next_month;

                                                }

                                                // Find Month
                                                $_due_date = explode('-', $due_date);
                                                $next_due = $_due_date[0].'-'.$next_month.'-'.$_due_date[2];



                                                // If today
                                                if($due_date == $today["day"])
                                                {

                                                    // Check time
                                                    if($todays_time > $due_time_stamp)
                                                    {

                                                        $date = new DateTime($next_due, new DateTimeZone($time_zone));
                                                        $output = $date->format('U');


                                                    }
                                                    else
                                                    {
                                                        // If Args for Real Time
                                                        if(isset($args) && $args == "real")
                                                        {

                                                            $date = new DateTime(''.$today["day"].' '.$due_time.'', new DateTimeZone($time_zone));

                                                            $output = $date->format('U');
                                                        }
                                                        else
                                                            $output = "Today";
                                                    }

                                                }
                                                else
                                                {
                                                    // If Args for Real Time
                                                    if(isset($args) && $args == "real")
                                                    {

                                                        $date = new DateTime(''.$next_due.' '.$due_time.'', new DateTimeZone($time_zone));

                                                        $output = $date->format('U');
                                                    }
                                                    else
                                                        $output = $due_date;
                                                }

                                            }


                                        // output if no args
                                        if(isset($args) && empty($args))
                                            $output .= ' @ '.$hour.':'.$minute.' '.$ampm;

                                    }


                            }


                    }

            }

        }

        return $output;

    }

    /**
     * Check Quota By User ID
     *
     * @return array or string
     *
     */
    public function check_quota_user($user_id, $quota_groups, $type)
    {
        // Get Timezone for time match
        $time_zone = get_option('timezone_string');
        if(empty($time_zone))
        {
            $time_zone = 'UTC '.get_option('gmt_offset');
        }

        date_default_timezone_set($time_zone);

        // Get WP time/day
        $today = $this->get_today();

        $output = '';
        $output_array = [];

        if(isset($quota_groups) && !empty($quota_groups))
        {

            // Quota Groups

            foreach ($quota_groups as $quota_group)
            {

                // Get Quota Requirements
                if(isset($quota_group["quotas"]) && !empty($quota_group["quotas"]))
                {
                    if(is_array($quota_group["quotas"]))
                    {
                        foreach ($quota_group["quotas"] as $quota)
                        {

                            // No OR, Required
                            if($quota["require"] == "-")
                            {

                                $quota_required[] = array('type' => $quota["type"], 'status' => $quota["status"], 'amount' => $quota["amount"]  );

                            }

                            // If more than one requirement and using OR
                            if($quota["require"] == "or")
                            {
                                // Find the other OR's statements
                                $quota_or[] = array('type' => $quota["type"], 'status' => $quota["status"], 'amount' => $quota["amount"]  );

                            }


                        }


                    }

                }

                // Get Quota Due Date
                if(isset($quota_group["timeframe"]) && !empty($quota_group["timeframe"]))
                {

                    // get Type of due date

                    // If due every X day during the week
                    if($quota_group["timeframe"] == "day_week")
                    {

                        // Find last X day (up to the activate day)
                        if(isset($quota_group["start_date"]) && !empty($quota_group["start_date"]))
                        {

                            // If today is newer than the start date (date passed)
                            if($today["day"] < $quota_group["timeframe"])
                            {

                                // Is it over a week since the start date towards the due date?

                                // Check how long it's been
                                if(isset($quota_group["timeframe_args"]) && !empty($quota_group["timeframe_args"]))
                                {
                                    // Get Due Dow (mon-sun)
                                    $due_day = $quota_group["timeframe_args"]["day_week"];

                                    // Conversion, mon = Monday
                                    $day_of_week = $this->get_days_of_week($due_day);


                                    // Get the time due
                                    if(isset($quota_group["time_args"]) && !empty($quota_group["time_args"]))
                                    {

                                        $hour = $quota_group["time_args"]["hour"];
                                        $minute = $quota_group["time_args"]["minute"];
                                        $ampm = $quota_group["time_args"]["ampm"];


                                        // Check hour
                                        $hour = $this->check_hour($hour, $ampm);
                                    }

                                    // Due time
                                    $due_time = $hour.':'.$minute.':00';
                                    // Get WP date
                                    $today_day = current_time( 'l', 0 );

                                    // If today is due date and time expired
                                    if(($day_of_week == $today_day) && (strtotime($today["full_time"]) >= strtotime($due_time)))
                                    {

                                        // If so, get Last's day until today
                                        $lasts_day_of_week = 'This '.$day_of_week;

                                        // If so, get this day until/or today
                                        $this_day_of_week = 'Next '.$day_of_week;

                                        $last_dow = date('Y-m-d', strtotime($lasts_day_of_week));

                                        $this_dow = date('Y-m-d', strtotime($this_day_of_week));



                                    }
                                    else
                                        {
                                            // If so, get Last's day until today
                                            $lasts_day_of_week = 'Last '.$day_of_week;

                                            // If so, get this day until/or today
                                            $this_day_of_week = 'This '.$day_of_week;

                                            $last_dow = date('Y-m-d', strtotime($lasts_day_of_week));

                                            $this_dow = date('Y-m-d', strtotime($this_day_of_week));

                                        }




                                    // If Quota Amount is Required
                                    if(isset($quota_required) && !empty($quota_required))
                                    {
                                        // Reset if used
                                        if(isset($quota_count))
                                            unset($quota_count);

                                        if(isset($_quota_count))
                                            unset($_quota_count);

                                        // So in order to check the quota requirement, we need the following
                                        $args = array(
                                            'author'    => $user_id,
                                            'due_date' => array('after' => $last_dow, 'before' => $this_dow),
                                            'posts' => $quota_required,
                                            'hour'  => $hour,
                                            'minute' => $minute

                                        );

                                        // Get Quotas and amount/due
                                        $_quota_count = $this->get_quota_posts('required', $args);

                                        // Quota Conditions
                                        $quota_count = $this->get_quota_conditions($_quota_count, $quota_group);

                                        // Get the Quota count for roster
                                        if(isset($type) && $type == "html")
                                            $output .= $this->get_quota_count($quota_count["posts"], $type);
                                        else
                                            $output_array["required"] = $this->get_quota_count($quota_count["posts"], $type);
                                    }

                                    if(isset($quota_or) && !empty($quota_or))
                                    {
                                        // Reset if used
                                        if(isset($quota_count))
                                            unset($quota_count);

                                        if(isset($_quota_count))
                                            unset($_quota_count);

                                        // So in order to check the quota requirement, we need the following
                                        $args = array(
                                            'author'    => $user_id,
                                            'due_date' => array('after' => $last_dow, 'before' => $this_dow),
                                            'posts' => $quota_or,
                                            'hour'  => $hour,
                                            'minute' => $minute

                                        );

                                        $_quota_count = $this->get_quota_posts('or', $args);

                                        // Quota Conditions
                                        $quota_count = $this->get_quota_conditions($_quota_count, $quota_group);

                                        if(isset($type) && $type == "html")
                                            $output .= $this->get_quota_count($quota_count["posts"], $type);
                                        else
                                            $output_array["or"] = $this->get_quota_count($quota_count["posts"], $type);
                                    }

                                }
                                // Get Day of Week due date is set


                            }

                        }


                    }


                    // Get Day of Month
                    if($quota_group["timeframe"] == "day_month")
                    {

                        // Find last X day (up to the activate day)
                        if(isset($quota_group["start_date"]) && !empty($quota_group["start_date"]))
                        {

                            // If today is newer than the start date (date passed)
                            if($today["day"] < $quota_group["timeframe"])
                            {

                                // Is it over a week since the start date towards the due date?

                                // Check how long it's been
                                if(isset($quota_group["timeframe_args"]) && !empty($quota_group["timeframe_args"]))
                                {
                                    // Get Due Day (1-31) AND/OR LAST = LAst day of month
                                    $due_day = $quota_group["timeframe_args"]["day_month"];


                                    // Check if Due Date is int of string
                                    if($due_day == "end")
                                    {
                                        // Get last day of this month

                                        $due_date =  date("Y-m-t", strtotime($today["day"]));

                                    }
                                    else{

                                        // Create Date Due
                                        $due_date = $today["year"]."-".$today["month"]."-".$due_day;
                                        }



                                    // Get the time due
                                    if(isset($quota_group["time_args"]) && !empty($quota_group["time_args"]))
                                    {

                                        $hour = $quota_group["time_args"]["hour"];
                                        $minute = $quota_group["time_args"]["minute"];
                                        $ampm = $quota_group["time_args"]["ampm"];


                                        // Check hour
                                        $hour = $this->check_hour($hour, $ampm);
                                    }

                                    // Due time
                                    $due_time = $hour.':'.$minute.':00';


                                    // If today is due date and time expired
                                    if($today["day"] == $due_date && (strtotime($today["full_time"]) >= strtotime($due_time)))
                                    {

                                        // Get Next Month's due date
                                        $time = strtotime($due_date);

                                        // This Month
                                        $this_month = $due_date;

                                        // Next Month
                                        $next_month = date("Y-m-d", strtotime("+1 month", $time));

                                    }
                                    else
                                        {
                                            // Else last month to this due date
                                            $next_month = $due_date;

                                            $time = strtotime($due_date);
                                            $this_month = date("Y-m-d", strtotime("-1 month", $time));

                                        }


                                    // If Quota Amount is Required
                                    if(isset($quota_required) && !empty($quota_required))
                                    {
                                        // Reset if used
                                        if(isset($quota_count))
                                            unset($quota_count);

                                        if(isset($_quota_count))
                                            unset($_quota_count);

                                        // So in order to check the quota requirement, we need the following
                                        $args = array(
                                            'author'    => $user_id,
                                            'due_date' => array('after' => $this_month, 'before' => $next_month),
                                            'posts' => $quota_required,
                                            'hour'  => $hour,
                                            'minute' => $minute

                                        );

                                        // Get Quotas and amount/due
                                        $_quota_count = $this->get_quota_posts('required', $args);

                                        // Quota Conditions
                                        $quota_count = $this->get_quota_conditions($_quota_count, $quota_group);

                                        // Get the Quota count for roster
                                        if(isset($type) && $type == "html")
                                            $output = $this->get_quota_count($quota_count["posts"], $type);
                                        else
                                            $output_array["required"] = $this->get_quota_count($quota_count["posts"], $type);
                                    }

                                    if(isset($quota_or) && !empty($quota_or))
                                    {
                                        // Reset if used
                                        if(isset($quota_count))
                                            unset($quota_count);

                                        if(isset($_quota_count))
                                            unset($_quota_count);

                                        // So in order to check the quota requirement, we need the following
                                        $args = array(
                                            'author'    => $user_id,
                                            'due_date' => array('after' => $this_month, 'before' => $next_month),
                                            'posts' => $quota_or,
                                            'hour'  => $hour,
                                            'minute' => $minute

                                        );

                                        $_quota_count = $this->get_quota_posts('or', $args);

                                        // Quota Conditions
                                        $quota_count = $this->get_quota_conditions($_quota_count, $quota_group);

                                        if(isset($type) && $type == "html")
                                            $output = $this->get_quota_count($quota_count["posts"], $type);
                                        else
                                            $output_array["or"] = $this->get_quota_count($quota_count["posts"], $type);
                                    }

                                }
                                // Get Day of Week due date is set


                            }

                        }


                    }

                }


            }

        }


        if(isset($type) && $type == "html")
            return $output;
        else
            return $output_array;

    }


    /**
     * Get Quota Due Date
     * @param $quota_groups
     * @param null $args
     * @return mixed|string
     * @throws Exception
     */
    public function get_quota_due_date($quota_group, $args)
    {

        // Get Timezone for time match
        $time_zone = get_option('timezone_string');
        if(empty($time_zone))
        {
            $time_zone = 'UTC '.get_option('gmt_offset');
        }

        // Get today's full date
        $today = $this->get_today();

        date_default_timezone_set($time_zone);

        if(isset($quota_group) && !empty($quota_group))
        {

                if(isset($quota_group["timeframe"]))
                {

                    // Get Arguments
                    if (isset($quota_group["timeframe_args"]) && !empty($quota_group["timeframe_args"]))
                    {

                        // Get WP date
                        $today_day = current_time('l', 0);

                        // Get Hour
                        $hour = $quota_group["time_args"]["hour"];

                        // Get Minute
                        $minute = $quota_group["time_args"]["minute"];
                        // Get am/pm
                        $ampm = $quota_group["time_args"]["ampm"];

                        $M_hour = $this->check_hour($hour, $ampm);

                        $due_time = $M_hour . ":" . $minute . ":00";

                        // Convert times
                        $_todays_time = new DateTime($today["full_time"], new DateTimeZone($time_zone));
                        $todays_time = $_todays_time->format('U');

                        $_due_time = new DateTime($due_time, new DateTimeZone($time_zone));
                        $due_time_stamp = $_due_time->format('U');

                        // if timeframe is a day of the week
                        if($quota_group["timeframe"] == 'day_week')
                        {

                            // Get days of the week
                            $days = array("mon" => "Monday", "tue" => "Tuesday", "wed" => "Wednesday", "thu" => "Thursday", "fri" => "Friday", "sat" => "Saturday", "sun" => "Sunday");

                            // Get Day
                            $_day = $quota_group["timeframe_args"]["day_week"];

                            foreach ($days as $key => $dow)
                            {
                                if ($key == $_day)
                                    $day = $dow;

                            }


                            // If Checking Record, get Last X
                            if(isset($args) && $args == 'record')
                            {
                                // If so, get this day until/or today
                                $last_day_of_week = 'Last '.$day;

                                $last_dow = date('Y-m-d', strtotime($last_day_of_week));

                                $date = new DateTime('' . $last_dow . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                $output = $date->format('Y-m-d');
                            }
                            else
                            {
                                // If today
                                if ($day == $today_day)
                                {

                                    // Check time
                                    if($todays_time > $due_time_stamp)
                                    {
                                        // If Args for Real Time
                                        if (isset($args) && $args == "real")
                                        {

                                            // If so, get this day until/or today
                                            $this_day_of_week = 'Next '.$day;

                                            $this_dow = date('Y-m-d', strtotime($this_day_of_week));

                                            $date = new DateTime('' . $this_dow . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                            $output = $date->format('U');

                                        }
                                    }
                                    else
                                    {
                                        // If Args for Real Time
                                        if (isset($args) && $args == "real")
                                        {

                                            $date = new DateTime('' . $today["day"] . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                            $output = $date->format('U');
                                        }

                                    }

                                }
                                else
                                {
                                    if (isset($args) && $args == "real")
                                    {
                                        // If so, get this day until/or today
                                        $this_day_of_week = 'This '.$day;

                                        $this_dow = date('Y-m-d', strtotime($this_day_of_week));

                                        $date = new DateTime('' . $this_dow . ' ' . $due_time . '', new DateTimeZone($time_zone));

                                        $output = $date->format('U');
                                    }


                                }

                            }


                        }

                        // If timeframe is Day of Month
                        if($quota_group["timeframe"] == 'day_month')
                        {

                            // Get Day of Month
                            $day = $quota_group["timeframe_args"]["day_month"];

                            // Check if Due Date is int of string
                            if($day == "end")
                            {
                                // Get last day of this month

                                $date = new DateTime($today["day"], new DateTimeZone($time_zone));
                                $timestamp = $date->format('U');

                                $due_date =  date("Y-m-t", $timestamp);

                            }
                            else
                            {

                                // Create Date Due
                                $due_date = $today["year"]."-".$today["month"]."-".$day;
                            }

                            // If Checking Record, get Last X
                            if(isset($args) && $args == 'record')
                            {
                                // If so, get this day until/or today
                                $last_day_of_week = 'Last '.$day;

                                $last_dow = date('Y-m-d', strtotime($last_day_of_week));

                                // Get Last Month
                                $_last_month = $today["month"];

                                if($_last_month == '01')
                                    $last_month = '12';
                                else
                                {
                                    $_last_month--;

                                    // Add 0 is below 10
                                    if($_last_month < 10)
                                        $last_month = str_pad($_last_month,2,"0",STR_PAD_LEFT);
                                    else
                                        $last_month = $_last_month;

                                }

                                $_due_date = explode('-', $due_date);
                                $next_due = $_due_date[0].'-'.$last_month.'-'.$_due_date[2];

                                $date = new DateTime('' . $next_due . ' ' . $due_time . '', new DateTimeZone($time_zone));
                                $output = $date->format('Y-m-d');
                            }
                            else
                            {

                                // Get Next Month
                                $_next_month = $today["month"];

                                if($_next_month == '12')
                                    $next_month = '01';
                                else
                                {
                                    $_next_month++;

                                    // Add 0 is below 10
                                    if($_next_month < 10)
                                        $next_month = str_pad($_next_month,2,"0",STR_PAD_LEFT);
                                    else
                                        $next_month = $_next_month;

                                }

                                // Find Month
                                $_due_date = explode('-', $due_date);
                                $next_due = $_due_date[0].'-'.$next_month.'-'.$_due_date[2];



                                // If today
                                if($due_date == $today["day"])
                                {

                                    // Check time
                                    if($todays_time > $due_time_stamp)
                                    {

                                        $date = new DateTime($next_due, new DateTimeZone($time_zone));
                                        $output = $date->format('U');


                                    }
                                    else
                                    {
                                        // If Args for Real Time
                                        if(isset($args) && $args == "real")
                                        {

                                            $date = new DateTime(''.$today["day"].' '.$due_time.'', new DateTimeZone($time_zone));

                                            $output = $date->format('U');
                                        }

                                    }

                                }
                                else
                                {
                                    // If Args for Real Time
                                    if(isset($args) && $args == "real")
                                    {

                                        $date = new DateTime(''.$next_due.' '.$due_time.'', new DateTimeZone($time_zone));

                                        $output = $date->format('U');
                                    }

                                }

                            }


                        }


                    }


                }



        }

        return $output;

    }

    /**
     * Check Records for Reminders and return timestamps of records
     * @param $quota
     * @throws Exception
     */
    function check_quota_reminder($quota)
    {
        $time_stamps = [];

        // Get Timezone for time match
        $time_zone = get_option('timezone_string');
        if(empty($time_zone))
        {
            $time_zone = 'UTC '.get_option('gmt_offset');
        }

        // Today's date
        $_datetime_today = new DateTime('today', new DateTimeZone($time_zone));

        // Get Quota id
        if(isset($quota) && !empty($quota))
        {
            // Quota ID
            $quota_id = $quota["id"];


        }

            $records = $this->get_record_index($quota_id);


            foreach ($records as $record)
            {

                // if Record is not checked
                if(isset($record["checked"]) && $record["checked"] == 0)
                {

                    // If the post hasn't sent notification already
                    if(isset($record["notifications"]) && in_array("reminder", $record["notifications"]))
                    {
                    }
                    else
                    {

                        //Convert key to timestamp
                        // Check if less than or due tomorrow
                        $_datetime_tomorrow = DateTime::createFromFormat( 'U', $record["time_stamp"] );

                        $diff = $_datetime_today->diff($_datetime_tomorrow);

                        $hours = $diff->h;
                        $hours = $hours + ($diff->days*24);

                        // If time is 24 or less hours from due date
                        if($hours <= 24)
                        {

                            // Add timestamp to notify list
                            $time_stamps[] = $record["time_stamp"];

                        }

                    }

                }
            }


        return $time_stamps;
    }


    /**
     * Get Record Index
     * @param $quota_id
     * @return array|mixed
     */
    function get_record_index($quota_id)
    {
        global $wpdb;
        $record_ids = '';

        // Get Records
        $get_record = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s AND meta_key=%s", $quota_id,'record'));

        if(isset($get_record) && !empty($get_record))
        {
            foreach ($get_record as $record)
            {

                $record_ids = json_decode($record->meta_value, true);

            }

        }

        return $record_ids;
    }


    /**
     * Add to Record Index
     * @param $quota_id
     * @param $time_stamp
     */
    function add_record_index($quota_id, $time_stamp)
    {

        global $wpdb;

        // Get records
        $records = $this->get_record_index($quota_id);

        $record_flag = false;


        if(isset($records) && !empty($records))
        {

            foreach ($records as $record)
            {

                if( isset($record["time_stamp"]) && $record["time_stamp"] == $time_stamp)
                {

                    // Do nothing, already exists
                    $record_flag = true;

                }

            }



            // If record is not found, add it
            if($record_flag == false && !empty($time_stamp))
            {
                // Add to records
                $records[] = array('time_stamp' => $time_stamp, 'checked' => 0, 'quota_id' => $quota_id, 'notifications' => array());
                //array_push($args, $record_ids);


                $updated_record = json_encode($records);

                // Update records
                $wpdb->update(
                    $wpdb->prefix.'quotas',
                    array(
                        'meta_value' => $updated_record
                    ),
                    array( 'meta_key' => 'record', 'id' => $quota_id ),
                    array(
                        '%s'
                    ),
                    array( '%s' )
                );

            }
        }
        else
            {

                // Add timestamp
                $args[] = array(

                    'time_stamp' => $time_stamp,
                    'checked' => 0,
                    'quota_id' => $quota_id,
                    'notifications' => array()

                );

                $updated_record = json_encode($args);

                // Insert
                $wpdb->insert(
                    $wpdb->prefix.'quotas',
                    array(
                        'id'  => $quota_id,
                        'meta_key' => 'record',
                        'meta_value' => $updated_record
                    ),
                    array(
                        '%s',
                        '%s',
                        '%s'
                    )
                );

            }


    }


    /**
     * Update Record Index
     * @param $quota_id
     * @param $timestamp
     */
    function update_record_index($quota_id, $timestamp)
    {
        global $wpdb;
        $update = false;

        $records = $this->get_record_index($quota_id);

        if(isset($records) && !empty($records))
        {

            if(isset($timestamp) && !empty($timestamp))
            {

                foreach ($records as $record)
                {

                    // Check if timestamp matches
                    if(isset($record["time_stamp"]) && $record["time_stamp"] ==  $timestamp)
                    {

                        // Check if record is checked
                        if(isset($record["checked"]) && $record["checked"] == 0)
                        {
                            $record["checked"] = 1;

                        }

                    }

                    $_record[] = $record;
                }


                $updated_record = json_encode($_record);

                $wpdb->update(
                    $wpdb->prefix.'quotas',
                    array(
                        'meta_value' => $updated_record
                    ),
                    array( 'meta_key' => 'record', 'id' => $quota_id ),
                    array(
                        '%s'
                    ),
                    array( '%s' )
                );

                $update = true;

            }

        }

        return $update;

    }

    /**
     * Update Record Notification Status
     * @param $time_stamp
     * @param $quota_id
     * @param $type
     */
    function update_record_index_notification($time_stamp, $quota_id, $type)
    {
        global $wpdb;

        $records = $this->get_record_index($quota_id);

        if(isset($records) && !empty($records))
        {

            // Json decode the records
            foreach ($records as $record)
            {

                if($record["time_stamp"] == $time_stamp)
                {

                    // Update Record Notification
                    $record["notifications"][] = $type;

                }

                // Updated Records
                $_record[] = $record;

            }


            $updated_record = json_encode($_record);

            $wpdb->update(
                $wpdb->prefix.'quotas',
                array(
                    'meta_value' => $updated_record
                ),
                array( 'meta_key' => 'record', 'id' => $quota_id ),
                array(
                    '%s'
                ),
                array( '%s' )
            );

        }

    }

    /**
     * Check if notification type exists
     * @param $time_stamp
     * @param $quota_id
     * @param $type
     */
    function check_record_index_notification($time_stamp, $quota_id, $type)
    {

        $return = "not_set";

        $records = $this->get_record_index($quota_id);

        if(isset($time_stamp) && !empty($time_stamp))
        {

            foreach ($records as $record)
            {
                if(in_array($type, $record["notifications"]) )
                {
                    $return = "set";
                }
            }


        }
        else
            $return = 'error';



        return $return;
    }

    /**
     * Purge the Index Record set by Settings
     */
    function purge_record_index()
    {
        global $wpdb;

        // Get Groups
        $groups = $this->get_quota_groups();

        if(isset($groups) && !empty($groups))
        {

            if(is_array($groups))
            {

                foreach ($groups as $group)
                {

                    // Get group ID
                    $group_id = $group["id"];

                    $get_record = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s AND meta_key=%s", $group_id,'record'));

                    if(isset($get_record) && !empty($get_record))
                    {
                        foreach ($get_record as $record)
                        {

                            $record_ids = json_decode($record->meta_value, true);

                        }

                        // Count Records
                        $record_count = count($record_ids);

                                if($record_count > 5)
                                {
                                    // Shift array with keys
                                    $first_key = array_key_first($record_ids);

                                    if(isset($first_key) && !empty($first_key))
                                        unset($record_ids[$first_key]);


                                    $updated_record = json_encode($record_ids);

                                        $wpdb->update(
                                            $wpdb->prefix.'quotas',
                                            array(
                                                'meta_value' => $updated_record
                                            ),
                                            array( 'meta_key' => 'record', 'id' => $group_id ),
                                            array(
                                                '%s'
                                            ),
                                            array( '%s' )
                                        );

                                }

                    }
                }
            }
        }
        // Check Group Record

        // If exist, count the returned array

        // Prune array if needed

    }

    /**
     * Save Users's Record
     * @param $user_id
     * @param $record_data
     */
    function save_user_record($user_id, $record_data)
    {
        // Get API Data

    $commands = array("add_user_data" => array("user_id" => $user_id, "type" => "record"));
    $api_return = $this->dropstr_api_call($commands, $record_data);

    }

    /**
     * Purge User Record
     */
    function purge_user_record()
    {

        global $wpdb;

        // Get All Group IDs
        $group_ids = $this->get_group_ids();

        //Convert to string
        if(isset($group_ids) && !empty($group_ids))
        {
            $group_string = implode(" , ", $group_ids);

            // Get All user records
            $get_records = $wpdb->get_results($wpdb->prepare("SELECT id, meta_value FROM {$wpdb->prefix}quotas WHERE id NOT IN  (  %s  ) AND meta_key = %s", $group_string, 'record'));

            if(isset($get_records) && !empty($get_records))
            {
                foreach ($get_records as $record)
                {

                    // Get User Records
                    $user_record = json_decode($record->meta_value, true);

                    $record_count = count($user_record);


                        if($record_count > 1)
                        {
                            // Shift Array
                            array_shift($user_record);

                            $updated_record = json_encode($user_record);

                            $wpdb->update(
                                $wpdb->prefix.'quotas',
                                array(
                                    'meta_value' => $updated_record
                                ),
                                array( 'meta_key' => 'record', 'id' => $record->id ),
                                array(
                                    '%s'
                                ),
                                array( '%s' )
                            );


                        }


                }
            }

        }



    }

    /**
     * Process Quota to Record
     */
    function process_record($quota, $due_stamp)
    {
        // Set return data
        $editor_report = [];

        if(isset($quota) && !empty($quota))
        {


            // Get Quota Requirements
            if(isset($quota["quotas"]) && !empty($quota["quotas"]))
            {
                if(is_array($quota["quotas"]))
                {
                    foreach ($quota["quotas"] as $_quota)
                    {

                        // No OR, Required
                        if($_quota["require"] == "-")
                        {

                            $quota_required[] = array('type' => $_quota["type"], 'status' => $_quota["status"], 'amount' => $_quota["amount"]  );

                        }

                        // If more than one requirement and using OR
                        if($_quota["require"] == "or")
                        {
                            // Find the other OR's statements
                            $quota_or[] = array('type' => $_quota["type"], 'status' => $_quota["status"], 'amount' => $_quota["amount"]  );

                        }


                    }


                }

            }

            // Convert timestamp into date
            if(isset($due_stamp) && !empty($due_stamp))
                $due_date = date('Y-m-d H:i:s', $due_stamp);

            // Split Date
            if(isset($due_date) && !empty($due_date))
            {
                $_due_date = explode(' ', $due_date);

                // Set Date
                $due_date = $_due_date[0];

                // Set Time
                $due_time = $_due_date[1];

                if(isset($due_time) && !empty($due_time))
                {
                    $_due_time = explode(':', $due_time);

                    // Get Hour
                    $due_date_time["hour"] = $_due_time[0];
                    // Get Minute
                    $due_date_time["minute"] = $_due_time[1];
                }
            }
            else
                $due_date = '';

                // From Date
                $start_date = $this->get_quota_due_date($quota, 'record');

                // Get Quota User Ids
                $quota_users = $this->get_users_by_quota($quota["users"]);

                // Get Quota Amount/Requirement per user
            if(isset($quota_users) && !empty($quota_users))
            {

                foreach ($quota_users as $user_id)
                {

                    // If Quota Amount is Required
                    if(isset($quota_required) && !empty($quota_required))
                        {
                            // So in order to check the quota requirement, we need the following
                            $args = array(
                                'author'    => $user_id,
                                'due_date' => array('after' => $start_date, 'before' => $due_date),
                                'posts' => $quota_required,
                                'hour'  => $due_date_time["hour"],
                                'minute' => $due_date_time["minute"]

                            );

                            // Get Quotas and amount/due
                            $_quota_count = $this->get_quota_posts('required', $args);

                        }

                    if(isset($quota_or) && !empty($quota_or))
                        {
                            // So in order to check the quota requirement, we need the following
                            $args = array(
                                'author'    => $user_id,
                                'due_date' => array('after' => $start_date, 'before' => $due_date),
                                'posts' => $quota_or,
                                'hour'  => $due_date_time["hour"],
                                'minute' => $due_date_time["minute"]

                            );

                            if(isset($_quota_count))
                                $_quota_count += $this->get_quota_posts('or', $args);
                            else
                                $_quota_count = $this->get_quota_posts('or', $args);

                        }

                    // returned user quota if add-ons
                    if(isset($_quota_count) && !empty($_quota_count))
                        $quota_count[] = $this->get_quota_conditions($_quota_count, $quota);

                    // Create User Quota
                    $user_quota = [];

                    // Add Dates
                    $user_quota["start_date"] = $start_date;
                    $user_quota["due_date"] = $due_date;

                    // Get Users Quota Count
                    if(isset($quota_count) && !empty($quota_count))
                    {

                        foreach ($quota_count as $quota_item)
                        {

                            foreach ($quota_item["posts"] as $quota_type => $quota_set)
                            {


                                // quota is optional, one must meet requirement
                                if($quota_type == "or")
                                {

                                    // Check if any are met
                                    $or_quota = false;

                                    foreach ($quota_set as $item)
                                    {

                                        if ($item["count"] >= $item["amount"])
                                            $or_quota = true;

                                    }

                                    foreach ($quota_set as $item)
                                    {

                                        if($or_quota == true)
                                        {

                                            // Quota Met
                                            $user_quota["met"]["or"] = 1;

                                        }
                                        else
                                        {
                                            $user_quota["met"]["or"] = 0;
                                        }

                                        $user_quota["posts"]["or"][] = ''.$item["type"].' '.$item["status"].' - '.$item["count"].'/'.$item["amount"];

                                        // Add post ids (if any)
                                        $user_quota["posts"]["or"]["post_ids"] = $item["post_ids"];
                                    }
                                }

                                // If quota is required
                                if($quota_type == 'required')
                                {

                                    foreach ($quota_set as $item)
                                    {

                                        if($item["count"] >= $item["amount"])
                                            $user_quota["met"]["required"] = 1;
                                        else
                                            $user_quota["met"]["required"] = 0;

                                        $user_quota["posts"]["required"][] = ''.$item["type"].' '.$item["status"].' - '.$item["count"].'/'.$item["amount"];

                                        // Add post ids (if any)
                                        $user_quota["posts"]["required"]["post_ids"] = $item["post_ids"];

                                    }



                                }

                            }

                        }



                    }


                    // Save User Record
                    $this->save_user_record($user_id, $user_quota);

                    // Notify User Report if enabled
                    $editor_report[$user_id] = $user_quota;

                    // reset per user
                    unset($quota_count, $_quota_count, $user_quota);
                }

            }


        }

        return $editor_report;
    }

    /**
     * Get MyQuota Settings
     */
    function get_myquota_settings()
        {

            $site_settings = [];

            // Get Settings from DB
            global $wpdb;

            $get_settings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'myquota_settings'));

            if(isset($get_settings) && !empty($get_settings))
                {
                    foreach ($get_settings as $setting)
                        {

                            $site_settings = json_decode($setting->option_value, true);

                        }
                }
                else
                    {

                        // Set Default Records
                        $site_settings["records"] = '25';
                    }

            return $site_settings;
        }

    /**
     * Save MyQuota Settings
     */
    function save_myquota_settings($settings)
        {

            global $wpdb;

            $get_settings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'myquota_settings'));

            if(isset($get_settings) && !empty($get_settings))
                {
                    foreach ($get_settings as $setting)
                        {

                            $site_settings = json_decode($setting->option_value, true);

                        }
                }

            if(isset($site_settings) && !empty($site_settings) && isset($settings) && !empty($settings))
                {

                    $updated_settings = json_encode($settings);

                    // Update settings
                    $wpdb->update(
                        $wpdb->prefix.'options',
                        array(
                            'option_value' => $updated_settings
                        ),
                        array( 'option_name' => 'myquota_settings'),
                        array(
                            '%s'
                        ),
                        array( '%s' )
                    );
                }
                else
                    {

                        if(isset($settings) && !empty($settings))
                            {

                                $updated_settings = json_encode($settings);

                                $wpdb->insert(
                                    $wpdb->prefix.'options',
                                    array(
                                        'option_name' => 'myquota_settings',
                                        'option_value' => $updated_settings
                                    ),
                                    array(
                                        '%s',
                                        '%s'
                                    )
                                );
                            }
                    }

        }

    /**
     * Save and Activate API Tokens
     * @param $settings
     */
    function save_myquota_api_settings($settings)
    {

        global $wpdb;

        $validate = false;


        $get_settings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'dropstr_api'));

        if(isset($get_settings) && !empty($get_settings))
        {
            foreach ($get_settings as $setting)
            {

                $site_settings = json_decode($setting->option_value, true);

            }
        }

        if(isset($site_settings) && isset($settings) && !empty($settings))
        {

            $updated_settings = json_encode($settings);

            // Update settings
            $wpdb->update(
                $wpdb->prefix.'options',
                array(
                    'option_value' => $updated_settings
                ),
                array( 'option_name' => 'dropstr_api'),
                array(
                    '%s'
                ),
                array( '%s' )
            );
        }
        else
            {

                if(isset($settings) && !empty($settings))
                {

                    $updated_settings = json_encode($settings);

                    $wpdb->insert(
                        $wpdb->prefix.'options',
                        array(
                            'option_name' => 'dropstr_api',
                            'option_value' => $updated_settings
                        ),
                        array(
                            '%s',
                            '%s'
                        )
                    );
                }
            }

        // Validate Tokens
        $command = array("validate");
        $validate_api = $this->dropstr_api_call($command);

        if(isset($validate_api) && !empty($validate_api))
        {

            if(isset($validate_api["validate"]))
            {
                if($validate_api["validate"]["validate"] == 'true')
                {
                    $validate = true;
                    $this->activate_api();
                }
            }



        }

        return $validate;

    }


    /**
     * Validate API Key and Return API Information
     * @return array
     */
    function validate_api()
    {

        $validate = [];

        // Validate Tokens
        $command = array("validate");
        $validate_api = $this->dropstr_api_call($command);

        if(isset($validate_api) && !empty($validate_api))
        {

            if(isset($validate_api["validate"]))
            {
                if($validate_api["validate"]["validate"] == 'true')
                {


                    // Get Information
                    $validate = $validate_api["validate"]["data"];

                }
            }


        }

        return $validate;
    }


    /**
     * Activate API
     */
    function activate_api()
    {

        global $wpdb;

        $get_settings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'dropstr_api'));

        if(isset($get_settings) && !empty($get_settings))
        {
            foreach ($get_settings as $setting)
            {

                $site_settings = json_decode($setting->option_value, true);

            }
        }

        if(isset($site_settings) && !empty($site_settings))
        {

            // Add Activation Settins
            $site_settings["activated"] = '1';

            $updated_settings = json_encode($site_settings);

            // Update settings
            $wpdb->update(
                $wpdb->prefix.'options',
                array(
                    'option_value' => $updated_settings
                ),
                array( 'option_name' => 'dropstr_api'),
                array(
                    '%s'
                ),
                array( '%s' )
            );

        }

    }

    /**
     * Remove Activation
     */
    function remove_api_activation()
    {
        global $wpdb;

        $blank = [];

        $updated_settings = json_encode($blank);
        //Remove Tokens
        $wpdb->update(
            $wpdb->prefix.'options',
            array(
                'option_value' => $updated_settings
            ),
            array( 'option_name' => 'dropstr_api'),
            array(
                '%s'
            ),
            array( '%s' )
        );

    }


    /**
     * Get API Tokens
     * @return array|mixed
     */
    function get_myquota_api()
    {

        $site_settings = [];

        // Get Settings from DB
        global $wpdb;

        $get_settings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'dropstr_api'));

        if(isset($get_settings) && !empty($get_settings))
        {
            foreach ($get_settings as $setting)
            {

                $site_settings = json_decode($setting->option_value, true);

            }
        }

        if(empty($site_settings))
            $site_settings = $this->dropstr_create_tokens();

        return $site_settings;
    }


    /**
     * Update Notification Alerts for Settings Page
     * @param $version
     */
        function update_notification_setting($version)
        {

            global $wpdb;

            $get_settings = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s", 'myquota_notifications'));

            if(isset($get_settings) && !empty($get_settings))
            {
                foreach ($get_settings as $setting)
                {

                    $site_settings = json_decode($setting->option_value, true);

                }
            }

            if(isset($site_settings) && !empty($site_settings) && isset($version) && !empty($version))
            {

                $updated_settings = json_encode($version);

                // Update settings
                $wpdb->update(
                    $wpdb->prefix.'options',
                    array(
                        'option_value' => $updated_settings
                    ),
                    array( 'option_name' => 'myquota_notifications'),
                    array(
                        '%s'
                    ),
                    array( '%s' )
                );
            }
            else
            {

                if(isset($version) && !empty($version))
                {

                    $updated_settings = json_encode($version);

                    $wpdb->insert(
                        $wpdb->prefix.'options',
                        array(
                            'option_name' => 'myquota_notifications',
                            'option_value' => $updated_settings
                        ),
                        array(
                            '%s',
                            '%s'
                        )
                    );
                }
            }

        }

    /**
     * Check Quota via Cron
     */
    function check_quotas_cron()
        {

            // Get Today's date
            $today = $this->get_today();

            // Notifications
            $notification = [];

            // Get Timezone for time match
            $time_zone = get_option('timezone_string');
            if(empty($time_zone))
                {
                    $time_zone = 'UTC '.get_option('gmt_offset');
                }

            // Convert full day to unix time
            $date = new DateTime($today["full_day"], new DateTimeZone($time_zone));
            $day_stamp = $date->format('U');

            // Get All Quotas
            $quotas = $this->get_quota_groups();

            if(isset($quotas) && !empty($quotas))
                {

                    // If date is today
                    // Each Quota Group
                    foreach ($quotas as $quota)
                        {
                            $this_quota[] = $quota;

                            // Get Date Due for Quota (timestamp)
                            $due_date = $this->get_quota_due_date($quota, 'real');

                            // Get timestamp less than 24 hours with due date in 24 hours
                            $time_stamps = $this->check_quota_reminder($quota);

                            // foreach record, check user for the reminder
                            if(isset($time_stamps) && !empty($time_stamps))
                            {
                                foreach ($time_stamps as $time_stamp)
                                {
                                    // Get users from group
                                    $user_ids = $this->get_users_by_quota($quota["users"]);

                                    if(isset($user_ids) && !empty($user_ids))
                                    {
                                        foreach ($user_ids as $user_id)
                                        {
                                            //Check user for quota, return post data
                                            $post_data_array = $this->check_quota_user($user_id, $this_quota, 'data');


                                            if(isset($post_data_array) && !empty($post_data_array))
                                            {

                                                foreach ($post_data_array as $post_data)
                                                {

                                                    if(isset($post_data) && !empty($post_data))
                                                    {

                                                        // Set flag
                                                        $or_flag = false;

                                                        foreach ($post_data as $post_item)
                                                        {

                                                            if(isset($post_item["quota_type"]) && !empty($post_item["quota_type"]))
                                                            {

                                                                // If quota type is or and amount met
                                                                if($post_item["quota_type"] == "or" && (intval($post_item["count"]) >= intval($post_item["amount"])))
                                                                    $or_flag = true;


                                                                if($post_item["quota_type"] == "required" && (intval($post_item["count"]) < intval($post_item["amount"])))
                                                                    $notification[] =  array("quota_type" => $post_item["quota_type"], "type" => $post_item["type"], "status" => $post_item["status"], "count" => $post_item["count"], "amount" => $post_item["amount"]);
                                                            }


                                                        }


                                                        //Check Or flag, notify for missing post counts
                                                        if($or_flag == false)
                                                        {

                                                            foreach ($post_data as $post_item)
                                                            {

                                                                if(isset($post_item["quota_type"]) && !empty($post_item["quota_type"]))
                                                                {

                                                                    if($post_item["quota_type"] == "or" && (intval($post_item["count"]) < intval($post_item["amount"])))
                                                                        $notification[] =  array("quota_type" => $post_item["quota_type"], "type" => $post_item["type"], "status" => $post_item["status"], "count" => $post_item["count"], "amount" => $post_item["amount"]);



                                                                }


                                                            }


                                                        }


                                                    }
                                                } // post data

                                                    // If not empty, send notifications
                                                    if(isset($notification) && !empty($notification))
                                                    {

                                                        // Get Group Settings for Notifications
                                                        if(isset($quota["notifications"]) && in_array('reminder',$quota["notifications"]))
                                                        {

                                                            $this->send_notification('reminder', $notification, $user_id);

                                                        }
                                                        // Remove notification if not empty
                                                        unset($notification);
                                                    }
                                            }





                                        }


                                    } // Users

                                    // Check if notification type is already sent
                                    if($this->check_record_index_notification($time_stamp, $quota["id"], "reminder") == "not_set")
                                    {
                                        // Update Index for notifications, set reminder
                                        $this->update_record_index_notification($time_stamp, $quota["id"], "reminder");
                                    }

                                }


                                // reset time stamps per quota
                                unset($time_stamps, $this_quota);
                            }

                                // Get any records for quota with today's due date
                                // Add new record
                            if(isset($due_date) && !empty($due_date))
                                $this->add_record_index($quota["id"], $due_date);

                                $records = $this->get_record_index($quota["id"]);

                                if(isset($records) && !empty($records))
                                    {

                                        foreach ($records as $record)
                                        {

                                            // check if other records have been processed
                                            if ($record["checked"] == 0 && ($record["time_stamp"] <= $day_stamp))
                                            {
                                                // Process Record with timestamp and return (if any) reports for editors
                                                $reports_data = $this->process_record($quota, $record["time_stamp"]);

                                                // Update Record index
                                                $updated_record = $this->update_record_index($quota["id"], $record["time_stamp"]);

                                                // Notify Editors if Quota has reports enabled
                                                if(isset($quota["notifications"]) && in_array('report',$quota["notifications"]))
                                                {

                                                    // Send Reports Cron to editors (if none, all admins)
                                                    if(isset($quota["editors"]))
                                                        $_editors = $quota["editors"];
                                                    else
                                                        $_editors = "administrator";

                                                    //Get user ids for editors
                                                    $editors = $this->get_user_ids($_editors);


                                                    // Send Notification
                                                    $this->send_notification('report', $reports_data, $editors);

                                                    // Clear message data
                                                    if(isset($reports_data))
                                                        unset($reports_data);

                                                }

                                                // Check if notification type is already sent
                                                if(($this->check_record_index_notification($record["time_stamp"], $quota["id"], "report") == "not_set") && ( $updated_record == true) )
                                                {
                                                    // Update Index for notifications, set report
                                                    $this->update_record_index_notification($record["time_stamp"], $quota["id"], "report");
                                                }


                                            }

                                        }



                                    }


                                //unset Quota
                                unset($this_quota);
                            }

                }

            // Purge Record Index
            //$this->purge_record_index();

            // Purge User Records
            $this->purge_user_record();



        }



}