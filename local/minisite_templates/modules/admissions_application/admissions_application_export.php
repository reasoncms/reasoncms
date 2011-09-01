<?php

include_once('reason_header.php');
require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
reason_include_once('function_libraries/user_functions.php');

force_secure_if_available();

$username = check_authentication();
$group = id_of('application_export_group');
$gh = new group_helper();
$gh->set_group_by_id($group);
$has_access = $gh->has_authorization($username);

$date = date("Ymd:his");
$yesterday = date("Y-m-d", strtotime("-1 day"));
//$yesterday = date("2011-07-29");

if ($has_access) {
    connectDB('admissions_applications_connection');

    $query_string = "SELECT open_id, student_type, enrollment_term, citizenship_status, first_name, middle_name,
last_name, suffix, preferred_first_name, gender, DATE_FORMAT(date_of_birth, '%m/%d/%Y') as date_of_birth, ssn, email, home_phone,
cell_phone, permanent_address, permanent_address_2, permanent_apartment_number, permanent_city,
permanent_state_province, permanent_zip_postal, permanent_country, different_mailing_address,
mailing_address, mailing_address_2, mailing_apartment_number, mailing_city, mailing_state_province,
mailing_zip_postal, mailing_country,
CASE heritage
    WHEN 'No' THEN 'NHS'
    WHEN 'HI' THEN 'HIS'
    ELSE ''
END as heritage,
race, church_name, church_city, church_state,
religion, parent_marital_status, permanent_home_parent, parent_1_type, parent_1_living,
parent_1_title, parent_1_first_name, parent_1_middle_name, parent_1_last_name, parent_1_suffix,
parent_1_address, parent_1_address_2, parent_1_apartment_number, parent_1_city, parent_1_state_province,
parent_1_zip_postal, parent_1_country, parent_1_phone_type, parent_1_phone, parent_1_email,
parent_1_occupation, parent_1_employer, parent_1_college, parent_1_college_ceeb, parent_2_type,
parent_2_living, parent_2_title, parent_2_first_name, parent_2_middle_name, parent_2_last_name,
parent_2_suffix, parent_2_address_same, parent_2_address, parent_2_address_2, parent_2_apartment_number,
parent_2_city, parent_2_state_province, parent_2_zip_postal, parent_2_country, parent_2_phone_type,
parent_2_phone, parent_2_email, parent_2_occupation, parent_2_employer, parent_2_college,
parent_2_college_ceeb, guardian_relation, guardian_type, guardian_living, guardian_title,
guardian_first_name, guardian_middle_name, guardian_last_name, guardian_suffix, guardian_address,
guardian_address_2, guardian_apartment_number, guardian_city, guardian_state_province,
guardian_zip_postal, guardian_country, guardian_phone_type, guardian_phone, guardian_email,
guardian_occupation, guardian_employer, guardian_college, guardian_college_ceeb, legacy,
sibling_1_relation, sibling_1_first_name, sibling_1_last_name, sibling_1_age, sibling_1_grade,
sibling_1_college, sibling_1_college_ceeb,
sibling_2_relation, sibling_2_first_name, sibling_2_last_name, sibling_2_age, sibling_2_grade,
sibling_2_college, sibling_2_college_ceeb,
sibling_3_relation, sibling_3_first_name, sibling_3_last_name, sibling_3_age, sibling_3_grade,
sibling_3_college, sibling_3_college_ceeb,
sibling_4_relation, sibling_4_first_name, sibling_4_last_name, sibling_4_age, sibling_4_grade,
sibling_4_college, sibling_4_college_ceeb,
sibling_5_relation, sibling_5_first_name, sibling_5_last_name, sibling_5_age, sibling_5_grade,
sibling_5_college, sibling_5_college_ceeb,
hs_name, hs_ceeb, hs_grad_year, college_1_name, college_1_ceeb, college_2_name, college_2_ceeb,
college_3_name, college_3_ceeb, taken_tests, sat_math, sat_critical_reading, sat_writing,
act_composite,
activity_1, activity_1_other, activity_1_participation, activity_1_honors,
activity_2, activity_2_other, activity_2_participation, activity_2_honors,
activity_3, activity_3_other, activity_3_participation, activity_3_honors,
activity_4, activity_4_other, activity_4_participation, activity_4_honors,
activity_5, activity_5_other, activity_5_participation, activity_5_honors,
activity_6, activity_6_other, activity_6_participation, activity_6_honors,
activity_7, activity_7_other, activity_7_participation, activity_7_honors,
activity_8, activity_8_other, activity_8_participation, activity_8_honors,
activity_9, activity_9_other, activity_9_participation, activity_9_honors,
activity_10, activity_10_other, activity_10_participation, activity_10_honors,
college_plan_1, college_plan_2, music_audition, music_audition_instrument, financial_aid, influences,
other_colleges, personal_statement, conviction_history, conviction_history_details, hs_discipline,
hs_discipline_details, honesty_statement, submitter_ip, creation_date, submit_date, do_not_contact,
last_update ";
    $q_string_no_export_date = "FROM `applicants` WHERE `export_date` IS NULL AND CAST(submit_date AS DATE) != '0000-00-00 00:00:00' ";
    $q_string_cummulative = "FROM `applicants`";

    /**
     * Run the query to get new (unexported) applications
     * If successful, set `export_date` and `export_by` fields in the database
     */
    $no_export_date_results = db_query($query_string . $q_string_no_export_date);
    $num_rows = mysql_num_rows($no_export_date_results);
    // output settings
//    die($num_rows."asdfasdfasdfasdfasdfasdfasdfasdfasdfasdf");
    if ($num_rows) {
        $fname = "/var/reason_admissions_app_exports/application_exports/{$date}_app_export.csv";
        $fp = fopen($fname, 'w');
        $first_time = true;
        $i = 0;
        while ($row = mysql_fetch_array($no_export_date_results, MYSQL_ASSOC)) {
//            echo $row['open_id'] . "\n";
            // store the open_ids for use later
            $open_id_array[$i] = $row['open_id'];
            $i++;
            if ($first_time) {
                $keys = array_keys($row);
                $line = '"'.implode('","',$keys).'"';
                fwrite($fp, $line."\n");
                $first_time = false;
            }
            $values = array_values($row);
            $line = '"'.implode('","',$values).'"';
            $fwrite = fwrite($fp, $line."\n");
        }
        // close file
        $fclose = fclose($fp);

        // Do some small error checking
        // If ok, update database fields
        if ($fclose === false || $fwrite === false) {
            die('There was a problem writing the latest applicants file. Please contact LIS for help');
        } else {
            $qstring = "UPDATE `applicants` SET `export_date`=NOW(), `export_by`='" . $username . "' WHERE `open_id` IN (";
            foreach($open_id_array as $o_id){
                $qstring .= '\'' . $o_id . '\', ';
            }                
            $qstring = rtrim($qstring,", ");
            $qstring .= ") ";

//            echo $qstring;
            
//            foreach ($keys as $key){
//                if ($key == 'open_id'){
//                }
//            }
            $qresult = db_query($qstring);
        }
//        die('wtf');
    }

    $cummulative_results = db_query($query_string . $q_string_cummulative);
    $num_rows = mysql_num_rows($cummulative_results);
    //echo $query_string;
    // output settings

    if ($cummulative_results) {
        $fname = "/var/reason_admissions_app_exports/application_exports/cummulative_app_export.csv";
        $fp = fopen($fname, 'w');
        $first_time = true;
        while ($row = mysql_fetch_array($cummulative_results, MYSQL_ASSOC)) {
            if ($first_time) {
                $keys = array_keys($row);
                $line = '"'.implode('","',$keys).'"';
                fwrite($fp, $line."\n");
                $first_time = false;
            }
            $values = array_values($row);
            $line = '"'.implode('","',$values).'"';
            $fwrite = fwrite($fp, $line."\n");
        }
        // close file
        $fclose = fclose($fp);

        // Do some small error checking
        if ($fclose === false || $fwrite === false) {
            die('There was a problem writing the latest applicants file. Please contact LIS for help');
        }
    }
    connectDB(REASON_DB);
}
?>
