<?php
	//include_once( 'charts.php' );
	
	function ut_to_mysql( $ut )
	{
		list( $y, $m, $d, $h, $i, $s ) = explode( ' ', date('Y m d H i s',$ut) );
		return "$y-$m-$d $h:$i:$s";
	}
	
	function get_date_part( $date, $part )
	{
		switch( $part )
		{
			case 'year': return date( 'Y', $date ); break;
			case 'quarter': return floor((date('m', $date) - 1) / 3) + 1; break;
			case 'month': return date( 'n', $date ); break;
			case 'week': return date( 'W', $date ); break;
			case 'dayofmonth': return date( 'j', $date ); break;
			case 'hour': return date( 'G', $date ); break;
			case 'minute': return date( 'i', $date ); break;
		}
		return false;
	}
	
	function step_date( $date, $part, $step = 1 )
	{
		$y = get_date_part( $date, 'year' );
		$q = get_date_part( $date, 'quarter' );
		$m = get_date_part( $date, 'month' );
		$d = get_date_part( $date, 'dayofmonth' );
		$h = get_date_part( $date, 'hour' );
		$mi = get_date_part( $date, 'minute' );
		$s = 0;
		switch( $part )
		{
			case 'year': $y += $step; $m = 1; $d = 1; $h = 0; $mi = 0; break;
			case 'quarter': $m = ((($q += $step) * 3 ) - 2); $d = 1; $h = 0; $mi = 0; break;
			case 'month': $m += $step; $d = 1; $h = 0; $mi = 0; break;
			case 'week': $d += (7 * $step); $h = 0; $mi = 0; break;
			case 'dayofmonth': $d += $step; $h = 0; $mi = 0; break;
			case 'hour': $h += $step; $mi = 0; break;
			case 'minute': $mi += $step; break;
		}
		return mktime( $h, $mi, $s, $m, $d, $y );
	}
	
	function prettify_date( $date, $part )
	{
		switch( $part )
		{
			case 'year':
				$r = get_date_part( $date, 'year' );
				break;
			case 'quarter':
				$r = 'Q'.get_date_part( $date, 'quarter' ).' '.date( 'Y', $date );
				break;
			case 'month':
				$r = date( 'M y', $date );
				break;
			case 'week':
				$r = 'Week '.get_date_part( $date, 'week' );
				break;
			case 'dayofmonth':
				$r = date('M jS',$date);
				break;
			case 'hour':
				$r = date('ga',$date);
				break;
			case 'minute':
				$r = date('g:ia',$date);
				break;
			default:
				$r = get_date_part( $date, $part );
				break;
		}
		return $r;
	}
	
	/**
	* Creates a chart using the PHP/SWF Chart utility, specifically a chart based on date
	* information.  The function takes an array full of options to specify the characteristics of
	* the chart.
	*
	* Array Options
	*
	** grouping =>
	*     year
	*     quarter
	*     month
	*     week
	*     dayofmonth
	*     hour
	*   How to group the results
	*
	******** NOT IMPLEMENTED
	** summary => bool
	*   Whether to group all rows or just the last X time groupings
	*
	** table => string
	*   the table to use
	*
	** where => string
	*   additions to the where clause.  This is just an SQL string that will be appended to the WHERE clause that the
	*   function generates.  It will be ANDed with the rest of the date parameters and such.  More specifically, at the
	*   end of the generated WHERE clause, it will be inserted like so: WHERE stuff and stuff and ($where) so you can
	*   use ORs inside of your own fragment if you wish
	*
	** num_groupings => int
	*   number of days/weeks/hours/etc to use (not in summary mode)
	*   can be positive or negative.  If num_groupings = 0, it will report
	*   on all available data and ignore the start_date.
	*
	******* PROBABLY BUGGY
	** group_every => int
	*   groups together the grouping you have chosen.  example: if you want to group on every 2 days instead of 1 day,
	*   this would be set to 2 and groupings would be set to 'day'.  also useful in combination with minutes.  for
	*   example, grouping every 15, 20, or 30 minutes.
	*
	*
	** start_date => unix timestamp
	** end_date => unix_timestamp
	*   the base date to use.  this acts as either a beginning or an ending based on whether the
	*   num_groupings is positive or negative, respectively.  it's also possible to use a start date
	*   and an end date.  this will ignore the num_groupings variable.  there may be some slight
	*   errors in date calculations at the edges, specifically, it looks like the end_date does
	*   not include the full day.
	*
	** select => array( 'string' => string, string => string, ...) OR string
	*   The actual select statement to run the chart for.  example: if you are looking for total
	*   minutes of phone calls you would use something like 'sum(minutes)'.
	*   This can be an array of strings to give multiple measures to chart.
	*
	** date_field => string
	*   the date(time) field
	*/
	function draw_date_chart( $args, $chart = array(), $debug = false )
	{
		////////////////////////////////////////////////////////////////////////////////
		// DRAW CHART
		////////////////////////////////////////////////////////////////////////////////
		$data = build_date_chart_table( $args, $debug );
		
		$default_font_size = 11;
		
		$chart_defaults = array();
		$chart_defaults[ 'chart_value' ][ 'position' ] = 'cursor';
		$chart_defaults[ 'chart_value' ][ 'size' ] = $default_font_size;
		$chart_defaults[ 'axis_category' ][ 'skip' ] = count( $data[ 1 ] ) / 10;
		$chart_defaults[ 'axis_category' ][ 'size' ] = $default_font_size;
		$chart_defaults[ 'axis_value' ][ 'size' ] = $default_font_size;
		$chart_defaults[ 'legend_label' ][ 'size' ] = $default_font_size;
		
		// order is important.  we want anything in $chart to overwrite the defaults
		$chart = array_merge_n( $chart_defaults, $chart );
		
		$chart[ 'chart_data' ] = $data;
		
		drawChart( $chart );
	}
	function build_date_chart_table( $args, $debug = false )
	{
		////////////////////////////////////////////////////////////////////////////////
		// SETUP
		////////////////////////////////////////////////////////////////////////////////
		$date_func_order = array(
			'year',
			'quarter',
			'month',
			'week',
			'dayofmonth',
			'hour',
			'minute',
		);
		$grouping_aliases = array(
			'day' => 'dayofmonth',
		);
		$default_num_groupings = array(
			'year' => -5,
			'quarter' => -4,
			'month' => -6,
			'week' => -10,
			'dayofmonth' => -30,
			'hour' => -48,
			'minute' => -60,
		);
		
		$q_grouping = '';
		$q_select = '';
		$groupby = '';
		$where = '';
		
		$table = $args[ 'table' ];
		$df = $args[ 'date_field' ];
		$grouping = !empty( $args[ 'grouping' ] ) ? $args['grouping'] : 'dayofmonth';
		$select = !empty( $args['select'] ) ? $args['select'] : array( 'Total' => 'count(*)' );
		$start_date = !empty( $args['start_date'] ) ? $args[ 'start_date' ] : time();
		$end_date = !empty( $args['end_date'] ) ? $args[ 'end_date' ] : '';
		$group_every = !empty( $args['group_every'] ) ? $args[ 'group_every' ] : '';
		$where = !empty( $args['where'] ) ? $args[ 'where' ] : '';
		
		////////////////////////////////////////////////////////////////////////////////
		// CHECK INPUT
		////////////////////////////////////////////////////////////////////////////////
		
		if( !in_array( $grouping, $date_func_order ) )
		{
			if( in_array( $grouping, array_keys( $grouping_aliases ) ) )
				$grouping = $grouping_aliases[ $grouping ];
			else
			{
				trigger_error( 'Grouping "'.$grouping.'" is not a recognized value. Valid groupings: '.join( array_merge( array_keys( $grouping_aliases ), $date_func_order ), ', ') );
				return false;
			}
		}
		
		// make sure to determine num_groupings AFTER resolving aliases
		$num_groupings = isset( $args['num_groupings'] ) ? $args[ 'num_groupings' ] : $default_num_groupings[ $grouping ];
		
		////////////////////////////////////////////////////////////////////////////////
		// BUILD QUERY
		////////////////////////////////////////////////////////////////////////////////
		
		if( !empty( $args[ 'summary' ] ) )
		{
			$q_groups = "$grouping($df), ";
		}
		else
		{
			foreach( $date_func_order AS $g )
			{
				if( $g == 'week' )
					$tmp = "$g($df,1)";
				else
					$tmp = "$g($df)";
				
				if( $g == $grouping AND !empty( $group_every ) )
				{
					$tmp = "FLOOR( $tmp / $group_every )";
				}
				
				$q_select .= "\t$tmp $g, \n";
				$q_grouping .= "$tmp, ";
				if( $g == $grouping )
					break;
			}
		}
		
		if( is_array( $select ) )
		{
			$i = 1;
			foreach( $select AS $select_part )
			{
				$q_select .= "\t$select_part AS c$i, \n";
				$i++;
			}
		}
		else
		{
			$q_select .= "\t$select AS c1, \n";
			$select = array( 'Total' => $select );
		}
		
		if( !empty( $where ) OR !empty( $num_groupings ) )
		{
			$q_where = '';
			if( !empty( $start_date ) AND !empty( $end_date ) )
			{
				if( $start_date > $end_date )
				{
					$tmp = $end_date;
					$end_date = $start_date;
					$start_date = $tmp;
				}
				$q_where .= $df.' >= "'.ut_to_mysql($start_date).'" AND '.$df.' <= "'.ut_to_mysql($end_date).'" ';
			}
			else if( !empty( $num_groupings ) )
			{
				if( $num_groupings > 0 )
				{
					$end_date = step_date( $start_date, $grouping, $num_groupings );
				}
				else
				{
					$end_date = $start_date;
					$start_date = step_date( $end_date, $grouping, $num_groupings );
				}
				$q_where .= $df.' >= "'.ut_to_mysql($start_date).'" AND '.$df.' <= "'.ut_to_mysql($end_date).'" ';
			}
			if( !empty( $where ) )
			{
				if( !empty( $q_where ) )
					$q_where .= ' AND ';
				$q_where .= '('.$where.')';
			}
			$q_where = "WHERE\n\t" . $q_where;
		}
		
		$q_select = "$q_select \tUNIX_TIMESTAMP(MAX($df)) AS max_ut, UNIX_TIMESTAMP(MIN($df)) AS min_ut ";
		$q_grouping = substr( $q_grouping, 0, -2 );
		
		
		$q = "
SELECT
$q_select
FROM
	$table
$q_where
GROUP BY
	$q_grouping
		";
		
		if( $debug ) echo $q."<br/><br/>";
		
		////////////////////////////////////////////////////////////////////////////////
		// RUN AND BUILD TABLE
		////////////////////////////////////////////////////////////////////////////////
		
		// this code is slightly insane.  the problem is that we are creating a multi-dimensional
		// array whose depth is dynamic based on the needs of the user.  so it might hurt your
		// brain.
		
		$data = array();
		$min_ut = NULL;
		$max_ut = NULL;
		$max_data = array();
		$min_data = array();
		$r = mysql_query( $q ) OR die( 'Unable to run date chart query: '.mysql_error() );
		while( $row = mysql_fetch_assoc( $r ) )
		{
			$t = array();
			foreach( $date_func_order AS $d )
			{
				$t[] = $row[ $d ];
				if( $d == $grouping ) break;
			}
			$t = array_reverse( $t );
			
			// run loop once for every SELECT measure
			for( $i = 1; $i <= count( $select ); $i++ )
			{
				// use the 'c' prepend to make sure the indices are strings
				$tmp = $row[ "c$i" ];
				if( empty( $max_data[ "c$i" ] ) OR $tmp > $max_data[ "c$i" ] )
					$max_data[ "c$i" ] = $tmp;
				if( empty( $min_data[ "c$i" ] ) OR $tmp < $min_data[ "c$i" ] )
					$min_data[ "c$i" ] = $tmp;
				foreach( $t AS $k )
				{
					$arr = array();
					// prepend a 'k' to keep these indices as strings.  array_merge does not like
					// integers.
					$arr[ "k$k" ] = $tmp;
					$tmp = $arr;
				}
				if( !empty( $data[ $i ] ) )
					$data[ $i ] = array_merge_recursive( $data[ $i ], $arr );
				else
					$data[ $i ] = $arr;
			}
			// get max and min
			if( $min_ut == NULL OR $row[ 'min_ut' ] < $min_ut )
				$min_ut = $row[ 'min_ut' ];
			if( $max_ut == NULL OR $row[ 'max_ut' ] > $max_ut )
				$max_ut = $row[ 'max_ut' ];
		}
		
		$columns = array('');
		$i = 1;
		foreach( $select AS $name => $s )
		{
			$data_row[ $i++ ] = array( $name );
		}
		if( !empty( $num_groupings ) )
		{
			$min_ut = $start_date;
			$max_ut = $end_date;
		}
		if( !empty( $group_every ) )
		{
			//echo date('r',$min_ut);
			$remainder = get_date_part( $min_ut, $grouping ) % $group_every;
			//echo " -- $remainder ";
			if( $remainder != 0 )
			{
				$min_ut = step_date( $min_ut, $grouping, -$remainder );
			}
			//echo "  ----   ".date('r',$min_ut)."<br/><br/>";
		}
		for( $cur_time = $min_ut; $cur_time <= $max_ut; $cur_time = step_date( $cur_time, $grouping, !empty( $group_every ) ? $group_every : 1 ) )
		{
			$columns[] = prettify_date( $cur_time, $grouping );
			for( $i = 1; $i <= count( $select ); $i++ )
			{
				$val = $data[ $i ];
				foreach( $date_func_order AS $g )
				{
					$dp = get_date_part( $cur_time, $g );
					if( $g == $grouping  AND !empty( $group_every ) )
					{
						//echo "$dp / $group_every = ";
						$dp = floor($dp / $group_every);
						//echo $dp.'<br/>';
					}
					if( !empty( $val[ 'k'.$dp ] ) )
						$val = $val[ 'k'.$dp ];
					else
						$val = 0;
					if( $g == $grouping )
						break;
				}
				$data_row[ $i ][] = !empty( $val ) ? $val : 0;
			}
		}

		$retval = array( $columns );
		foreach( $data_row AS $row )
			$retval[] = $row;
		
		if( $debug )
		{
			debug_display_table( $retval );
		}
		return $retval;
	}

	function combine_tables( $a, $b )
	{
		if( !array( $a ) OR !array( $b ) )
		{
			trigger_error('Tables not passed.');
			return false;
		}
		
		$a_header = $a[0];
		$b_header = $b[0];
		
		if( empty( $a_header ) OR empty( $b_header ) )
		{
			trigger_error('no table headers');
			return false;
		}
		
		if( count( $a_header ) != count( $b_header ) )
		{
			trigger_error( "columns don't match length" );
			return false;
		}
		for( $i = 0; $i < count( $a_header ); $i++ )
		{
			if( $a_header[ $i ] != $b_header[ $i ] )
			{
				trigger_error( "columns have different headers" );
				return false;
			}
		}
		for( $i = 1; $i < count($b); $i++ )
		{
			$a[] = $b[ $i ];
		}
		return $a;
	}
	
	// an attempt at some automatic scaling on tables to make all rows at somewhat equal scales.
	function rescale_table( $t, $measure = 'max' )
	{
		if( count( $t ) <= 2 )
		{
			trigger_error('table with one row does not need to be rescaled.');
			return false;
		}
		
		// gather major metrics for the rows
		$stats = array();
		for( $i = 1; $i < count( $t ); $i++ )
		{
			$max = NULL;
			$min = NULL;
			$row = $t[ $i ];
			$row_wo_category = array_slice( $row, 1 );
			
			$sorted = $row_wo_category;
			sort( $sorted );
			
			// caclulate and store the various measures
			$stats[ $i ] = array(
				'max' => max( $row_wo_category ),
				'min' => min( $row_wo_category ),
				'avg' => array_sum( $row_wo_category ) / count( $row_wo_category ),
				'range' => max( $row_wo_category ) - min( $row_wo_category ),
				'median' => $sorted[ ceil( count( $sorted ) / 2 ) ],
			);
		}
		
		// find the row with the highest of the chosen measure.
		// this will be the comparison base.
		$comp_base_index = 1;
		for( $i = 2; $i < count( $t ); $i++ )
			if( $stats[ $i ][ $measure ] > $stats[ $comp_base_index ][ $measure ] )
				$comp_base_index = $i;
		
		// now, go through the rest of the rows and find the power of ten scale that gets the chosen measure as close to
		// the higheset measure as possible.
		for( $i = 1; $i < count( $t ); $i++ )
		{
			if( $i != $comp_base_index )  // ignore the comparison base index
			{
				// increase the scale factor until we see the difference increasing again.
				$scale = 1;
				$diff = abs( $stats[ $comp_base_index ][ $measure ] - $stats[ $i ][ $measure ] );
				do
				{
					$scale *= 10;
					$prev_diff = $diff;
					$diff = abs( $stats[ $comp_base_index ][ $measure ] - ( $stats[ $i ][ $measure ] * $scale ) );
				} while( $diff < $prev_diff );
				$scale /= 10;
				
				// now, iterate through this row applying the scale value (as well as adding how much the row is being
				// scaled to the category column)
				$row =& $t[ $i ];
				$row[ 0 ] .= ' ('.round($scale).'x)';
				for( $j = 1; $j < count( $row ); $j++ )
				{
					$row[ $j ] = ($row[ $j ] * $scale);
				}
			}
		}
		return $t;
	}
	
	function debug_display_table( $t )
	{
		echo '<table border="1" cellspacing="0" cellpadding="4">';
		for( $i = 0; $i < count( $t ); $i++ )
		{
			echo '<tr>';
			for( $j = 0; $j < count( $t[ 1 ] ); $j++ )
			{
				echo '<td>';
				echo $t[ $i ][ $j ];
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table><br/>';
	}
	
	/**
     *  Merges two arrays of any dimension
     *
     *  This is the process' core!
     *  Here each array is merged with the current resulting one
     *
     *  @access private
     *  @author Chema Barcala Calveiro <shemari75@mixmail.com>
     *  @param array $array  Resulting array - passed by reference
     *  @param array $array_i Array to be merged - passed by reference
     */

   function array_merge_2(&$array, &$array_i) {
       // For each element of the array (key => value):
       foreach ($array_i as $k => $v) {
           // If the value itself is an array, the process repeats recursively:
           if (is_array($v)) {
               if (!isset($array[$k])) {
                   $array[$k] = array();
               }
               array_merge_2($array[$k], $v);

           // Else, the value is assigned to the current element of the resulting array:
           } else {
               if (isset($array[$k]) && is_array($array[$k])) {
                   $array[$k][0] = $v;
               } else {
                   if (isset($array) && !is_array($array)) {
                       $temp = $array;
                       $array = array();
                       $array[0] = $temp;
                   }
                   $array[$k] = $v;
               }
           }
       }
   }

   /**
     *  Merges any number of arrays of any dimension
     *
     *  The arrays to be merged are passed as arguments to the function,
     *  which uses an external function (array_merge_2) to merge each of them
     *  with the resulting one as it's being constructed
     *
     *  @access public
     *  @author Chema Barcala Calveiro <shemari75@mixmail.com>
     *  @return array Resulting array, once all have been merged
     */

   function array_merge_n() {
       // Initialization of the resulting array:
       $array = array();

       // Arrays to be merged (function's arguments):
       $arrays =& func_get_args();

       // Merging of each array with the resulting one:
       foreach ($arrays as $array_i) {
           if (is_array($array_i)) {
               array_merge_2($array, $array_i);
           }
       }

       return $array;
   }

?>
