<?php

function check_logout(&$the_form) {
    $the_form->sess = & get_reason_session();

    $should_logout = false;

    try {
        if ($the_form->get_value('logout1')) {
            $should_logout = true;
        };
    } catch (Exception $e) { /* not page 1 */
    }
    try {
        if ($the_form->get_value('logout2')) {
            $should_logout = true;
        };
    } catch (Exception $e) { /* not page 2 */
    }
    try {
        if ($the_form->get_value('logout3')) {
            $should_logout = true;
        };
    } catch (Exception $e) { /* not page 3 */
    }
    try {
        if ($the_form->get_value('logout4')) {
            $should_logout = true;
        };
    } catch (Exception $e) { /* not page 4 */
    }
    try {
        if ($the_form->get_value('logout5')) {
            $should_logout = true;
        };
    } catch (Exception $e) { /* not page 5 */
    }
    try {
        if ($the_form->get_value('logout6')) {
            $should_logout = true;
        };
    } catch (Exception $e) { /* not page 6 */
    }

    //die($should_logout);

    if ($should_logout == true || $should_logout == 'true') {
        $the_form->sess->destroy();
    }
}

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
    if (is_submitted($_SESSION['openid_id'])){
        return;
    } else {
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
        $url = $parts['scheme'] . '://' . $parts['host'] . '/reason/open_id/new_token.php?next=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . $parts['query'];
    } else {
        $url = $parts['scheme'] . '://' . $parts['host'] . '/reason/open_id/new_token.php';
    }

    //need to change iframe for official janrain account!!!
    $iframe = '<iframe src="https://luthertest2.rpxnow.com/openid/embed?token_url=' . $url . '"
    scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>';

    //production janrain account
    //    echo '<iframe src="http://luthercollege.rpxnow.com/openid/embed?token_url=' . $url . '"
    //        scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe> ';

    $open_id_text = '<div><p>The Luther College online application uses open authentication methods, which allow you to sign into your
        application account using one of the above host sites. By signing in through one of these sites, Luther
        College has access only to information that applicants have identified as “public.” Please be assured that
        Luther College will not seek to gain any additional information than what is necessary to complete the
        application for admission.</p>

        <p>Luther College will not sell, rent, or share your personal information with other individuals, private or
        public organizations for any purpose outside official college business. We will not share your personal
        information with any unauthorized third parties. Please see Luther’s <a href="/privacy" target="_blank">privacy
        policy</a> for additional information.</p></div>';

    return $txt . $iframe . $open_id_text;
    }
}

