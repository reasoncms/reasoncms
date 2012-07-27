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
		'first_name' => array('type' => 'text','size' => '15'),
		'last_name' => array('type' => 'text','size' => '15'),
//		'more_comment' => array('type' => 'comment','text' => '<h3>More options</h3>'),
//		'search_for' => array(
//			'display_name' => 'Search for',
//			'type' => 'select_no_sort',
//			'options' => array('anyone'=>'Anyone',
//					   'student'=>'Students only',
//					   'faculty'=>'Faculty only',
//					   'staff'=>'Staff only',
//					   ),
//			),
//		'phone_number' => array('type' => 'text','size' => '15',
//			'comments' => '<span class="formComment">e.g. 4444<span>'),
//		'email_address' => array('type' => 'text','size' => '15',
//			'comments' => '<span class="formComment">e.g. mheiman<span>'),
//		'building' => array('type' => 'text','size' => '15'),
//		'room' => array(
//			'type' => 'text',
//			'size' => '15',
//			'display_name' => 'Office / Room',
//			),
//		'student_comment' => array('type' => 'comment','text' => '<h3>Students</h3>'),
//		'major' => array(
//			'display_name' => 'Major / Concentration',
//			'type' => 'text','size' => '15'),
//		'year' => array('type' => 'text','size' => '15',
//			'comments' => '<span class="formComment">e.g. 2012<span>'),
//		'faculty_comment' => array('type' => 'comment','text' => '<h3>Faculty/Staff</h3>'),
//		'department' => array('type' => 'text','size' => '15'),
//		'office' => array('type' => 'text','size' => '15'),
//		'title' => array('type' => 'text','size' => '15',
//			'comments' => '<span class="formComment">e.g. dean<span>'),
//
//		'exact' => array(
//			'display_name' => 'Find matches only at the beginning of fields.',
//			'type' => 'checkboxfirst',
//			),
//		'pictures' => array(
//			'display_name' => 'Show pictures',
//			'type' => 'checkboxfirst',
//			),
//		'display_as' => array(
//			'display_name' => 'Display as',
//			'type' => 'select_no_sort',
//			'options' => array('list'=>'Directory Listing',
//					   'book'=>'Photo Book',
//					   ),
//			),
		);
	// These are fields from the old directory form that people might try to pass in a URL,
	// mapped to the appropriate field in the new form.
	var $old_form_keys = array(
		'dept' => 'department',
		'givenName' => 'first_name',
		'sn' => 'last_name',
		'phone'=>'phone_number',
		'email'=>'email_address',
		'target'=>'search_for',
		'display'=>'display_as',
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
		{
			$head_items->add_stylesheet('/global_stock/css/campus_dir.css');
			// iphone support; scales to screen and disables zooming
			$head_items->add_head_item('meta', array('name'=>'viewport','content'=>'width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;'));
		}
echo '<p>how about them Cubbies</a></p>';
		
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
		
		$this->form = new disco();
		$this->form->elements = $this->elements;
		$this->form->actions = array('Search');
		$this->form->error_header_text = 'Search error';
		$this->form->add_callback(array(&$this, 'show_results'),'process');
		$this->form->add_callback(array(&$this, 'display_form_help'),'post_show_form');
		$this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
		$this->form->init();
		$this->get_menu_data();	
		$url_parts = parse_url( get_current_url() );
		$this->search_url = $url_parts['path'];

	}//}}}

	function run()//{{{ 
	{
		$this->get_menu_data();
		$this->display_form();

	} //}}}

	/** Return an error if not enough has been filled out in the form or passed in the URL.
	*/
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
		$form->set_error('first_name','You did not specify anything to search for.');
	}
	
	function show_results(&$form)
	{
		// Assemble all the data that's come in via the form or the URL into $q
		$elements = $form->get_element_names();
		foreach ($elements as $element)
		{
			if ($form->get_value($element))
				$q[$element] = $form->get_value($element);
		}
		foreach ($this->cleanup_rules as $name => $rule)
		{
			if (isset($this->request[$name]))
				$q[$name] = $this->request[$name];
		}
		$query_parts = $this->build_query($q);
		if (!$query_parts)
		{
			$form->set_error('first_name', 'You do not appear to be searching for anything.  Please try again.');
			return;
		}
		// Get results from the Telecomm database
		$telecomm = $this->get_telecomm_data($q);
		
		// Build and execute an LDAP query
		list($query, $query_desc) = $query_parts;
		$entries = $this->get_search_results($query);

		// If there aren't any results, try again with similarity searching
		if (!count($entries))
		{
			list($query, $query_desc) = $this->build_query($q, 'approx');
			$entries = $this->get_search_results($query);
			$this->result_comment = '<br /><strong>Note:</strong> No exact matches were found; these are entries similar to what you searched for.';
		}
		
		// If we have some results, call the appropriate display method
		if (count($entries) ) {
			$this->scrub_results($entries);
			switch ($this->view)
			{
				case 'pdf':
					if ($form->get_value('display_as') == 'book')
						$this->pdf_export_photobook($entries);
					else
						$this->pdf_export_list($entries);
					break;
				case 'export':
					$this->export_tab_results($entries);
					break;
				case 'xml':
					$this->export_xml_results($entries);
					break;
				default:
					if ($form->get_value('display_as') == 'book')
						$this->display_results_photobook($entries, $query_desc);
					else
						$this->display_results($entries, $query_desc, $telecomm);
			}
			$form->show_form = false;
		} else {
			$form->set_error('first_name', 'Your search for '.$query_desc.' did not find any matches.  Please try again.');
		}
	}
		
	function display_form() //{{{
	{
		$this->form->change_element_type( 'building', 'select', array('options' => $this->menu_data['buildings']) );
		//$this->form->change_element_type( 'major', 'select', array('options' => $this->menu_data['majors']) );
		$this->form->change_element_type( 'department', 'select', array('options' => $this->menu_data['acad']) );
		$this->form->change_element_type( 'office', 'select', array('options' => $this->menu_data['admin']) );
		$this->form->set_value('pictures', true);
		$this->form->set_value('exact', true);
		
		if ($this->context == 'external')
		{
			$this->form->remove_element('phone_number');
			$this->form->remove_element('email_address');
			$this->form->remove_element('building');
			$this->form->remove_element('room');
			$this->form->remove_element('student_comment');
			$this->form->remove_element('major');
			$this->form->remove_element('year');
			$this->form->remove_element('faculty_comment');
			$this->form->remove_element('pictures');
			$this->form->remove_element('display_as');			
		}
		
		echo '<div id="campusDirForm">';
echo '<p>how about them Cubbies</a></p>';


		// Prominent login link for off-campus mobile users
		if ($this->context == 'external' && !reason_check_authentication())
		{
			echo '<p id="mobileLogin"><a href="/login/">Log in for full access</a></p>';
		} 	
		$this->form->run();
		echo '</div>';	

	} //}}}
	
	/** Generate a textual description of the results of the search
	*   @param $people array of search results
	*   @param $desc string describing the search parameters (optional)
	*/
	function get_search_status($people, $desc= '')
	{
		$status =  '<p class="matchCount">Your search ';
		$status .= ($desc) ? 'for '. $desc .' found ' : 'found ';
		if (count($people) > MAX_RESULTS)
			$status .= 'more than '. MAX_RESULTS .' matches. Showing the first '. MAX_RESULTS .'.';
		elseif (count($people) > 1)
			$status .= count($people).' matches.';
		else 
			$status .= 'one match.';
		$status .= $this->result_comment;
		$status .= ' <a class="newSearch" href="'.$this->search_url.'">Search Again</a></p>';
		return $status;		
	}
	
	function display_results($people, $desc, $telecomm) //{{{
	{
		$depts = $this->find_depts_in_result_set($people);
		if (count($depts))
			$sites = $this->get_reason_sites($depts);
		else
			$sites = array();
		echo $this->get_search_status($people, $desc);
		$image_class = ($this->form->get_value('pictures')) ? '' : 'noImage';
		echo '<p class="personPager"></p>';
		echo '<div id="searchResults">';
		// Display any non-person results from the Telecomm database
		if (count($telecomm))
		{
			foreach ($telecomm as $name => $data) {
				echo '<div class="person">';
				echo '<div class="personBody '.$image_class.'">';
				echo '<div class="personHeader">';
				echo '<ul>';
				echo '<li class="personName">' . $name . '</li>';
				if (isset($data[0]))
				{
					echo '<li class="officePhone">' . $data[0] . '</li>';
					unset ($data[0]);
				}
				if (isset($sites[$name]))
					echo '<li class="officeSite"><a href="' . $sites[$name]['url'] . '">Web Site</a></li>'; 
				echo '</div>';
				echo '<div class="officeNumbers">';
				echo '<ul>';
				foreach ($data as $name => $number)
					echo '<li><span class="officeService">'.$name.'</span><span class="officeNumber">' . $number . '</span></li>';	
				echo '</ul>';
				echo '</div>';
				echo '</div>';
				echo '</div>';
			}
		}
		// Show all of the people results
		foreach ($people as $data) {			
			echo '<div class="person">';
			if ($this->form->get_value('pictures') != false)
			{	
				echo '<div class="personPhoto">';
				echo '<img src="/stock/ldapimage.php?id='.$data['uid'][0].'">';
				echo '</div>';
			}
			echo '<div class="personBody '.$image_class.'">';
			echo '<div class="personHeader">';
			echo '<ul>';
			echo '<li class="personName">' . $this->format_name($data) . '</li>';
			if (isset($data['alumclassyear']))
			{
				echo '<li class="personYear">'.$data['alumclassyear'][0].'</li>';
			} else {
				if ($affil = $this->format_affiliation($data))
					echo '<li class="personAffil">'.$affil.'</li>';
			}
			if (isset($data['studentMajor']) && $data['edupersonprimaryaffiliation'][0] == 'student')
			{
				echo '<li class="personMajor">'. $this->format_majors($data) .'</li>';
			}
			if (isset($data['mail']))
			{
				echo '<li class="personEmail">'. $this->format_email($data['mail'][0]) .'</li>';
			}
			echo '</ul>';
			echo '</div>'; //personHeader
			echo '<div class="personAddresses">';
			
			// If this is faculty or staff
			if ($data['edupersonprimaryaffiliation'][0] == 'faculty' || $data['edupersonprimaryaffiliation'][0] == 'staff')
			{
				if (isset($data['title']))
				{
					echo '<ul class="personPosition">';
					foreach ($data['title'] as $title)
						echo '<li class="personTitle">'.$title.'</li>';
					foreach ($data['ou'] as $dept)
					{
						if ($dept == 'No Department') continue;
						echo '<li class="personOu">'.$this->make_search_link($dept,'department',$dept);
						if (isset($sites[$dept]))
							echo ' <a class="officeSite" href="'.$sites[$dept]['url'].'">[web site]</a>';
						echo '</li>';
					}
					if (isset($data['carlfacultyleaveterm']))
						echo '<li class="personStatus">'. $this->format_leave($data) . '</li>';
					echo '</ul>';
				}
				if (isset($data['officelocation']))
				{
					echo '<ul class="personCampusAddress">';
					foreach ($data['officelocation'] as $loc)
						echo '<li class="personOffice">'.$loc.'</li>';
					if (isset($data['campuspostaladdress']))
						foreach ($data['campuspostaladdress'] as $loc)
							echo '<li class="personMailstop">Mail stop: '.$loc.'</li>';
					echo '</ul>';
				}
				if (isset($data['address']) && !isset($data['carlhidepersonalinfo']))
				{
					echo '<ul class="personHomeAddress">';
					echo $this->format_postal_address($data['address'][0]);
					if (isset($data['homephone']))
						echo '<li class="personHomePhone">'.$data['homephone'][0].'</li>';
					if (isset($data['spousename']))
						echo '<li class="personSpouse">'.$data['spousename'][0].'</li>';
				}
				
			}
			else // if this is a student
			{
				echo '<ul class="personCampusAddress">';
				if (isset($data['address']))
				{
					echo $this->format_postal_address($data['address'][0]);
				}
				if ($status = $this->format_status($data))
					echo '<li class="personStatus">'.$status.'</li>';
				echo '</ul>';
				if (isset($data['carlstudentpermanentaddress']))
				{
					echo '<ul class="personHomeAddress">';
					echo $this->format_postal_address($data['carlstudentpermanentaddress'][0]);
					echo '</ul>';
				}
					
			}
			echo '</div>'; //personAddresses
			
			echo '<div class="personContacts">';
			echo '<ul class="personPhones">';
			if ($phone = $this->format_phone($data))
				echo '<li class="personCampusPhone">'.$phone.'</li>';
			if ($cells = $this->format_cell($data))
				foreach ($cells as $cell)
					echo '<li class="personCellPhone">cell: '.$cell.'</li>';
			echo '</ul>';
			echo '</div>';
			echo '</div>'; // personBody
			echo '</div>'; // person
	
		} /* endforeach */
		echo '</div>'; // searchResults
	
		echo '<p class="personPager"></p>';
		echo '<p class="searchFoot"><a class="newSearch" href="'.$this->search_url.'">Search Again</a></p>';
	}//}}}
	
	function display_results_photobook($people, $desc) //{{{
	{
		echo $this->get_search_status($people, $desc);
		echo '<p class="personPager"></p>';
		echo '<div id="searchResults" class="photoBook">';
		foreach ($people as $data) {			
			echo '<div class="person">';
			echo '<div class="personPhoto">';
			echo '<img src="/stock/ldapimage.php?id='.$data['uid'][0].'">';
			echo '</div>';
			echo '<div class="personInfo">';
			echo '<ul>';
			echo '<li class="personName">' . $this->make_search_link($this->format_name($data),'netid[]',$data['uid'][0]);
			if (isset($data['alumclassyear']))
			{
				echo ', '.$data['alumclassyear'][0];
			}
			echo '</li>';
			if ($data['edupersonprimaryaffiliation'][0] == 'student')
			{
				if (isset($data['studentMajor']))
				{
					echo '<li>'.$this->format_majors($data).'</li>';
				}				
			} else {
				if (isset($data['title']))
				{
					foreach ($data['title'] as $title)
						echo '<li class="personTitle">'.$title.'</li>';	
				}
			}
			echo '</div>'; // personInfo
			echo '</div>'; // person
		}
		echo '</div>'; // searchResults
		echo '<p class="personPager"></p>';
		echo $this->build_printable_link();
	}//}}}
	
	/** dump search results as a tab-delimited file
	**/
	function export_tab_results($people)
	{
		$output = array();
		foreach ($people as $data) {			
			$row = array();
			$row[] = $data['sn'][0];
			$row[] = $data['givenname'][0];
			$row[] = (isset($data['edupersonnickname']) && $data['edupersonnickname'][0] != $data['givenname'][0]) ? $data['edupersonnickname'][0] : '';
			$row[] = (isset($data['mail'])) ? $data['mail'][0] : '';
			$row[] = $this->format_phone($data);
			$row[] = (isset($data['ou'])) ? join(' / ', $data['ou']): '';
			if ($data['edupersonprimaryaffiliation'][0] == 'student')
			{
				$row[] = (isset($data['address'])) ? join(' / ', $this->format_postal_address($data['address'][0], false)) : '';
			} else {
				$row[] = (isset($data['officelocation'])) ? join(' / ', $data['officelocation']): '';				
			}
			$output[] = $row;
		}		
		while (ob_get_level()) ob_end_clean(); // discard other page output
		header('Content-type: text/tab-separated-values');
		header('Content-disposition: inline; filename=directory.tab');
		echo join("\t",array('Last Name', 'First Name', 'Nick Name', 'Email', 'Phone', 'Dept', 'Address'))."\r\n";
		foreach ($output as $row) {
			echo join("\t",$row)."\r\n";
		}
		exit; // end processing so no more output is created.	
	}
	
	/** dump search results as an xml file
	**/
	function export_xml_results($people)
	{
		$output = array();
		foreach ($people as $data) {			
			$row = array();
			$row['netid'] = $data['uid'][0];
			$row['fullname'] = $this->format_name($data);
			$row['lastname'] = $data['sn'][0];
			$row['firstname'] = $data['givenname'][0];
			$row['nickname'] = (isset($data['edupersonnickname']) && $data['edupersonnickname'][0] != $data['givenname'][0]) ? $data['edupersonnickname'][0] : '';
			$row['email'] = (isset($data['mail'])) ? $data['mail'][0] : '';
			$row['phone'] = $this->format_phone($data);
			$row['dept'] = (isset($data['ou'])) ? join(' / ', $data['ou']): '';
			$row['po'] = (isset($data['campuspostaladdress'])) ? $data['campuspostaladdress'][0] : '';
			if ($data['edupersonprimaryaffiliation'][0] == 'student')
			{
				$row['address'] = (isset($data['address'])) ? join(' / ', $this->format_postal_address($data['address'][0], false)) : '';
				$row['major'] = (isset($data['studentMajor'])) ? $data['StudentMajor'][0] : '';
				$row['class'] = (isset($data['alumclassyear'])) ? $data['alumclassyear'][0] : '';
			} else {
				$row['address'] = (isset($data['officelocation'])) ? join(' / ', $data['officelocation']): '';				
			}
			$output[] = $row;
		}		
		while (ob_get_level()) ob_end_clean(); // discard other page output
		header('Content-type: text/xml');
		echo '<search_results>';
		
		foreach ($output as $row) {
			if (!empty($row['netid'])) {
				printf ('<result id="%s">', $row['netid']);
				foreach ($row as $label => $value) {
					printf('<%s>%s</%s>', $label, htmlentities($value), $label);
				}
				echo '</result>';
			}
		}
		echo '</search_results>';		
		exit; // end processing so no more output is created.	
	}
	
	/** Based on the current view context, modify the data in the result set to show only what
	*   should be seen
	**/
	function scrub_results(&$results)
	{
		// Attributes which should be hidden from the external view
		$ext_suppress = array('officelocation','campuspostaladdress', 'address',
			'carlstudentpermanentaddress', 'homephone', 'studentMajor', 'studentspecialization',
			'carlhomeemail','spousename','alumclassyear','carlcohortyear','mobile',
			'studentstatus');
		
		foreach ($results as $key => $data)
		{
			// Remove the people who should be gone completely.
			if ($this->view != 'po' && isset($data['carlhideinfo']) && $data['carlhideinfo'][0] == 'TRUE')
			{
				unset($results[$key]);
				continue;
			}

			if (isset($data['carlhidepersonalinfo']))
			{
				unset($results[$key]['address']);
				unset($results[$key]['homephone']);
				unset($results[$key]['spousename']);
			}
			
			if ($this->context == 'external')
			{
				foreach ($ext_suppress as $attr)
					unset($results[$key][$attr]);
			}
		}
	}
	
	function make_search_link($text, $field, $value)
	{
		$params = '';
		// carry over any display params that are relevant
		if (isset($_REQUEST['pictures']))
			$params = '&pictures='.$_REQUEST['pictures'];
		return sprintf('<a class="crossRef" href="?%s=%s%s" title="Search for %s">%s</a>', urlencode($field), urlencode($value), $params, strip_tags($text), $text);	
	}
	
	function format_name($data)
	{
		if (isset($data['edupersonnickname']))
			$name = $data['edupersonnickname'][0];
		else if (isset( $data['givenname'] ))
			$name = $data['givenname'][0];

		if (isset($data['displayname'])) 
			$name =  $data['displayname'][0]; 
		else if (isset($name) && isset($data['sn'])) 
			$name .= ' ' . $data['sn'][0];
		else
			$name = $data['cn'][0];

		return $name;
	}
	
