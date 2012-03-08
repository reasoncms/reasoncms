<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildSitesModule';

	/**
	 * A minisite module that lists child sites of the current site
	 *
	 * If the current site is live, the module only lists live child sites.
	 */
	class ChildSitesModule extends DefaultMinisiteModule
	{
		var $child_sites = array();
		var $acceptable_params = array(
			'sites' => NULL, // can be an array of site unique names, which will override normal site-grabbing and show those sites
		);
		
		function init( $args = array() ) // {{{
		{
			parent::init( $args );
			
			$site = new entity( $this->site_id );

			if(!empty($this->params['sites']))
			{
				$this->child_sites = array();
				foreach($this->params['sites'] as $unique_name)
				{
					if($id = id_of($unique_name))
					{
						$e = new entity($id);
						if($e->get_value('type') == id_of('site') && ($site->get_value('site_state') != 'Live' || $e->get_value('site_state') == 'Live') )
						{
							$this->child_sites[$id] = $e;
						}
					}
				}
			}
			else
			{
	
				$es = new entity_selector();
				$es->description = 'Getting child sites of this site';
				$es->add_type( id_of( 'site' ) );
				$es->add_left_relationship( $this->site_id, relationship_id_of( 'parent_site' ) );
				$es->set_order( 'entity.name' );
				if($site->get_value('site_state') == 'Live')
				{
					$es->add_relation('site_state="Live"');
				}
				$this->child_sites = $es->run_one();
			}
		} // }}}
		function has_content() // {{{
		{
			if( empty( $this->child_sites ) )
			{
				return false;
			}
			return true;
		} // }}}
		function run() // {{{
		{
			echo '<div id="childSites">'."\n";
			echo '<ul>'."\n";

			foreach( $this->child_sites as $site )
			{
				$this->show_site( $site );
			}
			
			echo '</ul>'."\n";
			echo '</div>'."\n";
		} // }}}
		function show_site( $site )
		{
			echo '<li>'."\n";
			echo '<h4><a href="'.$site->get_value('base_url').'">'.$site->get_value('name').'</a></h4>';
			$desc = $this->get_description($site);
			if(!empty($desc))
				echo $desc;
			echo '</li>'."\n";
		}
		function get_description($site)
		{
			$es = new entity_selector($site->id());
			$es->add_type( id_of('text_blurb' ) );
			$es->add_relation( 'entity.name = "Site Description"' );
			$es->set_order( 'entity.last_modified DESC');
			$descriptions = $es->run_one();
			if(!empty($descriptions))
			{
				reset($descriptions);
				$desc = current($descriptions);
				return($desc->get_value('content'));
			}
			elseif($site->get_value('description'))
			{
				return($site->get_value('description'));
			}
			else
				return NULL;
			
		}
	}
?>
