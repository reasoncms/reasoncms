<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('function_libraries/user_functions.php');
include_once( DISCO_INC . 'disco.php');

/**
 * Allows batch deletion of registration slot data.
 * 
 * We reserve this right now for people with master admin access. If we make the UI a little nicer we could open it up to site administrators.
 *
 * @author Nathan White
 */

class DeleteRegistrationSlotDataModule extends DefaultModule // {{{
{
	function DeleteRegistrationSlotDataModule( &$page )
	{
		$this->admin_page =& $page;
	}
	
	/**
	 * Verify that we are running on a site.
	 *
	 * @return void
	 */
	function init()
	{
		parent::init();
		$this->admin_page->title = 'Delete Registration Slot Data';
	}
	
	/**
	 * Check if the current site has the registration slot type.
	 */
	function site_has_slot_type()
	{
		if (!isset($this->_site_has_slot_type))
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($this->admin_page->site_id,relationship_id_of('site_to_type'));
			$es->add_relation('entity.id = "'.id_of('registration_slot_type').'"');
			$es->set_num(1);
			$result = $es->run_one();
			$this->_site_has_slot_type = (!empty($result));
		}
		return $this->_site_has_slot_type;
	}	
	
	/**
	 * @return mixed array of slot data OR false.
	 */
	function get_site_slots_with_data()
	{
		if (!isset($this->_site_slots_with_data))
		{
			$es = new entity_selector($this->admin_page->site_id);
			$es->add_type(id_of('registration_slot_type'));
			$es->add_relation('( (registrant_data <> "") AND (registrant_data IS NOT NULL) )');
			$result = $es->run_one();
			if (!empty($result))
			{
				foreach ($result as $k=>$v)
				{
					$this->_site_slots_with_data[$k] = $v->get_value('name');
				}
			}
			else $this->_site_slots_with_data = FALSE;
		}
		return $this->_site_slots_with_data;
	}
	
	/**
	 * @return void
	 */
	function run()
	{
		echo '<div class="deleteSlotDataModule">'."\n";
		if (!user_can_edit_site($this->admin_page->user_id, id_of('master_admin')))
		{
			echo '<p>You need to have master admin access to use this tool.</p>';
		}
		elseif (empty($this->admin_page->site_id))
		{
			echo '<p>You need to have a site selected to use this module.</p>';
		}
		elseif (!$this->site_has_slot_type())
		{
			echo '<p>This site doesn\'t have the registration slot type.</p>';
		}
		elseif ($slots_with_data = $this->get_site_slots_with_data())
		{
			$d = new Disco();
			$d->add_element('comment', 'comment', array('text' => '<p>Registrant data will be erased from all checked slots immediately upon submit.</p>'));
			$d->add_element('slots_with_data', 'checkboxgroup', array('options' => $slots_with_data));
			$d->add_callback(array($this,'delete_slot_data_process'),'process');
			$d->add_callback(array($this,'delete_slot_data_where_to'),'where_to');
			$d->actions = array('Delete registrant data from checked slots');
			$d->set_value('slots_with_data', array_keys($slots_with_data));
			$d->run();
		}
		else
		{
			echo '<p>The site doesn\'t have any registration slots with registrant data to delete.</p>';
		}
		echo '</div>'."\n";
	}
	
	/**
	 * Delete the registrant_data for checked slots.
	 */
	function delete_slot_data_process( $disco )
	{
		$slots = $disco->get_value('slots_with_data');
		foreach($slots as $id)
		{
			$e = new entity($id);
			if (reason_is_entity($e, 'registration_slot_type'))
			{
				$values['registrant_data'] = '';
				reason_update_entity($e->id(), $this->admin_page->user_id, $values, false);
			}
		}
	}
	
	/**
	 * Redirect back to the page without the action params.
	 */
	function delete_slot_data_where_to( $disco )
	{
		return carl_make_redirect(array());
	}
}
?>