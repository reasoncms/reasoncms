<?php
/**
 * A content manager for admin link entities
 * @package reason
 * @subpackage content_managers
 */
 
 /**
  * Define the class name so that the admin page can use this content manager
  */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'adminLinkManager';

/**
 * A content manager for admin link entities
 *
 * this content manager transparently fixes the situation where an admin link is specified as being
 * relative to the reason_http_base, but the url entered begins with a slash ... this strips the slash,
 * thus preserving the consistency of the way admin links are stored in the database but without bothering
 * the user. at least this is the intention.
 */
	class adminLinkManager extends ContentManager
	{
		function process()
		{
			if ($this->get_value('relative_to_reason_http_base') == 'true')
			{
				if (substr($this->get_value('url'), 0, 1) == '/')
				{
					$new_value = substr($this->get_value('url'), 1);
					$this->set_value('url', $new_value);
				}
			}
			parent::process();
		}
	}
?>
			