function is_submitted($open_id) {
    if($open_id){
        connectDB('admissions_applications_connection');
        $qstring = "SELECT `submit_date` FROM `applicants` WHERE `open_id`='" . mysql_real_escape_string($open_id) . "' ";
        $results = db_query($qstring);
        $row = mysql_fetch_array($results, MYSQL_ASSOC);
        connectDB(REASON_DB);
        if (is_null($row['submit_date'])) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

/*
 *  Repopulate elements with info that has been saved to the database
 */

function get_applicant_data($openid, &$the_form) {
    connectDB('admissions_applications_connection');
    $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . mysql_real_escape_string($openid) . "' ";
//            $qstring = "SELECT * FROM `applicants` WHERE `open_id`='". $openid . "' ";

    $results = db_query($qstring);

    if (mysql_num_rows($results) < 1) {
        //
        //$qstring = "INSERT INTO `applicants` (`open_id`)  VALUES ('" . mysql_real_escape_string($openid) . "'); ";
        $qstring = "INSERT INTO `applicants` (`open_id`, `creation_date`,  `submitter_ip`)
            VALUES ('" . mysql_real_escape_string($openid) . "', NOW(), '" . $_SERVER['REMOTE_ADDR'] . "'); ";
        $results = mysql_query($qstring) or die(mysql_error());
        $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . mysql_real_escape_string($openid) . "' ";
        $results = db_query($qstring);
    }

    /*
     * array of elements that are a checkbox_group_type
     * these are stored comma-separated in their database field
     * to set the value of these they must be exploded then set
     */
    $checkbox_elements = array('activity_1_participation', 'activity_2_participation', 'activity_3_participation',
        'activity_4_participation', 'activity_5_participation', 'activity_6_participation', 'activity_7_participation',
        'activity_8_participation', 'activity_9_participation', 'activity_10_participation', 'race');
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        foreach ($the_form->get_element_names() as $element) {
            if (array_key_exists($element, $row)) {
                if (in_array($element, $checkbox_elements)) {
                    $the_value = explode(',', $row[$element]);
                    $the_form->set_value($element, $the_value);
                } else {
                    if (($element != 'date_of_birth') || ($element == 'date_of_birth' && ($row[$element] != '0000-00-00'))) {
                        $the_value = $row[$element];
                        $the_form->set_value($element, $the_value);
                    }
                }
            } else if ($element == 'ssn_1') {
                // handle ssn which is an element group of ssn_1, ssn_2, and ssn_3
                // but stored in the db as ssn
                $exploded_ssn = explode('-', $row['ssn']);
                list ($ssn1, $ssn2, $ssn3) = explode('-', $row['ssn']);
//                echo(pray($exploded_ssn));
                $the_form->set_value('ssn_1', $ssn1);
                $the_form->set_value('ssn_2', $ssn2);
                $the_form->set_value('ssn_3', $ssn3);
            }
        }
    }
    connectDB(REASON_DB);
}

/*
 * Write application data to database
 */

function set_applicant_data($openid, &$the_form) {
    connectDB('admissions_applications_connection');
    $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . mysql_real_escape_string($openid) . "' ";
    $results = db_query($qstring);
    if (mysql_num_rows($results) < 1) {
        $qstring = "INSERT INTO `applicants` (`open_id`, `creation_date`, `submitter_ip`)
            VALUES ('" . mysql_real_escape_string($openid) . "', NOW(), '" . $_SERVER['REMOTE_ADDR'] . "'); ";
        $results = mysql_query($qstring) or die(mysql_error());
        $qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . mysql_real_escape_string($openid) . "' ";
        $results = db_query($qstring);
    }
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        $qstring = "UPDATE `applicants` SET ";
        foreach ($the_form->get_element_names() as $element) {
            if (array_key_exists($element, $row)) {
                $qstring .= $element . "=";
                if ((!is_null($the_form->get_value($element))) && ($the_form->get_value($element) <> '')) {
                    if (is_array($the_form->get_value($element))) {
                        $qstring .= "'" . mysql_real_escape_string(implode(',', $the_form->get_value($element))) . "'";
                    } else {
                        $qstring .= "'" . mysql_real_escape_string($the_form->get_value($element)) . "'";
                    }
                } else {
                    $qstring .= 'NULL';
                }
                $qstring .= ", ";
            }
            if ($element == 'ssn_1') {
                if ($the_form->get_value('ssn_1') || $the_form->get_value('ssn_2') || $the_form->get_value('ssn_3')) {
                    $qstring .= "`ssn` = '" . mysql_real_escape_string($the_form->get_value('ssn_1')) . "-" . mysql_real_escape_string($the_form->get_value('ssn_2')) . "-" . mysql_real_escape_string($the_form->get_value('ssn_3')) . "', ";
                }
            }
        }
        // ssn is 3 individual form elements, combine and write to db
        $qstring .= "`last_update`=NOW()";
//        $qstring = rtrim($qstring, ' ,');
        $qstring .= " WHERE `open_id`= '" . mysql_real_escape_string($openid) . "' ";
        //die($qstring);
    }
    $qresult = db_query($qstring);
    connectDB(REASON_DB);
}

function get_open_id() {
    $the_sess = get_reason_session();
    return $the_sess->get('openid_id');
}

function get_data($qstring) {
    connectDB('admissions_applications_connection');
    $results = db_query($qstring);
    connectDB(REASON_DB);
    return $results;
}

function validate_page1(&$the_form) {
    /* Required fields: student_type, enrollment_term, citizenship_status */
    $elements = array('student_type', 'enrollment_term', 'citizenship_status');

    $qstring = "SELECT ";
    foreach ($elements as $element) {
        $qstring .= $element . ", ";
    }
    $qstring = rtrim($qstring, ", ");
    $qstring .= " FROM applicants " .
            "WHERE open_id = '" . get_open_id() . "';";

    $results = get_data($qstring);
    $valid = True;
    $return = array();

    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        foreach ($elements as $element) {
            if (is_null($row[$element])) {
                $valid = False;
                $return[$element] = $the_form->get_display_name($element);
            }
        }
    }

    $return['valid'] = $valid;
    return $return;
}

