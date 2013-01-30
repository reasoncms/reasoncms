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
reason_include_once('function_libraries/root_finder.php');

/**
 * An administrative module that provides page view analytics of the current site using the Google Analytics API v.3
 */

class AnalyticsModule extends DefaultModule
{
	var $site;
	var $site_pages = array();

	var $client;
	var $service;
	var $daily_results;
	var $source_results;
	
	var $startdate;
	var $enddate;

	
	function AnalyticsModule( &$page )
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
		$this->head_items->add_javascript(JQUERY_URL, true);
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/google_api/analytics/analytics.css');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.pie.js');
		$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'flot/jquery.flot.selection.js');

		$this->site = new entity( $this->admin_page->site_id );
		$this->admin_page->title = 'Analytics for '.$this->site->get_value('name');

		$es = new entity_selector($this->admin_page->site_id);
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('(entity.name != "") AND ((url.url = "") OR (url.url IS NULL))'); // only pages, not custom urls
		$this->site_pages = $es->run_one();

		// Initialise the Google Client object
		$this->client = new Google_Client();
		// Your 'Product name'
		$this->client->setApplicationName(GOOGLE_ANALYTICS_APP_NAME);
		 
		$this->client->setAssertionCredentials(
			new Google_AssertionCredentials(
				GOOGLE_ANALYTICS_SERVICE_EMAIL, // email you added to GA
			array('https://www.googleapis.com/auth/analytics.readonly'),
			file_get_contents(GOOGLE_ANALYTICS_PRIVATE_KEY_FILE)  // keyfile you downloaded
			)
		);
		// other settings
		$this->client->setClientId(GOOGLE_ANALYTICS_SERVICE_CLIENT_ID);
		// Return results as objects.
		$this->client->setUseObjects(true);
		$this->client->setAccessType('offline_access');  // this may be unnecessary?

		// create analytics service
		$this->service = new Google_AnalyticsService($this->client);

		//initialize start and end dates
		$this->startdate = date('Y-m-d', strtotime('-1 month -1 day'));
		$this->enddate = date('Y-m-d', strtotime('-1 day'));
	}
	
	/**
	 * Lists the top pages (views) and show analytics
	 * 
	 * @return void
	 */
	function run()
	{
		/**
		 * @todo change to chosen plasmature element
		 */

		$site_urls_array = array();
		foreach ($this->site_pages as $page)
		{
			$u = build_URL($page->get_value('id'));
			$site_urls_array[$u] = $u;
		}
		asort($site_urls_array);    
		$site_urls_array = array('all_pages' => 'All ' . $this->site->get_value('name') . ' pages') + $site_urls_array;
		$disco = new Disco();
		// $disco->add_element('url', 'select_no_sort', array('options' => $site_urls_array, 'add_empty_value_to_top' => false, 'default' => 'all_pages', 'display_name'=>'URL(s)'));
		$disco->add_element('url', 'chosen_select', array('options' => $site_urls_array, 'default' => 'all_pages', 'display_name'=>'URL(s)', 'size'=>'60'));
		$disco->add_element('location', 'radio_inline_no_sort', array('options' => array('anywhere'=>'Anywhere', 'off_campus'=>'Off-Campus', 'on_campus'=>'On-Campus'), 'default' => 'anywhere'));
		$disco->add_element('start_date', 'textdate');
		$disco->add_element('end_date', 'textdate');
		$disco->add_required('url');
		$disco->add_required('location');
		$disco->add_required('start_date');
		$disco->add_required('end_date');
		$disco->set_actions(array('Get Analytics'));
		$disco->show_error_jumps = false;
		$error_checks_callback = array($this,'error_check_disco');
		$disco->add_callback($error_checks_callback, 'run_error_checks');
		$start = $disco->get_element('start_date');
		$startdate = date('Y-m-d', strtotime('-1 month -1 day'));
		$start->set($startdate);
		$end = $disco->get_element('end_date');
		$enddate = date('Y-m-d', strtotime('-1 day'));
		$end->set($enddate);

		echo '<div id="analytics-module" class="noscript">' . "\n";
		echo '<script>$("div").removeClass("noscript");</script>' ."\n";
		 
		$disco->run();
		
		if (!$disco->has_errors())
		{
			if ($this->get_ga_data($disco->get_value('url'), $disco->get_value('location'), $disco->get_value('start_date'), $disco->get_value('end_date')))
			{
				$totals = $this->daily_results->getTotalsForAllResults();
				if (($totals['ga:pageviews'] != '(none)') && !is_null($this->source_results->getRows()))
				{
					$loc = $disco->get_value_for_display('location');
					$location_text = $loc == 'Anywhere' ? '' : $loc . ' ';

					echo '<div class="results-title"><h4>' . $location_text . 'Totals for <em>' . $disco->get_value_for_display('url') . '</em>, ' . date("M d, Y", strtotime($disco->get_value('start_date'))) . ' - ' . date("M d, Y", strtotime($disco->get_value('end_date'))) . '</h4></div>' . "\n";

					echo '<div id="total-results">' . "\n";
					echo '<div class="metric"><span>Pageviews</span><br /><strong>'.number_format($totals['ga:pageviews']).'</strong></div>' . "\n";
					echo '<div class="metric"><span>Unique pageviews</span><br /><strong>'.number_format($totals['ga:uniquePageviews']).'</strong></div>' . "\n";
					echo '<div class="metric"><span>Entrances</span><br /><strong>'.number_format($totals['ga:entrances']).'</strong></div>' . "\n";
					$pagetext = ($disco->get_value('url') == 'all_pages' ? 'pages' : 'page');
					echo '<div class="metric"><span>Avg time on ' . $pagetext . '</span><br /><strong>'. gmdate("i:s", round($totals['ga:avgTimeOnPage'])).'</strong></div>' . "\n";
					echo '<div class="metric"><span>Bounce rate</span><br /><strong>'.round($totals['ga:entranceBounceRate'], 2).'%</strong></div>' . "\n";
					echo '<div class="metric"><span>Exit rate</span><br /><strong>'.round($totals['ga:exitRate'], 2).'%</strong></div>' . "\n";
					echo '<div style="clear: left;"></div>' . "\n";
					echo '</div>' . "\n";  //total-results

					echo '<div class="results-title"><h4>' . $location_text . 'Daily Results for <em>' . $disco->get_value_for_display('url') . '</em>, ' . date("M d, Y", strtotime($disco->get_value('start_date'))) . ' - ' . date("M d, Y", strtotime($disco->get_value('end_date'))) . '</h4></div>' . "\n";

					echo '<div id="daily-results-div" class="jsdisabled">' ."\n";
					echo $this->draw_table($this->daily_results);
					echo '</div>' . "\n";//daily-results

					echo '<div class="jsenabled">' . "\n";
					echo '<div id="placeholder" style="width:785px;height:300px;margin:20px"></div>' . "\n";

					echo '<p>Click drag to change zoom level. The plot below shows an overview.</p>' . "\n";

					echo '<div id="overview" style="margin-left:125px;margin-top:20px;width:600px;height:50px"></div>' . "\n";
					echo '</div>' . "\n"; //jsenabled

					$top = ($this->source_results->getTotalResults() > 20 ? '20' : $this->source_results->getTotalResults());
					echo '<div class="results-title"><h4>' . $location_text . 'Top ' . $top . ' Sources for <em>' . $disco->get_value_for_display('url') . '</em>, ' . date("M d, Y", strtotime($disco->get_value('start_date'))) . ' - ' . date("M d, Y", strtotime($disco->get_value('end_date'))) . '</h4></div>' . "\n";

					echo '<div id="source-results-div" class="jsdisabled">' ."\n";
					echo $this->draw_source_list($this->source_results);
					echo '</div>' . "\n";//source-results

					echo '<div id="source-results-div" class="jsenabled">' . "\n";
					echo '<div id="source-pie-chart" style="width:785px;height:375px;margin:20px"></div>' . "\n";
					echo '<div id="source-pie-hover"></div>';
					echo '</div>' . "\n"; //jsenabled

					$this->draw_pie_chart($this->source_results);
					$this->draw_line_chart($this->daily_results);
				}
				else
				{
					echo '<div class="analytics-error">There are no results for this time frame.</div>';
				}
			}
		}

		echo '<div class="analytics-footer">' . "\n";
		echo '<p>Please contact '.REASON_CONTACT_INFO_FOR_ANALYTICS.' for more detailed analytics.';
		echo '&nbsp;&nbsp;&nbsp;'
				. '<a href="' 
				. $this->admin_page->make_link( array(
				'site_id'=>$this->site->id(),
				'cur_module'=>'AnalyticsAbout' ) ) 
				. '">About Analytics <img src="'.REASON_HTTP_BASE_PATH.'silk_icons/information.png" alt="" /></a></p>';

		echo '</div>' . "\n"; //analytics-footer
		echo '</div>' . "\n"; //analytics-module

	}

	function error_check_disco(&$disco)
	{
		if ($disco->get_value('start_date') > $disco->get_value('end_date')){
			$disco->set_error('start_date', 'Please choose a start date that is before the end date.');
		}
	}


	function get_ga_data($page_url, $location, $start, $end)
	{   
		$provider_name = strtolower(addslashes(GA_SERVICE_PROVIDER_NAME));
		// set filters
		$filter = '';
		if ($location == 'off_campus'){ 
			$filter .= 'ga:networkLocation!~' . $provider_name . ';';
		}
		if ($location == 'on_campus'){
			$filter .= 'ga:networkLocation=~' . $provider_name . ';';   
		}
		if ($page_url == 'all_pages'){
			$filter .= 'ga:pagePath=~' . GA_HOST_NAME . $this->site->get_value('base_url');
		} else { 
			$filter .= 'ga:pagePath==' . GA_HOST_NAME . $page_url . 'index.html';
		}

		// get daily google analytics data ()
		$profile_id = 'ga:' . GOOGLE_ANALYTICS_PROFILE_ID;
		try {
			$this->daily_results = $this->service->data_ga->get($profile_id, $start, $end, 'ga:pageviews, ga:entrances, ga:avgTimeOnPage, ga:uniquePageViews, ga:entranceBounceRate, ga:exitRate', array('filters'=> $filter,'dimensions' => 'ga:date'));
		} 
		catch(Exception $de) {
			$de->getMessage();
		}     
		// get cumulative source ga data
		try {
			$this->source_results = $this->service->data_ga->get($profile_id, $start, $end, 'ga:pageviews', array('filters'=>$filter,'dimensions' =>'ga:source', 'sort'=>'-ga:pageviews', 'max-results'=>20));
		}
		catch (Exception $se) {
			$se->getMessage();
		}

		if (isset($de) || isset($se))
		{
			if (is_developer())
			{
				if ($de)
				{
					echo '<div class="analytics-error">Daily Results Error: ' . htmlspecialchars($de) . '</div>';
				}
				if ($se)
				{
					echo '<div class="analytics-error">Source Results Error: ' . htmlspecialchars($se) . '</div>';
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

	
	function draw_table(&$data)
	{
		$table = '';
		if (count($data->getRows()) > 0) {
			$table .= '<table id="daily-results">' . "\n";

			// Print headers.
			$table .= '<tr class="daily-results">' . "\n";

			foreach ($data->getColumnHeaders() as $header) {
				//remove ga: from the headers
				$head = substr($header->name, 3);
				//capitalize first letter and addspaces
				$head = preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $head);
				$head = ucfirst($head);

				if ($head == 'Date')
					$head == $head . '<br>(yyyymmdd)';
				if (stripos($head, 'rate'))
					$head = $head . '<br>%';
				if (stripos($head, 'time'))
					$head = $head . '<br>(seconds)';
				$table .= '<th class="daily-results">' . $head . '</th>' . "\n";
			}
			$table .= '</tr>' . "\n";

			$table .= '<tbody>' . "\n";
			// Print table rows.
			foreach ($data->getRows() as $row) {
			  $table .= '<tr class="daily-results">' . "\n";
				foreach ($row as $cell) {
					//round off decimals
					if (is_numeric( $cell ) && floor( $cell ) != $cell)    
						$cell = round($cell, 2);
					$table .= '<td class="daily-results">' . htmlspecialchars($cell, ENT_NOQUOTES) . '</td>' . "\n";
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


	function draw_source_list(&$data)
	{
		$table = '';
		$count = count($data->getRows());
		if ($count > 0) {
			$table .= '<table id="source-results">' . "\n";

			// Print headers.
			$table .= '<tr class="source-results">' . "\n";

			$head = '';
			foreach ($data->getColumnHeaders() as $header) {
				//remove ga: from the headers
				$head = substr($header->name, 3);
				//capitalize first letter and addspaces
				$head = preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $head);
				$head = ucfirst($head);

				$headers[] = $head;
			}

			$table .= '<th class="source-results" colspan="100%">' . implode('&nbsp;&nbsp;&nbsp;&rarr;&nbsp;&nbsp;&nbsp;', $headers) . '</th>' . "\n";
			$table .= '</tr>' . "\n";

			$table .= '<tbody>' . "\n";
			// Print table rows.
			$rows = $data->getRows();
			$col1 = array_slice($rows, 0, round($count/2));
			$col2 = array_slice($rows, round($count/2));
			$table .= '<tr>' . "\n";
			$table .= '<td>' . "\n";
			$count = 1;
			foreach ($col1 as $col) {
					$table .= $count . '. ' . $col[0] . '&nbsp;&nbsp;&nbsp;&rarr;&nbsp;&nbsp;&nbsp;' . $col[1] . '<br>';
					$count++;
			}
			$table .= '</td>' . "\n";
			$table .= '<td>';
			foreach ($col2 as $col) {
					$table .= $count . '. ' . $col[0] . '&nbsp;&nbsp;&nbsp;&rarr;&nbsp;&nbsp;&nbsp;' . $col[1] . '<br>';
					$count++;
			}
			$table .= '</td>' . "\n";
			$table .= '</tr>' . "\n";
			$table .= '</tbody>' . "\n";
			$table .= '</table>' . "\n";

		} else {
			$table .= '<h4>There are no source results for this query. Please contact ' . REASON_CONTACT_INFO_FOR_ANALYTICS . ' for assistance.</h4>' . "\n";
		}
		return $table;
	}

	function draw_pie_chart(&$data){
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

	function draw_line_chart(&$data)
	{
		$rows = $data->getRows();
		foreach ($rows as $row) {
			$unixTime = '';
			$year = substr($row[0], 0, -4);
			$month = substr($row[0], 4, -2);
			$day = substr($row[0], -2);
	
			$unix_time = mktime(0,0,0,$month,$day,$year) . '000';
			$pageviews[] = $unix_time . ', ' . $row[1];
			$entrances[] = $unix_time . ', ' . $row[2];
		}
		$pageviews_output = '[[';
		$pageviews_output .= implode('], [', $pageviews);
		$pageviews_output .= ']]' . "\n";
		$entrances_output = '[[';
		$entrances_output .= implode('], [', $entrances);
		$entrances_output .= ']]' . "\n";

		echo '<script id="source">
		$(function () {
			var d = ' . $pageviews_output . ';' .
		   'var e = ' . $entrances_output . ';' .


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

			var plot = $.plot($("#placeholder"), [{label:"Pageviews", data:d},{label:"Entrances", data:e}], options);

			var overview = $.plot($("#overview"), [d,e], {
				series: {
					lines: { show: true, lineWidth: 1 , fill: true},
					shadowSize: 0
				},
				xaxis: { ticks: [], mode: "time" },
				yaxis: { ticks: [], min: 0, autoscaleMargin: 0.1 },
				selection: { mode: "x" }
			});

			// now connect the two

			$("#placeholder").bind("plotselected", function (event, ranges) {
				// do the zooming
				plot = $.plot($("#placeholder"), [{label:"Pageviews", data:d},{label:"Entrances", data:e}],  
							  $.extend(true, {}, options, {
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
	 * Lists the top pages (views) and show analytics
	 * 
	 * @return void
	 */
	function run() // {{{
	{
		echo '<div id="analytics-about">'."\n";
		echo '<div id="average_time_help">'."\n";
		echo '<h5>Average Time On Page</h5>'."\n";
		echo '<p>The average amount of time visitors spent viewing the selected page or set of pages. Generally, a higher average time on page is better, because it indicates that the visitors are engaging with your content.</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="bounce_rate_help">'."\n";
		echo '<h5>Bounce Rate</h5>'."\n";
		echo '<p>The percentage of visits that looked at one single page and moved on to a different website. Generally, the lower the bounce rate, the better, because it means visitors are staying on the ' . SHORT_ORGANIZATION_NAME . ' website.</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="entrance_rate_help">'."\n";
		echo '<h5>Entrances</h5>'."\n";
		echo '<p>The number of visits to the ' . SHORT_ORGANIZATION_NAME . ' website that started on the selected page or set of pages. A higher number means that this page is good at attracting visitors to the ' . SHORT_ORGANIZATION_NAME . ' website.</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="exit_rate_help">'."\n";
		echo '<h5>Exit Rate</h5>'."\n";
		echo '<p>The percentage of total page views where the selected page was the last page in the visitor\'s session. The exit rate is important for finding pages in a visitor flow where visitors are dropping off before completing a desired action.</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="pageviews_help">'."\n";
		echo '<h5>Pageviews</h5>'."\n";
		echo '<p>The total number of times visitors viewed the selected page or set of pages. The higher the better!</p>'."\n";
		echo '</div>'."\n";
		echo '<hr>';
		echo '<div id="unique_views_help">'."\n";
		echo '<h5>Unique Pageviews</h5>'."\n";
		echo '<p>The number of user sessions during which the selected page or set of pages was viewed one or more times. I.e. each page is counted only once in a given visitor session.</p>'."\n";
		echo '</div>'."\n";
		
		echo '<hr>';
		echo '<div id="sessions_help">'."\n";
		echo '<h5>Session</h5>'."\n";
		echo '<p>The activity of one user (IP address) on a given website within a given time frame.</p>'."\n";
		echo '</div>'."\n";

		echo '<hr>';
		echo '<div id="source_help">'."\n";
		echo '<h5>Source</h5>'."\n";
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