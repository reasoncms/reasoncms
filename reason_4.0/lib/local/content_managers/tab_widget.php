<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'LutherTabWidget';

	/**
	 * A content manager for tab widgets
	 */
	class LutherTabWidget extends ContentManager
	{
		function alter_data()
		{
			$this->set_display_name('tab_widget_description', 'Description');
			$this->set_display_name('tab_widget_title_1', 'Tab 1 Title');
			$this->set_display_name('tab_widget_title_2', 'Tab 2 Title');
			$this->set_display_name('tab_widget_title_3', 'Tab 3 Title');
			$this->set_display_name('tab_widget_title_4', 'Tab 4 Title');
			$this->set_display_name('tab_widget_title_5', 'Tab 5 Title');
			$this->set_display_name('tab_widget_content_1', 'Tab 1 Content');
			$this->set_display_name('tab_widget_content_2', 'Tab 2 Content');
			$this->set_display_name('tab_widget_content_3', 'Tab 3 Content');
			$this->set_display_name('tab_widget_content_4', 'Tab 4 Content');
			$this->set_display_name('tab_widget_content_5', 'Tab 5 Content');
			$this->set_comments('tab_widget_content_1', form_comment('Enter html markup for content.'));
			$this->add_required('tab_widget_title_1');
			$this->add_required('tab_widget_content_1');

			$this->set_order(
				array(
					'name',
					'unique_name',
					'tab_widget_description',
					'tab_widget_title_1',
					'tab_widget_content_1',
					'tab_widget_title_2',
					'tab_widget_content_2',
					'tab_widget_title_3',
					'tab_widget_content_3',
					'tab_widget_title_4',
					'tab_widget_content_4',
					'tab_widget_title_5',
					'tab_widget_content_5',
				)
			);
		}
		
		function process()
		{

			parent::process();
		}
	}
?>