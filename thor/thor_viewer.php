<?

/**
 * Thor Viewer
 *
 * @package thor
 * @author nathan white
 *
 * Provides a view of thor form data with filtering and .csv export
 *
 */
include_once ( 'paths.php' );
require_once( 'XML/Unserializer.php'); // Requires PEAR XML_Serialize package
include_once( DISCO_INC . 'disco.php'); // Requires Disco

class Thor_Viewer
{
	/**
	 * @var string which field label to sort by default 'date_created'
	 */
	var $sort_field = 'date_created';
	
	/**
	 * @var string sort order to use default 'desc'
	 */
	var $sort_order = 'desc';
	
	/**
	 * @var string export mode currently only csv is supported
	 */
	var $export = ''; 
	
	/**
	 * @var array data filters, indexed by column name
	 */
	var $filters = array();
	
	/**
	 * @var boolean toggles whether filtering is active or not default true
	 */ 
	var $filter_toggle = true;
	
	var $export_enable = true;
	
	var $allow_delete = false;
	
	var $delete_action;
	
	var $delete_form;
	/**
	 * @var array populated with a sample row when data is built - allows header row to render correctly if filtering gets rid of all rows
	 * @access private
	 */ 
	var $_row = array();
	
	/**
	 * @var array representing form structure - parsed using PEAR XML Unserialize
	 */
	var $_form_xml = array();

	var $_display_values = array();
	
	/**
	 * @var int total number of rows in $table_name
	 */
	var $total_rows = 0; // total number of rows in database
	
	/**
	 * @var int total number of filtered rows
	 */
	var $filtered_rows;
	
	/**
	 * @var array extra fields that aren't represented in thor
	 */
	var $extra_fields = array('id', 'submitted_by', 'submitter_ip', 'date_created');
	
	var $cleanup_rules = array('thor_sort_order' => array('function' => 'check_against_array', 'extra_args' => array('asc', 'ASC', 'desc', 'DESC')),
							   'thor_filters' => array('function' => 'turn_into_array'),
							   'thor_filter_clear' => array('function' => 'turn_into_string'),
							   'thor_delete' => array('function' => 'check_against_array', 'extra_args' => array('delete', 'confirm_delete')),
							   'thor_export' => array('function' => 'check_against_array', 'extra_args' => array('csv')));
	
	var $filename_frag = 'form';
	var $filename_real = '';
	
	function Thor_Viewer($xml = '')
	{
		$this->_xml = $xml;
	}
	
	function init_using_reason_form_id($form_id)
	{
		$form = new entity($form_id);
		$form->get_values();
		if ($form->get_value('type') != id_of('form'))
		{
			trigger_error('the thor viewer was passed an invalid id ('.$form_id.') - it must be passed the ID of a reason form entity');
		}
		$this->init($form->get_value('thor_content'), THOR_FORM_DB_CONN, 'form_'.$form->id(), $form->id());
	}
	
	function init($xml, $thor_db, $table_name, $filename_frag = '', $filename_real = '')
	{
		$this->_xml = $xml;
		$this->set_db_conn($thor_db, $table_name);
		if(!empty($this->filename_frag)) $this->filename_frag = $filename_frag;
		if(!empty($this->_xml))
		{
			$unserializer_options = array ( 'parseAttributes' => TRUE, 
											'forceEnum' => array('hidden','textarea', 'comment', 'optiongroup','checkboxgroup','radiogroup','checkbox','radio','option') ); 
			$unserializer = &new XML_Unserializer($unserializer_options);
			$unserializer->unserialize($this->_xml);
			$this->_form_xml = $unserializer->getUnserializedData();
			$this->_display_values = $this->_build_display_values();
		}
		$this->_set_params();
		if (!empty($this->delete_action)) $this->init_delete();
		$this->init_history();
	}
	
	function init_delete()
	{
		if ($this->delete_action == 'delete')
		{
			$confirm = new DiscoConfirm();
			$this->delete_form =& $confirm;
		}
		elseif ($this->delete_action == 'confirm_delete')
		{
		
		}
	}

	function init_history()
	{
		if (!$this->_check_table_exists('thor_history'))
		{
			$this->_create_thor_history_table();
		}
	}
	
	function enable_delete()
	{
		$this->allow_delete = true;
	}
	
	function disable_delete()
	{
		$this->allow_delete = false;
	}
	