function validate_page2(&$the_form) {
    /*
     * Required fields: first_name, middle_name, last_name, gender, date_of_birth,
     *                  email, home_phone, permanent_address, permanent_city,
     *                  permanent_state_province, permanent_state_province, permanent_zip_postal,
     *                  permanent_country,
     *                  different_mailing_address (mailing_address, mailing_city,
     *                  mailing_state_province, mailing_zip_postal, mailing_country)
     */

    $qstring = "SELECT first_name, middle_name, last_name, gender, date_of_birth, " .
            "email, home_phone, permanent_address, permanent_city, " .
            "permanent_state_province, permanent_state_province, permanent_zip_postal, " .
            "permanent_country, different_mailing_address, mailing_address, mailing_city, " .
            "mailing_state_province, mailing_zip_postal, mailing_country " .
            "FROM applicants " .
            "WHERE open_id = '" . get_open_id() . "';";
    $results = get_data($qstring);
    $valid = True;
    $return = array();

    //should only be one row to loop through
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {

        //check always required fields
        if (empty($row['first_name'])) {
            $valid = False;
            $return['first_name'] = $the_form->get_display_name('first_name');
        }
        if (empty($row['middle_name'])) {
            $valid = False;
            $return['middle_name'] = $the_form->get_display_name('middle_name');
        }
        if (empty($row['last_name'])) {
            $valid = False;
            $return['last_name'] = $the_form->get_display_name('last_name');
        }
        if (empty($row['gender'])) {
            $valid = False;
            $return['gender'] = $the_form->get_display_name('gender');
        }
        if (empty($row['date_of_birth'])) {
            $valid = False;
            $return['date_of_birth'] = $the_form->get_display_name('date_of_birth');
        }
        if (empty($row['email'])) {
            $valid = False;
            $return['email'] = $the_form->get_display_name('email');
        }
        if (empty($row['home_phone'])) {
            $valid = False;
            $return['home_phone'] = $the_form->get_display_name('home_phone');
        }
        if (empty($row['permanent_address'])) {
            $valid = False;
            $return['permanent_address'] = $the_form->get_display_name('permanent_address');
        }
        if (empty($row['permanent_city'])) {
            $valid = False;
            $return['permanent_city'] = $the_form->get_display_name('permanent_city');
        }
        if (empty($row['permanent_state_province'])) {
            $valid = False;
            $return['permanent_state_province'] = $the_form->get_display_name('permanent_state_province');
        }
        if (empty($row['permanent_zip_postal'])) {
            $valid = False;
            $return['permanent_zip_postal'] = $the_form->get_display_name('permanent_zip_postal');
        }
        if (empty($row['permanent_country'])) {
            $valid = False;
            $return['permanent_country'] = $the_form->get_display_name('permanent_country');
        }

        //if different_mailing_address is "Yes", check associated fields
        if (($row['different_mailing_address']) == "Yes") {
            if (is_null($row['mailing_address'])) {
                $valid = False;
                $return['mailing_address'] = 'Mailing Address';
            }
            if (empty($row['mailing_city'])) {
                $valid = False;
                $return['mailing_city'] = 'Mailing City';
            }
            if (empty($row['mailing_state_province'])) {
                $valid = False;
                $return['mailing_state_province'] = 'Mailing State/Province';
            }
            if (empty($row['mailing_zip_postal'])) {
                $valid = False;
                $return['mailing_zip_postal'] = 'Mailing Zip/Postal';
            }
            if (empty($row['mailing_country'])) {
                $valid = False;
                $return['mailing_country'] = 'Mailing Country';
            }
        }
    }

    $return['valid'] = $valid;

    return $return;
}

