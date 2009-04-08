<?php
/**
 * @package reason
 * @subpackage scripts
 */
/**
 * Completion
 */
class MigratorScreen5 extends MigratorScreen
{
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		$this->user_id = $this->helper->get_user_id();
	}
	
	function step_pre_show_form()
	{
		$link = carl_construct_link();
		echo '<h4>Finished</h4>';
		echo '<p><a href="'.$link.'">Start Over</a></p>';		  
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "5", 'site_id' => $this->site_id);
		return $values;
		//return array();
	}
}
?>