<?php
/**
 * Profile Functions - shared functions for profiles. Link building for now.
 *
 * Helps with tasks, including
 * 
 * - link building
 *
 * @todo restructure or make into a class so multiple profile sites could exist.
 *
 * @author Nathan White
 *
 * @todo implement profile_get_explore_slug() 
 */
  
/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once( 'config/modules/profile/config.php' );
reason_include_once( 'function_libraries/url_utils.php' );

/**
 * Construct a profile link -
 *
 * - params set to a value use that value
 * - params set to an empty string are removed from the current URL if present
 * - params set to NULL maintain any present value they might have
 *
 * @todo the base_path concatenation needs work to be robust (or to be replaced).
 *
 * @author Nathan White
 */
function _profile_construct_link($params, $type, $is_redirect)
{
	$config = profile_get_config();
	if ($page = profile_get_profile_page())
	{
		$url = reason_get_page_url($page);
		$base_path = parse_url($url, PHP_URL_PATH);
	}
	else $base_path = profile_get_base_url();
	
	if ($type == 'explore')
	{
		if (!empty($config->explore_controller))
		{
			if ($config->friendly_urls)
			{
				$base_path .= '/explore/';
			}
			else
			{
				$params['explore'] = 1;
			}
		}
		elseif ($page = profile_get_profile_explore_page())
		{
			$url = reason_get_page_url($page);
			$base_path = parse_url($url, PHP_URL_PATH);
		}
	}
	elseif ($type == 'list')
	{
		if (!empty($config->list_controller))
		{
			if ($config->friendly_urls)
			{
				$base_path .= '/list/';
			}
			else
			{
				$params['list'] = 1;
			}
		}
		elseif ($page = profile_get_profile_list_page())
		{
			$url = reason_get_page_url($page);
			$base_path = parse_url($url, PHP_URL_PATH);
		}
	}
	$preserve_params = array();
	foreach ($params as $k=>$v)
	{
		if (is_null($v))
		{
			$preserve_params[] = $k;
		}
		else $new_params[$k] = $v;
	}
	// remove from new_params and add to URL according to friendliness rules
	if ($config->friendly_urls)
	{
		if ($type == 'profile')
		{
			if (!empty($new_params['username']))
			{
				$base_path .= urlencode($new_params['username']);
				unset($new_params['username']);
			}
		}
		
		if ($type == 'explore') // support tag rewrite;
		{
			if (!empty($new_params['tag']))
			{
				$base_path .= urlencode($new_params['tag']);
				unset($new_params['tag']);
			}
		}
	}
	return ($is_redirect) ? carl_construct_redirect($new_params, $preserve_params, $base_path) : carl_construct_link($new_params, $preserve_params, $base_path);
}

function profile_construct_link($params = array())
{
	return _profile_construct_link($params, 'profile', false);
}

function profile_construct_redirect($params = array())
{
	return _profile_construct_link($params, 'profile', true);
}

function profile_construct_explore_link($params = array())
{
	return _profile_construct_link($params, 'explore', false);
}

function profile_construct_explore_redirect($params = array())
{
	return _profile_construct_link($params, 'explore', true);
}

function profile_construct_list_link($params = array())
{
	return _profile_construct_link($params, 'list', false);
}

function profile_construct_list_redirect($params = array())
{
	return _profile_construct_link($params, 'list', true);
}

function profile_get_base_url()
{
	$config = profile_get_config();
	$site_id = id_of($config->site_unique_name);
	$site = new entity($site_id);
	return $site->get_value('base_url');
}

function profile_get_site_id()
{
	$config = profile_get_config();
	return id_of($config->site_unique_name);
}

/**
 * @todo return the slug of the page that runs the profile/explore module
 */
function profile_get_explore_slug()
{
	return 'explore/';
}

/**
 * Return profile entities on a site.
 *
 * @todo abstract me into a class.
 */
function profile_get_site_profile_entities($site_id)
{
	$es = new entity_selector( $site_id );
	$es->description = 'Selecting profiles on site';
	$es->add_type( id_of('profile_type') );
	$results = $es->run_one();
	return $results;	
}

/**
 * Return profile entities on a site.
 *
 * @todo explore not fully implemented in core profiles I don't think this is used but leaving in case we want it.
 */
function profile_get_site_profile_entities_by_tag($site_id, $section, $tag)
{
	// get rel_id
	$config = profile_get_config();
	if (isset($config->tag_section_relationship_names[$section]))
	{
		
		$rel_id = relationship_id_of($config->tag_section_relationship_names[$section]);
		if (!$rel_id) return false;
		
		// valid rel_id lets do it
		$es = new entity_selector( $site_id );
		$es->description = 'Selecting category from slug';
		$es->add_type( id_of('category_type') );
		$es->add_relation('slug = "' . mysql_real_escape_string($tag) .'"');
		$es->add_right_relationship_field($config->tag_section_relationship_names[$section], 'entity', 'id', 'profile_id');
		$results = $es->run_one();
		if (!empty($results))
		{
			$result = reset($results);
			$es2 = new entity_selector( $site_id );
			$es2->description = 'Selecting profiles on site';
			$es2->add_type( id_of('profile_type') );
			$es2->add_left_relationship($result->id(), $rel_id);
			$results2 = $es2->run_one();
			return $results2;
		}
	}
	return false;
}

/**
 * Return a link to the profile list module on the site if it exists 
 *
 * @todo basic fallback needs to be friendly URL compatible
 */
function profile_get_list_link($link_title, $html = true)
{
	$url = profile_construct_list_link();
	return ($html) ? '<a href="'.$url.'">'.$link_title.'</a>' : $url;
}

/**
 * Return a link to the profile list module on the site if it exists 
 *
 * @todo basic fallback needs to be friendly URL compatible
 */
function profile_get_explore_link($link_title, $html = true)
{
	$url = profile_construct_explore_link();
	return ($html) ? '<a href="'.$url.'">'.$link_title.'</a>' : $url;
}
	
function profile_get_profile_page()
{
	static $page;
	if (!isset($page))
	{
		$es = new entity_selector(profile_get_site_id());
		$es->limit_tables('page_node');
		$es->limit_fields();
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('page_node.custom_page = "profile"');
		$result = $es->run_one();
		$page = (!empty($result)) ? reset($result) : false;
	}
	return $page;
}

function profile_get_profile_explore_page()
{
	static $page;
	if (!isset($page))
	{
		$es = new entity_selector(profile_get_site_id());
		$es->limit_tables('page_node');
		$es->limit_fields();
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('page_node.custom_page = "profile_explore"');
		$result = $es->run_one();
		$page = (!empty($result)) ? reset($result) : false;
	}
	return $page;
}

function profile_get_profile_list_page()
{
	static $page;
	if (!isset($page))
	{
		$es = new entity_selector(profile_get_site_id());
		$es->limit_tables('page_node');
		$es->limit_fields();
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('page_node.custom_page = "profile_list"');
		$result = $es->run_one();
		$page = (!empty($result)) ? reset($result) : false;
	}
	return $page;
}

/**
 * Return our config object
 */
function profile_get_config()
{
	static $config;
	if (!isset($config))
	{
		$config = new ProfileConfig();
	}
	return $config;
}