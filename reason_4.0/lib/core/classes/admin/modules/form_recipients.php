<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once(DISCO_INC.'disco.php');
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	
/**
 * Export Reason data
 */
class FormRecipientsModule extends DefaultModule
{
	function EntityInfoModule( &$page )
	{
		$this->admin_page =& $page;
	} // }}}
	function init() // {{{
	{
		$this->admin_page->title = 'Form Recipients';
	} // }}}
	function run() // {{{
	{
		if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
		{
			echo '<p>Sorry; use of this module is restricted.</p>'."\n";
			return;
		}
		
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$sites = $es->run_one();
		
		$options = array();
		foreach($sites as $id=>$site)
		{
			$options[$id] = $site->get_value('name');
		}
		
		$d = new disco();
		$d->add_element('site','select',array('options'=>$options, 'empty_value_label' => 'All Sites'));
		$d->add_element('resolve_to_email_addresses', 'checkboxgroup', array('options' => array('yes'=>'Yes')));
		$d->set_actions(array('submit'=>'Get Form Recipients'));
		$d->run();
		
		if($d->successfully_submitted())
		{
			$es = new entity_selector();
			$es->add_type(id_of('form'));
			$es->add_relation('`email_of_recipient` != ""');
			$site_id = (integer) $d->get_value('site');
			if($site_id)
				$es->set_site($site_id);
			$forms = $es->run_one();
			$recipients = $this->get_form_recipients($forms);
			if(!empty($recipients))
			{
				echo '<p>Forms found have these recipients:</p>';
				
				if($d->get_value('resolve_to_email_addresses'))
					$items = $this->get_email_addresses($recipients);
				else
					$items = $recipients;
				echo '<textarea>'.implode(', ',$items).'</textarea>';
			}
			else
			{
				echo '<p>There were no forms found with email recipients</p>';
			}
		}
	}
	function get_form_recipients($forms)
	{
		$recipients = array();
		foreach($forms as $form)
		{
			$recipients = array_merge($recipients, array_map('trim',explode(',',$form->get_value('email_of_recipient'))));
		}
		return array_unique($recipients);
	}
	function get_email_addresses($recipients)
	{
		$emails = array();
		$lookup = array();
		foreach($recipients as $recipient)
		{
			if(strpos($recipient,'@'))
			{
				$emails[] = $recipient;
			}
			else
			{
				$lookup[] = $recipient;
			}
		}
		if(!empty($lookup))
		{
			$dir = new directory_service();
			$dir->search_by_attribute('ds_username', $lookup, array('ds_username','ds_email'));
			$people = $dir->get_records();
			foreach($people as $person)
			{
				if(!empty($person['ds_email'][0]))
					$emails[] = $person['ds_email'][0];
			}
		}
		return array_unique($emails);
	}
}