//	function format_majors($data)
//	{
//		foreach ($data['carlmajor'] as $major)
//			$majors[] = $this->make_search_link('<span class="major">'.$this->majors[$major].'</span>', 'major', $major);
//		if (isset($data['carlconcentration']))
//		{
//			foreach ($data['carlconcentration'] as $major)
//				$majors[] = $this->make_search_link('<span class="concentration">'.$this->majors[$major].'</span>', 'major', $major);
//		}
//		return '('.join(' / ', $majors).')';		
//	}

	function format_phone($data)
	{
		$phones = array();
		if ($data['edupersonprimaryaffiliation'][0] == 'student')
		{
			if (isset($data['homephone']))
				$phones = $data['homephone'];
		} else {
			if (isset($data['officephone']))
				$phones = $data['officephone'];
		}
		
		foreach ($phones as $phone)
		{
			// Strip out all but the extension for internal viewers, 
			// except on students with 222 exchanges and Northfield addresses 
			// who need full numbers listed.
			if ($this->context <> 'external' && 
			     !($data['edupersonprimaryaffiliation'][0] == 'student' &&
			     isset($data['address']) &&
			     stristr($data['address'][0],'Northfield')) && 
			       strpos($phone, '+1 507 222') !== FALSE) 
			{ 
				$phonetemp = str_replace('+1 507 222 ', '', $phone);
				// add the "x" except on extensions starting with '9' which are 
				// voice mailboxes requiring special access dialing:
				$prefix = (substr($phonetemp,0,1) == '9') ? '3737** ' : 'x';
				$display[] = $prefix.$phonetemp;
			} 
			// For external viewers, just strip out the +1
			else 
			{
				$phonetemp = str_replace('+1 ', '', $phone);
				// .. unless it's one of those special mailboxes
		 		if (substr($phonetemp,4,1) == '222 9') 
		 			$phonetemp = substr($phonetemp,0,8).'3737 '.substr($phonetemp,8,4);
		 		$display[] = $phonetemp;
			}
		}
		if (isset($display))
			return join(' / ',$display);
		else
			return '';
	}
	
	function format_cell($data)
	{
		$cells = array();
		if (isset($data['mobile']))
		{
			foreach ($data['mobile'] as $cell)
			{
				if (isset($data['homephone']) && in_array($cell, $data['homephone']))
					continue;
				else
					$cells[] = str_replace('+1 ', '', $cell);
			}
		}
		return $cells;
	}
	
	function termCmp($a,$b) {
	// sort an array of terms in 00/TT format
		list($aYear,$aTerm) = split('/',$a);
		list($bYear,$bTerm) = split('/',$b);
		$term['FA'] = 4;
		$term['SU'] = 3;
		$term['SP'] = 2;
		$term['WI'] = 1;
		if ($aYear == $bYear) { $result = ($term[$aTerm] < $term[$bTerm]) ? -1 : 1; }
		else { $result = ($aYear < $bYear) ? -1 : 1; }
		return $result;
	}

	/** Take a list of leave terms in 00/TT format and turn them into
	*   a human-friendly string that describes when the leave is happening.
	*/
	function format_leave($data)
	{		
		$termlist = $data['carlfacultyleaveterm'];
		usort($termlist, array($this,'termCmp'));
		  $termName['FA'] = 'Fall';
		  $termName['WI'] = 'Winter';
		  $termName['SP'] = 'Spring';
		  $termName['SU'] = 'Summer';
		
		  $term['FA'] = 4;
		  $term['SU'] = 3;
		  $term['SP'] = 2;
		  $term['WI'] = 1;
		  $start[] = $termlist[0];
		  $currEnd = $termlist[0];
		  for ($i=0; $i<(sizeof($termlist)-1); $i++) {
			list($aYear,$aTerm) = split('/',$termlist[$i]);
			list($bYear,$bTerm) = split('/',$termlist[$i+1]);
			// if the year is the same, see if the terms are sequential
			if ($aYear == $bYear) {
				if (($term[$aTerm] + 1) == $term[$bTerm]) {
					$currEnd = $termlist[$i+1]; 
				} else { 
					$end[] = $termlist[$i];
					$start[] = $termlist[$i+1];
					$currEnd = $termlist[$i+1];
				}
			// if the years aren't the same, see if they're sequential
			} elseif ((int)$aYear + 1 == (int)$bYear) {
				// If this is Fall -> Winter, append to the current sequence
				if ($term[$aTerm] - $term[$bTerm] == 3) {
					$currEnd = $termlist[$i+1];
				// Otherwise, start a new sequence
				} else {
					$end[] = $termlist[$i];
					$start[] = $termlist[$i+1];
					$currEnd = $termlist[$i+1];
				}
			// if the years aren't the same or sequential, start a new sequence
			} else {
				$end[] = $termlist[$i];
				$start[] = $termlist[$i+1];
				$currEnd = $termlist[$i+1];
			}
		  }
		  $end[] = $currEnd;
		  for ($i=0; $i<sizeof($start); $i++) {
			// convert to Term Year format for display
			list($lyear,$lterm) = split('/', $start[$i]);
			$start[$i] = $termName[$lterm].' 20'.$lyear;
			list($lyear,$lterm) = split('/', $end[$i]);
			$end[$i] = $termName[$lterm].' 20'.$lyear;
			$range[] = ($start[$i] == $end[$i]) ? $start[$i] : $start[$i].' through '.$end[$i];
		  }
		  if (is_array($range)) $range_str = join(', ',$range);
		return 'Off campus: '. $range_str;		
	}
	
	function format_postal_address($address, $html = true)
	{
		$parts = split('\$', $address);
		if ($html)
		{
			$return = '';
			foreach ($parts as $part)
				$return .= '<li>'.$part.'</li>'."\n";
			return $return;
		} else {
			return $parts;
		}
	}
	
	function format_email($address)
	{
		if ($this->context == 'external')
			return str_replace('.', '&nbsp;&#046;&nbsp;', str_replace('@', '&nbsp;&lt;&#065;&#084;&gt;&nbsp;', $address));
		else
			return '<a href="mailto:'.$address.'">'.$address.'</a>';
	}

	function format_status($data)
	{
		$statusFlag['F'] = false; // Full time
		$statusFlag['N'] = false; // ?
		$statusFlag['G'] = false; // Grad
		$statusFlag['L'] = 'On Leave';
		$statusFlag['R'] = 'On Leave'; // required
		$statusFlag['W'] = 'Withdrawn'; // probably not used
		$statusFlag['X'] = 'Early Finish';
		$statusFlag['O'] = 'Off Campus Program';
		
		if (isset($data['studentstatus']))
			return $statusFlag[$data['studentstatus'][0]];
		else
			return false;
	}
		
	function format_affiliation($data)
	{
		// define the default sort order for affiliations
		$stat['faculty'] = 1;
		$stat['staff'] = 2;
		$stat['alum'] = 3;
		$stat['parent'] = 4;
		$stat['student'] = 5;
		$stat['trustee'] = 6;
		$stat['affiliate'] = 7;

		$affils = array();
		foreach ($data['edupersonaffiliation'] as $affil)
		{
			if ($affil == 'alum' && isset($data['carlcohortyear']))
				$affils[$stat[$affil]] = 'Alum ('.$data['carlcohortyear'][0].')';
			else
				$affils[$stat[$affil]] = ucfirst($affil);
		}
		ksort($affils);
		return join(' / ', $affils);
	}
	
	function format_search_key($key)
	{
		return '<span class="searchKey">'.$key.'</span>';	
	}
	

