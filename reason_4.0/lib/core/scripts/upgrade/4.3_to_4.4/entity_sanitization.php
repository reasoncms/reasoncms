<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.3_to_4.4']['entity_sanitization'] = 'ReasonUpgrader_44_EntitySanitization';

/**
 * Sanitize all entities in a Reason CMS database according to rules in config/entity_sanitization/setup.php.
 *
 * This upgrade script provides some tools to help you manage the load.
 *
 * - Start / stop control
 * - Dynamic control over entities to update at one time. (default 10).
 *
 * @todo add dynamic control over MS delay between update requests (default 0).
 * @todo add status bar
 */
class ReasonUpgrader_44_EntitySanitization extends reasonUpgraderDefault implements reasonUpgraderInterfaceAdvanced
{
	protected $user_id;
	protected $helper;
	protected $num_to_process = 100;

	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Sanitize all entities in Reason CMS';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		$str = '';
		$str .= '<p>Reason 4.4 adds a new setting REASON_ENABLE_ENTITY_SANITIZATION. In order to thwart XSS requests, you should enable this, and setup any necessary sanitization rules. ';
		$str .= 'Sensible defaults are provided, but you may need to customize your sanitization configuration depending upon what you have allowed / want to allow.</p>';
		$str .= '<p>See config/entity_sanitization/setup.php for details on how to do this.</p>';
		$str .= '<p>Once you have your rules setup, use this script to applying the current sanitization rules to all entities. This might take some time.</p>';
		return $str;
	}
	
	/**
	 * Setup callbacks to make this do its magic.
	 */
	public function init($disco, $head_items)
	{
		$disco->actions = array('Sanitize Entities');
		$disco->add_callback(array($this, 'process'), 'process');
		$disco->add_callback(array($this, 'on_every_time'), 'on_every_time');
		$disco->add_callback(array($this, 'run_error_checks'), 'run_error_checks');
		
		$head_items->add_javascript(JQUERY_URL);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'js/upgrade/entity_sanitization/entity_sanitization.js'); 
	}
	
	/**
	 * Add the fields we need.
	 */
	public function on_every_time( $disco )
	{
		echo '<h3>' . $this->title() . ' </h3>';
		echo '<p>This script runs all live entities in Reason CMS through the entity sanitization rules that you have defined. ';
		echo 'Make sure to configure sanitization rules (see config/entity_sanitization/setup.php) and test your configuration ';
		echo 'on a development server before updating entities using this script.</p>';
		echo '<hr/>';
		
		if ($this->entity_sanitization_enabled())
		{
			$disco->add_element('form_header', 'comment', array('text' => '<h3>Entity Sanitization Configuration</h3>'));
			$disco->add_element('starting_id', 'text');
			$disco->set_value('starting_id', 0);
			$num_options = array(1,5,10,50,100,500,1000);
			$disco->add_element('num_to_process', 'select_no_sort', array('options' => array_combine($num_options, $num_options)));
			$disco->set_value('num_to_process', 10);
			$disco->add_element('number_of_live_entities', 'hidden');
			$disco->set_value('number_of_live_entities', (isset($_POST['number_of_live_entities'])) 
														 ? $_POST['number_of_live_entities'] 
														 : $this->get_number_of_live_entities());
		}
		else
		{
			echo '<p>REASON_ENABLE_ENTITY_SANITIZATION must be enabled to use this script.</p>';
			$disco->show_form = FALSE;
		}
	}
	
	public function run_error_checks( $disco )
	{
		$starting_id = $disco->get_value('starting_id');
		if (!is_numeric($starting_id))
		{
			$disco->set_error('starting_id', 'The starting ID must be a number');
		}
	}
	
	public function process( $disco )
	{
		$s = get_microtime();
		$q = 'SELECT * from entity where state = "Live" AND entity.id > ' . (int) $disco->get_value('starting_id') . ' LIMIT ' . (int) $disco->get_value('num_to_process');
		$result = db_query($q);
		$num_rows = mysql_num_rows($result);
		$updated_count = 0;
		if ($num_rows > 0)
		{
			while ($row = mysql_fetch_assoc($result))
			{
				$eid = $row['id'];
				$e = new entity($eid);
				$values = $e->get_values();
				$hash = md5(serialize($values));
				$updated = reason_update_entity($e->id(), $this->user_id(), $values, false);
				if ($updated) $updated_count++;
			}
			$disco->set_value('starting_id', $eid);
		}
		$e = get_microtime();
		
		$complete = ($num_rows < $disco->get_value('num_to_process'));
		
		// Lets show our results
		echo '<div id="results">';
		echo '<h3>Results</h3>';
		echo '<ul>';
		echo '<li><strong>Checked - </strong> <span class="num_checked">' . $num_rows . '</span></li>';
		echo '<li><strong>Updated - </strong> <span class="num_updated">' . $updated_count . '</span></li>';
		echo '<li><strong>Processing Time (ms) - </strong> ' . round( (($e - $s) * 1000) ) . '</li>';
		echo '</ul>';
		if (!$complete) echo '<p>The starting ID has been updated to <span class="starting_id">' . $eid . '</span></p>';
		echo '</div>';
		echo '<hr/>';
		
		// If we processed less than the num_to_process we must have finished
		if ( $num_rows < $disco->get_value('num_to_process') )
		{
			echo '<p><strong>Processing is complete.</strong></p>';
			$disco->show_form = false;
		}
		else
		{
			$disco->actions = array('Continue Entity Sanitization');
		}
	}
	
	/**
	 * Is REASON_ENABLE_ENTITY_SANITIZATION defined and true?
	 */
	private function entity_sanitization_enabled()
	{
		return (defined("REASON_ENABLE_ENTITY_SANITIZATION") && (REASON_ENABLE_ENTITY_SANITIZATION));
	}
	
	/**
	 * Returns the total number of live entities.
	 */
	private function get_number_of_live_entities()
	{
		if (!isset($this->_number_of_live_entities))
		{
			$q = 'SELECT count(id) as live_count from entity where state = "Live"';
			$result = db_query($q);
			$row = mysql_fetch_assoc($result);
			$this->_number_of_live_entities = $row['live_count'];
		}
		return $this->_number_of_live_entities;
	}
}
?>