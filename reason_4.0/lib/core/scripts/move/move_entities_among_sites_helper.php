<?php
/**
 * Helper library for move entities among sites.
 *
 * - Defines MoveEntitiesPreProcess and MoveEntitiesPostProcess as containers for type specific ReasonJob creation.
 * - Defines a number of ReasonJob objects for jobs related to moving entities.
 *
 * @author Nathan White
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/job.php' );

/**
 * This class returns ReasonJob entities to add to the stack. Any type specific jobs added here land in the stack 
 * just before the DeleteBorrowshipRelIfItExistsJob for each entity.
 *
 * To add to this, just declare a method with the unique name of the type for which you need a pre process job.
 */
class MoveEntitiesPreProcess
{
	/**
	 * Before moving a page, we check if it is a root node - if so, we delete its minisite_page_parent relationship to itself so that
	 * we do not end up with a site that has two parents. Someone needs to manually edit the page after to attach it to the appropriate
	 * place on the destination site.
	 */
	public static function minisite_page($info)
	{
		static $verify_job;
		// lets alter the verification job if it exists
		if (!isset($verify_job))
		{
			$verify_job = new MakeSurePageTreeIntegrityIsPreserved();
			$verify_job->add_page_id($info['entity_id']);
			$jobs[] = $verify_job;
		}
		else
		{
			$verify_job->add_page_id($info['entity_id']);
		}
		
		$job = new RemoveParentRelIfRootNodeJob();
		$job->config('page_id', $info['entity_id']);
		$job->config('new_site_id', $info['new_site_id']);
		$jobs[] = $job;
		return $jobs;
	}
}

/**
 * This class returns ReasonJob entities to add to the stack. Any type specific jobs added here land in the stack 
 * just after the UpdateOwnershipRelJob for each entity.
 *
 * To add to this, just declare a method with the unique name of the type for which you need a post process job.
 */
class MoveEntitiesPostProcess
{
}

/**
 * This job takes a set of page_ids we intend to move and makes sure that for each id we want to move,
 * all of its children are in the set of page ids we are moving. This makes sure that we don't move a single page
 * in the middle of a page tree which would horribly break the source site.
 */
class MakeSurePageTreeIntegrityIsPreserved extends ReasonJob
{
	var $required = array('page_ids');
	//var $blocking = true;
	
	function run_job()
	{
		reason_include_once('minisite_templates/nav_classes/default.php');
	
		$page_ids = $this->config('page_ids');
		$id = reset($page_ids);
		$page = new entity($id);
		$site = $page->get_owner();
		
		// lets make and get the page tree
		$page_tree = new MinisiteNavigation;
		$page_tree->order_by = 'sortable.sort_order';
		$page_tree->init( $site->id(), id_of('minisite_page') );
		
		foreach ($page_ids as $page_id)
		{
			$children = $page_tree->children($page_id);
			$diff = array_diff($children, $page_ids);
			
			// are we trying to move a page without its children?
			if (!empty($diff))
			{
				$problem[] = 'Cannot move page id ' . $page_id . ' without also moving child pages with ids (' . implode(", ", $diff) . ')';
			}
		}
		
		if (isset($problem))
		{
			$report = 'Blocking any further jobs. When moving pages you need to move entire branches';
			$report .= '<ul>';
			$report .= '<li>' . implode("</li><li>", $problem) . '</li>';
			$report .= '</ul>';
			$this->set_report($report);
			$this->block_jobs();
			return false;
		}
		else $this->set_report('Verified that the page tree integrity is preserved with this move.');
		return true;
	}
	
	function add_page_id($page_id)
	{
		$page_ids = $this->config('page_ids');
		if (!is_array($page_ids))
		{
			$page_ids = array();
		}
		$page_ids[] = $page_id;
		$this->config('page_ids', $page_ids);
	}
}

/**
 * Requires a page_id to be provided for configuration
 */
class RemoveParentRelIfRootNodeJob extends ReasonJob
{
	var $required = array('page_id', 'new_site_id');
	
