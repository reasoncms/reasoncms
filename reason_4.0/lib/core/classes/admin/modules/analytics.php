<?php

/**
 * Google Analytics Admin Module
 * @package reason
 * @subpackage admin
 * @author Steve Smith
 */
 
/**
* Include the default module
*/
reason_include_once('classes/admin/modules/default.php');

/** 
 * api dependencies
 */
include_once( SETTINGS_INC . 'google_api_settings.php' );
require_once( GOOGLE_API_INC . 'Google_Client.php');
require_once( GOOGLE_API_INC . 'contrib/Google_AnalyticsService.php');
include_once( DISCO_INC . 'disco.php');
reason_include_once('classes/object_cache.php');
reason_include_once('classes/url/page.php');
/**
 * An administrative module that provides page view analytics of the current site using the Google Analytics API v.3
 */

/**
 * 	@todo tabbed tables
 * 	@todo cache google results
 */
class AnalyticsModule extends DefaultModule
{
	var $site;
	var $site_urls;
	var $pages;
	var $about;
	var $has_events;
	var $has_faq;
	var $has_news;
	var $has_policies;

	var $client;
	var $service;
	var $page_results;
	var $daily_results;
	var $source_results;
	var $event_results;
	var $news_results;
	var $policy_results;
	var $default_page;		//the Google Analytics Profile's defaultPage 
	
	var $startdate;
	var $enddate;
	var $ok_to_run;
	var $not_ok_to_run_message;
	
	/**
	 * Lifespan of URL cache for a site - default is 24 hours.
	 */
	var $url_cache_lifespan = 86400;

	
	function AnalyticsModule( &$page )
	{
		$this->admin_page =& $page;
	}
	
	function ok_to_run()
	{
		if(!isset($this->ok_to_run))
		{
			$this->ok_to_run = false;
		
			if(!defined('GOOGLE_API_PRIVATE_KEY_FILE') || !GOOGLE_API_PRIVATE_KEY_FILE)
			{
				$this->not_ok_to_run_message = 'The Google Analytics Module is not set up. An administrator should define GOOGLE_API_PRIVATE_KEY_FILE in Google API settings.';
				trigger_error('GOOGLE_API_PRIVATE_KEY_FILE is not defined.');
			}
			elseif(!file_exists(GOOGLE_API_PRIVATE_KEY_FILE))
			{
				$this->not_ok_to_run_message = 'The Google Analytics Module is not set up. An administrator should check the setting GOOGLE_API_PRIVATE_KEY_FILE because the file could not be found.';
				trigger_error('Unable to find a p12 file at GOOGLE_API_PRIVATE_KEY_FILE ('.GOOGLE_API_PRIVATE_KEY_FILE.')');
			}
			elseif(!is_readable(GOOGLE_API_PRIVATE_KEY_FILE))
			{
				$this->not_ok_to_run_message = 'The Google Analytics Module is not set up. An administrator should check the permissions on the GOOGLE_API_PRIVATE_KEY_FILE, which could not be read.';
				trigger_error('Unable to read the p12 file at  GOOGLE_API_PRIVATE_KEY_FILE ('.GOOGLE_API_PRIVATE_KEY_FILE).')';
				
			}
			else
			{
				$this->ok_to_run = true;
			}
		}
		return $this->ok_to_run;
	}
	
	function not_ok_to_run_message()
	{
		$this->ok_to_run();
		return $this->not_ok_to_run_message;
	}
	