//lane
	//function display_form_help() //{{{
	//{
	//	if ($blurb = get_text_blurb_content('campus_directory_help_blurb'))
	//	{
	//		echo '<div id="campusDirHelp">';
	//		if ($this->context == 'external')
	//			echo '<p><strong>Off-campus users:</strong> If you have a NorseKey, you can <a href="/login/">log in for full directory access.</a></p>';
	//		echo get_text_blurb_content('campus_directory_corrections_blurb');
	//		echo $blurb;
	//		echo '</div>';
	//	} else {
	//		echo '<div id="campusDirCorrections">';
	//		echo get_text_blurb_content('campus_directory_corrections_blurb');
	//		echo '</div>';			
	//	}
	//}
			
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
		// If you add something here, be sure to update the check at the bottom that's 
		// discarding filters with only 3 elements
		$filter[] = '(!(ou=Null temporary OU))'; // exclude temporary accounts
		$filter[] = '(!(description=Left feed*))'; // exclude expired accounts
		//$filter[] = '(eduPersonEntitlement=urn:mace:carleton.edu:entl:whitepages)';
		$filter[] = '(edupersonentitlement=urn:mace:luther.edu:entl:whitepages)';
		/*if(!empty($id_number)) {
		*	//$filter[] = "(carlColleagueid$cmp$id_number)";
		*	$filter[] = "(carlColleagueid$cmp$id_number)";
		*	$filter_desc[] = 'whose ID Number is ' . $this->format_search_key($id_number);
		*}
                 *
                 */
		if(!empty($first_name)) {
			$filter[] = "(|(givenName$cmp$pre$first_name$post)(eduPersonNickname$cmp$pre$first_name$post))";
			$filter_desc[] = 'whose first name is ' . $this->format_search_key($first_name);
		}
		if(!empty($last_name)) {
			$filter[] = "(sn$cmp$pre$last_name$post)";
			$filter_desc[] = 'whose last name is ' . $this->format_search_key($last_name);
		}
		if(!empty($search_for) && $search_for != 'anyone') {
			if ($search_for == 'facstaff')
			{
				$filter[] = '(|(edupersonprimaryaffiliation=faculty)(edupersonprimaryaffiliation=staff))';
				$filter_desc[] = 'who are faculty or staff';
			} else {
				$filter[] = "(edupersonprimaryaffiliation=$search_for)";
				$filter_desc[] = 'whose role is ' . $this->format_search_key($search_for);
			}
		}
		if(!empty($phone_number)) {
			$filter[] = "(|(homePhone$cmp$post$phone_number)(officephone$cmp$post$phone_number))";
			$filter_desc[] = 'whose phone number is '. $this->format_search_key($phone_number);
		}
		if(!empty($email_address)) {
			$filter[] = "(|(mail$cmp$pre$email_address$post)(carlHomeEmail$cmp$pre$email_address$post))";
			$filter_desc[] = 'whose email is '. $this->format_search_key($email_address);
		}
		if(!empty($building)) {
			$room = (!empty($room)) ? ' '.$room : '';
			$filter[] = "(|(officelocation$cmp$building$room$post)(carlStudentCampusAddress$cmp$building$room$post))";
			$filter_desc[] = 'who live or work in '. $this->format_search_key($building . ' ' . $room);
		}
