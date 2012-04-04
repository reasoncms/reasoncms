<?php
/**
 * A file that graphs out page cache information
 * @todo This method of recording stats is way too slow to report on; should probably log into files instead of into the DB
 * @package reason
 * @subpackage scripts
 */
  
	include_once( 'reason_header.php' );
	connectDB( REASON_DB );
	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'view_sensitive_data' ) )
	{
		die('<html><head><title>Cache Stats</title></head><body><h1>Sorry.</h1><p>You do not have permission to view cache stats.</p><p>Only Reason users who have sensitive data viewing privileges may do that.</p></body></html>');
	}
	else
	{
		echo '<h1>Not Implemented for Your Version of Reason</h1>';
		echo '<p>The current version of Reason does not have the page_cache_log_archive table, and is not setup by default to populate the table page_cache_log with page cache hit / miss information.</p>';
		die;
	}
	$request = array(
		'clear' => '',
		'col' => 'total',
		'dir' => 'desc',
		'start' => 0,
		'min_views' => 0,
		'start_dt' => '',
		'end_dt' => '',
		'key' => '',
	);
	foreach( $request AS $var => $default_val )
	{
		$$var = (!empty( $_GET[ $var ] ) ? $_GET[ $var ] : $default_val);
	}
	$per_page = 20;

	if( $clear )
	{
		include_once( CARL_UTIL_INC . 'cache/cache.php' );
		$c = new PageCache();
		$c->dir = REASON_CACHE_DIR;
		$c->clear( urldecode($clear) );
		if( !empty( $_SERVER['HTTP_REFERER'] ) )
			$url = $_SERVER['HTTP_REFERER'];
		else
			$url = $_SERVER['PHP_SELF'];
		header( 'Location: '.$url );
	}
	if( !empty( $key ) )
	{
		include_once( CARL_UTIL_INC . 'charts/charts.php' );
		include_once( CARL_UTIL_INC . 'charts/chart_funcs.php' );

		echo '<a href="javascript:history.back()">back to listing</a><br/>';
		echo '<a href="'.$key.'">'.$key.'</a><br/><br/>';
		
		//////////////////////////////////////////////////////////////////////////////////////////////
		// DAILY CHART
		//////////////////////////////////////////////////////////////////////////////////////////////
		$chart = array(
			'table' => 'page_cache_log_archive',
			'grouping' => 'month',
			'select' => 'sum( if(action_type IN ("hit","miss") AND cache_key = "'.$key.'",1,0) )',
			'date_field' => 'dt',
		);
		draw_date_chart( $chart );
		$chart[ 'grouping' ] = 'week';
		draw_date_chart( $chart );
		$chart[ 'grouping' ] = 'day';
		draw_date_chart( $chart );
		$chart[ 'grouping' ] = 'hour';
		draw_date_chart( $chart );
		/*
		echo 'Views: Last 30 Days<br/>';
		$start_date = time() - (60*60*24*30);
		$q = '
			select month(dt) month, dayofmonth(dt) day, count(*) count 
			from page_cache_log_archive
			where cache_key = "'.urldecode( $key ).'"
			  and dt > "'.date('Y-m-d 00:00:00',$start_date).'"
			  and (action_type = "hit" OR action_type = "miss")
			group by month(dt), dayofmonth(dt)
			order by month(dt), dayofmonth(dt)';
		
		$r = db_query( $q, 'Unable to grab hourly stats for key '.urldecode( $key ) );
		$data = array();
		while( $row = mysql_fetch_assoc( $r ) )
			$data[ $row[ 'month' ].'/'.$row['day'] ] = $row[ 'count' ];
		
		$hits = array( $key );
		$days = array( "" );
		for( $i = 0; $i <= 30; $i++ )
		{
			$d = date('m/j', $start_date + (60 * 60 * 24 * $i) );
			$days[] = $d;
			$hits[] = (!empty( $data[$d] ) ? $data[$d] : 0 );
		}
		$chart[ 'chart_type' ] = '';
		$chart[ 'chart_line' ] = array(
			'point_shape' => 'none',
			'line_thickness' => 2,
		);
		$chart[ 'chart_data' ] = array( $days, $hits );
		$chart[ 'canvas_bg' ] = array(
			'width' => 600,
			'height' => 400,
		);
		$chart[ 'axis_category' ] = array(
			'skip' => 4
		);
		$chart[ 'chart_value' ] = array(
			'position' => 'cursor',
			'hide_zero' => true,
			'size' => 12
		);
		
		DrawChart( $chart );
		unset( $chart[ 'chart_data' ] );
		echo '<br/><br/>';
		
		//////////////////////////////////////////////////////////////////////////////////////////////
		// HOURLY CHART
		//////////////////////////////////////////////////////////////////////////////////////////////
		echo 'Views: All Time, Hourly<br/>';
		$q = '
			select hour(dt) hour ,count(*) count 
			from page_cache_log_archive 
			where cache_key = "'.urldecode( $key ).'" 
			  and (action_type = "hit" OR action_type = "miss")
			group by hour(dt)';
		$r = db_query( $q, 'Unable to grab hourly stats for key '.urldecode( $key ) );
		$data = array();
		while( $row = mysql_fetch_assoc( $r ) )
			$data[ $row[ 'hour' ] ] = $row[ 'count' ];
		
		//$chart[ 'canvas_bg' ] = array(
		//	'width' => 600,
		//	'height' => 400,
		//);
		//$chart[ 'axis_category' ] = array(
		//	'skip' => 2
		//);
		
		$hours = array( "" );
		$hits = array( $key );
		for( $i = 0; $i < 24; $i++ )
		{
			$hours[] = date( 'ga', mktime( $i, 0, 0, 1, 1, 2004 ) );
			$hits[] = (!empty( $data[ $i ] ) ? $data[ $i ] : 0);
		}
		$chart[ 'chart_data' ] = array( $hours, $hits );
		$chart[ 'axis_category' ][ 'skip' ] = 2;
		DrawChart ( $chart );
		
		echo '<br/><br/>';
		*/
		
		exit;
	}
	
	$q_calcs =
		'sum( if(action_type="hit" OR action_type="miss",1,0 ) ) total, '.
		'sum( if(action_type="hit",1,0 ) ) hits, '.
		'sum( if(action_type="hit",1,0 ) ) / sum( if(action_type="hit" OR action_type="miss",1,0 ) ) hit_pct, '.
		'sum( if(action_type="miss",1,0 ) ) misses, '.
		'sum( if(action_type="miss",1,0 ) ) / sum( if(action_type="hit" OR action_type="miss",1,0 ) ) miss_pct, '.
		'sum( if(action_type="store" AND extra1 IS NOT NULL AND extra1="diff",1,0 ) ) diffs, '.
		'sum( if(action_type="store" AND extra1 IS NOT NULL AND extra1="diff",1,0 ) ) / sum( if(action_type="store",1,0 ) ) diff_pct, '.
		'(unix_timestamp( now() ) - unix_timestamp( max( if( action_type="store", dt, null ) ) ) ) / 60 minutes_since_last_view, '.
		'round((unix_timestamp( now() ) - unix_timestamp( min(dt) )) / sum( if(action_type="hit" OR action_type="miss",1,0) )) / 60 AS avg_lull_min, '.
		'sum( if(action_type="store" AND page_gen_time IS NOT NULL, page_gen_time, 0 ) ) / sum( if( action_type="store" AND page_gen_time IS NOT NULL, 1, 0) ) avg_gen_time ';
		//'unix_timestamp( now() ) - unix_timestamp( max( if( action_type="store", dt, null ) ) ) age, '.
		//'unix_timestamp( now() ) - unix_timestamp( min(dt) ) first_hit, '.
		//'round((unix_timestamp( now() ) - unix_timestamp( min(dt) )) / sum( if(action_type="hit" OR action_type="miss",1,0) )) AS avg_lull, '.
	
	$select_from = 
