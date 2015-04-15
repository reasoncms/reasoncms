<?
require('pfproclass.php');
//require_once( CARL_UTIL_INC . 'db/db.php');
require_once( 'carl_util/db/db.php' );

class homecomingPF extends pfpc {

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

                connectDB('homecoming_connection');
                $qresult = db_query("UPDATE `registrants` SET
                confirmation_message = '".addslashes($text)."' WHERE REFNUM = '".$this->result['REFNUM']."'");

                connectDB(REASON_DB);

        }

        function get_confirmation_text($id, $hash=0) {
                connectDB('homecoming_connection');
                $qresult = db_query("SELECT confirmation_message FROM `registrants` WHERE REFNUM='$id'");
                connectDB(REASON_DB);

                if ($qresult) {
                        $gift = mysql_fetch_array($qresult);
                        return $gift['confirmation_message'];
                }
        }

        function record_transaction($mode = '') {
                connectDB('homecoming_connection');

                $qstring = "INSERT INTO `registrants` SET
        REFNUM='".addslashes($this->result['REFNUM'])."',
        first_name='".addslashes($this->trans_details['current_first_name'])."',
        last_name='".addslashes($this->trans_details['current_last_name'])."',
        class_year=".((!empty($this->trans_details['class_year'])) ? addslashes($this->trans_details['class_year']) : 'NULL').",
        graduation_name='".addslashes($this->trans_details['graduation_name'])."',
        preferred_first_name='".((!empty($this->trans_details['preferred_first_name'])) ? addslashes($this->trans_details['preferred_first_name']) : 'NULL')."',
        address='".((!empty($this->trans_details['address'])) ? addslashes($this->trans_details['address']) : 'NULL')."',
        city='".((!empty($this->trans_details['city'])) ? addslashes($this->trans_details['city']) : 'NULL')."',
        state_province = '".((!empty($this->trans_details['state_province'])) ? addslashes($this->trans_details['state_province']) : 'NULL')."',
        zip = '".((!empty($this->trans_details['zip'])) ? addslashes($this->trans_details['zip']) : 'NULL')."',
        home_phone = '".((!empty($this->trans_details['home_phone'])) ? addslashes($this->trans_details['home_phone']) : 'NULL')."',
        cell_phone = '".((!empty($this->trans_details['cell_phone'])) ? addslashes($this->trans_details['cell_phone']) : 'NULL')."',
        email = '".addslashes($this->trans_details['e-mail'])."',
        guest_name='".((!empty($this->trans_details['guest_name'])) ? addslashes($this->trans_details['guest_name']) : 'NULL')."',
        attended_luther='".((!empty($this->trans_details['attended_luther'])) ? addslashes($this->trans_details['attended_luther']) : 'NULL')."',
        guest_class=".((!empty($this->trans_details['guest_class'])) ? addslashes($this->trans_details['guest_class']) : 'NULL').",
        attend_program=".((!empty($this->trans_details['attend_program'])) ? addslashes($this->trans_details['attend_program']) : 'NULL').",
        dinner_dietary_restrictions = '".((!empty($this->trans_details['dinner_dietary_restrictions'])) ? addslashes($this->trans_details['dinner_dietary_restrictions']) : 'NULL')."',
        attend_luncheon=".((!empty($this->trans_details['attend_luncheon'])) ? addslashes($this->trans_details['attend_luncheon']) : 'NULL').",
        attend_70th_dinner=".((!empty($this->trans_details['attend_70th_dinner'])) ? addslashes($this->trans_details['attend_70th_dinner']) : 'NULL').",
        attend_dinner_50_to_25=".((!empty($this->trans_details['attend_dinner_50_to_25'])) ? addslashes($this->trans_details['attend_dinner_50_to_25']) : 'NULL').",
        attend_dinner_20_to_10=".((!empty($this->trans_details['attend_dinner_20_to_10'])) ? addslashes($this->trans_details['attend_dinner_20_to_10']) : 'NULL').",
        attend_dinner_5=".((!empty($this->trans_details['attend_dinner_5'])) ? addslashes($this->trans_details['attend_dinner_5']) : 'NULL').",
        ride_in_parade='".((!empty($this->trans_details['ride_in_parade'])) ? addslashes($this->trans_details['ride_in_parade']) : 'NULL')."',
        attend_50th_reception='".((!empty($this->trans_details['attend_50th_reception'])) ? addslashes($this->trans_details['attend_50th_reception']) : 'NULL')."',
        credit_card_type = '".addslashes($this->trans_details['credit_card_type'])."',
        credit_card_number = '".addslashes($this->trans_details['credit_card_number'])."',
        credit_card_expiration_month = '".addslashes($this->trans_details['credit_card_expiration_month'])."',
        credit_card_expiration_year = '".addslashes($this->trans_details['credit_card_expiration_year'])."',
        credit_card_name = '".addslashes($this->trans_details['credit_card_name'])."',
        amount_paid = '".((!empty($this->trans_details['amount'])) ? addslashes($this->trans_details['amount']) : 'NULL')."',
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