//		if(!empty($major)) {
//			$majors = split(',',$major); // majors can be a comma separated list
//			$filter_string = '(|';
//			foreach ($majors as $maj)
//			{
//				$filter_string .= "(carlMajor$cmp$maj)(studentSpecialization$cmp$maj)";
//			}
//			$filter[] = $filter_string . ')';
//			$filter_desc[] = 'whose major or concentration is '. $this->format_search_key($this->majors[$maj]) ;
//		}
		if(!empty($year)) {
			$filter[] = "(|(alumclassyear=$year)(carlCohortYear=$year))";
			$filter_desc[] = 'whose class year is '.$this->format_search_key( $year );
		}
		if(!empty($department)) {
			$filter[] = "(ou=$department)";
			$filter_desc[] = 'who work in '. $this->format_search_key($department) ;
		}
		if(!empty($office)) {
			$filter[] = "(ou=$office)";
			$filter_desc[] = 'who work in '. $this->format_search_key($office) ;
		}
		if(!empty($title)) {
			$filter[] = "(title$cmp$pre$title$post)";
			$filter_desc[] = 'whose title contains '. $this->format_search_key($title) ;
		}
		if (isset($netid) && is_array($netid) && count($netid))
		{
			$netid_filter = '(|';
			foreach($netid as $id)
				$netid_filter .= "(uid=$id)";
			$netid_filter .= ')';
			$filter[] = $netid_filter;
		}
		if (isset($exclude) && !empty($exclude)) {
			$exfilter = '(&';
			$exlist = preg_split('/\W+/', $exclude);
			foreach ($exlist as $ex) $exfilter .= "(!(uid=$ex))";
			$exfilter .= ')';
			$filter[] = $exfilter;
		}
		if(!empty($free)) {
			$filter[] = "(|(givenName$cmp$pre$free$post)(eduPersonNickname$cmp$pre$free$post)(sn$cmp$pre$free$post)(title$cmp$pre$free$post)(ou$cmp$pre$free$post))";
			$filter_desc[] = 'like ' . $this->format_search_key($free);
		}
		if (count($filter) > 3) 
		{		
			$querystring = '(&'.join($filter, '').')';
			$querytext = (count($filter_desc)) ? 'people ' : '';
			$querytext .= join(' and ', $filter_desc);
			$query = array($querystring, $querytext);
			return ($query);
		}
		return false;
	}//}}}
	
	/** In some contexts (like the top of the printable photobook) a custom page title can be
	*   displayed.  This function figures out whether:
	*	a) a page title has been passed in the query, or
	*	b) the search being made is such that there's a logical title for the result set
	**/
	function determine_page_title()
	{
		if (isset($this->request['pagetitle']))
			return $this->request['pagetitle'];
		
		$fields = array('first_name','last_name','phone_number','email_address','building','major',
				'year','department','office','title');
		// Figure out which values are set
		$set = array();
		foreach ($fields as $field)
			if (isset($_REQUEST[$field])) $set[$field] = $_REQUEST[$field];
		
		// If just one is set, then we have a potential title
		if (count($set) == 1)
		{
			list($field,$value) = each($set);
			switch ($field)
			{
				case 'building':
				case 'department':
				case 'office':
					return $value;
					break;
				case 'major':
					if (isset($this->majors[$value]))
						return $this->majors[$value];
					else
						return $value;
					break;
				default:
					return '';
			}
		}
	}

	/** Perform an LDAP search based on the provided LDAP filter
	*/
	function get_search_results($querystring) //{{{
	{
		$attributes = array('dn','uid','ou','cn','sn','givenName','eduPersonNickname','displayName','mail','title',
			'eduPersonPrimaryAffiliation','officeBldg','studentPostOffice','officephone','spouseName',
			'address','ocPostalAddress', 'ocPhone','studentMajor','studentSpecialization',
                        'edupersonprimaryaffiliation',
                        'eduPersonAffiliation','studentStatus','alumclassyear',
			'eduPersonEntitlement','mobile');

		/*$attributes = array('dn','carlnetid','ou','cn','sn','givenName','eduPersonNickname','displayName','mail','title',
		*	'eduPersonPrimaryAffiliation','carlOfficeLocation','carlCampusPostalAddress','telephoneNumber','carlSpouse',
                *       'carlHideInfo',
		*	'homePostalAddress', 'carlStudentPermanentAddress', 'homePhone', 'carlMajor', 'carlConcentration',
                *       'eduPersonPrimaryAffiliation',
		*	'eduPersonAffiliation','carlStudentStatus','carlGraduationYear',
                *       'carlCohortYear','carlHomeEmail','carlFacultyLeaveTerm','carlHidePersonalInfo',
		*	'eduPersonEntitlement','mobile');
		*/

			$dir = new directory_service('ldap_luther');
			$dir->search_by_filter($first_name, $attributes);
                        pray($dir);
                        $dir->sort_records(array('sn','givenname'));
                        $entries = $dir->get_records();
                        return $entries;
	
//              $dir = new directory_service('ldap_carleton');
//		$dir = new directory_service('ldap_luther');
//		$dir->search_by_filter($querystring, $attributes);
//		$dir->sort_records(array('sn','givenname'));
//		$entries = $dir->get_records();
//		return $entries;
//               
        } //}}}
		
	/** Query the Telecommunications database for data relevant to the requested office or dept
	*/
	function get_telecomm_data($q)
	{
		$listing = array();
		if (isset($q['department']) && !empty($q['department']))
			$key = $q['department'];
		else if (isset($q['office']) && !empty($q['office']))
			$key = $q['office'];
		if (isset($key))
		{
			connectDB('telecommunications');
			$query = sprintf('FirstName LIKE "%1$s%%" OR LastName LIKE "%1$s%%"', mysql_real_escape_string(substr($key,0,15)));
			if ($key == 'all') $query = '1'; // Allows the pdf view to request all offices/depts
			$result = db_query('SELECT * FROM SDirEt WHERE PU LIKE "D" AND ('.$query.') order by LastName');
			if (mysql_num_rows($result)) {
				while ($entry = mysql_fetch_array ($result)) {
					if (trim($entry['FirstName']) <> '') {
						$listing[$key][$entry['LastName']] = $entry['Exten'];
					} else {
						$listing[$key][] = $entry['Exten'];
					}
				}
				ksort($listing);
			}
			connectDB(REASON_DB);
		}
		return $listing;
	}
	
	/** Generate a list of departments/offices that appear in the result set.  Used to generate links
	*   to department sites.
	**/
	function find_depts_in_result_set($results)
	{
		$depts = array();
		foreach ($results as $result)
		{
			if (isset($result['ou']))
			{
				foreach ($result['ou'] as $ou)
					$depts[$ou] = $ou;
			}
		}
		return $depts;
	}
	
	/** Find all of the Reason sites whose department matches one of those passed
	*   @param $depts array of department names
	**/
	function get_reason_sites($depts)
	{
		$sites = array();
		foreach ($depts as $dept)
			$queries[] = 'department = "'.$dept.'"';
		$query = '(' . join(' OR ', $queries) . ')';
		
		// find all the sites
		$es = new entity_selector();
		$es->description = 'Getting all live sites for search';
		$es->add_type( id_of( 'site' ) );
		$es->add_relation('site.site_state = "Live"');
		$es->add_relation($query);
		$results = $es->run_one();
		foreach ($results as $site)
		{		
			$url = ($site->get_value('base_url')) ? $site->get_value('base_url') : $site->get_value('url');
			$result = array(
			'name' => $site->get_value('name'),
			'url' => $url,
			'desc' => $site->get_value('description'),
			'dept' => $site->get_value('department'),
			'dept_code' => strtolower($site->get_value('short_department_name')),
			'keywords' => $site->get_value('keywords'),
			);	
			$sites[$result['dept']] = $result;
		}
		return $sites;
	}
		
	/** Load the lists of departments, majors, buildings, etc from the cache; rebuild if needed
	*/
	function get_menu_data()
	{
                // Load external list of majors
		if (empty($this->majors))
		{
			//include(WEB_PATH . 'campus/directory/majors.php');
			$this->majors =& $majors;
		}
		if ($this->menu_data) return $this->menu_data;
		$cache = new ReasonObjectCache('campus_directory_menu_data', 86400);
		$this->menu_data = $cache->fetch();
		if (!$this->menu_data)
		{
			$this->menu_data = $this->rebuild_menu_data();
			$cache->set($this->menu_data);
		}
	}
	
	/** Reconstruct the lists of departments, majors, buildings, etc from the LDAP data.
	**/
	function rebuild_menu_data()
	{
		//$dir = new directory_service('ldap_carleton');
		$dir = new directory_service('ldap_luther');
		// Get the full set of possible academic depts (not all have people)
		$dir->set_search_params('ldap_luther',array('base_dn' => 'dc=luther,dc=edu'));
		//$dir->set_search_params('ldap_carleton',array('base_dn' => 'dc=carleton,dc=edu'));
		$dir->search_by_filter('(businessCategory=ACADEMIC)', array('ou','description'));
		$result = $dir->get_records();
		foreach ($result as $dept)
			$acad_all[$dept['description'][0]] = $dept['ou'][0];
		asort($acad_all);
		$acad_all_by_name = array_flip($acad_all);
		
		//$dir->set_search_params('ldap_carleton',array('base_dn' => 'ou=people,dc=carleton,dc=edu'));
		$dir->set_search_params('ldap_luther',array('base_dn' => 'ou=people,dc=luther,dc=edu'));
		
		// Academic Departments
		$filter = '(& (objectClass=carlPerson) (eduPersonAffiliation=faculty) (!(eduPersonAffiliation=staff)) (ou = *))';
		if ($dir->search_by_filter($filter, array('ou','officelocation')))
		{
			$faculty = $dir->get_records();
			$menu_data['acad'] = $this->parse_attribute_data($faculty,'ou');
			// remove any that aren't in the list of all acad depts (e.g. Library)
			foreach ($menu_data['acad'] as $key => $val)
				if (!isset($acad_all_by_name[$key])) unset ($menu_data['acad'][$key]);
		}

		// Administrative Offices
		$filter = '(& (objectClass=eduperson) (edupersonaffiliation=staff) (ou = *))';
		if ($dir->search_by_filter($filter, array('ou','officelocation')))
		{
			$staff = $dir->get_records();
			$menu_data['admin'] = $this->parse_attribute_data($staff,'ou');
			// remove any that are in the list of all acad depts
			foreach ($menu_data['admin'] as $key => $val)
				if (isset($acad_all_by_name[$key])) unset ($menu_data['admin'][$key]);
			unset ($menu_data['admin']['Null temporary OU']);
		}
		
		// Majors
		$filter = '(& (objectClass=carlPerson) (eduPersonPrimaryAffiliation=student))';
		if ($dir->search_by_filter($filter, array('ou','carlstudentcampusaddress','studentMajor','carlconcentration')))
		{
			$students = $dir->get_records();
			$values = $counts = array();
			foreach ($students as $entry)
			{
				if (isset($entry['studentMajor']))
					$values = array_merge($values,$entry['studentMajor']);
				if (isset($entry['carlconcentration']))
					$values = array_merge($values,$entry['carlconcentration']);
			}
			foreach ($values as $value)
			{
				$display = (isset($this->majors[$value])) ? $this->majors[$value] : $value;
				if (isset($counts[$display][$value]))
					$counts[$display][$value]++;
				else
					$counts[$display][$value] = 1;
			}
			
			foreach ($counts as $display => $codes)
			{
				$count = 0;
				$parts = array();
				foreach ($codes as $code => $value)
				{
					$count += $value;
					$parts[] = $code;
				}
				$menu_data['majors'][join(',',$parts)] = $display . ' ('.$count.')';
			}
		
			asort($menu_data['majors']);
		}
		
		// Buildings
		$result = array_merge($faculty, $staff, $students);
		foreach ($result as $key => $val)
		{
			if (isset($val['carlstudentcampusaddress']))
				$result[$key]['location'] = $val['carlstudentcampusaddress'];
			else if (isset($val['officelocation']))
				$result[$key]['location'] = $val['officelocation'];
			else
				continue;
			
			$result[$key]['location'] = preg_replace('/ (\d|B\d|B-|G\d|G-|LL|UL).*/','',$result[$key]['location']);
		}
		$menu_data['buildings'] = $this->parse_attribute_data($result,'location');
		
		
		return $menu_data;
	}
	
	function parse_attribute_data($entries, $attr, $add_counts = true)
	{
		$values = $counts = array();
		foreach ($entries as $entry)
		{
			if (isset($entry[$attr]))
				$values = array_merge($values,$entry[$attr]);
		}
		foreach ($values as $value)
		{
			if (isset($counts[$value]))
				$counts[$value]++;
			else
				$counts[$value] = 1;
		}
		
		foreach ($counts as $value => $count)
		{
			if ($add_counts)
				$return[$value] = $value . ' ('.$count.')';
			else
				$return[$value] = $value;
		}
		
		asort($return);
		return $return;
	}

	/** Generate a link that will request a PDF version of the current result set
	*/
	function build_printable_link()
	{
		$params = array();
		// look at all the form vars and URL params to create a new set of URL params
		foreach ($this->elements as $name => $val)
		{
			if ($this->form->get_value($name))
				$params[$name] = $this->urlize($name, $this->form->get_value($name));
		}
		foreach ($this->cleanup_rules as $name => $val)
		{
			if (isset($this->request[$name]))
				$params[$name] = $this->urlize($name, $this->request[$name]);
		}
		
		$params['view'] = 'view=pdf';
		$param_list = join('&', $params);
		return '<p class="printable"><a class="printableLink" href="'.$this->search_url .'?'.$param_list.'">Printable Version</a></p>';
	}
	
	/** Format a name/value pair for use in a GET URL string, properly handling array values
	**/
	function urlize($name, $val)
	{
		if (is_array($val))
		{
			$parts = array();
			foreach ($val as $part)
				$parts[] = $name . '[]=' . urlencode($part);
			return join('&', $parts);
		} else {
			return $name . '=' . urlencode($val);	
		}
	}
	
	/** The PDF generating functions below were designed to support production of the printed
	*   campus directory.  They are complicated and kludgy.  However, they are still in use in
	*   various contexts, so be kind to them.
	**/

	/** Do the basic PDFLib setup, define available fonts, set document metadata
	*   @param $filename The name you want to save the temp file as
	**/
	function &pdf_start($filename='directory.pdf')
	{
		$pdf = pdf_new();
		
		pdf_set_parameter($pdf , 'licensefile', '/usr/local/wsg/httpd/conf/pdflib_licensekeys.txt');
		pdf_begin_document($pdf , '/tmp/'.$filename, '');
		$this->pdf_fonts['helv'] = pdf_load_font($pdf, 'Helvetica', 'host', '');
		$this->pdf_fonts['helvb'] = pdf_load_font($pdf, 'Helvetica-Bold', 'host', '');
		$this->pdf_fonts['helvi'] = pdf_load_font($pdf, 'Helvetica-Oblique', 'host', '');
		//pdf_set_info($pdf, 'Author', 'Carleton College');
		pdf_set_info($pdf, 'Author', 'Luther College');
		pdf_set_info($pdf, 'Title', 'Campus Directory');	
		return $pdf;
	}

	/** Common functions for closing out a directory page. Prints the standard disclaimer and page
	*   number (if appropriate).
	*   @param $f_pdf The PDFLib document reference
	*   @param $page The current page number
	**/
	function pdf_finish_page(&$f_pdf, &$page, $stamp=true) {
		if ($stamp)
		{
			if ($this->pdf_fonts['helvb']) pdf_setfont($f_pdf, $this->pdf_fonts['helvb'], 12);
			pdf_setcolor($f_pdf, 'both', 'gray', .6, 0, 0, 0);
			//pdf_show_xy($f_pdf,'Printed '.date('M j, Y').'. For current directory information go to www.carleton.edu/campus/directory', 35, 765);
			pdf_show_xy($f_pdf,'Printed '.date('M j, Y').'. For current directory information go to www.luther.edu/directory', 35, 765);
			pdf_setcolor($f_pdf, 'both', 'gray', 0, 0, 0, 0);
		}
		if ($page)
		{
			if ($this->pdf_fonts['helv']) pdf_setfont($f_pdf, $this->pdf_fonts['helv'], 9);
			pdf_show_xy($f_pdf,$page,304, 35);
			$page++;
		}
		pdf_end_page($f_pdf);
	}
	
	/** Generate a PDF photobook from the provided result set.
	*   @param $results array of search results
	*   @param $pagetitle optional text to print at the top of the page
	**/
	function pdf_export_photobook($results, $pagetitle='')
	{
		$page = (isset($this->request['page'])) ? $this->request['page'] : '';
		$pagetitle = $this->determine_page_title();

		include_once('ldapimage.php');
		$idb = new imageDB;
		$pdf = $this->pdf_start('photobook.pdf');
		pdf_begin_page($pdf, 612, 792);
		
		// Title page for full faculty/staff photobook
		if ($this->form->get_value('search_for') == 'facstaff') {
			if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 24);
			pdf_show_xy($pdf,'Faculty and Staff',150, 300);
			pdf_end_page($pdf);
			pdf_begin_page($pdf, 612, 792);
		}

		if ($pagetitle) {
			if ($this->pdf_fonts['helvb']) pdf_setfont($pdf, $this->pdf_fonts['helvb'], 18);
			pdf_show_xy($pdf,$pagetitle,55, 750);
		}
		$xpos = 50;
		$ypos = 630;
		$xcol = $xpos;
		pdf_set_text_pos($pdf, $xpos, $ypos);

		$colcount = -1;
		foreach ($results as $data)
		{
			$colcount++;
			if ($colcount > 4)
			{ 
				$colcount = 0;
				$ypos = $ypos - 135;
			}
			$xcol = $xpos + ($colcount * 100);
			
			// If we're at the bottom of the page, start a new page
			if ($ypos < 50)
			{
				$this->pdf_finish_page($pdf,$page,false);
				
				if ($this->form->get_value('search_for') == 'facstaff') { $this->pdf_detail_page($pdf, $namelist); }
				
				$namelist = array();
				pdf_begin_page($pdf, 612, 792);
				$ypos = 630;
			}
			
			$photostring = $idb->get_image($data['uid'][0]);
			$pvf = PDF_create_pvf($pdf , 'temp_image' , $photostring , '');
			$pim = pdf_open_image_file ( $pdf, 'jpeg' , 'temp_image' , '', 0 );
			$pvf = PDF_delete_pvf($pdf , 'temp_image');
		
			pdf_fit_image($pdf, $pim, $xcol, $ypos, 'boxsize {100 100} fitmethod meet position {50 50}');
			pdf_close_image($pdf, $pim);	
		
			$name = $this->format_name($data);
			if (isset($data['title'])) $namelist[$name] = $data['title'][0];
		
			if ($data['edupersonprimaryaffiliation'][0] == 'student' && isset($data['alumclassyear'])) {
				$name .= ', '. $data['alumclassyear'][0];
			}
			if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 10);
			pdf_fit_textline($pdf, $name, $xcol + 5, $ypos - 10, 'boxsize {90 15} fitmethod auto position {50 0}');		
		}
		pdf_end_page($pdf);
		if ($this->form->get_value('search_for') == 'facstaff') { $this->pdf_detail_page($pdf,$namelist); }

		pdf_close($pdf);
		
		while (ob_get_level()) ob_end_clean(); // discard other page output
		header('Content-type: application/pdf');
		header('Content-disposition: inline; filename=directory.pdf');
		header('Content-length: ' . filesize('/tmp/photobook.pdf'));
		readfile('/tmp/photobook.pdf');
		pdf_delete($pdf);
		exit; // End page processing
	}
	
	/** Called by pdf_export_photobook -- generates the alternate page that lists the name
	*   and title of the people show on the photo page.
	*   @param $f_pdf The PDFLib document reference
	*   @param $namelist array of names to print
	**/
	function pdf_detail_page(&$f_pdf, $namelist) {
		// Start a page for the expanded info
		pdf_begin_page($f_pdf, 612, 792);
		$xpos = 75;
		$ypos = 725;
		for ($i = 0; $i < 6; $i++) {
			$slice = array_slice($namelist, $i*5, 5);
			$text = '';
			foreach($slice as $name => $title) {
				$text .= "<fontname=Helvetica-Bold encoding=host>$name   <fontname=Helvetica encoding=host>$title\n";
			}
			$textflow = PDF_create_textflow($f_pdf, $text, 'leftindent=30 parindent=-30 fontsize=12 leading=14 alignment=justify'); 
			PDF_fit_textflow($f_pdf, $textflow, $xpos, $ypos, $xpos+490, $ypos-100, ''); 
			PDF_delete_textflow($f_pdf, $textflow); 
			$ypos = $ypos - 135;
		}
		pdf_end_page($f_pdf);
	}

	/** Generate a printed directory listing from the provided result set.
	*   @param $results array of search results
	**/
	function pdf_export_list($results)
	{
		$sort = (isset($this->request['sort'])) ? $this->request['sort'] : '';
		$page = (isset($this->request['page'])) ? $this->request['page'] : '';
		
		// If we're sorting by department, we need a separate entry in each
		// department for people with multiple roles.
		if ($sort == 'dept')
		{
			foreach ($results as $data)
			{
				if (isset($data['ou']))
				{
					if (count($data['ou']) == 1)
					{
						$data['dept'] = $data['ou'][0];
						$results2[$data['ou'][0].$data['sn'][0].$data['givenname'][0]] = $data;
					} else {
						foreach ($data['ou'] as $ou)
						{
							$data['dept'] = $ou;
							$results2[$ou.$data['sn'][0].$data['givenname'][0]] = $data;
						}
					}
				}
			}
			if (isset($results2))
			{
				ksort($results2);
				$results = $results2;
			}
		}
		
		
		// Get all the directory entries from the Telecomm database
		$telecom = $this->get_telecomm_data(array('office'=>'all'));
		foreach ($telecom['all'] as $entry => $val)
			if (!is_numeric($entry))
				$listing[substr($entry,0,22)][] = $val;
		ksort($listing);
		$pdf = $this->pdf_start('directory.pdf');
		pdf_begin_page($pdf, 612, 792);

		if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 10);
		pdf_set_value($pdf, 'leading', 12);
		pdf_set_value($pdf, 'textrendering', 0);
		$xpos = 75;
		$ypos = 760;
		$xcol = $xpos;
		pdf_set_text_pos($pdf, $xpos, $ypos);
		
		$last_dept='xxxx';
		foreach ($results as $data)
		{
			$name = $this->format_name($data);
			$phone = $this->format_phone($data);
			$phone = str_replace('+1 507 222', '222', $phone);
			$phone = str_replace('+1 507 645', '645', $phone);
			$phone = str_replace('+1 507 663', '663', $phone);
			$phone = str_replace('+1 507 664', '664', $phone);
			$phone = str_replace('x', '', $phone);
	   
			if ($data['edupersonprimaryaffiliation'][0] == 'student') 
			{
				$name .= ' '. $data['alumclassyear'][0];
				$ypos = pdf_get_value($pdf, 'texty', 0);
				$ypos = $ypos - 15;
				if ($ypos < 45)
				{
					$xcol = $xcol + 175;
					$ypos = 735;
					if ($xcol > 475)
					{
						if ($this->pdf_fonts['helvb']) pdf_setfont($pdf, $this->pdf_fonts['helvb'], 11);
						pdf_setcolor($pdf, 'both', 'gray', .6, 0, 0, 0);
						//pdf_show_xy($pdf,'Printed '.date('F j, Y').'. For current directory information go to www.carleton.edu/campus/directory',40, 765);
						pdf_show_xy($pdf,'Printed '.date('F j, Y').'. For current directory information go to www.luther.edu/directory',40, 765);
						pdf_setcolor($pdf, 'both', 'gray', 0, 0, 0, 0);
						pdf_end_page($pdf);
						pdf_begin_page($pdf, 612, 792);
						$xcol = 75;
					}
				}
				$xpos = $xcol;
				if ($this->pdf_fonts['helvb']) pdf_setfont($pdf, $this->pdf_fonts['helvb'], 9);
				if (strlen($phone) == 4) pdf_show_xy($pdf,$phone,($xpos - 25), $ypos);
				$namewidth = pdf_stringwidth($pdf,$name,$this->pdf_fonts['helvb'], 9);
				if ($namewidth > 140) {
					pdf_set_value($pdf, 'horizscaling', (140/$namewidth)*100);
				}	
				pdf_show_xy($pdf,$name,$xpos, $ypos);
				pdf_set_value($pdf, 'horizscaling', 100);
				if (strlen($phone) > 4) pdf_continue_text($pdf, $phone);
				if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 8.5);
				if ($status = $this->format_status($data))
				{
					if ($this->pdf_fonts['helvi']) pdf_setfont($pdf, $this->pdf_fonts['helvi'], 8.5);
					pdf_continue_text($pdf,$status);
					if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 8.5);
				}
				if (isset($data['address']))
				{
					$home = $this->format_postal_address($data['address'][0], false);
					foreach ($home as $line)
						pdf_continue_text($pdf, $line);
				}
				if (isset($data['carlstudentpermanentaddress']))
				{
					if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 7.5);
					$home = $this->format_postal_address($data['carlstudentpermanentaddress'][0], false);
					foreach ($home as $line)
						pdf_continue_text($pdf, $line);
				}	
			} else { // Not a student
				$margins['bottom'] = ($sort == 'dept') ? 75 : 115;
				$margins['top'] = 735;
				$ou = (isset($data['ou'])) ? $data['ou'] : array();
				if (in_array('No Department', $ou)) continue;
				$ypos = pdf_get_value($pdf, 'texty', 0);
				$ypos = $ypos - 15;
				if ($ypos < $margins['bottom']) {
					$xcol = $xcol + 175;
					$ypos = $margins['top'];
					if ($xcol > 475) {
						$this->pdf_finish_page($pdf, $page);
						pdf_begin_page($pdf, 612, 792);
						$xcol = 75;
					}
				}
				$xpos = $xcol;
				if (($sort == 'dept') && (in_array($last_dept, $ou) === FALSE)) 
				{
					$last_dept = $data['dept'];
					$ext = $listing[substr($last_dept,0,22)][0];
					// skip a line, unless at the top of the page
					$ypos = ($ypos == $margins['top']) ? $ypos : $ypos - 5;
					if ($this->pdf_fonts['helvb']) pdf_setfont($pdf, $this->pdf_fonts['helvb'], 9);
					pdf_show_xy($pdf,$ext,($xpos - 25), $ypos);
					// squeeze names that are too wide for the column
					$namewidth = pdf_stringwidth($pdf, strtoupper($last_dept),$this->pdf_fonts['helvb'], 9);
					if ($namewidth > 140) {
						pdf_set_value($pdf, 'horizscaling', (140/$namewidth)*100);
					}	
					pdf_show_xy($pdf, strtoupper($last_dept), $xpos, ($ypos));
					pdf_set_value($pdf, 'horizscaling', 100);
					$ypos -= 10;
				}
				if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 9);
				if (isset($phone_prefix))
				{
					pdf_show_xy($pdf,$phone_prefix,($xpos - 28), $ypos);
					pdf_show_xy($pdf,$phone,($xpos - 25), $ypos - 10);
				} else {
					pdf_show_xy($pdf,$phone,($xpos - 25), $ypos);
				}
				// If the phone number is extra long, put the name under it.
				if (strlen($phone) > 5) { $ypos -= 10; }
				$namefont = ($sort == 'dept') ? $this->pdf_fonts['helv'] : $this->pdf_fonts['helvb'];
				if ($namefont) pdf_setfont($pdf, $namefont, 9);
				$namewidth = pdf_stringwidth($pdf, $name, $namefont, 9);
				if ($namewidth > 140)
				{
					pdf_set_value($pdf, 'horizscaling', (140/$namewidth)*100);
				}	
				pdf_show_xy($pdf,$name,$xpos, $ypos);
				pdf_set_value($pdf, 'horizscaling', 100);
				if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 8.5);

				if (($sort != 'dept') && (isset($data['mail']))) pdf_continue_text($pdf, $data['mail'][0]);
				if (pdf_stringwidth($pdf,$data['title'][0], $this->pdf_fonts['helv'], 8.5) > 140)
				{
					$title = split(' ',$data['title'][0]);
					$current = array();
					while (count($title)) {
						array_push($current, array_shift($title));
						if (pdf_stringwidth($pdf,join(' ',$current), $this->pdf_fonts['helv'], 8.5) > 140)
						{
							array_unshift($title, array_pop($current));
							pdf_continue_text($pdf, join(' ',$current));
							$current = array();
						}
					}
					if (count($current)) pdf_continue_text($pdf, join(' ',$current));
				} else {
					if (isset($data['title'])) pdf_continue_text($pdf, $data['title'][0]);
				}


				if (isset($data['officelocation']))
				{
					if (isset($data['campuspostaladdress']))
						$address = $data['officelocation'][0] . '('. $data['campuspostaladdress'][0] .')';
					else
						$address = $data['officelocation'][0];
					$address = str_replace('Language and Dining Center', 'LDC', $address);
					$address = str_replace('Center for Math & Computing', 'CMC', $address);
					$address = str_replace('Music & Drama Center', 'Music & Drama', $address);
					$address = str_replace('Observatory', '', $address);
					if ($sort != 'dept') pdf_continue_text($pdf, $address);
				}
				if ($this->pdf_fonts['helv']) pdf_setfont($pdf, $this->pdf_fonts['helv'], 7.5);
				if (isset($data['address']))
				{
					$address = $this->format_postal_address($data['address'][0], false);
					foreach ($address as $line)
						if ($line <> 'Northfield MN 55057') pdf_continue_text($pdf, $line);
				}
				if (isset($data['homephone'])) pdf_continue_text($pdf, str_replace('+1 ', '', $data['homephone'][0]));
				if ($this->pdf_fonts['helvi']) pdf_setfont($pdf, $this->pdf_fonts['helvi'], 7.5);
				if (isset($data['spousename'])) pdf_continue_text($pdf, $data['spousename'][0]);
			}	
		}  

		$this->pdf_finish_page($pdf, $page);
		pdf_close($pdf);		
		while (ob_get_level()) ob_end_clean(); // discard other page output
		header('Content-type: application/pdf');
		header('Content-disposition: inline; filename=directory.pdf');
		header('Content-length: ' . filesize('/tmp/directory.pdf'));
		readfile('/tmp/directory.pdf');
		pdf_delete($pdf);
		exit; // End page processing
	}
}




?>