	/**
	 * Standard Module init function
	 *
	 * Sets up page variables and runs the entity selctor that grabs the site's page url_fragments
	 * 
	 * @return void
	 */
	function init()
	{
		parent::init();
		
		$this->site = new entity( $this->admin_page->site_id );
		if (!empty($this->admin_page->request['type_id']))
		{
			$type_name = $this->admin_page->get_name($this->admin_page->request['type_id']);
			$id_name = $this->admin_page->get_name($this->admin_page->request['id']);
			$this->admin_page->title = 'Analytics for <em>' . $id_name . '</em> (' . $type_name . ')';
		} 
		else 
		{
			$this->admin_page->title = 'Analytics for '.$this->site->get_value('name') . ' (Site)';
		}
		
		if(!$this->ok_to_run())
			return;
		
		$this->head_items->add_javascript(JQUERY_UI_URL, true);
		$this->head_items->add_javascript(JQUERY_URL, true);
		$this->head_items->add_stylesheet(JQUERY_UI_CSS_URL);
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/google_api/analytics/analytics.js');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/google_api/analytics/analytics.css');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.pie.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.time.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.selection.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/js/jquery.tablesorter.min.js');
		$this->head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/css/theme.blue.css');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/jquery.tablesorter.pager.min.js');
		$this->head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/jquery.tablesorter.pager.css');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/js/jquery.tablesorter.widgets.min.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/js/jquery.tablesorter.widgets-filter-formatter.min.js');
		$this->head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/css/filter.formatter.css');

		// Initialise the Google Client object
		$this->client = new Google_Client();
		// Your 'Product name'
		$this->client->setApplicationName(GOOGLE_API_APP_NAME);
		
		$this->client->setAssertionCredentials(
			new Google_AssertionCredentials(
				GOOGLE_API_SERVICE_EMAIL, // email you added to GA
			array('https://www.googleapis.com/auth/analytics.readonly'),
			file_get_contents(GOOGLE_API_PRIVATE_KEY_FILE)  // keyfile you downloaded
			)
		);
		// other settings
		$this->client->setClientId(GOOGLE_API_SERVICE_CLIENT_ID);
		// Return results as objects.
		$this->client->setUseObjects(true);
		$this->client->setAccessType('offline_access');  // this may be unnecessary?

		// create analytics service
		$this->service = new Google_AnalyticsService($this->client);

		// get management profiles
		$profiles = $this->service->management_profiles->listManagementProfiles(GOOGLE_ANALYTICS_ACCOUNT_ID, GOOGLE_ANALYTICS_PROPERTY_ID);
		// get the items
		$items = $profiles->getItems();
		// set the $default_page
		foreach ($items as $profile) {
			$this->default_page = $profile->getDefaultPage();
		}

		//Site has events?
		$es = new entity_selector($this->admin_page->site_id);
		$es->limit_tables();
		$es->add_type(id_of('event_type'));
		$this->has_events = $es->run_one();

		//Site has faq?
		$es = new entity_selector($this->admin_page->site_id);
		$es->limit_tables();
		$es->add_type(id_of('faq_type'));
		$this->has_faq = $es->run_one();

		//Site has news?
		$es = new entity_selector($this->admin_page->site_id);
		$es->limit_tables();
		$es->add_type(id_of('news'));
		$this->has_news = $es->run_one();

		//Site has policies?
		$es = new entity_selector($this->admin_page->site_id);
		$es->limit_tables();
		$es->add_type(id_of('policy_type'));
		$this->has_policies = $es->run_one();

		
	}

	/**
	 * 	Compare the Google Analytics array (rows) where pagePath contains 
	 * 	'event_id' or 'story_id' with an array of this sites entities (of 
	 * 	'events' or 'news_type'). 
	 * 	
	 * 	If the entity's id is in the Google Analytics array then the entity
	 * 	was viewed on some URL. I.e. it may have been viewed on 
	 * 	www.domain.edu/events/?event_id=12345&date=yyyy-mm-dd (your main
	 * 	events calendar) or it may have been viewed at 
	 *  www.domain.edu/yoursite/events/?event_id=12345&date=yyyy-mm-dd
	 * 	(this sites event page). We'll add all the metrics together
	 * 	and return them with the entity
	 */
	function get_query_url_array($entity_type, $ga_array)
	{
		$ret = array();
  		foreach ($entity_type as $e) {
	  		$id = $e->get_value('id');
	  		foreach ($ga_array->getRows() as $row) {
	  			if (strpos($row[0], $id)){
	  				if (!array_key_exists($id, $ret)){
	  					$ret[$id]['url'] = $row[0];
	  					$ret[$id]['pageviews'] = $row[1];
	  					$ret[$id]['entrances'] = $row[2];
	  					$ret[$id]['avgTimeOnPage'] = $row[3];
	  					$ret[$id]['uniquePageViews'] = $row[4];
	  					$ret[$id]['bounceRate'] = $row[5];
	  					$ret[$id]['exitRate'] = $row[6];
	  				} else {
	  					$ret[$id]['pageviews'] = $ret[$id]['pageviews'] + $row[1];
	  					$ret[$id]['entrances'] = $ret[$id]['entrances'] + $row[2];
	  					$ret[$id]['avgTimeOnPage'] = $ret[$id]['avgTimeOnPage'] + $row[3];
	  					$ret[$id]['uniquePageViews'] = $ret[$id]['uniquePageViews'] + $row[4];
	  					$ret[$id]['bounceRate'] = $ret[$id]['bounceRate'] + $row[5];
	  					$ret[$id]['exitRate'] = $ret[$id]['exitRate'] + $row[6];
	  				}
	  			$ret[$id]['column_headers'] = $ga_array->getColumnHeaders();
	  			$ret[$id]['entity'] = $e;
	  			}
	  		}
    	}
		return $ret;
	}

	/**
	 * We'll get our site URLs using the url_builder to save some time.
	 *
	 * We also will cache the urls for url_cache_lifespan so that only the first load has the overhead of URL building.
	 */
	function get_site_urls()
	{
		$roc = new ReasonObjectCache($this->admin_page->site_id . '_google_analytics_site_url_info', $this->url_cache_lifespan);
		$urls = $roc->fetch();
		if (!$urls)
		{
			$es = new entity_selector($this->admin_page->site_id);
			$es->limit_fields(array('url','url_fragment'));
			$es->add_type(id_of('minisite_page'));
			$es->add_left_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
			$es->add_relation('(entity.name != "") AND ((url.url = "") OR (url.url IS NULL))'); // only pages, not custom urls
			$this->pages = $es->run_one();
			$url_builder = new reasonPageURL();
			$url_builder->provide_page_entities($pages);
			foreach ($this->pages as $id => $page)
			{
				$url_builder->set_id($id);
				$url = $url_builder->get_relative_url();
				$urls[$id] = $url;
			}
			$roc->set($urls);
		}
		return $urls;
	}

	function get_site_type($entity_type)
	{
		$ret = array();
		foreach ($entity_type as $e) {
			$ret[$e->get_value('id')] = $e->get_value('name');
		}
		return $ret;
	}

	/**
	 * 	Return an array of this site's types are queryable through Google Analytics,
	 * 	if the type has content
	 */
	function get_queryable_site_types()
	{
		$ret = array();
		$ret[id_of('minisite_page')] = 'Page';
		if ($this->has_events) 
		{
			$e = current($this->has_events);
			$type_id = $e->get_value('type');
			$ret[$type_id] = $this->admin_page->get_name($type_id);
		}
		if ($this->has_events) 
		{
			$e = current($this->has_events);
			$type_id = $e->get_value('type');
			$ret[$type_id] = $this->admin_page->get_name($type_id);
		}
		if ($this->has_faq) 
		{
			$e = current($this->has_faq);
			$type_id = $e->get_value('type');
			$ret[$type_id] = $this->admin_page->get_name($type_id);
		}
		if ($this->has_news) 
		{
			$e = current($this->has_news);
			$type_id = $e->get_value('type');
			$ret[$type_id] = $this->admin_page->get_name($type_id);
		}
		if ($this->has_policies) 
		{
			$e = current($this->has_policies);
			$type_id = $e->get_value('type');
			$ret[$type_id] = $this->admin_page->get_name($type_id);
		}
		return $ret;
	}

	/**
	 * Lists the top pages (views) and show analytics
	 * 
	 * @return void
	 */
	function run()
	{
		if(!$this->ok_to_run())
		{
			echo '<p>'.$this->not_ok_to_run_message().'</p>';
			return;
		}
		$this->site_urls = $this->get_site_urls();
		asort($this->site_urls);
		
		$site_types = $this->get_queryable_site_types();
		$site_events = $this->get_site_type($this->has_events);
		$site_faq = $this->get_site_type($this->has_faq);
		$site_news = $this->get_site_type($this->has_news);
		$site_policies = $this->get_site_type($this->has_policies);

		$disco = new Disco();
		$disco->add_element('content_id', 'hidden', array('userland_changeable'=>true)); // placeholder for an entity's id
		$disco->add_element('content_type', 'radio_inline_no_sort', array('options'=>$site_types));
		$disco->add_element('url', 'chosen_select', array('options'=>$this->site_urls, 'display_name'=>'URL(s)'));

		$disco->add_element('propagate', 'checkboxfirst', array('display_name'=>'Include this page\'s sub-pages (children and grandchildren)'));
		if ($site_events)
		{
			$disco->add_element('events','chosen_select',array('options'=>$site_events,'display_name'=>'Events'));
		}
		if ($site_faq)
		{
			$disco->add_element('faq','chosen_select',array('options'=>$site_faq,'display_name'=>'FAQs'));
		}
		if ($site_news)
		{
			$disco->add_element('news','chosen_select',array('options'=>$site_news,'display_name'=>'News / Posts'));
		}
		if ($site_policies)
		{
			$disco->add_element('policies','chosen_select',array('options'=>$site_policies));
		}
		if (GA_SERVICE_PROVIDER_NAME != '')
		{
			$disco->add_element('location', 'radio_inline_no_sort', array('options' => array('anywhere'=>'Anywhere', 'off_campus'=>'Off-Campus', 'on_campus'=>'On-Campus'), 'default' => 'anywhere'));
		} else { 
			$disco->add_element('location', 'hidden');
			$disco->set_value('location', 'anywhere');
		}
		$disco->add_element('start_date', 'textdate');
		$disco->add_element('end_date', 'textdate');
		$disco->add_element('site_id', 'hidden');
		$disco->set_value('site_id', $this->site->id());
		$disco->add_element('cur_module', 'hidden');
		$disco->set_value('cur_module', 'Analytics');
		$disco->add_required('location');
		$disco->add_required('start_date');
		$disco->add_required('end_date');
		$disco->set_actions(array('Get Analytics'));
		$disco->show_error_jumps = false;
		$error_checks_callback = array($this,'error_check_disco');
		$on_every_time_callback = array($this,'on_every_time');
		$disco->add_callback($error_checks_callback, 'run_error_checks');
		$disco->add_callback($on_every_time_callback, 'pre_error_check_actions');
		$disco->set_value('start_date',date('Y-m-d', strtotime('-1 month -1 day')));
		$disco->set_value('end_date',date('Y-m-d', strtotime('-1 day')));

		echo '<div id="analytics-module" class="noscript">' . "\n";
		echo '<script>$("div").removeClass("noscript");</script>' ."\n";
		
	
		if($disco->get_value('url') == (NULL OR '')) { $disco->set_value('url',$this->site->get_value('base_url')); }
		$disco->run();
		
		// $disco->set_value('url',$this->site->get_value('base_url'));
		// echo '<h2> The Url Is: '.$this->site->get_value('base_url').'</h2>'

		// echo '<h2> The Url Is: '.$disco->get_value('url').'</h2>';

		// if ($disco->get_value('url') == (NULL OR '')) {	
		// 	$disco->set_error( 'url', 'select url', $element_must_exist = true); 
		// 	echo '<h2>'.var_dump($disco->has_errors()).'</h2>';
		// }		

		if (!$disco->has_errors())
		{
			// Query Google Analytics and display results if any
			if ($this->get_ga_daily_data($disco))
			if ($this->get_ga_sources_data($disco))
			{
				$totals = $this->daily_results->getTotalsForAllResults();
				if (($totals['ga:pageviews'] != '(none)') && !is_null($this->source_results->getRows()))
				{	
					$loc = $disco->get_value_for_display('location');
					$location_text = $loc == 'Anywhere' ? '' : $loc . ' ';
					echo '<div class="results-title"><h3>' . $location_text . 'Results for '. date("M d, Y", strtotime($disco->get_value('start_date'))) . ' - ' . date("M d, Y", strtotime($disco->get_value('end_date'))) . '</h3></div>'."\n";
					
					$prop = $disco->get_value('propagate');
					$display_url = '<a href="http://'.HTTP_HOST_NAME.$disco->get_value_for_display("url").'"target="_blank">'.$disco->get_value_for_display('url').'</a>';
					if ($disco->get_value('content_type') == id_of('minisite_page') && empty($this->admin_page->request['id']))
					{
						$page_path_text = $prop == true ? '<em>'.$display_url.'</em> and all sub-pages' : '<em>'.$display_url.'</em>';
					}
					else 
					{
						$page_path_text = '<em>'.$this->admin_page->get_name($disco->get_value('content_id')).'</em>';
					}

					// Display total results
					echo '<div class="results-title"><h4>Totals for ' . $page_path_text . '</h4></div>' . "\n";
					$this->about = new AnalyticsAbout();
					
					echo '<div id="total-results">' . "\n";
					echo '<div class="metric" title="'.$this->about->get_help('pageviews_help','text').'">Pageviews<br /><strong>'.number_format($totals['ga:pageviews']).'</strong></div>' . "\n";
					echo '<div id="pageviews_metric"></div>' . "\n";
					echo '<div class="metric" title="'.$this->about->get_help('unique_pageviews_help','text').'">Unique Pageviews<br /><strong>'.number_format($totals['ga:uniquePageViews']).'</strong></div>' . "\n";
					echo '<div id="unique_pageviews_metric"></div>' . "\n";
					echo '<div class="metric" title="'.$this->about->get_help('entrances_help','text').'">Entrances<br /><strong>'.number_format($totals['ga:entrances']).'</strong></div>' . "\n";
					echo '<div id="entrances_metric"></div>' . "\n";
					$pagetext = ($disco->get_value('url') == 'all_pages' ? 'pages' : 'page');
					echo '<div class="metric" title="'.$this->about->get_help('average_time_help','text').'">Avg time on ' . $pagetext . '<br /><strong>'. gmdate("i:s", round($totals['ga:avgTimeOnPage'])).'</strong></div>' . "\n";
					echo '<div id="average_time_metric"></div>' . "\n";
					echo '<div class="metric" title="'.$this->about->get_help('bounce_rate_help','text').'">Bounce rate<br /><strong>'.round($totals['ga:entranceBounceRate'], 2).'%</strong></div>' . "\n";
					echo '<div id="bounce_rate_metric"></div>' . "\n";
					echo '<div class="metric" title="'.$this->about->get_help('exit_rate_help','text').'">Exit rate<br /><strong>'.round($totals['ga:exitRate'], 2).'%</strong></div>' . "\n";
					echo '<div id="exit_rate_metric"></div>' . "\n";
					echo '<div style="clear: left;"></div>' . "\n";
					echo '</div>' . "\n";  //total-results

					//Display daily results plot
					echo '<div class="results-title"><h4>Daily Results for '.$page_path_text.'</h4></div>' . "\n";
					
					echo '<div id="daily-results-div" class="jsdisabled">' ."\n";
					echo $this->draw_ga_table($this->daily_results, 'daily_results');
					echo '</div>' . "\n";//daily-results

					echo '<div class="jsenabled">' . "\n";
					if ($this->daily_results->getTotalResults() == 1) 
					{
						echo $this->draw_ga_table($this->daily_results, 'daily_results');
					}
					else
					{

						echo '<div id="placeholder" style="width:785px;height:300px;margin:20px"></div>' . "\n";

						echo '<p>Click and drag on the overview plot below to change zoom level.<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/zoom.png" alt="Click-drag to Zoom" /></p>' . "\n";

						echo '<div id="overview" style="margin-left:125px;margin-top:20px;width:600px;height:50px"></div>' . "\n";
					}
					echo '</div>' . "\n"; //jsenabled

					//Display top 20 source results
					$top = ($this->source_results->getTotalResults() > 20 ? '20' : $this->source_results->getTotalResults());
					if (count($this->source_results->getRows()) == 1)
					{
						echo '<div class="results-title"><h4>Top <a  title="'.$this->about->get_help('source_help','text').'">Source</a> for '.$page_path_text.'</h4></div>' . "\n";
					}
					else
					{
						echo '<div class="results-title"><h4>Top ' . $top . ' <a  title="'.$this->about->get_help('source_help','text').'">Sources</a> for '.$page_path_text.'</h4></div>' . "\n";
					}
					echo '<div id="source_metric"></div>' . "\n";
					echo '<div id="source-jsdisabled-div" class="jsdisabled">' ."\n";
					echo $this->draw_ga_table($this->source_results, 'source_results');
					echo '</div>' . "\n";//source-results

					echo '<div id="source-jsenabled-div" class="jsenabled">' . "\n";
					if (count($this->source_results->getRows()) == 1)
					{
						echo $this->draw_ga_table($this->source_results, 'source_results');
					}
					else
					{
						echo '<div id="source-pie-chart" style="width:785px;height:375px;margin:20px"></div>' . "\n";
						echo '<div id="source-pie-hover"></div>';
						echo '</div>' . "\n"; //jsenabled
					}	

					$this->draw_line_chart($this->daily_results);
					$this->draw_pie_chart($this->source_results);

					if (empty($this->admin_page->request['type_id']))
					{
						/**
						 * 	@todo include pages table
						 */

						// $pages = $this->get_query_url_array($this->site_urls, $this->page_results);
						echo '<div class="results-title"><h4>Pages</h4></div>'."\n";
						echo '<div id="page-results">'."\n";
						echo $this->draw_ga_table($this->page_results, 'page-results-table');
						echo '<div id="source-jsenabled-div" class="jsenabled">' . "\n";
						echo $this->draw_pager_div('page-results');
						echo '</div>'."\n";	
						echo '</div>'."\n"; // page-results

						if ($this->has_events)
						{
							$events = $this->get_query_url_array($this->has_events, $this->event_results);
							echo '<div class="results-title"><h4>Events</h4></div>'."\n";
							echo '<div id="event-results">'."\n";
							if ($events)
							{
								echo $this->draw_query_url_table($events, 'event-results-table');
								echo '<div id="source-jsenabled-div" class="jsenabled">' . "\n";
								echo $this->draw_pager_div('event-results');
								echo '</div>'."\n";
							} else {
								echo '<h5>There are no event results for this timeframe.</h5>' . "\n";
							}
							echo '</div>'."\n"; // event-results
						}

						if ($this->has_faq)
						{
							$faq = $this->get_query_url_array($this->has_faq, $this->faq_results);
							echo '<div class="results-title"><h4>FAQ</h4></div>'."\n";
							echo '<div id="faq-results">'."\n";
							if ($faq)
							{
								echo $this->draw_query_url_table($faq, 'faq-results-table');
								echo '<div id="source-jsenabled-div" class="jsenabled">' . "\n";
								echo $this->draw_pager_div('faq-results');
								echo '</div>'."\n";
							} else {
								echo '<h5>There are no faq results for this timeframe.</h5>' . "\n";
							}
							echo '</div>'."\n"; // faq-results
						}

						if ($this->has_news)
						{
							$posts = $this->get_query_url_array($this->has_news, $this->news_results);
							echo '<div class="results-title"><h4>News / Posts</h4></div>'."\n";
							echo '<div id="news-results">'."\n";
							if ($posts)
							{
								echo $this->draw_query_url_table($posts, 'news-results-table');
								echo '<div id="source-jsenabled-div" class="jsenabled">' . "\n";
								echo $this->draw_pager_div('news-results');
								echo '</div>'."\n";
							} else {
								echo '<h5>There are no news/post results for this timeframe.</h5>' . "\n";
							} 
							echo '</div>'."\n"; // news-results
						}

						if ($this->has_policies)
						{
							$policies = $this->get_query_url_array($this->has_policies, $this->policy_results);
							echo '<div class="results-title"><h4>Policies</h4></div>'."\n";
							echo '<div id="policy-results">'."\n";
							if ($policies)
							{
								echo $this->draw_query_url_table($policies, 'policy-results-table');
								echo '<div id="source-jsenabled-div" class="jsenabled">' . "\n";
								echo $this->draw_pager_div('policy-results');
								echo '</div>'."\n";
							} else {
								echo '<h5>There are no policy results for this timeframe.</h5>' . "\n";
							}
							echo '</div>'."\n"; // policy-results
						}
					}
				}
				else
				{
					echo '<div class="analytics-error">There are no results for this time frame.</div>';
				}
			}
		}

		$this->create_footer();

	}


	function create_footer() {
		echo '<div class="analytics-footer">' . "\n";
		echo '<p>Please contact '.REASON_CONTACT_INFO_FOR_ANALYTICS.' for more detailed analytics.</p>';
		
		$aboutLink = $this->admin_page->make_link( array( 'site_id'=>$this->site->id(),'cur_module'=>'AnalyticsAbout' ) );
		echo '<p><a href="' . $aboutLink . '">About Analytics <img src="'.REASON_HTTP_BASE_PATH.'silk_icons/information.png" alt="" /></a></p>';

		echo '</div>' . "\n"; //analytics-footer
		echo '</div>' . "\n"; //analytics-module
	}

	function on_every_time(&$disco)
	{
		//called from the 'main' admin_page button
		if (empty($this->admin_page->request['type_id']) && empty($this->admin_page->request['id']))
		{
			$this->get_ga_data($disco->get_value('location'), $disco->get_value('start_date'), $disco->get_value('end_date'));
			if (!array_key_exists('submitted', $this->admin_page->request))
			{
				$disco->set_value('content_type', id_of('minisite_page'));
	            $disco->set_value('content_id', key($this->site_urls));
	            $disco->set_value('url', key($this->site_urls));
	            $disco->set_value('propagate', true);
            }
            else
            {
            	if ($this->admin_page->request['content_type'] == id_of('event_type'))
            	{
            		$disco->set_value('content_id', $this->admin_page->request['events']);
            	}
            	if ($this->admin_page->request['content_type'] == id_of('faq_type'))
            	{
            		$disco->set_value('content_id', $this->admin_page->request['faq']);
            	}
            	if ($this->admin_page->request['content_type'] == id_of('news'))
            	{
            		$disco->set_value('content_id', $this->admin_page->request['news']);
            	}
            	if ($this->admin_page->request['content_type'] == id_of('policy_type'))
            	{
            		$disco->set_value('content_id', $this->admin_page->request['policies']);
            	}

            }
		}
		// called from an entity admin page or form submission
		else 
		{
				$disco->change_element_type('content_type', 'hidden', array('userland_changeable'=>true));
	            $disco->set_value('content_type', $this->admin_page->request['type_id']);
	            $disco->set_value('content_id', $this->admin_page->request['id']);
				$disco->remove_element('url');
				$disco->remove_element('propagate');
				$disco->remove_element('events');
				$disco->remove_element('faq');
				$disco->remove_element('news');
				$disco->remove_element('policies');
		}
		if ($disco->get_element('url') && $disco->get_element('propagate'))
		{
			$disco->add_element_group('inline', 'url_group', array('url', 'propagate'), array('use_element_labels'=>false, 'display_name'=>'URL(s)'));
            $disco->move_element('url_group', 'after', 'content_type');
		}
	}

	function error_check_disco(&$disco)
	{
		if ($disco->get_value('start_date') > $disco->get_value('end_date')){
			$disco->set_error('start_date', 'Please choose a start date that is before the end date.');
		}
	}

	function build_filter($disco)
	{
		$type_id = $disco->get_value('content_type');
		$id = $disco->get_value('content_id');
		$location = $disco->get_value('location');
		$propagate = $disco->get_value('propagate');
		$provider_name = strtolower(addslashes(GA_SERVICE_PROVIDER_NAME));
		// set networkLocation filter
		if ($location == 'off_campus'){ 
			$filter['location'] = 'ga:networkLocation!~' . $provider_name;
		}
		elseif ($location == 'on_campus'){
			$filter['location'] = 'ga:networkLocation=~' . $provider_name;   
		} else {
			$filter['location'] = '';
		}
		/**
		 * 	set pagePath filter
		 * 	if type is a page, set pagePath with its URL
		 * 	all other types set pagePath with id (we don't 
		 * 	care about propagating)
		 */
		if ($type_id == id_of('minisite_page'))
		{
			if (isset($this->admin_page->request['url']))
			{
				$url = $this->site_urls[$this->admin_page->request['url']];
			}
			else
			{
				$url = $this->site_urls[$id];
			}
			if ($url == (NULL OR '')) { 
				$url = $this->site->get_value('base_url');  
				$disco->set_value('url', array_search($url, $this->site_urls));
			}
			$default = isset($this->default_page) ? $this->default_page : '';
			if ($propagate && $id)
			{
				$filter['path'] = 'ga:pagePath=~^' . GA_HOST_NAME . $url;
			} else { 
				$filter['path'] = 'ga:pagePath==' . GA_HOST_NAME . $url . $default;
			}
		}  
		elseif ($id === false)
		{
			$filter['path'] = 'ga:pagePath=~'.$type_id;
		} 
		else 
		{
			$filter['path'] = 'ga:pagePath=~'.$id;
		}

		return implode(';',array_filter($filter));
	}

	function get_ga_daily_data($disco)
	{
		$filter = $this->build_filter($disco);
		$profile_id = 'ga:' . GOOGLE_ANALYTICS_PROFILE_ID;
		try {
			$this->daily_results = 
				$this->service->data_ga->get(
					$profile_id, 
					$disco->get_value('start_date'), 
					$disco->get_value('end_date'), 
					'ga:pageviews,ga:entrances,ga:avgTimeOnPage,ga:uniquePageViews,ga:entranceBounceRate,ga:exitRate', 
					array(
						'filters' => $filter,
						'dimensions' => 'ga:date')
				);
		} 
		catch(Exception $de) {
			echo htmlspecialchars($de->getMessage());
		}
		if (isset($de)){
			if (is_developer())
			{
				echo '<div class="analytics-error">Daily Results Error: ' . htmlspecialchars($de) . '</div>';
			}
			else
			{
				echo '<div class="analytics-error">Oops. There was an error getting the analytics. Please contact ' . REASON_CONTACT_INFO_FOR_ANALYTICS . ' for assistance.</div>';
			}
			return false;
		}
		return true;	
	}

	function get_ga_sources_data(&$disco)
	{
		// get cumulative source ga data
		$filter = $this->build_filter($disco);
		$profile_id = 'ga:' . GOOGLE_ANALYTICS_PROFILE_ID;
		try {
			$this->source_results = 
				$this->service->data_ga->get(
					$profile_id, 
					$disco->get_value('start_date'), 
					$disco->get_value('end_date'),
					'ga:pageviews', 
					array(
						'filters'=>$filter,
						'dimensions' =>'ga:source',
						'sort'=>'-ga:pageviews',
						'max-results'=>20)
				);
		}
		catch (Exception $se) {
			$se->getMessage();
		}
		if (isset($se)){
			if (is_developer())
			{
				echo '<div class="analytics-error">Source Results Error: ' . htmlspecialchars($se) . '</div>';
			}
			else
			{
				echo '<div class="analytics-error">Oops. There was an error getting the analytics. Please contact ' . REASON_CONTACT_INFO_FOR_ANALYTICS . ' for assistance.</div>';
			}
			return false;
		}
		return true;
	}


	function get_ga_data($location, $start, $end)
	{   
		$provider_name = strtolower(addslashes(GA_SERVICE_PROVIDER_NAME));
        $profile_id = 'ga:' . GOOGLE_ANALYTICS_PROFILE_ID;
		if ($location == 'off_campus'){ 
			$filter['location'] = 'ga:networkLocation!~' . $provider_name . ';';
		}
		elseif ($location == 'on_campus'){
			$filter['location'] = 'ga:networkLocation=~' . $provider_name . ';';   
		} else {
			$filter['location'] = '';
		}

		//get page analytics data
		try {
			$this->page_results = 
				$this->service->data_ga->get(
					$profile_id, 
					$start, 
					$end, 
					'ga:pageviews,ga:uniquePageViews,ga:entrances,ga:avgTimeOnPage,ga:entranceBounceRate,ga:exitRate', 
					array(
						'filters'=>$filter['location'].'ga:pagePath=~^'.GA_HOST_NAME.$this->site->get_value('base_url'),
						'dimensions' =>'ga:pagePath',
						'sort'=>'-ga:pageviews',
						'max-results'=>500)
				);
		}
		catch (Exception $ee) {
			$ee->getMessage();
		}

		// if site has events, get events google analytics data
		if ($this->has_events)
		{
			try {
				$this->event_results = 
					$this->service->data_ga->get(
						$profile_id, 
						$start, 
						$end, 
						'ga:pageviews,ga:entrances,ga:avgTimeOnPage,ga:uniquePageViews,ga:entranceBounceRate,ga:exitRate', 
						array(
							'filters'=>$filter['location'].'ga:pagePath=~event_id',
							'dimensions' =>'ga:pagePath',
							'sort'=>'-ga:pageviews')
					);
			}
			catch (Exception $ee) {
				$ee->getMessage();
			}
		}
		// if site has faq, get faq google analytics data
		if ($this->has_faq)
		{
			try {
				$this->faq_results = 
					$this->service->data_ga->get(
						$profile_id, 
						$start, 
						$end, 
						'ga:pageviews,ga:entrances,ga:avgTimeOnPage,ga:uniquePageViews,ga:entranceBounceRate,ga:exitRate', 
						array(
							'filters'=>$filter['location'].'ga:pagePath=~faq_id',
							'dimensions' =>'ga:pagePath',
							'sort'=>'-ga:pageviews')
					);
			}
			catch (Exception $fe) {
				$fe->getMessage();
			}
		}
		// if site has news/posts, get news/posts google analytics data
		if ($this->has_news)
		{
			try {
					$this->news_results = 
						$this->service->data_ga->get(
							$profile_id, 
							$start, 
							$end, 
							'ga:pageviews,ga:entrances,ga:avgTimeOnPage,ga:uniquePageViews,ga:entranceBounceRate,ga:exitRate', 
							array(
								'filters'=>$filter['location'].'ga:pagePath=~story_id',
								'dimensions' =>'ga:pagePath',
								'sort'=>'-ga:pageviews')
						);
				}
				catch (Exception $ne) {
					$ne->getMessage();
				}
		}
		// if site has policies, get policy google analytics data
		if ($this->has_policies)
		{
			try {
					$this->policy_results = 
						$this->service->data_ga->get(
							$profile_id, 
							$start, 
							$end, 
							'ga:pageviews,ga:entrances,ga:avgTimeOnPage,ga:uniquePageViews,ga:entranceBounceRate,ga:exitRate', 
							array(
								'filters'=>$filter['location'].'ga:pagePath=~policy_id',
								'dimensions' =>'ga:pagePath',
								'sort'=>'-ga:pageviews')
						);
				}
				catch (Exception $pe) {
					$pe->getMessage();
				}
		}

		if (isset($fe) || isset($ee) || isset($ne) || isset($pe))
		{
			if (is_developer())
			{
				if (isset($fe))
				{
					echo '<div class="analytics-error"><p>FAQ Results Error: ' . htmlspecialchars($fe) . '</p></div>';
				}
				if (isset($ee))
				{
					echo '<div class="analytics-error"><p>Event Results Error: ' . htmlspecialchars($ee) . '</p></div>';	
				}
				if (isset($ne))
				{
					echo '<div class="analytics-error"><p>News Results Error: ' . htmlspecialchars($ne) . '</p></div>';	
				}
				if (isset($pe))
				{
					echo '<div class="analytics-error"><p>Policy Results Error: ' . htmlspecialchars($pe) . '</p></div>';	
				}
			}
			else
			{
				echo '<div class="analytics-error">Oops. There was an error getting the analytics. Please contact ' . REASON_CONTACT_INFO_FOR_ANALYTICS . ' for assistance.</div>';
			}
			return false;
		}
		return true;
	}

	/**
	 *	Draw a table from Google Analytics data
	 */
	function draw_ga_table($data, $table_id)
	{
		$table = '';
		if (count($data->getRows()) > 0) {
			$table .= '<table class="tablesorter" id='.$table_id.'>' . "\n";

			// Print headers.
			$table .= '<thead>'."\n";
			$table .= '<tr>' . "\n";

			foreach ($data->getColumnHeaders() as $header) {
				//remove ga: from the headers
				$head = substr($header->name, 3);
				//capitalize first letter and addspaces
				$head = preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $head);
				$head = ucfirst($head);
				if ($head == 'Date')
					$table .= '<th>' . $head . '<br>(yyyymmdd)</th>' . "\n";
				elseif ($head == 'Page Path')
					$table .= '<th data-placeholder="Search Page Path">'.str_replace(' ', '&nbsp;', 'Page Path').'</th>' . "\n";
				elseif ($head == 'Pageviews')
					$table .= '<th title="'.$this->about->get_help('pageviews_help','text').'">Pageviews</th>' . "\n";
				elseif ($head == 'Unique Page Views')
					$table .= '<th title="'.$this->about->get_help('unique_pageviews_help','text').'">'.str_replace(' ', '&nbsp;', 'Unique Page Views').'</th>' . "\n";
				elseif ($head == 'Entrances')
					$table .= '<th title="'.$this->about->get_help('entrances_help','text').'">Entrances</th>' . "\n";
				elseif ($head == 'Avg Time On Page')
					$table .= '<th title="'.$this->about->get_help('average_time_help','text').'">'.str_replace(' ', '&nbsp;', 'Avg Time On Page').'</th>' . "\n";
				elseif ($head == 'Entrance Bounce Rate')
					$table .= '<th data-placeholder="Select a filter" title="'.$this->about->get_help('bounce_rate_help','text').'">'.str_replace(' ', '&nbsp;', 'Entrance Bounce Rate').'</th>' . "\n";
				elseif ($head == 'Exit Rate')
					$table .= '<th data-placeholder="Select a filter" title="'.$this->about->get_help('exit_rate_help','text').'">'.str_replace(' ', '&nbsp;', 'Exit Rate').'</th>' . "\n";
				else
					$table .= '<th>' . $head . '</th>' . "\n"; 

			}
			$table .= '</tr>' . "\n";
			$table .= '</thead>'."\n";
			$table .= '<tbody>' . "\n";
			// Print table rows.
			foreach ($data->getRows() as $row) 
			{
			  $table .= '<tr>' . "\n";
			  	$i = 0;
				foreach ($row as $cell) 
				{
					//round off decimals
					if (is_numeric( $cell ) && floor( $cell ) != $cell)    
						$cell = round($cell, 2);
					if ($i == 0)
					{
						// $urls = $this->get_site_urls();
						// pray($urls);
						$path = str_replace(GA_HOST_NAME, '', $cell);
						if (strpos($path, '?'))
						{
							$table .= '<td>'.$path.'</td>'."\n";
						}
						else
						{
							$id = array_search($path, $this->get_site_urls());
							$table .= '<td><a href="'.$this->admin_page->make_link(array('type_id'=>id_of('minisite_page'), 'id'=>$id)).'">'.$path.'</a></td>'."\n";
						}
					}
					elseif ($i == 4)
						$table .= '<td>' . gmdate("i:s", $cell) . '</td>' . "\n";
					elseif ($i >= 5)
						$table .= '<td>' . htmlspecialchars($cell, ENT_NOQUOTES) . '%</td>' . "\n";
					else
						$table .= '<td>' . htmlspecialchars($cell, ENT_NOQUOTES) . '</td>' . "\n";
					$i++;
				}
			  $table .= '</tr>' . "\n";
			}
			$table .= '</tbody>' . "\n";
			$table .= '</table>' . "\n";

		} else
		{
			$table .= '<h4>There are no daily results for this query. Please contact ' . REASON_CONTACT_INFO_FOR_ANALYTICS . ' for assistance.</h4>' . "\n";
		}
		return $table;
	}

	function draw_query_url_table($data, $table_id)
	{
		$table = '';
		$table .= '<table class="tablesorter" id="'.$table_id.'">' . "\n";

		// Print headers.
		$table .= '<thead>'."\n";
		$table .= '<tr>' . "\n";
		$table .= '<th data-placeholder="Search Title">Title</th>' . "\n";
		$table .= '<th title="'.$this->about->get_help('pageviews_help','text').'">Pageviews</th>' . "\n";
		$table .= '<th title="'.$this->about->get_help('unique_pageviews_help','text').'">'.str_replace(' ', '&nbsp;', 'Unique Page Views').'</th>' . "\n";
		$table .= '<th title="'.$this->about->get_help('entrances_help','text').'">Entrances</th>' . "\n";
		$table .= '<th title="'.$this->about->get_help('average_time_help','text').'">'.str_replace(' ', '&nbsp;', 'Avg Time On Page').'</th>' . "\n";
		$table .= '<th data-placeholder="Select a filter" title="'.$this->about->get_help('bounce_rate_help','text').'">'.str_replace(' ', '&nbsp;', 'Entrance Bounce Rate').'</th>' . "\n";
		$table .= '<th data-placeholder="Select a filter" title="'.$this->about->get_help('exit_rate_help','text').'">'.str_replace(' ', '&nbsp;', 'Exit Rate').'</th>' . "\n";
		$table .= '</tr>' . "\n";
		$table .= '</thead>'."\n";
		$table .= '<tbody>' . "\n";
		// Print table rows.
		foreach ($data as $row) {
		  $table .= '<tr>' . "\n";
		  if ($table_id != 'page-results')
		  {
		  	$preview_link = '<a href="'.$this->admin_page->make_link(array('id'=>$row['entity']->get_value('id'),'type_id'=>$row['entity']->get_value('type'),'cur_module'=>'Analytics')).'">'.$row['entity']->get_value('name').'</a>';
		  }
		  else 
		  {
		  	$preview_link = '<a href="'.$this->admin_page->make_link(array('id'=>$row['entity']->get_value('id'),'type_id'=>$row['entity']->get_value('type'),'cur_module'=>'Analytics')).'">'.$row['entity']->get_value('name').'</a>';
		  }
		  		$table .= '<td>' .$preview_link .'</td>' . "\n";
				$table .= '<td>' . $row['pageviews'] . '</td>' . "\n";
				$table .= '<td>' . $row['uniquePageViews'] . '</td>' . "\n";
				$table .= '<td>' . $row['entrances'] . '</td>' . "\n";
				$table .= '<td>' . gmdate("i:s", round($row['avgTimeOnPage'])) . '</td>' . "\n";
				$table .= '<td>' . round($row['bounceRate'], 2) . '%</td>' . "\n";
				$table .= '<td>' . round($row['exitRate'], 2) . '%</td>' . "\n";
			// }
		  $table .= '</tr>' . "\n";
		}
		$table .= '</tbody>' . "\n";
		$table .= '</table>' . "\n";
		return $table;
	}

	function draw_pager_div($div_name)
	{
		$div = '';
		$div .= '<div id="'.$div_name.'-pager" class="pager">'."\n";
		$div .= '<form>'."\n";
		$div .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/first.png" alt="First" class="first"></img>'."\n";
		$div .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/prev.png" alt="Previous" class="prev"></img>'."\n";
		$div .= '<input type="text" class="pagedisplay"></input>'."\n";
		$div .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/next.png" alt="Next" class="next"></img>'."\n";
		$div .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/last.png" alt="Last" class="last"></img>'."\n";
		$div .= '<select class="pagesize">'."\n";
		$div .= '<option selected="selected" value="10">10</option>'."\n";
		$div .= '<option value="20">20</option>'."\n";
		$div .= '<option value="30">30</option>'."\n";
		$div .= '<option value="40">40</option>'."\n";
		$div .= '</select>'."\n";
		$div .= '</form>'."\n";
		$div .= '</div>'."\n";
		return $div;
	}

	/**
	 * 	Draw the flot pie chart
	 */
	function draw_pie_chart($data){
		$rows = $data->getRows();
		if ($rows){
			$arr = array();
			foreach ($rows as $row) {
				$arr[] = '{label: "' . $row[0] . '", data: ' . $row[1] . '}';
			}
			$data_output = '[' . implode(', ', $arr) . ']';
			
			/**
			 * @todo move javascipts to their own files
			 */                

			echo '<script type="text/javascript">
			$(function () {
				$.plot($("#source-pie-chart"), ' . $data_output . ',
				{
						series: {
							pie: { 
								show: true
							}
						},
						grid: {
							hoverable: true,
						}
				});
				$("#source-pie-chart").bind("plothover", pieHover);
			});

			function pieHover(event, pos, obj) 
			{
				if (!obj)
							return;
				percent = parseFloat(obj.series.percent).toFixed(2);
				$("#source-pie-hover").html(\'<span style="font-weight: bold; color: \'+obj.series.color+\'">\'+obj.series.label+\' (\'+obj.series.data[0][1]+\' pageviews, \'+percent+\'%)</span>\');
			}
			</script>' . "\n";

		} else {
			return '<h4>There are no source results for this query. Please contact ' . REASON_CONTACT_INFO_FOR_ANALYTICS . ' for assistance.</h4>' . "\n";
		}
	}

	function draw_line_chart($data)
	{
		$rows = $data->getRows();
		foreach ($rows as $row) {
			$unixTime = '';
			$year = substr($row[0], 0, -4);
			$month = substr($row[0], 4, -2);
			$day = substr($row[0], -2);
			$unix_time = mktime(0,0,0,$month,$day,$year) . '000';
			$pageviews[] = $unix_time . ', ' . $row[1];
			$unique_pageviews[] = $unix_time . ', ' . $row[4];
			$entrances[] = $unix_time . ', ' . $row[2];
		}
		$pageviews_output = '[[';
		$pageviews_output .= implode('], [', $pageviews);
		$pageviews_output .= ']]' . "\n";
		$unique_output = '[[';
		$unique_output .= implode('], [', $unique_pageviews);
		$unique_output .= ']]' . "\n";
		$entrances_output = '[[';
		$entrances_output .= implode('], [', $entrances);
		$entrances_output .= ']]' . "\n";

		echo '<script id="source">
		$(function () {
			var d = ' . $pageviews_output . ';' .
			'var e = ' . $unique_output . ';' .
		   	'var f = ' . $entrances_output . ';' .


			'// first correct the timestamps - they are recorded as the daily
			// midnights in UTC+0100, but Flot always displays dates in UTC
			// so we have to add one hour to hit the midnights in the plot
			/**
			 * is this needed? -sls
			 * commenting out for now -- was causing the plot to be bumped over
			
			for (var i = 0; i < d.length; ++i){
			  d[i][0] += 60 * 60 * 1000;
			  e[i][0] += 60 * 60 * 1000;
			}
			*/

			// helper for returning the weekends in a period
			function weekendAreas(axes) {
				var markings = [];
				var d = new Date(axes.xaxis.min);
				// go to the first Saturday
				d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
				d.setUTCSeconds(0);
				d.setUTCMinutes(0);
				d.setUTCHours(0);
				var i = d.getTime();
				do {
					// when we don\'t set yaxis, the rectangle automatically
					// extends to infinity upwards and downwards
					markings.push({ xaxis: { from: i, to: i + 2 * 24 * 60 * 60 * 1000 } });
					i += 7 * 24 * 60 * 60 * 1000;
				} while (i < axes.xaxis.max);

				return markings;
			}

			var options = {
				xaxis: { mode: "time", minTickSize:[1,"day"]},
				selection: { mode: "x" },
				grid: { markings: weekendAreas, hoverable: true },             
			};

			var plot = $.plot($("#placeholder"), [{label:"Pageviews", data:d},{label:"Unique Pageviews", data:e},{label:"Entrances", data:f}], options);

			var overview = $.plot($("#overview"), [d,e,f], {
				series: {
					lines: { show: true, lineWidth: 1 , fill: true},
					shadowSize: 0
				},
				xaxis: { mode: "time" },
				yaxis: { ticks: [], min: 0, autoscaleMargin: 0.1 },
				selection: { mode: "x" }
			});

			// now connect the two

			$("#placeholder").bind("plotselected", function (event, ranges) {
				// do the zooming
				plot = $.plot($("#placeholder"), [
					{label:"Pageviews", data:d},
					{label:"Unique Pageviews", data:e},
					{label:"Entrances", data:f}
					], $.extend(true, {}, options, {
							  xaxis: { min: ranges.xaxis.from, max: ranges.xaxis.to },
						  }));

				// don\'t fire event on the overview to prevent eternal loop
				overview.setSelection(ranges, true);
			});

			$("#overview").bind("plotselected", function (event, ranges) {
				plot.setSelection(ranges);
			});    
		});


			function showTooltip(x, y, contents) {
					$(\'<div id="tooltip">\' + contents + \'</div>\').css( {
						position: \'absolute\',
						display: \'none\',
						top: y + 5,
						left: x + 5,
						border: \'1px solid #fdd\',
						padding: \'2px\',
						\'background-color\': \'#fee\',
						opacity: 0.80
					}).appendTo("body").fadeIn(200);
				}

				var previousPoint = null;
				$("#placeholder").bind("plothover", function (event, pos, item) {
					$("#x").text(pos.x.toFixed(2));
					$("#y").text(pos.y.toFixed(2));

						if (item) {
							if (previousPoint != item.datapoint) {
								previousPoint = item.datapoint;

								$("#tooltip").remove();
								var x = item.datapoint[0],
									y = item.datapoint[1];
							   var xdate = new Date(x);
						var xday = xdate.getDate() + 1;
				
			var xmonth=new Array();
						xmonth[0]="Jan";
						xmonth[1]="Feb";
						xmonth[2]="Mar";
						xmonth[3]="Apr";
						xmonth[4]="May";
						xmonth[5]="Jun";
						xmonth[6]="Jul";
						xmonth[7]="Aug";
						xmonth[8]="Sep";
						xmonth[9]="Oct";
						xmonth[10]="Nov";
						xmonth[11]="Dec";
						var month = xmonth[xdate.getMonth()];
						var date = month + \', \' + xday;

			showTooltip(item.pageX, item.pageY,
											y + \' \' + item.series.label + " on " + date);
							}
						}
						else {
							$("#tooltip").remove();
							previousPoint = null;
						}
				});

				$("#placeholder").bind("plotclick", function (event, pos, item) {
					if (item) {
						$("#clickdata").text("You clicked point " + item.dataIndex + " in " + item.series.label + ".");
						plot.highlight(item.series, item.datapoint);
					}
				});
		</script>';  
	}
}

