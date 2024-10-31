<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 10/15/20
 * Time: 1:20 PM
 */

class my_quota_dropstr_roster extends my_quota_dropstr_quota_list
{

    /**'
     * Get Quota by User ID
     * @param $user_id
     * @return array
     */
    private function get_quota_by_user($user_id, $quotas, $wp_roles, $groups = null)
    {

        // set
        $user_quota = [];

        foreach ($quotas as $quota)
        {

            if(isset($user_id) && !empty($user_id))
            {

                // First try, the user id is in the group
                if(in_array($user_id, $quota["users"]))
                {

                    $user_quota[] = $quota;

                }

                // If user is in a role
                if(isset($wp_roles) && !empty($wp_roles))
                {

                    // Get user role by id
                    $user_meta=get_userdata($user_id);

                    $user_roles = $user_meta->roles;

                    // Check in the users if a group or role
                    if(isset($user_roles))
                    {

                        foreach ($user_roles as $role)
                        {
                            if(in_array($role, $quota["users"]))
                            {

                                $user_quota[] = $quota;

                            }
                        }
                    }



                }

                // If other groups are set
                if(isset($groups) && !empty($groups))
                {

                    if(is_array($groups))
                    {

                        foreach ($groups as $name => $group)
                        {

                            // If Edit Flow Group
                            if($name == 'ef')
                            {
                                if(is_array($group))
                                {

                                    foreach ($group as $item)
                                    {


                                        // Get Users if no empty
                                        if(isset($item["meta_data"]["user_ids"]) && !empty($item["meta_data"]["user_ids"]))
                                        {
                                            if(in_array($user_id, $item["meta_data"]["user_ids"]))
                                            {


                                                $edit_flow_id = $item["id"];
                                                $edit_flow_id = 'ef_'.$edit_flow_id;

                                                if(in_array($edit_flow_id, $quota["users"]))
                                                    $user_quota[] = $quota;

                                            }

                                        }
                                    }

                                }

                            }

                            // If Publish Press Group
                            if($name == 'pp')
                            {
                                if(is_array($group))
                                {

                                    foreach ($group as $item)
                                    {


                                        // Get Users if no empty
                                        if(isset($item["meta_data"]["user_ids"]) && !empty($item["meta_data"]["user_ids"]))
                                        {
                                            if(in_array($user_id, $item["meta_data"]["user_ids"]))
                                            {


                                                $publishpress_id = $item["id"];
                                                $publishpress_id = 'pp_'.$publishpress_id;

                                                if(in_array($publishpress_id, $quota["users"]))
                                                    $user_quota[] = $quota;

                                            }

                                        }
                                    }

                                }

                            }

                        }
                    }

                }

            }


        }


        return $user_quota;


    }

    /**
     * Get list of quota users by editor id
     * @param $editor_id
     * @return array
     */
    private function get_quota_users_by_editor($editor_id)
    {
        // Set users
        $users = [];

        $core = new my_quota_dropstr_quota_core();

        // Get Quotas
        $quotas = $this->get_quota_groups();

        if(isset($quotas) && !empty($quotas))
        {
            foreach ($quotas as $quota)
            {
                if(isset($quota["editors"]) && !empty($quota["editors"]))
                {

                    // Get editor ids by group
                    $editors_ids = $core->get_user_ids($quota["editors"]);

                    // Check if editor is in group
                    if(in_array($editor_id, $editors_ids))
                    {
                        // Get quota users
                        $users = $core->get_user_ids($quota["users"]);
                    }
                }
            }
        }

        return $users;

    }

    /**
     * Get Current Posts from User
     * @param $user
     * @param $user_quotas
     * @return string
     */
    private function get_current_user_posts($user, $user_quotas)
    {
        $core = new my_quota_dropstr_quota_core();

        $output = '';

        if(isset($user_quotas) && !empty($user_quotas))
        {


            $user_posts_data =  $core->check_quota_user($user, $user_quotas, 'data');


            if(isset($user_posts_data) && !empty($user_posts_data))
            {
                // get post ids (if)
                foreach ($user_posts_data as $user_posts)
                {
                    foreach ($user_posts as $user_post)
                    {

                        if(isset($user_post["post_ids"]) && !empty($user_post["post_ids"]))
                        {
                            foreach ($user_post["post_ids"] as $post_id)
                            {
                                // Get title
                                $output .= '<p><a href="'.get_post_permalink($post_id).'">'.get_the_title($post_id).'</a></p>';
                            }

                        }
                    }
                }

            }


        }
        return $output;
    }