	/**
	 * We always return true - we check if the entity id is a root node (and then if the destination has a root node) and delete the page parent rel if so.
	 */
	function run_job()
	{
		$d = new DBselector();
		$d->add_table( 'r', 'relationship' );
		$d->add_field( 'r', 'entity_b', 'parent_id' );
		$d->add_field( 'r', 'id', 'rel_id' );
		$d->add_relation( 'r.type = ' . relationship_id_of('minisite_page_parent'));
		$d->add_relation( 'r.entity_a = ' . $this->config('page_id') );
		$d->set_num(1);
		$result = db_query( $d->get_query() , 'Error getting parent ID.' );
		if( $row = mysql_fetch_assoc($result))
		{
			if ($row['parent_id'] == $this->config('page_id')) // this is a root node
			{
				if ($this->new_site_has_root_node())
				{
					delete_relationship($row['rel_id']);
					$this->set_report("Page ID " . $this->config('page_id') . " is a root node - deleted rel " . $row['rel_id'] .'.' );
				}
				else
				{
					$this->set_report("Page ID " . $this->config('page_id') . " remains a root node - not deleting because destination site has no root node.");
				}
			}
		}
		return true;
	}
	
	/**
	 * If the destination site lacks a root node - there is no need to remove the relationship.
	 */
	private function new_site_has_root_node()
	{
		reason_include_once('function_libraries/root_finder.php');
		$root = root_finder($this->config('new_site_id'));
		return (!empty($root));
	}
}

/**
 * Requires a page_id to be provided for configuration
 */
class DeleteBorrowshipRelIfItExistsJob extends ReasonJob
{
	var $required = array('entity_id', 'new_site_id');
	
	/**
	 * We always return true - we check if the entity id is a root node and delete the page parent rel if so.
	 */
	function run_job()
	{
		$q = 'DELETE FROM relationship WHERE entity_a = ' . 
			 $this->config('new_site_id') . ' AND entity_b = ' . 
			 $this->config('entity_id') . ' AND type = ' . 
			 $this->config('borrows_relationship_id');
		$r = db_query( $q , 'Error removing borrowship' );
		$affected = mysql_affected_rows();
		if (!empty($affected))
		{
			$this->set_report("Removed " . $affected . " borrowship relationship for entity " . $this->config('entity_id') . " on site " . $this->config('new_site_id').'.');
		}
		return true;
	}
}


		
class UpdateOwnershipRelJob extends ReasonJob
{
	var $new_site_id;
	var $old_site_id;
	var $entity_id;
	var $allowable_relationship_id;
	
	function run_job()
	{
		$q = ( 'UPDATE relationship SET ' .
				   'entity_a="' . $this->config('new_site_id') . '" ' .
				   'WHERE entity_a="' . $this->config('old_site_id') . '" ' .
				   'AND entity_b="' . $this->config('entity_id') . '" ' .
				   'AND type="' . $this->config('allowable_relationship_id') . '"' );
		$r = db_query($q, 'Unable to update relationships.');
		$affected = mysql_affected_rows();
		if (!empty($affected))
		{
			$this->set_report("Updated ownership relationship for entity " . $this->config('entity_id') . " - now owned by site " . $this->config('new_site_id').'.');
		}
		return true;
	}
}

/**
 * Checks if our type is one that requires sites rewrites - runs them if so.
 *
 * @author Nathan White
 */
class MoveEntitiesRewriteJob extends ReasonJob
{
	var $site_ids;
	var $type_unique_name;
	var $types_requiring_rewrites = array('minisite_page', 'asset', 'event_type', 'publication_type', 'news', 'av_file');
	var $required = array('type_unique_name', 'site_ids');

	function run_job()
	{
		if (in_array($this->config('type_unique_name'), $this->types_requiring_rewrites))
		{
			$site_ids = $this->config('site_ids');
			foreach ($site_ids as $site_id)
			{
				$urlm = new url_manager($site_id);
				$urlm->update_rewrites();
			}
			$this->set_report('Ran rewrites for site id(s) ' . implode(', ', $site_ids) .'.');
		}
		else
		{
			$this->set_report('Did not run rewrites - the type moved (' . $this->config('type_unique_name') . ') does not require it.');
		}
		return true;
	}
}

/**
 * Takes an array of site_ids, and clears the navigation cache from those sites.
 *
 * @author Nathan White
 */
class MoveEntitiesNavCacheJob extends ReasonJob
{
	var $site_ids;
	var $type_unique_name;
	var $required = array('site_ids');

