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
//$query_string = "SELECT 'open_id', 'student_type', 'enrollment_term', 'citizenship_status', 'first_name', 'middle_name',
//'last_name', 'suffix', 'preferred_first_name', 'gender', DATE_FORMAT('date_of_birth', '%m/%d/%Y') as 'date_of_birth', 'ssn', 'email', 'home_phone',
//'cell_phone', 'permanent_address', 'permanent_address_2', 'permanent_apartment_number', 'permanent_city',
//'permanent_state_province', 'permanent_zip_postal', 'permanent_country', 'different_mailing_address',
//'mailing_address', 'mailing_address_2', 'mailing_apartment_number', 'mailing_city', 'mailing_state_province',
//'mailing_zip_postal', 'mailing_country',
//CASE 'heritage'
//    WHEN 'No' THEN 'NHS'
//    WHEN 'HI' THEN 'HIS'
//    ELSE ''
//END as 'heritage',
//'race', 'church_name', 'church_city', 'church_state',
//'religion', 'parent_marital_status', 'permanent_home_parent', 'parent_1_type', 'parent_1_living',
//'parent_1_title', 'parent_1_first_name', 'parent_1_middle_name', 'parent_1_last_name', 'parent_1_suffix',
//'parent_1_address', 'parent_1_address_2', 'parent_1_apartment_number', 'parent_1_city', 'parent_1_state_province',
//'parent_1_zip_postal', 'parent_1_country', 'parent_1_phone_type', 'parent_1_phone', 'parent_1_email',
//'parent_1_occupation', 'parent_1_employer', 'parent_1_college', 'parent_1_college_ceeb', 'parent_2_type',
//'parent_2_living', 'parent_2_title', 'parent_2_first_name', 'parent_2_middle_name', 'parent_2_last_name',
//'parent_2_suffix', 'parent_2_address_same', 'parent_2_address', 'parent_2_address_2', 'parent_2_apartment_number',
//'parent_2_city', 'parent_2_state_province', 'parent_2_zip_postal', 'parent_2_country', 'parent_2_phone_type',
//'parent_2_phone', 'parent_2_email', 'parent_2_occupation', 'parent_2_employer', 'parent_2_college',
//'parent_2_college_ceeb', 'guardian_relation', 'guardian_type', 'guardian_living', 'guardian_title',
//'guardian_first_name', 'guardian_middle_name', 'guardian_last_name', 'guardian_suffix', 'guardian_address',
//'guardian_address_2', 'guardian_apartment_number', 'guardian_city', 'guardian_state_province',
//'guardian_zip_postal', 'guardian_country', 'guardian_phone_type', 'guardian_phone', 'guardian_email',
//'guardian_occupation', 'guardian_employer', 'guardian_college', 'guardian_college_ceeb', 'legacy',
//'sibling_1_relation', 'sibling_1_first_name', 'sibling_1_last_name', 'sibling_1_age', 'sibling_1_grade',
//'sibling_1_college', 'sibling_1_college_ceeb',
//'sibling_2_relation', 'sibling_2_first_name', 'sibling_2_last_name', 'sibling_2_age', 'sibling_2_grade',
//'sibling_2_college', 'sibling_2_college_ceeb',
//'sibling_3_relation', 'sibling_3_first_name', 'sibling_3_last_name', 'sibling_3_age', 'sibling_3_grade',
//'sibling_3_college', 'sibling_3_college_ceeb',
//'sibling_4_relation', 'sibling_4_first_name', 'sibling_4_last_name', 'sibling_4_age', 'sibling_4_grade',
//'sibling_4_college', 'sibling_4_college_ceeb',
//'sibling_5_relation', 'sibling_5_first_name', 'sibling_5_last_name', 'sibling_5_age', 'sibling_5_grade',
//'sibling_5_college', 'sibling_5_college_ceeb',
//'hs_name', 'hs_ceeb', 'hs_grad_year', 'college_1_name', 'college_1_ceeb', 'college_2_name', 'college_2_ceeb',
//'college_3_name', 'college_3_ceeb', 'taken_tests', 'sat_math', 'sat_critical_reading', 'sat_writing',
//'act_composite',
//'activity_1', 'activity_1_other', 'activity_1_participation', 'activity_1_honors',
//'activity_2', 'activity_2_other', 'activity_2_participation', 'activity_2_honors',
//'activity_3', 'activity_3_other', 'activity_3_participation', 'activity_3_honors',
//'activity_4', 'activity_4_other', 'activity_4_participation', 'activity_4_honors',
//'activity_5', 'activity_5_other', 'activity_5_participation', 'activity_5_honors',
//'activity_6', 'activity_6_other', 'activity_6_participation', 'activity_6_honors',
//'activity_7', 'activity_7_other', 'activity_7_participation', 'activity_7_honors',
//'activity_8', 'activity_8_other', 'activity_8_participation', 'activity_8_honors',
//'activity_9', 'activity_9_other', 'activity_9_participation', 'activity_9_honors',
//'activity_10', 'activity_10_other', 'activity_10_participation', 'activity_10_honors',
//'college_plan_1', 'college_plan_2', 'music_audition', 'music_audition_instrument', 'financial_aid', 'influences',
//'other_colleges', personal_statement, 'conviction_history', 'conviction_history_details', 'hs_discipline',
//'hs_discipline_details', 'honesty_statement', 'submitter_ip', 'creation_date', 'submit_date', 'do_not_contact',
//'last_update' ";

    $query_string = "SELECT IFNULL(open_id, '') AS open_id, IFNULL(student_type, '') AS student_type, IFNULL(enrollment_term, '') AS enrollment_term, IFNULL(citizenship_status, '') AS citizenship_status,
