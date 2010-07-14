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
class LFWRegistrationConfirmation extends FormStep
{
	var $date_format = 'F, j Y';

	function on_first_time()
	{
		$this->show_form = false;
		$blurb = $this->get_thank_you_blurb();
		echo '<div id="thankYouBlurb">' . $blurb . '</div>';
		
		$title = $this->controller->get('title');
		$first_name = $this->controller->get('first_name');
		$middle_initial = $this->controller->get('middle_initial');
		$last_name = $this->controller->get('last_name');
		$street_address = $this->controller->get('street_address');
		$city = $this->controller->get('city');
		$state_province = $this->controller->get('state_province');
		$zip = $this->controller->get('zip');
		$home_phone = $this->controller->get('home_phone');
		$office_phone = $this->controller->get('office_phone');
		$cell_phone = $this->controller->get('cell_phone');
		$email = $this->controller->get('e-mail');
		$institution = $this->controller->get('institution');
		$position_title = $this->controller->get('position_title');
		$profession = $this->controller->get('profession');
		$conference_fee = $this->controller->get('conference_fee');
		$attend_banquet = $this->controller->get('attend_banquet');
		$dietary_needs = $this->controller->get('dietary_needs');
		$student_housing = $this->controller->get('student_housing');
		$housing_gender = $this->controller->get('housing_gender');
		$housing_student_type = $this->controller->get('housing_student_type');
		
		$txt = '<div id="reviewLFWRegistration">'."\n";			
		$txt .= '<ul>'."\n";
		$txt .= '<li><strong>Date:</strong> '.date($this->date_format).'</li>'."\n";
		//Format the name
		$txt .= '<li>';
		$txt .= '<strong>Name:</strong> ';
		if ($title){
			$txt .= $title.' ';
		}
		$txt .= $first_name.' ';
		if ($middle_initial){ 
			$txt .= $middle_initial.' ';
		}
		$txt .= $last_name.'</li>'."\n";
		
		$txt .= '<li><strong>Address:</strong>'."\n".$street_address."\n".$city.' '.$state_province.' '.$zip./*$country.*/'</li>'."\n";
		if ($home_phone)
		{
			$txt .= '<li><strong>Home Phone:</strong> '.$home_phone.'</li>'."\n";
		}
		if ($office_phone)
		{
			$txt .= '<li><strong>Office Phone:</strong> '.$office_phone.'</li>'."\n";
		}
		if ($cell_phone)
		{
			$txt .= '<li><strong>Cell Phone:</strong> '.$cell_phone.'</li>'."\n";
		}
		$txt .= '<li><strong>Email:</strong> '.$email.'</li>'."\n";
		if ($institution)
		{
			$txt .= '<li><strong>Institution:</strong> '.$institution.'</li>'."\n";
		}
		if($position_title)
		{
			$txt .= '<li><strong>Position Title:</strong> '.$position_title.'</li>'."\n";
		}		
		if ($profession )
		{
			$txt .= '<li><strong>Profession:</strong> '.$profession.'</li>'."\n";
		}
		if($conference_fee)
		{
			$txt .= '<li><strong>Conference Fee:</strong> $'.number_format( $conference_fee, 2, '.', ',' ).'</li>'."\n";
		}
		if ($attend_banquet == 'Yes')
		{
			$txt .= '<li><strong>Banquet Fee:</strong> $25</li>'."\n";
		}
		if($dietary_needs)
		{
			$txt .= '<li><strong>Special dietary needs:</strong> '.$dietary_needs.'</li>'."\n";
		}
		if ($student_housing == 'Yes')
		{
			$txt .= '<li><strong>Student Housing Needs:</strong> '.$housing_gender.', '.$housing_student_type.' for '.
			$housing_nights.'</li>'."\n";
		}
		$txt .= '</ul>'."\n";
		$txt .= '</div>'."\n";
		
		echo $txt;
		$this->email_alumni($txt);
		$this->email_registrant($blurb . $txt);
		echo 'A copy of this confirmation has been sent to your email address.'."\n";
				
		connectDB('lfw_connection');
		
		$qstring = "INSERT INTO `registrants` SET  
		title='".((!empty($title)) ? addslashes($title) : 'NULL')."', 
		first_name='".addslashes($first_name)."',
		middle_initial='".((!empty($middle_initial)) ? addslashes($middle_initial) : 'NULL')."',
		last_name='".addslashes($last_name)."', 
		street_address='".addslashes($street_address)."',
		city='".addslashes($city)."',
		state_province = '".addslashes($state_province)."',
		zip = '".addslashes($zip)."',
		home_phone = '".((!empty($home_phone)) ? addslashes($home_phone) : 'NULL')."',
		office_phone = '".((!empty($office_phone)) ? addslashes($office_phone) : 'NULL')."',
		cell_phone = '".((!empty($cell_phone)) ? addslashes($cell_phone) : 'NULL')."',
		email = '".addslashes($email)."', 
		institution='".((!empty($institution)) ? addslashes($institution) : 'NULL')."',
		position_title='".((!empty($position_title)) ? addslashes($position_title) : 'NULL')."',
		profession='".((!empty($profession)) ? addslashes($profession) : 'NULL')."', 
		conference_fee='".((!empty($conference_fee)) ? addslashes($conference_fee) : 'NULL')."', 
		attend_banquet='".((!empty($attend_banquet)) ? addslashes($attend_banquet) : 'NULL')."',
		dietary_needs='".((!empty($dietary_needs)) ? addslashes($dietary_needs) : 'NULL')."',
		student_housing='".((!empty($student_housing)) ? addslashes($student_housing) : 'NULL')."',
		housing_gender='".((!empty($housing_gender)) ? addslashes($housing_gender) : 'NULL')."',
		housing_student_type='".((!empty($housing_student_type)) ? addslashes($housing_student_type) : 'NULL')."',
		housing_nights='".((!empty($housing_nights)) ? addslashes($housing_nights) : 'NULL')."'";
		
		
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
		$mail = new Email('luthfestreg@gmail.com,einckmic@luther.edu', 'noreply@luther.edu','noreply@luther.edu', 'New LFW Registration '.date('mdY H:i:s'),strip_tags($text), $text);
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
		$mail = new Email($this->controller->get('e-mail'),'luthfestreg@gmail.edu','lutherfestreg@gmail.edu,eicnkmic@luther.edu','Lutheran Festival of Writing Registration Confirmation',strip_tags($mail_text),$mail_text);
		$mail->send();
	}
}
?>