	function set_db_conn($db_conn, $table_name)
	{
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;
	}

	function set_filename_real($filename)
	{
		$this->filename_real = $filename;
	}
	/**
	 * @deprecated
	 */
	function set_params($sort_order, $sort_field, $export, $filters)
	{
		$this->init($this->_xml, $this->_db_conn, $this->_table_name);
	}
	
	function _set_params()
	{
		$this->cleanup_rules['thor_sort_field'] = array('function' => 'check_against_array', 'extra_args' => array_merge($this->extra_fields, array_keys($this->_display_values))); // dynamically add
		
		$this->request = carl_clean_vars($_REQUEST, $this->cleanup_rules);
		if (!empty($this->request['thor_sort_order'])) $this->set_sort_order($this->request['thor_sort_order']);
		if (!empty($this->request['thor_sort_field'])) $this->set_sort_field($this->request['thor_sort_field']);
		if (!empty($this->request['thor_export'])) $this->set_export($this->request['thor_export']);
		if ((!empty($this->request['thor_filters'])) && !isset($this->request['thor_filter_clear'])) $this->set_filters($this->request['thor_filters']);
		if (!empty($this->request['thor_delete'])) $this->set_delete($this->request['thor_delete']);
		else ($this->set_filters(array('')));
	}

	function set_sort_field($input)
	{
		$this->sort_field = $input;
	}
	
	function set_sort_order($input)
	{
		//only 'desc' or 'asc' are valid -- if invalid default to 'desc'
		$this->sort_order = ((strtolower($input) == 'desc') || (strtolower($input) == 'asc')) ? $input : 'desc';
	}
	
	function set_page($input)
	{
		$this->page = $input;
	}
	
	function set_export($input)
	{
		if ($this->export_enable)
		{
			$this->export = $input;
		}
	}
	
	function set_num_per_page($num_per_page)
	{
		$this->num_per_page = $num_per_page;
	}
	
	function set_filters($filter_array)
	{
		foreach ($filter_array as $k=>$v)
		{
			if (!empty($v)) $this->filters[$k] = array('name' => $k, 'value' => $v);
		}
	}
	
	function set_delete($string)
	{
		$this->delete_action = $string;
	}
	
	/**
	 * build_data reads in all values stored from a thor form according to sort_field and sort_order
	 * @return $my_data array of associative arrays which represent each row
	 */
	function build_data()
	{
		if ($this->_check_table_exists() == false) return array();
		// connect to database
    	connectDB($this->_db_conn);
    	// build the query
		$q = 'SELECT '.$this->build_columns().' FROM '. $this->_table_name;
		$q .= ' ORDER BY ' . $this->sort_field . ' ' . $this->sort_order;
		$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
		if ( mysql_num_rows( $res ) > 0 )
		{
			while($row = mysql_fetch_assoc($res))
			{
				$my_data[] = $row;
			}
		}
		connectDB(REASON_DB); // reconnect to default DB
		$this->_row = current($my_data);
		$this->total_rows = count($my_data);
		if (($this->filter_toggle == true) && (count($this->filters) > 0)) $this->_filter_data($my_data, $this->filters);
		$this->filtered_rows = count ($my_data);
		return $my_data;
	}
	
	/**
	 * @return boolean true if the row count of unfiltered data is greater than 0
	 */
	function has_data()
	{
		if ($this->total_rows > 0) return true;
		else return false;
	}
	
	/**
	 * _filter_data modifies data according to an array of filters, and allows modules to apply filtering rules unrelated to user input 
	 * @param &$data
	 * @param $filter_array
	 */
	function _filter_data(&$data, $filter_array = '')
	{
		//pray ($filter_array);
		if (!empty($filter_array))
		{
			$active_filter = array_pop($filter_array);
			if ($active_filter['name'] == 'id')
			{
				foreach ($data as $k => $v)
				{
					if ($v[$active_filter['name']] != $active_filter['value']) unset ($data[$k]);
				}
			}
			elseif ($active_filter['value'] == '*NULL*')
			{
				foreach ($data as $k => $v)
				{
					if (!empty($v[$active_filter['name']])) unset ($data[$k]);
				}
			}
			else
			{
				foreach ($data as $k => $v) 
				{
					if (strpos(strtolower($v[$active_filter['name']]), strtolower($active_filter['value'])) === false) unset ($data[$k]);
				}
			}
			if (count($filter_array) > 0) $this->_filter_data($data, $filter_array);
		}
	}