IFNULL(first_name, '') AS first_name, IFNULL(middle_name, '') AS middle_name, IFNULL(last_name, '') AS last_name, IFNULL(suffix, '') AS suffix, IFNULL(preferred_first_name, '') AS preferred_first_name, IFNULL(gender, '') AS gender,
IFNULL(DATE_FORMAT(date_of_birth, '%m/%d/%Y'), '') as date_of_birth, IFNULL(ssn, '') AS ssn, IFNULL(email, '') AS email, IFNULL(home_phone, '') AS home_phone,
IFNULL(cell_phone, '')as cell_phone, IFNULL(permanent_address, '')as permanent_address, IFNULL(permanent_address_2, '') as permanent_address_2, IFNULL(permanent_apartment_number, '') as permanent_apartment_number,
IFNULL(permanent_city, '') AS permanent_city, IFNULL(permanent_state_province, '') AS permanent_state_province, IFNULL(permanent_zip_postal, '') AS permanent_zip_postal, IFNULL(permanent_country, '') AS permanent_country,
IFNULL(different_mailing_address, '') AS different_mailing_address, IFNULL(mailing_address, '') AS mailing_address, IFNULL(mailing_address_2, '') AS mailing_address_2, IFNULL(mailing_apartment_number, '') AS mailing_apartment_number,
IFNULL(mailing_city, '') AS mailing_city, IFNULL(mailing_state_province, '') AS mailing_state_province, IFNULL(mailing_zip_postal, '') AS mailing_zip_postal, IFNULL(mailing_country, '') AS mailing_country,
CASE heritage
    WHEN 'No' THEN 'NHS'
    WHEN 'HI' THEN 'HIS'
    ELSE ''
