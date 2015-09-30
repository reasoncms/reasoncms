<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once( DISCO_INC . 'disco.php');
	
	/**
	 * Batch Deletion administrative module
	 *
	 * @author Matt Ryan
	 */
	
	class BatchDeleteModule extends DefaultModule // {{{
	{
		var $_items;
		var $_ok_to_run = true;
		var $_not_ok_message = '';
		var $_type;
		
		function BatchDeleteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * @return void
		 */
		function init()
		{
			parent::init();
			
			if(!reason_user_has_privs($this->admin_page->user_id, 'delete' ))
			{
				$this->_ok_to_run = false;
				$this->_not_ok_message = 'Sorry; you don\'t have the privileges to delete items on this site.';
			}
			elseif(empty($this->admin_page->site_id))
			{
				$this->_ok_to_run = false;
				$this->_not_ok_message = 'Sorry; you need to specify a site before batch deleting items.';
			}
			elseif(empty($this->admin_page->type_id))
			{
				$this->_ok_to_run = false;
				$this->_not_ok_message = 'Sorry; you need to specify a type before batch deleting items.';
			}
			
			if($this->_ok_to_run)
			{
				$this->_type = new entity($this->admin_page->type_id);
				$this->admin_page->title = 'Batch Delete ' . $this->_type->get_value('plural_name');
				
				$es = new entity_selector( $this->admin_page->site_id);
				$es->add_type($this->admin_page->type_id);
				$es->set_sharing( 'owns' );
				$es->set_order('entity.last_modified DESC');
				// pray($this->admin_page->request);
				if(isset($this->admin_page->request['state']) && $this->admin_page->request['state'] == 'pending')
					$status = 'Pending';
				else
					$status = 'Live';
				$this->_items = $es->run_one('',$status);
				foreach(array_keys($this->_items) as $id)
				{
					if(!$this->admin_page->is_deletable($id))
					{
						unset($this->_items[$id]);
					}
				}
			}
		}
		
		/**
		 * @return void
		 */
		function run() // {{{
		{
			if(!$this->_ok_to_run)
			{
				echo '<p>'.$this->_not_ok_message.'</p>'."\n";
			}
			else
			{
				$d = new batchDeleteDisco();
				$d->set_items($this->_items);
				$d->set_user_id($this->admin_page->user_id);
				echo '<div class="sortPostsModule">'."\n";
				$d->run();
				echo '</div>'."\n";
			}
		}
		
	} // }}}
	
	class batchDeleteDisco extends disco
	{
		var $_items = array();
		var $_user_id;
		var $_changes_made = false;
		var $actions = array('delete'=>'Delete Items');
		
		function set_items($items)
		{
			$this->_items = $items;
			foreach($items as $id=>$item)
			{
				$this->add_element($id, 'checkbox');
				$this->set_display_name($id, $item->get_display_name());
			}
		}
		function set_user_id($user_id)
		{
			$this->_user_id = $user_id;
		}
		function process() // {{{
		{
			foreach($this->_items as $id=>$item)
			{
				if($this->get_value($id) == 'true')
				{
					reason_update_entity( $id, $this->_user_id, array('state'=>'Deleted'));
					$this->remove_element($id);
					$this->_changes_made = true;
				}
			}
		} // }}}
		function pre_show_form() // {{{
		{
			if($this->_changes_made)
			{
				echo '<h3>Entities deleted</h3>'."\n";
			}
			echo '<p class="smallText"><a href="'.carl_make_link( array('cur_module'=>'Lister') ).'">Done deleting items</a></p>'."\n";
		} // }}}
	}
?>