    /**
	 * Generates HTML for two table rows - one with input box to control filtering for each column, and the other for "apply filter" and "clear filter" buttons
	 * @param array $row can be any row from the data since the keys are always the same
	 * @todo add hidden labels for each filtering field for accessibility
	 * @return string HTML for the 2 filter related rows
	 */
	function gen_filter_rows($row)
	{		
		$first = ' class="first"';
		$ret = '<tr>';
		$count = 0;
		foreach ($row as $k => $v)
		{	
			$type = (in_array($k, $this->extra_fields) == false) ? $this->_display_values[$k]['type'] : 'input';
			$cur_value = (!empty($this->filters[$k])) ? htmlentities($this->filters[$k]['value']) : '';
			$ret .= '<td'.$first.'>';
			if ($count == 0) $ret .= '<form name="search" action="'.get_current_url().'" method="post">';

			if (($type == 'radiogroup') || ($type == 'optiongroup'))
			{
				$selected = '';
				$ret .= '<select name="thor_filters['.$k.']">';
				$ret .= '<option value="">---</option>';
				foreach ($this->_display_values[$k]['options'] as $v2)
				{
					$selected = ($cur_value == $v2) ? ' SELECTED' : '';
					$ret .= '<option value = "'.$v2.'"'.$selected.'>'.$v2.'</option>';
				}
				$ret .= '</select>';
			}
			else
			{
				$ret .= '<input type="text" name="thor_filters['.$k.']" value="'.$cur_value.'" size="10" /></td>';
			}
			$count++;
			$first = '';
		}
		$ret .= '</tr>';
		$ret .= '<tr>';
		$ret .= '<td class="filterButtons" colspan='.$count.'>';
		$ret .= '<input type="submit" name="filter_submit" value="Apply Filters"> <input type="submit" name="thor_filter_clear" value="Clear Filters" />';
		$ret .= '</form>';
		$ret .= '</td>';
		$ret .= '</tr>';
		return $ret;
	}
	
	/**
	 * Generates HTML for a table row containing column headers
	 * @param array $header_row can be any row from the data since the keys are always the same
	 * @todo add mechanism to pad to certain length
	 * @return string HTML for the header row
	 */
	function gen_header_row($header_row)
	{
		$first = ' class="first"';
		$order_display_name = array('asc' => 'Sort Ascending', 'desc' => 'Sort Descending');
		//$parts = parse_url(get_current_url());
		//$base_url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?mode=data_view&';
		$ret = '<tr class="head">';
		foreach ($header_row as $k => $v)
		{
			if (($this->sort_field == $k) && ($this->sort_order == 'asc')) $order = 'desc';
			else $order = 'asc';
			$url_array = array('thor_sort_field' => $k, 'thor_sort_order' => $order, 'thor_filters' => '', 'thor_export' => '');
			$this->parse_filters_for_url($url_array);
			$url = carl_make_link($url_array);
			$v = (isset($this->_display_values[$k])) ? $this->_display_values[$k]['label'] : $k;
			$ret .= '<th'.$first.'><a href="'.$url.'" title="'.$order_display_name[$order].'">'.htmlentities($v).'</a></th>';
			$first = '';
		}
		$ret .= '</tr>';
		return $ret;
	}
	
	/**
	 * Generates HTML for a data row
	 * @param array $header_row can be any row from the data since the keys are always the same
	 * @return string HTML for the data row
	 *
	 */
	function gen_data_row($data_row, $class)
	{
		$first = ' class="first"';
		$ret = '<tr class="'.$class.'">';
		foreach ($data_row as $k=>$v)
		{
			if ($k=='date_created') $v = prettify_mysql_timestamp($v, 'M jS, Y - g:i A');
			$v = htmlentities($v);	
			$v = (!empty($v)) ? $v : '<br />';
			$ret .= '<td'.$first.'>'.$v.'</td>';
			$first = '';
		}
		$ret .= '</tr>';
		return $ret;
	}
	
