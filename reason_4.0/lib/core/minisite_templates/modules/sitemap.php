<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the parent class and register the module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'siteMapModule';

	/**
	 * A minisite module that lists live sites alphabetically by type
	 *
	 * This module merges both Reason sites and non_reason_sites entered in the master admin.
	 */
	class siteMapModule extends DefaultMinisiteModule
	{
		var $id_of_site_type;
		var $site_types = array();
		var $acceptable_params = array('site_types_unique_names' => array());

		function init( $args = array() )	 // {{{
		{
			parent::init( $args );
			$this->id_of_site_type = id_of( 'site' );
			$this->id_of_non_reason_site_type = id_of( 'non_reason_site_type' );
			if (!empty($this->params['site_types_unique_names'])) 
			{
				// populate $site_types according to params['site_types_unique_names']
				{
					foreach($this->params['site_types_unique_names'] as $cat_name => $unique_name_array)
					{
						foreach ($unique_name_array as $unique_name)
						{
							$this->site_types[$cat_name][] = id_of($unique_name);
						}
					}
				}
			}
			else	// grab order according to show_hide settings and sortable field for all site types
			{
				$es = new entity_selector(id_of('master_admin'));
				$es->add_type(id_of('site_type_type'));
				$es->add_relation('((show_hide.show_hide IS NULL) OR (show_hide.show_hide = "show"))');
				$es->set_order('sortable.sort_order ASC');
				$result = $es->run_one();
				foreach ($result as $e)
				{
					
					$cat_name = $e->get_value('name');
					$this->site_types[$cat_name][] = $e->id();
				}
			}
		} // }}}
		
		function has_content() // {{{
		{
			return true;
		} // }}}
		
		function run() // {{{
		{
			foreach($this->site_types as $name=>$site_type_id_array)
			{
				$this->grab_sites($name, $site_type_id_array);
			}
		} // }}}
		
		function grab_sites($name, $site_type_id_array) //{{{
		{
			$sites_by_type = array();
			$sites_by_type[ $this->id_of_site_type ] = array();
			$sites_by_type[ $this->id_of_non_reason_site_type ] = array();
			
			foreach($site_type_id_array as $site_type_id)
			{
				
				$r_es = new entity_selector();
				$r_es->description = 'Getting all '.$name;
				$r_es->add_type( $this->id_of_site_type );
				$r_es->add_relation('site.site_state = "Live"');
				$r_es->add_left_relationship($site_type_id, relationship_id_of('site_to_site_type'));
				$r_es->set_order('entity.name ASC');
				$sites_by_type[ $this->id_of_site_type ] += $r_es->run_one();
				
				$nr_es = new entity_selector();
				$nr_es->description = 'Getting all '.$name;
				$nr_es->add_type( $this->id_of_non_reason_site_type );
				$nr_es->add_left_relationship($site_type_id, relationship_id_of('non_reason_site_to_site_type'));
				$nr_es->add_relation('site.site_state = "Live"');
				$sites_by_type[ $this->id_of_non_reason_site_type ] += $nr_es->run_one();
			}
			
			$this->list_sites($sites_by_type, $name);
		} // }}}
		function list_sites(&$sites_by_type, $name) //{{{
		{
			if(!empty($sites_by_type))
			{
				$site_merge = array();
				$titles = array();
				foreach($sites_by_type as $type_id => $sites)
				{
					if(!empty($sites))
					{
						foreach($sites as $site)
						{
							if($type_id == $this->id_of_site_type)
								$site_merge[$site->get_value('name')] = $site->get_value('base_url');
							else
								$site_merge[$site->get_value('name')] = $site->get_value('url');
							
							if($site->get_value('keywords'))
							{
								$titles[$site->get_value('name')] = $site->get_value('keywords');
							}
						}
					}
				}
				ksort($site_merge);
				if (!empty($site_merge))
				{
					echo '<h3>'.$name.'</h3>'."\n";
					echo '<ul>'."\n";
					foreach($site_merge as $name=>$url)
					{
						$title_attr = '';
						if(!empty($titles[$name]) && strtolower($titles[$name]) != strtolower($name))
							$title_attr = ' title="'.reason_htmlspecialchars($titles[$name]).'"';
						echo '<li><a href="'.$url.'"'.$title_attr.'>'.$name.'</a></li>'."\n";
					}
					echo '</ul>'."\n";
				}
			}
		} // }}}
	}
?>
