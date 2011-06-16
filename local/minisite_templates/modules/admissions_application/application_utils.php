<?php

function check_open_id(&$the_form) {
    $the_form->sess = & get_reason_session();
    if ($the_form->sess->exists()) {
        if (!$the_form->sess->has_started())
            $the_form->sess->start();
    }

    if ($the_form->sess->get('openid_id')) {
        $the_form->openid_id = $the_form->sess->get('openid_id');
        return $the_form->openid_id;
    } else {
        return false;
    }
}

function check_login() {
    $url = get_current_url();
    $parts = parse_url($url);
//    $url = $parts['scheme'] . '://' . $parts['host'] . '/openid/?next=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];

    $txt = '<h3>Hi There!</h3>';
    $txt .= '<p>To begin or resume your application, please sign in using an
            <a href="http://openid.net/get-an-openid/what-is-openid/" target="_blank">Open ID</a>.</p>';
    $txt .= '</div>';

//    $url = get_current_url();
    try {
        $next_url = $_GET['next'];
    } catch (Exception $e) {
        $next_url = '';
    }
    if ($url) {
        $url = $parts['scheme'] . '://' . $parts['host'] . '/openid/?next=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo $url;
//        $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php?next=' . $url;
    } else {
        $url = $parts['scheme'] . '://' . $parts['host'] . '/openid/new_token.php';
//        $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php';
    }
    return $txt . '<iframe src="https://luthertest2.rpxnow.com/openid/embed?token_url=' . $url . '"
    scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>';
}

function get_applicant_data($openid, &$the_form) {
    echo '<br />openid: ' . $openid . '<br />';
    connectDB('admissions_applications_connection');
    $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . addslashes($openid) . "' ";
//            $qstring = "SELECT * FROM `applicants` WHERE `open_id`='". $openid . "' ";

    $results = db_query($qstring);

    if (mysql_num_rows($results) < 1) {
        //
        //$qstring = "INSERT INTO `applicants` (`open_id`)  VALUES ('" . addslashes($openid) . "'); ";
        $qstring = "INSERT INTO `applicants` (`open_id`, `creation_date`,  `submitter_ip`)
            VALUES ('" . addslashes($openid) . "', 'NOW', '" . $_SERVER['REMOTE_ADDR'] . "'); ";
        $results = mysql_query($qstring) or die(mysql_error());
        $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . addslashes($openid) . "' ";
        $results = db_query($qstring);
    }

    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        foreach ($the_form->get_element_names() as $element) {
            if (array_key_exists($element, $row)) {
                $the_value = $row[$element];
                $the_form->set_value($element, $the_value);
            }
        }
    }
    connectDB(REASON_DB);
}

function set_applicant_data($openid, &$the_form) {

    connectDB('admissions_applications_connection');
    echo '<br>' . addslashes($openid) . '<br>';
    $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . addslashes($openid) . "' ";
    $results = db_query($qstring);
    if (mysql_num_rows($results) < 1) {
        $qstring = "INSERT INTO `applicants` (`open_id`, `creation_date`, `last_update`, `submitter_ip`)
            VALUES ('" . addslashes($openid) . "', 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', '" . $_SERVER['REMOTE_ADDR'] . "'); ";
        $results = mysql_query($qstring) or die(mysql_error());
        $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . addslashes($openid) . "' ";
        $results = db_query($qstring);
    }
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        $qstring = "UPDATE `applicants` SET ";
        foreach ($the_form->get_element_names() as $element) {
            if (array_key_exists($element, $row)) {
                $qstring .= $element . "='";
                if (is_null($the_form->get_value($element)) <> True) {
                    if (is_array($the_form->get_value($element))) {
                        $qstring .= addslashes(implode(',', $the_form->get_value($element)));
                    } else {
                        $qstring .= addslashes($the_form->get_value($element));
                    }
                } else {
                    $qstring .= NULL;
                }
                $qstring .= "', ";
            }
        }
        $qstring = rtrim($qstring, ' ,');
        $qstring .= " WHERE `open_id`= '" . addslashes($openid) . "' ";
        //die($qstring);
    }
    $qresult = db_query($qstring);
    connectDB(REASON_DB);
}

?>