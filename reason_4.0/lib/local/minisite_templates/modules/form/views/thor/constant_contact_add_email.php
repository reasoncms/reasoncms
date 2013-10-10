<?php
/**
 * @package reason_package_local
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
reason_include_once( 'classes/constant_contact/add_contact.php');
/**
 * Register form with Reason
 */
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'ConstantContactAddEmail';

/**
 * Sign up a given email to Luther News
 * in Constant Contact
 *
 * @author Brian Jones
 */
class ConstantContactAddEmail extends DefaultThorForm
{
	// The following codes are associated with the form on /connect/newsletters page
	// Any changes to the form will require a code update below
	
	var $newsletter_codes = array(
		'Luther Alumni Magazine (3x/year)' => array('username' => 'lcmagazine', 'listname' => 'E-Magazine Reminder', 'token' => "68d44a70-3e25-4c8d-aac7-73ce9e5e35fc"),
		'Parent Newsletter (monthly)' => array('username' => 'lcparents', 'listname' => 'Parents of Luther College Newsletter', 'token' => "41513ddc-bbac-4e16-bb11-44f0e0228ae4"),
		'The Bulletin (Tu, F)' => array('username' => 'lcbulletin', 'listname' => 'Bulletin', 'token' => "e069031c-ed27-4583-bfdf-13a7624da435"),
		'Diversity Today (monthly)' => array('username' => 'lcdiversity', 'listname' => 'Diversity Today', 'token' => "ef688df7-c24b-4ac1-826d-68d6ed22bd16"),
		'Luther College Chapel (M, W, F)' => array('username' => 'lcchapel', 'listname' => 'Upcoming In Chapel', 'token' => "d8296e3f-4097-4380-9136-7e83f6885d5e"), //The list name here may be changing in the near future
		'Luther News (weekly)' => array('username' => 'lccampusnews', 'listname' => 'Luther News', 'token' => "1082bbed-d18c-442f-9f45-f109d4c71888"),
		'Sustainability (2x/month)' => array('username' => 'lcsustainability', 'listname' => 'Newsletter', 'token' => "cc938f38-031e-40bb-a4e6-bc548e2f4799"),
		'Baseball' => array('username' => 'lcbaseball', 'listname' => 'Luther College Baseball Newsletter', 'token' => "887408da-ffa7-4223-b742-3c1770bb4b56"),
		'Men\'s Basketball' => array('username' => 'lcmbasketball', 'listname' => 'Luther College Men\'s Basketball Newsletter', 'token' => "51a3b3fd-ff76-4a29-8197-b22f2cd418ca"),
		'Women\'s Basketball' => array('username' => 'lcwbasketball', 'listname' => 'Luther College Women\'s Basketball Newsletter', 'token' => "81dd621f-847f-4a3b-9f94-a29ec759a872"),
		'Men\'s Cross Country' => array('username' => 'lccrosscountry', 'listname' => 'Luther College Men\'s Cross Country Newsletter', 'token' => "f2b76f59-6251-43ac-9c00-70ecc1f8dc1d"),
		'Women\'s Cross Country' => array('username' => 'lccrosscountry', 'listname' => 'Luther College Women\'s Cross Country Newsletter', 'token' => "f2b76f59-6251-43ac-9c00-70ecc1f8dc1d"),
		'Football' => array('username' => 'lcfootball1', 'listname' => 'Luther College Football Newsletter', 'token' => "67b2beef-b91f-439c-904d-ea518242cb41"),
		'Men\'s Golf' => array('username' => 'lcmgolf', 'listname' => 'Luther College Men\'s Golf Newsletter', 'token' => "c562309e-bc96-4c53-9cff-c349e630f5bd"),
		'Women\'s Golf' => array('username' => 'lcwgolf', 'listname' => 'Luther College Women\'s Golf Newsletter', 'token' => "e2f1c67a-c275-4f8a-8afb-332e9f36041e"),
		'Men\'s Soccer' => array('username' => 'lcmenssoccer', 'listname' => 'Luther College Men\'s Soccer Newsletter', 'token' => "2a4ab5fd-8edd-417a-92c4-1967b57ad062"),
		'Women\'s Soccer' => array('username' => 'lcwsoccer', 'listname' => 'Luther College Women\'s Soccer Newsletter', 'token' => "8aa9f9f9-90c4-41b0-87fe-a61f429c8e2a"),
		'Softball' => array('username' => 'lcsoftball', 'listname' => 'Luther College Softball Newsletter', 'token' => "115fc0e1-73dc-4c51-b2dd-e099b02522e9"),
		'Swimming and Diving' => array('username' => 'lcswimming', 'listname' => 'Luther College Swimming and Diving Newsletter', 'token' => "d2439e02-e73c-480a-b25f-0d20c9ff4edf"),
		'Tennis' => array('username' => 'lctennis', 'listname' => 'Luther College Tennis Newsletter', 'token' => "51f68ba5-1381-4f46-967c-e388265ef1e4"),
		'Track and Field' => array('username' => 'lctrack', 'listname' => 'Luther College Track and Field Newsletter', 'token' => "df76086d-34db-4f85-a25b-5e0b5f6d5017"),
		'Volleyball' => array('username' => 'lcvolleyball', 'listname' => 'Luther College Volleyball Newsletter', 'token' => "af0829e6-eb70-407d-ba69-71b2f17ac48c"),
		'Wrestling' => array('username' => 'lcwrestling', 'listname' => 'Luther College Wrestling Newsletter', 'token' => "0dd9679c-4085-4522-94e4-e59166f05a3b"),
	);

