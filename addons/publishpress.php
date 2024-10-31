<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 3/19/21
 * Time: 12:55 PM
 */

class my_quota_dropstr_publishpress_plugin extends my_quota_dropstr_quota_list
{

    /**
     * Get Groups Name from Term id
     * @param $term_id
     * @return string
     */
    private function get_group_name($term_id)
    {
        global $wpdb;

        $get_group_name = $wpdb->get_results($wpdb->prepare("SELECT name FROM {$wpdb->prefix}terms WHERE term_id = %s", $term_id));

        if(isset($get_group_name) && !empty($get_group_name))
        {
            foreach ($get_group_name as $group_name)
            {

                return $group_name->name;
            }

        }
        else
        {
            // Nothing found, return empty
            return 0;
        }



    }

    /*
     * Get Publish Press groups
     */
    private function get_groups($group_id = NULL)
    {
        global $wpdb;

        if(isset($group_id))
        {
            $get_groups = $wpdb->get_results($wpdb->prepare("SELECT term_id, description FROM {$wpdb->prefix}term_taxonomy WHERE term_id = %s AND taxonomy = %s", $group_id, 'pp_usergroup'));

        }
        else
        {
            $get_groups = $wpdb->get_results($wpdb->prepare("SELECT term_id, description FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy = %s", 'pp_usergroup'));
        }


        if(isset($get_groups) && !empty($get_groups))
        {

            foreach ($get_groups as $group)
            {
                // Term id
                $group_id = $group->term_id;

                // Display Name
                $group_name = $this->get_group_name($group->term_id);

                // base 64 encoded (description and user ids)
                $group_description = base64_decode($group->description);

                $ef_group[] = array('id' => $group_id, 'name' => $group_name, 'meta_data' => unserialize($group_description));

            }

            return $ef_group;

        }
        else
        {

            return 0;
        }


    }

    /*
     * Get the Publish Press Group(s) and users
     */
    function get_publishpress_groups($group_id = NULL)
    {

        $groups = $this->get_groups($group_id);

        return $groups;

    }


    /**
     * Get user by Publish Press Groups
     * @param $ef_group $current_quota_group $taken_users
     * @return string
     */
    function get_user_by_pp_group($ef_group, $current_quota_group)
    {


        // split users to obtain edit flow group ids
        if(isset($current_quota_group["users"]) && is_array($current_quota_group["users"]))
        {
            foreach ($current_quota_group["users"] as $user)
            {
                if(!is_int($user))
                {
                    $user_data = explode('_', $user);

                    $user_name =  $user_data[0];

                    if($user_name == 'pp')
                    {
                        $ef_group_id = $user_data[1];
                    }
                }

            }

            if(isset($ef_group_id) && !empty($ef_group_id))
            {

                // Current Edit Flow group's users
                $current_ef_group = $this->get_publishpress_groups($ef_group_id);
                $ef_group_users =  $current_ef_group[0]["meta_data"]["user_ids"];




            }


        }

        // if isset and ef groups is an array
        if(isset($ef_group))
        {


            // Create All Role Type checkbox
            $output = ' <tr class="level-0 "><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-4">Select Publish Press Group</label><input type="checkbox" name="users[]"';

            // If WP role type exists in current group
            if(isset($ef_group_users))
            {
                if (in_array('pp_'.$ef_group["id"], $current_quota_group["users"]))
                {
                    $output .= ' checked';
                }
            }

            $output .= ' value="pp_' . $ef_group["id"] . '" class="all-' . $ef_group["id"] . '"></th><td class="name column-name has-row-actions column-primary" data-colname="Name"><strong>' . $ef_group["name"] . '(s) - publish press group</strong><br><div class="hidden" id="inline_4"><div class="name">' . $ef_group["name"]. '</div><div class="slug">' . $ef_group["name"] . '(s) - publish press group</div></div><div class="row-actions"></div></td></tr>';




        }


        return $output;


    }


    /*
 * Get Editor by Publish Press Group
 */
    function get_editor_by_pp_group($ef_group, $current_quota_group)
    {


        // split users to obtain edit flow group ids
        if(isset($current_quota_group["editors"]) && is_array($current_quota_group["editors"]))
        {
            foreach ($current_quota_group["editors"] as $user)
            {
                if(!is_int($user))
                {
                    $user_data = explode('_', $user);

                    $user_name =  $user_data[0];

                    if($user_name == 'pp')
                    {
                        $ef_group_id = $user_data[1];
                    }
                }

            }

            if(isset($ef_group_id) && !empty($ef_group_id))
            {

                // Current Edit Flow group's users
                $current_ef_group = $this->get_publishpress_groups($ef_group_id);
                $ef_group_users =  $current_ef_group[0]["meta_data"]["user_ids"];




            }


        }

        // if isset and ef groups is an array
        if(isset($ef_group))
        {


            // Create All Role Type checkbox
            $output = ' <tr class="level-0 "><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-4">Select Publish Press Group</label><input type="checkbox" name="editors[]"';

            // If WP role type exists in current group
            if(isset($ef_group_users))
            {
                if (in_array('pp_'.$ef_group["id"], $current_quota_group["users"]))
                {
                    $output .= ' checked';
                }
            }

            $output .= ' value="pp_' . $ef_group["id"] . '" class="all-' . $ef_group["id"] . '"></th><td class="name column-name has-row-actions column-primary" data-colname="Name"><strong>' . $ef_group["name"] . '(s) - publish press group</strong><br><div class="hidden" id="inline_4"><div class="name">' . $ef_group["name"]. '</div><div class="slug">' . $ef_group["name"] . '(s) - publish press group</div></div><div class="row-actions"></div></td></tr>';




        }

        return $output;


    }


}