    // Check args coming from Roster table
    public function roster_check_args($args)
    {

        // Sort A-Z
        if (!empty($args["order"]))
        {
            if (!$args["order"] == 'desc' || !$args["order"] == 'asc')
            {
                $args["order"] = 'asc';
                $args["alt_order"] = 'desc';
            }
            else
            {

                if ($args["order"] == 'desc')
                {
                    $args["alt_order"] = 'asc';
                }
                else
                {
                    $args["order"] = 'desc';
                    $args["alt_order"] = 'asc';
                }
            }

        }
        else
            {
                // Set Default order
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

        //Set Page
        if (empty($args["page"]))
        {
            $args["page"] = '1';
        }



        return $args;

    }

    /**
     * Get Default List View
     */
    public function get_list_view($args = NULL)
    {


        $output = '<h1 class="wp-heading-inline">Quota Roster</h1>';

        // Get Quota Groups
        $quotas = new my_quota_dropstr_quota_list();
        $quota_list = $quotas->get_quota_groups();

        // Get WP Roles
        $wp_roles =  $quotas->get_site_roles();

        $groups = [];


        //Update Snap Status
        $snap = new my_quota_dropstr_quota_core();

        // IF enabled, get other groups
        if ( $snap->is_snap_active('edit-flow') )
        {

            $ef_plugin = new my_quota_dropstr_edit_flow_plugin();

            // get edit flow groups
            $groups["ef"] = $ef_plugin->get_edit_flow_groups();
        }

        // If Publish Press
        if ( $snap->is_snap_active('publishpress') )
        {

            $pp_plugin = new my_quota_dropstr_publishpress_plugin();

            // get edit flow groups
            $groups["pp"] = $pp_plugin->get_publishpress_groups();
        }


        // Get Users
        // Get all users in WP role

        if(isset($args["editor"]) && !empty($args["editor"]))
            $editor_id = $args["editor"];
        else
            {
                // No editor, must be admin
                $users = get_users();

            }

        // Check if editor is user
        if(isset($editor_id) && !empty($editor_id))
        {
            // Get quota users based on editor id
            $_users = $this->get_quota_users_by_editor($editor_id);

            // If no users found, user not editor
            if(isset($_users) && empty($_users))
            {
                // Get Current User info
                $users[] = wp_get_current_user();
            }
            else
                {


                    // Get Users from array of user ids
                    $users_args = array(
                    'include' =>  $_users,
                    'fields'   => ['ID', 'display_name']
                );

                    $users = get_users( $users_args );
                }

        }

        // Sort User
        if(isset($users) && !empty($users))
        {
            // Sorting Users
            $_sort_users = [];

            // Sort based on display_name
            if(isset($args['order']) && !empty($args['order']))
            {
                // Get sort order
                foreach ($users as $_user)
                {

                    $_sort_users[$_user->display_name] = $_user;


                }


            }

            // Sort Ascending (default)
            if($args["order"] == "asc")
                ksort($_sort_users);
            else
                krsort($_sort_users);

        }



        // Show Quota Users
        if(!empty($users))
        {

            $quota_users = [];

            $action_bar_users = [];

            // Set user per page limit
            $user_limit = 25;

            //Get Page offset
            $pages = ceil(count($_sort_users) / $user_limit);

            // Get starting point
            $user_offset = 1;

            for($i=1; $i < $args["paged"]; $i++ )
            {
                $user_offset += $user_limit;
            }

            // Show each users from WP role
            $i = 1;
            $x = 1;
            foreach ($_sort_users as $user)
            {

                // Get users quota groups
                $user_quotas = $this->get_quota_by_user($user->ID, $quota_list, $wp_roles, $groups);

                if(isset($user_quotas) && !empty($user_quotas))
                {
                    // Get Offset for paging
                    if(($i >= $user_offset) && ($x <= $user_limit))
                    {
                        $quota_users[] = $user;
                        $x++;
                    }

                    //Action bar count
                    $action_bar_users[] = $user;
                    $i++;

                }


            }
        }


        // Top Action Bar
        $output .= $this->get_action_bar($action_bar_users, 'top', $args, $pages);

        $output .= '<h2 class="screen-reader-text">Quota list</h2><table class="wp-list-table widefat fixed striped users">'
            . '<thead>'
            . '<tr>'
            . '<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td><th scope="col" id="workflowname" class="manage-column column-username column-primary sortable ' . $args["order"] . '"><a href="admin.php?page=myquota&orderby=' . $args["orderby"] . '&amp;order=' . $args["alt_order"] . '"><span>Name</span><span class="sorting-indicator"></span></a></th><th scope="col" id="requirement" class="manage-column column-role">Posts</th><th scope="col" id="requirement" class="manage-column column-role">Quota</th><th scope="col" id="due" class="manage-column column-role">Due</th><th scope="col" id="Group" class="manage-column column-role">Group</th></tr>
                                    </thead>';


        // Get Archives for users if set
        if(isset($_GET["quota"]) && $_GET["quota"] == "last")
        {
            $archive = new my_quota_dropstr_archive_class();

            foreach($quota_users as $user)
            {
                $user_ids[] = $user->ID;
            }

            $user_records = $archive->get_users_archives($user_ids, 0, 1);

        }


        // If users is not empty, show role and users
        if(!empty($quota_users))
        {

            // Get Quota Amount Needed for User
            $core = new my_quota_dropstr_quota_core();

            // Show each users from WP role
            foreach($quota_users as $user)
            {
                // Get Quota Data for User
                $editLink = wp_nonce_url('admin.php?page=myquota&archive=user&action=edit&amp;user_id=' . $user->ID . '', 'edit', 'edit_status');

                //Send Notification Reminder
                //$notifyLink = wp_nonce_url('users.php?page=myquota&c=edit&action=edit&amp;user_id=' . $user->ID . '', 'notify', 'edit_status');

                $output .= '<tr id="user-' . $user->ID . '"><th scope="row" class="check-column"><label class="screen-reader-text" for="user_' . $user->ID . '">Select ' . $user->display_name . '</label><input type="checkbox" name="users[]" id="user_' . $user->ID . '" class="editor" value="' . $user->ID . '"></th><td class="username column-username has-row-actions column-primary" data-colname="Username"><strong><a href="' . $editLink . '">' . $user->display_name . '</a></strong><br><div class="row-actions"><span class="edit"><a href="' . $editLink . '">History</a> | </span><span class="delete"></span></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';


                // Get current or last Quota Record

                    $user_quotas = $this->get_quota_by_user($user->ID, $quota_list, $wp_roles, $groups);  // Get users quota groups



                // Get Posts
                $output .= '</td><td class="types column-types" data-colname="posts">';

                // If archives
                if(isset($_GET["quota"]) && $_GET["quota"] == "last")
                {

                    $output .= $archive->get_user_posts($user->ID, $user_records);
                }
                else
                    {
                        $output .=  $this->get_current_user_posts($user->ID, $user_quotas);

                    }


                // Quota
                $output .= '</td><td class="types column-types" data-colname="Quota">';

                // Archived Quota
                if(isset($_GET["quota"]) && $_GET["quota"] == "last")
                {

                    $output .= $archive->get_user_archive_quota($user->ID, "quotas", $user_records);
                }
                else
                    {
                        // Current Quota
                        if(isset($user_quotas) && !empty($user_quotas))
                        {

                            $output .= $core->check_quota_user($user->ID, $user_quotas, 'html');
                        }
                    }




                // Closing Quota
                $output .= '</td>';

                // Due Date
                $output .= '<td class="types column-types" data-colname="Due">';

                if(isset($_GET["quota"]) && $_GET["quota"] == "last")
                {

                    $output .= $archive->get_user_archive_quota($user->ID, "due_date", $user_records);
                }
                else
                    {
                        if(isset($user_quotas) && !empty($user_quotas))
                            $output .=  $core->get_due_date($user_quotas);
                    }


                $output .= '</td>';



                // Quota Group
                $output .= '<td class="types column-types" data-colname="Group">';

                if(isset($user_quotas) && !empty($user_quotas))
                {

                    if(is_array($user_quotas))
                    {

                        foreach ($user_quotas as $user_quota)
                        {
                            // get names
                            $quota_names[] = $user_quota["name"];

                        }

                        $output .= implode(', ', $quota_names);
                        unset($quota_names);
                    }


                }

                $output .= '</td>';

                $output .= '</tr>';

                unset($user_quotas);

            }



        }
        else
            {

                // Show No users, create group
                $output .= '<tr id="user-0"><th scope="row" class="check-column"></th><td class="username column-username has-row-actions column-primary" colspan="4" data-colname="Username"><strong>No quota users found, <a href="admin.php?page=myquota-editor">create a group first</a>.</strong></td></tr>';

            }



        $output .= '</tbody><tfoot><tr><td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td><th scope="col" class="manage-column column-username column-primary sortable ' . $args["alt_order"] . '"><a href="admin.php?page=myquota&orderby=' . $args["orderby"] . '&amp;order=' . $args["alt_order"] . '"><span>Name</span><span class="sorting-indicator"></span></a></th><th scope="col" id="requirement" class="manage-column column-role">Posts</th><th scope="col" id="requirement" class="manage-column column-role">Quota</th><th scope="col" id="due" class="manage-column column-role">Due</th><th scope="col" id="Group" class="manage-column column-role">Group</th></tr></tfoot></table>';


        // Top Action Bar
        $output .= $this->get_action_bar($action_bar_users, 'bottom', $args, $pages);



        return $output;


    }

}