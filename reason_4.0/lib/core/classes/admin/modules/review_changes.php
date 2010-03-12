<?php
/**
 * An administrative module for reviewing edits and deletions
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	include_once(DISCO_INC.'disco.php');
	
	/**
	 * An administrative module for reviewing edits and deletions
	 *
	 * This module limits its access to users who have the privilege to view sensitive data.
	 */
	class ReasonReviewChangesModule extends DefaultModule// {{{
	{
		/**
		 * Internal list of all Reason types
		 * 
		 * Please use _get_types() instead of directly accessing this array, as it is lazy-loaded.
		 *
		 * @access private
		 */
		var $_types = array();
		
		/**
		 * Internal list of all live Reason site
		 * 
		 * Please use _get_sites() instead of directly accessing this array, as it is lazy-loaded.
		 *
		 * @access private
		 */
		var $_sites = array();
		
		/**
		 * Constructor function
		 *
		 * @access public
		 */
		function ReasonReviewModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		/**
		 * Initialize the module
		 */
		function init() // {{{
		{
			$this->admin_page->title = 'Review Changes';
		} // }}}
		/**
		 * Run the module
		 */
		function run() // {{{
		{
			echo '<div id="reviewChangesModule">'."\n";
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			{
				echo '<p>Sorry; use of this module is restricted.</p></div>'."\n";
				return;
			}
			
			$d = new disco();
			$d->add_element('start_date','textdate', array('prepopulate'=>true,'year_max'=>carl_date('Y'),'year_min'=>'1000'));
			$d->add_required('start_date');
			$d->add_element('end_date','textdate', array('year_max'=>carl_date('Y'),'year_min'=>'1000'));
			$d->add_comments('end_date',form_comment('If no end date given, changes will be shown for just the start date') );
			$d->add_element('type','select',array('options'=>$this->_prep_for_disco($this->_get_types()) ) );
			if(!empty($this->admin_page->request['type_id']))
				$d->set_value('type',$this->admin_page->request['type_id']);
			$d->add_element('site','select',array('options'=>$this->_prep_for_disco($this->_get_sites()) ) );
			if(!empty($this->admin_page->request['site_id']))
				$d->set_value('site',$this->admin_page->request['site_id']);
			$d->set_actions( array('review'=>'Review') );
			
			$d->run();
			
			if($d->successfully_submitted())
			{
				$end_date = $d->get_value('end_date') ? $d->get_value('end_date') : $d->get_value('start_date');
				if($end_date < $d->get_value('start_date'))
					echo 'Please pick a end date on or after the start date.';
				else
					echo $this->_get_changes_markup($d->get_value('start_date'),$end_date,$d->get_value('type'),$d->get_value('site'));
			}
			echo '</div>'."\n";
		} // }}}
		
		/**
		 * Take a bunch of entities and transform into an id => name array for a select element in disco
		 * @param array $entities
		 * @return array form: array(id => name,id => name,...)
		 */
		function _prep_for_disco($entities)
		{
			$ret = array();
			foreach($entities as $e)
			{
				$ret[$e->id()] = strip_tags($e->get_display_name());
			}
			return $ret;
		}
		
		/**
		 * Get all Reason types
		 * @return array reason type entities
		 */
		function _get_types()
		{
			if(empty($this->_types))
			{
				$es = new entity_selector();
				$es->add_type(id_of('type'));
				$this->_types = $es->run_one();
			}
			return $this->_types;
		}
		
		/**
		 * Get all live Reason sites
		 * @return array reason site entities
		 */
		function _get_sites()
		{
			if(empty($this->_sites))
			{
				$es = new entity_selector();
				$es->add_type(id_of('site'));
				$es->add_relation('site.site_state = "Live"');
				$this->_sites = $es->run_one();
			}
			return $this->_sites;
		}
		
		/**
		 * Get a link to the archive/history module for an item, given a start and end date
		 *
		 * This function will return a link to the archive module, with the entities found by
		 * _get_archived_entities() for the given start and end dates highlighted.
		 *
		 * @param object $item reason entity
		 * @param string $start_date
		 * @param string $end_date
		 * @return string html encoded link
		 */
		function _get_archive_link($item, $start_date, $end_date)
		{
			$archives = $this->_get_archived_entities($item,$start_date,$end_date);
			$owner = $item->get_owner();
			$link_parts = array(
								'site_id' => $owner->id(),
								'type_id' => $item->get_value('type'),
								'id' => $item->id(),
								'cur_module' => 'Archive',
							);
			if($archives['start'])
				$link_parts['archive_a'] = $archives['start']->id();
			if($archives['end'])
				$link_parts['archive_b'] = $archives['end']->id();
			return $this->admin_page->make_link($link_parts);
		}
		
		/**
		 * Get a link to the preview module for a given item
		 * @param object $item reason entity
		 * @return string html encoded link
		 */
		function _get_preview_link($item)
		{
			$owner = $item->get_owner();
			$link_parts = array(
								'site_id' => $owner->id(),
								'type_id' => $item->get_value('type'),
								'id' => $item->id(),
								'cur_module' => 'Preview',
							);
			return $this->admin_page->make_link($link_parts);
		}
		
		/**
		 * Get the markup for a given date range, type (optional) and site (optional)
		 *
		 * @param string $start_date
		 * @param string $end_date
		 * @param integer $type_id
		 * @param integer $site_id
		 * @return string markup
		 */
		function _get_changes_markup($start_date, $end_date, $type_id = '',$site_id = '')
		{
			if($start_date == $end_date)
				echo '<p>Items added or edited on '.prettify_mysql_datetime($start_date).'</p>'."\n";
			else
				echo '<p>Items added or edited between '.prettify_mysql_datetime($start_date).' and '.prettify_mysql_datetime($end_date).'</p>'."\n";
			
			$types = $this->_get_types();
			$sites = $this->_get_sites();
			if($site_id)
				$site_param = $site_id;
			else
				$site_param = array_keys($sites);
			
			foreach($types as $type)
			{
				if($type_id && $type_id != $type->id())
					continue;
				$es = new entity_selector($site_param);
				$es->add_type($type->id());
				$es->add_relation('entity.last_modified >= "'.$start_date.'"');
				$es->add_relation('entity.last_modified <= "'.$end_date.' 23:59:59"');
				$es->set_sharing('owns');
				$changes = $es->run_one();
				$deletions = $es->run_one('','Deleted');
				
				if(!empty($changes) || !empty($deletions))
				{
					$plural_word = $type->get_value('plural_name') ? $type->get_value('plural_name') : $type->get_value('name');
					echo '<div class="'.htmlspecialchars($type->get_value('unique_name')).'_report">'."\n";
					echo '<h3>'.$plural_word.'</h3>'."\n";
					if(!empty($changes))
					{
						echo '<h4>Changes: '.count($changes).'</h4>'."\n";
						echo '<ul class="changes">'."\n";
						foreach($changes as $item)
						{
							$change_type = 'change';
							if($item->get_value('creation_date') > $start_date && $item->get_value('creation_date') <= $end_date.' 23:59:59')
								$change_type = 'addition';
							echo '<li class="'.$change_type.'">';
							if($change_type == 'change')
								echo '<a href="'.$this->_get_archive_link($item, $start_date, $end_date).'">'.$item->get_display_name().'</a>';
							else
								echo '<a href="'.$this->_get_preview_link($item).'">'.$item->get_display_name().'</a> (new)';
							
							if(empty($site_id) && $owner = $item->get_owner())
								echo '<div class="owner">'.$owner->get_value('name').'</div>'."\n";
							echo '</li>'."\n";
						}
						echo '</ul>'."\n";
					}
					if(!empty($deletions))
					{
						echo '<h4>Deletions: '.count($deletions).'</h4>'."\n";
						echo '<ul class="deletions">'."\n";
						foreach($deletions as $item)
						{
							echo '<li class="deletion">';
							
							echo '<a href="'.$this->_get_preview_link($item).'">'.$item->get_display_name().'</a>';
							if(empty($site_id) && $owner = $item->get_owner())
								echo '<div class="owner">'.$owner->get_value('name').'</div>'."\n";
							echo '</li>'."\n";
						}
						echo '</ul>'."\n";
					}
					echo '</div>'."\n";
					//die('foo');
				}
			}
		}
		
		/**
		 * Get the archived entities that represent the state of the item at
		 * the beginning of a given start date and at the end of a given end date
		 *
		 * @param object $item reason entity
		 * @param string $start_date
		 * @param string $end_date
		 * @return array form: array('start'=>entity,'end'=>entity)
		 */
		function _get_archived_entities($item,$start_date,$end_date)
		{
			//echo $start_date.'.'.$end_date;
			$ret = array('start'=>NULL,'end'=>NULL);
		
			$es = new entity_selector();
			$es->add_type( $item->get_value('type') );
			$es->add_right_relationship( $item->id(), reason_get_archive_relationship_id($item->get_value('type')) );
			$es->add_relation('entity.last_modified < "'.addslashes($start_date).'"');
			$es->set_order('entity.last_modified DESC');
			$es->set_num(1);
			$starts = $es->run_one(false,'Archived');
			
			if(!empty($starts))
				$ret['start'] = current($starts);
			
			$es = new entity_selector();
			$es->add_type( $item->get_value('type') );
			$es->add_right_relationship( $item->id(), reason_get_archive_relationship_id($item->get_value('type')) );
			$es->add_relation('entity.last_modified <= "'.addslashes($end_date).' 23:59:59"');
			$es->set_order('entity.last_modified DESC');
			$es->set_num(1);
			$ends = $es->run_one(false,'Archived');
			
			if(!empty($ends))
				$ret['end'] = current($ends);
				
			return $ret;
		}
	} // }}}
?>