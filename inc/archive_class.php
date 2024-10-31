<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 11/16/20
 * Time: 2:26 PM
 */

class my_quota_dropstr_archive_class extends my_quota_dropstr_quota_list
{


    /**
     * Get User's Record Archive
     * @param $user_id
     * @param $args
     */
    function get_user_archive($user_id)
    {
        $user_record = [];

        $dropstr = new my_quota_dropstr_quota_core();


            $commands = array("get_user_data" => array("user_id" => $user_id, "type" => "record"));
            $_user_record = $dropstr->dropstr_api_call($commands);

            if(isset($_user_record) && !empty($_user_record))
            {

                if(isset($_user_record["get_user_data"]["records"]) && !empty($_user_record["get_user_data"]["records"]))
                    $user_record[] = $_user_record["get_user_data"]["records"];
            }


        return $user_record;


    }

    /**
     * Get Users Record Archives
     * @param $users
     * @param $offset
     * @param $limit
     * @return array
     */
    function get_users_archives($users, $offset, $limit)
    {
        $user_records = [];

        $dropstr = new my_quota_dropstr_quota_core();

        //$settings = $dropstr->get_myquota_settings();

        /*if($dropstr->is_api_validated() && (isset($settings["records"]) && $settings["records"] == "Dropstr"))
        {*/

            $commands = array("get_users_data" => array( "type" => "record", "offset" => $offset, "limit" => $limit));
            $data = array("user_ids" => $users);
            $_user_record = $dropstr->dropstr_api_call($commands, $data);

            if(isset($_user_record) && !empty($_user_record))
            {

                if(isset($_user_record["get_users_data"]) && !empty($_user_record["get_users_data"]))
                    $users_records = $_user_record["get_users_data"];


                // Set offset and limits
                if(isset($users_records) && !empty($users_records))
                {

                    // Loop returned data
                    foreach ($users_records as $item)
                    {

                        if(is_array($item))
                        {

                            krsort($item["records"]); // get last records first
                            $user_records[] = array("user_id" => $item["user_id"], "records" => array_slice($item["records"], $offset, $limit));
                        }

                    }


                }

            }


        return $user_records;
    }

    function get_user_posts($user_id, $records)
    {
        $output = '';

        if(isset($user_id) && !empty($user_id))
        {

            if(is_array($records))
            {

                foreach ($records as $record)
                {

                        // Find Record that matches
                        if($record["user_id"] == $user_id)
                        {

                            // Get records for user
                            if(isset($record["records"]) && !empty($record["records"]))
                            {
                                foreach ($record["records"] as $record_item)
                                {

                                    foreach ($record_item as $item)
                                    {

                                        // Get optional posts (if any)
                                        if(isset( $item["or"]["post_ids"] ) && !empty( $item["or"]["post_ids"]))
                                        {

                                            foreach ($item["or"]["post_ids"] as $post_id)
                                            {
                                                $output .= '<p><a href="'.get_post_permalink($post_id).'">'.get_the_title($post_id).'</a></p>';
                                            }

                                        }

                                        // Get required posts (if any)
                                        if(isset( $item["required"]["post_ids"] ) && !empty( $item["required"]["post_ids"]))
                                        {

                                            foreach ($item["required"]["post_ids"] as $post_id)
                                            {
                                                $output .= '<p><a href="'.get_post_permalink($post_id).'">'.get_the_title($post_id).'</a></p>';
                                            }

                                        }

                                    }

                                }

                            }

                        }

                }
            }
        }


        return $output;

    }

