<?php
/**
 * List Filter Display
 * @package reason
 * @subpackage filter_displayers
 */
/**
 * Include parent class & register filter displayer with Reason
 */
reason_include_once('minisite_templates/modules/filter_displayers/listnav.php');
reason_include_once('function_libraries/feed_utils.php');

$GLOBALS['_reason_filter_displayers'][basename(__FILE__)] = 'listNavWithSubscribeFilterDisplay';

/**
 * A filter displayer that lists items as links rather than using select elements
 */
class listNavWithSubscribeFilterDisplay extends listNavFilterDisplay
{
	/**
	 * Assemble the markup for a particular filter selector
	 * @return string
	 * @access private
	 */
	function _build_filter_set($key)
	{
		$ret = parent::_build_filter_set($key);
		if (isset($this->module_ref) && method_exists($this->module_ref, 'get_feed_url'))
		{
			$url = $this->module_ref->get_feed_url();
			$ret .= '<div class="feedSubscribe">'.make_feed_link($url).'</div>';
		}
		return $ret;
	}
}
?>
