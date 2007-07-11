<?php

/////////////////////////////////////////////////
// This class transforms an appropriately-constructed xml file into an html form.
//
// For example:
//
//	<form email="fillmorn@carleton.edu" nextpage="http://www.carleton.edu" submit="Enter" reset="Clear">
//		<input id="first_name" value="Nathanael" size="20" maxlength="40" label="First name:" required="required" />
//		<hidden id="last_name" value="Fillmore" />
//		<textarea id="message" value="Dear Mr. President, You look like a monkey. Sincerely, Nathanael Fillmore"
//		          cols="40" rows="10" label="Message for the President:" required="required" />
//		<radiogroup id="affiliation" label="Are you a student, faculty, or staff?" required="required">
//			<radio selected="selected" value="student" label="Student" />
//			<radio value="faculty" label="Faculty" />
//			<radio value="staff" label="Staff" />
//		</radiogroup>
//		<checkboxgroup id="complaint" label="Which of these describes your complaint?" required="required">
//			<checkbox selected="selected" label="You're arrogant, Mr. President" value="arrogance" />
//			<checkbox selected="selected" label="You're a bad speaker, Mr. President" value="badspeaker" />
//			<checkbox label="Those monkey ears of your are just too damn handsome, Mr. President" value="handsome" />
//		</checkboxgroup>
//		<optiongroup id="replacement" label="Who's your favorite replacement?" size="3" required="required">
//			<option value="john kerry" label="John Kerry" />
//			<option selected="selected" value="howard dean" label="Howard Dean" />
//		</optiongroup>
//	</form>
//
// Created 26/Nov/2003, Nathanael Fillmore
/////////////////////////////////////////////////

// 4/10/2006 added ability to save form data to a database  - nwhite

require_once( THOR_INC .'disco_thor.php');
include_once(TYR_INC.'tyr.php');
		
class Thor
{
	var $_nextpage;
	var $_email;
	var $_xml;
	var $_html;
	var $_d;
	var $_db_conn;
	var $_table_name;
	var $_show_submitted_data;
	var $extra_fields = array('submitted_by', 'submitter_ip'); // extra fields that aren't represented in thor

	function Thor($xml, $email, $nextpage, $db_conn = '', $table_name = '') {
		$this->_xml = $xml;
		$this->_email = $email;
		$this->_nextpage = $nextpage;
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;

		$this->_build_form();
	}

	function set_db_conn($db_conn, $table_name)
	{
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;
	}
	
	function get_html() {
		$result = $this->_d->run();
		if ($this->_d->finished)
		{
			if ((!empty($this->_db_conn)) && (!empty($this->_table_name))) $this->save_to_db();
			
			if ($this->_show_submitted_data) 
			{
				if (!session_id()) session_start();
				$_SESSION['form_confirm'] = ($this->_d->get_values_array(false, false));
			}
			
			if (!empty($this->_email)) $this->_d->process_email(); // Tyr e-mail processing includes redirect so execute after database save
			else header( 'Location: ' . $this->_nextpage );
		}
	}
	
	function save_to_db()
	{
		//check if table exists
		if ($this->_check_table_exists() == false)
		{
			//create table
			$this->_create_db_table();
		}
		
		$db_save_array = conditional_stripslashes($_REQUEST);
		if (isset($db_save_array['transform']))
		{
			foreach ($db_save_array['transform'] as $k=>$v) //process checkbox transformations
			{
				foreach ($v as $k2=>$v2)
				{
					$db_save_array[$v2] = isset($db_save_array[$k][$k2]) ? $db_save_array[$k][$k2] : '';
				}
				unset ($db_save_array['transform'][$k]);
				unset ($db_save_array[$k]);
			}
		}
		foreach ($db_save_array as $k => $v)
		{
			if (substr($k, 0, 3) != "id_")
			{
				if (in_array($k, $this->extra_fields) == false) unset ($db_save_array[$k]);
			}
		}
		
		if (count($db_save_array) > 0)
		{
			connectDB($this->_db_conn);
			$GLOBALS['sqler']->mode = 'get_query';
			$query = $GLOBALS['sqler']->insert( $this->_table_name, $db_save_array );
			$result = mysql_query($query);
			if (mysql_error()) 
			{
				trigger_error('There was a problem saving thor form data to the table ' . $this->_table_name . ' at ' . get_current_url());
				echo '<h3>Error Saving Data</h3>';
				echo '<p>The data you submitted has not been saved or e-mailed. Please try the form again later. The Web Services group has been notified of the error and will resolve the problem as quickly as possible.</p>';
				$die = true;
			}
			else $die = false;
			$GLOBALS['sqler']->mode = '';
			connectDB(REASON_DB); // reconnect to default DB
			if ($die) die;
		}
	}
	
