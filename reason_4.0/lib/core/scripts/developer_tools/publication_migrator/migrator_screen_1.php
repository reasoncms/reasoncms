<?php
/**
 * @package reason
 * @subpackage scripts
 */
/**
 * Choose site to migrate
 */
class MigratorScreen1 extends MigratorScreen
{
	var $actions = array('Continue');
	var $site_names_by_id;
	
	function step_init()
	{
		$this->site_names_by_id = $this->helper->get_site_names_by_id();
	}
	
	function on_every_time()
	{
		$this->add_element('active_screen', 'hidden');
		$this->set_value('active_screen', 1);		
		$this->add_element('site_id', 'select_no_sort', array('options' => $this->site_names_by_id, 'display_name' => 'Choose a Site'));
	}
	
	function step_pre_show_form()
	{
		echo '<h2>Select a Site</h2>';
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->get_value('site_id'));
		return $values;
	}
}
?>