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
	 * An administrative module that allows a use to select a site to borrow a given item into
	 */
	class CopyBorrowingModule extends DefaultModule
	{
		/**
		 * Constructor
		 * @param object admin page
		 * @return void
		 */
		function CopyBorrowingModule( &$page )
		{
			$this->admin_page =& $page;
		}
		/**
		 * Initialize the module
		 * @return void
		 */
		function init()
		{
			$this->admin_page->title = 'Copy Borrowing';
		}
		function get_all_sites()
		{
			static $sites;
			if(!isset($sites))
			{
				$es = new entity_selector(id_of('master_admin'));
				$es->add_type(id_of('site'));
				$sites = $es->run_one();
			}
			return $sites;
		}
		function get_all_types()
		{
			static $types;
			if(!isset($types))
			{
				$es = new entity_selector(id_of('master_admin'));
				$es->add_type(id_of('type'));
				$types = $es->run_one();
			}
			return $types;
		}
		function entities_to_options($entities)
		{
			$ret = array();
			foreach($entities as $e)
			{
				$ret[$e->id()] = $e->get_value('name');
			}
			return $ret;
		}
		/**
		 * Run the module & produce output
		 * @return void
		 */
		function run()
		{
			if ($this->admin_page->site_id != id_of('master_admin'))
			{
				echo 'This tool can be run only in the context of the master admin';
				return;
			}
		
			$site_options = $this->entities_to_options($this->get_all_sites());
			$type_options = $this->entities_to_options($this->get_all_types());
			
			$d = new Disco();
			$d->set_box_class( 'StackedBox' );
			$d->add_element('site_to_copy_from', 'select', array('options' => $site_options));
			
			$d->add_element('site_to_copy_to', 'select', array('options' => $site_options));
			
			$d->add_element('type_to_copy', 'select', array('options' => $type_options));
			$d->add_required('site_to_copy_from');
			$d->add_required('site_to_copy_to');
			$d->add_required('type_to_copy');
			
			$d->set_actions(array('copy'=>'Copy Borrowing Relationships'));
			$d->add_callback(array($this,'process_borrow_form'), 'process');
			$d->run();
			
		}
		
		function process_borrow_form($d)
		{
			$allowable_relationship_id = get_borrows_relationship_id($d->get_value('type_to_copy'));
			
			$es = new entity_selector($d->get_value('site_to_copy_from'));
			$es->add_type($d->get_value('type_to_copy'));
			$es->set_sharing( 'borrows' );
			$es->limit_tables();
			$es->limit_fields();
			$entity_ids = $es->get_ids();
			
			$successes = array();
			$failures = array();
			
			foreach($entity_ids as $entity_id)
			{
				if( create_relationship( $d->get_value('site_to_copy_to'), $entity_id, $allowable_relationship_id) )
				{
					$successes[] = $entity_id;
				}
				else
				{
					$failures = $entity_id;
				}
			}
			
			echo '<h3>Successfully copied over ' . count($successes) .' borrow relationships</h3>';
			echo '<h3>Failed to copy over ' . count($failures) . ' borrow relationships</h3>';
		}
	}