	/**
	 * Generates HTML to represent the stored data which corresponds to a thor form
	 * @param array $data_array
	 * @param boolean $filters whether or not to run filters
	 * @return string $ret HTML for table
	 */
	function gen_table_html($data_array, $filters = true)
	{
		$ret = '';
		$class = 'odd';
		$header = $this->gen_header_row($this->_row);
		$links_base = $links_export_all = $this->gen_menu_links_base();
		$this->parse_filters_for_url($links_base);	
		$links_export = $links_delete = $links_base;
		$links_delete['thor_delete'] = 'delete';
		$links_export_all['thor_export'] = 'csv'; // does not consider filtering
		$menu_links['Export Stored Data'] = carl_make_link($links_export_all);
		if ($this->filtered_rows > 0 && ($this->filtered_rows != $this->total_rows))
		{
			$num_string = ($this->filtered_rows == 1) ? '1 Item' : $this->filtered_rows . ' Items';
			$links_export['thor_export'] = 'csv';
			$menu_links['Export Found Set ('.$num_string.')'] = carl_make_link($links_export);
		}
		if ($this->allow_delete) $menu_links['Delete Stored Data'] = carl_make_link($links_delete);
		$ret .= '<h3>Displaying '.$this->filtered_rows.' of '.$this->total_rows.' rows</h3>';
		if (!empty($menu_links)) $ret .= $this->gen_menu($menu_links);
		$ret .= '<table class="thor_data">';
		$ret .= $header;
		if ($this->filter_toggle) $ret .= $this->gen_filter_rows($this->_row);
		foreach ($data_array as $data_row)
		{
			$class = ($class == 'odd') ? 'even' : 'odd';
			$ret .= $this->gen_data_row($data_row, $class);
		}
		$ret .= '</table>';
		return $ret;
	}

	/**
	 * Needs to preserve proper request variables relevant to this module and not provide a link to whatever is currently requested
	 */
	function gen_menu_links_base()
	{
		$links_base = array('thor_sort_field' => $this->sort_field, 'thor_sort_order' => $this->sort_order, 'thor_filters' => '', 'thor_filters_clear' => '', 'thor_delete' => '', 'thor_export' => '');
		return $links_base;
	}
	
	function gen_menu($link_array)
	{
		foreach ($link_array as $k=>$v)
		{
			if (!empty($v))
			{
				$links[] = '<a href="'.$v.'">'.$k.'</a>';
			}
			else 
			{
				$links[] = '<strong>'.$k.'</strong>';
			}
		}
		return '<p><strong>Options</strong>: ' . implode(' | ', $links) . '</p>';
	}
	
	/**
	 * Creates an excel compatible csv file and outputs it as a user download. This function will
	 * not work properly unless output buffering is active. It cleans the output buffer before
	 * sending the appropriate headers to prompt the user to download the .csv file
	 * @param array $thor_data
	 * @param string $delim default ","
	 * @param boolean $head whether or not to print header row default true
	 * @param string $filename custom filename for a .csv download defaults to date_$_table_name (XXXX-XX-XX_form_XXXXXX)
	 */
	function gen_csv($thor_data, $delim = ',', $head = true, $filename = '')
	{
		$ret = '';
		if ($head) // if head we append a flipped row to the head of the array with flipped and mapped keys
		{
			foreach($this->_row as $k=>$v)
			{
				$head_row[$k] = (isset($this->_display_values[$k])) ? $this->_display_values[$k]['label'] : $k;
			}
			array_unshift($thor_data, $head_row);
		}
		foreach ($thor_data as $data_row)
		{
			$line = '';
			$separator = '';
			foreach ($data_row as $k => $v)
			{
				if ($k=='date_created' && $v != 'date_created') $v = prettify_mysql_timestamp($v);
       		 	$pos = strpos($v, $delim);
       		 	$v = str_replace('"', '""', $v);
			$v = str_replace("\r\n", "\n", $v); // best linefeed handling I can come up with though mac and windows
							    // excel behave a bit differently
       		 	$line .= $separator. '"' . trim($v) . '"';
        		if ($separator == '') $separator = $delim;
			}
			$line .= "\n";
			$ret .= $line;
		}
		if (!empty($ret))
		{
			// this will only work properly when output buffering is active
			ob_end_clean();
			if (empty($filename)) $filename = $this->_table_name . '_'. date("Y-m-d");
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=$filename.csv");
			header("Content-Transfer-Encoding: binary");

			// IE will barf without these
			header("Pragma: private");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			
			header("Expires: 0");
			echo $ret;			
			exit;
		}
	}

    /**
	 * Wraps up the typical use case
	 */
	
