<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'av_handler';

	class av_handler extends ContentManager
	{
		function alter_data() {
			$this -> set_display_name ('name', 'Title');
			$this -> set_display_name ('datetime', 'Date Originally Recorded/Created');
			$this -> add_comments ('datetime', form_comment('The date this work was made or released'));
			$this -> add_comments ('description', form_comment('The brief description that will appear with the work'));
			$this -> add_comments ('content', form_comment('Full content, such as a transcript of the media work. You can leave this blank if you don\'t have time to transcribe the content of the work.'));

			$editor_name = html_editor_name($this->admin_page->site_id);
			$wysiwyg_settings = html_editor_params($this->admin_page->site_id, $this->admin_page->user_id);
			$min_wysiwyg_settings = $wysiwyg_settings;
			if(strpos($editor_name,'loki') === 0)
			{
				$min_wysiwyg_settings['widgets'] = array('strong','em','lists','link');
			}
			$this->change_element_type( 'description' , $editor_name , $min_wysiwyg_settings );
			$this->change_element_type( 'content' , $editor_name , $wysiwyg_settings );
			$this->change_element_type( 'rights_statement' , $editor_name , $min_wysiwyg_settings );

			$this -> set_display_name ('content', 'Transcript');
			$this -> set_display_name ('show_hide', 'Show or Hide?');
			$this->change_element_type( 'show_hide', 'radio_no_sort', array('options'=>array('show'=>'Show this work on the public site','hide'=>'Hide this work from the public site')));
			$this -> set_display_name ('author', 'Creator');
			$this -> add_comments ('author', form_comment('The person or entity who made this work (e.g. director/producer)'));
			$this -> add_comments ('rights_statement', form_comment('e.g. "Some rights reserved. '.FULL_ORGANIZATION_NAME.' licenses this work under the <a href="http://creativecommons.org/licenses/by/2.5/">Creative Commons Attribution 2.5 License</a>." or "Copyright Margaret Smith, 1983. All rights reserved. Used with permission." You may leave this field blank if you are not sure about what license applies to this work.'));
			$this -> add_comments ('keywords', form_comment('Help others find this by entering the key terms and ideas presented in this work.'));
			$this -> add_comments ('transcript_status', form_comment('Choose "Published" when the transcript is finalized'));
			if($this->get_value('media_publication_datetime') && $this->get_value('media_publication_datetime') != '0000-00-00 00:00:00')
			{
				$this->change_element_type('media_publication_datetime','solidText',array('display_name'=>'Published'));
			}
			$this -> set_order (array ('name', 'datetime', 'author', 'description', 'keywords', 'content','transcript_status', 'rights_statement', 'show_hide'));
		}
		function process() // {{{
		{
			$old_entity = new entity($this->get_value('id'));
			if($this->get_value('show_hide') == 'show' && ($old_entity->get_value('show_hide') == 'hide' || !$old_entity->get_value('show_hide') ) )
			{
				$this->set_value('media_publication_datetime',date('Y-m-d H:i:s'));
			}
			elseif($this->get_value('show_hide') == 'hide' && $old_entity->get_value('media_publication_datetime'))
			{
				$this->set_value('media_publication_datetime','');
			}
			parent::process();
		} // }}}
	}
?>
