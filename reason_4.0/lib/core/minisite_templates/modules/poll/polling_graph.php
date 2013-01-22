<?php
/**
 * Poll Module
 * @author Amanda Frisbee
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent and thor classes and register module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
include_once( THOR_INC . 'thor.php' );

$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'PollingGraphModule';

/**
 * A minisite module that converts a form into a poll and displays the results in a pie graph
 *
 * @author Amanda Frisbee
 * @todo lets move database work to init and add a has_content.
 */
class PollingGraphModule extends DefaultMinisiteModule
{

	var $acceptable_params = array ('custom_colors' => array('#cb4b4b', '#edc240', '#2f90dc', '#9440ed', '#4da74d'), );
	var $sidebar = false;
	var $cleanup_rules = array('show_results' => 'turn_into_int');
	
	function init( $args = array() )
	{
		// Add all necessary JavaScript files as head items
		if($hi =& $this->get_head_items())
		{
			$hi->add_javascript(JQUERY_URL, true);
			$hi->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.js');
			$hi->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.pie.js');
			$hi->add_javascript(WEB_JAVASCRIPT_PATH.'modules/poll/polls.js');
			if ($this->sidebar) $hi->add_stylesheet(WEB_JAVASCRIPT_PATH.'modules/poll/poll_sidebar.css');
			else $hi->add_stylesheet(WEB_JAVASCRIPT_PATH.'modules/poll/poll.css');
		}
	}
	
	function get_form()
	{
		if (!isset($this->_form))
		{
			$this->_form = false;
			// Get the form entity attached to the current page
			$es = new entity_selector();
			$es->add_type( id_of('form') );
			$es->add_right_relationship( $this->cur_page->id(), relationship_id_of('page_to_form') );
			$es->set_num(1);
			$result = $es->run_one();
			if ($result)
			{
				$this->_form = reset($result);
			}
		}
		return $this->_form;
	}
	
	function show_results()
	{
		return (isset($this->request['show_results']) && ($this->request['show_results'] == 1) );
	}
	
	function has_content()
	{
		return ($this->get_form());
	}
	
	function run() // {{{
	{
		$form = $this->get_form();
		// thor_core needs the XML from the form entities thor_content field, and the table name the data is stored in
		$xml = $form->get_value('thor_content');
		$table_name = 'form_' . $form->id();	
		$thor_core = new ThorCore($xml, $table_name);
		$thor_values = $thor_core->get_rows();

		// Loop through the database information to gather the poll results
		$pretty_values = array();
		
		if ($thor_values != "")
		{
			foreach($thor_values as $t_v)
			{
				foreach($t_v as $key => $value)
				{
					if ($key != "id" && $key != "submitted_by" && $key != "submitter_ip" && $key != "date_created" && $key != "date_modified")
					{ 
						$pretty_values[] = $value;
					}	
				}
			}
		}

		// Get the total number of results and then find the total results for each option
		$responses = (array_count_values($pretty_values));
		$accumulator = array();
		
		foreach ($responses as $response)
		{
			$accumulator[] = $response;
		}
		
		// Get the label for each option
		$name = array();
		
		foreach ($responses as $key => $value)
		{
			$name[] = $key;
		}
		
		// Calculate the total and get the percentage for each option
		$total = 0;
		$per = array();

		foreach ($accumulator as $key => $value)
		{
			$total += $value;
		}

		if ($total > 0)
		{
			for ($i = 0; $i <= (count($accumulator) - 1); $i++)
			{
				$per[$i] = round(($accumulator[$i]/$total) * 100) . "%";
			}
		}
	
		if (!$this->show_results()) echo '<p><a href="' . carl_make_link(array('show_results' => 1, 'submission_key' => '')) . '">Show Results</a></p>';
		else echo '<p><a href="' . carl_make_link(array('show_results' => 0, 'submission_key' => '')) . '">Hide Results</a></p>';
		
		if ($this->show_results()) 
		{
			if ($this->sidebar) echo '<div id="interactive" class="graph">';
			else echo '<div id="graph1" class="graph">';
			for ($i = 0; $i <= (count($accumulator) - 1); $i++)
			{	
				echo '<table width="300px" border="solid" cellpadding="5px">
				<tr>
				<td width="50px">' . $per[$i] . ' </td><td> ' . $name[$i] . '</td>
				</tr>
				</table>';
			}
			echo '</div>';
			if ($this->sidebar) echo '<div id="hover"></div>';
			echo '<div id="responses"><p><strong>Total responses: ' . $total .'</strong></p></div>';
		}
		
		$datas = array();
		$lbls = array();

		for ($i = 0; $i <= (count($accumulator) - 1); $i++)
		{
			$datas[$i] = $accumulator[$i];
			$lbls[$i] = $name[$i];
		}
		
		// Transfer the labels and totals for each option to polls.js
		echo '<script type="text/javascript">'."\n";
		echo 'var data = new Array();'."\n";
		echo 'var lbl = new Array();'."\n";
		echo 'var color = new Array();'."\n";
		
		for($i = 0; $i <= (count($datas) - 1); $i++)
		{
			$color = (isset($this->params['custom_colors'][$i])) ? $this->params['custom_colors'][$i] : "#" . dechex(rand(0,10000000));
			echo 'color['.$i.'] = "'.addslashes(htmlspecialchars($color)).'" ;'."\n";
			echo 'data['.$i.'] ='.addslashes(htmlspecialchars($datas[$i])).';'."\n";
			echo 'lbl['.$i.'] = "'.addslashes(htmlspecialchars($lbls[$i])).'" ;'."\n";
		}
		echo '</script>'."\n";
	}
}
?>