function validate_page3(&$the_form) {
    /*
     * Required Fields: permanent_home_parent,
     *                  based on permanent_home_parent:  parent_1_first_name, parent_1_middle_name,
     *                  parent_1_last_name, parent_1_address, parent_1_city, parent_1_state_province,
     *                  parent_1_zip_postal, parent_1_country, parent_1_phone, parent_1_email,
     *                  parent_1_occupation (or replace "parent_1" with "parent_2" or "guardian"),
     *                  legacy,
     *                  based on legacy:  parent_1_college/parent_2_college/guardian_college
     */

    $qstring = "SELECT permanent_home_parent, " .
            "parent_1_first_name, parent_1_last_name, " .
            "parent_1_address, parent_1_city, parent_1_state_province, parent_1_zip_postal, parent_1_country, " .
            "parent_1_phone, parent_1_occupation, " .
            "parent_2_first_name, parent_2_last_name, " .
            "parent_2_address_same, parent_2_address, parent_2_city, parent_2_state_province, parent_2_zip_postal, parent_2_country, " .
            "parent_2_phone, parent_2_occupation, " .
            "guardian_first_name, guardian_last_name, " .
            "guardian_address, guardian_city, guardian_state_province, guardian_zip_postal, guardian_country, " .
            "guardian_phone, guardian_occupation, " .
            "legacy, " .
            "parent_1_college, parent_2_college, guardian_college, " .
            "parent_1_living, parent_2_living " .
            "FROM applicants " .
            "WHERE open_id = '" . get_open_id() . "';";

    $results = get_data($qstring);
    $valid = True;
    $return = array();

    //should only be one row to loop through
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        if (is_null($row['permanent_home_parent'])) {
            $valid = False;
            $return['permanent_home_parent'] = 'Permanent Home Parent';
        }
        switch ($row['permanent_home_parent']) {
            case 'parent1':
                if (empty($row['parent_1_first_name'])) {
                    $valid = False;
                    $return['parent_1_first_name'] = $the_form->get_display_name('parent_1_first_name');
                }
                if (empty($row['parent_1_last_name'])) {
                    $valid = False;
                    $return['parent_1_last_name'] = $the_form->get_display_name('parent_1_last_name');
                }
                if ($row['parent_1_living'] != 'no') {
                    if (empty($row['parent_1_address'])) {
                        $valid = False;
                        $return['parent_1_address'] = $the_form->get_display_name('parent_1_address');
                    }
                    if (empty($row['parent_1_city'])) {
                        $valid = False;
                        $return['parent_1_city'] = $the_form->get_display_name('parent_1_city');
                    }
                    if (empty($row['parent_1_state_province'])) {
                        $valid = False;
                        $return['parent_1_state_province'] = $the_form->get_display_name('parent_1_state_province');
                    }
                    if (empty($row['parent_1_zip_postal'])) {
                        $valid = False;
                        $return['parent_1_zip_postal'] = $the_form->get_display_name('parent_1_zip_postal');
                    }
                    if (empty($row['parent_1_country'])) {
                        $valid = False;
                        $return['parent_1_country'] = $the_form->get_display_name('parent_1_country');
                    }

                    if (empty($row['parent_1_phone'])) {
                        $valid = False;
                        $return['parent_1_phone'] = $the_form->get_display_name('parent_1_phone');
                    }
                    if (empty($row['parent_1_occupation'])) {
                        $valid = False;
                        $return['parent_1_occupation'] = $the_form->get_display_name('parent_1_occupation');
                    }
                }
                break;
            case 'parent2':
                if (empty($row['parent_2_first_name'])) {
                    $valid = False;
                    $return['parent_2_first_name'] = $the_form->get_display_name('parent_2_first_name');
                }
                if (empty($row['parent_2_last_name'])) {
                    $valid = False;
                    $return['parent_2_last_name'] = $the_form->get_display_name('parent_2_last_name');
                }
                if (($row['parent_2_living'] != 'no') && ($row['parent_2_address_same'] == 'no')) {
                    if (empty($row['parent_2_address'])) {
                        $valid = False;
                        $return['parent_2_address'] = $the_form->get_display_name('parent_2_address');
                    }
                    if (empty($row['parent_2_city'])) {
                        $valid = False;
                        $return['parent_2_city'] = $the_form->get_display_name('parent_2_city');
                    }
                    if (empty($row['parent_2_state_province'])) {
                        $valid = False;
                        $return['parent_2_state_province'] = $the_form->get_display_name('parent_2_state_province');
                    }
                    if (empty($row['parent_2_zip_postal'])) {
                        $valid = False;
                        $return['parent_2_zip_postal'] = $the_form->get_display_name('parent_2_zip_postal');
                    }
                    if (empty($row['parent_2_country'])) {
                        $valid = False;
                        $return['parent_2_country'] = $the_form->get_display_name('parent_2_country');
                    }

                    if (empty($row['parent_2_phone'])) {
                        $valid = False;
                        $return['parent_2_phone'] = $the_form->get_display_name('parent_2_phone');
                    }
                    if (empty($row['parent_2_occupation'])) {
                        $valid = False;
                        $return['parent_2_occupation'] = $the_form->get_display_name('parent_2_occupation');
                    }
                }
                break;
            case 'guardian':
                if (empty($row['guardian_first_name'])) {
                    $valid = False;
                    $return['guardian_first_name'] = $the_form->get_display_name('guardian_first_name');
                }
                if (empty($row['guardian_last_name'])) {
                    $valid = False;
                    $return['guardian_last_name'] = $the_form->get_display_name('guardian_last_name');
                }

                if (empty($row['guardian_address'])) {
                    $valid = False;
                    $return['guardian_address'] = $the_form->get_display_name('guardian_address');
                }
                if (empty($row['guardian_city'])) {
                    $valid = False;
                    $return['guardian_city'] = $the_form->get_display_name('guardian_city');
                }
                if (empty($row['guardian_state_province'])) {
                    $valid = False;
                    $return['guardian_state_province'] = $the_form->get_display_name('guardian_state_province');
                }
                if (empty($row['guardian_zip_postal'])) {
                    $valid = False;
                    $return['guardian_zip_postal'] = $the_form->get_display_name('guardian_zip_postal');
                }
                if (empty($row['guardian_country'])) {
                    $valid = False;
                    $return['guardian_country'] = $the_form->get_display_name('guardian_country');
                }

                if (empty($row['guardian_phone'])) {
                    $valid = False;
                    $return['guardian_phone'] = $the_form->get_display_name('guardian_phone');
                }
                if (empty($row['guardian_occupation'])) {
                    $valid = False;
                    $return['guardian_occupation'] = $the_form->get_display_name('guardian_occupation');
                }
                break;
            case 'both':
                //parent 1 info
                if (empty($row['parent_1_first_name'])) {
                    $valid = False;
                    $return['parent_1_first_name'] = $the_form->get_display_name('parent_1_first_name');
                }
                if (empty($row['parent_1_last_name'])) {
                    $valid = False;
                    $return['parent_1_last_name'] = $the_form->get_display_name('parent_1_last_name');
                }
                if (empty($row['parent_1_address'])) {
                    $valid = False;
                    $return['parent_1_address'] = $the_form->get_display_name('parent_1_address');
                }
                if (empty($row['parent_1_city'])) {
                    $valid = False;
                    $return['parent_1_city'] = $the_form->get_display_name('parent_1_city');
                }
                if (empty($row['parent_1_state_province'])) {
                    $valid = False;
                    $return['parent_1_state_province'] = $the_form->get_display_name('parent_1_state_province');
                }
                if (empty($row['parent_1_zip_postal'])) {
                    $valid = False;
                    $return['parent_1_zip_postal'] = $the_form->get_display_name('parent_1_zip_postal');
                }
                if (empty($row['parent_1_country'])) {
                    $valid = False;
                    $return['parent_1_country'] = $the_form->get_display_name('parent_1_country');
                }

                if (empty($row['parent_1_phone'])) {
                    $valid = False;
                    $return['parent_1_phone'] = $the_form->get_display_name('parent_1_phone');
                }
                if (empty($row['parent_1_occupation'])) {
                    $valid = False;
                    $return['parent_1_occupation'] = $the_form->get_display_name('parent_1_occupation');
                }

                //parent 2 info
                if (empty($row['parent_2_first_name'])) {
                    $valid = False;
                    $return['parent_2_first_name'] = $the_form->get_display_name('parent_2_first_name');
                }
                if (empty($row['parent_2_last_name'])) {
                    $valid = False;
                    $return['parent_2_last_name'] = $the_form->get_display_name('parent_2_last_name');
                }
                if ($row['parent_2_address_same'] == 'no') {
                    if (empty($row['parent_2_address'])) {
                        $valid = False;
                        $return['parent_2_address'] = $the_form->get_display_name('parent_2_address');
                    }
                    if (empty($row['parent_2_city'])) {
                        $valid = False;
                        $return['parent_2_city'] = $the_form->get_display_name('parent_2_city');
                    }
                    if (empty($row['parent_2_state_province'])) {
                        $valid = False;
                        $return['parent_2_state_province'] = $the_form->get_display_name('parent_2_state_province');
                    }
                    if (empty($row['parent_2_zip_postal'])) {
                        $valid = False;
                        $return['parent_2_zip_postal'] = $the_form->get_display_name('parent_2_zip_postal');
                    }
                    if (empty($row['parent_2_country'])) {
                        $valid = False;
                        $return['parent_2_country'] = $the_form->get_display_name('parent_2_country');
                    }
                    if (empty($row['parent_2_phone'])) {
                        $valid = False;
                        $return['parent_2_phone'] = $the_form->get_display_name('parent_2_phone');
                    }
                }

                if (is_null($row['parent_2_occupation'])) {
                    $valid = False;
                    $return['parent_2_occupation'] = $the_form->get_display_name('parent_2_occupation');
                }
                break;
            default:
                break;
        }

        switch ($row['legacy']) {
            case 'Yes':
                if (empty($row['parent_1_college']) && empty($row['parent_2_college']) && empty($row['guardian_college'])) {
                    $valid = False;
                    $return['parent_1_college'] = $the_form->get_display_name('parent_1_college');
                    $return['parent_2_college'] = $the_form->get_display_name('parent_2_college');
                    $return['guardian_college'] = $the_form->get_display_name('guardian_college');
                }
                break;
            default:
                break;
        }
    }

    $return['valid'] = $valid;
    return $return;
}

