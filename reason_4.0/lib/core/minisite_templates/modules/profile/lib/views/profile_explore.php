<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

$GLOBALS[ '_profiles_view' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileExploreView';

/**
 * This view expects the data to be an instance of a profile connector class.
 *
 * - Need to implement tag browsing - or tag browsing within connect tab.
 */
 
class DefaultProfileExploreView extends ReasonMVCView
{
	var $str;
	
	function get()
	{
		$config = profile_get_config();
		$connector = $this->data();
		
		$str = '';
		$str .= '<h2>Profile Explore</h2>';
		foreach ($config->tag_section_relationship_names as $section => $rel)
		{
			$str .= '<h3>'.$connector->get_section_name($section).'</h3>';
			if ($top = $connector->get_top_tags_for_relation($rel))
			{
				$top = array_slice($top, 0, 10, true);
				$str .= '<ul class="tagList">'."\n";
				foreach ($top as $id => $count)
				{
					if ($tag = $connector->get_tag_by_id($id))
					{
						$str .= '<li><a class="interestTag" href="'.profile_construct_explore_link(array('tag'=>$tag['slug'])).'" title="Explore this tag">'.htmlspecialchars($tag['name']).' ('.$count.')</a></li>' ."\n";
						$count++;
					}
				}
				$str .= '</ul>';
			}
		}
		return $str;
	}
}
?>