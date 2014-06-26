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
    echo '<h1>Reason: Tableless/Tabled Form Inventory</h1>';
    
    function show_forms()
    {
        
        $es = new entity_selector();
        $es->add_type( id_of('form') );
        $result = $es->run_one();       

        // pray($result);
        echo '<h2>'.count($result).' Results</h2>';
        $c = count($result);
        foreach ($result as $form) {
            echo $c;
            if ($form->get_value('tableless') == false) {
                echo '<ul>';
                $link = '<a href="/reason/admin/?entity_id_test='.$form->get_value('id').'&cur_module=EntityInfo">'.$form->get_value('name').'</a>';
                echo '<li><strong>Name &raquo;</strong> ' . $link . '</li>';
                echo '<li><strong>Tableless &raquo;</strong> ' . $form->get_value('tableless') . '</li>';
                echo '<li><strong>Thor View &raquo;</strong> ' . $form->get_value('thor_view') . '</li>';
                echo "<li><strong>Updated? &raquo;</strong></li>";
                echo '</ul>';
                echo '<hr>'; 
                reason_update_entity(
                        $form->get_value('id'),
                        reason_check_authentication(),
                        array('tableless'=>'1'),
                        false
                    );
            } else {
                echo '<ul title="Tabled">';
                $link = '<a href="/reason/admin/?entity_id_test='.$form->get_value('id').'&cur_module=EntityInfo">'.$form->get_value('name').'</a>';
                echo '<li><strong>Name &raquo;</strong> ' . $link . '</li>';
                echo '<li><strong>Tableless &raquo;</strong> ' . $form->get_value('tableless') . '</li>';
                echo '<li><strong>Thor View &raquo;</strong> ' . $form->get_value('thor_view') . '</li>';
                echo '</ul>';
                echo '<hr>'; 
            }
            $c--;
        }
    }
    
    show_forms();
    
    echo '</div></body></html>';

    
?>