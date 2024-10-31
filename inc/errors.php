<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 10/9/20
 * Time: 10:21 AM
 */

// official error list code

class my_quota_dropstr_errors extends my_quota_dropstr_quota_list
{


    public function error_code($code)
        {

            // Updated
            if($code == 0)
            {

                $error = '<strong>Saved/Updated</strong> Submission was successfully saved/updated.';

            }

            // 100 Group Errors

            if($code == 100)
            {

                $error = '<strong>Special Characters Found</strong> Please check your group name and remove special characters.';

            }

            if($code == 101)
            {
                $error = '<strong>No Group Name Entered</strong> Please enter a group name.';

            }

            if($code == 102)
            {
                $error = '<strong>Invalid Group Type</strong> Please check a group type.';

            }

            if($code == 103)
            {
                $error = '<strong>No Group Type Selected</strong> Please check a group type.';

            }

            // Group Users Errors
            if($code == 104)
            {
                $error = '<strong>Invalid User/Role Selected</strong> Please check if the user/role exists.';

            }

            if($code == 105)
            {
                $error = '<strong>User Already in Group</strong> The selected user already belongs to a group/role.';

            }

            if($code == 106)
            {
                $error = '<strong>No Users/Roles Selected</strong> Please select a user/role.';

            }

            // Excerpts Errors
            if($code == 110)
            {
                $error = '<strong>No Selection Submitted</strong> Please select an option.';

            }

            if($code == 111)
            {
                $error = '<strong>Wrong Value Entered</strong> Please insert a numerical value.';

            }

            if($code == 112)
            {
                $error = '<strong>Wrong Value Entered</strong> Please insert a valid value.';

            }

            // Invalid post type
            if($code == 113)
            {
                $error = '<strong>Invalid Post Type</strong> Please select a valid post type.';

            }

            // Category Errors
            if($code == 115)
            {
                $error = '<strong>Invalid Category Selected</strong> Please check if the category exists.';

            }

            // Post Status Editor
            if($code == 120)
            {
                $error = '<strong>Not A Valid Name</strong> Please check the name to see if its already used or illegal characters were entered.';

            }
            // post status name invalid characters
            if($code == 121)
            {

                $error = '<strong>Special Characters Found</strong> Please check your group name and remove special characters.';

            }

            return $error;
        }
}
