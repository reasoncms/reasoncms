<?php
reason_include_once('classes/media/interfaces/media_work_content_manager_modifier_interface.php');

/**
 * A class that modifies the given Media Work content manager for non-integrated media works.
 */
class DefaultMediaWorkContentManagerModifier implements MediaWorkContentManagerModifierInterface
{
	/**
	 * The content manager this modifier class will modify.
	 */
	protected $manager;

	function set_content_manager($manager)
	{
		$this->manager = $manager;
		$manager->recognized_extensions = array();
	}
	
	function set_head_items($head_items)
	{}
	
	/**
	 * Called in content manager's alter_data().
	 */
	function alter_data()
	{		
		$this->manager->set_display_name ('name', 'Title');
		$this->manager->set_display_name ('datetime', 'Date Originally Recorded/Created');
		$this->manager->add_comments ('datetime', form_comment('The date this work was made or released'));
		$this->manager->add_comments ('description', form_comment('The brief description that will appear with the work'));

		$editor_name = html_editor_name($this->manager->admin_page->site_id);
		$wysiwyg_settings = html_editor_params($this->manager->admin_page->site_id, $this->manager->admin_page->user_id);
		$min_wysiwyg_settings = $wysiwyg_settings;
		if(strpos($editor_name,'loki') === 0)
		{
			$min_wysiwyg_settings['widgets'] = array('strong','em','linebreak','lists','link');
			if(reason_user_has_privs( $this->manager->admin_page->user_id, 'edit_html' ))
			{
				$min_wysiwyg_settings['widgets'][] = 'source';
			}
		}
		$this->manager->change_element_type( 'description' , $editor_name , $min_wysiwyg_settings );
		$this->manager->set_element_properties( 'description', array('rows'=>5));
		
		$this->manager->change_element_type( 'content' , $editor_name , $wysiwyg_settings );
		$this->manager->set_element_properties('content', array('rows'=>12));
		$this->manager->add_comments ('content', form_comment('Full content, such as a transcript of the media work. You can leave this blank if you don\'t have time to transcribe the content of the work.'));
		$this->manager->set_display_name ('content', 'Transcript');
		
		$this->manager->change_element_type( 'rights_statement' , $editor_name , $min_wysiwyg_settings );
		$this->manager->set_element_properties('rights_statement', array('rows'=>3));
		$this->manager->add_comments ('rights_statement', form_comment('e.g. "Some rights reserved. '.FULL_ORGANIZATION_NAME.' licenses this work under the <a href="http://creativecommons.org/licenses/by/2.5/">Creative Commons Attribution 2.5 License</a>." or "Copyright Margaret Smith, 1983. All rights reserved. Used with permission." You may leave this field blank if you are not sure about what license applies to this work.'));
		
		$this->manager->set_display_name ('show_hide', 'Show or Hide?');
		$this->manager->change_element_type( 'show_hide', 'radio_no_sort', array('options'=>array('show'=>'Show this work on the public site','hide'=>'Hide this work from the public site')));
		$this->manager->add_required ('show_hide');
		$show_hide_val = $this->manager->get_value('show_hide');
		if (empty($show_hide_val)) $this->manager->set_value('show_hide', 'show');
		
		$this->manager->set_display_name ('author', 'Creator');
		$this->manager->add_comments ('author', form_comment('The person or entity who made this work (e.g. director/producer)'));
		
		$this->manager->add_comments ('keywords', form_comment('Help others find this by entering the key terms and ideas presented in this work.'));
		
		$this->manager->add_comments ('transcript_status', form_comment('Choose "Published" when the transcript is finalized'));
		
		if($this->manager->get_value('media_publication_datetime') && $this->manager->get_value('media_publication_datetime') != '0000-00-00 00:00:00')
		{
			$this->manager->change_element_type('media_publication_datetime','solidText',array('display_name'=>'Published'));
		}
		if (!empty($this->manager->fields_to_remove))
		{
			foreach ($this->manager->fields_to_remove as $field)
			{
				$this->manager->remove_element($field);
			}
		}
		$this->manager->set_order($this->manager->field_order);
		
		$this->manager->change_element_type('tmp_file_name', 'protected');
		
		// Hide all of the integrated-related fields in the form
		$this->manager->change_element_type('entry_id', 'protected');
		$this->manager->change_element_type('av_type', 'protected');
		$this->manager->change_element_type('media_duration', 'protected');
		$this->manager->change_element_type('transcoding_status', 'protected');
		$this->manager->change_element_type('integration_library', 'protected');
		$this->manager->change_element_type('email_notification', 'protected');
		$this->manager->change_element_type('show_embed', 'protected');
		$this->manager->change_element_type('show_download', 'protected');
		$this->manager->change_element_type('salt', 'cloaked');
		$this->manager->change_element_type('original_filename', 'protected');
		
		reason_include_once( 'classes/media/default/displayer_chrome/default.php' );
		$displayer_chrome = new DefaultDefaultDisplayerChrome();
		$displayer_chrome->set_media_work(new entity($this->manager->get_value('id')));
		$displayer_chrome->set_media_height('small');
		$html_markup = $displayer_chrome->get_html_markup();
		
		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($this->manager->get_value('id'), relationship_id_of('av_to_av_file'));
		$results = $es->run_one();
		
		if (count($results) > 0)
		{
			$this->manager->add_element( 'file_preview' , 'commentWithLabel' , array('text'=>$html_markup));
			$this->manager->set_order(array('file_preview'));
		}
	}
	
	/**
	 * Callback for manager's process().
	 */
	function process()
	{
		$this->manager->add_callback(array($this, '_process_callback'), 'process');
	}
	
	function _process_callback()
	{
		$old_entity = new entity($this->manager->get_value('id'));
		if($this->manager->get_value('show_hide') == 'show' && ($old_entity->get_value('show_hide') == 'hide' || !$old_entity->get_value('show_hide') ) )
		{
			$this->manager->set_value('media_publication_datetime',date('Y-m-d H:i:s'));
		}
		elseif($this->manager->get_value('show_hide') == 'hide' && $old_entity->get_value('media_publication_datetime'))
		{
			$this->manager->set_value('media_publication_datetime','');
		}		
	}
	
	function run_error_checks() 
	{}
	
}
?>