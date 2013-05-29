<?php
class EnsureTypeIsOnSite extends AbstractImportJob implements ImportJob
{
	var $site_id;
	var $type_id;
	
	/**
	 * We do basically nothing here since create_relationship does its own duplication check by default.
	 */
	function run_job()
	{
		create_relationship( $this->site_id, $this->type_id, relationship_id_of('site_to_type'));
		$this->report = 'Ensured type id ' . $this->type_id . ' is on the site.';
		return true;
	}
}

class EnsureNobodyGroupIsOnSite extends AbstractImportJob implements ImportJob
{
	var $site_id;
	
	function run_job()
	{
		if (!empty($this->site_id))
		{
			if(!(site_borrows_entity( $this->site_id, id_of('nobody_group')) || site_owns_entity( $this->site_id, id_of('nobody_group'))))
			{
				// borrow it
				create_relationship( $this->site_id, id_of('nobody_group'), get_borrow_relationship_id(id_of('group_type')));
				$this->report = 'Borrowed the nobody group entity to the site.';
			}
			return true;
		}
		else
		{
			trigger_error('EnsureNobobyGroupIsOnSite requires a site_id - will self destruct.');
			$this->self_destruct();
			return false;
		}
	}
}

/**
 * @todo I think the duplication check stuff should work like this ... if it exists, we do set_result with the existing entity, otherwise we do it with the new entity.
 */
class EntityCreationJob extends AbstractImportJob implements ImportJob
{
	var $site_id;
	var $user_id;
	var $type_id;
	var $entity_info;
	
	/**
	 * The idea here is that we can setup a set of fields to check to see if we have already created this entity ... we are not using this yet.
	 */
	var $params_to_match = array('name' => 'entity.name', 'datetime' => 'dated.datetime');
	
	function run_job()
	{
		static $created_eids;
		$es = new entity_selector($this->site_id);
		$es->add_type($this->type_id);		
		if (!isset($this->entity_info['new'])) $this->entity_info['new'] = "0";
		$id = reason_create_entity($this->site_id, $this->type_id, $this->user_id, $this->entity_info['name'], $this->entity_info);
		$this->report = 'Created entity of type id ' . $this->type_id . ' with id ' . $id;
		$this->set_result($id);
		return true;	
	}
}

class NewsRewriteAlertJob extends AbstractImportJob implements ImportJob
{
	var $page_id_guid;
	var $story_id_guid;
	var $original_url;	

	/** 
	 * Basically we just want to build a static list of rewrites and echo as an alert.
	 *
	 * We support the default ?p=aaaaa scheme and also custom friendly URLs
	 *
	 * Note that if you moved between various schemes over the lifespan of a blog, we may only
	 * end up supporting the latest scheme.
	 *
	 * If we have a default wordpress ?p=xxxx style URL
	 *
	 * RewriteCond %{QUERY_STRING} ^p=1044(.*)
	 * RewriteRule ^$ /newblogpage/?story_id=301002%1 [R=301,L]
	 *
	 * If we have a friendly URL
	 *
	 * RewriteRule ^old/permalink/structure$ /old/permalink/structure/ [R=301]
	 * RewriteRule ^old/permalink/structure/$ /newblogpage/?story_id=301010 [QSA,R=301]
	 */
	function run_job()
	{
		static $rewrite_html;
		$page_id = $this->get_result($this->page_id_guid);
		$story_id = $this->get_result($this->story_id_guid);
		$original_url = $this->original_url;
		
		if ($page_id && $story_id && $this->original_url)
		{
			if (!isset($rewrite_html)) $rewrite_html = 'RewriteEngine On' ."\n";
			
			$page_url = reason_get_page_url($page_id);
			$dest_url = parse_url($page_url);
			$orig_url = parse_url($original_url);
			
			if (isset($orig_url['query'])) parse_str($orig_url['query'], $orig_qs_array);
			else $orig_qs_array = array();
			
			if (isset($orig_qs_array['p'])) // if p is set lets do the first style redir - this redirects the default post style.
			{
				$old_path = ltrim($orig_url['path'], "/");
				$rewrite_html .= 'RewriteCond %{QUERY_STRING} ^p='.turn_into_int($orig_qs_array['p'])."(.*)". "\n";
				$rewrite_html .= 'RewriteRule ^'.$old_path.'$ '.$dest_url['path'].'?story_id='.$story_id.'%1 [R=301,L]'. "\n";
			}
			else // this redirects pretty urls
			{
				$old_path = ltrim($orig_url['path'], "/");
				if (!empty($old_path))
				{
					$rewrite_html .= 'RewriteRule ^'.rtrim($old_path, "/").'$ '.$orig_url['path']. ' [R=301]' . "\n";
					$rewrite_html .= 'RewriteRule ^'.$old_path.'$ '.$dest_url['path'].'?story_id='.$story_id .' [QSA,R=301]' . "\n";
				}
			}
			$this->alert['Post Rewrites'] = '<pre>'.$rewrite_html.'</pre>';
			return true;
		}
		else return false;
	}
}

/**
 * Produces a few general rewrite rules that are useful for an instance of wordpress
 */
