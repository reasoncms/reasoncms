<html>
<head>
	<meta http-equiv="Refresh" content="600; URL=<?php echo $_SERVER['PHP_SELF'] ?>">
</head>
<body>
<center>
page automatically refreshes every 10 minutes<br/><br/>
this page loaded at <?php echo date('r' ) ?><br/><br/>
<hr/>
<?php
	include_once( 'reason_header.php' );
	connectDB( REASON_DB );
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	//   Total hits and misses, last 30 days
	//
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	include_once( CARL_UTIL_INC . 'charts/charts.php' );
	
	include_once( CARL_UTIL_INC . 'charts/chart_funcs.php' );
	
	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to view current cache graphs.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
	}
	
	$chart_options = array();
	$chart_options[ 'axis_category' ][ 'skip' ] = 6;
	$chart_options[ 'chart_type' ] = array( 'column', 'line', 'column' );
	$chart_options[ 'chart_line' ][ 'point_shape' ] = 'none';
	$chart_options[ 'chart_line' ][ 'line_thickness' ] = 2;
	$chart_options[ 'canvas_bg' ][ 'width' ] = 800;
	$chart_options[ 'legend_label' ][ 'size' ] = 11;
	$chart_options[ 'axis_category' ][ 'size' ] = 11;
	$chart_options[ 'axis_value' ][ 'size' ] = 11;
	$chart_options[ 'chart_value' ][ 'position' ] = 'cursor';
	
	$chart = array(
		'table' => 'entity',
		'date_field' => 'last_modified',
		'grouping' => 'day',
		'num_groupings' => -60,
		'select' => array(
			'Entities Changed' => 'count(*)',
			'Active Users' => 'count(distinct(last_edited_by))',
		),
	);
	$created_chart = $chart;
	$created_chart[ 'date_field' ] = 'creation_date';
	$created_chart[ 'select' ] = array( 'Entities Created' => 'count(*)');
	$created_chart[ 'where' ] = 'state = "Live"';
	
	$table = build_date_chart_table( $chart );
	$chart_options[ 'chart_data' ] = combine_tables( rescale_table( $table ), build_date_chart_table( $created_chart ) );
	echo '<p><strong>Reason Activity, Last 60 Days</strong></p>';
	drawChart( $chart_options );
	
	$chart[ 'grouping' ] = 'month';
	$chart[ 'num_groupings' ] = -36;
	$created_chart[ 'grouping' ] = 'month';
	$created_chart[ 'num_groupings' ] = -36;
	$chart_options[ 'axis_category' ][ 'skip' ] = 2;
	
	$table = build_date_chart_table( $chart );
	$chart_options[ 'chart_data' ] = combine_tables( rescale_table( $table ), build_date_chart_table( $created_chart ) );
	echo '<p><strong>Reason Activity, Lifetime</strong></p>';
	drawChart( $chart_options );
	
	///////////////////// 24 HOURS ///////////////////////////
	echo '<p><strong>Page Views and Load Average, Last 36 Hours</strong></p>';
	
	$bot_string = 'googlebot|htdig|msnbot';
	
	$chart = array(
		'table' => 'page_cache_log',
		'date_field' => 'dt',
		'grouping' => 'hour',
		'num_groupings' => -36,
		'select' => array(
			'Total Views' => 'count(*)',
		),
		'where' => 'action_type IN ("miss","hit")',
	);

	$la_chart = array(
		'table' => 'system_status',
		'date_field' => 'dt',
		'grouping' => 'hour',
		'num_groupings' => -36,
		'select' => array(
			//'1 Minute Load Average' => 'round(100*avg(la_1_min))',
			'5 Minute Load Average' => 'avg(la_5_min)',
			//'15 Minute Load Average' => 'round(100*avg(la_15_min))',
		),
		'where' => 'host = "'.strtolower(trim(`hostname`)).'"',
	);
	
	
	$views = build_date_chart_table( $chart );
	$load_avg = build_date_chart_table( $la_chart );
	$combined = combine_tables( $views, $load_avg );
	$chart_options[ 'chart_data' ] = rescale_table( $combined );
	drawChart( $chart_options );
	

	///////////////////// 3 DAYS /////////////////////////////
	echo '<p><strong>Page Views, Last 3 Days</strong></p>';
	$chart[ 'num_groupings' ] = -24*3;
	$la_chart[ 'num_groupings' ] = -24*3;
	$chart_options[ 'axis_category' ][ 'skip' ] = 5;
	
	$views = build_date_chart_table( $chart );
	$load_avg = build_date_chart_table( $la_chart );
	$combined = combine_tables( $views, $load_avg );
	$chart_options[ 'chart_data' ] = rescale_table( $combined );
	drawChart( $chart_options );
	
	///////////////////// 7 DAYS /////////////////////////////
	echo '<p><strong>Page Views, Last 7 Days</strong></p>';
	$chart_options[ 'chart_type' ] = array('area','line');
	$chart[ 'num_groupings' ] = -24*7;
	$la_chart[ 'num_groupings' ] = -24*7;
	$chart_options[ 'axis_category' ][ 'skip' ] = 11;

	$views = build_date_chart_table( $chart );
	$load_avg = build_date_chart_table( $la_chart );
	$combined = combine_tables( $views, $load_avg );
	$chart_options[ 'chart_data' ] = rescale_table( $combined );
	drawChart( $chart_options );
	
	

	/////////////////  MAN VS ROBOT /////////////////////////

	echo '<hr/>';
	
	$chart_options[ 'axis_category' ][ 'skip' ] = 1;
	$chart[ 'num_groupings' ] = -36;
	$chart[ 'grouping' ] = 'hour';
	$chart[ 'select' ] = array(
		'People Views' => 'sum(if(user_agent NOT RLIKE "'.$bot_string.'" OR user_agent IS NULL,1,0))',
		'Bot Views' => 'sum(if(user_agent RLIKE "'.$bot_string.'", 1, 0 ))',
	);
	$views = build_date_chart_table( $chart );
	$chart_options[ 'chart_data' ] = $views;
	$chart_options[ 'chart_type' ] = 'stacked column';
	echo '<p><strong>MAN V ROBOT, 36 HOURS</strong></p>';
	drawChart( $chart_options );

	$chart[ 'num_groupings' ] = -24*3;
	$la_chart[ 'num_groupings' ] = -24*3;
	$chart_options[ 'axis_category' ][ 'skip' ] = 5;
	$views = build_date_chart_table( $chart );
	$chart_options[ 'chart_data' ] = $views;
	echo '<p><strong>MAN V ROBOT, 3 DAYS</strong></p>';
	drawChart( $chart_options );

	$chart_options[ 'chart_type' ] = 'stacked area';
	$chart[ 'num_groupings' ] = -24*7;
	$chart_options[ 'axis_category' ][ 'skip' ] = 11;

	$views = build_date_chart_table( $chart );
	$chart_options[ 'chart_data' ] = $views;
	echo '<p><strong>MAN V ROBOT, 7 DAYS</strong></p>';
	drawChart( $chart_options );

	unset( $chart_options[ 'canvas_bg' ][ 'width' ] );
	unset( $chart_options[ 'axis_category' ][ 'skip' ] );
	unset( $chart[ 'num_groupings' ] );
	$chart_options[ 'chart_type' ] = '';
	///////////////// HIT VS MISS ///////////////////////////
	
	echo '<hr/>';
	echo '<br/><strong>Total Views, broken down by hits and misses</strong><br/>';
	
	
	$chart[ 'select' ] = array(
		'Cache Hits' => 'sum( if(action_type = "hit", 1, 0) )',
		'Cache Misses' => 'sum( if( action_type = "miss", 1, 0 ) )',
	);
	$chart_options[ 'chart_type' ] = 'stacked column';
	
	$chart[ 'grouping' ] = 'day';
	$chart[ 'num_groupings' ] = -10;
	draw_date_chart( $chart, $chart_options );
	
	$chart[ 'grouping' ] = 'hour';
	unset( $chart[ 'num_groupings' ] );
	draw_date_chart( $chart, $chart_options );
	
	
	echo '<hr/>';
	echo '<br/><strong>Cache Effectiveness (Hit Ratio)</strong><br/>';
	
	$chart[ 'select' ] = array(
		'Hit Percent' => '100 * (sum( if(action_type="hit",1,0 ) ) / sum( if(action_type="hit" OR action_type="miss",1,0 ) ) )',
		// showing the miss percent just gives a visual confirmation that everything adds up to 100%
		//'Miss Percent' => '100 * ( sum( if(action_type="miss",1,0 ) ) / sum( if(action_type="hit" OR action_type="miss",1,0 ) ) )',
	);
	$chart_options[ 'chart_type' ] = 'column';
	$chart[ 'grouping' ] = 'day';
	$chart[ 'num_groupings' ] = -10;
	draw_date_chart( $chart, $chart_options );
	$chart[ 'grouping' ] = 'hour';
	unset( $chart[ 'num_groupings' ] );
	draw_date_chart( $chart, $chart_options );
	
	
	echo '<hr/>';
	echo '<br/><strong>Page Generation Time</strong><br/>';
	$chart[ 'select' ] = array(
		'Page Gen Time' => 'sum( if(action_type="store" AND page_gen_time IS NOT NULL, page_gen_time, 0 ) ) / sum( if( action_type="store" AND page_gen_time IS NOT NULL, 1, 0) )',
	);
	$chart[ 'where' ] = '';
	unset( $chart[ 'num_groupings' ] );
	$chart[ 'grouping' ] = 'day';
	$chart[ 'num_groupings' ] = -10;
	draw_date_chart( $chart, $chart_options );
	$chart[ 'grouping' ] = 'hour';
	unset( $chart[ 'num_groupings' ] );
	draw_date_chart( $chart, $chart_options );
	
	echo '<pre>';
	passthru('tail -n100 /etc/httpd/logs/access_log | /home/hendlerd/log_watcher.php');
	echo '</pre>';
	
	
	
	
	
	
	
	
	
?>
</center>
</body>
</html>
