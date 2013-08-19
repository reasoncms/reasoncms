<?php
/**
 * This reports on the number of actions it will take and asks for confirmation.
 *
 * @package reason
 * @subpackage scripts
 * @author Nathan White
 */
 
/**
 * Dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/object_cache.php');

class ConfirmForm extends FormStep
{	
	var $display_name = 'Wordpress Import - Confirm';
	
	/**
	 * Lets remove / alter config options that are not relevant.
	 */
	function on_every_time()
	{
		$my_xml_id = $this->controller->get_form_data('xml_id');
		if (empty($my_xml_id))
		{
			$this->show_form = false;
			echo '<p>This step does not have the necessary information.</p>';
			echo '<a href="' . REASON_HTTP_BASE_PATH . '/scripts/import/wordpress/">Start over</a>';
		}
	}
	
	function pre_show_form()
	{
		$my_xml_id = $this->controller->get_form_data('xml_id');
		if ($my_xml_id)
		{
			$cache = new ReasonObjectCache($my_xml_id);
			$import =& $cache->fetch();
			$count = $import->get_job_count();
			echo '<p>This import involves ' . $count .' actions.</p>';
			echo '<p>Click confirm to perform the import.</p>';
		}
	}
	
	function process()
	{
		$my_xml_id = $this->controller->get_form_data('xml_id');
		$cache = new ReasonObjectCache($my_xml_id);
		$import =& $cache->fetch();
		$import->run_job();
		
		// lets save the report in a cache
		$result['report'] = $import->get_report();
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