<?php

////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the confirmation page of the transcript request form
//
////////////////////////////////////////////////////////////////////////////////
//require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
include_once(TYR_INC . 'tyr.php');
include_once('paths.php');

class TranscriptRequestConfirmation extends FormStep {

    var $date_format = 'F, j Y';

    function on_first_time() {
        $this->show_form = false;
        $blurb = $this->get_thank_you_blurb();
        echo '<div id="thankYouBlurb">' . $blurb . '</div>';
        $amount = $this->controller->get('amount');

        $name = $this->controller->get('name');
        $date_of_birth = $this->controller->get('date_of_birth');
        $latf = $this->controller->get('LATF');
        $daytime_phone = $this->controller->get('daytime_phone');
        $email = $this->controller->get('e-mail');
        $address = $this->controller->get('address');
        $city = $this->controller->get('city');
        $state_province = $this->controller->get('state_province');
        $zip = $this->controller->get('zip');
        $country = $this->controller->get('country');
        $unofficial = $this->controller->get('unofficial');
        $unofficial_address = $this->controller->get('unofficial_address');
        //$official_type = $this->controller->get('official_type');
        $number_of_official = $this->controller->get('number_of_official');
        $deliver_to = $this->controller->get('deliver_to');
        $institution_name = $this->controller->get('institution_name');
        $institution_attn = $this->controller->get('institution_attn');
        $institution_email = $this->controller->get('institution_email');
        $delivery_time = $this->controller->get('delivery_time');
        $submitter_ip = $this->controller->get('submitter_ip');

        $txt = '<div id="reviewTranscriptRequest">' . "\n";
        $txt .= '<ul>' . "\n";
        $txt .= '<li><strong>Date:</strong> ' . date($this->date_format) . '</li>' . "\n";
        $txt .= '<li><strong>Name:</strong> ' . $name . '</li>' . "\n";
        $txt .= '<li><strong>Date of Birth:</strong> ' . $date_of_birth . '</li>' . "\n";
        $txt .= '<li><strong>Lifetime Academic Transcript Fee:</strong> ' . $latf . '</li>'."\n";
        $txt .= '<li><strong>Daytime Phone:</strong> ' . $daytime_phone . '</li>' . "\n";
        $txt .= '<li><strong>Email:</strong> ' . $email . '</li>' . "\n";
        if ($unofficial) {
            $txt .= '<li><strong>Unofficial transcripts requested:</strong> Yes </li>' . "\n";
            $txt .= '<li><strong>Mail to:</strong> ' . $unofficial_address . '</li>' . "\n";
        }
        //if ($official_type == 'paper') {
        if ($number_of_official) {
            $txt .= '<li><strong>Official paper transcripts requested:</strong> ' . $number_of_official . '</li>' . "\n";
            $txt .= '<li><strong>Delivery Information:</strong> ' . $deliver_to . '</li>' . "\n";
            $txt .= '<ul>' . "\n";
            if ($deliver_to == 'institution') {
                $txt .= '<li><strong>Institution/Company:</strong><br>' .
                        $institution_name . '<br>' .
                        'Attn: ' . $institution_attn . '<br>';
            }
            $txt .= '<li><strong>Address:</strong><br />' . $address . '<br />' . $city . ' ' . $state_province . ' ' . $zip . ' ' . $country . '</li>' . "\n";
            $txt .= '<li><strong>Delivery Timeline: </strong>' . $delivery_time . '</li>' . "\n";
            $txt .= '</ul>' . "\n";
        }
//        if ($official_type == 'eScrip') {
//            $txt .= '<li><strong>Official eScrip-Safe transcripts requested:</strong> ' . $number_of_official . '</li>' . "\n";
//            $txt .= '<li><strong>Delivery Information</strong></li>' . "\n";
//            $txt .= '<ul>' . "\n";
//            if ($deliver_to == 'institution') {
//                $txt .= '<li><strong>Institution/Company:</strong><br>' .
//                        $institution_name . '<br>' .
//                        'Attn: ' . $institution_attn . '<br>' .
//                        $institution_email . '</li>' . "\n";
//            } else { //deliver to requestor
//                $txt .= '<li><strong>Your E-mail Address:</strong>' . $email . '</li>' . "\n";
//            }
//            $txt .= '<li><strong>Delivery Timeline: </strong>' . $delivery_time . '</li>' . "\n";
//            $txt .= '</ul>' . "\n";
//        }
        $txt .= '</ul>' . "\n";
        $txt .= '</div>' . "\n";
        echo $txt;

        $this->email_registrar($txt);
        $this->email_requestor($blurb . $txt);

        connectDB('transcript_connection');

        $qstring = "INSERT INTO `requestor` SET
        submitter_ip='" . addslashes($submitter_ip) . "',
		name='" . addslashes($name) . "',
		date_of_birth ='" . addslashes($date_of_birth) . "',
        LATF='". (($latf == 'Paid') ? 'LATF' : '') ."',
		address='" . ((!empty($address)) ? addslashes($address) : '') . "',
        unofficial_address='" . ((!empty($unofficial_address)) ? addslashes($address) : '') . "',
		city='" . ((!empty($city)) ? addslashes($city) : '') . "',
		state_province = '" . ((!empty($state_province)) ? addslashes($state_province) : '') . "',
		zip = '" . ((!empty($zip)) ? addslashes($zip) : '') . "',
		country = '" . ((!empty($coumtry)) ? addslashes($country) : '') . "',
		daytime_phone = '" . ((!empty($daytime_phone)) ? addslashes($daytime_phone) : '') . "',
		email = '" . ((!empty($email)) ? addslashes($email) : '') . /*"',
		official_type='" . ((!empty($official_type)) ? addslashes($official_type) : '') . */"',
        number_of_official='" . ((!empty($number_of_official)) ? addslashes($number_of_official) : '') . "',
        amount_paid='" . ((!empty($amount)) ? addslashes($amount) : '') . "',
		unofficial='" . ((!empty($unofficial)) ? addslashes($unofficial) : '') . "',
        deliver_to='" . addslashes($deliver_to) . "',
		delivery_time='" . ((!empty($delivery_time)) ? addslashes($delivery_time) : '') . "',
		institution_name='" . ((!empty($institution_name)) ? addslashes($institution_name) : '') . "',
		institution_attn='" . ((!empty($institution_attn)) ? addslashes($institution_attn) : '') . "',
		institution_email='" . ((!empty($institution_email)) ? addslashes($institution_email) : '') . "' ";


        if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty($this->_request['tm'])) {
            $qstring .= ", status = 'TEST' ";
        }

