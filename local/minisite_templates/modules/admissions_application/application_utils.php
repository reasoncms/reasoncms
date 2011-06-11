<?php

function check_open_id(&$the_form) {
    $the_form->sess = & get_reason_session();
    if ($the_form->sess->exists()) {
        if (!$the_form->sess->has_started())
            $the_form->sess->start();
    }

    if ($the_form->sess->get('openid_id')) {
        $the_form->openid_id = $the_form->sess->get('openid_id');
        echo $the_form->openid_id;
        return $the_form->openid_id;
    } else {
        return false;
    }
}

function get_applicant_data($openid, &$the_form) {
    connectDB('admissions_applications_connection');
    $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . addslashes($openid) . "' ";
//            $qstring = "SELECT * FROM `applicants` WHERE `open_id`='". $openid . "' ";

    $results = db_query($qstring);

    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        foreach ($the_form->get_element_names() as $element) {
            if (array_key_exists($element, $row)) {
                $the_value = $row[$element];
//                echo $the_value . 'BLANK????? <br>';
                $the_form->set_value($element, $the_value);
                echo $element . '=' . $row[$element] . '<br>';
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
        $qstring = "INSERT INTO `applicants` (`open_id`)  VALUES ('" . addslashes($openid) . "'); ";
        $results = mysql_query($qstring) or die(mysql_error());
        $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . addslashes($openid) . "' ";
        $results = db_query($qstring);
    }
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        $qstring = "UPDATE `applicants` SET ";
        foreach ($the_form->get_element_names() as $element) {
            if (array_key_exists($element, $row)) {
                $qstring .= $element . "='";
                if ($the_form->get_value($element)) {
                    $qstring .= addslashes($the_form->get_value($element));
                } else {
                    $qstring .= NULL;
                }
                $qstring .= "', ";
            }
        }
        $qstring = rtrim($qstring, ' ,');
        $qstring .= " WHERE `open_id`= '" . addslashes($openid) . "' ";
//        echo $qstring;
//        die;
    }
    $qresult = db_query($qstring);
}

?>
