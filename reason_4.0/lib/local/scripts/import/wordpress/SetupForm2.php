<?php
/**
 * Dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/object_cache.php');

/**
 * Based upon the XML imported and verified in step one, present relevant configuration options.
 */
class ConfirmForm extends FormStep
{	
	var $display_name = 'Wordpress Import - Confirm';
	var $elements = array();//'import_pages' => array('type' => 'select_no_sort', 'options' => array('yes', 'no')),
						   	  //'import_posts' => array('type' => 'select_no_sort', 'options' => array('yes', 'no')),
						  	  //'import_categories' => array('type' => 'select_no_sort', 'options' => array('yes', 'no')));

	/**
	 * Lets remove / alter config options that are not relevant.
	 */
	function on_every_time()
	{
		//$my_xml_id = $this->controller->get_form_data('xml_id');
		//echo $my_xml_id . ' is the xml id';
	}
	
	function pre_show_form()
	{
		$my_xml_id = $this->controller->get_form_data('xml_id');
		//echo '<p>' . $my_xml_id . ' is the xml id for this import.</p>';
		
		$cache = new ReasonObjectCache($my_xml_id);
		$import =& $cache->fetch();
		$count = count($import->job_queue);
		echo '<p>This import involves ' . $count .' actions.</p>';
		echo '<p>Click continue to perform the import.</p>';
	}
	
	function process()
	{
		$my_xml_id = $this->controller->get_form_data('xml_id');
		$cache = new ReasonObjectCache($my_xml_id);
		$import =& $cache->fetch();
		$import->run();
		
		// lets save the report in a cache
		$result['report'] = $import->get_report();
		$result['alerts'] = $import->get_alerts();
		$cache = new ReasonObjectCache($my_xml_id . '_result');
		$cache->set($result);
	}
	
	function where_to()
	{
		$redirect = carl_make_redirect(array('_step' => '', 'report' => $this->controller->get_form_data('xml_id') . '_result'));
		return $redirect;
	}
}
?>