        $qresult = db_query($qstring);
        connectDB(REASON_DB);


        echo 'A copy of this confirmation has been sent to your email address.' . "\n";
        echo '<br><a href ="/registrar/transcript/">Request more transcripts</a>' . "\n";
        $this->controller->destroy_form_data();
    }

    function get_thank_you_blurb() {
        if (reason_unique_name_exists('transcript_thank_you_blurb'))
            $blurb = get_text_blurb_content('transcript_thank_you_blurb');
        else
            $blurb = '<p><strong>Your transcript request has been processed.</strong></p>' . "\n";
        return $blurb;
    }

    function email_registrar($text) {
        $mail = new Email('registrar@luther.edu', 'noreply@luther.edu', 'noreply@luther.edu', 'New Transcript Request ' . date('mdY H:i:s'), strip_tags($text), $text);
        $mail->send();
    }

    function email_requestor($text) {
        $replacements = array(
            '<th class="col1">Date</th>' => '',
            '<th class="col1">Year</th>' => '',
            '<th>Amount</th>' => '',
            '</td><td>' => ': ',
            '�' => '-',
            '<h3>' => '--------------------' . "\n\n",
            '</h3>' => '',
            '<br />' => "\n",
        );
        $mail_text = str_replace(array_keys($replacements), $replacements, $text);
        $mail = new Email($this->controller->get('e-mail'), 'registrar@luther.edu', 'registrar@luther.edu', 'Luther College Transcript Request', strip_tags($mail_text), $mail_text);
        $mail->send();
    }

}

?>