class AnalyticsAboutModule extends DefaultModule
{
	var $site; 
	
	function AnalyticsAboutModule( &$page )
	{
		$this->admin_page =& $page;
	}
	
	/**
	 * Standard Module init function
	 *
	 * Sets up page variables and runs the entity selctor that grabs the site's page url_fragments
	 * 
	 * @return void
	 */
	function init()
	{
		parent::init();
		$this->site = new entity( $this->admin_page->site_id );
		$this->admin_page->title = 'About Analytics';
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/google_api/analytics/analytics.css');
	}
	
	/**
	 * Help content for Analytics About
	 * 
	 * @return void
	 */
	function run() // {{{
	{
		$this->about = new AnalyticsAbout();
		echo '<div id="analytics-about">'."\n";
		echo '<div id="average_time_help">'."\n";
		echo '<h3>'.$this->about->get_help('average_time_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('average_time_help','text').'</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="bounce_rate_help">'."\n";
		echo '<h3>'.$this->about->get_help('bounce_rate_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('bounce_rate_help','text').'</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="entrances_help">'."\n";
		echo '<h3>'.$this->about->get_help('entrances_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('entrances_help','text').'</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="exit_rate_help">'."\n";
		echo '<h3>'.$this->about->get_help('exit_rate_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('exit_rate_help','text').'</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="pageviews_help">'."\n";
		echo '<h3>'.$this->about->get_help('pageviews_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('pageviews_help','text').'</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="unique_pageviews_help">'."\n";
		echo '<h3>'.$this->about->get_help('unique_pageviews_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('unique_pageviews_help','text').'</p>'."\n";
		echo '</div>'."\n";
		
		echo '<hr>';
		echo '<div id="sessions_help">'."\n";
		echo '<h3>'.$this->about->get_help('sessions_help','heading').'</h3>'."\n";
		echo '<p>'.$this->about->get_help('sessions_help','text').'</p>'."\n";
		echo '</div>'."\n";

		echo '<hr>';
		echo '<div id="source_help">'."\n";
		echo '<h3>Source</h3>'."\n";
		echo '<p>The referral source of visits to the selected page or set of pages. "(direct)" denotes a source without a referral. For example, the user typed the URL in the address bar, clicked a bookmark, clicked a link from a PDF, clicked a link in a email newsletter that wasn\'t tracked, etc. The source helps determine how visitors are finding the ' . SHORT_ORGANIZATION_NAME . ' website and subsequently your pages.</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '</div>'."\n";

		echo '<div class="analytics-footer">' . "\n";            
		echo '<p>Please contact '.REASON_CONTACT_INFO_FOR_ANALYTICS.' for more detailed analytics.';
		echo '&nbsp;&nbsp;&nbsp;'
			. '<a href="' 
			. $this->admin_page->make_link( array(
					'site_id'=>$this->site->id(),
					'cur_module'=>'Analytics',) )
			. '">Back to Analytics <img src="'.REASON_HTTP_BASE_PATH.'silk_icons/chart_curve.png" alt="" /></a></p>'."\n";
		echo '</div>' . "\n"; //analytics-footer
	}
}

class AnalyticsAbout {
	var $help_array;

	function get_help($help, $part)
	{
		return $this->help_array[$help][$part];
	}

	function AnalyticsAbout()
	{
		$this->help_array['average_time_help'] = array(
			'heading'=>'Average Time on Page', 
			'text' => 'The average amount of time visitors spent viewing the selected page or set of pages. Generally, a higher average time on page is better, because it indicates that the visitors are engaging with your content.'
			);
		$this->help_array['bounce_rate_help'] = array(
			'heading'=>'Bounce Rate', 
			'text' => 'For all sessions that start with the page, bounce rate is the percentage of pageviews that were the only one in the session. Generally, the lower the bounce rate, the better, because it means visitors are staying on the ' . SHORT_ORGANIZATION_NAME . ' website'
			);
		$this->help_array['entrances_help'] = array(
			'heading'=>'Entrances', 
			'text' => 'The number of visits to the ' . SHORT_ORGANIZATION_NAME . ' website that started on the selected page or set of pages. A higher number means that this page is good at attracting visitors to the ' . SHORT_ORGANIZATION_NAME . ' website.'
			);
		$this->help_array['exit_rate_help'] = array(
			'heading' => '% Exit',
			'text' => 'The percentage of total page views that were the last in the visitor\'s session. The exit rate is important for finding pages in a visitor flow where visitors are dropping off before completing a desired action.'
			);
		$this->help_array['pageviews_help'] = array(
			'heading' => 'Pageviews',
			'text' => 'The total number of times visitors viewed the selected page or set of pages. The higher the better!'
			);
		$this->help_array['unique_pageviews_help'] = array(
			'heading' => 'Unique Pageviews',
			'text' => 'The number of user sessions during which the selected page or set of pages was viewed one or more times. I.e. each page is counted only once in a given visitor session.'
			);
		$this->help_array['sessions_help'] = array(
			'heading' => 'Session',
			'text' => 'The activity of one user (IP address) on a given website within a given time frame.'
			);
		$this->help_array['source_help'] = array(
			'heading' => 'Source',
			'text' => 'The referral source of visits to the selected page or set of pages. "(direct)" denotes a source without a referral. For example, the user typed the URL in the address bar, clicked a bookmark, clicked a link from a PDF, clicked a link in a email newsletter that wasn\'t tracked, etc. The source helps determine how visitors are finding the ' . SHORT_ORGANIZATION_NAME . ' website and subsequently your pages.'
			);
	}
}