'select sql_calc_found_rows
	cache_key,
	'.$q_calcs.'
from
	page_cache_log_archive
';

	$where = '';
	if( $start_dt OR $end_dt )
	{
		$where = 'WHERE ';
		if( $start_dt )
			$where .= 'dt > "'.$start_dt.'" ';
		if( $start_dt AND $end_dt )
			$where .= ' AND ';
		if( $end_dt )
			$where .= 'dt < "'.$end_dt.'" ';
	}

	$group_by = '
group by
	cache_key
';
	if( $min_views )
	{
		$group_by .= '
having
	sum(if(action_type="hit" OR action_type="miss",1,0)) > '.$min_views;
	}

	$order_by = '
order by
	'.$col.' '.$dir.'
limit '.$start.', '.$per_page;
	
	
	
	$q = $select_from.$where.$group_by.$order_by;
	
	$r = db_query( $q, 'Unable to grab stats' );
	while( $row = mysql_fetch_assoc( $r ) )
		$rows[] = $row;
	$keys = array_keys(current($rows));
	
	// get total rows found
	list( ,$total_rows ) = each( mysql_fetch_assoc( mysql_query( 'select found_rows()' ) ) );
	
	$q_total = 'select "TOTAL" AS cache_key, '.$q_calcs.' from page_cache_log_archive '.$where;
	$r_total = db_query( $q_total, 'Unable to grab stats' );
	$total_row = mysql_fetch_assoc( $r_total );
