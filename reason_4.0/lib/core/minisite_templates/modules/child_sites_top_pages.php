<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/child_sites.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildSitesTopPagesModule';

	/**
	 * A minisite module that lists child sites of the current site, plus 
	 * the top pages in their page hierarchies
	 */
	class ChildSitesTopPagesModule extends ChildSitesModule
	{
		function show_site( $site )
		{
			$link = $site->get_value('base_url');
			echo '<li>'."\n";
			echo '<h4><a href="'.$link.'">'.$site->get_value('name').'</a></h4>'."\n";
			
			$es = new entity_selector($site->id());
			$es->description = 'Getting pages for child site';
			$es->add_type( id_of( 'minisite_page' ) );
			$es->add_left_relationship_field( 'minisite_page_parent', 'entity' , 'id' , 'parent_id' );
			$es->add_relation('page_node.nav_display = "Yes"');
			$es->set_order( 'sortable.sort_order' );
			$pages = $es->run_one();
			
			$parents = array();
			foreach($pages as $page)
			{
				if($page->id() == $page->get_value('parent_id'))
					$root_page_id = $page->id();
				else
					$parents[$page->get_value('parent_id')][$page->id()] = $page;
			}
			$this->display_pages( $link, $parents, $root_page_id );
			echo '</li>'."\n";
		}
		function display_pages( $base_url, $parents, $root_page_id )
		{
			if(!empty($root_page_id) && !empty($parents[$root_page_id]))
			{
				echo '<ul>'."\n";
				foreach($parents[$root_page_id] as $page)
				{
					$link = $page->get_value('url') ? $page->get_value('url') : $base_url.$page->get_value('url_fragment').'/';
					if($page->get_value('link_name'))
						$name = $page->get_value('link_name');
					else
						$name = $page->get_value('name');
					echo '<li><a href="'.$link.'">'.$name.'</a></li>'."\n";
				}
				echo '</ul>'."\n";
			}
		}
	}
?>
