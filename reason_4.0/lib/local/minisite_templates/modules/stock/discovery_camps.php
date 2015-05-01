<?php
// PayFlowPro processing class
// This class encapsulates some of the work of handling a real-time transaction.
// The process is already fairly abstract -- what this class mostly does is provide
// some intelligent input and error checking and reporting so you don't have to code it
// into your app.  Note: expiration needs to be 4 digits, i.e. moyr.
//
// Usage:
//
// require("giftclass.php");
// $pf = new gift;
// $pf->set_info($Amount,$CardNumber,"$ExpMonth$ExpYear",$BudgetNumber,$NameOnCard,$Comment);
// $pf->set_recur($RecurringAmount,$StartDateMMDDYYY,$NoOfPayments,$PayPeriod,$CustomerEmail)
// $result = $pf->transact();
//    To operate in test mode, use $result = $pf->transact('test') instead.
// if (!$pf->approved)  {
//    $message = $pf->message;
//    ... do something with the error message
// } else {
//    ... do something with a successful transaction.  The returned values
//    you need to save as records of the transaction are these two:
//
//    echo $result['PNREF'];
//    echo $result['AUTHCODE'];
// }


include('pfproclass.php');
require_once( CARL_UTIL_INC . 'db/db.php');

class discovery_campsPF extends pfpc {

var $trans_details = array();
var $result = array();
var $giver_id;

function transact($mode = '')  {
	if(isset($this->recur_amt)) {
		$this->result = parent::recur_transact($mode);
		if ($this->approved) $this->result['REFNUM'] = $this->result['RPREF'];
	} else {
		$this->result = parent::transact($mode);
		if ($this->approved) $this->result['REFNUM'] = $this->result['PNREF'];
	}
	if ($this->approved)  {
		$this->record_transaction($mode);
	}
	return $this->result;
}

function set_params($details) {
 $this->trans_details = $details;
 return true;
}

function set_confirmation_text($text) {
	connectDB('discovery_camps_connection');

	$qresult = db_query("UPDATE `camper` SET
	confirmation_message = '".addslashes($text)."' WHERE REFNUM = '".$this->result['REFNUM']."'");

	connectDB(REASON_DB);

}

function get_confirmation_text($id, $hash=0) {
	connectDB('discovery_camps_connection');

	$qresult = db_query("SELECT confirmation_message FROM `camper` WHERE REFNUM='$id'");

	if ($qresult) {
		$camper = mysql_fetch_array($qresult);
		return $camper['confirmation_message'];
	}

	connectDB(REASON_DB);
}

function record_transaction($mode = '') {
    connectDB('discovery_camps_connection');

    $qstring = "INSERT INTO `camper` SET
    REFNUM='".addslashes($this->result['REFNUM'])."',
    submitter_ip='".addslashes($this->trans_details['submitter_ip'])."',
    first_name='".addslashes($this->trans_details['first_name'])."',
    last_name='".addslashes($this->trans_details['last_name'])."',
    gender='".addslashes($this->trans_details['gender'])."',
    grade = '".addslashes($this->trans_details['grade'])."',
    age = '".addslashes($this->trans_details['age'])."',
    address='".addslashes($this->trans_details['address'])."',
    city='".addslashes($this->trans_details['city'])."',
    state_province = '".addslashes($this->trans_details['state_province'])."',
    zip = '".addslashes($this->trans_details['zip'])."',
    tshirt_size = '".addslashes($this->trans_details['t-shirt_size'])."',
    parent_guardian_name = '".addslashes($this->trans_details['parent_guardian_name'])."',
    home_phone = '".addslashes($this->trans_details['home_phone'])."',
    work_phone = '".addslashes($this->trans_details['work_phone'])."',
    email = '".addslashes($this->trans_details['e-mail'])."' ";
        
 //    if (array_key_exists('adventure_hunt', $this->trans_details)) {
	// $qstring .= ",
	// adventure_hunt = '".addslashes($this->trans_details['adventure_hunt'])."' ";
 //    }
    if (array_key_exists('ww_grade_1', $this->trans_details)) {
	$qstring .= ",
	ww_grade_1 = '".addslashes($this->trans_details['ww_grade_1'])."' ";
    }
    if (array_key_exists('ww_grade_2', $this->trans_details)) {
	$qstring .= ",
	ww_grade_2 = '".addslashes($this->trans_details['ww_grade_2'])."' ";
    }
    if (array_key_exists('adventurers', $this->trans_details)) {
	$qstring .= ",
	adventurers = '".addslashes($this->trans_details['adventurers'])."' ";
    }
    if (array_key_exists('survival_camp', $this->trans_details)) {
	$qstring .= ",
	survival_camp = '".addslashes($this->trans_details['survival_camp'])."' ";
    }
    if (array_key_exists('energy_camp', $this->trans_details)) {
	$qstring .= ",
	energy_camp = '".addslashes($this->trans_details['energy_camp'])."' ";
    }
 //    if (array_key_exists('edible_earth', $this->trans_details)) {
	// $qstring .= ",
	// edible_earth = '".addslashes($this->trans_details['edible_earth'])."' ";
 //    }
    if (array_key_exists('river_expeditioners', $this->trans_details)) {
    $qstring .= ",
    river_expeditioners = '".addslashes($this->trans_details['river_expeditioners'])."' ";
    }
    if (array_key_exists('kindernature_1', $this->trans_details)) {
    $qstring .= ",
    kindernature_1 = '".addslashes($this->trans_details['kindernature_1'])."' ";
    }
    if (array_key_exists('kindernature_2', $this->trans_details)) {
	$qstring .= ",
	kindernature_2 = '".addslashes($this->trans_details['kindernature_2'])."' ";
    }

    $qstring .= ",
    payment_amount = '".addslashes($this->trans_details['payment_amount'])."',
    credit_card_type = '".addslashes($this->trans_details['credit_card_type'])."',
    credit_card_number = '".addslashes($this->trans_details['credit_card_number'])."',
    credit_card_expiration_month = '".addslashes($this->trans_details['credit_card_expiration_month'])."',
    credit_card_expiration_year = '".addslashes($this->trans_details['credit_card_expiration_year'])."',
    credit_card_name = '".addslashes($this->trans_details['credit_card_name'])."',

    billing_address = '".addslashes($this->trans_details['billing_address'])."' ";
    if (array_key_exists('billing_street_address', $this->trans_details)) {
	$qstring .= ",
	billing_street_address = '".addslashes($this->trans_details['billing_street_address'])."',
	billing_city = '".addslashes($this->trans_details['billing_city'])."',
	billing_state_province = '".addslashes($this->trans_details['billing_state_province'])."',
	billing_zip = '".addslashes($this->trans_details['billing_zip'])."',
	billing_country = '".addslashes($this->trans_details['billing_country'])."'";
    }

    if ($mode == 'test') {
	$qstring .= ", status = 'TEST' ";
    }

    $qresult = db_query($qstring);

    connectDB(REASON_DB);
    }

}
?>