function validate_page4(&$the_form) {
    /*
     * Required Fields: hs_name, hs_grad_year, based on student_type:  college_1_name
     */
    $qstring = "SELECT student_type, hs_name, hs_grad_year, college_1_name " .
            "FROM applicants " .
            "WHERE open_id = '" . get_open_id() . "';";
    $results = get_data($qstring);
    $valid = True;
    $return = array();

    //should only be one row to loop through
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        if (empty($row['hs_name'])) {
            $valid = False;
            $return['hs_name'] = $the_form->get_display_name('hs_name');
        }
        if (empty($row['hs_grad_year'])) {
            $valid = False;
            $return['hs_grad_year'] = $the_form->get_display_name('hs_grad_year');
        }
        if (($row['student_type']) == "TR") {
            if (empty($row['college_1_name'])) {
                $valid = False;
                $return['college_1_name'] = $the_form->get_display_name('college_1_name');
            }
        }
    }

    $return['valid'] = $valid;
    return $return;
}

function validate_page5(&$the_form) {
    /*
     * Required Fields: based on activity_1, if 'other' require activity_1_other (same for all activities)
     */
    $qstring = "SELECT activity_1, activity_2, activity_3, activity_4, activity_5, " .
            "activity_6, activity_7, activity_8, activity_9, activity_10, " .
            "activity_1_other, activity_2_other, activity_3_other, activity_4_other, activity_5_other, " .
            "activity_6_other, activity_7_other, activity_8_other, activity_9_other, activity_10_other " .
            "FROM applicants " .
            "WHERE open_id = '" . get_open_id() . "';";
    $results = get_data($qstring);
    $valid = True;
    $return = array();
    //should only be one row to loop through
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        if ((($row['activity_1']) == "Other") || ($row['activity_1']) == "PROGV") {
            if (empty($row['activity_1_other'])) {
                $valid = False;
                $return['activity_1_other'] = 'Other/Volunteer Program details - activity 1';
            }
        }
        if ((($row['activity_2']) == "Other") || ($row['activity_2']) == "PROGV") {
            if (empty($row['activity_2_other'])) {
                $valid = False;
                $return['activity_2_other'] = 'Other/Volunteer Program details - activity 2';
            }
        }
        if ((($row['activity_3']) == "Other") || ($row['activity_3']) == "PROGV") {
            if (empty($row['activity_3_other'])) {
                $valid = False;
                $return['activity_3_other'] = 'Other/Volunteer Program details - activity 3';
            }
        }
        if ((($row['activity_4']) == "Other") || ($row['activity_4']) == "PROGV") {
            if (empty($row['activity_4_other'])) {
                $valid = False;
                $return['activity_4_other'] = 'Other/Volunteer Program details - activity 4';
            }
        }
        if ((($row['activity_5']) == "Other") || ($row['activity_5']) == "PROGV") {
            if (empty($row['activity_5_other'])) {
                $valid = False;
                $return['activity_5_other'] = 'Other/Volunteer Program details - activity 5';
            }
        }
        if ((($row['activity_6']) == "Other") || ($row['activity_6']) == "PROGV") {
            if (empty($row['activity_6_other'])) {
                $valid = False;
                $return['activity_6_other'] = 'Other/Volunteer Program details - activity 6';
            }
        }
        if ((($row['activity_7']) == "Other") || ($row['activity_7']) == "PROGV") {
            if (empty($row['activity_7_other'])) {
                $valid = False;
                $return['activity_7_other'] = 'Other/Volunteer Program details - activity 7';
            }
        }
        if ((($row['activity_8']) == "Other") || ($row['activity_8']) == "PROGV") {
            if (empty($row['activity_8_other'])) {
                $valid = False;
                $return['activity_8_other'] = 'Other/Volunteer Program details - activity 8';
            }
        }
        if ((($row['activity_9']) == "Other") || ($row['activity_9']) == "PROGV") {
            if (empty($row['activity_9_other'])) {
                $valid = False;
                $return['activity_9_other'] = 'Other/Volunteer Program details - activity 9';
            }
        }
        if ((($row['activity_10']) == "Other") || ($row['activity_10']) == "PROGV") {
            if (empty($row['activity_10_other'])) {
                $valid = False;
                $return['activity_10_other'] = 'Other/Volunteer Program details - activity 10';
            }
        }
    }

    $return['valid'] = $valid;
    return $return;
}

