<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the transcript request form
//
////////////////////////////////////////////////////////////////////////////////

class TranscriptPageOneForm extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
		'amount' => 'cloaked',
		'your_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Your Information</h3>',
		),
		'name' => array(
			'type' => 'text',
			'size' => 35,
		),
//		'middle_initial' =>  array(
//			'type' => 'text',
//			'size'=> 2,
//                ),
//		'last_name' => array(
//			'type' => 'text',
//			'size'=> 35,
//		),
//		'previous_name' => array(
//			'type' => 'text',
//			'size' => 35,
//		),
		'daytime_phone' => array(
			'type' => 'text',
			'size' => 20,
		),
		'e-mail' => array(
			'type' => 'text',
			'size' => 35,
		),
		'unofficial_header' => array(
			'type' => 'comment',
			'text' => '<h3>Unofficial transcripts</h3>',
		),
		'unofficial' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => '&nbsp;',
			'options' => array('yes' => 'Yes', 'no' => 'No'),
                        'comments' => '<em>Unofficial</em> transcript are sent to your address via postal mail',
		),
                'official_header' => array(
			'type' => 'comment',
			'text' => '<h3>Official transcripts</h3>',
		),
                'official_comment' => array(
                        'type' => 'comment',
                        'text' => 'Official transcripts cost $5 per transcript'
                ),
                'official_type' => array(
                    'type' => 'radio_inline_no_sort',
                    'display_name' => 'What kind of official transcript would you like sent?',
                    'options' => array('paper'=>'Paper', 'eScrip' => 'eScrip-Safe'),
                    'comments' => '<br><a href="http://www.scrip-safe.com/" target=__blank>What is an eScrip-Safe transcript?</a>',
                ),
                'official_paper_comment' => array(
                        'type' => 'comment',
                        'text' => 'Number of <em>official</em> paper transcripts',
                ),		
                'official_escrip_comment' => array(
                        'type' => 'comment',
                        'text' => 'Number of <em>official</em> eScrip-Safe transcripts',
                ),
                'number_of_official' => array(
			'type' => 'text',
			'display_name' => '&nbsp;',
			'size' => 3,
		),
                'delivery_header' => array(
			'type' => 'comment',
			'text' => '<h3>Delivery Information</h3>',
		),                
                'deliver_to' => array(
                        'type' => 'radio_no_sort',
                        'display_name' => 'Where should these transcripts be delivered?',
                        'options' => array('Your address' => 'Your address', 'institution' => 'An Institution/Company')
                ),
                'institution_name' => array(
                        'type' => 'text',
                        'display_name' => 'Institution/&nbsp;Company&nbsp;Name',
                ),
                'institution_attn' => array(
                        'type' => 'text',
                        'display_name' => 'Attention'
                ),
                'institution_email' => array(
                        'type' => 'text',
                        'display_name' => 'Institution/Company E-mail'
                ),
                'address' => array(
			'type' => 'text',
			'size' => 35,
		),
		'city' => array(
			'type' => 'text',
			'size' => 35,
		),
		'state_province' => array(
			'type' => 'state_province',
			'display_name' => 'State/Province',
		),
		'zip' => array(
			'type' => 'text',
			'display_name' => 'Zip/Postal Code',
			'size' => 10,
		),
		'country' => array(
			'type' => 'country',
			'display_name' => 'Country',
		),
		'delivery_time' => array(
			'type' => 'radio',
			'display_name' => 'When to prepare transcripts',
			'options' => array( 
				'now'=>'Send out as soon as possible<br>
						(allow 48 hours processing time)',
				'after current semester' => 'Wait until current semester grades are posted',
				'after degree' => 'Wait until degree is posted',
			),
		),
                'submitter_ip' => 'hidden',
	);
	
	var $required = array('daytime_phone', 'e-mail');
	
	var $display_name = 'Transcript Request Info';
	var $error_header_text = 'Please check your form.';
	


	// style up the form and add comments et al	
	function  on_every_time()
        {
            $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);

             $username = reason_check_authentication(); // this will force login
             $group = id_of('transcripts_group');
             $has_access = (reason_user_is_in_group($username, $group));

            if ($has_access) {


                   $qlist = array('cn', 'sn');
                    $dir = new directory_service();
               
                    $lookup_login = 'uid='.$username.',dc=luther,dc=edu'; /// username is get login norsekey
                    $dir->serv_inst['ldap_luther']->set_conn_param('cn=webauth,dc=luther,dc=edu', $lookup_login);

                    $dir->search_by_attribute('uid', $username, $qlist);

                    $name = $dir->get_first_value('cn');
                    //$last_name  = $dir->get_first_value('sn');
                    $email = $dir->get_first_value('mail');

                    $this->show_form = true;

                    $this->change_element_type('name', 'solidtext');
                    $this->set_value('name', $name);
                    $this->change_element_type('e-mail', 'solidtext');
                    $this->set_value('e-mail', $email);

            } else {
                    if (reason_check_authentication ()){
                        echo  '<div class = "loginlogout">';
                        echo '<p>You are logged in as '.  reason_check_authentication() . '. Unfortunately, you do not have access to fill out this form.
                            Please contact the Office of the Registrar if you think this is an error.</p>';
                        echo '</div>';
                    }
                    $this->show_form = false;
            }
	}

        function no_show_form()
        {
            $txt = '<h3>Access to this form is restricted</h3>';
            $txt .= '<p>You are not currently logged in. Luther College students and alumni have access to this form. The contents will be displayed after you login.'."\n";
            $txt .= 'If you have forgotten your norsekey (username or password), please try our automated <a href="https://norsekey.luther.edu/prod1/forgot.php">
                Forgot My Norsekey</a> system.</p>';
            if (reason_unique_name_exists('transcript_request_form')){
                $asset_url = '/registrar/assets/Transcript_Request_Form.pdf';
            }
            $txt .= '<p>If you\'d prefer, you can mail in your request and payment by downloading and filling out this <a href="'.$asset_url.'">Tanscript Request Form PDF</a></p>';
            $txt .= '<div class = "loginlogout">';

            $url = get_current_url();
            $parts = parse_url( $url );
            $url = $parts['scheme'].'://'.$parts['host'].'/login/?dest_page='.$parts['scheme'].'://'.$parts['host'].$parts['path'];
            $txt .= '<a href="'.$url.'">Log In</a>';
            $txt .= '</div>';
            return $txt;
        }
	function pre_show_form()
	{
            /// show a logout link if logged in
            if (reason_check_authentication ()) {
                echo  '<div class = "loginlogout">';
                echo '<p>You are logged in as '.$this->get_value('name') . '</p>';
                $url = get_current_url();
		$parts = parse_url( $url );
		$url = $parts['scheme'].'://'.$parts['host'].'/login/?logout=true&dest_page='.$parts['scheme'].'://'.$parts['host'].$parts['path'];
                $txt = '<a href="'.$url.'">Log Out</a>';
                $txt .= '</div>';
                echo $txt;
            }
		echo '<div id="transcriptRequestForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
	
	function needs_payment()
	{
	  	$amount = 0;
	  	$official_number = $this->get_value('number_of_official');
	  	
		if ($official_number)
	  	{
			$amount = $amount + ($official_number * 5);
		}
		$this->set_value('amount', $amount);
		
		if ($amount == 0)
		{
			return 'TranscriptRequestConfirmation';
		}else{
			return 'TranscriptPageTwoForm';
		}
	}
        function  run_error_checks()
        {
            parent::run_error_checks();

            if ($this->get_value('number_of_official') && (!preg_match('/^\d+$/', $this->get_value('number_of_official'))))
            {
                $this-> set_error('number_of_official', "Please enter a whole number");
            }
        }
}
?>