<?php
	/**
	 *	Minisite front end page generation
	 *
	 *	Gets site id and page id from passed paramaters
	 *
	 *	Gets all minisite information from that, finds the proper template, and
	 *  generates the page.
	 *
	 *  Also handles page caching.
	 *
	 *	@author Dave Hendler
	 * 	@package reason
	 * 	@subpackage minisite
	 */
	
	/**
	 *  Ok.  The way this page works has been altered in parts and in order to maximize the efficiency of the caching
	 *  mechanism.
	 */
	 
	/**
	 * We don't want people to call generate_page.php directly so we check to see if their requested
	 * path ends in '.php' and give them an error if it does. Keeps people from fishing around for not
	 * live sites and trying to find things they shouldn't.
	 */
	include_once('reason_header.php');
	$request_uri = get_current_url();
	$parts = parse_url($request_uri);
	if( substr($parts['path'],-4) == ".php" )
	{
		include(ERROR_403_PAGE);
		die();
	}
	
	function get_theme($site_id) //{{{
	{
		$es = new entity_selector();
		$es->add_type( id_of( 'theme_type' ) );
		$es->add_right_relationship( $site_id , relationship_id_of( 'site_to_theme' ) );
		$tmp = $es->run_one();
		return current( $tmp );
	} // }}}
	function get_minisite_template($theme_id) //{{{
	{
		$template = false;
		$es = new entity_selector();
		$es->add_type(id_of('minisite_template'));
		$es->add_right_relationship( $theme_id, relationship_id_of('theme_to_minisite_template'));
		$tmp = $es->run_one();
		return current( $tmp );
	} // }}}
	
	// ensures site_entity is a site and optionally that page_id corresponds to a live page on the site
	function get_validated_site($site_id, $page_id) 
	{
		if ($site_id != $_REQUEST['site_id'])
		{
			trigger_error('the site id in $_REQUEST[\'site_id\'] - ' . $_REQUEST['site_id'] . ' - does not match the site id in the .htaccess file - ' . $site_id . ' - possible hack attempt.', FATAL);
			die;			
		}
		if ($page_id != $_REQUEST['page_id'])
		{
			trigger_error('the page id in $_REQUEST[\'page_id\'] - ' . $_REQUEST['page_id'] . ' - does not match the page id in the .htaccess file - ' . $page_id . ' - possible hack attempt.', FATAL);
			die;			
		}

		$page = new entity($page_id);
		if (!$page->get_values())
		{
			trigger_error('generate_page could not generate page id ' . $page_id . ' - the page entity was empty', FATAL);
			die;
		}
		if ($page->get_value('type') != id_of('minisite_page'))
		{
			trigger_error('generate_page passed page_id ' . $page_id . ' which is not an entity of type page', FATAL);
			die;
		}
		if (($page->get_value('state') != 'Live') && ($page->get_value('state') != 'Pending'))
		{
			trigger_error('generate_page called with page_id ' . $page_id . ' which is not a live or pending page', FATAL);
			die;
		}
		
		$actual_site_id = get_owner_site_id($page_id);
		if (empty($actual_site_id))
		{
			trigger_error('page must have an owner site to be displayed', FATAL);
			die;
		}
		if ($actual_site_id != $site_id)
		{
			trigger_error('generate page called with site_id ' . $site_id . ', but the actual owner of page_id ' . $page_id . ' is ' . $actual_site_id .'. Rewrites may need to be run.', FATAL);
			die;
		}
		
		$site = new entity($actual_site_id);
		if (!$site->get_values())
		{
			trigger_error('generate_page could not generate a page for site id ' . $actual_site_id . ' - the site entity was empty', FATAL);
			die;
		}
		if ($site->get_value('type') != id_of('site'))
		{
			trigger_error('generate_page called with a page whose owner with id ' . $actual_site_id . ' is not an entity of type site', FATAL);
			die;
		}
		return $site;
	}

	$collect_cache_stats = true;
	
	header("Content-Type: text/html; charset=UTF-8");
    
    // Apache >=2.0.48 sets the REDIRECT REMOTE USER and not the REMOTE USER if an internal redirect
    // is sent to an unauthenticated (no BASIC AUTH applied) page.  This gets around our code not
    // being aware of the change. 
    if(empty($_SERVER['REMOTE_USER']) AND !empty($_SERVER['REDIRECT_REMOTE_USER']))
		$_SERVER['REMOTE_USER'] = $_SERVER['REDIRECT_REMOTE_USER'];
    //if(!empty($_SERVER['REMOTE_USER'])){ $_SERVER['REMOTE_USER'] = $_SERVER['REDIRECT_REMOTE_USER']; }

	include_once( CARL_UTIL_INC . 'cache/cache.php' );
		
	$reason_session = false;
	reason_include_once( 'classes/entity.php' );
	connectDB( REASON_DB );
	
	$s = get_microtime();
	
	/* if(function_exists('xdebug_start_profiling'))
		xdebug_start_profiling(); */
	
	$site_id = !empty( $_GET['site_id'] ) ? turn_into_int($_GET['site_id']) : ''; // force to int
	$page_id = !empty( $_GET['page_id'] ) ? turn_into_int($_GET['page_id']) : ''; // force to int

	if( !empty( $site_id ) && !empty( $page_id )) // need site_id and page_id to proceed
	{
		$site = get_validated_site( $site_id, $page_id );

		// Since we are using mod_rewrite to handle all URLs for the minisites,
		// we have to do a little fancy footwork to get any variables passed
		// on the GET string.  Basically, the original REQUEST_URI has the
		// query string we are interested in, so we parse that URL and then
		// parse the query string.  Then, we merge the two query strings back
		// into the superglobal one.
		// ALSO - this needs to happen before caching so we have access to the REQUEST vars in the proper way

		$my_request = array();
		if( !empty( $parts[ 'query' ] ) )
			parse_str( $parts[ 'query' ], $my_request );
		// original request clobbers new request
		// GET global also merged so that we can differentiate between gets and posts
		if( !empty( $my_request ) )
		{
			$_GET = array_merge( $my_request, $_GET );
			$_REQUEST = array_merge( $my_request, $_REQUEST );
			$_REQUEST['site_id'] = $site_id;
			$_REQUEST['page_id'] = $page_id;
		}
	
		// Determine whether to use caching or not
		$no_cache_reasons = array();
		if( $site->get_value( 'use_page_caching' ) )
		{
			$use_cache = true;
		}
		else
	    	{
			$use_cache = false;
			$no_cache_reasons[] = 'unsupported site';
		}
		// did we hit or miss the cache if we used it?
		$cache_hit = null;

		//-----------------------------------------------------------
		// CONDITION UNDER WHICH WE SHOULD NOT USE PAGE CACHING
		//  - if visitor is a listed developer
		//  - if something was _POSTed
		//  - if there is an active reason session
		//  - ** future ** if a module tells us not to use caching
		//-----------------------------------------------------------
		$sess = get_reason_session();
		
		if( is_developer() )
		{
			$use_cache = false;
			$no_cache_reasons[] = 'developer';
		}
		if( !empty( $_POST ) )
		{
			$use_cache = false;
			$no_cache_reasons[] = '_POST';
		}
		if ( $sess->exists() )
		{
			$use_cache = false;
			$no_cache_reasons[] = 'session';
		}

		// Check the cache
		if( $use_cache )
		{
			$cache = new PageCache();
			$cache->dir = REASON_CACHE_DIR;
			$page = $cache->fetch( get_current_url() );
			// if $page is not empty, we've got a hit.  otherwise, we have a miss and we need to refresh the cache
			$cache_hit = !empty( $page );
		}
		
		// we actually run the page code if we are not using the caching system at all OR
		// if we are using the system but we have a miss.
		// this is kind of a roundabout logical statement, but I think it's the clearest.  It could be reduced, but I'll
		// go with clarity this time.  I know it's rare.
		// Upon a little reflection, this is not very clear.  but it works.
		if( !$use_cache OR ($use_cache AND !$cache_hit) )
		{
			// A small assurance - the cache will only contain the output of the following code.  Anything printed
			// before the ob_start() or after the ob_end_clean() will NOT be contained in the saved cache.  Ah, the
			// beauty of stackable output buffers.
			ob_start();
			
			reason_include_once( 'classes/entity_selector.php' );
			// get minisite template for this minisite
			$theme = get_theme( $site_id );
			if( $theme )
				$template = get_minisite_template($theme->id());
			else
			{
				// perhaps we should use a default theme if none is found?
				trigger_error('Site id '.$site_id.' does not have a theme associated with it, so minisite index cannot determine which template to use', FATAL);
				die();
			}
			
			if( !empty( $template ) )
			{
				$filename = $template->get_value('name').'.php';
			}
			else
			{
				trigger_error('Theme id '.$theme->id().' does not have a template associated with it, so minisite index cannot determine which template to use', FATAL);
				die();
			}
			
			$include_path = 'minisite_templates/'.$filename;
			reason_include_once( $include_path );
			if(empty($GLOBALS[ '_minisite_template_class_names' ][ $filename ]))
			{
				trigger_error('Unable to use specified template ('.htmlspecialchars($filename,ENT_QUOTES,'UTF-8').') because it does not have a class name properly set in the array $GLOBALS[ \'_minisite_template_class_names\' ].');
				reason_include_once( 'minisite_templates/default.php' );
				$minisite_template = $GLOBALS[ '_minisite_template_class_names' ][ 'default' ];
			}
			else
			{
				$minisite_template = $GLOBALS[ '_minisite_template_class_names' ][ $filename ];
			}
			
			$t = new $minisite_template;
			$t->template_id = $template->id();
			$t->set_theme( $theme );
			$t->initialize($site_id,$page_id);
			$t->run();
			$page = ob_get_contents();
			ob_end_clean();
		}
		
		///////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////
		//    display the page
		///////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////
		echo $page;
		
		// if we're using a cache, make sure to store the new result
		// also, record stats about misses here
		
		$page_gen_time = round( 1000 * (get_microtime() - $s) );
		if( $use_cache AND !$cache_hit )
		{
			$cache->page_gen_time = $page_gen_time;
			//echo $cache->page_gen_time;
			$cache->store( get_current_url(), $page );
		}
		if( is_developer() )
		{
			$str = $page_gen_time.' ms | ';
			if( $use_cache )
			{
				$str .= 'caching is ON: ';
				if( $cache_hit )
					$str .= 'hit';
				else
					$str .= 'miss';
			}
			else
				$str .= 'caching is OFF: '.implode(', ',$no_cache_reasons);
			
			echo "\n".'<div style="background-color:#ddd;color:#555;font-size:0.75em;padding:1px 1em;">';
			echo '<p>'.$str.'</p>';
			if (isset($t) && method_exists($t, 'display_developer_section'))
			{	
				$t->display_developer_section();
			}
			if(defined('THIS_IS_A_DEVELOPMENT_REASON_INSTANCE') && THIS_IS_A_DEVELOPMENT_REASON_INSTANCE)
			{
				echo '<p style="color:#777;">This instance of Reason is set up as a development/testing instance. As a result, this page is hidden from search engines. If this is an error, modify the setting THIS_IS_A_DEVELOPMENT_REASON_INSTANCE.</p>';
			}
			echo '</div>';
		}
		reason_log_page_generation_time($page_gen_time);
	}
	/* if(function_exists('xdebug_dump_function_profile'))
	{
		xdebug_dump_function_profile(4);
	} */
?>
