<?php
/**
 * Changes Tabled forms to Tableless
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
    include_once( 'reason_header.php' );
    reason_require_once( 'minisite_templates/page_types.php' );
    reason_include_once( 'classes/page_types.php' );
    $rpts =& get_reason_page_types();
    reason_include_once( 'classes/entity.php' );
    connectDB( REASON_DB );
    reason_include_once('classes/admin/modules/default.php');
    $top_link = '<a href="#top" class="top">Return to top</a>';

    reason_include_once( 'function_libraries/user_functions.php' );
        force_secure_if_available();
        $current_user = check_authentication();
    if (!reason_user_has_privs( get_user_id ( $current_user ), 'assign_any_page_type') )
    {
        die('<h1>Sorry.</h1><p>You do not have permission to view page type info.</p></body></html>');
    }

    echo '<!DOCTYPE html><head>';
    echo '<title>Reason: Change deprecated pagetypes to Default pagetype</title>';
    ?>
    <link rel="stylesheet" type="text/css" href="/reason_package/css/universal.css" />
    <link rel="stylesheet" type="text/css" href="/reason/css/simplicity/blue.css" />
    <link rel="stylesheet" type="text/css" href="/reason/css/forms/form.css" />
    <script type="text/javascript" src="/jquery/jquery_latest.js"></script>
    <style type="text/css">
      body {background-color: white;}
      div#wrapper {border: none; width: 90%;}
      h4 {margin-bottom: 0px;}
      table.table_data {border-left: 1px solid #000000;}
      
    </style>
    
    <?php
    echo '</head><body><div id="wrapper">';
    echo '<h1>Reason: Change deprecated pagetypes to Default pagetype</h1>';
    
    function show_sidebar_blurb_pagetypes()
    {
        $deprecated_pages = array( 'aaron_test_page','admissions_account_signup','admissions_application','admissions_application_export','admissions_clear_export_table','alumni_auction_registration','app_dev_on_call','audio_video_on_current_site','audio_video_sidebar','directory_sample_campus','directory_alumni','django_form','dorian_band_nomination','event_with_form','form_sidebar_blurbs','homepage','luther2010_admissions','luther2010_alumni','luther2010_carousel','luther2010_giving','luther2010_home','luther2010_home_feature','luther2010_landing','luther2010_landing_feature','luther2010_landing_feature_form','luther2010_landing_feature_form','luther2010_landing_feature_sidebar_news','luther2010_landing_single_column','luther2010_music','luther2010_music','luther2010_public_information','luther2010_sports','luther_tab_widget','mobile_admissions','mobile_blank','mobile_caf_cam','mobile_caf_menu','mobile_directions','mobile_directory','mobile_event_cal','mobile_home','mobile_labstats','mobile_librarysearch','mobile_map','mobile_map_home','mobile_news','mobile_visitor','norge_conference','artwork_module','norse_form','onecard','standalone_login_page' );   

        $es = new entity_selector();
        $es->add_type(id_of('minisite_page'));
        $es->limit_tables(array('page_node'));
        $es->limit_fields('entity.name, page_node.custom_page');
        $result = $es->run_one();       
        // pray($result);
        $c = 1;
        $pagetype_exists = false;

        foreach ($deprecated_pages as $deprecated_page) {
            // echo '<ul>';
            echo '<h3>Pagetype &raquo; ' . $deprecated_page . '</h3>';
            echo '<hr>';
            foreach ($result as $page) {
                // echo "<hr>";
                if ($page->get_value('custom_page') == $deprecated_page){

                    $pagetype_exists = true;
                    echo $c;
                    echo "<ul>";
                    echo '<li><strong>ID &raquo;</strong> ' . $page->get_value('id') . '</li>';
                    $link = '<a href="/reason/admin/?entity_id_test='.$page->get_value('id').'&cur_module=EntityInfo">'.$page->get_value('name').'</a>';
                    echo '<li><strong>Name &raquo;</strong> ' . $link . '</li>';
                    echo '<li><strong>Site &raquo;</strong> ' . $page->get_value('name'). '</li>';
                    // pray($page);

                    // $str = '<li><strong>Updated? &raquo;</strong> ';
                    // $str .= reason_update_entity(
                    //         $page->get_value('id'),
                    //         reason_check_authentication(),
                    //         array('custom_page'=>'default'),
                    //         false
                    //     );
                    // echo $str.'</li>';
                    echo '</ul>';
                    $c++;
                }
            // echo "</ul>";
            }
        }
        // if (!$pagetype_exists) {
        //     echo '<h3>No "'.$page->get_value('custom_page').'" pagetypes exists.</h3>';
        // }
    }
    
    show_sidebar_blurb_pagetypes();
    
    echo '</div></body></html>';

    
?>