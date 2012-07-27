<?php
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CampusDirectoryModule';
    reason_include_once( 'minisite_templates/modules/default.php' );
    reason_include_once( 'classes/object_cache.php' );
    include_once(DISCO_INC.'disco.php');

    define ('MAX_RESULTS', 100);
    
    class CampusDirectoryModule extends DefaultMinisiteModule
{
	// Allowed addresses for Post Office non-FERPA-restricted view:
	var $po = array('10.10.190.34','10.22.2.234'/*'192.203.196.2','192.203.196.3','192.203.196.4'*/);

	var $cleanup_rules = array(
		'view' => array('function' => 'turn_into_string'),
		'context' => array('function' => 'turn_into_string'),
		'free' => array('function' => 'turn_into_string'),
		'id_number' => array('function' => 'turn_into_string'),
		'sort' => array('function' => 'turn_into_string'),
		'netid' => array('function' => 'turn_into_array'),
		'pagetitle' => array('function' => 'turn_into_string'),
	);
	var $form;
	var $view;
	var $context;
	var $menu_data;
	var $majors;
	var $pdf_fonts;
	var $user_netid;
	var $photos;	
	var $search_url;
	var $result_comment;
	var $elements = array(
		'last_name' => array('type' => 'text','size' => '15'),
	);
	
		function init( $args = array() ) //{{{
	{		
		// If the IP address isn't local and there's no user, then we get the 
		// restricted off-campus view.
		$this->context = (strncmp('192.203.',$_SERVER['REMOTE_ADDR'],7) <> 0) ? 'external' : 'internal';
		if ($this->user_netid = reason_check_authentication()) $this->context = 'internal';
		if (isset($this->request['context']) && THIS_IS_A_DEVELOPMENT_REASON_INSTANCE) $this->context = $this->request['context'];
		
		if (isset($this->request['view'])) $this->view = $this->request['view'];
		if (in_array($_SERVER['REMOTE_ADDR'],$this->po) && ($this->view <> 'pdf')) $this->view = 'po';

		
		parent::init( $args );
		if($head_items =& $this->get_head_items())
/*		{

			$head_items->add_stylesheet('/global_stock/css/campus_dir.css');
			// iphone support; scales to screen and disables zooming
			$head_items->add_head_item('meta', array('name'=>'viewport','content'=>'width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;'));
		}
*/
		
/*
		// Allow any of the form elements (old or new) to be set from the URL or POST, and look like a submission
		foreach ($_REQUEST as $key => $val)
		{
			if (isset($this->elements[$key]))
			{
				$_REQUEST['submitted'] = true;
			} 
			else if (isset($this->old_form_keys[$key]))
			{
				$_REQUEST[$this->old_form_keys[$key]] = $val;
				$_REQUEST['submitted'] = true;				
			}
			else if (isset($this->cleanup_rules[$key]))
			{
				$_REQUEST['submitted'] = true;				
			}
		}		
		
*/
		$this->form = new disco();
		$this->form->elements = $this->elements;
		$this->form->actions = array('Search');
		$this->form->error_header_text = 'Search error';
		$this->form->add_callback(array(&$this, 'show_results'),'process');
		//$this->form->add_callback(array(&$this, 'display_form_help'),'post_show_form');
		$this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
		$this->form->init();
		//$this->get_menu_data();	
		$url_parts = parse_url( get_current_url() );
		$this->search_url = $url_parts['path'];

	}//}}}
	

	function display_form() //{{{
	{
		echo '<div id="campusDirForm">';
		$this->form->run();
		echo '</div>';	

	} //}}}
	
		function run()//{{{ 
	{
		//$this->get_menu_data();
		$this->display_form();

	} //}}}
	
	/** Return an error if not enough has been filled out in the form or passed in the URL.
	*/
	
	//todo change to require at least two characters.
	function run_error_checks(&$form)
	{
		// These fields don't count toward having filled out the form
		$not_sufficient = array('room','exact','pictures','display_as','view','context');
		$elements = $form->get_element_names();
		foreach ($elements as $element)
		{
			if (in_array($element,$not_sufficient)) continue;
			if ($form->get_value($element)) return true;
		}
		foreach ($this->cleanup_rules as $name => $rule)
		{
			if (in_array($name,$not_sufficient)) continue;
			if (isset($this->request[$name])) return true;
		}
		$form->set_error('name_or_username','You did not specify anything to search for.');
	}
	
	function show_results(&$form)
	{	
		$username = $form->get_value('last_name');
		//query ldap ou=People to get the user info for the user having the problem
//		$attributes = array('uid','cn','sn','officePhone');

		$attributes = array('dn','uid','ou','cn','sn','givenName','eduPersonNickname','displayName','mail','title',
			'eduPersonPrimaryAffiliation','officeBldg','studentPostOffice','officephone','spouseName',
			'address','ocPostalAddress', 'ocPhone','studentMajor','studentSpecialization',
                        'edupersonprimaryaffiliation',
                        'eduPersonAffiliation','studentStatus','alumclassyear',
			'eduPersonEntitlement','mobile');		

		$dir = new directory_service('ldap_luther');
		//$dir->search_by_attribute('uid', $username,  $attributes);

		//$query = $this->build_query($username, 'approx');
		//$dir->search_by_filter($query, $attributes);
		$search_string = array("sn = smit*");
//		$dir->search_by_filter($username, $attributes);
		$dir->filter_search($username,$attributes);
		$records = $dir->get_records();
			pray($records);	
		return $records;
	}
	
	function clean_input(&$q) //{{{
	{		
		foreach ($q as $fvar => $val) {
			if (!is_array($val))
			{
				// do the usual cleanup
				$q[$fvar] = ldap_escape(trim(strip_tags($q[$fvar])));
				// remove any weird characters
				$q[$fvar] = preg_replace('/\>|\<|\=|\~|\`|\!|\||\;|\:|\?|\+|\_|\^|\%|\#/','',$q[$fvar]);
			}
		}
		return $q;
	} //}}}

	
	/** Construct the query for searching for people, as well as the text description of the query.
	*/
	function build_query($q, $style = 'equal') //{{{
	{
		extract($this->clean_input($q));
		$filter = array();
		$filter_desc = array();
		$filter_desc_loc = array();
		$filter_desc_work = array();
		if ($style == 'equal') 
		{
			$cmp = '=';
			$post = '*';
			if (isset($exact))
				$pre = '';
			else
				$pre = '*';			
		}
		else
		{
			$cmp = '~=';
			$pre = $post = '';
		}
		$filter[] = '(!(givenName=Temporary))'; // exclude temporary accounts
		pray($q);
		if (!empty($q))
		$filter[]="(sn=smit*)";
		$filter[]="(cn=stev*)";
		pray($filter);
		if (count($filter) > 2) 
		{		
			$querystring = '(&'.join($filter, '').')';
			$querytext = (count($filter_desc)) ? 'people ' : '';
			$querytext .= join(' and ', $filter_desc);
			$query = array($querystring, $querytext);
			return ($query);
		}
		return false;
	}//}}}

}
?>