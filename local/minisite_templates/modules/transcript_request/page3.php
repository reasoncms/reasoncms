<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the transcript request form
//
////////////////////////////////////////////////////////////////////////////////
//require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
include_once(TYR_INC . 'tyr.php');
include_once('paths.php');
class TranscriptConfirmation extends FormStep
{
	var $date_format = 'F, j Y';

	function on_first_time()
	{
		$this->show_form = false;
		$blurb = $this->get_thank_you_blurb();
		echo '<div id="thankYouBlurb">' . $blurb . '</div>';
		
		$first_name = $this->controller->get('first_name');
		$last_name = $this->controller->get('last_name');
		$previous_name = $this->controller->get('previous_name');
		$daytime_phone = $this->controller->get('daytime_phone');
		$email = $this->controller->get('e-mail');
		$address = $this->controller->get('address');
		$city = $this->controller->get('city');
		$state_province = $this->controller->get('state_province');
		$zip = $this->controller->get('zip');
		$country = $this->controller->get('country');
		$official = $this->controller->get('official');
		$unofficial = $this->controller->get('unofficial');
		$delivery = $this->controller->get('delivery');
		
		$txt = '<div id="reviewTranscriptRequest">'."\n";			
		$txt .= '<ul>'."\n";
		$txt .= '<li><strong>Date:</strong> '.date($this->date_format).'</li>'."\n";
		$txt .= '<li><strong>Name:</strong> '.$first_name.' '.$last_name.'</li>'."\n";
		if ($previous_name)
		{
			$txt .= '<li><strong>Previous Name:</strong> '.$previous_name.'</li>'."\n";
		}
		$txt .= '<li><strong>Daytime Phone:</strong> '.$daytime_phone.'</li>'."\n";
		$txt .= '<li><strong>Email:</strong> '.$email.'</li>'."\n";
		$txt .= '<li><strong>Address:</strong><br />'.$address.'<br />'.$city.' '.$state_province.' '.$zip. ' '.$country.'</li>'."\n";
		if($official)
		{
			$txt .= '<li><strong>Official transcripts requested:</strong> '.$official.'</li>'."\n";
		}		
		if($unofficial)
		{
			$txt .= '<li><strong>Unofficial transcripts requested:</strong> '.$unofficial.'</li>'."\n";
		}		
		$txt .= '<li><strong>Deliver:</strong> '.$delivery.'</li>'."\n";
		$txt .= '</ul>'."\n";
		$txt .= '</div>'."\n";

		echo $txt;
		$this->email_alumni($txt);
		$this->email_requestor($blurb . $txt);
		echo 'A copy of this confirmation has been sent to your email address.'."\n";
				
		connectDB('transcript_connection');
		
		$qstring = "INSERT INTO `requestors` SET
		first_name='".addslashes($first_name)."', 
		last_name='".addslashes($last_name)."', 
		previous_name='".((!empty($previous_name)) ? addslashes($previous_name) : 'NULL')."', 
		address='".((!empty($address)) ? addslashes($address) : 'NULL')."', 
		city='".((!empty($city)) ? addslashes($city) : 'NULL')."', 
		state_province = '".((!empty($state_province)) ? addslashes($state_province) : 'NULL')."', 
		zip = '".((!empty($zip)) ? addslashes($zip) : 'NULL')."',
		country = '".((!empty($coumtry)) ? addslashes($country) : 'NULL')."', 		 
		daytime_phone = '".((!empty($home_phone)) ? addslashes($home_phone) : 'NULL')."',
		email = '".((!empty($email)) ? addslashes($email) : 'NULL')."',
		official=".((!empty($official)) ? addslashes($official) : 'NULL').",
		unofficial=".((!empty($unofficial)) ? addslashes($unofficial) : 'NULL').",
		delivery='".((!empty($delivery)) ? addslashes($delivery) : 'NULL')."' ";
		
		
		if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) ){
			$qstring .= ", status = 'TEST' ";
		}
	
		$qresult = db_query($qstring);
		connectDB(REASON_DB);
	
	}
	
	function get_thank_you_blurb()
	{
		if (reason_unique_name_exists('transcript_thank_you_blurb'))
			$blurb = get_text_blurb_content('transcript_thank_you_blurb');
		else $blurb = '<p><strong>Your transcript request has been processed.</strong></p>'."\n";
		return $blurb;
	}
	
	function email_alumni($text)
	{
		$mail = new Email('slylth@gmail.edu', 'noreply@luther.edu','noreply@luther.edu', 'New Transcript Request '.date('mdY H:i:s'),strip_tags($text), $text);
		$mail->send();
	}
	function email_requestor($text)
	{
		$replacements = array(
			'<th class="col1">Date</th>'=>'',
			'<th class="col1">Year</th>'=>'',
			'<th>Amount</th>'=>'',
			'</td><td>'=>': ',
			'�'=>'-',
			'<h3>'=>'--------------------'."\n\n",
			'</h3>'=>'',
			'<br />'=>"\n",
		);
		$mail_text = str_replace(array_keys($replacements),$replacements,$text);
		$mail = new Email($this->controller->get('e-mail'), 'registrar@luther.edu','registrar@luther.edu','Luther College Transcript Request',strip_tags($mail_text),$mail_text);
		$mail->send();
	}
}
?>