<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'theme_previewer';

	/**
	 * A minisite previewer for Reason themes
	 */
	class theme_previewer extends default_previewer
	{
		function _get_rel_list_display_name($entity,$rel_name,$direction)
		{
			if($rel_name == 'site_to_theme' && $direction == 'right')
			{
				return $entity->get_display_name().' (<a href="'.$entity->get_value('base_url').'" target="_blank">public site</a>)';
			}
			return parent::_get_rel_list_display_name($entity,$rel_name,$direction);
		}
	}
?>