?>
<html>
	<head>
		<style type="text/css">
		<!--
		a {
			text-decoration: none;
		}
		-->
		</style>
	</head>
	<body>
	<a href="cache_current_graphs.php">this week's graphs</a><br/>
	<a href="cache_graphs.php">longer term graphs</a><br/>
	<br/>
	<br/>
<?php
	echo '<table border="1" cellspacing="0" cellpadding="4" style="font-size: 9pt; font-face: Monaco, Courier, fixed-width">';
	echo '<tr style="background-color: #6f6">';
	foreach( $keys AS $key )
	{
		$bg = '6f6';
		$output = prettify_string($key);
		// make sorting link
		$link = "?col=$key&amp;dir=".($key != $col ? 'desc' : ($dir == 'desc' ? 'asc' : 'desc' ) ).'&amp;min_views='.$min_views.'&amp;start_dt='.$start_dt.'&amp;end_dt='.$end_dt;
		$output = '<a href="'.$link.'">'.$output.'</a>';
		// show sorting arrows
		if( $key == $col )
		{
			if( $dir == 'desc' )
				$output = "v&nbsp;$output&nbsp;v";
			else
				$output = "^&nbsp;$output&nbsp;^";
			$bg = 'f66';
		}
		echo '<th bgcolor="#'.$bg.'">'.$output.'</th>';
	}
	echo '<th>Action</th>';
	echo '<th>hash</th>';
	echo '</tr>';
	$row_alt = 0;
	foreach( $rows AS $row )
	{
		echo '<tr';
		if( $row_alt ) echo ' style="background-color: #ccc"';
		echo '>';
		foreach( $row AS $key => $val )
		{
			$output = $val;
			if( $key == 'cache_key' )
			{
				$parts = parse_url( $val );
				$output = '<a href="?key='.urlencode($val).'" title="'.$val.'">'.$parts['path'].(!empty($parts['query'])?'?'.$parts['query']:'').'</a>';
			}
			echo "<td>$output</td>";
		}
		echo '<td><a href="?clear='.urlencode($row['cache_key']).'">Clear</a></td>';
		echo '<td>'.md5($row['cache_key']).'</td>';
		echo '</tr>';
		$row_alt = 1 - $row_alt;
	}
	echo '<tr>';
	foreach( $total_row AS $val )
		echo '<td bgcolor="#999">'.$val.'</td>';
	echo '<td bgcolor="#999">-</td>';
	echo '<td bgcolor="#999">-</td>';
	echo '</tr>';
	echo '</table>';
	$s = $start + 1;
	$e = $start + $per_page;
	if( $e > $total_rows ) $e = $total_rows;
	echo "Showing records $s - $e of $total_rows<br/><br/>";
	if( $start > 0 )
		echo '<a href="?dir='.$dir.'&amp;col='.$col.'&amp;min_views='.$min_views.'&amp;start='.($start-$per_page).'&amp;start_dt='.$start_dt.'&amp;end_dt='.$end_dt.'">&laquo; Previous Page</a>';
	if( $start > 0 AND ($start + $per_page) < $total_rows )
		echo ' || ';
	if( ($start + $per_page) < $total_rows )
		echo '<a href="?dir='.$dir.'&amp;col='.$col.'&amp;min_views='.$min_views.'&amp;start='.($start+$per_page).'&amp;start_dt='.$start_dt.'&amp;end_dt='.$end_dt.'">Next Page &raquo;</a>';
	
?>
	<form method="get">
		Only show pages with at least <input type="text" size="3" name="min_views" value="<?php echo $min_views; ?>"/> views.<br/>
		Start Datetime <input type="text" name="start_dt" value="<?php echo $start_dt ?>"/> (YYYYMMDDHHMSS)<br/>
		End Datetime <input type="text" name="end_dt" value="<?php echo $end_dt ?>"/> (YYYYMMDDHHMSS)<br/>
		<input type="submit"/>
		<input type="hidden" name="col" value="<?php echo $col ?>"/>
		<input type="hidden" name="dir" value="<?php echo $dir ?>"/>
	</form>
	</body>
</html>