	function _check_table_exists()
	{
		$ret = true;
		connectDB($this->_db_conn);
  		$q = 'check table ' . $this->_table_name . ' fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  		$res = mysql_query($q);
  		$results = mysql_fetch_assoc($res);
  		if (strstr($results['Msg_text'],"doesn't exist") ) 
  		{
  			$ret = false;
  		}
  		connectDB(REASON_DB); // reconnect to default DB
  		return $ret;
	}
	
	function _create_db_table()
	{
		$db_structure = $this->_build_db_structure();
		
		connectDB($this->_db_conn); // connecting here so that mysql_real_escape_string() uses the right charset for the connection
		
		$q = 'CREATE TABLE ' . $this->_table_name . '(`id` int(11) NOT NULL AUTO_INCREMENT, ';
		//$q .= '`formkey` tinytext NOT NULL , ';
		foreach ($db_structure as $k=>$v)
		{
			switch ($v['type']) {
			case 'tinytext':
   				$q .= '`'.$k.'` tinytext NOT NULL , ';
   				break;
			case 'enum':
   				$q .= '`'.$k.'` enum(';
   				foreach ($v['options'] as $option)
   				{
   					$q .= "'" . mysql_real_escape_string($option) . "',";
   				}
   				$q = substr( $q, 0, -1 ); // trim trailing comma
   				$q .= ') NOT NULL , ';
   				break;
			case 'text':
   				$q .= '`'.$k.'` text NOT NULL , ';
   				break;
			}
		}
		$q .= '`submitted_by` tinytext NOT NULL , ';
		$q .= '`submitter_ip` tinytext NOT NULL , ';
		$q .= '`date_created` timestamp NOT NULL , ';
		$q .= 'PRIMARY KEY(`id`)) TYPE = MYISAM;';
		$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
		connectDB(REASON_DB); // reconnect to default DB
	}
	
	function _build_db_structure() {
		$xmldoc = domxml_open_mem($this->_xml); // get dom object
		$xpath = xpath_new_context($xmldoc); // init xpath
		$db_structure = array();

		// II. Transform the form's elements
		$xpresult = xpath_eval($xpath, '/form[1]/*');
		
		foreach ($xpresult->nodeset as $node) {
			if ($node->tagname == 'input') {
				$db_structure[$node->get_attribute('id')]['type'] = 'tinytext';
			}
			elseif ($node->tagname == 'textarea') {
				$db_structure[$node->get_attribute('id')]['type'] = 'text';
			}
			elseif ($node->tagname == 'radiogroup') {
				$db_structure[$node->get_attribute('id')]['type'] = 'enum';
				$xpresult2 = xpath_eval($xpath, "//*[@id='" . $node->get_attribute('id') . "']/radio");
				foreach ($xpresult2->nodeset as $node2) {
					$db_structure[$node->get_attribute('id')]['options'][] = $node2->get_attribute('value');
				}
			}
			elseif ($node->tagname == 'checkboxgroup') {
				$xpresult2 = xpath_eval($xpath, "//*[@id='" . $node->get_attribute('id') . "']/checkbox");
				foreach ($xpresult2->nodeset as $node2) {
					$db_structure[$node2->get_attribute('id')]['type'] = 'tinytext';
				}
			}
			elseif ($node->tagname == 'optiongroup') {
				$db_structure[$node->get_attribute('id')]['type'] = 'enum';
				$xpresult2 = xpath_eval($xpath, "//*[@id='" . $node->get_attribute('id') . "']/option");
				foreach ($xpresult2->nodeset as $node2) {
					$db_structure[$node->get_attribute('id')]['options'][] = $node2->get_attribute('value');
				}
			}
			elseif ($node->tagname == 'hidden') {
			$db_structure[$node->get_attribute('id')]['type'] = 'tinytext';
			}
		}
		return $db_structure;
	}