    function get_user_archive_quota($user_id, $type, $records)
    {

        $output = '';

        if(isset($user_id) && !empty($user_id))
        {

            if(is_array($records))
            {

                foreach ($records as $record)
                {

                    // Find Record that matches
                    if($record["user_id"] == $user_id)
                    {

                        // Get records for user
                        if(isset($record["records"]) && !empty($record["records"]))
                        {
                            foreach ($record["records"] as $record_item)
                            {


                                if(isset($record_item["met"]["or"]) && $type == "quotas")
                                {
                                    $quota_met = true;

                                    if($record_item["met"]["or"] == "0")
                                        $quota_met = false;

                                    // If quota's and not the flag check
                                    if(is_array($record_item["posts"]["or"]))
                                    {

                                        $output .= '<div class="or-quota">';

                                        foreach ($record_item["posts"]["or"] as $quotas)
                                        {
                                            if(!is_array($quotas))
                                            {
                                                // If quota isn't met (all, not just one)
                                                if(isset($quota_met) && $quota_met == false)
                                                {
                                                    $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>';
                                                }
                                                else
                                                {
                                                    // Will need to parse the data to find the true one and the failed one.
                                                    // post pending - 0/1
                                                    $split = explode("-", $quotas);

                                                    // Remove space in front
                                                    $_quota_vals  = substr($split[1], 1);

                                                    // Explode with / delimiter
                                                    $post_vals = explode("/", $_quota_vals);

                                                    // If the post count is greater or equal the required amount
                                                    if($post_vals[0] >= $post_vals[1])
                                                    {
                                                        $output .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';

                                                    }
                                                    else
                                                    {

                                                        // This post type was not met but some other post was
                                                        $output .= '<span  class="dashicons dashicons-marker"></span>';
                                                    }


                                                }


                                                $output .= $quotas.'</br>'; // only quotas, not ids
                                            }

                                        }
                                        $output .= '</div>';
                                    }
                                }



                                if(isset($record_item["met"]["required"]) && $type == "quotas")
                                {
                                    $quota_met = true;

                                    if ($record_item["met"]["required"] == "0")
                                        $quota_met = false;


                                    if(is_array($record_item["posts"]["required"]))
                                    {
                                        foreach ($record_item["posts"]["required"] as $quotas)
                                        {
                                            if(!is_array($quotas))
                                            {
                                                // If quota isn't met (all, not just one)
                                                if($quota_met == false)
                                                    $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>';
                                                else
                                                    $output .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';

                                                $output .= $quotas.'</br>'; // only quotas, not ids
                                            }

                                        }
                                    }

                                }

                                /*foreach ($record_item as $item)
                                {


                                    // Or quota
                                    if(isset($item["or"]) && $type == "quotas")
                                    {


                                        if(!is_array($item["or"]))
                                        {

                                            // Quota Not Met
                                            if($item["or"] == '0')
                                                $quota_met = false;

                                        }

                                        // If quota's and not the flag check
                                        if(is_array($item["or"]))
                                        {

                                            $output .= '<div class="or-quota">';

                                            foreach ($item["or"] as $quotas)
                                            {
                                                if(!is_array($quotas))
                                                {
                                                    // If quota isn't met (all, not just one)
                                                    if(isset($quota_met) && $quota_met == false)
                                                    {
                                                        $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>';
                                                    }
                                                    else
                                                        {
                                                            // Will need to parse the data to find the true one and the failed one.
                                                            // post pending - 0/1
                                                            $split = explode("-", $quotas);

                                                            // Remove space in front
                                                            $_quota_vals  = substr($split[1], 1);

                                                            // Explode with / delimiter
                                                            $post_vals = explode("/", $_quota_vals);

                                                            // If the post count is greater or equal the required amount
                                                            if($post_vals[0] >= $post_vals[1])
                                                            {
                                                                $output .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';

                                                            }
                                                            else
                                                                {

                                                                    // This post type was not met but some other post was
                                                                    $output .= '<span  class="dashicons dashicons-marker"></span>';
                                                                }


                                                        }


                                                    $output .= $quotas.'</br>'; // only quotas, not ids
                                                }

                                            }
                                            $output .= '</div>';
                                        }
                                    }*/

                                    // If required
                                    /*if(isset($item["required"]) && $type == "quotas")
                                    {

                                        $quota_met = true;

                                        if(!is_array($item["required"]))
                                        {

                                            // Quota Not Met
                                            if($item["required"] == '0')
                                                $quota_met = false;

                                        }

                                        if(is_array($item["required"]))
                                        {
                                            foreach ($item["required"] as $quotas)
                                            {
                                                if(!is_array($quotas))
                                                {
                                                    // If quota isn't met (all, not just one)
                                                    if($quota_met == false)
                                                        $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>';
                                                    else
                                                        $output .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';

                                                    $output .= $quotas.'</br>'; // only quotas, not ids
                                                }

                                            }
                                        }


                                    }

                                }*/

                                if(isset($record_item["due_date"]) && $type == "due_date")
                                {

                                    $output = $record_item["due_date"];
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
     * Get Archive Header
     * @return string
     */
    private function get_archive_header($user_name)
    {

        $output = '<h1 class="wp-heading-inline">'.$user_name.' History</h1>';


        return $output;

    }

    /**
     * // Get Archive Table Header/Footer
     * @param $location
     * @param $args
     * @return string
     */
    private function get_archive_table_header($location, $args)
    {
        if($location == 'top')
        {
            $htmlOutput = '<h2 class="screen-reader-text">History list</h2><table class="wp-list-table widefat fixed striped users">'
                . '<thead>'
                . '<tr>'
                . '<td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td><th scope="col" id="workflowname" class="manage-column column-username column-primary sortable ' . $args["alt_order"] . '"><a href="admin.php?page=myquota-editor&orderby=' . $args["orderby"] . '&amp;order=' . $args["order"] . '"><span>Date Range</span><span class="sorting-indicator"></span></a></th><th scope="col" id="posts" class="manage-column column-role">Post Title</th><th scope="col" id="types" class="manage-column column-role">Quota Met</th><th scope="col" id="types" class="manage-column column-role">Details</th></tr>
                                    </thead>';
        }


        if($location == 'bottom')
        {
            $htmlOutput = '</tbody><tfoot><tr><td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td><th scope="col" class="manage-column column-username column-primary sortable ' . $args["alt_order"] . '"><a href="admin.php?page=myquota-editor&orderby=' . $args["orderby"] . '&amp;order=' . $args["order"] . '"><span>Date Range</span><span class="sorting-indicator"></span></a></th><th scope="col" id="posts" class="manage-column column-role">Post Title</th><th scope="col" id="types" class="manage-column column-role">Quota Met</th><th scope="col" id="types" class="manage-column column-role">Details</th></tr></tfoot></table>';
        }


        return $htmlOutput;

    }

    public function get_archive_view($user_id, $args)
    {
        $pages = 0;

        // Set Query
        $args["query"] = '&archive=user&action=edit&user_id='.$user_id.'&edit_status='.$_GET["edit_status"].'';

        // Get User Archive
        if(isset($user_id)&& !empty($user_id))
            $user_records = $this->get_user_archive($user_id);

        if(isset($args) && !empty($args))
            // Check args
            $checkedArgs = $this->check_args($args);


        // Set Pages
        if(isset($user_records) && !empty($user_records))
        {

            $quota_users = [];

            $action_bar_users = [];

            // Set user per page limit
            $record_limit = 25;

            //Get Page offset
            $pages = ceil(count($user_records[0]) / $record_limit);

            // Get starting point
            $user_offset = 1;

            for($i=1; $i < $args["paged"]; $i++ )
            {
                $user_offset += $record_limit;
            }

            // Show each users from WP role
            $i = 1;
            $x = 1;
            foreach ($user_records[0] as $user_record_data)
            {

                if(isset($user_record_data) && !empty($user_record_data))
                {
                    // Get Offset for paging
                    if(($i >= $user_offset) && ($x <= $record_limit))
                    {
                        $quota_users[] = $user_record_data;
                        $x++;
                    }

                    //Action bar count
                    $action_bar_users[] = $user_record_data;
                    $i++;

                }


            }
        }


        $user_meta = get_userdata($user_id);
        $user_name = $user_meta->display_name;

        // Get Header
        $output = $this->get_archive_header($user_name);

        // Top Action Bar
        if(isset($user_records) && !empty($user_records))
            $output .= $this->get_action_bar($user_records[0], 'top', $args, $pages);
        else
            $output .= $this->get_action_bar($user_records, 'top', $args, $pages);

        $output .= $this->get_archive_table_header('top', $checkedArgs);

        // Rows Output
        $output .= '<tbody id="the-list" data-wp-lists="list:user">';

        // list Archive
        if(isset($quota_users) && !empty($quota_users))
        {


                    // Reverse Array (if no API)
            /*$core = new my_quota_dropstr_quota_core();

            if($core->is_api_validated())
                $reversed_user_record = $quota_users;
            else*/
                $reversed_user_record = array_reverse($quota_users);

                    $i = 0;
                    foreach ($reversed_user_record as $item)
                    {

                        // Date Range

                        //Delete Quota
                        //$deleteLink = wp_nonce_url('users.php?page=myquota&c=edit&action=edit&amp;record_id=' . $i . '', 'delete', 'edit_status');

                        $date_range = $item["start_date"].' - '.$item["due_date"];

                        $output .= '<tr id="record-' . $i . '"><th scope="row" class="check-column"><label class="screen-reader-text" for="record_' . $i . '">Select ' . $date_range . '</label><input type="checkbox" name="record[]" id="record_' . $i . '" class="editor" value="' . $i . '"></th><td class="username column-username has-row-actions column-primary" data-colname="Username"><strong>' . $date_range . '</strong><br><div class="row-actions"></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>';


                        // List Post Titles
                        $output .= '<td class="types column-types" data-colname="Quota">';
                        // if posts are found

                        // Or posts
                        if(isset($item["posts"]["or"]["post_ids"]))
                        {

                            foreach ($item["posts"]["or"]["post_ids"] as $post_id)
                            {

                                // Get title
                                $output .= '<p><a href="'.get_post_permalink($post_id).'">'.get_the_title($post_id).'</a></p>';
                            }

                            unset($item["posts"]["or"]["post_ids"]);
                        }

                        // Required Posts
                        if(isset($item["posts"]["required"]["post_ids"]))
                        {

                            foreach ($item["posts"]["required"]["post_ids"] as $post_id)
                            {

                                // Get title
                                $output .= '<p><a href="'.get_post_permalink($post_id).'">'.get_the_title($post_id).'</a></p>';
                            }

                            unset($item["posts"]["or"]["post_ids"]);
                        }


                        $output .= '</td>';

                        // Quota Met/Failed
                        $output .= '<td class="types column-types" data-colname="Quota">';


                        // Loop for Quotas
                        foreach ($item["met"] as $met_key => $met_value)
                        {
                            $met_flag = true;

                            if($met_key == "or")
                            {

                                if($met_value == "0")
                                {
                                    $met_flag = false;
                                }

                                if(isset($item["posts"]["or"]) && !empty($item["posts"]["or"]))
                                {

                                    $output .= '<div class="or-quota">';

                                    foreach ($item["posts"]["or"] as $quotas)
                                    {
                                        if(!is_array($quotas))
                                        {
                                            // If quota isn't met (all, not just one)
                                            if($met_flag == false)
                                            {
                                                $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>';
                                            }
                                            else
                                                {
                                                    // Will need to parse the data to find the true one and the failed one.
                                                    // post pending - 0/1
                                                    $split = explode("-", $quotas);

                                                    // Remove space in front
                                                    $_quota_vals  = substr($split[1], 1);

                                                    // Explode with / delimiter
                                                    $post_vals = explode("/", $_quota_vals);

                                                    // If the post count is greater or equal the required amount
                                                    if($post_vals[0] >= $post_vals[1])
                                                    {
                                                        $output .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';

                                                    }
                                                    else
                                                        {

                                                            // This post type was not met but some other post was
                                                            $output .= '<span  class="dashicons dashicons-marker"></span>';
                                                        }


                                                }


                                            $output .= $quotas.'<br/>'; // only quotas, not ids
                                        }

                                    }

                                    // Close div
                                    $output .= '</div>';

                                }


                            }

                            if($met_key == "required")
                            {

                                if($met_value == "0")
                                {
                                    $met_flag = false;
                                }

                                if(isset($item["posts"]["required"]) && !empty($item["posts"]["required"]))
                                {
                                    foreach ($item["posts"]["required"] as $quotas)
                                    {

                                        if(!is_array($quotas)) // Not post ids
                                        {
                                            if($met_flag ==  false)
                                            {
                                                $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>'.$quotas.'</br>';
                                            }
                                            else
                                                {

                                                    // Will need to parse the data to find the true one and the failed one.
                                                    // post pending - 0/1
                                                    $split = explode("-", $quotas);

                                                    // Remove space in front
                                                    $_quota_vals  = substr($split[1], 1);

                                                    // Explode with / delimiter
                                                    $post_vals = explode("/", $_quota_vals);

                                                    // If the post count is greater or equal the required amount
                                                    if($post_vals[0] >= $post_vals[1])
                                                    {
                                                        $output .= '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>'.$quotas.'</br>';

                                                    }
                                                    else
                                                        {

                                                            // This post type was not met but some other post was
                                                            $output .= '<span style="color: red" class="dashicons dashicons-dismiss"></span>'.$quotas.'</br>';
                                                        }


                                                }

                                        }

                                    }


                                }


                            }

                        }

                        $output .= '</td>';

                        $output .= '<td class="types column-types" data-colname="Details">';
                      // Nothing yet
                        $output .= '</td>';

                        $output .= '</tr>';

                        $i++;
                    }







        }
        else
            {

                // No records to display
                $output .= '<tr id="record-0"><td class="types column-types" data-colname="Details" colspan="3">No Quota Records Found</td></tr>';

            }

        // Html Table footer
        $output .= $this->get_archive_table_header('bottom', $checkedArgs);

        // Bottom Action Bar
        if(isset($user_records) && !empty($user_records))
            $output .= $this->get_action_bar($user_records[0], 'bottom');
        else
            $output .= $this->get_action_bar($user_records, 'bottom');

        $output .= '<p><div> <a href="?page=myquota" class="button">Back</a> </div></p>';

        // Footer
        print $output;

    }




}