END as heritage,
IFNULL(race, '') AS race, IFNULL(church_name, '') AS church_name, IFNULL(church_city, '') AS church_city, IFNULL(church_state, '') AS church_state, IFNULL(religion, '') AS religion, IFNULL(parent_marital_status, '') AS parent_marital_status,
IFNULL(permanent_home_parent, '') AS permanent_home_parent, IFNULL(parent_1_type, '') AS parent_1_type, IFNULL(parent_1_living, '') AS parent_1_living, IFNULL(parent_1_title, '') AS parent_1_title,
IFNULL(parent_1_first_name, '') AS parent_1_first_name, IFNULL(parent_1_middle_name, '') AS parent_1_middle_name, IFNULL(parent_1_last_name, '') AS parent_1_last_name, IFNULL(parent_1_suffix, '') AS parent_1_suffix,
IFNULL(parent_1_address, '') AS parent_1_address, IFNULL(parent_1_address_2, '') AS parent_1_address_2, IFNULL(parent_1_apartment_number, '') AS parent_1_apartment_number, IFNULL(parent_1_city, '') AS parent_1_city,
IFNULL(parent_1_state_province, '') AS parent_1_state_province, IFNULL(parent_1_zip_postal, '') AS parent_1_zip_postal, IFNULL(parent_1_country, '') AS parent_1_country, IFNULL(parent_1_phone_type, '') AS parent_1_phone_type,
IFNULL(parent_1_phone, '') AS parent_1_phone, IFNULL(parent_1_email, '') AS parent_1_email, IFNULL(parent_1_occupation, '') AS parent_1_occupation, IFNULL(parent_1_employer, '') AS parent_1_employer,
IFNULL(parent_1_college, '') AS parent_1_college, IFNULL(parent_1_college_ceeb, '') AS parent_1_college_ceeb, IFNULL(parent_2_type, '') AS parent_2_type, IFNULL(parent_2_living, '') AS parent_2_living,
IFNULL(parent_2_title, '') AS parent_2_title, IFNULL(parent_2_first_name, '') AS parent_2_first_name, IFNULL(parent_2_middle_name, '') AS parent_2_middle_name, IFNULL(parent_2_last_name, '') AS parent_2_last_name,
IFNULL(parent_2_suffix, '') AS parent_2_suffix, IFNULL(parent_2_address_same, '') AS parent_2_address_same, IFNULL(parent_2_address, '') AS parent_2_address, IFNULL(parent_2_address_2, '') AS parent_2_address_2,
IFNULL(parent_2_apartment_number, '') AS parent_2_apartment_number, IFNULL(parent_2_city, '') AS parent_2_city, IFNULL(parent_2_state_province, '') AS parent_2_state_province, IFNULL(parent_2_zip_postal, '') AS parent_2_zip_postal,
IFNULL(parent_2_country, '') AS parent_2_country, IFNULL(parent_2_phone_type, '') AS parent_2_phone_type, IFNULL(parent_2_phone, '') AS parent_2_phone, IFNULL(parent_2_email, '') AS parent_2_email,
IFNULL(parent_2_occupation, '') AS parent_2_occupation, IFNULL(parent_2_employer, '') AS parent_2_employer, IFNULL(parent_2_college, '') AS parent_2_college, IFNULL(parent_2_college_ceeb, '') AS parent_2_college_ceeb,
IFNULL(guardian_relation, '') AS guardian_relation, IFNULL(guardian_type, '') AS guardian_type, IFNULL(guardian_living, '') AS guardian_living, IFNULL(guardian_title, '') AS guardian_title, IFNULL(guardian_first_name, '') AS guardian_first_name,
IFNULL(guardian_middle_name, '') AS guardian_middle_name, IFNULL(guardian_last_name, '') AS guardian_last_name, IFNULL(guardian_suffix, '') AS guardian_suffix, IFNULL(guardian_address, '') AS guardian_address,
IFNULL(guardian_address_2, '') AS guardian_address_2, IFNULL(guardian_apartment_number, '') AS guardian_apartment_number, IFNULL(guardian_city, '') AS guardian_city, IFNULL(guardian_state_province, '') AS guardian_state_province,
IFNULL(guardian_zip_postal, '') AS guardian_zip_postal, IFNULL(guardian_country, '') AS guardian_country, IFNULL(guardian_phone_type, '') AS guardian_phone_type, IFNULL(guardian_phone, '') AS guardian_phone,
IFNULL(guardian_email, '') AS guardian_email, IFNULL(guardian_occupation, '') AS guardian_occupation, IFNULL(guardian_employer, '') AS guardian_employer, IFNULL(guardian_college, '') AS guardian_college,
IFNULL(guardian_college_ceeb, '') AS guardian_college_ceeb, IFNULL(legacy, '') AS legacy, IFNULL(sibling_1_relation, '') AS sibling_1_relation, IFNULL(sibling_1_first_name, '') AS sibling_1_first_name,
IFNULL(sibling_1_last_name, '') AS sibling_1_last_name, IFNULL(sibling_1_age, '') AS sibling_1_age, IFNULL(sibling_1_grade, '') AS sibling_1_grade, IFNULL(sibling_1_college, '') AS sibling_1_college,
IFNULL(sibling_1_college_ceeb, '') AS sibling_1_college_ceeb, IFNULL(sibling_2_relation, '') AS sibling_2_relation, IFNULL(sibling_2_first_name, '') AS sibling_2_first_name, IFNULL(sibling_2_last_name, '') AS sibling_2_last_name,
IFNULL(sibling_2_age, '') AS sibling_2_age, IFNULL(sibling_2_grade, '') AS sibling_2_grade, IFNULL(sibling_2_college, '') AS sibling_2_college, IFNULL(sibling_2_college_ceeb, '') AS sibling_2_college_ceeb,
IFNULL(sibling_3_relation, '') AS sibling_3_relation, IFNULL(sibling_3_first_name, '') AS sibling_3_first_name, IFNULL(sibling_3_last_name, '') AS sibling_3_last_name, IFNULL(sibling_3_age, '') AS sibling_3_age, IFNULL(sibling_3_grade, '') AS sibling_3_grade,
IFNULL(sibling_3_college, '') AS sibling_3_college, IFNULL(sibling_3_college_ceeb, '') AS sibling_3_college_ceeb, IFNULL(sibling_4_relation, '') AS sibling_4_relation, IFNULL(sibling_4_first_name, '') AS sibling_4_first_name,
IFNULL(sibling_4_last_name, '') AS sibling_4_last_name, IFNULL(sibling_4_age, '') AS sibling_4_age, IFNULL(sibling_4_grade, '') AS sibling_4_grade, IFNULL(sibling_4_college, '') AS sibling_4_college,
IFNULL(sibling_4_college_ceeb, '') AS sibling_4_college_ceeb, IFNULL(sibling_5_relation, '') AS sibling_5_relation, IFNULL(sibling_5_first_name, '') AS sibling_5_first_name, IFNULL(sibling_5_last_name, '') AS sibling_5_last_name,
IFNULL(sibling_5_age, '') AS sibling_5_age, IFNULL(sibling_5_grade, '') AS sibling_5_grade, IFNULL(sibling_5_college, '') AS sibling_5_college, IFNULL(sibling_5_college_ceeb, '') AS sibling_5_college_ceeb,
IFNULL(hs_name, '') AS hs_name, IFNULL(hs_ceeb, '') AS hs_ceeb, IFNULL(hs_grad_year, '') AS hs_grad_year, IFNULL(college_1_name, '') AS college_1_name, IFNULL(college_1_ceeb, '') AS college_1_ceeb,
IFNULL(college_2_name, '') AS college_2_name, IFNULL(college_2_ceeb, '') AS college_2_ceeb, IFNULL(college_3_name, '') AS college_3_name, IFNULL(college_3_ceeb, '') AS college_3_ceeb, IFNULL(taken_tests,  '') AS taken_tests,
IFNULL(sat_math, '') AS sat_math, IFNULL(sat_critical_reading, '') AS sat_critical_reading, IFNULL(sat_writing, '') AS sat_writing, IFNULL(act_composite, '') AS act_composite, IFNULL(activity_1, '') AS activity_1,
IFNULL(activity_1_other, '') AS activity_1_other, IFNULL(activity_1_participation, '') AS activity_1_participation, IFNULL(activity_1_honors, '') AS activity_1_honors, IFNULL(activity_2, '') AS activity_2, IFNULL(activity_2_other, '') AS activity_2_other,
IFNULL(activity_2_participation, '') AS activity_2_participation, IFNULL(activity_2_honors, '') AS activity_2_honors, IFNULL(activity_3, '') AS activity_3, IFNULL(activity_3_other, '') AS activity_3_other,
IFNULL(activity_3_participation, '') AS activity_3_participation, IFNULL(activity_3_honors, '') AS activity_3_honors, IFNULL(activity_4, '') AS activity_4, IFNULL(activity_4_other, '') AS activity_4_other,
IFNULL(activity_4_participation, '') AS activity_4_participation, IFNULL(activity_4_honors, '') AS activity_4_honors, IFNULL(activity_5, '') AS activity_5, IFNULL(activity_5_other, '') AS activity_5_other,
IFNULL(activity_5_participation, '') AS activity_5_participation, IFNULL(activity_5_honors, '') AS activity_5_honors, IFNULL(activity_6, '') AS activity_6, IFNULL(activity_6_other, '') AS activity_6_other,
IFNULL(activity_6_participation, '') AS activity_6_participation, IFNULL(activity_6_honors, '') AS activity_6_honors, IFNULL(activity_7, '') AS activity_7, IFNULL(activity_7_other, '') AS activity_7_other,
IFNULL(activity_7_participation, '') AS activity_7_participation, IFNULL(activity_7_honors, '') AS activity_7_honors, IFNULL(activity_8, '') AS activity_8, IFNULL(activity_8_other, '') AS activity_8_other,
IFNULL(activity_8_participation, '') AS activity_8_participation, IFNULL(activity_8_honors, '') AS activity_8_honors, IFNULL(activity_9, '') AS activity_9, IFNULL(activity_9_other, '') AS activity_9_other,
IFNULL(activity_9_participation, '') AS activity_9_participation, IFNULL(activity_9_honors, '') AS activity_9_honors, IFNULL(activity_10, '') AS activity_10, IFNULL(activity_10_other, '') AS activity_10_other,
IFNULL(activity_10_participation, '') AS activity_10_participation, IFNULL(activity_10_honors, '') AS activity_10_honors, IFNULL(college_plan_1, '') AS college_plan_1, IFNULL(college_plan_2, '') AS college_plan_2,
IFNULL(music_audition, '') AS music_audition, IFNULL(music_audition_instrument, '') AS music_audition_instrument, IFNULL(financial_aid, '') AS financial_aid, IFNULL(influences, '') AS influences, IFNULL(other_colleges,  '') AS other_colleges,
IFNULL(personal_statement, '') AS personal_statement, IFNULL(conviction_history, '') AS conviction_history, IFNULL(conviction_history_details, '') AS conviction_history_details, IFNULL(hs_discipline, '') AS hs_discipline,
IFNULL(hs_discipline_details, '') AS hs_discipline_details, IFNULL(honesty_statement, '') AS honesty_statement, IFNULL(submitter_ip, '') AS submitter_ip, IFNULL(creation_date, '') AS creation_date, IFNULL(submit_date, '') AS submit_date,
IFNULL(do_not_contact, '') AS do_not_contact, IFNULL(last_update, '') AS last_update ";

    $export_query = ", IFNULL(export_date, '') AS export_date, IF_NULL(export_by, '') AS export_by ";

    $q_string_no_export_date = "FROM `applicants` WHERE `export_date` IS NULL AND submit_date IS NOT NULL ";
    $q_string_cumulative = "FROM `applicants` WHERE submit_date IS NOT NULL ";
    $q_string_unfinished = "FROM `applicants` WHERE submit_date IS NULL ";
    /**
     * Run the query to get new (unexported) applications
     * If successful, set `export_date` and `export_by` fields in the database
     */

    $no_export_date_results = db_query($query_string . $q_string_no_export_date);
    $num_rows = mysql_num_rows($no_export_date_results);
    if ($num_rows != 0) {
        $fname = "/var/reason/admissions_app_exports/application_exports/{$date}_app_export.csv";
        $fp = fopen($fname, 'w');
        $first_time = true;
        $i = 0;
        while ($row = mysql_fetch_array($no_export_date_results, MYSQL_ASSOC)) {
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
            $line = '"';
            foreach ($values as $value) {
//                $value = preg_replace("/\t/", "\\t ", $value);
//                $value = preg_replace(",", "\,", $value);
//                $value = preg_replace('"', '" ', $value);
                $value = preg_replace("/\r?\n/", "\\n ", $value);
//                if(strstr($value, '"')) $value = '"' . str_replace ('"', '""', $value) . '"';
//                if(strstr($value, ',')) $value = '"' . str_replace (',', '\,', $value) . '"';
                $line .= $value . '","';
//                $line .= addslashes($value) . '","';
            }
            $line .= '"';
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
            $qresult = db_query($qstring);
        }
    } else {
        echo "<script type='text/javascript'>alert('There are no newly submitted apps since the last export. Click OK to continue.') ;</script>";
    }

    $cumulative_results = db_query($query_string . $q_string_cumulative);
    $cumulative_num_rows = mysql_num_rows($cumulative_results);
    //echo $query_string;
    // output settings

    if ($cumulative_num_rows) {
        $fname = "/var/reason/admissions_app_exports/application_exports/cumulative_exported.csv";
        $fp = fopen($fname, 'w');
        $first_time = true;

        while ($row = mysql_fetch_array($cumulative_results, MYSQL_ASSOC)) {
            if ($first_time) {
                $keys = array_keys($row);
                $line = '"'.implode('","',$keys).'"';
                fwrite($fp, $line."\n");
                $first_time = false;
            }
            $values = array_values($row);
            $line = '"';
            foreach ($values as $value) {
//                $value = preg_replace("/\t/", "\\t ", $value);
//                $value = preg_replace(",", "\,", $value);
//                $value = preg_replace('"', '" ', $value);
                $value = preg_replace("/\r?\n/", "\\n ", $value);
//                if(strstr($value, '"')) $value = '"' . str_replace ('"', '""', $value) . '"';
//                if(strstr($value, ',')) $value = '"' . str_replace (',', '\,', $value) . '"';
                $line .= $value . '","';
//                $line .= addslashes($value) . '","';
            }
            $line .= '"';
            $fwrite = fwrite($fp, $line."\n");
        }
        // close file
        $fclose = fclose($fp);

        // Do some small error checking
        if ($fclose === false || $fwrite === false) {
            die('There was a problem writing the latest applicants file. Please contact LIS for help');
        }
    }

    $unfinished_results = db_query($query_string . $q_string_unfinished);
    $unfinished_num_rows = mysql_num_rows($unfinished_results);

    if ($unfinished_num_rows) {
        $fname = "/var/reason/admissions_app_exports/application_exports/unfinished.csv";
        $fp = fopen($fname, 'w');
        $first_time = true;
        while ($row = mysql_fetch_array($unfinished_results, MYSQL_ASSOC)) {
            if ($first_time) {
                $keys = array_keys($row);
                $line = '"'.implode('","',$keys).'"';
                fwrite($fp, $line."\n");
                $first_time = false;
            }
            $values = array_values($row);
            $line = '"';
            foreach ($values as $value) {
//                $value = preg_replace("/\t/", "\\t ", $value);
//                $value = preg_replace(",", "\,", $value);
//                $value = preg_replace('"', '" ', $value);
                $value = preg_replace("/\r?\n/", "\\n ", $value);
//                if(strstr($value, '"')) $value = '"' . str_replace ('"', '""', $value) . '"';
//                if(strstr($value, ',')) $value = '"' . str_replace (',', '\,', $value) . '"';
                $line .= $value . '","';
//                $line .= addslashes($value) . '","';
            }
            $line .= '"';
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