	function run()
	{
		$thor_data = $this->build_data();
		if (!$this->has_data())
		{
			echo '<p><strong>The form has no stored data.</strong></p>';
			return false;
		}
		if (!empty($this->delete_action))
		{
			$this->run_delete();
		}
		elseif (empty($this->export))
		{
			// basic display of table
			$thor_viewer_html = $this->gen_table_html($thor_data);
			echo $thor_viewer_html;
		}
		elseif ($this->export == 'csv')
		{
			// csv export
			$filename = (!empty($this->real_filename)) ? $this->real_filename : $this->filename_frag . '_' .date("Y-m-d");
 			$this->gen_csv($thor_data, ',', true, $filename);
		}
	}

	function run_delete()
	{
		$links_base = $this->gen_menu_links_base();
		$links_export = $links_view = $links_base;
		$this->parse_filters_for_url($links_view);
		$links_export['thor_export'] = 'csv';
		$menu_links['View Stored Data'] = carl_make_link($links_view);
		$menu_links['Delete Stored Data'] = '';
		$this->delete_form->set_num_rows($this->total_rows);
		$this->delete_form->provide_link_to_csv_export(carl_make_link($links_export));
		$this->delete_form->generate();
		$status = $this->delete_form->get_status();
		if (empty($status))
		{
			echo $this->gen_menu($menu_links);
			echo $this->delete_form->get_form_output();
		}
		elseif($status == 'cancel')
		{
			header( 'Location: '. carl_make_redirect($links_view) );
		}
		elseif($status == 'delete_forever')
		{
			if ($this->_check_table_exists())
			{
				$this->_delete_data();
				echo '<p><strong>Deleted ' . $this->total_rows . ' row(s)</strong></p>';
			}
			else
			{
				echo '<p><strong>There is no data to delete</strong></p>';
			}
		}
	}
	
	// placeholder function for eventual ability to show limited number of columns
	function build_columns()
	{
		return '*';
	}
	
	// placeholder function for eventual ability to add columns
	function add_column($column_name)
	{
	}
	
	// placeholder function for eventual ability to remove columns
	function remove_column($column_name)
	{
	}
	
	/**
	 * build_filters builds the WHERE clause of a query string based upon the class array filters
	 *
	 * This function is currently not being used by the class, as all filtering is handled in code rather than queries
	 *
	 * @return string
	 */
	function build_filters()
	{
		$ret = '';
		if (count($this->filters) > 0)
		{
			$ret = ' WHERE';
			foreach ($this->filters as $k=>$v)
			{
				if (!empty($v)) $ret .= ' '. $k . ' LIKE ' . $v . ',';		
			}
			$ret = substr($ret, 0, -1); //trim trailing comma
		}
		return $ret;
	}
	
	function parse_filters_for_url(&$url_array)
	{
		foreach ($this->filters as $k => $v)
		{
			$url_array['thor_filters['.$k.']'] = htmlentities($v['value']);
		}
	}
	
	/**
	 * _check_table_exists does just what it says - checks if the table parameter is actually a table in the database
	 *
	 * @return boolean true if the table exists, false otherwise
	 */
	function _check_table_exists($table = '')
	{
		$ret = true;
		if (empty($table)) $table = $this->_table_name;
		connectDB($this->_db_conn);
  		$q = 'check table ' . $table . ' fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  		$res = mysql_query($q);
  		$results = mysql_fetch_assoc($res);
  		if (strstr($results['Msg_text'],"doesn't exist") ) 
  		{
  			$ret = false;
  		}
  		connectDB(REASON_DB); // reconnect to default DB
  		return $ret;
	}
	