//class GeneralRewriteAlertJob extends AbstractImportJob implements ImportJob
//{

//}

class MakePublicationPageJob extends AbstractImportJob implements ImportJob
{
	var $site_id;
	var $page_id;
	var $page_id_guid;
	var $user_id;
	var $pub_id;
	var $pub_import_guid;
	
	/**
	 * return false if we don't have a guid yet for the pub id
	 */
	function run_job()
	{
		if (empty($this->pub_id) && !empty($this->pub_import_guid))
		{
			$this->pub_id = $this->get_result($this->pub_import_guid);
		}
		if (empty($this->page_id) && !empty($this->page_id_guid))
		{
			$this->page_id = $this->get_result($this->page_id_guid);
		}
		if (!empty($this->site_id) && !empty($this->page_id) && !empty($this->pub_id))
		{
			// lets make the page a publication page
			reason_update_entity($this->page_id, $this->user_id, array('custom_page' => 'publication'));
			
			// lets relate the publication to the page
			create_relationship( $this->page_id, $this->pub_id, relationship_id_of('page_to_publication') );
			$this->set_result($this->page_id);
			
			$this->report = 'Related publication id ' . $this->pub_id . ' to page id ' . $this->page_id;
			return true;
		}
		else
		{
			if (empty($this->pub_id)) $this->report = 'Deferred because pub_import_guid ' . $this->pub_import_guid . ' does not return a publication id.';
			elseif (empty($this->page_id)) $this->report = 'Deferred because no page_id was set or determinable.';
			return false;
		}
	}
}
/**
 * If we have our guids, then run the job, else return false;
 */
class RelateItemsJob extends AbstractImportJob implements ImportJob
{
	var $rel_id;
	var $left_id;
	var $right_id;
	var $left_import_guid; // it takes an import guid ID which should return an entity id
	var $right_import_guid; // it takes an import guid ID which should return an entity id
	
	function run_job()
	{
		$left_id = (isset($this->left_id)) ? $this->left_id : $this->get_result($this->left_import_guid);
		$right_id = (isset($this->right_id)) ? $this->right_id : $this->get_result($this->right_import_guid);
		$rel_id = $this->rel_id;
		
		if ($rel_id && $left_id && $right_id) // we have our ids - lets DO IT!
		{
			create_relationship($left_id, $right_id, $rel_id);
			$this->set_result(array('left_id' => $left_id, 'right_id' => $right_id, 'rel_id' => $rel_id));
			$this->report = 'Related entity ' . $left_id . ' to ' . $right_id . ' across rel ' . $rel_id;
			return true;
		}
		else
		{
			$this->report = 'Deferred relate items - probably waiting for the entity on the right or left to be ready';
			return false;
		}
	}
}

class SiteRewritesJob extends AbstractImportJob implements ImportJob
{
	var $site_id;
	
	function run_job()
	{
		static $included;
		if (!isset($included))
		{
			reason_include_once('classes/url_manager.php');
			$included = true;
		}
		$um = new url_manager( $this->site_id, true );
		$um->update_rewrites();
		return true;
	}
}

/**
 * The idea here is to create in URL history a record for the old location of pages.
 *
 * @todo I think if pages do not have a friendly URL they are currently ignored.
 */
class URLHistoryJob extends AbstractImportJob implements ImportJob
{
	var $wp_link;
	var $wp_permalink;
	var $rel_guid;
	var $entity_guid;
	
	/**
	 * @todo add the old link as well
	 */
	function run_job()
	{
		static $included;
		if (!isset($included))
		{
			reason_include_once('function_libraries/URL_History.php');
			$included = true;
		}
		$eid = $this->get_result($this->entity_guid);
		$rid = $this->get_result($this->rel_guid);
		if ($eid && $rid)
		{
			// we are ready to update the URL history table
			if (isset($this->wp_permalink) && !empty($this->wp_permalink))
			{	
				$url = parse_url($this->wp_permalink);
				$rel_url = '/'.trim_slashes($url['path']).'/';
				$qs = (!empty($url['query'])) ? '?'.$url['query'] : '';
				if (!$qs)
				{
					$sqler = new SQLER();
					$cur_time = time();
					$values = array('url' => $rel_url, 'page_id' => $eid, 'timestamp' => $cur_time);
					$sqler->insert('URL_history', $values);
				}
			}
			if (isset($this->wp_link) && !empty($this->wp_link))
			{
				$url = parse_url($this->wp_link);
				$rel_url = '/'.trim_slashes($url['path']).'/';
				$qs = (!empty($url['query'])) ? '?'.$url['query'] : '';
				if (!$qs)
				{
					$sqler = new SQLER();
					$cur_time = time();
					$values = array('url' => $rel_url, 'page_id' => $eid, 'timestamp' => $cur_time);
					$sqler->insert('URL_history', $values);
				}
			}
			
			$e = new entity($eid);
			update_URL_history($eid, false);
			$this->report = 'Added new and old URL (if not query string based) to URL history';
			return true;
		}
		else
		{
			$this->report = 'Deferred - either the entity is not created or it has not been attached to a parent.';
			return false;
		}
	}
}
?>