    function process()
    {
        parent::process();
        $url = get_current_url();
        $email = $this->get_value_from_label('Email');
        
        $keepintouch = $this->get_value_from_label('<strong>Keep in Touch</strong>');
        $lifeatluther = $this->get_value_from_label('<strong>Life at Luther</strong>');
        $norseathletics = $this->get_value_from_label('<strong>Norse Athletics</strong> (Intro to season, recap of season & team highlights)');
        
        if ($keepintouch || $lifeatluther || $norseathletics)
        // one or more newsletters are checked on /connect/newsletters
        {
        	$this->newsletter_form($email);
        }
        else if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/connect\/?/", $url))  // connect site landing page
        {
        	add_contact("1082bbed-d18c-442f-9f45-f109d4c71888", "Luther News", $email);
        }
        
    }

    function run_error_checks()
    {
    	$url = get_current_url();
    	$email = $this->get_value_from_label('Email');
    	
    	$keepintouch = $this->get_value_from_label('<strong>Keep in Touch</strong>');
    	$lifeatluther = $this->get_value_from_label('<strong>Life at Luther</strong>');
    	$norseathletics = $this->get_value_from_label('<strong>Norse Athletics</strong> (Intro to season, recap of season & team highlights)');
    	
    	if (!($keepintouch || $lifeatluther || $norseathletics))
    	{
    		$this->set_error($this->get_element_name_from_label('<strong>Keep in Touch</strong>'), 'Check at least one of the newsletters below.');
    		$this->set_error($this->get_element_name_from_label('<strong>Life at Luther</strong>'), 'Check at least one of the newsletters below.');
    		$this->set_error($this->get_element_name_from_label('<strong>Norse Athletics</strong> (Intro to season, recap of season & team highlights)'), 'Check at least one of the newsletters below.');
    	}
        
    	if (empty($email))
    	{
    		$this->set_error($this->get_element_name_from_label('Email'), 'Email can\'t be blank.');
    	}
        else if (!$this->valid_email($email))
        {
            $this->set_error($this->get_element_name_from_label('Email'), 'Invalid email.');
        }
        parent::run_error_checks();
    }
    
    function newsletter_form($email)
    {
    	$newsletter_sections = array(
    		$this->get_value_from_label('<strong>Keep in Touch</strong>'),
    		$this->get_value_from_label('<strong>Life at Luther</strong>'),
    		$this->get_value_from_label('<strong>Norse Athletics</strong> (Intro to season, recap of season & team highlights)'),
    	);
    	
    	foreach ($newsletter_sections as $newsletter_section)
    	{
    		if ($newsletter_section != null)
    		{
	    		foreach ($newsletter_section as $newsletter)
	    		{
	    			add_contact($this->newsletter_codes[$newsletter]['token'], $this->newsletter_codes[$newsletter]['listname'], $email);
	    		}
    		}
    	}
    }
    
    function valid_email($email)
    {
    	if (preg_match("/@/", $email))
    	{
    		return true;
    	}
    	return false;
    }

}

?>
