<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.4_to_4.5']['template_update_notice'] = 'ReasonUpgrader_45_TemplateUpdateNotice';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_45_TemplateUpdateNotice extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '<h3>Add a line of PHP to your custom templates</h3>'."\n";
		$str .= '<p>If any of your custom templates overload the start_page() function, add this line of PHP:</p>'."\n";
		$str .= '<p><code>$this->add_extra_head_content_structured();</code></p>'."\n";
		$str .= '<p>Immediately above this line, which should already be in the template:</p>'."\n";
		$str .= '<p><code>echo $this->head_items->get_head_item_markup();</code></p>'."\n";
		return $str;
	}
}
?>