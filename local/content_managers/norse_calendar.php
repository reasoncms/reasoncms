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
		function alter_data()
		{
			$this->set_display_name('name', 'Norsekey Username');
			$this->set_comments('name', form_comment('Enter the username the of Norse Calendar you wish to display.'));
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
