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
	 * @var string export mode currently only csv is supported and this is unused
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
	
	/**
	 * @var array populated with a sample row when data is built - allows header row to render correctly if filtering gets rid of all rows
	 * @access private
	 */ 
	var $_row = array();
	
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
	
	function Thor_Viewer($xml, $db_conn = '', $table_name = '')
	{
		$this->_xml = $xml;
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;
	}
	
	function set_db_conn($db_conn, $table_name)
	{
		$this->_db_conn = $db_conn;
		$this->_table_name = $table_name;
	}
	
	function set_params($sort_order, $sort_field, $export, $filters)
	{
		if (!empty($sort_order)) $this->set_sort_order($sort_order);
		if (!empty($sort_field)) $this->set_sort_field($sort_field);
		if (!empty($export)) $this->set_export($export);
		if (!empty($filters)) $this->set_filters($filters);
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
		$this->export = $input;
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
			//if (!empty($v)) $this->filters[$k] = $v; // this should be enough ....
		}
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
		if (($this->filter_toggle == true) && (count($this->filters) > 0)) $this->filter_data($my_data, $this->filters);
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
	 * filter_data modifies data according to an array of filters, and allows modules can apply filtering rules unrelated to user input 
	 * @param &$data
	 * @param $filter_array
	 */
	function filter_data(&$data, $filter_array = '')
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
					if (strpos($v[$active_filter['name']], $active_filter['value']) === false) unset ($data[$k]);
				}
			}
			if (count($filter_array) > 0) $this->filter_data($data, $filter_array);
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
				$ret .= '<select name="filters['.$k.']">';
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
				$ret .= '<input type="text" name="filters['.$k.']" value="'.$cur_value.'" size="10" /></td>';
			}
			$count++;
			$first = '';
		}
		$ret .= '</tr>';
		$ret .= '<tr>';
		$ret .= '<td class="filterButtons" colspan='.$count.'>';
		$ret .= '<input type="submit" name="filter_submit" value="Apply Filters"> <input type="submit" name="clear" value="Clear Filters" />';
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
		if (empty($this->_display_values)) $this->_map_labels($header_row);
		$parts = parse_url(get_current_url());
		$base_url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?mode=data_view&';
		$ret = '<tr class="head">';
		foreach ($header_row as $k => $v)
		{
			if (($this->sort_field == $k) && ($this->sort_order == 'asc')) $order = 'desc';
			else $order = 'asc';
			$url = $base_url . 'sort_field='.$k.'&sort_order='.$order;
			foreach ($this->filters as $k2 => $v2)
			{
				$url .= '&filters['.$k2.']='.htmlentities($v2['value']);
			}
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
		$ret .= '<h3>Displaying '.$this->filtered_rows.' of '.$this->total_rows.' rows</h3>';
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
			if (empty($this->_display_values)) $this->_map_labels($this->_row);
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
	
	// placeholder function for eventual ability to add/remove columns
	function build_columns()
	{
		return '*';
	}
	
	function add_column($column_name)
	{
	}
	
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
	
	/**
	 * _check_table_exists does just what it says - checks if the table parameter is actually a table in the database
	 *
	 * @return boolean true if the table exists, false otherwise
	 */
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
	
    /**
	 * _map_lables takes a row, and returns an array mapping database column headers to human readable labels
	 *
	 * @param array with keys that are database column headers
	 * @param int maxlength determines number of characters to pad column headers at
	 * @return array which maps database column headers to real labels
	 *
	 * @todo _display_values does not really need to be a class variable probably, though it might be handy ... 
	 */
	function _map_labels($array)
	{
		$xmldoc = domxml_open_mem($this->_xml); // get dom object
		$xpath = xpath_new_context($xmldoc); // init xpath
		$this->_html = '';

		// II. Transform the form's elements
		$xpresult = xpath_eval($xpath, '/form[1]/*');
		foreach ($xpresult->nodeset as $node) {
			if ($node->tagname == 'input') {
				$this->_display_input($node);
			}
			elseif ($node->tagname == 'textarea') {
				$this->_display_textarea($node);
			}
			elseif ($node->tagname == 'radiogroup') {
				$this->_display_radiogroup($node, $xpath);
			}
			elseif ($node->tagname == 'checkboxgroup') {
				$this->_display_checkboxgroup($node, $xpath);
			}
			elseif ($node->tagname == 'optiongroup') {
				$this->_display_optiongroup($node, $xpath);
			}
			elseif ($node->tagname == 'hidden') {
				$this->_display_hidden($node);
			}
			//elseif ($node->tagname == 'comment') {
			//	$this->_display_comment($node);
			//}
		}
		return $this->_display_values;
	}
	
	/**
	 * Helper functions for _map_labels($array)
	 * @access private
	 */
	 
	function _display_input($element) {
		$id = $element->get_attribute('id');
		$this->_display_values[$id]['label'] = $element->get_attribute('label');
		$this->_display_values[$id]['type'] = $element->tagname;
	}

	function _display_hidden($element) {
		// probably don't want to include hidden elements in display
	}
 
	function _display_textarea($element) {
		$id = $element->get_attribute('id');
		$this->_display_values[$id]['label'] = $element->get_attribute('label');
	    $this->_display_values[$id]['type'] = $element->tagname;
	}

	function _display_radiogroup($element, $xpath) {
		$id = $element->get_attribute('id');
		$this->_display_values[$id]['label'] = $element->get_attribute('label');
	    $this->_display_values[$id]['type'] = $element->tagname;
	    $xpresult = xpath_eval($xpath, "//*[@id='" . $element->get_attribute('id') . "']/radio");
		foreach ($xpresult->nodeset as $node) {
		    $this->_display_values[$id]['options'][] = $node->get_attribute('value');
		}
	}

	function _display_checkboxgroup($element, $xpath) {
	    $id = $element->get_attribute('id');
	    //$this->_display_values[$id]['label']['wrapper_label'] = $element->get_attribute('label');
	    //$this->_display_values[$id]['type'] = $element->tagname;
		$xpresult = xpath_eval($xpath, "//*[@id='" . $element->get_attribute('id') . "']/checkbox");
		foreach ($xpresult->nodeset as $node) {
			$id2 = $node->get_attribute('id');
			$this->_display_values[$id2]['label'] = $node->get_attribute('label');
			$this->_display_values[$id2]['type'] = $node->tagname;
		}
	}

	function _display_optiongroup($element, $xpath) {
		$id = $element->get_attribute('id');
		$this->_display_values[$id]['label'] = $element->get_attribute('label');
	    $this->_display_values[$id]['type'] = $element->tagname;
	    $xpresult = xpath_eval($xpath, "//*[@id='" . $element->get_attribute('id') . "']/option");
		foreach ($xpresult->nodeset as $node) {
		    $this->_display_values[$id]['options'][] = $node->get_attribute('value');
		}
	}
}
?>
