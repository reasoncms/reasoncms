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
require_once( CARL_UTIL_INC . 'db/db.php');
//require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );

class transcriptPF extends pfpc {

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
    connectDB('transcript_connection');

    $qresult = db_query("UPDATE `requestor` SET
    confirmation_message = '".addslashes($text)."' WHERE REFNUM = '".$this->result['REFNUM']."'");

    connectDB(REASON_DB);
}

function get_confirmation_text($id, $hash=0) {
    connectDB('transcript_connection');
    $qresult = db_query("SELECT confirmation_message FROM `requestor` WHERE REFNUM='$id'");
    connectDB(REASON_DB);

    if ($qresult) {
            $gift = mysql_fetch_array($qresult);
            return $gift['confirmation_message'];
    }
}

function record_transaction($mode = '') {
    connectDB('transcript_connection');

    $qstring = "INSERT INTO `requestor` SET
    REFNUM='".addslashes($this->result['REFNUM'])."',
    submitter_ip='".addslashes($this->trans_details['submitter_ip'])."',
    name='".addslashes($this->trans_details['name'])."',
    date_of_birth='".addslashes($this->trans_details['date_of_birth'])."',
    LATF='".addslashes($this->trans_details['latf'])."',
    address='".addslashes($this->trans_details['address'])."',
    city='".addslashes($this->trans_details['city'])."',
    state_province = '".addslashes($this->trans_details['state_province'])."',
    zip = '".addslashes($this->trans_details['zip'])."',
    country ='".((!empty($this->trans_details['country'])) ? addslashes($this->trans_details['country']) : 'NULL')."',
    daytime_phone = '".((!empty($this->trans_details['daytime_phone'])) ? addslashes($this->trans_details['daytime_phone']) : 'NULL')."',
    email = '".addslashes($this->trans_details['e-mail'])."',
    official_type='".((!empty($this->trans_details['official_type'])) ? addslashes($this->trans_details['official_type']) : 'NULL')."',
    number_of_official='".((!empty($this->trans_details['number_of_official'])) ? addslashes($this->trans_details['number_of_official']) : 'NULL')."',
    official_email='".((!empty($this->trans_details['official_email'])) ? addslashes($this->trans_details['official_email']) : 'NULL')."',
    unofficial='".((!empty($this->trans_details['unofficial'])) ? addslashes($this->trans_details['unofficial']) : 'NULL')."',
    unofficial_address ='".((!empty($this->trans_details['unofficial_address'])) ? addslashes($this->trans_details['unofficial_address']) : 'NULL')."',
    deliver_to='".addslashes($this->trans_details['deliver_to'])."',
    delivery_type='".addslashes($this->trans_details['deliver_type'])."',
    delivery_time='".((!empty($this->trans_details['delivery_time'])) ? addslashes($this->trans_details['delivery_time']) : 'NULL')."',
    amount_paid='".((!empty($this->trans_details['amount'])) ? addslashes($this->trans_details['amount']) : 'NULL')."',
    credit_card_type = '".addslashes($this->trans_details['credit_card_type'])."',
    credit_card_number = '".addslashes($this->trans_details['credit_card_number'])."',
    credit_card_expiration_month = '".addslashes($this->trans_details['credit_card_expiration_month'])."',
    credit_card_expiration_year = '".addslashes($this->trans_details['credit_card_expiration_year'])."',
    credit_card_name = '".addslashes($this->trans_details['credit_card_name'])."',
    billing_address = '".addslashes($this->trans_details['billing_address'])."' ";

    if (array_key_exists('institution_name', $this->trans_details)) {
        $qstring .= ",
        institution_name='".addslashes($this->trans_details['institution_name']). "',
        institution_attn='".addslashes($this->trans_details['institution_attn']). "' ";
    }
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

