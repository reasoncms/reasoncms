<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
include_once(DISCO_INC . 'boxes/stacked.php');

/**
 * Register form with Reason
 */
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'PollThorForm';

/**
 * PollThorForm is a view for the Polls
 * @author Matt Ryan
 */

class PollThorForm extends DefaultThorForm
{
   /**
	* Class name of the box object to use.
	* @var string
	*/
	var $box_class = 'StackedBox';
	
	function on_every_time()
	{
		parent::on_every_time();
		$this->set_form_class('StackedBox');
		//$this->add_element('show_results','hidden');
		//$this->set_value('show_results', 1);
	}
	
	function where_to()
	{
		$model =& $this->get_model();
		return carl_make_redirect(array('submission_key' => $model->create_form_submission_key(), 'show_results' => '1'));
	}
}
?>
