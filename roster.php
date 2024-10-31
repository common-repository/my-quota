<style>
    #wpfooter { position: relative;}
    .dashicons-calendar-alt { vertical-align: middle; }
    .or-quota { outline: 1px dashed #2271b1;}
</style>
<div class="wrap">
<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 10/14/20
 * Time: 12:19 PM
 */


    // Get Args
    if(!isset($_GET["orderby"]))
            $order_by = null;
    else
        $order_by = $_GET["orderby"];


    if(!isset($_GET["order"]))
            $order = null;
    else
        $order = $_GET["order"];

    if(!isset($_GET["paged"]))
            $paged = 1;
    else
        $paged = $_GET["paged"];


    // Set args from query
    $args = array(

        "orderby" => $order_by,
        "order" => $order,
        "paged"  => $paged,
        "page" => 'myquota'

    );

    // Archive View
if(isset($_GET["archive"]))
    {
        // Get User id
        if(isset($_GET["user_id"]))
            {
                $user_id = sanitize_text_field( $_GET["user_id"] );

                if(is_numeric($user_id))
                {
                    // Get Archive View
                    $roster = new my_quota_dropstr_archive_class();

                    print $roster->get_archive_view($user_id, $args);
                }

            }

    }
    else
        {
            // Get Current user
            $current_user = wp_get_current_user();

            // IF user Admin, show all users
            if ( in_array( 'administrator', (array) $current_user->roles ) )
            {
                //The user has the "author" role
            }
            else
                $args["editor"] = $current_user->ID;

            // Get Default View
            $roster = new my_quota_dropstr_roster();

            $checkedArgs = $roster->check_args($args);

            print $roster->get_list_view($checkedArgs);

        }
?>
</div>

