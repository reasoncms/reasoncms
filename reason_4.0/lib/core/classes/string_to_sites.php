<?php

class stringToSites
{
	protected $context_site;
	protected $cache_lifespan;
	function set_context_site($site)
	{
		if(is_numeric($site))
		{
			$site = new entity($site);
		}
		elseif(is_string($site))
		{
			if(reason_unique_name_exists($site))
			{
				$site = new entity(id_of($site));
			}
			else
			{
				trigger_error('set_context_site() passed a string that is not a site unique name');
				return;
			}
		}
		$this->context_site = $site;
	}
	
	function set_cache_lifespan($lifespan)
	{
		$this->cache_lifespan = (integer) $lifespan;
	}

	function get_sites_from_string($string)
	{
		if(empty($string))
		{
			return array();
		}
		$sites = array();
		$site_strings = explode(',',$string);
		foreach($site_strings as $site_string)
		{
			$site_string = trim($site_string);
			switch($site_string)
			{
				case 'k_parent_sites':
					$psites = $this->get_parent_sites();
					if(!empty($psites))
					{
						$sites = $sites + $psites;
					}
					break;
				case 'k_child_sites':
					$csites = $this->get_child_sites();
					if(!empty($csites))
					{
						$sites = $sites + $csites;
					}
					break;
				case 'k_sharing_sites':
					$ssites = $this->get_sharing_sites();
					if(!empty($ssites))
					{
						$sites = $sites + $ssites;
					}
					break;
				default:
					$usites = $this->get_sites_by_unique_name($site_string);
					if(!empty($usites))
					{
						$sites = $sites + $usites;
					}
			}
		}
		return $sites;
	}
	
	/**
	 * Get parent sites for a given site
	 *
	 * @param integer $site entitye
	 * return array site entities
	 */
	function get_parent_sites()
	{
		if(empty($this->context_site))
		{
			return array();
		}
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_right_relationship( $this->context_site->id(), relationship_id_of( 'parent_site' ) );
		
		if($this->context_site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->cache_lifespan);
		return $es->run_one();
	}
	/**
	 * Get child sites for a given site
	 *
	 * @param integer $site site entity
	 * return array site entities
	 */
	function get_child_sites()
	{
		if(empty($this->context_site))
		{
			return array();
		}
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship( $this->context_site->id(), relationship_id_of( 'parent_site' ) );
		
		if($this->context_site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->cache_lifespan);
		return $es->run_one();
	}
	/**
	 * Returns an array of site entities keyed by site id
	 *
	 * If a site unique name is given, this function will return a single-member array of that site
	 *
	 * If a site type unique name is given, this function will return all the sites that are of that site type.
	 * If the context site is live, only live sites will be returned.
	 *
	 * @param string $unique_name the unique anem of a site or a site type entity
	 * @param integer $context_site_id the id of the context site
	 * @access private
	 */
	function get_sites_by_unique_name($unique_name)
	{
		$return = array();
		if($id = id_of($unique_name))
		{
			$entity = new entity($id);
		
			switch($entity->get_value('type'))
			{
				case id_of('site'):
					$return[$id] = $entity;
					break;
				case id_of('site_type_type'):
					$es = new entity_selector();
					$es->add_type(id_of('site'));
					$es->add_left_relationship( $id, relationship_id_of( 'site_to_site_type' ) );
					if(empty($this->context_site) || $this->context_site->get_value('site_state') == 'Live')
					{
						$es->limit_tables('site');
						$es->limit_fields('site_state');
						$es->add_relation('site_state="Live"');
					}
					else
					{
						$es->limit_tables();
						$es->limit_fields();
					}
					$es->set_cache_lifespan($this->cache_lifespan);
					$return = $es->run_one();
					break;
				default:
					trigger_error('Unique name "'.$unique_name.'" passed to events module in additional_sites parameter does not correspond to a Reason site or site type. Not included in sites shown.');
			}
		}
		else
		{
			trigger_error($unique_name.' is not a unique name of any Reason entity. Site(s) will not be included.');
		}
		return $return;
	}
	
	/**
	 * Get the sites that share events
	 *
	 * This module will return non-live sites if the current site is non-live.
	 *
	 * return array site entities
	 */
	function get_sharing_sites()
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_left_relationship( id_of('event_type'), relationship_id_of('site_shares_type'));
		
		if(empty($this->context_site) || $this->context_site->get_value('site_state') == 'Live')
		{
			$es->limit_tables('site');
			$es->limit_fields('site_state');
			$es->add_relation('site_state="Live"');
		}
		else
		{
			$es->limit_tables();
			$es->limit_fields();
		}
		$es->set_cache_lifespan($this->cache_lifespan);
		
		return $es->run_one();
	}
}
