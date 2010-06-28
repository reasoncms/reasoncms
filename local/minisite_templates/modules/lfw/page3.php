<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the Lutheran Festival of Writing form
//		Registrants land here if they have to pay nothing
////////////////////////////////////////////////////////////////////////////////
//require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
include_once(TYR_INC . 'tyr.php');
include_once('paths.php');
class LFWConfirmation extends FormStep
{
	var $date_format = 'F, j Y';

	function on_first_time()
	{
		$this->show_form = false;
		$blurb = $this->get_thank_you_blurb();
		echo '<div id="thankYouBlurb">' . $blurb . '</div>';
		
		$text = $this->controller->get_confirmation_text();
		echo $text;
		
		$this->email_alumni($text);
		$this->email_registrant($blurb . $text);
		echo 'A copy of this confirmation has been sent to your email address.'."\n";
				
		connectDB('lfw_connection');
		
		$qstring = "INSERT INTO `registrants` SET  
		title=".((!empty($this->trans_details['title'])) ? addslashes($this->trans_details['title']) : 'NULL').", 
		first_name='".addslashes($this->trans_details['first_name'])."',
		middle_initial='".((!empty($this->trans_details['middle_initial'])) ? addslashes($this->trans_details['middle_initial']) : 'NULL')."',
		last_name='".addslashes($this->trans_details['last_name'])."', 
		address='".addslashes($this->trans_details['address'])."',
		city='".addslashes($this->trans_details['city'])."',
		state_province = '".addslashes($this->trans_details['state_province'])."',
		zip = '".addslashes($this->trans_details['zip'])."',
		home_phone = '".((!empty($this->trans_details['home_phone'])) ? addslashes($this->trans_details['home_phone']) : 'NULL')."',
		office_phone = '".((!empty($this->trans_details['office_phone'])) ? addslashes($this->trans_details['office_phone']) : 'NULL')."',
		cell_phone = '".((!empty($this->trans_details['cell_phone'])) ? addslashes($this->trans_details['cell_phone']) : 'NULL')."',
		email = '".addslashes($this->trans_details['e-mail'])."', 
		institution='".((!empty($this->trans_details['institution'])) ? addslashes($this->trans_details['institution']) : 'NULL')."',
		position_title='".((!empty($this->trans_details['position_title'])) ? addslashes($this->trans_details['position_title']) : 'NULL')."',
		profession=".((!empty($this->trans_details['profession'])) ? addslashes($this->trans_details['profession']) : 'NULL').", 
		conference_fee=".((!empty($this->trans_details['conference_fee'])) ? addslashes($this->trans_details['conference_fee']) : 'NULL').", 
		attend_banquet=".((!empty($this->trans_details['attend_banquet'])) ? addslashes($this->trans_details['attend_banquet']) : 'NULL').",
		dietary_needs='".((!empty($this->trans_details['dietary_needs'])) ? addslashes($this->trans_details['dietary_needs']) : 'NULL')."',
		student_housing=".((!empty($this->trans_details['student_housing'])) ? addslashes($this->trans_details['student_housing']) : 'NULL').",
		housing_gender=".((!empty($this->trans_details['housing_gender'])) ? addslashes($this->trans_details['housing_gender']) : 'NULL').",
		housing_student_type=".((!empty($this->trans_details['housing_student_type'])) ? addslashes($this->trans_details['housing_student_type']) : 'NULL').",
		housing_nights=".((!empty($this->trans_details['housing_nights'])) ? addslashes($this->trans_details['housing_nights']) : 'NULL')." ",
		
		
		if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) ){
			$qstring .= ", status = 'TEST' ";
		}
	
		$qresult = db_query($qstring);
		connectDB(REASON_DB);
	
	}
	
	function get_thank_you_blurb()
	{
		if (reason_unique_name_exists('lfw_thank_you_blurb'))
			$blurb = get_text_blurb_content('lfw_thank_you_blurb');
		else $blurb = '<p><strong>Thank you for registering for the Lutheran Festival of Writing!</strong></p>'."\n";
		return $blurb;
	}
	
	function email_alumni($text)
	{
		$mail = new Email('gilbertc@luther.edu', 'noreply@luther.edu','noreply@luther.edu', 'New Homecoming Registration '.date('mdY H:i:s'),strip_tags($text), $text);
		$mail->send();
	}
	function email_registrant($text)
	{
		$replacements = array(
			'<th class="col1">Date</th>'=>'',
			'<th class="col1">Year</th>'=>'',
			'<th>Amount</th>'=>'',
			'</td><td>'=>': ',
			'Ð'=>'-',
			'<h3>'=>'--------------------'."\n\n",
			'</h3>'=>'',
			'<br />'=>"\n",
		);
		
		$mail_text = str_replace(array_keys($replacements),$replacements,$text);
		$mail = new Email($this->controller->get('e-mail'),'alumni@luther.edu','alumni@luther.edu','Luther College Homecoming Registration Confirmation',strip_tags($mail_text),$mail_text);
		$mail->send();
	}
}
?>