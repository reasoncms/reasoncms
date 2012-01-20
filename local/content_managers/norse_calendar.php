<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'NorseCalendar';

	/**
	 * A content manager for text blurbs
	 */
	class NorseCalendar extends ContentManager
	{
		function run_error_checks()
		{
			$name_field = str_replace(' ','',$this->get_value('name'));
			$name_array = explode(',',$name_field);
			
			if (sizeof($name_array) > 10)
			{
				$this->set_error('name', 'Please enter 10 or less usernames');
			}
			
		
		}
		
		function alter_data()
		{
			$this->set_display_name('name', 'Norsekey Username');
			$this->set_comments('name', form_comment('Enter up to 10 comma-delimited usernames or calendar IDs. Calendars must be publicly viewable.<br>(Examples: smitst01, bjerkr01, luther.edu_pca88a341agcrepmhe9faiecfg@group.calendar.google.com)'));
			$this->add_required('default_view');
			$this->change_element_type('default_view', 'radio_inline');
			
		/*
	$this->set_order(
				array(
					'name',
					'twitter_username',
					'twitter_posts',
				)
		);
*/
		}
	}
?>