	function _create_thor_history_table()
	{
		$q = 'CREATE TABLE thor_history(`id` int(11) NOT NULL AUTO_INCREMENT,
										`table_name` TINYTEXT NOT NULL,
										`date_created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
										`num_rows` int(6) NOT NULL,
										`csv_data` MEDIUMTEXT NOT NULL,
										PRIMARY KEY(`id`)) TYPE = MYISAM;';
		connectDB($this->_db_conn);
		$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
		connectDB(REASON_DB); // reconnect to default DB
	}
	
	function _build_display_values()
	{
		$display_values = array();
		foreach ($this->_form_xml as $k=>$v)
		{
			if (method_exists($this, '_build_display_'.$k))
			{
				$build_function = '_build_display_'.$k;
				$display_values = array_merge($display_values, $this->$build_function($v));
			}
		}
		return $display_values;
	}

	/**
	 * Helper functions for _build_display_values()
	 * @access private
	 */
	 
	function _build_display_input($element_array)
	{
		$type = 'input';
		foreach ($element_array as $element) 
		{
			$display_values[$element['id']] = array('label' => $element['label'], 'type' => $type);
		}
		return $display_values;
	}

	function _build_display_hidden($element_array)
	{
		$type = 'hidden';
		foreach ($element_array as $element) 
		{
			$display_values[$element['id']] = array('label' => $element['label'], 'type' => $type);
		}
		return $display_values;
	}
 
	function _build_display_textarea($element_array)
	{
		$type = 'textarea';
		foreach ($element_array as $element) 
		{
			$display_values[$element['id']] = array('label' => $element['label'], 'type' => $type);
		}
		return $display_values;
	}

	function _build_display_checkboxgroup($element_array)
	{
		foreach ($element_array as $element) 
		{
			$type = 'checkbox';
			foreach ($element['checkbox'] as $k=>$v)
			{
				$label = $v['label'];
				$display_values[$v['id']] = array('label' => $label, 'type' => $type);
			}
			
		}
		return $display_values;
	}
	
	function _build_display_radiogroup($element_array)
	{
		foreach ($element_array as $element) 
		{
			$id = $element['id'];
			$label = $element['label'];
			$type = 'radiogroup';
			foreach ($element['radio'] as $k=>$v)
			{
				$options[] = $v['value'];
			}
			$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		}
		return $display_values;
	}

	function _build_display_optiongroup($element_array)
	{
		foreach ($element_array as $element) 
		{
			$id = $element['id'];
			$label = $element['label'];
			$type = 'optiongroup';
			foreach ($element['option'] as $k=>$v)
			{
				$options[] = $v['value'];
			}
			$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		}
		return $display_values;
	}

	function _delete_data()
	{
		// connect with thor database defined in settings.php3
		connectDB(THOR_FORM_DB_CONN);
		$q = 'DROP TABLE ' . $this->_table_name;
		$res = mysql_query( $q ) or mysql_error();//or trigger_error( 'Error: mysql error in Thor Data delete - URL ' . get_current_url() . ': '.mysql_error() );
  		connectDB(REASON_DB);
		return $res;
	}
}

	class DiscoConfirm extends Disco
	{
		var $num_rows;              
		var $elements = array('disco_confirm_private' => 'hidden');
		var $actions = array( 'disco_confirm_delete_forever' => 'Delete Forever',
							  'disco_confirm_cancel'         => 'Cancel' );
		var $status = '';
		var $csv_export_string;
		var $form_output;
		
		function DiscoConfirm()
		{
		}
		
		function set_num_rows($num_rows)
		{
			$this->num_rows = $num_rows;
		}
		
		function pre_show_form()
		{
			if ($this->num_rows > 0)
			{
				echo '<p>If you choose to proceed to delete the stored data, ';
				echo '<strong>all information</strong> that has been entered using this form on any page will be deleted from the database. ';
				echo 'If this information is important, it is highly recommend that you use the following link to <strong>save ';
				echo 'the data from the form before you proceed with deletion!</strong></p>'."\n";
				echo '<hr/>'.$this->csv_export_string.'<hr/>';
				echo '<p>Choose the "Delete Forever" button to <strong>permanently delete all ' . $this->num_rows . ' row(s)</strong> of data:</p>';
			}
			else
			{
				$this->show_form = false;
				echo '<p>There appear to be no rows to delete.</p>';
				$this->actions = array();
			}
		}
		
		function provide_link_to_csv_export($link)
		{
			$this->csv_export_string = '<ul><li><a href="'.$link.'">Download all '.$this->num_rows.' rows of data as .csv file</a></li></ul>';
		}
		
		function get_status()
		{
			return $this->status;
		}
		
		function get_form_output()
		{
			return $this->form_output;
		}
		
		function generate()
		{
			ob_start();
			$this->run();
			$this->form_output = ob_get_contents();
			ob_end_clean();
		}
		
		function process()
		{
			$this->show_form = false;
			if ($this->chosen_action == 'disco_confirm_delete_forever')
			{
				$this->status = 'delete_forever';
			}
			else
			{
				$this->status = 'cancel';
			}
		}
	}
?>
