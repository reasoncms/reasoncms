<?php
/**
 * @package reason
 * @subpackage content_managers
 */
/**
 * Register content manager with Reason
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'newsEmbedHandler';

/**
 * Content manager for news/post embed handlers
 */
class newsEmbedHandler extends ContentManager
{
	function alter_data()
	{
		if(!reason_user_has_privs($this->admin_page->user_id, 'manage_embed_handlers'))
		{
			$this->show_form = false;
		}
	}
	function no_show_form()
	{
		return '(Creation and editing of embed handlers is something only Reason administrators can do.)';
	}
}