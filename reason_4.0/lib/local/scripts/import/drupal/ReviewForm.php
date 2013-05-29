<?php
class ReviewForm extends FormStep
{	
	var $display_name = 'Drupal Import - Review Results';
	var $elements = array();
	var $actions = array('');
	/**
	 * Show a summary of what we will do.
	 */
	function pre_show_form()
	{
		echo '<p>Here is what we did.</p>';
		
		//$data = $this->controller->get_all_form_data();
		//pray ($data);
		
		$my_xml_id = $this->controller->get_form_data('xml_id');
		$cache = new ReasonObjectCache($my_xml_id . '_result');
		$result =& $cache->fetch();
		
		echo 'ice';
		die;
		pray ($result);
		echo 'ice';
		die;
		
		if (isset($result['alerts']))
		{
			echo '<h2>The following alerts likely require attention right away in order for your site to work properly.</h2>';
			foreach ($result['alerts'] as $k=>$v)
			{
				echo '<h3>' . $k . '</h3>';
				echo $v;
			}
		}
		if (isset($result['report']))
		{
			echo '<h2>The full report is mostly useful for developers.</h2>';
			pray ($result['report']);
		}
	}
}
?>