	function run_job()
	{
		reason_include_once('classes/object_cache.php');
		$site_ids = $this->config('site_ids');
		foreach ($site_ids as $site_id)
		{
			$cache = new ReasonObjectCache($site_id . '_navigation_cache');
			$cache->clear();
		}
		$this->set_report('Zapped the navigation cache for site id(s) ' . implode(', ', $site_ids) .'.');
		return true;
	}
}

/**
 * When we move an entity_a to a site that previously borrowed it, we may have relationships on the site with
 * relationship context included in the relationship.site field. If so, we want to set relationship.site to 0
 *
 * @author Nathan White
 */
class RemoveSiteContextFromDestinationSiteJob extends ReasonJob
{
	var $required = array('new_site_id', 'a_entities');
	
	function run_job()
	{
		$new_site_id = (integer) $this->config('new_site_id');
		$a_entities = (array) $this->config('a_entities');
		$a_entities_str = implode(",", $a_entities);
		$q = ( 'UPDATE relationship SET site=0 ' .
				   'WHERE entity_a IN (' . $a_entities_str . ') ' .
				   'AND site="' . $new_site_id .'"');
		$r = db_query($q, 'Unable to update relationships.');
		$affected = mysql_affected_rows();
		if (!empty($affected))
		{
			$this->set_report("Removed site context value for entities (".$a_entities_str.") on site " . $new_site_id.'.');
		}
		return true;
	}
}

/**
 * When we move an entity that is on the b side of relationships with borrowed a side entities, we need to do a few things.
 *
 * - Primarily, we want to change the site context to the destination site id.
 * - Also, the destination site should borrow the entities on the a side of the rels (if they are borrowed on original site).
 * - Third, if we do any borrowing we want to make sure the destination site has access to the type.
 *
 * @author Nathan White
 */
class ChangeSiteContextAndAutoBorrowJob extends ReasonJob
{
	var $report_item;
	var $_borrowed_type = false;
	
	function run_job()
	{
		$new_site_id = (integer) $this->config('new_site_id');
		$b_entities = (array) $this->config('b_entities');
		$b_entities_str = implode(",", $b_entities);
		$q = ( 'SELECT id, entity_a, site FROM relationship ' .
				   'WHERE entity_b IN (' . $b_entities_str . ') ' .
				   'AND site > 0' );		   
		$result = db_query($q, 'Unable to select relationships');
		$count = mysql_num_rows($result);
		
		if ($count > 0)
		{
			$q2 = ( 'UPDATE relationship SET site=' . $this->config('new_site_id') . ' ' .
				    'WHERE entity_b IN (' . $b_entities_str . ') ' .
				    'AND site > 0' );
			$result2 = db_query($q2, 'Unable to change relationships');
			
			//using the original result set take care of borrowing and site_to_type changes
			$borrowed_count = $site_has_type_count = 0;
			while ($row = mysql_fetch_assoc($result))
			{
				$this->report_item[] = 'Changed site context of rel id ' . $row['id'] . ' from ' . $row['site'] . ' to ' . $this->config('new_site_id') .'.';
				$site_has_type = $borrowed = false;
				// figure out if $row['site'] borrows $row['entity_a']
				if (site_borrows_entity( $row['site'], $row['entity_a']))
				{
					$entity_a = new entity($row['entity_a']);
					$entity_a_type = $entity_a->get_value('type');
					$borrowed = create_relationship( $this->config('new_site_id'), $row['entity_a'], get_borrows_relationship_id($entity_a_type));
					if (!isset($this->_ensured_site_has_type[$entity_a_type]))
					{
						$site_has_type = create_relationship( $this->config('new_site_id'), $entity_a_type, relationship_id_of('site_to_type') );
						$this->_ensured_site_has_type[$entity_a_type] = true;
					}
				}
				if ($borrowed) $this->report_item[] = 'New site id ' . $this->config('new_site_id') . ' borrowed entity id ' . $row['entity_a'] .'.';
				if ($site_has_type) $this->report_item[] = 'New site id ' . $this->config('new_site_id') . ' now has access to type ' . $entity_a_type . '.';
			}
			
			if (count($this->report_item) > 0)
			{
				if (count($this->report_item) > 1)
				{
					$this->set_report('<p>Job did some work:</p><ul><li>' . implode("</li><li>",$this->report_item) . '</li></ul>');
				}
				else $this->set_report(reset($this->report_item));
			}
		}
		return true;
	}
}
?>