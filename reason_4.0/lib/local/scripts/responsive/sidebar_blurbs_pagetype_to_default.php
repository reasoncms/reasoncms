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
    echo '<title>Reason: Tableless/Tabled Form Inventory</title>';
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
    echo '<h1>Reason: Change "Sidebar Blurb" pagetypes to Default pagetype</h1>';
    
    function show_sidebar_blurb_pagetypes()
    {
        
        $es = new entity_selector();
        $es->add_type(id_of('minisite_page'));
        $es->limit_tables(array('page_node'));
        $es->limit_fields('entity.name, page_node.custom_page');
        $result = $es->run_one();       

        $c = 1;
        $sidebar_blurb_pagetype_exists = false;
        foreach ($result as $page) {
            if ($page->get_value('custom_page') == 'sidebar_blurb'){
                $sidebar_blurb_pagetype_exists = true;
                echo $c;
                echo '<li><strong>ID &raquo;</strong> ' . $page->get_value('id') . '</li>';
                $link = '<a href="/reason/admin/?entity_id_test='.$page->get_value('id').'&cur_module=EntityInfo">'.$page->get_value('name').'</a>';
                echo '<li><strong>Name &raquo;</strong> ' . $link . '</li>';
                // pray($page);

                $str = '<li><strong>Updated? &raquo;</strong> ';
                $str .= reason_update_entity(
                        $page->get_value('id'),
                        reason_check_authentication(),
                        array('custom_page'=>'default'),
                        false
                    );
                echo $str.'</li>';
                echo '<hr>';

                $c++;
            }
        }
        if (!$sidebar_blurb_pagetype_exists) {
            echo '<h3>No "Sidebar Blurb" pagetypes exists.</h3>';
        }

    }
    
    show_sidebar_blurb_pagetypes();
    
    echo '</div></body></html>';

    
?>