	function _build_form() {

		$this->_d = new DiscoThor;

		$xmldoc = domxml_open_mem($this->_xml); // get dom object
		$xpath = xpath_new_context($xmldoc); // init xpath
		$this->_html = '';

		// II. Transform the form's elements
		$xpresult = xpath_eval($xpath, '/form[1]/*');
		foreach ($xpresult->nodeset as $node) {
			if ($node->tagname == 'input') {
				$this->_transform_input($node);
			}
			elseif ($node->tagname == 'textarea') {
				$this->_transform_textarea($node);
			}
			elseif ($node->tagname == 'radiogroup') {
				$this->_transform_radiogroup($node, $xpath);
			}
			elseif ($node->tagname == 'checkboxgroup') {
				$this->_transform_checkboxgroup($node, $xpath);
			}
			elseif ($node->tagname == 'optiongroup') {
				$this->_transform_optiongroup($node, $xpath);
			}
			elseif ($node->tagname == 'hidden') {
				$this->_transform_hidden($node);
			}
			elseif ($node->tagname == 'comment') {
				$this->_transform_comment($node);
			}
		}

		// II. Transform the form's submit and reset buttons
		$xpresult = xpath_eval($xpath, '/form[1]');			
		$this->_transform_submit($xpresult->nodeset[0]);


		// IV. Transform the form's meta info
		if (!empty($this->_email)) $this->_transform_email();
		$this->_transform_nextpage();
	}


	function _transform_input($element) {
		$id = $element->get_attribute('id');
		$args = Array('size' => ($element->get_attribute('size') ? $element->get_attribute('size') : 30),
					  'maxlength' => $element->get_attribute('maxlength'),
					  'display_name' => $element->get_attribute('label'),
					  'default' => $element->get_attribute('value'));

		$this->_d->add_element($id, 'text', $args);

		if ( $element->get_attribute('required') != '' )
			$this->_d->add_required($id);
	}

	function _transform_hidden($element) {
		$id = $element->get_attribute('id');

		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $element->get_attribute('value'));
		$this->_d->set_display_name($id, '(hidden field) ' . $element->get_attribute('label'));

