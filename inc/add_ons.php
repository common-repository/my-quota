<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 2/26/21
 * Time: 12:07 PM
 */

class my_quota_add_ons extends my_quota_dropstr_quota_list
{


    /**
     * Get Add-ons
     * @return array
     */
    function get_add_on_list()
    {

        $list = [];
        // List all plugins

        // Edit Flow
        $list[] = array("id" => "1", "plugin_name"=> "edit-flow", "requires_plugin" => "edit-flow", "plugin_location" => "edit-flow/edit_flow.php", "plugin_version" => "0.9.6", "plugin_nicename" => "Edit Flow", "name" => "Edit Flow Groups", "type" => "Free", "description" => "Use your Edit-Flow plugin groups with My Quota. (<b>requires the <a href=\"/wp-admin/plugin-install.php?tab=plugin-information&amp;plugin=edit-flow&amp;TB_iframe=true&amp;width=772&amp;height=888\" class=\"thickbox open-plugin-details-modal\" aria-label=\"More information about Edit Flow\" data-title=\"Edit Flow\">Edit-Flow plugin</a> </b>)");

        // Yoast SEO
        $list[] = array("id" => "2", "plugin_name"=> "wordpress-seo", "requires_plugin" => "wordpress-seo", "plugin_location" => "wordpress-seo/wp-seo.php", "plugin_version" => "16.2", "plugin_nicename" => "Yoast SEO", "name" => "Yoast Connect", "type" => "Free", "description" => "Integrate Yoast SEO plugin with My Quota, adding additional options for your groups and details in reporting. (<b>requires the <a href=\"/wp-admin/plugin-install.php?tab=plugin-information&plugin=wordpress-seo&TB_iframe=true&width=600&height=550\" class=\"thickbox open-plugin-details-modal\" aria-label=\"More information about Yoast SEO\" data-title=\"Yoast SEO\">Yoast SEO plugin</a> </b>)");

        // Publish Press
        $list[] = array("id" => "3", "plugin_name"=> "publishpress", "requires_plugin" => "publishpress", "plugin_location" => "publishpress/publishpress.php", "plugin_version" => "3.3.1", "plugin_nicename" => "Publish Press", "name" => "Publish Press Groups", "type" => "Free", "description" => "Use your Publish Press plugin groups with My Quota. (<b>requires the <a href=\"/wp-admin/plugin-install.php?tab=plugin-information&plugin=publishpress&TB_iframe=true&width=600&height=550\" class=\"thickbox open-plugin-details-modal\" aria-label=\"More information about Publish Press\" data-title=\"Publish Press\">Publish Press plugin</a> </b>)");

        // My Editor

        return $list;

    }


    function get_add_on_table()
    {
        add_thickbox();

        $core = new my_quota_dropstr_quota_core();

        // Get Title
        $output = '<h2 class="wp-heading-inline">Add Ons</h2>';

        $output .= '<h2 class="screen-reader-text">Plugin list</h2><table class="wp-list-table widefat plugins">'
            . '<thead>'
            . '<tr>'
            . '<th scope="col" id="cb" class="manage-column column-cb check-column" style="">
					</th><th scope="col" id="workflowname" class="manage-column column-name"><span>Name</span></a></th><th scope="col" id="requirement" class="manage-column column-type">Type</th><th scope="col" id="requirement" class="manage-column column-description">Description</th></tr>
                                    </thead><tbody>';

        // Get Add ons
        $list = $this->get_add_on_list();

        // API Call for add-on version check
        $api = new my_quota_dropstr_quota_core();

        $plugin_versions = $api->get_version_updates();


        $allPlugins = get_plugins(); // associative array of all installed plugins
        $activePlugins = get_option('active_plugins'); // simple array of active plugins
        // traversing $allPlugins array
        foreach($allPlugins as $key => $value) {
            if (in_array($key, $activePlugins)) { // display active only

                $slug = explode('/',$key)[0];
                $active_plugin[$slug] = $value['Version'];
            }
        }

        if(isset($list) && !empty($list))
        {


            foreach ($list as $item)
            {

                $deactivateLink = wp_nonce_url('admin.php?page=myquota-settings&tab=add-ons&add-on=' . $item["plugin_name"] . '', 'deactivate_addon', 'deactivate');

                $activateLink = wp_nonce_url('admin.php?page=myquota-settings&tab=add-ons&add-on=' . $item["plugin_name"] . '', 'activate_addon', 'activate');

                $output .= '<tr';

                // Addon Active
                if(isset($item["requires_plugin"]) && !empty($item["requires_plugin"]))
                {
                    // Check if plugin exists first (maybe no longer installed
                    if (is_plugin_active($item["plugin_location"]))
                    {

                        // Check and update plugin check if required
                        if ($core->check_snap($item["requires_plugin"], "check"))
                        {
                            $output .= ' class="active"';
                        }
                        else
                            $output .= ' class="uninstalled inactive"';

                    }
                    else
                        {
                            // Deactivate snap
                            $core->check_snap($item["requires_plugin"], "update");
                            $output .= ' class="uninstalled inactive"';
                        }



                }
                else
                    {
                        if ($core->check_snap($item["addon_name"], "check"))
                        {
                            $output .= ' class="active"';
                        }
                        else
                            $output .= ' class="uninstalled inactive"';
                    }



                $output .= '><th scope="row" class="check-column"></th><td><b>'.$item["name"].'</b><div class="row-actions visible">';

                // Check if Add-on requires another plugin (and check if active)
                if(isset($item["requires_plugin"]) && !empty($item["requires_plugin"]))
                {

                    // Check if valid (get location)
                    if ( is_plugin_active( $item["plugin_location"] ))
                        {

                            // if found, check if active (snap)
                            if($core->check_snap($item["requires_plugin"], "check"))
                            {
                                $output .= '<span class="deactivate"><a href="'.$deactivateLink.'">Deactivate</a></span>';
                            }
                            else
                                $output .= '<span class="activate"><a href="'.$activateLink.'">Activate</a></span>';


                        }
                        else
                            $output .= '<span class="activate">Plugin Required</span>';

                }
                else
                    {
                        // Plugin not required


                    }


						$output .='</div></td><td>'.$item["type"].'</td><td>'.$item["description"].'';
						// check plugin version

                    if(isset($active_plugin[''.$item["plugin_name"].'']) && $active_plugin[$item["plugin_name"]] > $plugin_versions["add-ons"][''.$item["plugin_name"].''])
                    {
                        $output .= '<br><font color="red"><b>!</b></font> Current plugin version, <b>'.$active_plugin[$item["plugin_name"]].'</b>, may not be compatible. Last approved version, <b>'.$plugin_versions["add-ons"][''.$item["plugin_name"].''].'</b>';
                    }
                        $output .= '</td></tr>';

            }
        }


        $output .= '</tbody></table>';


        return $output;
    }


}