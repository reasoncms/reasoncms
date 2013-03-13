<?php
    include_once('reason_header.php');
    reason_include_once('function_libraries/relationship_finder.php');
    reason_include_once('function_libraries/admin_actions.php');
    reason_include_once('function_libraries/user_functions.php');

    if (!reason_user_has_privs( id_of( reason_check_authentication() ) ), 'db_maintenance' ) )
    {
        die('<html><head><title>Reason: Delete Categories</title></head><body><h1>Sorry.</h1><p>You do not have permission to delete categories.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
    } else {
        // this code deletes all the categories from the Luther Home (2007) site
        $es = new entity_selector();
        $es->add_type(id_of('site'));
        $es->add_relation('entity.id != ' . id_of('home'));
        $sites = $es->run_one();

        $cat_es = new entity_selector(id_of('home'));
        $cat_es->add_type(id_of('category_type'));
        $cat_result = $cat_es->run_one();
        $cats = array_keys($cat_result);


        foreach ($cats as $cat) {
            reason_expunge_entity($cat, id_of(reason_check_authentication());
        }
    }   
?>