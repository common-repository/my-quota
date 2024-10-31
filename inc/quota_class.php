<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 10/15/20
 * Time: 1:16 PM
 */


// Quota Core
include 'core.php';

// Roster Class
include 'roster_class.php';

// Archive Class
include 'archive_class.php';

// Add-on Class
include 'add_ons.php';

// Add-ons
foreach ( glob( plugin_dir_path( __DIR__ ) . "addons/*.php" ) as $file )
{
    include_once $file;
}



/**
 * Get title Page
 * @return string
 */
class my_quota_dropstr_quota_list
{


    function get_errors($errors)
    {
        // Get official error list
        include 'errors.php';

        $_error = new my_quota_dropstr_errors();

        if(isset($errors) && !empty($errors))
        {
            // Default WP notify CSS
            if($errors[0] != 0)
            {
                $output = '<div class="notice notice-error is-dismissible"><p>';
            }
            else
            {
                $output = '<div class="notice notice-success is-dismissible"><p>';
            }


            foreach ($errors as $error)
            {

                $output .= '<p>'.$_error->error_code($error).'</p>';


            }

            $output .= '</p></div>';

            return $output;
        }


    }


    /**
     * Get Quota Group
     * @param $quota_id
     * @return array || int
     */
    private function get_quota_group($quota_id)
    {

        global $wpdb;

        // Check if valid, if so get group data
        $get_groups = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = 0 AND meta_key=%s", 'groups'));

        if(isset($get_groups) && !empty($get_groups))
        {
            foreach ($get_groups as $groups)
            {

                $group_ids = json_decode($groups->meta_value, true);

            }

            // If exists
            if(in_array($quota_id, $group_ids))
            {
                $get_group = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s AND meta_key=%s", $quota_id, 'group'));

                if(isset($get_group) && !empty($get_group))
                {
                    foreach ($get_group as $group)
                    {

                        $group_data = json_decode($group->meta_value, true);
                        $group_data["id"] = $quota_id;

                    }
                    return $group_data;
                }

            }
            else
            {
                return 0;
            }

        }
        else
        {

            return 0;
        }

    }

    /**
     * Update Quota Groups
     * @param $quota_id
     * @param $args
     * @return string
     */
    private function update_quota_group($quota_id, $args)
    {
        global $wpdb;

        // Check// Create ID
        if(!isset($quota_id) && empty($quota_id))
            $quota_id = md5($args["name"]);

        // Set Return
        $return_array[] = $quota_id;

        // Update quota id if
        $quota_group = $this->get_quota_group($quota_id);

        //encode
        $updated_group = json_encode($args);

        if(isset($quota_group) && !empty($quota_group))
        {
            // Update
            $wpdb->update(
                $wpdb->prefix.'quotas',
                array(
                    'meta_value' => $updated_group
                ),
                array( 'meta_key' => 'group', 'id' => $quota_id ),
                array(
                    '%s'
                ),
                array( '%s' )
            );

            //if update, return valid
            $return_array[] = 0;

        }
        else
        {

            // Create
            $wpdb->insert(
                $wpdb->prefix.'quotas',
                array(
                    'id'  => $quota_id,
                    'meta_key' => 'group',
                    'meta_value' => $updated_group
                ),
                array(
                    '%s',
                    '%s',
                    '%s'
                )
            );

            // Update groups
            $this->update_quota_groups($quota_id);

            //if update, return valid
            $return_array[] = 0;
        }

        return $return_array;

    }

    /**
     * Delete Quota Group by ID
     * @param $quota_id
     */
    public function delete_quota_group($quota_id)
    {
        global $wpdb;

        // Check Valid
        $group = $this->get_quota_group($quota_id);

        if (isset($group) && !empty($group)) {

            // Remove quota from index

            $get_groups = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = 0 AND meta_key=%s", 'groups'));

            if (isset($get_groups) && !empty($get_groups)) {
                foreach ($get_groups as $groups) {

                    $group_ids = json_decode($groups->meta_value, true);

                }

                $updated_ids = [];

                foreach ($group_ids as $group_id)
                {

                    if($group_id != $quota_id)
                    {
                        $updated_ids[] = $group_id;
                    }
                }

                $updated_groups = json_encode($updated_ids);

                $wpdb->update(
                    $wpdb->prefix . 'quotas',
                    array(
                        'meta_value' => $updated_groups
                    ),
                    array('meta_key' => 'groups', 'id' => '0'),
                    array(
                        '%s'
                    ),
                    array('%s')
                );

                // Delete Quota Data
                $wpdb->delete($wpdb->prefix . 'quotas', array('id' => $quota_id));

            }

        }
    }


