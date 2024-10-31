<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 3/3/21
 * Time: 4:32 PM
 */

class my_quota_dropstr_wordpress_seo_plugin extends my_quota_dropstr_quota_core
{


    /**
     * Get the keyword and readability score
     * @param $post_id
     * @return array|string
     */
    private function get_wordpress_seo_post_scores($post_id)
    {

        $post_data = '';

        global $wpdb;

        $get_post_data = $wpdb->get_results($wpdb->prepare("SELECT primary_focus_keyword_score, readability_score FROM {$wpdb->prefix}yoast_indexable WHERE object_id = %s AND object_type = %s AND object_sub_type = %s", $post_id, "post", "post"));

        if(isset($get_post_data) && !empty($get_post_data))
        {
            foreach ($get_post_data as $post_datum)
            {

                $post_data = array("keyword_score" => $post_datum->primary_focus_keyword_score, "readability_score" => $post_datum->readability_score);
            }

        }

        return $post_data;
    }

    /**
     * Get SEO score for post and the requirement
     */
    function check_post_SEO_score($post_id)
    {
        $seo_rank = [];

        // Get Rank data from class-wpseo-rank.php
        $rank = array('none' => 0, 'bad' => 40, 'ok' => 70, 'good' => 71);

        $scores = $this->get_wordpress_seo_post_scores($post_id);

        if(isset($scores) && !empty($scores))
        {

            // Keyword Score
            if(isset($scores["keyword_score"]) && !empty($scores["keyword_score"]))
            {

                if($scores["keyword_score"] <= 40)
                    $seo_rank["keyword_score"] = "1"; // Bad
                elseif ($scores["keyword_score"] <= 70)
                    $seo_rank["keyword_score"] = "2"; // Ok
                else
                    $seo_rank["keyword_score"] = "3"; // Good
            }
            else
                $seo_rank["keyword_score"] = "0"; // No rank set


            // Readability Score
            if(isset($scores["readability_score"]) && !empty($scores["readability_score"]))
            {

                if($scores["readability_score"] <= 40)
                    $seo_rank["readability_score"] = "1"; // Bad
                elseif ($scores["readability_score"] <= 70)
                    $seo_rank["readability_score"] = "2"; // Ok
                else
                    $seo_rank["readability_score"] = "3"; // Good
            }
            else
                $seo_rank["readability_score"] = "0"; // No rank set


        }


        return $seo_rank;
    }

    /**
     * Get the Addon Module for Groups Option
     * @param $data
     * @return string
     */
    function get_wordpress_seo_module($data)
    {

        $output = '<p><b>Yoast SEO Plugin Options</b></p>';


        // Keyword Score
        $output .= '<div style="margin-left: 20px;"><p><b>Keyword Score</b> - Set the minimal score required for the quota.<br/><select name="wordpress-seo-keyword">';


            $keywords = array("0" => "Disabled", "3" => "Good", "2" => "Ok", "1" => "Bad");

            foreach ($keywords as $key => $keyword)
            {

                $output .= '<option value="'.$key.'"';

                if(isset($data["conditions"]["wordpress_seo"]["keyword_score"]) && $data["conditions"]["wordpress_seo"]["keyword_score"] == $key)
                {

                    $output .= ' selected';
                }

                $output .= '>'.$keyword.'</option>';
            }


        $output .= '</select></p></div>';


        // Readability Score
        $output .= '<div style="margin-left: 20px;"><p><b>Readability Score</b> - Set the minimal readability score required for the quota.<br/><select name="wordpress-seo-readability">';

        foreach ($keywords as $key => $keyword)
        {

            $output .= '<option value="'.$key.'"';

            if(isset($data["conditions"]["wordpress_seo"]["readability_score"]) && $data["conditions"]["wordpress_seo"]["readability_score"] == $key)
            {

                $output .= ' selected';
            }

            $output .= '>'.$keyword.'</option>';
        }

        $output .= '</select></p></div>';


        return $output;

    }

}