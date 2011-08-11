<?php

// @ sign suppresses warnings and errors
    @include_once( 'reason_header.php' );
    @reason_include_once( 'function_libraries/user_functions.php' );
    
    @force_secure_if_available();

    $username = @check_authentication();
    $group = @id_of('application_export_group');
    @$gh = new group_helper();
    @$gh->set_group_by_id($group);
    @$has_access = $gh->has_authorization($username);
    
    $path = '/var/reason_admissions_app_exports/application_exports/';
    $cleaned_term = @mysql_real_escape_string($_GET['file_name']);

    $file = $path . $cleaned_term;

    if ($has_access){
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $cleaned_term);
        header('Content-Length : ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    } else {
        die('Sorry. You are not allowed to download this file.');
    }

?>