    /**
     * Update Quota Groups
     * @param $quota_id
     * @return bool
     */
    private function update_quota_groups($quota_id)
    {
        global $wpdb;


        $get_groups = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = 0 AND meta_key=%s", 'groups'));

        if (isset($get_groups) && !empty($get_groups))
        {
            foreach ($get_groups as $groups)
            {

                $group_ids = json_decode($groups->meta_value, true);

            }

            if(!in_array($quota_id, $group_ids))
            {

                $group_ids[] = $quota_id;

                $updated_groups = json_encode($group_ids);

                //Update
                $wpdb->update(
                    $wpdb->prefix.'quotas',
                    array(
                        'meta_value' => $updated_groups
                    ),
                    array( 'meta_key' => 'groups', 'id' => '0' ),
                    array(
                        '%s'
                    ),
                    array( '%s' )
                );

            }
        }
        else
        {
            // new groups
            $updated_data = array($quota_id);

            $updated_groups = json_encode($updated_data);

            // first time
            $wpdb->insert(
                $wpdb->prefix.'quotas',
                array(
                    'id'  => '0',
                    'meta_key' => 'groups',
                    'meta_value' => $updated_groups
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
     * Get Quota Users by Users field
     * @return array
     */
    private function get_quota_users($quota_users)
    {
        $user_name = [];

        // Get site roles
        $_roles = $this->get_site_roles();

        foreach ($_roles as $role)
        {

            $role_slugs[] = $role["slug"];
        }

        // Get Edit Flow Groups

        if(isset($quota_users) && !empty($quota_users))
        {

            if(is_array($quota_users))
            {

                foreach ($quota_users as $user)
                {
                    $flag = false;

                    // if int, user id
                    if(is_int($user))
                    {
                        $user_name[] = get_user_meta($user, 'user_nicename', true);
                        $flag = true;
                    }

                    else
                    {
                        // If user is an edit flow group
                        if ( is_plugin_active( 'edit-flow/edit_flow.php' ) && $flag == false )
                        {

                            $ef_plugin = new my_quota_dropstr_edit_flow_plugin();

                            // get edit flow groups
                            $ef_groups = $ef_plugin->get_edit_flow_groups();

                            if(isset($ef_groups) && !empty($ef_groups))
                            {

                                foreach ($ef_groups as $group)
                                {

                                    $ef_group_names[] = 'ef-'.$group["name"];
                                }
                            }


                            if(in_array($user, $ef_group_names))
                            {

                                $user_name[] = $user;
                                $flag = true;

                            }

                        }

                        if($flag == false)
                        {
                            // if user is role
                            foreach ($_roles as $role)
                            {

                                if($user == $role["slug"])
                                {
                                    $user_name[] = $role["display_name"];
                                }

                            }
                        }

                    }

                }
            }

        }

        return $user_name;
    }

    /**
     * Get WP post types and clean
     * @return array
     */
    private function get_post_types()
    {

        // get post types
        $post_types = get_post_types();
        $types = [];

        $removed = array('attachment', 'revision', 'inherit', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'wp_block', 'dashboard-note');

        // remove extra types
        foreach ($post_types as $post_type)
        {
            if(!in_array($post_type, $removed))
            {
                $types[] = $post_type;
            }
        }

        return $types;

    }

    /**
     * Get WP post statuses
     * @return array
     */
    private function get_post_statuses()
    {
        $post_statuses = get_post_stati();
        $statuses = [];

        $removed = array('trash', 'inherit', 'auto-draft', 'request-pending', 'request-confirmed', 'request-failed', 'request-completed');

        foreach ($post_statuses as $post_status)
        {
            if(!in_array($post_status, $removed))
            {
                $statuses[] = $post_status;
            }


        }

        return $statuses;
    }
    // Check args coming from table
    public function check_args($args)
    {

        // Sort A-Z
        if (!empty($args["order"]))
        {
            // Safe orders
            $safe_orders = array("asc", "desc");

            if(!in_array($args["order"], $safe_orders))
            {
                $args["order"] = 'asc';
                $args["alt_order"] = 'desc';
            }

            // Set Alt Order
            if($args["order"] == 'asc')
                $args["alt_order"] = "desc";

            if($args["order"] == "desc")
                $args["alt_order"] = "asc";

        }
        else
            {
                $args["order"] = 'asc';
                $args["alt_order"] = 'desc';
            }

        // Orderby
        if (!empty($args["orderby"]))
        {
            if (!$args["orderby"] == 'group_name')
            {
                $args["orderby"] = 'group_name';
            }

        }
        else
        {
            $args["orderby"] = 'group_name';
        }

        // Role
        if (!empty($args["role"]))
        {
            if (!$args["role"] == 'all' || !$args["role"] == 'users' || !$args["role"] == 'editors')
            {
                $args["role"] = 'all';
            }

        }
        else
        {
            $args["role"] = 'all';
        }


        return $args;

    }


    /*
     * output for JS
     */
    private function js_output($script)
    {
        $output = '<script type="text/javascript">jQuery(document).ready(function(){';
        $output .= $script;
        $output .= '})</script>';

        print($output);

    }

    /**
     * JS load Group Users
     * @return string
     */
    private function js_group_users()
    {

        // Get all roles for the site (exclude subscribers)
        $args = array(
            'exclude' => array('subscribers')
        );

        $allRoles = $this->get_site_roles($args);

        $output = '';

        foreach($allRoles as $roles)
        {


            // Preload page with saved users checked
            $output .= 'if(jQuery(\'#users-list input.all-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#users-list .role-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#users-list .list-'.$roles["slug"].'\').hide();'
                .'} else {'
                .'jQuery(\'#users-list .list-'.$roles["slug"].'\').show();'
                .'if(jQuery(\'#users-list input.role-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#users-list all-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#users-list .all-'.$roles["slug"].'\').hide();'

                .'}};';


            // If users clicks All Role checkbox
            $output .= 'jQuery(\'body\').on(\'change\',\'#users-list input.all-'.$roles["slug"].'\',function(){'

                .'if (jQuery(\'#users-list input.all-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#users-list .role-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#users-list .list-'.$roles["slug"].'\').hide();'
                .'} else {'
                .' jQuery(\'#users-list .list-'.$roles["slug"].'\').show();'
                .'}'

                .'});';

            // If user clicks any user in role checkbox
            $output .= 'jQuery(\'body\').on(\'change\',\'#users-list input.role-'.$roles["slug"].'\',function(){'

                .'if (jQuery(\'#users-list input.role-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#users-list .all-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#users-list .all-'.$roles["slug"].'\').hide();'
                .'} else {'
                .' jQuery(\'#users-list .all-'.$roles["slug"].'\').show();'
                .'}'

                .'});';

        }

        // Index for Select All
        $output .= 'jQuery(\'body\').on(\'change\',\'#users-list input.select-all\',function(){'

            .'if (jQuery(\'#users-list input.select-all\').is(\':checked\')) {';

        foreach($allRoles as $roles)
        {
            $output .= 'jQuery(\'#users-list .role-'.$roles["slug"].'\').attr(\'checked\', false);';
            $output .= 'jQuery(\'#users-list .list-'.$roles["slug"].'\').hide();';

            $output .= 'jQuery(\'#users-list .all-'.$roles["slug"].'\').attr(\'checked\', true);';

        }

        $output .= '} else {';

        foreach($allRoles as $roles)
        {
            $output .= 'jQuery(\'#users-list .role-'.$roles["slug"].'\').attr(\'checked\', false);';
            $output .= 'jQuery(\'#users-list .list-'.$roles["slug"].'\').show();';

            $output .= 'jQuery(\'#users-list .all-'.$roles["slug"].'\').attr(\'checked\', false);';

        }

        $output .= '}});';




        $this->js_output($output);
    }

    /**
     * Javascript Group Editors
     */
    private function js_group_editors()
    {

        // Get all roles for the site (exclude subscribers)
        $args = array(
            'exclude' => array('subscribers', 'authors', 'contributors')
        );

        $allRoles = $this->get_site_roles($args);

        $output = '';

        foreach($allRoles as $roles)
        {


            // Preload page with saved users checked
            $output .= 'if(jQuery(\'#editors-list input.all-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#editors-list .role-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#editors-list .list-'.$roles["slug"].'\').hide();'
                .'} else {'
                .'jQuery(\'#editors-list .list-'.$roles["slug"].'\').show();'
                .'if(jQuery(\'#editors-list input.role-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#editors-list all-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#editors-list .all-'.$roles["slug"].'\').hide();'

                .'}};';


            // If users clicks All Role checkbox
            $output .= 'jQuery(\'body\').on(\'change\',\'#editors-list input.all-'.$roles["slug"].'\',function(){'

                .'if (jQuery(\'#editors-list input.all-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#editors-list .role-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#editors-list .list-'.$roles["slug"].'\').hide();'
                .'} else {'
                .' jQuery(\'#editors-list .list-'.$roles["slug"].'\').show();'
                .'}'

                .'});';

            // If user clicks any user in role checkbox
            $output .= 'jQuery(\'body\').on(\'change\',\'#editors-list input.role-'.$roles["slug"].'\',function(){'

                .'if (jQuery(\'#editors-list input.role-'.$roles["slug"].'\').is(\':checked\')) {'
                .'jQuery(\'#editors-list .all-'.$roles["slug"].'\').attr(\'checked\', false);'
                .'jQuery(\'#editors-list .all-'.$roles["slug"].'\').hide();'
                .'} else {'
                .' jQuery(\'#editors-list .all-'.$roles["slug"].'\').show();'
                .'}'

                .'});';

        }

        // Index for Select All
        $output .= 'jQuery(\'body\').on(\'change\',\'#editors-list input.select-all\',function(){'

            .'if (jQuery(\'#editors-list input.select-all\').is(\':checked\')) {';

        foreach($allRoles as $roles)
        {
            $output .= 'jQuery(\'#editors-list .role-'.$roles["slug"].'\').attr(\'checked\', false);';
            $output .= 'jQuery(\'#editors-list .list-'.$roles["slug"].'\').hide();';

            $output .= 'jQuery(\'#editors-list .all-'.$roles["slug"].'\').attr(\'checked\', true);';

        }

        $output .= '} else {';

        foreach($allRoles as $roles)
        {
            $output .= 'jQuery(\'#editors-list .role-'.$roles["slug"].'\').attr(\'checked\', false);';
            $output .= 'jQuery(\'#editors-list .list-'.$roles["slug"].'\').show();';

            $output .= 'jQuery(\'#editors-list .all-'.$roles["slug"].'\').attr(\'checked\', false);';

        }

        $output .= '}});';




        $this->js_output($output);
    }

    private function js_quota_options($quota_group = NULL)
    {
        $output_type = '<input type="hidden" name="quotas[]" value="\'+count+\'">';

        // Requirement
        $output_type .= '<label for="tag-name"><b>Type</b></label> <select name="require-\'+count+\'"><option value="-"> - </option><option value="or">OR</option></select>';

        // Get post types
        $post_types = $this->get_post_types();

        $output_types = ' <label for="tag-name"><b>Post Type</b></label> <select name="type-\'+count+\'">';
        foreach ($post_types as $post_type)
        {
            $output_types .= '<option value="'.$post_type.'">'.$post_type.'</option>';
        }

        // Post Types
        $output_types .= '</select>';

        // Post Statuses
        $post_statuses = $this->get_post_statuses();


        $output_status = ' <label for="tag-name"><b>Post Status</b></label> <select name="status-\'+count+\'">';
        foreach ($post_statuses as $post_status)
        {
            $output_status .= '<option value="'.$post_status.'">'.$post_status.'</option>';
        }

        $output_status .= '</select>';
        // Amount
        $output_amount = ' <label for="tag-name"><b>Amount</b></label> <input type="number" name="amount-\'+count+\'" style="width: 70px;">';

        $output_del = ' <a href="#" id="del-\'+count+\'" class="button button-primary del-quota">-</a>';


        // If Quota Exist
        if(isset($quota_group) && !empty($quota_group["quotas"]))
            $count = count($quota_group["quotas"]);
        else
            $count = 1;

        $output = 'var count = '.$count.';';

        $output .= 'jQuery(\'body\').on(\'click\',\'#add\',function(){';

        $output .= 'count++;';

        $output .= 'jQuery(\'<li id="quota-\' + count + \'"><p>'.$output_type.$output_types.$output_status.$output_amount.$output_del.'</p></li>\').appendTo(\'#options\');';



        $output .= '});';

        // Delete Quotas

        $output .= 'jQuery(\'body\').on(\'click\',\'.del-quota\',function(){';

        $output .= 'var id = jQuery(this).attr(\'id\');';
        $output .= 'var res = id.split("-");';

        $output .= 'jQuery(\'li\').remove(\'#quota-\'+res[1]);';

        $output .= '});';


        $this->js_output($output);

    }

    // Get Date Picker/ Calendar
    private function js_date_picker()
    {

        $output = 'jQuery(document).ready(function(){';
        $output .= 'jQuery(\'#date\').datepicker({'
            .'dateFormat: \'dd-mm-yy\''
            .'});';

        $this->js_output($output);
    }

    /**
     * Get Timeframe Selector
     */
    private function js_timeframe_selector()
    {

        // On Change
        $output = 'jQuery(\'body\').on(\'change\',\'select#timeframe\',function(){'

            .'var selectedTimeframe = jQuery(this). children("option:selected"). val();'

            .'if (selectedTimeframe == \'num_days\') {';

        $output .= 'jQuery(\'#num-days\').show();';
        $output .= 'jQuery(\'#day-week\').hide();';
        $output .= 'jQuery(\'#day-month\').hide();';

        $output .= '}; if (selectedTimeframe == \'day_week\') {';

        $output .= 'jQuery(\'#day-week\').show();';
        $output .= 'jQuery(\'#num-days\').hide();';
        $output .= 'jQuery(\'#day-month\').hide();';

        $output .= '}; if (selectedTimeframe == \'day_month\') {';

        $output .= 'jQuery(\'#day-month\').show();';
        $output .= 'jQuery(\'#num-days\').hide();';
        $output .= 'jQuery(\'#day-week\').hide();';

        $output .= '}});';


        // When page loads
        $output .= 'var timeframe = jQuery("#timeframe option:selected"). val();'

            .'if (timeframe == \'num_days\') {';

        $output .= 'jQuery(\'#num-days\').show();';
        $output .= 'jQuery(\'#day-week\').hide();';
        $output .= 'jQuery(\'#day-month\').hide();';

        $output .= '}; if (timeframe == \'day_week\') {';

        $output .= 'jQuery(\'#day-week\').show();';
        $output .= 'jQuery(\'#num-days\').hide();';
        $output .= 'jQuery(\'#day-month\').hide();';

        $output .= '}; if (timeframe == \'day_month\') {';

        $output .= 'jQuery(\'#day-month\').show();';
        $output .= 'jQuery(\'#num-days\').hide();';
        $output .= 'jQuery(\'#day-week\').hide();';
        $output .= '};';



        $this->js_output($output);
    }

    /**
     * Get WP User Roles
     * @return array
     */
    public function get_site_roles($args = NULL)
    {

        if ( ! function_exists( 'get_editable_roles' ) )
        {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        // Get all WP editable user roles
        $roles = get_editable_roles();

        // Get names for roles
        foreach($roles as $key => $rolenames)
        {
            // if value is an array
            if(is_array($rolenames))
            {
                // Official name in roles index (like author)
                $roleType = $key;
                // Common name (like Author)
                $roleName = $roles[$key]["name"];
                // Get user role type (openSuite type)

                // Min capability for Admins
                if(isset($roles[$key]["capabilities"]["manage_options"]))
                {
                    $role = "admins";
                }
                // Min capability for Editors
                elseif(isset($roles[$key]["capabilities"]["edit_others_posts"]))
                {
                    $role = "editors";
                }
                // Min capability for Authors or Contributors
                elseif(isset($roles[$key]["capabilities"]["edit_posts"]))
                {
                    $role = "authors";
                }
                else
                {   // No edit role, some type of subscriber role
                    $role = "subscribers";
                }

                // Extra Options
                if(isset($args))
                {

                    // Exclusion Role option
                    if(isset($args["exclude"]))
                    {
                        if(!in_array($role, $args["exclude"]))
                        {
                            // Return with role slug, display name, and min capability feature
                            $roleTypes[] = array("slug" => $roleType, "display_name" => $roleName, "capability" => $role);

                        }
                    }
                }
                else
                {
                    // Return with role slug, display name, and min capability feature
                    $roleTypes[] = array("slug" => $roleType, "display_name" => $roleName, "capability" => $role);
                }


            }

        }

        return $roleTypes;
    }


    /**
     * Return all taken users from other selected groups
     * @param $groups
     * @return array|int
     */
    public function get_taken_users($quota_groups = NULL)
    {

        $taken_user = [];

        if(isset($quota_groups))
        {

            // loop groups and return users (json)
            foreach($quota_groups as $group)
            {

                $users[] = $group["users"];
            }

            // users = array(users => array() )

            // get taken users from grouped users
            if(isset($users))
            {
                foreach($users as $user)
                {

                    if(is_array($user))
                    {
                        // foreach group's users
                        foreach($user as $user_id)
                        {

                            $taken_user[] = $user_id;

                        }
                    }
                    else
                    {
                        $taken_user[] = $user;
                    }

                }
            }


        }


        // If no users found
        if(!empty($taken_user))
        {

            foreach ($taken_user as $user)
            {
                // If Role Type (not id)
                if(!is_numeric($user))
                {

                    $_user= implode('-', $user);

                    // User group type
                    $user_type = $_user[0];
                    // User id/name
                    $user_id = $_user[1];

                    // Internal WP user roles
                    if($user_type == 'wp')
                    {
                        // Get All Users with this role
                        $args = array(

                            'role__in' => $user_id

                        );

                        // Get all users in WP role
                        $role_users = get_users($args);

                        foreach ($role_users as $role_user)
                        {
                            if(!in_array($role_user->ID, $taken_user))
                            {
                                // add value in array
                                $taken_user[] = $role_user->ID;

                            }

                        }

                    }

                    // If user is Edit Flow Group
                    if($user_type == 'ef_group')
                    {

                        // Get group's users
                        $ef_plugin = new my_quota_dropstr_edit_flow_plugin();
                        $ef_users = $ef_plugin->get_edit_flow_groups($user_id);

                        $ef_users =  $ef_users[0]["meta_data"]["user_ids"];

                        if(isset($ef_users) && is_array($ef_users))
                        {
                            foreach ($ef_users as $ef_user)
                            {
                                $taken_user[] = $ef_user;
                            }
                        }


                    }



                }

            }

        }


        return $taken_user;

    }


    // Header Title
    function get_header()
    {
        $htmlOutput = '<h2 class="screen-reader-text">Quota Groups</h2>';

        return $htmlOutput;
    }

    // Get Table Action Bar
    function get_action_bar($quotas, $location, $args = [], $pages = 1)
    {

        $user = get_current_user_id();
        $screen = get_current_screen();

        // Set current page
        if(isset($args["paged"]) && !empty($args["paged"]))
            $current_page = $args["paged"];
        else
            $current_page = 1;


        // Get page
        if(isset($args["page"]) && !empty($args["page"]))
            $plugin_page = '?page='.$args["page"];
        else
            $plugin_page = '';

        //Get OrderBy
        if(isset($args["orderby"]) && !empty($args["orderby"]))
            $order_by = '&orderby='.$args["orderby"];
        else
            $order_by = '';

        // Get Order
        if(isset($args["order"]) && !empty($args["order"]))
            $order = '&order='.$args["order"];
        else
            $order = '';

        // Additional Query Strings
        if(isset($args["query"]) && !empty($args["query"]))
            $query = $args["query"];
        else
            $query = '';

        // retrieve the "per_page" option
        $screen_option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $screen_option, true);

        // if groups is null or 0
        if ($quotas == 0)
        {
            $count_quota = 0;
        }
        else
        {
            $count_quota = count($quotas);
        }

        if($pages > 1)
            $page_class = '';
        else
            $page_class = 'one-page';

        $htmlOutput = '<div class="tablenav '.$location.'">';


        // Get Menus
        if(!isset($args["query"]) && empty($args["query"]))
        {

            // Archive Records Options
            if(isset($_GET["archive"]) || (isset($_GET["page"]) && $_GET["page"] == "myquota-editor") )
            {

            }
            else
                {

                    // Get action page
                    if(isset($plugin_page) && !empty($plugin_page))
                        $_plugin_page = explode("=", $plugin_page);


                    $htmlOutput .= '<div class="alignleft actions bulkactions"><form method="get">';

                    // If acount page is set
                    if(isset($plugin_page) && !empty($plugin_page))
                        $htmlOutput .= '<input type="hidden" name="page" value="'.$_plugin_page[1].'">';


                    // Get Paged location
                    if(isset($current_page) && !empty($current_page))
                    {
                        $htmlOutput .= '<input type="hidden" name="paged" value="'.$current_page.'">';
                    }



                    // Get Order By
                    if(isset($order_by) && !empty($order_by))
                    {
                        $_order_by = explode("=", $order_by);
                        $htmlOutput .= '<input type="hidden" name="order_by" value="'.$_order_by[1].'">';
                    }



                    // Get Order
                    if(isset($order) && !empty($order))
                    {
                        $_order = explode("=", $order);
                        $htmlOutput .= '<input type="hidden" name="order" value="'.$_order[1].'">';
                    }



                    // Roster Page Options
                    //<a href="#" id="pick_date" class="button action"><span class="dashicons dashicons-calendar-alt"> </span></a>
                    $htmlOutput .= ' <select name="quota"><option value="current">Current Quota</option><option value="last" ';
                    // If option selected
                    if(isset($_GET["quota"]) && $_GET["quota"] == "last") $htmlOutput .= " selected";

                    $htmlOutput .= '>Last Quota</option></select><input type="submit" id="doaction" class="button action" value="Apply"></form> </div>';

                    $htmlOutput .= '<div class="alignleft actions">';
                    $htmlOutput .= '<a href="admin.php?page=myquota-editor" class="button action"><span class="dashicons dashicons-groups" style="vertical-align: middle"> </span></a>';
                    $htmlOutput .= ' <a href="admin.php?page=myquota-settings" class="button action"><span class="dashicons dashicons-admin-generic" style="vertical-align: middle"> </span></a>';
                    $htmlOutput .= '</div>';
                }
        }


        // Items
        $htmlOutput .= '<div class="tablenav-pages '.$page_class.'"><span class="displaying-num">' . $count_quota . ' items</span>';

        // Get Pages

        $htmlOutput .= ' <span class="pagination-links">';

        // First Page
        if($current_page == 1 && $pages == 1)
        {
            $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
        }
        else
            {
                // More than 1 page and current page is not 1
                if($pages > 1 && $current_page != 1)
                {
                    $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
                }
                else
                    {
                        $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
                    }

            }

        //Previous Page
        if($current_page == 1 && $pages == 1)
        {
            $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
        }
        else
            {
                // Previous Page
                $previous_page = $current_page-1;

                // More than 1 page and current page is 1
                if($current_page == 1 && $pages > 1)
                {

                    $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
                }
                else
                    {
                        $previous_page = $current_page-1;

                        $htmlOutput .= '<a class="previous-page button" href="'.$plugin_page.'&paged='.$previous_page.$order_by.$order.$query.'"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
                    }

            }

        // Current Page
        if($pages > 1)
        {
            $htmlOutput .= '<span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="'.$current_page.'" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">'.$pages.'</span></span></span>';
        }
        else
            {
                $htmlOutput .= '<span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">1 of <span class="total-pages">1</span></span></span>';
            }

        // Next Page
        if($current_page == 1 && $pages == 1)
        {
            $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
        }
        else
            {
                // More than 1 page and current is last page
                if($pages > 1 && $current_page >= $pages)
                {
                    $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
                }
                else
                    {
                        $next_page = $current_page+1;

                        $htmlOutput .= '<a class="next-page button" href="'.$plugin_page.'&paged='.$next_page.$order_by.$order.$query.'"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>';
                    }

            }

        // Last Page
        if($current_page == 1 && $pages == 1)
        {
            $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
        }
        else
            {
                if($pages > 1 && $current_page != 1)
                {
                    $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
                }
                else
                    {
                        $htmlOutput .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
                    }

            }

        $htmlOutput .= '</span></div><br class="clear"></div>';


        /*$htmlOutput .= '<span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>'
            . '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>'
            . '<span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">1 of <span class="total-pages">1</span></span></span>'
            . '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>'
            . '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span></div>'
            . '<br class="clear">'
            . '</div>';*/


        if($location == 'bottom')
            $htmlOutput .= '<br class="clear"></div>';



        return $htmlOutput;
    }

    /**
     * Set Page Header
     * @return string
     */
    private function set_page_header()
    {

        $output = '<div id="col-container" class="wp-clearfix">';

        return $output;
    }

    /**
     * Set Table Header
     * @param $title
     * @return string
     */
    private function set_table_header($title)
    {

        // Wrapper
        $output = '<div id="col-left"><div class="col-wrap"><h2>' . $title . '</h2>';

        return $output;
    }


    /**
     * Set the right hand side header
     */
    private function set_right_side()
    {

        $output = '<div id="col-right"><div class="col-wrap">';

        return $output;

    }

    /**
     * Set right hand side footer
     * @return string
     */
    private function set_right_footer()
    {

        $output = '</div></div>';

        return $output;
    }

    /**
     * Set Quota Options
     * @return string
     */
    private function set_quota_options($quota_group)
    {

        $output = '<h2>Requirements</h2><p>Select the post requirements for the quota group. Add additional amounts with the + button.</p>';
        // Table
        $output .= '<div class="form-field form-required term-name-wrap"><ul id="options" class="wp-list-table widefat fixed striped tags">';

        // If Quota exists
        if(isset($quota_group) && isset($quota_group["quotas"]))
        {

            // Load Previous Quota's
            $i = 1;
            // Get post types
            $post_types = $this->get_post_types();

            // Post Statuses
            $post_statuses = $this->get_post_statuses();

            foreach ($quota_group["quotas"] as $quota)
            {
                $output .= '<input type="hidden" name="quotas[]" value="'.$i.'">';

                // Requirement
                $output .= '<li id="quota-'.$i.'"><p><label for="tag-name"><b>Type</b></label> <select name="require-'.$i.'"';

                // If - selected
                if($quota["require"] == '-')
                    $output .= ' selected';

                $output .= '><option value="-"> - </option>';

                $output .= '<option value="or"';

                // If OR Selected
                if($quota["require"] == 'or')
                    $output .= ' selected';

                $output .= '>OR</option></select>';

                $output .= ' <label for="tag-name"><b>Post Type</b></label> <select name="type-'.$i.'">';
                foreach ($post_types as $post_type)
                {
                    $output .= '<option value="'.$post_type.'"';

                    // if selected
                    if($quota["type"] == $post_type)
                        $output .= ' selected';

                    $output .= '>'.$post_type.'</option>';
                }

                // Post Types
                $output .= '</select>';


                $output .= ' <label for="tag-name"><b>Post Status</b></label> <select name="status-'.$i.'">';
                foreach ($post_statuses as $post_status)
                {
                    $output .= '<option value="'.$post_status.'"';

                    if($quota["status"] == $post_status)
                        $output .= ' selected';

                    $output .= '>'.$post_status.'</option>';
                }

                $output .= '</select>';
                // Amount
                $output .= ' <label for="tag-name"><b>Amount</b></label> <input type="number" name="amount-'.$i.'"';

                if(isset($quota["amount"]))
                    $output .= ' value="'.$quota["amount"].'"';

                $output .= 'style="width: 70px;">';

                $output .= ' <a href="#" id="del-'.$i.'" class="button button-primary del-quota">-</a>';

                $output .= '</p></li>';

                $i++;
            }

            $output .= '</ul>';

        }
        else
        {
            $output .= '<input type="hidden" name="quotas[]" value="1">';

            // Requirement
            $output .= '<li id="quota-1"><p><label for="tag-name"><b>Type</b></label> <select name="require-1"><option value="-"> - </option><option value="or">OR</option></select>';

            // Get post types
            $post_types = $this->get_post_types();

            $output .= ' <label for="tag-name"><b>Post Type</b></label> <select name="type-1">';
            foreach ($post_types as $post_type)
            {
                $output .= '<option value="'.$post_type.'">'.$post_type.'</option>';
            }

            // Post Types
            $output .= '</select>';

            // Post Statuses
            $post_statuses = $this->get_post_statuses();


            $output .= ' <label for="tag-name"><b>Post Status</b></label> <select name="status-1">';
            foreach ($post_statuses as $post_status)
            {
                $output .= '<option value="'.$post_status.'">'.$post_status.'</option>';
            }

            $output .= '</select>';
            // Amount
            $output .= ' <label for="tag-name"><b>Amount</b></label> <input type="number" name="amount-1" style="width: 70px;">';

            $output .= '</p></li></ul>';

        }



        // Days/timeframe

        // Add New
        $output .= '</div><div><a href="#" id="add" class="button button-primary">+</a></div>';

        $output .= '<p><b>Type</b><ul><li><b>-</b> The quota type must be met for the quota requirement.</li><li><b>OR</b> - Only one OR type post needs to be met for the quota requirement.</li></ul> </p>';


        // js
        $output .= $this->js_quota_options($quota_group);


        // Get Conditions (if enabled)
        $core = new my_quota_dropstr_quota_core();

        $snaps = $core->get_snaps();

        if(isset($snaps) && !empty($snaps))
        {
            $output .= '<hr><div><h2>Additional Requirements</h2><p>Set additional conditions for the required post types above.</p></div>';


            // Yoast SEO
            if(isset($snaps["wordpress-seo"]) && !empty($snaps["wordpress-seo"]))
            {

                $wordpress_seo = new my_quota_dropstr_wordpress_seo_plugin();

                $output .= $wordpress_seo->get_wordpress_seo_module($quota_group);

            }

        }




        return $output;
    }

    /**
     * Set Timeframe
     * @return string
     */
    private function set_timeframe($quota_group = NULL)
    {

        $timezone = get_option('timezone_string');
        if(empty($timezone))
        {
            $timezone = 'UTC '.get_option('gmt_offset');
        }

        $output = '<hr><h2>Timeframe</h2>';

        // Dropdown box for options
        $output .= '<p>Select a timeframe option</p>';


        if(isset($quota_group) && !empty($quota_group["timeframe"]))
        {

            $output .= '<select id="timeframe" name="timeframe">';

            /*$output .= '<option value="num_days" ';

            if($quota_group["timeframe"] == 'num_days')
                $output .= ' selected';

            $output .= '>Every X Amount of Days</option>';*/

            $output .= '<option value="day_week"';

            if($quota_group["timeframe"] == 'day_week')
                $output .= ' selected';

            $output .='>Every X Day of the Week</option>';

            $output .= '<option value="day_month"';

            if($quota_group["timeframe"] == 'day_month')
                $output .= ' selected';

            $output .= '>Every X Day of the Month</option></select><hr>';

            // Every X Days
            $output .= '<div id="num-days" style="display: none"><h2>Every X Amount of Days</h2><p>Enter in how many days the quota should be met:</p><label for="tag-days"><b>Every</b></label> <input type="number" name="num_days"';

            if(isset($quota_group["timeframe_args"]["num_days"]))
                $output .= ' value="'.$quota_group["timeframe_args"]["day_month"].'"';

            $output .= 'style="width: 70px;"> Days</div>';

            // Every X Day of Week
            $output .= '<div id="day-week" style="display: none"><h2>Every X Day of the Week</h2><p>Select the day of the week the quota should be met:</p><label for="week-days"><b>Every</b></label><select name="day_week">';


            $day_of_week = array('Monday' => 'mon', 'Tuesday' => 'tue', 'Wednesday' => 'wed', 'Thursday' => 'thu', 'Friday' => 'fri', 'Saturday' => 'sat', 'Sunday' => 'sun');

            foreach($day_of_week as $key => $day)
            {

                $output .= '<option value="'.$day.'"';

                if(isset($quota_group["timeframe_args"]["day_week"]) && $quota_group["timeframe_args"]["day_week"] == $day)
                    $output .= ' selected';

                $output .= '>'.$key.'</option>';


            }

            $output .= '</select></div>';

            // Every X Day of Month
            $output .= '<div id="day-month" style="display: none"><h2>Every X Day of the Month</h2><p>Select the day of the month the quota should be met:</p><label for="month-days"><b>Every</b></label><select name="day_month">';

            $day_of_month = array(
                '1st' => 1,
                '2nd' => 2,
                '3rd' => 3,
                '4th' => 4,
                '5th' => 5,
                '6th' => 6,
                '7th' => 7,
                '8th' => 8,
                '9th' => 9,
                '10th' => 10,
                '11th' => 11,
                '12th' => 12,
                '13th' => 13,
                '14th' => 14,
                '15th' => 15,
                '16th' => 16,
                '17th' => 17,
                '18th' => 18,
                '19th' => 19,
                '20th' => 20,
                '21st' => 21,
                '22nd' => 22,
                '23rd' => 23,
                '24th' => 24,
                '25th' => 25,
                '26th' => 26,
                '27th' => 27,
                '28th' => 28,
                '29th' => 29,
                '30th' => 30,
                '31st' => 31
            );

            foreach($day_of_month as $key => $day)
            {

                $output .= '<option value="'.$day.'"';

                if(isset($quota_group["timeframe_args"]["day_month"]) && $quota_group["timeframe_args"]["day_month"] == $day)
                    $output .= ' selected';

                $output .= '>'.$key.'</option>';


            }

            $output .= '<option value="end">End of Month</option></select><p>Choose <u>End</u> for the last day of every month.</p></div>';


            // Time Due
            $output .= '<hr><h2>Time Due</h2><p>Enter in the time the quota is due on the date above. The time zone is based on the <a href="options-general.php">site settings</a>.</p><label for="time"><b>At</b></label><select name="time_h">';

            // Hour Select

            $time_hour = array(
                '01' => 1,
                '02' => 2,
                '03' => 3,
                '04' => 4,
                '05' => 5,
                '06' => 6,
                '07' => 7,
                '08' => 8,
                '09' => 9,
                '10' => 10,
                '11' => 11,
                '12' => 12
            );

            foreach($time_hour as $key => $hour)
            {

                $output .= '<option value="'.$hour.'"';

                if(isset($quota_group["time_args"]["hour"]) && $quota_group["time_args"]["hour"] == $hour)
                    $output .= ' selected';

                $output .= '>'.$key.'</option>';


            }


            $output .= '</select> <label for="time"> <b>:</b> </label><select name="time_m">';

            $time_minute = array(
                '00' => '00',
                '01' => '01',
                '02' => '02',
                '03' => '03',
                '04' => '04',
                '05' => '05',
                '06' => '06',
                '07' => '07',
                '08' => '08',
                '09' => '09',
                '10' => '10'
            );

            foreach($time_minute as $key => $minute)
            {

                $output .= '<option value="'.$minute.'"';

                if(isset($quota_group["time_args"]["minute"]) && $quota_group["time_args"]["minute"] == $minute)
                    $output .= ' selected';

                $output .= '>'.$key.'</option>';


            }

            // Minutes+
            for ($i=11; $i<60;$i++)
            {
                $output .= '<option value="'.$i.'"';

                if(isset($quota_group["time_args"]["minute"]) && $quota_group["time_args"]["minute"] == $i)
                    $output .= ' selected';

                $output .= '>'.$i.'</option>';

            }
            $output .= '</select> <select name="time_z">';

            $output .= '<option value="am"';

            if(isset($quota_group["time_args"]["ampm"]) && $quota_group["time_args"]["ampm"] == 'am')
                $output .= ' selected';

            $output .= '>AM</option>';

            $output .='<option value="pm"';

            if(isset($quota_group["time_args"]["ampm"]) && $quota_group["time_args"]["ampm"] == 'pm')
                $output .= ' selected';

            $output .= '>PM</option></select> '.$timezone.'';


            // Start Date
            $output .= '<h2>Start Date</h2><p>Enter in the date in which this quota policy will become active.</p>';

            // Calendar
            $output .= '<input type="date" id="date" name="start_date"';

            // If date is selected
            if(isset($quota_group["start_date"]) && !empty($quota_group["start_date"]))
                $output .= ' value="'.$quota_group["start_date"].'"';


            $output .= '>';

            $output .= $this->js_timeframe_selector();

            $output .= $this->js_date_picker();


        }
        else
        {

            $output .= '<select id="timeframe" name="timeframe"><option value="" disabled selected>Select Timeframe</option><option value="day_week">Every X Day of the Week</option><option value="day_month">Every X Day of the Month</option></select><hr>';

            // Every X Days
            /*
             * <option value="num_days">Every X Amount of Days</option>
             * $output .= '<div id="num-days" style="display: none"><h2>Every X Amount of Days</h2><p>Enter in how many days the quota should be met:</p><label for="tag-days"><b>Every</b></label> <input type="number" name="num_days" style="width: 70px;"> Days</div>';
             */


            // Every X Day of Week
            $output .= '<div id="day-week" style="display: none"><h2>Every X Day of the Week</h2><p>Select the day of the week the quota should be met:</p><label for="week-days"><b>Every</b></label><select name="day_week"><option value="mon">Monday</option><option value="tue">Tuesday</option><option value="wed">Wednesday</option><option value="thu">Thursday</option><option value="fri">Friday</option><option value="sat">Saturday</option><option value="sun">Sunday</option></select></div>';

            // Every X Day of Month
            $output .= '<div id="day-month" style="display: none"><h2>Every X Day of the Month</h2><p>Select the day of the month the quota should be met:</p><label for="month-days"><b>Every</b></label><select name="day_month"><option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option><option value="5">5th</option><option value="6">6th</option><option value="7">7th</option><option value="8">8th</option><option value="9">9th</option><option value="10">10th</option><option value="11">11th</option><option value="12">12th</option><option value="13">13th</option><option value="14">14th</option><option value="15">15th</option><option value="16">16th</option><option value="17">17th</option><option value="18">18th</option><option value="19">19th</option><option value="20">20th</option><option value="21">21st</option><option value="22">2nd</option><option value="23">23rd</option><option value="24">24th</option><option value="25">25th</option><option value="26">26th</option><option value="27">27th</option><option value="28">28th</option><option value="29">29th</option><option value="30">30th</option><option value="31">31st</option><option value="end">End of Month</option></select><p>Choose <u>End</u> for the last day of every month.</p></div>';


            // Time Due
            $output .= '<hr><h2>Time Due</h2><p>Enter in the time the quota is due on the date above. The time zone is based on the <a href="options-general.php">site settings</a>.</p><label for="time"><b>At</b></label><select name="time_h"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></select> <label for="time"> <b>:</b> </label><select name="time_m"><option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option>';

            // Minutes
            for ($i=10; $i<60;$i++)
            {
                $output .= '<option value="'.$i.'">'.$i.'</option>';

            }

            $output .= '</select> <select name="time_z"><option value="am">AM</option><option value="pm">PM</option></select> '.$timezone.'';


            // Start Date
            $output .= '<h2>Start Date</h2><p>Enter in the date in which this quota policy will become active.</p>';

            // Calendar
            $output .= '<input type="date" id="date" name="start_date"  >';

            $output .= $this->js_timeframe_selector();

            $output .= $this->js_date_picker();

        }



        return $output;
    }

    /**
     * Set Table Footer
     * @return string
     */
    private function set_table_footer()
    {

        // Wrapper
        $output = '</div></div>';

        return $output;
    }

    /**
     * Set Page Footer
     * @return string
     */
    private function set_page_footer()
    {

        $output = '</div>';

        return $output;
    }

    /**
     * Set Form Header
     * @param $page_command
     * @param $group_command
     * @return string
     */
    private function set_form_header($page_command)
    {
        // Form Begin
        $output = '<form id="quota-groups" class="form-horizontal" method="GET" action="admin.php">'
            . '<input type="hidden" name="page" value="myquota-editor">'
            . '<input type="hidden" name="c" value="' . $page_command . '">';

        // If edit, add verify
        if ($page_command == "edit")
        {
            $output .= wp_nonce_field('edit', 'edit_status');
        }

        $output .= '<div class="form-group">';

        return $output;
    }

    /**
     * Set the Form Footer
     * @return string
     */
    private function set_form_footer()
    {

        $output = '</form>';

        return $output;
    }

    /**
     * Ste Form Footer
     * @return string
     */
    private function set_left_footer()
    {
        $output = '</div>';

        return $output;
    }

    /**
     * Set hidden quota id input
     * @param $quota_id
     * @return string
     */
    private function set_form_id($quota_id)
    {

        $output = '<input type="hidden" name="status_id" value="' . $quota_id . '">';
        return $output;
    }

    /**
     * Get Quota Name
     * @param $args
     * @return string
     */
    private function get_quota_name($args)
    {

        // Status Label
        $output = '<div class="form-field form-required term-name-wrap"><label for="tag-name">Group Name</label>';

        if(isset($args["name"]) && !empty($args["name"]))
        {

            // disable label
            $output .= '<input type="text" id="name" name="label-disabled" value="'.$args["name"].'" size="40" aria-required="true" disabled>';
            // send label as hidden
            $output .= '<input type="hidden" id="name" name="name" value="'.$args["name"].'">';
        }
        else
        {
            $output .= '<input type="text" id="name" name="name" size="40" aria-required="true">';

        }

        $output .= '<p>Give your quota group a name. (do not use special characters)</p></div>';

        return $output;

    }
    /**
     * Get User/Group by User Role
     * @param $roles $current_quota_group $taken_users
     * @return string
     */
    private function get_user_by_role($roles, $current_quota_group)
    {
        // Get all users from role type
        $role_args = array(
            'orderby' => 'nicename',
            'role__in' => $roles["slug"]

        );

        // Create All Role Type checkbox
        $output = ' <tr class="level-0 all-'.$roles["slug"].'"><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-4">Select Role</label><input type="checkbox" name="users[]"';

        // If WP role type exists in current group
        if(isset($current_quota_group["users"]))
        {
            if (in_array($roles["slug"], $current_quota_group["users"]))
            {
                $output .= ' checked';
            }
        }


        $output .= ' value="' . $roles["slug"] . '" class="all-' . $roles["slug"] . '"></th><td class="name column-name has-row-actions column-primary" data-colname="Name"><strong>All ' . $roles["display_name"] . '(s)</strong><br><div class="hidden" id="inline_4"><div class="name">' . $roles["display_name"]. '</div><div class="slug">All ' . $roles["display_name"] . '(s)</div></div><div class="row-actions"></div></td></tr>';


        // Get all users in WP role
        $users = get_users($role_args);

        // If users is not empty, show role and users
        if(!empty($users))
        {


            // Show each users from WP role
            foreach($users as $user)
            {


                $output .= ' <tr class="level-0 list-'.$roles["slug"].'"><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-4">Select Blog</label><input type="checkbox" name="users[]"';

                // If user is selected from current group
                if(isset($current_quota_group["users"]))
                {
                    if (in_array($user->ID, $current_quota_group["users"]))
                    {
                        $output .= ' checked';
                    }
                }


                $output .= ' value="' . $user->ID . '" class="role-'.$roles["slug"].'"></th><td class="name column-name has-row-actions column-primary" data-colname="Name">- <strong>' . $user->display_name . '</strong> (' . $user->user_nicename . ')<br><div class="hidden" id="inline_4"><div class="name">' . $user->display_name . '</div><div class="slug">[' . $user->user_nicename . ']</div></div><div class="row-actions"></div></td></tr>';



            }

            unset($users);

        }

        return $output;

    }

    /*
     * Get Editor by Wp Role
     *
     */
    private function get_editor_by_role($roles, $current_quota_group)
    {
        // Get all users from role type
        $role_args = array(
            'orderby' => 'nicename',
            'role__in' => $roles["slug"]

        );

        // Create All Role Type checkbox
        $output = ' <tr class="level-0 all-'.$roles["slug"].'"><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-4">Select Role</label><input type="checkbox" name="editors[]"';

        // If WP role type exists in current group
        if(isset($current_quota_group["editors"]))
        {
            if (in_array($roles["slug"], $current_quota_group["editors"]))
            {
                $output .= ' checked';
            }
        }


        $output .= ' value="' . $roles["slug"] . '" class="all-' . $roles["slug"] . '"></th><td class="name column-name has-row-actions column-primary" data-colname="Name"><strong>All ' . $roles["display_name"] . '(s)</strong><br><div class="hidden" id="inline_4"><div class="name">' . $roles["display_name"]. '</div><div class="slug">All ' . $roles["display_name"] . '(s)</div></div><div class="row-actions"></div></td></tr>';


        // Get all users in WP role
        $users = get_users($role_args);

        // If users is not empty, show role and users
        if(!empty($users))
        {


            // Show each users from WP role
            foreach($users as $user)
            {


                $output .= ' <tr class="level-0 list-'.$roles["slug"].'"><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-4">Select Blog</label><input type="checkbox" name="editors[]"';

                // If user is selected from current group
                if(isset($current_quota_group["editors"]))
                {
                    if (in_array($user->ID, $current_quota_group["editors"]))
                    {
                        $output .= ' checked';
                    }
                }


                $output .= ' value="' . $user->ID . '" class="role-'.$roles["slug"].'"></th><td class="name column-name has-row-actions column-primary" data-colname="Name">- <strong>' . $user->display_name . '</strong> (' . $user->user_nicename . ')<br><div class="hidden" id="inline_4"><div class="name">' . $user->display_name . '</div><div class="slug">[' . $user->user_nicename . ']</div></div><div class="row-actions"></div></td></tr>';



            }

            unset($users);

        }

        return $output;

    }



    /**
     * Set Group Users
     * @param $args
     * @return string
     */
    private function set_users($quota_group = NULL)
    {

        // Table creation
        $output = '<hr><h2>Quota Users</h2>';
        $output .= '<div id="users-list" class="form-field form-required term-name-wrap"><p>Select the user(s)/role(s) to apply to this group.</p><table class="wp-list-table widefat fixed striped tags">'
            .'<thead>'
            .'<tr>'
            .'<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox" class="select-all"></td><th scope="col" id="name" class="manage-column column-name column-primary sortable desc"><a href="#"><span>User/Role/Group</span><span class="sorting-indicator"></span></a></th></tr>
                    </thead><tbody id="the-list" data-wp-lists="list:tag">';


        // Get all roles (by type, not name) for the site
        $args = array(
            'exclude' => array('subscribers')

        );

        $allRoles = $this->get_site_roles($args);

        // If edit flow active, get edit flow groups
        //Update Snap Status
        $snap = new my_quota_dropstr_quota_core();

        if ( $snap->is_snap_active('edit-flow') )
        {

            $ef_plugin = new my_quota_dropstr_edit_flow_plugin();

            // get edit flow groups
            $ef_groups = $ef_plugin->get_edit_flow_groups();
        }

        // Publish Press Groups
        if ( $snap->is_snap_active('publishpress') )
        {

            $pp_plugin = new my_quota_dropstr_publishpress_plugin();

            // get edit flow groups
            $pp_groups = $pp_plugin->get_publishpress_groups();
        }

        // Get Current Quota Group
        if(isset($quota_group))
        {
            $current_quota_group = $quota_group;
        }
        else
        {
            $current_quota_group = [];
        }

        // Get all roles that match not editors/admins
        foreach($allRoles as $roles)
        {

            // Display users by role
            $output .= $this->get_user_by_role($roles, $current_quota_group);

        }

        // if Edit Flow is enabled
        if(isset($ef_groups))
        {
            foreach( $ef_groups as $ef_group)
            {

                // Display users by Edit Flow groups
                $output .= $ef_plugin->get_user_by_ef_group($ef_group, $current_quota_group);
            }
        }

        // If Publish Press is enabled
        if(isset($pp_groups))
        {
            foreach( $pp_groups as $pp_group)
            {

                // Display users by Edit Flow groups
                $output .= $pp_plugin->get_user_by_pp_group($pp_group, $current_quota_group);
            }
        }

        // Closing Table
        $output .= '</tbody><tfoot>'
            . '<tr>'
            . '<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2 " type="checkbox" class="select-all"></td><th scope="col" class="manage-column column-name column-primary sortable desc"><a href="#"><span>User/Role/Group</span><span class="sorting-indicator"></span></a></th></tr>'
            . '</tfoot></table></div>';

        // add js
        $output .= $this->js_group_users();

        return $output;

    }

    /**
     * Set Quota Editors
     * @param null $quota_group
     * @return string
     */
    private function set_editors($quota_group = NULL)
    {

        // Table creation
        $output = '<hr><h2>Quota Editors</h2>';
        $output .= '<div id="editors-list" class="form-field form-required term-name-wrap"><p><b>Optional</b> - Select the editor(s)/role(s) to apply to this group. If no editors are selected, reminders and reports will only be sent to the admin.</p><table class="wp-list-table widefat fixed striped tags">'
            .'<thead>'
            .'<tr>'
            .'<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox" class="select-all"></td><th scope="col" id="name" class="manage-column column-name column-primary sortable desc"><a href="#"><span>User/Role/Group</span><span class="sorting-indicator"></span></a></th></tr>
                    </thead><tbody id="the-list" data-wp-lists="list:tag">';


        // Get all roles (by type, not name) for the site
        $args = array(
            'exclude' => array('subscribers', 'authors')

        );

        $allRoles = $this->get_site_roles($args);

        // If edit flow active, get edit flow groups
        //Update Snap Status
        $snap = new my_quota_dropstr_quota_core();

        if ( $snap->is_snap_active('edit-flow') )
        {

            $ef_plugin = new my_quota_dropstr_edit_flow_plugin();

            // get edit flow groups
            $ef_groups = $ef_plugin->get_edit_flow_groups();
        }

        // Publish Press Groups
        if ( $snap->is_snap_active('publishpress') )
        {

            $pp_plugin = new my_quota_dropstr_publishpress_plugin();

            // get edit flow groups
            $pp_groups = $pp_plugin->get_publishpress_groups();
        }

        // Get Current Quota Group
        if(isset($quota_group))
        {
            $current_quota_group = $quota_group;
        }
        else
        {
            $current_quota_group = [];
        }

        // Get all roles that match not editors/admins
        foreach($allRoles as $roles)
        {

            // Display users by role
            $output .= $this->get_editor_by_role($roles, $current_quota_group);

        }

        // if Edit Flow is enabled
        if(isset($ef_groups))
        {
            foreach( $ef_groups as $ef_group)
            {

                // Display users by Edit Flow groups
                $output .= $ef_plugin->get_editor_by_ef_group($ef_group, $current_quota_group);
            }
        }

        // if Publish Press is enabled
        if(isset($pp_groups))
        {
            foreach( $pp_groups as $pp_group)
            {

                // Display users by Edit Flow groups
                $output .= $pp_plugin->get_editor_by_pp_group($pp_group, $current_quota_group);
            }
        }

        // Closing Table
        $output .= '</tbody><tfoot>'
            . '<tr>'
            . '<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2 " type="checkbox" class="select-all"></td><th scope="col" class="manage-column column-name column-primary sortable desc"><a href="#"><span>User/Role/Group</span><span class="sorting-indicator"></span></a></th></tr>'
            . '</tfoot></table></div>';

        // add js
        $output .= $this->js_group_editors();

        return $output;

    }

    /**
     * Set Reminders
     * @param $quota_group
     */
    private function set_reminders($quota_group)
    {

        // Get Notification Settings
        $settings = new my_quota_dropstr_quota_core();
        $notification_settings = $settings->get_myquota_settings();

        $output = "<hr><h2>Notifications</h2>";

        // Is set and not disabled

            $output .= '<p><label><input type="checkbox" name="reminder_email" value="1"';

            if(isset($quota_group) && !empty($quota_group))
            {

                // If reminder set, checked
                if(isset($quota_group["notifications"]))
                {
                    if(in_array("reminder", $quota_group["notifications"]))
                        $output .= ' checked=""';
                }

            }

            $output .= '> Send user reminder email 24 hours before due date (for missed quotas).</label></p>';

            $output .= '<p><label><input type="checkbox" name="report_email" value="1"';

            if(isset($quota_group) && !empty($quota_group))
            {

                // If reminder set, checked
                if(isset($quota_group["notifications"]))
                {
                    if(in_array("report", $quota_group["notifications"]))
                        $output .= ' checked=""';
                }

            }

            $output .= '> Send editor(s) quota report on non/completed quotas.</label></p>';


        return $output;
    }

    /**
     * Set Form Save Button
     * @return string
     */
    private function set_save_button()
    {

        $output = '<p><hr><div><input type="submit" name="save" value="Save" class="button button-primary"> <a href="?page=myquota-editor" class="button">Back</a> </div></p>';

        return $output;

    }


    /**
     * Get Quota Settings
     * @return string
     */
    function get_quota_settings($page_command, $quota_id)
    {

        // Set Table Div
        $html = $this->set_page_header();

        // Set header
        $html .= $this->set_form_header($page_command);

        $html .= $this->set_table_header('Quota Settings');

        // If Saving/Updating
        if(isset($_GET["save"]) && $_GET["save"] == 'Save')
        {

            // Get Quota id
            if(isset($_GET["status_id"]))
                $quota_group["id"] = sanitize_key($_GET["status_id"]);


            // Group Name
            if(isset($_GET["name"]))
                $name = sanitize_text_field( $_GET['name'] );


            // Group Users
            if(isset($_GET["users"]))
            {
                // Check Array
                foreach ($_GET["users"] as $_user)
                {

                    $users[] = sanitize_text_field( $_user );

                }

            }

            // Group Editors
            if(isset($_GET["editors"]))
            {
                // Check Array
                foreach ($_GET["editors"] as $_editor)
                {

                    $editors[] = sanitize_text_field( $_editor );

                }

            }
            else
                $editors = '';

            // Check quota requirements
            if(isset($_GET["quotas"]))
            {

                if(is_array($_GET["quotas"]))
                {

                    $quotas = [];

                    foreach ($_GET["quotas"] as $_key)
                    {
                        $require = '';
                        $status = '';
                        $type = '';
                        $amount = '';

                        $key = sanitize_text_field( $_key );

                        // Group Options
                        if(isset($_GET["require-".$key.""]))
                            $require = sanitize_text_field( $_GET["require-".$key.""] );

                        if(isset($_GET["status-".$key.""]))
                            $status = sanitize_text_field( $_GET["status-".$key.""] );


                        if(isset($_GET["type-".$key.""]))
                            $type = sanitize_text_field( $_GET["type-".$key.""] );


                        if(isset($_GET["amount-".$key.""]))
                            $amount = sanitize_text_field( $_GET["amount-".$key.""] );


                        if(isset($require, $status, $type, $amount) && !empty($require) && !empty($status) && !empty($type) && !empty($amount))
                            $quotas[] = array('require' => $require, 'status' => $status, 'type' => $type, 'amount' => $amount);


                    }

                }

            }


            // Get Timeframe
            if(isset($_GET["timeframe"]))
            {
                $timeframe = sanitize_text_field( $_GET["timeframe"] );

                if($timeframe == 'num_days')
                {
                    $timeframe_args = array(
                        'num_days' => sanitize_text_field( $_GET["num_days"] )
                    );

                }

                if($timeframe == 'day_week')
                {
                    $timeframe_args = array(
                        'day_week'=>  sanitize_text_field( $_GET["day_week"] )
                    );

                }

                if($timeframe == 'day_month')
                {
                    $timeframe_args = array(
                        'day_month' => sanitize_text_field( $_GET["day_month"] )
                    );

                }

            }

            if(isset($_GET["time_h"]) && isset($_GET["time_m"]) && isset($_GET["time_z"]))
                $time_args = array(
                    'hour' => sanitize_text_field( $_GET["time_h"] ),
                    'minute' => sanitize_text_field( $_GET["time_m"] ),
                    'ampm' => sanitize_text_field( $_GET["time_z"] )
                );

            if(isset($_GET["start_date"]))
                $start_date = sanitize_text_field( $_GET["start_date"] );



            // Notifications
            $notifications = [];

            // Reminder Email
            if(isset($_GET["reminder_email"]))
            {
                // Validation Check
                if($_GET["reminder_email"] == "1")
                    $notifications[] = "reminder";
            }

            // Report Emails
            if(isset($_GET["report_email"]))
            {
                // Validation Check
                if($_GET["report_email"] == "1")
                    $notifications[] = "report";
            }

            // Conditions (Add-ons)
            $conditions = [];

            $core = new my_quota_dropstr_quota_core();
            $snaps = $core->get_snaps();

            if(isset($snaps) && !empty($snaps))
            {

                // Yoast SEO Plugin
                if(isset($snaps["wordpress-seo"]) && !empty($snaps["wordpress-seo"]))
                {

                    // Keyword Score
                    if(isset($_GET["wordpress-seo-keyword"]) && !empty($_GET["wordpress-seo-keyword"]))
                    {

                        $conditions["wordpress_seo"]["keyword_score"] = sanitize_text_field( $_GET["wordpress-seo-keyword"] );
                    }

                    // Readability Score
                    if(isset($_GET["wordpress-seo-readability"]) && !empty($_GET["wordpress-seo-readability"]))
                    {

                        $conditions["wordpress_seo"]["readability_score"] = sanitize_text_field( $_GET["wordpress-seo-readability"] );
                    }

                }

            }

            $args = array(

                'name' => $name,
                'users' => $users,
                'editors' => $editors,
                'quotas' => $quotas,
                'timeframe' => $timeframe,
                'timeframe_args' => $timeframe_args,
                'time_args' => $time_args,
                'start_date' => $start_date,
                'notifications' => $notifications,
                'conditions' => $conditions

            );


            // Return Array
            if(isset($quota_group))
                $check = $this->update_quota_group($quota_group["id"], $args);
            else
                $check = $this->update_quota_group(null, $args);

            // Send update message
            $errors[] = $check[1];
            $html .= $this->get_errors($errors);


            // If valid, get quota id
            if($check[1] == 0)
                $quota_id = $check[0];

        }

        // if id set, get group data
        if(isset($quota_id) && !empty($quota_id))
            $quota_group = $this->get_quota_group($quota_id);
        else
            $quota_group["id"] = null;

        // if set, send quota_id
        if(isset($quota_id) && !empty($quota_id))
            $html .= $this->set_form_id($quota_id);

        // Get Quota Name
        $html .= $this->get_quota_name($quota_group);

        // Get Quota Users
        $html .= $this->set_users($quota_group);

        // Get Quota Editors
        $html .=  $this->set_editors($quota_group);

        // Set left side footer
        $html .= $this->set_left_footer();

        // Set table footer
        $html .= $this->set_table_footer();

        // Set Right hand side header
        $html .= $this->set_right_side();

        // Set Quota Options
        $html .= $this->set_quota_options($quota_group);

        // Set timeframe
        $html .= $this->set_timeframe($quota_group);

        // Set Reminders
        $html .= $this->set_reminders($quota_group);

        // Set right hand side footer
        $html .= $this->set_right_footer();

        // Set form footer
        $html .= $this->set_page_footer();
        // Save/Update Button
        $html .= $this->set_save_button();

        $html .= $this->set_form_footer();

        // Add calendar picker
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');

        print $html;

    }

    /**
     * Get Title
     * @print string
     */
    function get_title($command)
    {

        // New Quota Group Selected
        if(isset($command) && $command =="new")
        {
            $output = '<h1 class="wp-heading-inline">New Quota Group</h1>';
        }

        // If Edit Quot Group
        if(isset($command) && $command =="edit")
        {
            $output = '<h1 class="wp-heading-inline">Edit Quota Group</h1>';
        }

        if(!isset($command) || empty($command))
        {
            $output = '<h1 class="wp-heading-inline">Quota Groups</h1>'.
                '<a href="?page=myquota-editor&c=new" class="page-title-action">Add New</a>'.
                '<hr class="wp-header-end">'.
                '<h2 class="screen-reader-text">Quota Groups</h2>';

        }

        print $output;
    }

    /**
     * Get Table Header / Footer
     * @return string
     */
    private function get_table_header($location, $args)
    {
        if($location == 'top')
        {
            $htmlOutput = '<h2 class="screen-reader-text">Status list</h2><table class="wp-list-table widefat fixed striped users">'
                . '<thead>'
                . '<tr>'
                . '<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td><th scope="col" id="workflowname" class="manage-column column-username column-primary sortable ' . $args["alt_order"] . '"><a href="admin.php?page=myquota-editor&orderby=' . $args["orderby"] . '&amp;order=' . $args["alt_order"] . '"><span>Name</span><span class="sorting-indicator"></span></a></th><th scope="col" id="types" class="manage-column column-role">Users</th></tr>
                                    </thead>';
        }


        if($location == 'bottom')
        {
            $htmlOutput = '</tbody><tfoot><tr><td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td><th scope="col" class="manage-column column-username column-primary sortable ' . $args["alt_order"] . '"><a href="admin.php?page=myquota-editor&orderby=' . $args["orderby"] . '&amp;order=' . $args["alt_order"] . '"><span>Name</span><span class="sorting-indicator"></span></a></th><th scope="col" id="types" class="manage-column column-role">Users</th></tr></tfoot></table>';
        }


        return $htmlOutput;

    }

    /**
     * Get Quotas
     * @param $quota_id
     * @return array
     */
    public function get_quota_groups($quota_id = NULL)
    {
        global $wpdb;
        $get_one = false;

        if(empty($quota_id))
        {
            $get_quotas = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s AND meta_key = %s", '0', 'groups'));

            // Loop Quota data
            foreach ($get_quotas as $quotas)
            {
                $quota_data = $quotas->meta_value;
            }
        }
        else
        {
            $get_one = true;

        }

        if($get_one == false)
        {
            if(isset($quota_data) && !empty($quota_data))
            {

                // json decode the array
                $quota_groups =json_decode($quota_data, true);
                $quota_group = [];

                $i = 0;
                foreach ($quota_groups as $id)
                {
                    $get_quota = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s AND meta_key = %s", $id, 'group'));

                    if(!empty($get_quota))
                    {


                        foreach ($get_quota as $quota)
                        {

                            $quota_group[] = json_decode($quota->meta_value, true);
                            $quota_group[$i]["id"] = $id;


                        }

                    }

                    $i++;
                }
            }
            else
            {
                // No data, return empty array
                return $quota_groups = [];
            }
        }
        else
        {
            $get_quota = $wpdb->get_results($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}quotas WHERE id = %s", $quota_id));

            if(!empty($get_quota))
            {

                $i = 0;
                foreach ($get_quota as $quota)
                {

                    $quota_group[] = json_decode($quota->meta_value, true);
                    $quota_group[$i]["id"] = $quota_id;
                    $i++;

                }

            }

        }

        return $quota_group;


    }

    /**
     * Get Quota Table
     * @print string
     */
    function get_quota_list($args)
    {

        // Get quota groups
        $quota_groups = $this->get_quota_groups();


        // Check args
        $checkedArgs = $this->check_args($args);


        // header
        $htmlOutput = $this->get_header();
        // form creation
        //$htmlOutput .= '<form method="get">';
        // wp security check
        $htmlOutput .= wp_nonce_field();

        // action bar
        $htmlOutput .= $this->get_action_bar($quota_groups, "top");
        //

        // Set counter to 0
        $allCounter = 0;


        // Html Table header
        $htmlOutput .= $this->get_table_header('top', $checkedArgs);


        // Rows Output
        $htmlOutput .= '<tbody id="the-list" data-wp-lists="list:user">';

        $row = 1;

        // if 0 quota groups
        if (empty($quota_groups))
        {
            $htmlOutput .= '<tr id="user-' . $row . '"><th scope="row" class="check-column"><label class="screen-reader-text" for="user_' . $row . '"></label></th><td class="username column-username has-row-actions column-primary" data-colname="Username"><strong>No quota groups found, <a href="?page=myquota-editor&c=new">create one</a>.</strong><br><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class="role column-role" data-colname="Role"></td></tr>';
        }


        $status_label = [];

        // Sort groups based on order
        if (isset($quota_groups) && is_array($quota_groups))
        {
            foreach ($quota_groups as $groups)
            {

                if(is_array($groups))
                {

                    foreach ($groups as $group)
                    {

                        // Get status label
                        $status_label[$groups["name"]] = $groups;
                    }



                }


            }

        }



        // Get type of order
        if (!empty($status_label))
        {

            if ($args["order"] == "desc")
            {
                // descending by name
                krsort($status_label);

            }
            else
            {
                // Ascending by name
                ksort($status_label);
            }


            // Return ordered groups

            foreach ($status_label as $sortGroup)
            {


                // Delete Group wp nonce
                $deleteLink = wp_nonce_url('admin.php?page=myquota-editor&action=delete&amp;status_id=' . $sortGroup["id"] . '', 'delete', 'delete_status');

                //Edit Group Policies
                $editLink = wp_nonce_url('admin.php?page=myquota-editor&c=edit&action=edit&amp;status_id=' . $sortGroup["id"] . '', 'edit', 'edit_status');

                // Output row
                $htmlOutput .= '<tr id="user-' . $row . '"><th scope="row" class="check-column"><label class="screen-reader-text" for="user_' . $row . '">Select ' . $sortGroup["name"] . '</label><input type="checkbox" name="groups[]" id="user_' . $row . '" class="editor" value="' . $row . '"></th><td class="username column-username has-row-actions column-primary" data-colname="Username"><strong><a href="' . $editLink . '">' . $sortGroup["name"] . '</a></strong><br><div class="row-actions"><span class="edit"><a href="' . $editLink . '">Edit</a> | </span><span class="delete"><a class="submitdelete" href="' . $deleteLink . '">Delete</a> </span></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>';


                // If public
                $htmlOutput .= '<td class="types column-types" data-colname="Types">';

                if(isset($sortGroup["users"]))
                {
                    if ($sortGroup["users"] == 0 || empty($sortGroup["users"]))
                    {
                        $htmlOutput .= "n/a";
                    }
                    else
                    {
                        // Show top three users
                        $quota_users = $this->get_quota_users($sortGroup["users"]);

                        if(isset($quota_users) && is_array($quota_users) && !empty($quota_users))
                        {
                            // More than 1
                            if(count($quota_users) > 1)
                            {

                                // if the count is 2
                                if(count($quota_users) <= 2)
                                {
                                    $htmlOutput .= implode(",", $quota_users);
                                }
                                else
                                {
                                    for($i=0;$i<=2;$i++)
                                    {
                                        $output[] = $quota_users[$i];

                                    }

                                    $htmlOutput .= implode(",", $output);
                                }

                            }
                            else
                            {
                                // Only 1 Group
                                foreach ($quota_users as $user)
                                {
                                    $htmlOutput .= $user;
                                }

                            }
                        }




                    }
                }

                $htmlOutput .= '</td>';

                $htmlOutput .= '</tr>';




            }
        }

        // Html Table footer
        $htmlOutput .= $this->get_table_header('bottom', $checkedArgs);

        $htmlOutput .= $this->get_action_bar($quota_groups, "bottom");


        print $htmlOutput;



    }
}