function validate_page6(&$the_form) {
    /*
     * Required Fields: college_plan_1, based on music_audition:  music_audition_instrument, financial_aid
     * based on conviction_history: conviction_history_details, based on hs_discipline: hs_discipline_details
     * honesty_statement
     */
    $qstring = "SELECT college_plan_1, music_audition, music_audition_instrument, financial_aid, conviction_history, " .
            "conviction_history_details, hs_discipline, hs_discipline_details, honesty_statement " .
            "FROM applicants " .
            "WHERE open_id = '" . get_open_id() . "';";
    $results = get_data($qstring);
    $valid = True;
    $return = array();
    //should only be one row to loop through
    while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
        if (empty($row['college_plan_1'])) {
            $valid = False;
            $return['college_plan_1'] = $the_form->get_display_name('college_plan_1');
        }
        if (($row['music_audition']) == "Yes") {
            if (empty($row['music_audition_instrument'])) {
                $valid = False;
                $return['music_audition_instrument'] = 'Music Audition Instrument';
            }
        }
        if (empty($row['financial_aid'])) {
            $valid = False;
            $return['financial_aid'] = 'Financial Aid';
        }
        if (empty($row['conviction_history'])) {
            $valid = False;
            $return['conviction_history'] = 'Conviction History';
        }
        if (($row['conviction_history']) == "Yes") {
            if (empty($row['conviction_history_details'])) {
                $valid = False;
                $return['conviction_history_details'] = 'Conviction History Details';
            }
        }
        if (empty($row['hs_discipline'])) {
            $valid = False;
            $return['hs_discipline'] = 'High School Discipline';
        }
        if (($row['hs_discipline']) == "Yes") {
            if (empty($row['hs_discipline_details'])) {
                $valid = False;
                $return['hs_discipline_details'] = 'High School Discipline Details';
            }
        }
        if (empty($row['honesty_statement'])) {
            $valid = False;
            $return['honesty_statement'] = 'Honesty Statement';
        }
    }
    $return['valid'] = $valid;
    return $return;
}

function already_submitted_message() {
    echo '<div style="padding:30px">It appears that you\'ve already submitted your application. If you\'d like to amend your application or have questions
                regarding, please contact the Admissions Office at 800-4-LUTHER ext. 1287.</div>';
}

?>
