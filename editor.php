<div class="wrap">
    <?php
    /**
     * My Quota Groups Editor Page
     */

    /**
     * Create New Quota
     */
    if(isset($_GET["c"]) && ($_GET["c"] == "new" || $_GET["c"] == "edit"))
        {
            // New Quota Class
            $quota = new my_quota_dropstr_quota_list();

            // Get Quota id
            if(isset($_GET["status_id"]))
            {
                $quota_id = sanitize_key($_GET["status_id"]);
            }
            else
                {
                    $quota_id = 0;
                }


            // Page Title
            $quota->get_title($_GET["c"]);

            // Get Quota Settings
            $quota->get_quota_settings($_GET["c"], $quota_id);

        }

        else
            {
                // New Quota Class
                $quota = new my_quota_dropstr_quota_list();

                if(isset($_GET["action"]) && $_GET["action"] == "delete")
                    {

                        if(isset($_GET["status_id"]))
                        {


                            $quota_id = sanitize_key($_GET["status_id"]);

                            // Delete Quota Group
                            $quota->delete_quota_group($quota_id);
                        }

                    }


                // Get Args
                if(!isset($_GET["orderby"]))
                    {
                        $order_by = null;
                    }

                if(!isset($_GET["order"]))
                    {
                        $order = null;
                    }

                // Set args from query
                $args = array(

                    "orderby" => $order_by,
                    "order" => $order

                );

                // Page Title
                $quota->get_title(null);


                // Get Quota Table
                $quota->get_quota_list($args);
            }
    ?>
</div>