		if ( $element->get_attribute('required') != '' )
			$this->_d->add_required($id);
	}
 
	function _transform_comment($element) {
		$id = $element->get_attribute('id');

		$args = Array('text' => $element->get_content());
		$this->_d->add_element($id, 'comment', $args);
	}
 
	function _transform_textarea($element) {
		$id = $element->get_attribute('id');
		$args = Array('rows' => ($element->get_attribute('rows') ? $element->get_attribute('rows') : 6),
					  'cols' => ($element->get_attribute('cols') ? $element->get_attribute('cols') : 40),
					  'display_name' => $element->get_attribute('label'),
					  'default' => $element->get_attribute('value'));

		$this->_d->add_element($id, 'textarea', $args);

		if ( $element->get_attribute('required') != '' )
			$this->_d->add_required($id);
	}

	function _transform_radiogroup($element, $xpath) {
		$id = $element->get_attribute('id');
		$args = Array( 'options' => Array(),
					   'display_name' => $element->get_attribute('label'),
					   'default' => '' );

		$xpresult = xpath_eval($xpath, "//*[@id='" . $element->get_attribute('id') . "']/radio");
		foreach ($xpresult->nodeset as $node) {
			$args['options'][$node->get_attribute('value')] = $node->get_attribute('label');

			if ( $node->get_attribute('selected') != '' )
				$args['default'] = $node->get_attribute('value');
		}

		$this->_d->add_element($id, 'radio_no_sort', $args);

		if ( $element->get_attribute('required') != '' )
			$this->_d->add_required($id);
	}

	function _transform_checkboxgroup($element, $xpath) {
		$id = $element->get_attribute('id');
		$args = Array('options' => Array(),
					  'display_name' => $element->get_attribute('label'),
					  'default' => Array());

		$xpresult = xpath_eval($xpath, "//*[@id='" . $element->get_attribute('id') . "']/checkbox");
		$index = 0;
		foreach ($xpresult->nodeset as $node) {
			$args['options'][$node->get_attribute('value')] = $node->get_attribute('label');
			$id2 = $node->get_attribute('id');
			if ( $node->get_attribute('selected') )
				$args['default'] []= $node->get_attribute('value');
			$this->_d->add_element('transform['.$id.']['.$index.']', 'hidden');
			$this->_d->set_value('transform['.$id.']['.$index.']', $id2);
			$index++;
		}

		$this->_d->add_element($id, 'checkboxgroup_no_sort', $args);

		if ( $element->get_attribute('required') != '' )
			$this->_d->add_required($id);
	}

	function _transform_optiongroup($element, $xpath) {
		$id = $element->get_attribute('id');
		$args = Array('options' => Array(),
					  'multiple' => $element->get_attribute('multiple') ? true : false,
					  'size' => $element->get_attribute('size') > 1 ? $element->get_attribute('size') : 1,
					  'default' => Array(),
					  'display_name' => $element->get_attribute('label'));

		$xpresult = xpath_eval($xpath, "//*[@id='" . $element->get_attribute('id') . "']/option");
		foreach ($xpresult->nodeset as $node) {
			$args['options'][$node->get_attribute('value')] = $node->get_attribute('label');
				
			if ( !$element->get_attribute('multiple') ) {
				if ( $node->get_attribute('selected') )
					$args['default'] = $node->get_attribute('value');
			}
			else {
				if ( $node->get_attribute('selected') )
					$args['default'] []= $node->get_attribute('value');
			}
		}

		$this->_d->add_element($id, 'select_no_sort', $args);

		if ( $element->get_attribute('required') != '' )
			$this->_d->add_required($id);
	}

	function _transform_email() {
		$id = 'messages[0][to]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $this->_email);
		$this->_d->add_required($id);
	}

	function _transform_nextpage() {
		$id = 'messages[all][next_page]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $this->_nextpage);
		$this->_d->add_required($id);
	}

	function _transform_submit($element) {
		$this->_d->actions = Array( 'submit' => $element->get_attribute('submit'),
									'reset' => $element->get_attribute('reset') );

		// 			$submit = htmlspecialchars($element->get_attribute('submit'));

		// 			$reset = htmlspecialchars($element->get_attribute('reset'));
	}
	
	function set_form_title($form_title) {
		$id = 'messages[all][form_title]';
		$this->_d->add_element($id, 'hidden');
		$this->_d->set_value($id, $form_title);
	}
	
	function set_show_submitted_data($show) {
		$this->_show_submitted_data = $show;
	//	$id = 'messages[all][show_submitted_data]';
	//	$this->_d->add_element($id, 'hidden');
	//	$this->_d->set_value($id, $show);
	}
	
	function set_submitted_by($netID) {
		$this->_d->add_element('submitted_by', 'hidden');
		$this->_d->set_value('submitted_by', $netID);
	}
	
	function set_submitter_ip($ip) {
		$this->_d->add_element('submitter_ip', 'hidden');
		$this->_d->set_value('submitter_ip', $ip);
	}
	
	function set_value($element, $value)
	{
		$this->_d->set_value($element, $value);
	}
	
	function change_element_type( $element, $new_type = '', $args = array() )
	{
		$this->_d->change_element_type( $element, $new_type, $args );
	}
	
	/**
	 * magic_transform sets values for disco thor elements according to the transform_array. The standard
	 * form for the transform array has keys that are the lowercase equivalent of form field labels with
	 * spaces replaced by underscores. If alter_string is set to false, keys should be equivalent to form
	 * field labels. If a prefix is specified, magic_transform will only transform values for form fields
	 * that have a label that begins with the prefix (case-insensitive).
	 *
	 * @param transform_array array mapping elements labels to the values that should be set
	 * @param prefix string optional prefix which limits fields to consider
	 * @param editable boolean are transformed fields editable or not?
	 * @param alter_string boolean should get true if keys in the transform array are in format 'this_is_my_label'
	 *
	 */
	function magic_transform($transform_array, $prefix, $editable = true, $alter_string = true)
	{
		$key_table = $this->_magic_map_keys_to_id($prefix, $alter_string);
		foreach ($transform_array as $k => $v)
		{
			if (!empty($key_table[$k]))
			{
				$cur_value = $this->_d->get_value($key_table[$k]);
				if (empty($cur_value))
				{
					$this->set_value($key_table[$k], $v);
					if ($editable == false)
					{
						$this->change_element_type($key_table[$k], 'solidtext', array());
					}
				}
			}
		}
	}
	
	/**
	 * _magic_map_keys_to_id is a helper function for magic_transform that creates an array mapping transform keys
	 * to disco ids. If a prefix is provided, only fields matching the prefix are mapped. If alter_string is true,
	 * display names are converted to lower case and spaces replaced with "_" characters in order to match the transform
	 * array.
	 *
	 * @access private
	 * @param prefix string match is case insensitive
	 * @param alter_string boolean default true
	 */
	function _magic_map_keys_to_id($prefix, $alter_string)
	{
		$mapping = array();
		foreach ($this->_d->_elements as $k=>$v)
		{
			if (!empty($prefix))
			{
				if (substr(strtolower($v->display_name), 0, strtolower(strlen($prefix))) == strtolower($prefix))
				{
					
					if ($alter_string) $key = str_replace(' ', '_', strtolower($v->display_name));
					else ($key = $v->display_name);
					$mapping[$key] = $k;
				}
			}
			else 
			{
				if ($alter_string) $key = str_replace(' ', '_', strtolower($v->display_name));
				else $key = $mapping[$v->display_name];
				$mapping[$key] = $k;
			}
		}
		return $mapping;
	}
	
} // end class

?>
