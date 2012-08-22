<?php
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/default.php' );
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/user_functions.php' );

	/**
	* Register module with Reason
	*/
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherBreadcrumbsModule';
	
	class LutherBreadcrumbsModule extends DefaultMinisiteModule
	{
		/**
		 * Tells the template that this module always contains content
		 */
		function has_content() 
		{
			return true;
		}
		
		function run() 
		{
			echo '<nav id="breadcrumbs">'."\n";

			$b = $this->parent->_get_breadcrumb_markup($this->parent->_get_breadcrumbs(), $this->parent->site_info->get_value('base_breadcrumbs'), '&nbsp;&#187;&nbsp;');
			
			$url = get_current_url();
			
			$is_story = preg_match("/[&?]story_id=\d+/", $url);
			$is_event = preg_match("/[&?]event_id=\d+/", $url);
			$is_issue = preg_match("/[&?]issue_id=\d+/", $url);
			
			if ($is_story // publication inserts link to story as well as the story itself so remove the link
				|| $is_event) // event does too	
			{
				$ba = explode('&nbsp;&#187;&nbsp;', $b);
				if ($is_issue)
				{
					array_splice($ba, -4, 3);
				}
				else
				{
					array_splice($ba, -2, 1);
				}
				$b = implode('&nbsp;&#187;&nbsp;', $ba);
			}
			$b = preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\1", $b);
			echo $b;
			echo '</nav>'."\n";
		}
	}
?>
