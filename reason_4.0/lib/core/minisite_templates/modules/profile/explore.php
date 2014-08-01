<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__, '.php' ) ] = 'ProfileConnectorModule';

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'minisite_templates/modules/profile/connector_class.php' );
reason_include_once( 'minisite_templates/modules/profile/profile.php' );


/**
 * Profile Connector Module
 *
 * This module provides a tag-oriented view of the profile space. Provided with a 
 * tag slug as a parameter, it shows related tags and individuals who have used
 * the tag.
 *
 * @author Mark Heiman
 */
class ProfileConnectorModule extends DefaultMinisiteModule
{
	/** 
	 * How many profiles should appear in a single list before a "More" 
	 * link is put up?
	 */
	protected $max_connections_shown = 12;
	
	public $cleanup_rules = array(
		'tag' => array( 'function' => 'turn_into_string' ),
		'refresh' => array( 'function' => 'turn_into_string' ),
	);
	
	protected $config;
	protected $tag;
	protected $pc;
	protected $person;
	protected $page_url;
	protected $site_url;
	protected $profiles_url;
		
	public function init( $args = array() )
	{		
		$this->config = new ProfileConfig();
		
		if ($this->should_require_authentication()) reason_require_authentication();
		
		$this->pc = new $this->config->connector_class(isset($this->request['refresh']));
		if (isset($this->request['tag']))
		{
			if ($this->tag = $this->pc->get_tag_by_slug($this->request['tag']))
			$this->_add_crumb(htmlspecialchars($this->tag['name']));
		}
				
		if($head_items = $this->get_head_items())
		{
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/profiles/general.js');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/profiles/connector.css');
		}
		
		$this->get_person();
				
