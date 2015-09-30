<?php
/**
 * @package reason
 * @subpackage content_managers
 */
 	/**
	 * Include parent class
	 */
	reason_include_once('content_managers/default.php3');
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'faqManager';

	/**
	 * A content manager for FAQs
	 */
	class faqManager extends ContentManager
	{
		function alter_data()
		{	
			$this->set_comments('name',form_comment('Shorthand; for internal use') );
			$this->set_display_name('datetime','FAQ Added');
			$this->set_comments('datetime',form_comment('mm/dd/yyyy') );
			$this->set_display_name('description','Question');
			$this->set_display_name('content','Answer');
			$this->set_display_name('author','Answer Author');
			$this->add_required('description');
			$this->add_element('audiences_header', 'comment', array('text'=>'<h4>Audiences</h4> What are the intended audiences for this FAQ? (Please enter at least one)'));
			
			
			$this->add_relationship_element('audiences', id_of('audience_type'), 
relationship_id_of('faq_to_audience'),'right','checkbox',REASON_USES_DISTRIBUTED_AUDIENCE_MODEL,'sortable.sort_order ASC');

			$old_audience_fields = array('prospective_students','new_students','students','faculty','staff','alumni','families','public');
			foreach($old_audience_fields as $field)
			{
				if($this->_is_element($field))
					$this->change_element_type($field,'hidden');
			}

			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );

			$this->set_order(
				array(
					'name',
					'description',
					'content',
					'author',
					'datetime',
					'keywords',
					'audiences_header',
					'audiences',
				)
			);
		}
	}
?>
