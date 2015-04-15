<?
// PayFlowPro processing class
// This class encapsulates some of the work of handling a real-time transaction.
// The process is already fairly abstract -- what this class mostly does is provide
// some intelligent input and error checking and reporting so you don't have to code it 
// into your app.  Note: expiration needs to be 4 digits, i.e. moyr.
//
// Usage:
//
// require("lfwPFclass.php");
// $pf = new lfwPF;
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
require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );

class lfwPF extends pfpc {

var $trans_details = array();
var $result = array();
var $registrant_id;

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
	connectDB('lfw_connection');
	echo 'This is the registrant_id:::' . $this->registrant_id . '<br />';
	echo 'This is the REFNUM:::' . $this->result['REFNUM'] . '<br />';	
	
	$qresult = db_query("UPDATE `registrants` SET 
	confirmation_message = '".addslashes($text)."' WHERE REFNUM = '".$this->result['REFNUM']."'");
	
	connectDB(REASON_DB);
}

function get_confirmation_text($id, $hash=0) {
	connectDB('lfw_connection');
	$qresult = db_query("SELECT confirmation_message FROM `registrants` WHERE REFNUM='$id'");
	connectDB(REASON_DB);
	
	if ($qresult) {
		$gift = mysql_fetch_array($qresult);
		return $gift['confirmation_message'];
	}
}

function record_transaction($mode = '') {	
	connectDB('lfw_connection');

	$qstring = "INSERT INTO `registrants` SET  
REFNUM='".addslashes($this->result['REFNUM'])."', 
title='".((!empty($this->trans_details['title'])) ? addslashes($this->trans_details['title']) : 'NULL')."', 
first_name='".addslashes($this->trans_details['first_name'])."',
middle_initial='".((!empty($this->trans_details['middle_initial'])) ? addslashes($this->trans_details['middle_initial']) : 'NULL')."',
last_name='".addslashes($this->trans_details['last_name'])."', 
street_address='".addslashes($this->trans_details['street_address'])."',
city='".addslashes($this->trans_details['city'])."',
state_province = '".addslashes($this->trans_details['state_province'])."',
zip = '".addslashes($this->trans_details['zip'])."',
home_phone = '".((!empty($this->trans_details['home_phone'])) ? addslashes($this->trans_details['home_phone']) : 'NULL')."',
office_phone = '".((!empty($this->trans_details['office_phone'])) ? addslashes($this->trans_details['office_phone']) : 'NULL')."',
cell_phone = '".((!empty($this->trans_details['cell_phone'])) ? addslashes($this->trans_details['cell_phone']) : 'NULL')."',
email = '".addslashes($this->trans_details['e-mail'])."', 
institution='".((!empty($this->trans_details['institution'])) ? addslashes($this->trans_details['institution']) : 'NULL')."',
position_title='".((!empty($this->trans_details['position_title'])) ? addslashes($this->trans_details['position_title']) : 'NULL')."',	
profession='".((!empty($this->trans_details['profession'])) ? addslashes($this->trans_details['profession']) : 'NULL')."', 
conference_fee='".((!empty($this->trans_details['conference_fee'])) ? addslashes($this->trans_details['conference_fee']) : 'NULL')."', 
attend_banquet='".((!empty($this->trans_details['attend_banquet'])) ? addslashes($this->trans_details['attend_banquet']) : 'NULL')."',
dietary_needs='".((!empty($this->trans_details['dietary_needs'])) ? addslashes($this->trans_details['dietary_needs']) : 'NULL')."',
student_housing='".((!empty($this->trans_details['student_housing'])) ? addslashes($this->trans_details['student_housing']) : 'NULL')."',
housing_gender='".((!empty($this->trans_details['housing_gender'])) ? addslashes($this->trans_details['housing_gender']) : 'NULL')."',
housing_student_type='".((!empty($this->trans_details['housing_student_type'])) ? addslashes($this->trans_details['housing_student_type']) : 'NULL')."',
housing_nights='".((!empty($this->trans_details['housing_nights'])) ? addslashes($this->trans_details['housing_nights']) : 'NULL')."',
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