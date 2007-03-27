<?php

$GLOBALS[ '_html_editor_plasmature_types' ][ basename( __FILE__) ] = 'loki2';
$GLOBALS[ '_html_editor_param_generator_functions' ][ basename( __FILE__) ] = 'get_loki_2_params';
$GLOBALS[ '_html_editor_options_function' ][ basename( __FILE__) ] = 'get_loki_2_options';

	/**
	 * Gets the paths/URLs that loki needs in a format that plasmature can use
	 *
	 * returns an array with the following keys:
	 *  -- site_feed (the URL of the feed that lists sites available, and contains the URLs of the feeds for their types)
	 *  -- finder_feed (the URL of the feed that will provide the URLs of the site and type feeds that point to a given URL passed in the query string)
	 *  -- image_feed (the URL of the feed that lists images in the site, and their URLs)
	 *  -- default_site_regexp (a js-style regex that will find the current site's type feed URL in the site feed)
	 *  -- default_type_regexp (a js-style regex thatr will find the page type in a type feed)
	 *
	 * @param int $site_id The id of the current site
	 * @return array $paths
	 *
	 * @author Matt Ryan
	 * @date 2006-09-21
	 */
	function get_loki_2_paths_for_reason($site_id)
	{
		include_once('paths.php');
		include_once(LOKI_2_INC.'Loki.php');
		$paths = array();
		// This commented-out section will allow us to only show live sites to other live sites
		/* $site_feed_query = '';
		if(!empty($site_id))
		{
			$e = new entity($site_id);
			if($e->get_value('site_state') != 'Live')
			{
				$site_feed_query = '?hide_non_live_sites=true';
			}
		}
				
		$paths['site_feed'] = 'https://'.REASON_HOST.'/feeds/sites/editor'.$site_feed_query; */
//		$paths['site_feed'] = 'https://'.REASON_HOST.'/feeds/sites/editor';
//		$paths['finder_feed'] = 'https://'.REASON_HOST.'/feeds/finder';

		$id_of_site_type = id_of('site');
		$paths['site_feed'] = 'https://'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.$id_of_site_type.'&site_id='.id_of('master_admin').'&feed=editor_sites';
		$paths['finder_feed'] = 'https://'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.$id_of_site_type.'&feed=editor_feed_finder';
		if(!empty($site_id))
		{
			$paths['image_feed'] = 'https://'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('image').'&feed=images&site_id='.$site_id;
			$paths['default_site_regexp'] = Loki2::js_regexp_quote('//'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('type').'&site_id='.$site_id);
		}
		else
		{
			$paths['image_feed'] = '';
			$paths['default_site_regexp'] = '';
			trigger_error('No site id passed to get_loki_paths');
		}
		$paths['default_type_regexp'] = Loki2::js_regexp_quote('//'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('minisite_page').'&site_id=').'[^&]*'.Loki2::js_regexp_quote('&feed=editor_links_for_minisite_page');
		return $paths;
	}
	
	function get_loki_2_params($site_id, $user_id = 0)
	{
		
		$params = array();
		
		// site id
		$params['site_id'] = $site_id;
		
		// paths
		$params['paths'] = get_loki_2_paths_for_reason($site_id);
		
		// default widgets
		$site = new entity($site_id);
		if($site->get_value( 'loki_default' ))
		{
			$params['widgets'] = $site->get_value( 'loki_default' );
		}
		
		// user is admin
		if( !empty($user_id) && (user_is_a($user_id, id_of('admin_role'))
					|| user_is_a($user_id, id_of('power_user_role') ) ) )
		{
			$params['user_is_admin'] = true;
		}
		else
		{
			$params['user_is_admin'] = false;
		}
		return $params;
		
		
	}
	
	function get_loki_2_options()
	{
		include_once(LOKI_2_INC.'Loki_Options.php');
		
		$options_object = new Loki2_Options();
		$options = $options_object->get_all();
		foreach ( $options as $k => $v )
			$options[$k] = prettify_string($k);
		return $options;
	}
?>