		$this->page_url = reason_get_page_url($this->cur_page);
		$this->site_url = reason_get_site_url(id_of($this->config->profiles_site_unique_name));
	}
	
	public function run()
	{
		echo '<div id="profilesModule" class="connector '.$this->get_api_class_string().'">'."\n";
		echo '<div id="mainProfileContent">'."\n";
		if (isset($this->tag))
			echo $this->get_tag_connections_html($this->tag);
		else if (isset($this->request['tag']))
			echo $this->get_tag_not_found_html($this->request['tag']);
		else
			echo $this->get_dashboard_html();
		echo '</div>';
		echo '<div id="secondaryProfileContent" class="connector">'."\n";
		echo $this->get_module_navigation();
		echo $this->get_sign_in_block();
		echo '</div>'."\n";
		echo '</div>';
	}
	
	/** Get the HTML for the navigation region of the page */
	protected function get_module_navigation()
	{
		if ($this->config->friendly_urls || reason_check_authentication())
		{
			$str  = '<div id="moduleNavigation" class="section">';
			$str .= '<ul>';
			if ($this->config->friendly_urls)
			{
				$str .= '<li><a href="'.$this->site_url.'me/">My Profile</a></li>';
			}
			else
			{
				$str .= '<li><a href="'.$this->site_url.$this->config->profile_slug.'/?username='.reason_check_authentication().'">My Profile</a></li>';
			}
			$str .= '</ul>';
			$str .= '</div>';
			return $str;
		}
		else return '';
	}
	
	/** Get the HTML for the login region of the page */
	protected function get_sign_in_block()
	{
		$str = '<div id="signIn" class="section">';
		if (reason_check_authentication())
			$str .= '<a href="/login/?logout=1" class="out">Sign Out</a>';
		else
			$str .= '<a href="/login/" class="in">Sign In</a>';
		$str .= '</div>'."\n";
		return $str;
	}

	/** Get the HTML for body of the tag page: related tags with lists of connected profiles */
	protected function get_tag_connections_html($tag)
	{		
		$str = $this->get_basic_info_html($tag);	
		$str .= $this->get_related_tags_html($tag);
		$str .= '<div id="tagInfo">';	
		$sections = $this->pc->get_connections_for_tag($tag['slug']);
		$has_children = (isset($tag['children']));		
		foreach ($sections as $section => $tags)
		{
			$str .= '<div class="connectSection" id="section'.$section.'">';
			$str .= '<h3 class="sectionName"><a name="'.$section.'"></a>'.$this->pc->get_section_name($section).'</h3>';
			if ($has_children) $str .= '<ul class="tag">';
			foreach ($tags as $id => $tag_data)
			{
				if (!isset($tag_data['profiles'][$this->config->tag_section_relationship_names[$section]])) continue;
				$profiles = $this->pc->get_profiles_by_affiliation($tag_data['profiles'][$this->config->tag_section_relationship_names[$section]]);

				// If we know who's logged in, sort the affiliations appropriately
				if ($this->person)
				{
					$profiles_by_affil = $this->pc->sort_profiles_by_user_affiliations($this->person, $profiles);
				} else {
					$profiles_by_affil = $profiles;
				}
				
				if ($has_children && $tag_data['slug'] != $tag['slug']) 
					$str .= '<li><h4 class="tagName"><a href="'. $this->page_url . $tag_data['slug'].'">'.$tag_data['name'].'</a></h4>';
				else
					$str .= '<li>';
					
				$str .= '<ul class="affiliations">';
				foreach ($profiles_by_affil as $affil => $profiles)
				{
					$str .= '<li><span class="affiliation">'.$this->pc->affiliations[$affil].':</span>';
					$str .= '<ul class="profiles">';
					$this->shuffle_assoc($profiles);
					$count = 0;
					foreach ($profiles as $username => $profile)
					{
						if ( $count < $this->max_connections_shown)
							$str .= '<li>';
						else
							$str .= '<li class="overflow">';
						if ($this->config->friendly_urls)
						{
							$str .= '<a href="'. $this->site_url . $username .'">'.$profile['display_name'].'</a></li>'."\n";
						}
						else
						{
							$str .= '<a href="'. $this->site_url . $this->config->profile_slug. '/?username='.$username .'">'.$profile['display_name'].'</a></li>'."\n";
						}	
						$count++;
					}
					$str .= '</ul>';
					$str .= '</li>';
				}
				$str .= '</ul>';
			}
			if ($has_children) $str .= '</ul>';
			$str .= '</div>';
			
		}
		$str .= '</div>';
		return $str;
	}

	/**
	 * HTML for the page heading
	 *
	 * @param array $tag  Tag data
	 */
	protected function get_basic_info_html($tag)
	{
		$str ='<div id="basicInfo" class="section">'."\n";
		$str .= '<h2 class="name">'.htmlspecialchars($tag['name']).'</h2>';
		$str .= $this->get_tag_header_html($tag);
		$str .= '</div>';
		return $str;
	}
	
	/**
	 * HTML for additional tag information you want to appear at the top of the tag
	 * page. Carleton uses it for links to other places the tag is used.
	 *
	 * @param array $tag  Tag data
	 */
	protected function get_tag_header_html($tag)
	{
		return null;
	}
	
	/**
	 * Content to display if an unknown tag is requested. By default, a list of tags with similar
	 * names is presented.
	 */
	protected function get_tag_not_found_html($tag)
	{		
		$str  ='<div id="basicInfo" class="section">'."\n";
		$str .= '<h2 class="name">'.htmlspecialchars($tag).'</h2>';
		$str .= '</div>';
		
		$str .= '<div id="contactInfo" class="section">';
		$str .= '<p><em>'.$tag.'</em> doesn\'t appear to be a valid tag.</p>';
		if ($similar = $this->pc->get_similar_tags($tag))
		{
			$str .= '<p>Did you mean:</p>';
			$str .= '<ul class="tagList">'."\n";
			$count = 0;
			foreach ($similar as $slug => $rank)
			{
				if ($tag = $this->pc->get_tag_by_slug($slug))
				{
					$str .= '<li><a class="interestTag" href="' . $this->page_url . $tag['slug'] . '" title="Explore this tag">'.htmlspecialchars($tag['name']).'</a></li>' ."\n";
					$count++;
				}
				if ($count == 10) break;
			}
			$str .= '</ul>';
		}
		$str .= '</div>';
		return $str;
	}
	
	/**
	 * Generate a very basic dashboard of tag stats
	 */
	protected function get_dashboard_html()
	{
		$str = '<div id="contactInfo" class="section">';
		foreach ($this->config->tag_section_relationship_names as $section => $rel)
		{
			$str .= '<h3>'.$this->pc->get_section_name($section).'</h3>';
			if ($top = $this->pc->get_top_tags_for_relation($rel))
			{
				$top = array_slice($top, 0, 10, true);
				$str .= '<ul class="tagList">'."\n";
				foreach ($top as $id => $count)
				{
					if ($tag = $this->pc->get_tag_by_id($id))
					{
						$str .= '<li><a class="interestTag" href="' . $this->page_url . $tag['slug'] . '" title="Explore this tag">'.htmlspecialchars($tag['name']).' ('.$count.')</a></li>' ."\n";
						$count++;
					}
				}
				$str .= '</ul>';
			}
		}
		$str .= '</div>';
		return $str;
	}
	
	/**
	 * Generate a list of tags that are related to the current tag, either as parents or children.
	 *
	 * @param array $tag_data
	 */
	protected function get_related_tags_html($tag_data)
	{
		if (isset($tag_data['parents']) || isset($tag_data['children']))
		{
			$parents = (isset($tag_data['parents'])) ? $tag_data['parents'] : array();
			$children = (isset($tag_data['children'])) ? $tag_data['children'] : array();
			$siblings = array();
			foreach ($parents as $parent_id)
			{
				if ($tag = $this->pc->get_tag_by_id($parent_id))
					$siblings = array_merge($siblings, $tag['children']);
			}
			// Child tags are generally listed in the body of the tag display, but if a child has
			// no profiles, but does have children of its own, it's a grouping tag, and we want to
			// show it as related.
			foreach ($children as $child_id)
			{
				if ($tag = $this->pc->get_tag_by_id($child_id))
				{
					if (empty($tag['profiles']) && !empty($tag['children']))
						$siblings[$tag['id']] = $tag['id'];
				}
			}
			
			$tags = array_merge($parents, $siblings);
			
			$tags_str = '';
			foreach ($tags as $id)
			{
				if ($id == $tag_data['id']) continue;
				if ($tag = $this->pc->get_tag_by_id($id))
				{
					$tags_str .= '<li><a class="interestTag" href="' . $this->page_url . $tag['slug'] . '" title="Explore this tag">'.htmlspecialchars($tag['name']).'</a></li>' ."\n";
				}
			}
			
			if ($tags_str)
			{
				$str = '<div id="contactInfo" class="section">';
				$str .= 'Related Tags: ';
				$str .= '<ul class="tagList">'."\n";
				$str .= $tags_str;
				$str .= '</ul>'."\n";
				$str .= '</div>';
				return $str;
			}
		}
	}
	
	/**
	 * Get the person entity for the currently logged in user.
	 */
	protected function get_person()
	{
		if(!isset($this->person))
		{
			$this->person = false;
			if($username = reason_check_authentication())
			{
				$this->person = new $this->config->person_class($username);
			}
		}
		return $this->person;
	}
	
	/**
	 * If you want to require authentication to view tag data (necessary if any of
	 * the individuals in the profiles system should not have their names exposed)
	 * put the appropriate logic here.
	 */
	protected function should_require_authentication()
	{
		return false;
	}
	
	protected function shuffle_assoc(&$array) 
	{
		$keys = array_keys($array);
		shuffle($keys);
		foreach($keys as $key) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
	}	
}
