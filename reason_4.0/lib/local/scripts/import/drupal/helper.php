<?php
reason_include_once('function_libraries/root_finder.php');

/**
 * I think this is deprecated ...
 */
 
/**
 * Expunge the publications, posts, and categories on a site - ALL OF EM!!!!!
 *
 * @todo add non home pages possibly - would be cool if this function would specifically select stuff from the import tables instead of zapping all
 */
class DrupalImportCleanup extends AbstractImportJob implements ImportJob
{
	var $site_id;
	var $user_id;
	
	function run_job()
	{
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('news'));
		$result = $es->run_one();
		if ($result)
		{
			$ids = array_keys($result);
			foreach ($result as $id=>$item)
			{
				reason_expunge_entity($id, $this->user_id);
			}
		}
		
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('publication_type'));
		$result = $es->run_one();
		if ($result)
		{
			$ids = array_keys($result);
			foreach ($result as $id=>$item)
			{
				reason_expunge_entity($id, $this->user_id);
			}
		}
		
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('category_type'));
		$result = $es->run_one();
		if ($result)
		{
			$ids = array_keys($result);
			foreach ($result as $id=>$item)
			{
				reason_expunge_entity($id, $this->user_id);
			}
		}
		
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('comment_type'));
		$result = $es->run_one();
		if ($result)
		{
			$ids = array_keys($result);
			foreach ($result as $id=>$item)
			{
				reason_expunge_entity($id, $this->user_id);
			}
		}
		
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('minisite_page'));
		$result = $es->run_one();
		if ($result)
		{
			$root_page = root_finder($this->site_id);
			$ids = array_keys($result);
			foreach ($result as $id=>$item)
			{
				if ($id != $root_page) reason_expunge_entity($id, $this->user_id);
			}
		}
		$this->report = 'Zapped all the news, publications, categories, comments, and pages from the site';
	}
}