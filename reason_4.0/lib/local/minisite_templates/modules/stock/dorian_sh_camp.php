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

class dorian_sh extends pfpc {

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
	connectDB('dorian_sh_camp_connection');

	$qresult = db_query("UPDATE `camper` SET
	confirmation_message = '".addslashes($text)."' WHERE REFNUM = '".$this->result['REFNUM']."'");

	connectDB(REASON_DB);

}

function get_confirmation_text($id, $hash=0) {
	connectDB('dorian_sh_camp_connection');

	$qresult = db_query("SELECT confirmation_message FROM `camper` WHERE REFNUM='$id'");

	if ($qresult) {
		$camper = mysql_fetch_array($qresult);
		return $camper['confirmation_message'];
	}

	connectDB(REASON_DB);
}

function record_transaction($mode = '') {
    connectDB('dorian_sh_camp_connection');

    $qstring = "INSERT INTO `camper` SET
    REFNUM='".addslashes($this->result['REFNUM'])."',
    submitter_ip='".addslashes($this->trans_details['submitter_ip'])."',
    first_name='".addslashes($this->trans_details['first_name'])."',
    last_name='".addslashes($this->trans_details['last_name'])."',
    gender='".addslashes($this->trans_details['gender'])."',
    address='".addslashes($this->trans_details['address'])."',
    city='".addslashes($this->trans_details['city'])."',
    state_province = '".addslashes($this->trans_details['state_province'])."',
    zip = '".addslashes($this->trans_details['zip'])."',
    home_phone = '".addslashes($this->trans_details['home_phone'])."',
    email = '".addslashes($this->trans_details['e-mail'])."',
    school = '".addslashes($this->trans_details['school'])."',
    grade = '".addslashes($this->trans_details['grade'])."',
    roomate_requested= '".addslashes($this->trans_details['roomate_requested'])."' ";
    if (array_key_exists('band_participant', $this->trans_details)) {
	$qstring .= ",
	band_instrument = '".addslashes($this->trans_details['band_instrument'])."' ";
    }
    if (array_key_exists('orchestra_participant', $this->trans_details)) {
	$qstring .= ",
	orchestra_instrument = '".addslashes($this->trans_details['orchestra_instrument'])."' ";
    }
    if (array_key_exists('jazz_participant', $this->trans_details)) {
	$qstring .= ",
	jazz_instrument = '".addslashes($this->trans_details['jazz_instrument'])."' ";
    }
    if (array_key_exists('wind_choir_participant', $this->trans_details)) {
	$qstring .= ",
	wind_choir_instrument = '".addslashes($this->trans_details['wind_choir_instrument'])."' ";
    }
    if (array_key_exists('private_lessons', $this->trans_details)){
        if ($this->trans_details['private_lessons'] >= 1){
            $qstring .= ",
            lesson_instrument_1 = '".addslashes($this->trans_details['lesson_instrument_1'])."' ";
        }
        if ($this->trans_details['private_lessons'] == 2){
            $qstring .= ",
            lesson_instrument_2 = '".addslashes($this->trans_details['lesson_instrument_2'])."' ";
        }
    }
    if (array_key_exists('workshops', $this->trans_details)) {
	$qstring .= ",
	workshop = '".addslashes($this->trans_details['workshops'])."' ";
    }
    if (array_key_exists('period_one', $this->trans_details)) {
	$qstring .= ",
	period_one_first = '".addslashes($this->trans_details['period_one'])."' ";
    }
    if (array_key_exists('period_two', $this->trans_details)) {
	$qstring .= ",
	period_two_first = '".addslashes($this->trans_details['period_two'])."' ";
    }
    if (array_key_exists('period_three_first', $this->trans_details)) {
	$qstring .= ",
	period_three_first = '".addslashes($this->trans_details['period_three_first'])."' ";
    }
    if (array_key_exists('period_three_second', $this->trans_details)) {
	$qstring .= ",
	period_three_second = '".addslashes($this->trans_details['period_three_second'])."' ";
    }
    if (array_key_exists('period_four_first', $this->trans_details)) {
	$qstring .= ",
	period_four_first = '".addslashes($this->trans_details['period_four_first'])."' ";
    }
    if (array_key_exists('period_four_second', $this->trans_details)) {
	$qstring .= ",
	period_four_second = '".addslashes($this->trans_details['period_four_second'])."' ";
    }
    if (array_key_exists('period_five', $this->trans_details)) {
	$qstring .= ",
	period_five_first = '".addslashes($this->trans_details['period_five'])."' ";
    }
    if (array_key_exists('period_six', $this->trans_details)) {
	$qstring .= ",
	period_six_first = '".addslashes($this->trans_details['period_six'])."' ";
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
