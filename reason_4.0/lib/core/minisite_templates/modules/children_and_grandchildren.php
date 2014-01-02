<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class & register module with Reason
 */
reason_include_once( 'minisite_templates/modules/children.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildrenAndGrandchildrenModule';

/**
 * A minisite module that shows a tree of children, grandchildren, etc. of the
 * current page.
 *
 * Use the parameter max_depth to control how deeply the module shows progeny
 *
 * @todo support thumbnail parameters in the original module.
 * @todo possibly rework so this supports all params of ChildrenModule, extends less ... see children_full_titles.php for example
 */
class ChildrenAndGrandchildrenModule extends ChildrenModule 
{
	var $acceptable_params = array(
		'max_depth' => 1,
		'use_link_name' => true,
		'default_tag' => 'strong',
		'depth_to_tag_map' => array(1=>'h4'),
		'show_only_this_branch' => '',
		'show_only_pages_in_nav' => false,
		'parent_unique_name' => '',
	);
	
	function run() // {{{
	{
		echo '<div id="childrenAndGrandchildren">'."\n";
		/* If the page has no entries, say so */
		if( empty($this->offspring ) )
		{
			echo 'This page has no children<br />';	
		}
		/* otherwise, list them */
		else
		{
			echo '<ul class="childrenList">'."\n";
			foreach( $this->offspring AS $child )
			{
				if ( $this->page_id != $child->id()
					&&
					( empty($this->params['show_only_this_branch']) || $this->params['show_only_this_branch'] == $child->get_value('unique_name') )
				 )
				{
					$this->show_child_item( $child );
				}
			}
			echo "</ul>\n";
		}
		echo '</div>'."\n";
	} // }}}
	function show_child_item( $child, $depth = 1, $prepend_url = NULL )
	{
		/* If the page has a link name, use that; otherwise, use its name */
		if( $this->params['use_link_name'] )
		{
			$page_name = $child->get_value( 'link_name' ) ? $child->get_value( 'link_name' ) : $child->get_value('name');
		}
		else
		{
			$page_name = $child->get_value('name');
		}

		$link = $this->get_page_link($child);
		if ( array_key_exists( $depth, $this->params['depth_to_tag_map'] ) )
		{
			$tag = $this->params['depth_to_tag_map'][$depth];
		}
		else
		{
			$tag = $this->params['default_tag'];
		}
			
		echo '<li><'.$tag.'><a href="'.$link.'">'.$page_name.'</a></'.$tag.'>';
		if ( $child->get_value( 'description' ))
			echo "\n".'<div class="childDesc">'.$child->get_value( 'description' ).'</div>';
		if($depth < $this->params['max_depth'])
		{
			$children_prepend = $prepend_url.$child->get_value('url_fragment').'/';
			$children_depth = $depth + 1;
			$grandchildren = $this->get_children( $child );
			if(!empty($grandchildren))
			{
				echo "\n".'<ul>';
				foreach($grandchildren as $grandchild)
					$this->show_child_item( $grandchild, $children_depth, $children_prepend );
				echo '</ul>';
			}
		}
		echo "</li>\n";
	}
	function get_children( $child )
	{
		$es = new entity_selector();
		$es->description = 'Selecting children of the page id '.$child->id();

		// find all the children of this page
		$es->add_type( id_of('minisite_page') );
		$es->add_left_relationship( $child->id(), relationship_id_of( 'minisite_page_parent' ) );
		if($this->params['show_only_pages_in_nav'])
		{
			$this->es->add_relation('nav_display = "Yes"');
		}
		$es->set_order('sortable.sort_order ASC');
		return $es->run_one();
	}/**
		 * Get the full page link for a page. We fork our linking logic based on whether we have specified a parent_unique_name or not.
		 *
		 * - If no, we get the relative URL just by looking at the url_fragment.
		 * - If yes, we call get_page_link_other_parent($page)
		 *
		 * @return string href attribute
		 */
		function get_page_link($page)
		{
			/* Check for a url (that is, the page is an external link); otherwise, use its relative address */
			if( $page->get_value( 'url' ) )
			{
				$link = $page->get_value( 'url' );
			}
			else
			{
				$link = $this->get_page_link_other_parent($page);
			}
			return $link;
		}
}

?>
