<?php
/**
 * Changes Nav Titles for a sites home page from "Site Home" to "Site"
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
    echo '<title>Reason: List of pages hidden from navigation</title>';
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
    echo '<h1>Reason: List of pages hidden from navigation</h1>';
    
    function show_hidden_pages()
    {
        $es = new entity_selector();
        $es->add_type(id_of('minisite_page'));
        $es->limit_tables(array('page_node'));
        $es->limit_fields('entity.name, page_node.custom_page, page_node.link_name, page_node.nav_display');
        $result = $es->run_one();  
        // pray($result);
        $c = 1;
        $pagetype_exists = false;

            foreach ($result as $page) {
                    $pagetype_exists = true;
                    
                    if ($page->get_value('nav_display') == 'No'){
                        echo $c;
                        echo "<ul>";
                        echo '<li><strong>ID &raquo;</strong> ' . $page->get_value('id') . '</li>';
                        $link = '<a href="/reason/admin/?entity_id_test='.$page->get_value('id').'&cur_module=EntityInfo">'.$page->get_value('name').'</a>';
                        echo '<li><strong>Name &raquo;</strong> ' . $link . '</li>';
                        echo '<li><strong>Link Name &raquo;</strong> ' . $page->get_value('link_name'). '</li>';
                        echo '<li><strong>Nav Display &raquo;</strong> ' . $page->get_value('nav_display'). '</li>';
                        echo '</ul>';
                        $c++;
                }
            }
    }
    
    show_hidden_pages();
    
    echo '</div></body></html>';

    
?>