<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the homecoming form
//
////////////////////////////////////////////////////////////////////////////////
//require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
include_once(TYR_INC . 'tyr.php');
include_once('paths.php');
class HomecomingRegistrationConfirmation extends FormStep
{
	var $date_format = 'F, j Y';

	function on_first_time()
	{
		$this->show_form = false;
		$blurb = $this->get_thank_you_blurb();
		echo '<div id="thankYouBlurb">' . $blurb . '</div>';
		
		$first_name = $this->controller->get('current_first_name');
		$last_name = $this->controller->get('current_last_name');
		$class_year = $this->controller->get('class_year');
		$graduation_name = $this->controller->get('graduation_name');
		$preferred_first_name = $this->controller->get('preferred_first_name');
		$address = $this->controller->get('address');
		$city = $this->controller->get('city');
		$state_province = $this->controller->get('state_province');
		$zip = $this->controller->get('zip');
		$home_phone = $this->controller->get('home_phone');
		$cell_phone = $this->controller->get('cell_phone');
		$email = $this->controller->get('e-mail');
		$guest_name = $this->controller->get('guest_name');
		$attended_luther = $this->controller->get('attended_luther');
		$guest_class = $this->controller->get('guest_class');
		$attend_program = $this->controller->get('attend_program');
		$dinner_dietary_restrictions = $this->controller->get('dinner_dietary_restrictions');
		$attend_luncheon = $this->controller->get('attend_luncheon');
		$attend_50th_reception = $this->controller->get('attend_50th_reception');
		$attend_dinner_50_to_25 = $this->controller->get('attend_dinner_50_to_25');
		$attend_dinner_20_to_10 = $this->controller->get('attend_dinner_20_to_10');
		$attend_dinner_5 = $this->controller->get('attend_dinner_5');
		$ride_in_parade = $this->controller->get('ride_in_parade'); 
		$booklet = $this->controller->get('booklet'); 
                
                
                
                $amount_paid = $this->controller->get('amount'); 
		
		$txt = '<div id="reviewHomecomingRegistration">'."\n";			
		$txt .= '<ul>'."\n";
		$txt .= '<li><strong>Date:</strong> '.date($this->date_format).'</li>'."\n";
		$txt .= '<li><strong>Name:</strong> '.$first_name.' '.$last_name.'</li>'."\n";
		$txt .= '<li><strong>Class Year:</strong> '.$class_year.'</li>'."\n";
		$txt .= '<li><strong>Graduation Name:</strong> '.$graduation_name.'</li>'."\n";
		if ($preferred_first_name)
		{
			$txt .= '<li><strong>Preferred First Name:</strong> '.$preferred_first_name.'</li>'."\n";
		}
		$txt .= '<li><strong>Address:</strong><br />'.$address.'<br />'.$city.' '.$state_province.' '.$zip./*$country'.*/'</li>'."\n";
		if ($home_phone)
		{
			$txt .= '<li><strong>Home Phone:</strong> '.$home_phone.'</li>'."\n";
		}
		if ($cell_phone)
		{
			$txt .= '<li><strong>Cell Phone:</strong> '.$cell_phone.'</li>'."\n";
		}
		$txt .= '<li><strong>Email:</strong> '.$email.'</li>'."\n";
		if ($guest_name)
		{
			$txt .= '<li><strong>Spouse/Guest Name:</strong> '.$guest_name.'</li>'."\n";
		}
		if($attended_luther)
		{
			$txt .= '<li><strong>Guest Class Year:</strong> '.$guest_class.'</li>'."\n";
		}		
		if($attend_program)
		{
			$txt .= '<li><strong>Tickets for Alumni Program:</strong> '.$attend_program.'</li>'."\n";
		}
		if($dinner_dietary_restrictions)
		{
			$txt .= '<li><strong>Dietary Restrictions:</strong> '.$dinner_dietary_restrictions.'</li>'."\n";
		}
		if ($attend_50th_reception){
			$txt .= '<li><strong>Attend 50th Year Reunion Reception:</strong> '.$attend_luncheon.'</li>'."\n";	
		}
		if($attend_luncheon)
		{
			$txt .= '<li><strong>Attend ' . $class_year . ' Year Reunion Luncheon:</strong> '.$attend_luncheon.'</li>'."\n";
		}
		if($attend_dinner_50_to_25)
		{
			$txt .= '<li><strong>Attend ' . $class_year . ' Year Reunion Dinner:</strong> '.$attend_dinner_50_to_25.'</li>'."\n";
		}
		if($attend_dinner_20_to_10)
		{
			$txt .= '<li><strong>Attend ' . $class_year . ' Year Reunion Reception:</strong> '.$attend_dinner_20_to_10.'</li>'."\n";
		}
		if($attend_dinner_5)
		{
			$txt .= '<li><strong>Attend ' . $class_year . ' Year Reunion Reception:</strong> '.$attend_dinner_5.'</li>'."\n";
		}
		if ($ride_in_parade)
		{
			$txt .= '<li><strong>Ride in the Parade:</strong> '.$ride_in_parade.'</li>'."\n";
		}
		if ($booklet)
		{
			$txt .= '<li><strong>Ride in the Parade:</strong> '.$ride_in_parade.'</li>'."\n";
		}
		$txt .= '</ul>'."\n";
		$txt .= '</div>'."\n";
/*
		if (reason_unique_name_exists('homecoming_thank_you_blurb'))
			$txt_with_blurb = get_text_blurb_content('homecoming_thank_you_blurb') . $txt;
		else
			$txt_with_blurb = '<p><strong>Thank you for registering for Homecoming!</strong></p>'."\n" . $txt;
*/
		echo $txt;
		$this->email_alumni($txt);
		$this->email_registrant($blurb . $txt);
		echo 'A copy of this confirmation has been sent to your email address.'."\n";
				
		connectDB('homecoming_connection');
		
		$qstring = "INSERT INTO `registrants` SET  
		first_name='".addslashes($first_name)."', 
		last_name='".addslashes($last_name)."', 
		class_year=".((!empty($class_year)) ? addslashes($class_year) : 'NULL').", 
		graduation_name='".((!empty($graduation_name)) ? addslashes($graduation_name) : 'NULL')."', 
		preferred_first_name='".((!empty($preferred_first_name)) ? addslashes($preferred_first_name) : 'NULL')."', 
		address='".((!empty($address)) ? addslashes($address) : 'NULL')."', 
		city='".((!empty($city)) ? addslashes($city) : 'NULL')."', 
		state_province = '".((!empty($state_province)) ? addslashes($state_province) : 'NULL')."', 
		zip = '".((!empty($zip)) ? addslashes($zip) : 'NULL')."', 
		home_phone = '".((!empty($home_phone)) ? addslashes($home_phone) : 'NULL')."',
		cell_phone = '".((!empty($cell_phone)) ? addslashes($cell_phone) : 'NULL')."',
		email = '".((!empty($email)) ? addslashes($email) : 'NULL')."',
		guest_name='".((!empty($guest_name)) ? addslashes($guest_name) : 'NULL')."',
		attended_luther='".((!empty($attended_luther)) ? addslashes($attended_luther) : 'NULL')."',
		guest_class=".((!empty($guest_class)) ? addslashes($guest_class) : 'NULL').",
		attend_program=".((!empty($attend_program)) ? addslashes($attend_program) : 'NULL').",
		dinner_dietary_restrictions= '".((!empty($dinner_dietary_restrictions)) ? addslashes($dinner_dietary_restrictions) : 'NULL')."',
		attend_50th_reception= '".((!empty($attend_50th_reception)) ? addslashes($attend_50th_reception) : 'NULL')."',
		attend_luncheon=".((!empty($attend_luncheon)) ? addslashes($attend_luncheon) : 'NULL').",
		attend_dinner_50_to_25=".((!empty($attend_dinner_50_to_25)) ? addslashes($attend_dinner_50_to_25) : 'NULL').",
		attend_dinner_20_to_10=".((!empty($attend_dinner_20_to_10)) ? addslashes($attend_dinner_20_to_10) : 'NULL').",
		attend_dinner_5=".((!empty($attend_dinner_5)) ? addslashes($attend_dinner_5) : 'NULL').",
                amount_paid ='".((!empty($amount_paid)) ? addslashes($amount_paid) : 'NULL')."',
		ride_in_parade='".((!empty($ride_in_parade)) ? addslashes($ride_in_parade) : 'NULL')."',
		booklet='".((!empty($booklet)) ? addslashes($booklet) : 'NULL')."' ";
		
		
		if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) ){
			$qstring .= ", status = 'TEST' ";
		}
	
		$qresult = db_query($qstring);
		connectDB(REASON_DB);
	
	}
	
	function get_thank_you_blurb()
	{
		if (reason_unique_name_exists('homecoming_thank_you_blurb'))
			$blurb = get_text_blurb_content('homecoming_thank_you_blurb');
		else $blurb = '<p><strong>Thank you for registering for Homecoming!</strong></p>'."\n";
		return $blurb;
	}
	
	function email_alumni($text)
	{
		$mail = new Email('alumni@luther.edu', 'noreply@luther.edu','noreply@luther.edu', 'New Homecoming Registration '.date('mdY H:i:s'),strip_tags($text), $text);
		$mail->send();
	}
	function email_registrant($text)
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
		$mail = new Email($this->controller->get('e-mail'),'alumni@luther.edu','alumni@luther.edu','Luther College Homecoming Registration Confirmation',strip_tags($mail_text),$mail_text);
		$mail->send();
	}
}
?>