<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/borrow_this.php');
	include_once( DISCO_INC . 'disco.php');
	
	/**
	 * An administrative module that allows a use to select a site to borrow a given item into
	 */
	class BorrowThisModule extends DefaultModule
	{
		/**
		 * The item, if it is in fact borrowable
		 *
		 * Use get_borrowable_item() to access
		 *
		 * @var entity object
		 */
		protected $borrowable_item;
		/**
		 * The site that the item was borrowed into
		 *
		 * @var integer site id
		 */
		protected $borrowed_to;
		/**
		 * The sites that are available to borrow the item into
		 *
		 * Use get_available_sites() to access
		 *
		 * @var array sites available for borrowing item
		 */
		protected $available_sites = array();
		/**
		 * The sites that are already borrowing the item
		 *
		 * Use get_borrowing_sites() to access
		 *
		 * @var array sites already borrowing item
		 */
		protected $borrowing_sites;
		/**
		 * Constructor
		 * @param object admin page
		 * @return void
		 */
		function BorrowThisModule( &$page )
		{
			$this->admin_page =& $page;
		}
		/**
		 * Initialize the module
		 * @return void
		 */
		function init()
		{
			$this->admin_page->title = 'Borrow';
			if($item = $this->get_borrowable_item())
			{
				$type_id = $item->get_value('type');
				$type = new entity($type_id);
				if($type->get_values())
					$this->admin_page->title .= ' '.$type->get_value('name');
				$this->admin_page->title .= ': ';
				$this->admin_page->title .= $item->get_value('name');
			}
			else
			{
				$this->admin_page->title .= ' Item';
			}
		}
		/**
		 * Run the module & produce output
		 * @return void
		 */
		function run()
		{
			$item = $this->get_borrowable_item();
			if(NULL === $item)
			{
				echo 'No Reason ID provided for borrowing.';
				return;
			}
			if(false === $item)
			{
				echo 'Sorry, the item is not able to be borrowed due to restrictions placed by the site maintainer.';
				return;
			}
			
			$all_borrowing_sites = $this->get_borrowing_sites($item);
			$borrowing_ids = array();
			
			$site_options = array();
			foreach($this->get_available_sites($item) as $site)
			{
				$site_options[$site->id()] = $site->get_value('name');
				if(isset($all_borrowing_sites[$site->id()]))
				{
					$site_options[$site->id()] .= ' [Already borrowing this item]';
					$borrowing_ids[] = $site->id();
				}
			}
			
			if(empty($site_options))
			{
				echo '<p>Sorry, you don\'t have access to any sites that can borrow this item.</p>';
				echo '<p>If you would like to have your site(s) set up so they can borrow this item, please <a href="mailto:'.WEBMASTER_EMAIL_ADDRESS.'">contact a Reason administrator</a>.</p>';
				return;
			}
			
			$d = new Disco();
			$d->set_box_class( 'StackedBox' );
			$d->add_element('site', 'select', array('options' => $site_options, 'disabled_options' => $borrowing_ids));
			$d->set_display_name('site','Into which site would you like to borrow this item?');
			$d->add_comments('site',form_comment('If you don\'t see one of your sites in this list, it is limited by policy from borrowing this item. Please <a href="mailto:'.WEBMASTER_EMAIL_ADDRESS.'">contact a Reason administrator</a> for assistance.'));
			$d->add_required('site');
			$d->set_actions(array('borrow'=>'Borrow This Item'));
			$d->add_callback(array($this,'process_borrow_form'), 'process');
			$d->run();
			
			if(!empty($this->borrowed_to))
			{
				echo '<p><strong>'.$item->get_value('name').'</strong> is now borrowed by '.$site_options[$this->borrowed_to].'</p>';
				echo '<p><a href="'.htmlspecialchars(get_current_url()).'">Borrow this item into another site</a></p>';
			}
			if(!empty($this->admin_page->request['return_to']))
			{
				
				echo '<p><a href="'.htmlspecialchars($this->admin_page->request['return_to']).'">Return to browsing</a></p>';
			}
			
		}
		/**
		 * Get the item, if it is in fact borrowable
		 * @return mixed NULL if no borrow_id provided in the query string, boolean false if an item is specified but is not borrowable, or an entity object if a valid, borrowable entity is specified.
		 */
		function get_borrowable_item()
		{
			if(!isset($this->borrowable_item))
			{
				if(empty($this->admin_page->request['borrow_id']))
					$this->borrowable_item = NULL;
				else
				{
					$id = (integer) $this->admin_page->request['borrow_id'];
					if(!BorrowThis::borrowable($id))
						$this->borrowable_item = false;
					else
						$this->borrowable_item = new entity($id);
				}
			}
			return $this->borrowable_item;
		}
		/**
		 * Get the sites that can borrow a given item
		 * @return array of site entities
		 */
		function get_available_sites($item)
		{
			if(!isset($this->available_sites[$item->id()]))
			{
				$owner_site_id = get_owner_site_id( $item->id() );
				if(empty($owner_site_id))
					return array();
				$owner_site = new entity($owner_site_id);
				$sites = $this->admin_page->get_sites();
				if(isset($sites[$owner_site_id]))
					unset($sites[$owner_site_id]);
				$es = new entity_selector();
				$es->add_type(id_of('site'));
				$es->add_relation('`entity`.`id` IN ("'.implode('","', array_keys($sites)).'")');
				if($owner_site->get_value('site_state') != 'Live')
					$es->add_relation('`site_state` != "Live"');
				$es->add_left_relationship($item->get_value('type'), relationship_id_of('site_to_type'));
				$this->available_sites[$item->id()] = $es->run_one();
			}
			return $this->available_sites[$item->id()];
		}
		/**
		 * Get the sites that are currently borrowing a given item
		 * @return array of site entities
		 */
		function get_borrowing_sites($item)
		{
			if(!isset($this->borrowing_sites))
			{
				$this->borrowing_sites = get_sites_that_are_borrowing_entity($item->id());
			}
			return $this->borrowing_sites;
		}
		/**
		 * Perform the borrowing action upon form submission
		 * @return void
		 */
		function process_borrow_form($d)
		{
			$site_id = (integer) $d->get_value('site');
			$item = $this->get_borrowable_item();
			$sites = $this->get_available_sites($item);
			$borrowing_sites = $this->get_borrowing_sites($item);
			
			if(isset($sites[$site_id]) && !isset($borrowing_sites[$site_id]))
			{
				$rel_id = get_borrows_relationship_id($item->get_value('type'));
				if(empty($rel_id))
				{
					trigger_error('Unable to find borrowing rel for type '. $type_id, HIGH);
					die();
				}
				if(create_relationship( $site_id, $item->id(), $rel_id))
				{
					$this->borrowed_to = $site_id;
					$d->show_form = false;
				}
			}
		}
	}