<?php

/**
 * @package reason
 */

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
		if (file_exists(LOKI_2_INC.'loki.php'))
		{
			include_once(LOKI_2_INC.'loki.php');
		}
		else
		{
			trigger_error('Loki 2 file structure has changed slightly. Please update LOKI_2_INC in package_settings.php to reference the ' . LOKI_2_INC . '/helpers/php/ directory.');
			include_once( LOKI_2_INC.'/helpers/php/loki.php' );
		}
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
				
		$paths['site_feed'] = securest_available_protocol() . '://'.REASON_HOST.'/feeds/sites/editor'.$site_feed_query; */
//		$paths['site_feed'] = securest_available_protocol() . '://'.REASON_HOST.'/feeds/sites/editor';
//		$paths['finder_feed'] = securest_available_protocol() . '://'.REASON_HOST.'/feeds/finder';

		$id_of_site_type = id_of('site');
		$paths['site_feed'] = FEED_GENERATOR_STUB_PATH.'?type_id='.$id_of_site_type.'&site_id='.id_of('master_admin').'&feed=editor_sites';
		$paths['finder_feed'] = FEED_GENERATOR_STUB_PATH.'?type_id='.$id_of_site_type.'&feed=editor_feed_finder';
		$loki_obj = new Loki2('temp');
		if(!empty($site_id))
		{
			$paths['image_feed'] = FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('image').'&feed=images&site_id='.$site_id;
			$paths['default_site_regexp'] = $loki_obj->js_regexp_quote('//'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('type').'&site_id='.$site_id);
		}
		else
		{
			$paths['image_feed'] = '';
			$paths['default_site_regexp'] = '';
			trigger_error('No site id passed to get_loki_paths');
		}
		$paths['default_type_regexp'] = $loki_obj->js_regexp_quote('//'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('minisite_page').'&site_id=').'[^&]*'.$loki_obj->js_regexp_quote('&feed=editor_links_for_minisite_page');
		if(defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH)
		{
			$paths['css'] = array(UNIVERSAL_CSS_PATH);
		}
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
			$map = get_loki_2_options_map();
			if(array_key_exists($site->get_value( 'loki_default' ),$map))
			{
				$params['widgets'] = $map[$site->get_value( 'loki_default' )]['maps_to'];
			}
			else
			{
				$params['widgets'] = $site->get_value( 'loki_default' );
			}
		}
		else
		{
			$params['widgets'] = 'default';
		}
		
		// user is admin
		if( !empty($user_id) && (user_is_a($user_id, id_of('admin_role'))
					|| user_is_a($user_id, id_of('power_user_role') ) ) )
		{
			$params['widgets'] .= ' +source +debug';
		}
		else
		{
			$params['widgets'] .= ' -source -debug';
		}
		return $params;
		
		
	}
	
	function get_loki_2_options_map()
	{
		$options = array(
			'notables' => array('label'=>'Standard (All minus Tables &amp; Pre)','maps_to'=>'all -pre -underline -table'),
			'default' => array('label'=>'Loki 2 Default Set','maps_to'=>'default'),
			'all' => array('label'=>'Most (no underline)','maps_to'=>'all -underline'),
			'all_minus_pre' => array('label'=>'Most minus Pre','maps_to'=>'all -pre -underline'),
			'notables_plus_pre' => array('label'=>'Most minus Tables','maps_to'=>'all -underline -table'),
		);
		return $options;
	}
	
	function get_loki_2_options()
	{
		$map = get_loki_2_options_map();
		$ret = array();
		foreach($map as $key=>$option)
		{
			$ret[$key] = $option['label'];
		}
		return $ret;
	}
?>
