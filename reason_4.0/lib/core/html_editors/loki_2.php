<?php

/**
 * @package reason
 * @subpackage html_editors
 * @author Matt Ryan
 */
 
reason_include('html_editors/base.php');

// Identify the class that should be used
$GLOBALS[ '_reason_editor_integration_classes' ][ basename( __FILE__) ] = 'reasonLoki2Integration';

/**
 * An editor integration class for Loki 2
 * @package reason
 * @subpackage html_editors
 */
class reasonLoki2Integration extends reasonEditorIntegrationBase
{
	/**
	 * Get the name of the plasmature element that should be used for this editor
	 * @return string name of the plasmature element
	 */
	function get_plasmature_type()
	{
		return 'loki2';
	}
	
	/**
	 * Get the appropriate parameters to pass to the plasmature element
	 * @param integer $site_id The Reason id of the site in which this editor is being invoked
	 * @param integer $user_id The Reason id of the current user (0 if user is anonymous or not in the Reason user store)
	 * @return array plasmature parameters 
	 */
	function get_plasmature_element_parameters($site_id, $user_id = 0)
	{
		
		$params = array();
		
		// site id
		$params['site_id'] = $site_id;
		
		// paths
		$params['paths'] = $this->_get_paths($site_id);
		
		// default widgets
		$site = new entity($site_id);
		if($site->get_value( 'loki_default' ))
		{
			$map = $this->_get_options_map();
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
		if(defined('REASON_DEFAULT_ALLOWED_TAGS'))
		{
			$params['allowable_tags'] = explode(',',str_replace(array('><','<','>'),array(',','',''),REASON_DEFAULT_ALLOWED_TAGS));
		}
		if(defined('REASON_LOKI_CRASH_REPORT_URI') && REASON_LOKI_CRASH_REPORT_URI != '' )
		{
			$params['crash_report_uri'] = REASON_LOKI_CRASH_REPORT_URI;
		}
		return $params;
	}
	
	/**
	 * Get the available configuration options for the editor
	 *
	 * These options are presented to administrators when setting up a Reason site
	 * Each option must be represented as a string <= 256 bytes, since it is stored in a tinytext field in the database
	 *
	 * @return array keys are values to be stored in the db and can then be used by @get_plasmature_element_parameters() when setting up the plasmature element, values are labels that are presented to the administrator
	 */
	function get_configuration_options()
	{
		$map = $this->_get_options_map();
		$ret = array();
		foreach($map as $key=>$option)
		{
			$ret[$key] = $option['label'];
		}
		return $ret;
	}
	
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
	 * @access private
	 *
	 * @author Matt Ryan
	 * @date 2006-09-21
	 */
	function _get_paths($site_id)
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

		$id_of_site_type = id_of('site');
		$paths['site_feed'] = FEED_GENERATOR_STUB_PATH.'?type_id='.$id_of_site_type.'&site_id='.id_of('master_admin').'&feed=editor_sites&site_context='.$site_id;
		$paths['finder_feed'] = FEED_GENERATOR_STUB_PATH.'?type_id='.$id_of_site_type.'&feed=editor_feed_finder&site_context='.$site_id;
		$loki_obj = new Loki2('temp');
		if(!empty($site_id))
		{
			$paths['image_feed'] = FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('image').'&feed=images&site_id='.$site_id;
			$paths['default_site_regexp'] = $loki_obj->js_regexp_quote('//'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('type').'&site_id='.$site_id).'[$&]';
		}
		else
		{
			$paths['image_feed'] = '';
			$paths['default_site_regexp'] = '';
			trigger_error('No site id passed to get_loki_paths');
		}
		$paths['default_type_regexp'] = $loki_obj->js_regexp_quote('//'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('minisite_page').'&site_id=').'[^&]*'.$loki_obj->js_regexp_quote('&feed=editor_links_for_minisite_page');
		$css = array();
		if(defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH)
		{
			$css[] = UNIVERSAL_CSS_PATH;
		}
		if(defined('REASON_LOKI_CSS_FILE') && REASON_LOKI_CSS_FILE)
		{
			$css[] = REASON_LOKI_CSS_FILE;
		}
		if(!empty($css))
		{
			$paths['css'] = $css;
		}
		return $paths;
	}
	
	/**
	 * @access private
	 */
	function _get_options_map()
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
}

?>
