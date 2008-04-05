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
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'view_sensitive_data' ) )
	{
		die('<html><head><title>Find Something in Reason</title></head><body><h1>Sorry.</h1><p>You do not have permission to view combined graphs.</p><p>Only Reason users who have sensitive data viewing privileges may do that.</p></body></html>');
	}
	
	$chart = array(
		'table' => 'page_cache_log',
		'date_field' => 'dt',
		'grouping' => 'month',
		'select' => array(
			'Total Views' => 'sum( if( action_type = "hit" OR action_type = "miss", 1, 0 ) )',
		),
	);
	
	$grouping = 'hour';
	$group_every = '';
	$num_groupings = -7;
	
	$chart_options[ 'canvas_bg' ][ 'width' ] = 800;
	$chart[ 'select' ] = array(
		'Total Views' => 'sum( if( action_type IN ("miss","hit"), 1, 0 ) )',
	);
	$chart[ 'grouping' ] = $grouping;
	$chart[ 'num_groupings' ] = $num_groupings;
	$chart[ 'group_every' ] = $group_every;
	
	///////////////////// 24 HOURS ///////////////////////////
	echo '<p><strong>Page Views, Last 24 Hours</strong></p>';
	$chart_options[ 'axis_category' ][ 'skip' ] = 1;
	
	
	$la_chart = array(
		'table' => 'system_status',
		'date_field' => 'dt',
		'grouping' => $grouping,
		'group_every' => $group_every,
		'select' => array(
			'1 Minute Load Average' => 'avg(la_1_min)',
			//'5 Minute Load Average' => 'avg(la_5_min)',
			//'15 Minute Load Average' => 'avg(la_15_min)',
		),
		'num_groupings' => $num_groupings,
	);
	
	$la_chart_options = array();
	$la_chart_options[ 'chart_type' ] = array( 'column', 'line' );
	$la_chart_options[ 'chart_line' ][ 'point_shape' ] = 'none';
	$la_chart_options[ 'chart_line' ][ 'line_thickness' ] = 2;
	$la_chart_options[ 'canvas_bg' ][ 'width' ] = 800;
	
	$view_table = build_date_chart_table( $chart );
	$la_table = build_date_chart_table( $la_chart );
	
	$combined_table = combine_tables( $view_table, $la_table );
	if( empty( $combined_table ) )
		trigger_error('Unable to combine tables');
	
	$la_chart_options[ 'chart_data' ] = rescale_table( $combined_table );
	drawChart( $la_chart_options );
	$la_chart_options[ 'chart_data' ] = rescale_table( $combined_table, 'median' );
	drawChart( $la_chart_options );
?>
