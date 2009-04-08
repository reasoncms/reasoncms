<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'job_handler';

	/**
	 * Content manager for jobs (i.e. emnployment opportunities)
	 */
	class job_handler extends ContentManager // {{{
	{
		function alter_data() // {{{
		{
			$site = new entity( $this->get_value( 'site_id' ) );
			if($site->get_value('unique_name') == 'sfs_site' && !$this->get_value('job_category'))
			{
				$this->set_value('job_category', 'Student');
			}
			elseif($site->get_value('unique_name') == 'human_resources_office_site' && !$this->get_value('job_category'))
			{
				$this->set_value('job_category', 'Staff');
			}
			elseif($site->get_value('unique_name') == 'dean_of_the_college_office' && !$this->get_value('job_category'))
			{
				$this->set_value('job_category', 'Faculty');
			}

			if($this->get_value('job_category') != 'Student')
			{
				//hide fields applicable to student jobs
				$this->change_element_type('supervisor', 'hidden');
				$this->change_element_type('term_job', 'hidden');
				$this->change_element_type('break_job', 'hidden');
				$this->change_element_type('dept_charge_number', 'hidden');
				$this->remove_element( 'author' );
			}
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			$this->add_element('hr1', 'hr');
			$this->add_element('hr2', 'hr');
			$this->add_element('hr3', 'hr');
			$this->set_display_name( 'name','Title' );
			$this->set_display_name( 'job_category', 'Position Type' );
			$this->set_display_name( 'position_start','Position starts on' );
			$this->set_comments( 'position_start',form_comment( 'mm/dd/yyyy' ) );
			$this->set_display_name( 'content','Position Information' );
			$this->set_comments( 'content',form_comment( 'This should include things like the purpose of the position, an overview, the responsibilities, hours required, minimum qualifications, desired qualities, contact information, and other notes.' ) );
			$this->set_display_name( 'duration','How long should job be posted?' );
			$this->set_display_name( 'posting_start','Posting starts on' );
			$this->set_comments( 'posting_start',form_comment( 'mm/dd/yyyy' ) );
			$this->set_comments( 'duration',form_comment( 'Number of days' ) );
			$this->set_display_name( 'show_hide','Show or hide posting?' );
			if(!$this->get_value('show_hide'))
			{
				$this->set_value('show_hide', 'show');
			}
			$this->add_required('tenure_track');
			$this->add_required('posting_start');
			$this->add_required('duration');
			$this->add_required('show_hide');
			$this->add_required('content');
			$this->add_required('job_category');
			
			if(!$this->get_value('tenure_track'))
			{
				$this->set_value('tenure_track', 'no');
			}
			if($site->get_value('unique_name') == 'human_resources_office_site')
			{
				$this->change_element_type( 'tenure_track', 'hidden');
			}
			elseif($site->get_value('unique_name') == 'dean_of_the_college_office')
			{
				$this->change_element_type( 'office', 'hidden');
			}

			$this->set_order( array( 'name','title_extension','office','position_start', 'job_category', 'hr1','content','tenure_track','hr2','posting_start','duration','show_hide','hr3' ) );
			
			
			if($this->get_value('job_category') == 'Student')
			{
				$this->add_element('comment1', 'comment', array('text'=>'Position is available:'));
		
				$this->change_element_type('title_extension', 'hidden');
				$this->change_element_type('tenure_track', 'hidden');

				$this->set_display_name('author', 'Post Submitted By');
				$this->set_display_name('name', 'Position Title');
				$this->set_display_name('office', 'Department or Office');
				$this->set_display_name('term_job', 'During Term');
				$this->set_display_name('break_job', 'During Break');
				$this->set_display_name('content', 'Description');
				$this->set_display_name('dept_charge_number', 'Department Charge Number');
				
				if(!$this->get_value('duration'))
				{
					$this->set_value('duration', '30');
				}
				
				if(!$this->get_value('posting_start')){
					$this->set_value('posting_start', time());
				}
				
				$this->set_order( array(
					'name', 
					'office',
					'dept_charge_number', 
					'supervisor',
					'job_category',
					'content',
					'hr1',
					'position_start',
					'comment1',
					'term_job',
					'break_job',
					'hr2',
					'posting_start',
					'duration', 
					'show_hide',
					'author',
					'hr3', 
					'unique_name'));
			}
	
		} // }}}
	} // }}}
?>
