<?php
/**
 * A script to create an xml sitemap of all indexable pages.
 *
 * It is advisable to set this file up to run as a cron job and then pipe its output to a web-available location. This will provide a regular pattern for updating your sitemap as content is added or removed from your site. The output file should then be added as a line to your robots.txt file, telling Google et al. where to find it.
 *
 * The proper format for including the output file in your robots.txt file is Sitemap: http://yoursite.edu/path_to_yourfilename.xml as outlined by Google Webmaster Tools.
 *
 * Example command-line usage:
 *
 * curl "http://yourdomain.com/reason/www/scripts/sitemap/sitemap.php" --connect-timeout 3600 --max-time 3600 -g > /path/to/webroot/sitemap.xml
 *
 * This script has a timeout of one hour. If things are taking longer than that, you could try upping the time limit in the curl
 * command and in this script, but a better solution would be to reqork the script for better performance.
 *
 * Advanced options:
 * default_priority: A value between 0.0 and 1.0. Default is 0.5.
 * home_page_priority: A value between 0.0 and 1.0 Default is 0.5.
 * page_type_priorities: An array of page type names to priorities. Default is 0.5.
 * exclude_sites: An array of site unique names to exclude. All live sites are included by default.
 *
 * Example URL with advanced options:
 *
 * http://yourdomain.com/reason/www/scripts/sitemap/sitemap.php?default_priority=0.3&home_page_priority=0.8&page_type_priorities[events]=0.6&page_type_priorities[publication]=0.5&exclude_sites[]=login_site&exclude_sites[]=some_other_site
 *
 * @package reason
 * @subpackage scripts
 * @author Amanda Frisbee
 * @author Matt Ryan
 */
	/**
	 * Include Reason libraries
	 */
	include_once('reason_header.php');
	reason_include_once('classes/entity_selector.php');
	reason_include_once('minisite_templates/nav_classes/default.php');
	
	
	/**
	 * Run the script... but only when
	 */
	if($_SERVER[ 'REMOTE_ADDR' ] == $_SERVER[ 'SERVER_ADDR' ])
	{
		$default_priority = 0.5;
		if(!empty($_GET['default_priority']))
			$default_priority = round((float) $_GET['default_priority'], 1);
			
		$home_page_priority = 0.5;
		if(!empty($_GET['home_page_priority']))
			$home_page_priority = round((float) $_GET['home_page_priority'], 1);
		
		$page_type_priorities = array();
		if(!empty($_GET['page_type_priorities']))
		{
			$ptp = (array) $_GET['page_type_priorities'];
			foreach($ptp as $page_type => $priority)
			{
				$page_type_priorities[$page_type] = round((float) $priority, 1);
			}
		}
		
		$exclude_sites = array();
		if(!empty($_GET['exclude_sites']))
		{
			$sites = (array) $_GET['exclude_sites'];
			foreach($sites as $site_name)
			{
				if(reason_unique_name_exists($site_name))
					$exclude_sites[] = id_of($site_name);
			}
		}
		
		// This script could take a while if the 
		set_time_limit(3600);
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_relation('site_state = "Live"');
		/* Add new relation to indicate to only include sites that are indexable. - AF*/
		//$es->set_num(200);
		if(!empty($exclude_sites))
			$es->add_relation('`entity`.`id` NOT IN ("'.implode('","',$exclude_sites).'")');
		$sites = $es->run_one();
		
		$info = array();
		foreach($sites as $site_id=>$site)
		{
			$pages = new MinisiteNavigation();
			$pages->site_info =& $site;
			
			//for a bot the order probably does not matter, and adding this line will slow things down
			//$pages->order_by = 'sortable.sort_order'
			
			$pages->init( $site_id, id_of('minisite_page') );
			foreach($pages->values as $page_id=>$page)
			{
				if( !$page->get_value( 'url' ) )
				{
					/* Add code here to only include pages that are indexable. (To be
					implemented after toggle is added to page interface.) - AF */
					if ('1' == $page->get_value('indexable'))
					{
						turn_carl_util_error_output_off();
						$url = $pages->get_full_url( $page_id, true );
						turn_carl_util_error_output_on;
						if(!empty($url))
						{
							$info[$page_id] = array('loc' => $url);
							$priority = $default_priority;
							if($pages->root_node() == $page_id)
								$priority = $home_page_priority;
							$page_type = $page->get_value('custom_page');
							if(empty($page_type))
								$page_type = 'default';
							if(isset($page_type_priorities[$page_type]))
								$priority = $page_type_priorities[$page_type];
							$info[$page_id]['priority'] = $priority;
						}
					}
				}
			}
		}
		
		header('Content-type: text/xml');
		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
		foreach(array_keys($info) as $key)
		{
			echo '<url>'."\n";
			echo "\t".'<loc>'.htmlspecialchars($info[$key]['loc']).'</loc>'."\n";
			echo "\t".'<priority>'.htmlspecialchars($info[$key]['priority']).'</priority>'."\n";
			echo '</url>'."\n";
		}
		echo '</urlset>'."\n";
	}
	else
	{
		echo '<!DOCTYPE html>';
		echo '<html>';
		echo '<body>';
		echo "<h1>Sorry! This script can only be run by an HTTP request from the server itself.</h1>";
		echo '<h2>Try this from the command line:</h2>';
		echo '<code>curl "'.htmlspecialchars(get_current_url()).'" --connect-timeout 3600 --max-time 3600 -g > '.htmlspecialchars(WEB_PATH).'sitemap.xml</code>';
		echo '<p>Add a line like the one above to your crontab if you want it to regularly refresh.</p>';
		echo '<h2>Advanced options:</h2>';
		echo '<ul>';
		echo '<li>default_priority: A value between 0.0 and 1.0. Default is 0.5.</li>';
		echo '<li>home_page_priority: A value between 0.0 and 1.0 Default is 0.5.</li>';
		echo '<li>page_type_priorities: An array of page type names to priorities. Default is 0.5.</li>';
		echo '<li>exclude_sites: An array of site unique names to exclude. All live sites are included by default.</li>';
		echo '</ul>';
		echo '<h2>Advanced example:</h2>';
		echo '<code>curl "'.htmlspecialchars(get_current_url()).'?default_priority=0.4&home_page_priority=0.9&page_type_priorities[events]=0.6&exclude_sites[]=site_login" --connect-timeout 3600 --max-time 3600 -g > '.htmlspecialchars(WEB_PATH).'sitemap.xml</code>';
		echo '</body>';
		echo '</html>';
	}
	
?>