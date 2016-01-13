<?php
/**
 * A collection of generally useful functions
 *
 * Includes string, array, object, and date/time manipulation functions.
 *
 * @package carl_util
 * @subpackage basic
 */

if( !defined( '__INC_DAVE_MISC' ) )
{
	define( '__INC_DAVE_MISC', true );
	include_once('date_funcs.php');
	include_once('url_funcs.php');
	include_once('cleanup_funcs.php');
	include_once('html_funcs.php');
	include_once('carl_util/tidy/tidy.php');
	
	/**
	 * Use both keys and values to produce imploded string
	 *
	 * Useful for building query strings
	 *
	 * Typical usage:
	 * $foo = array('key1'=>'val1','key2'=>'val2');
	 * echo implode_with_keys('&amp;',$foo);
	 * 
	 * Output:
	 * key1=val1&amp;key2=val2
	 *
	 * @param string $glue
	 * @param array $array
	 * @return string $imploded_value
	 */
	function implode_with_keys($glue, $array)
	{
        	$output = array();
        	foreach( array_keys($array) as $key )
        	{
                	if ($array[$key])
                        	$output[] = $key . "=" . $array[$key];
        	}
        	return implode($glue, $output);
	}
	
	/**
	 * Get a request variable if it exists.
	 *
	 * @param $key
	 * @return request value for that key, if provided in request. Otherwise will return an empty string.
	 */
	function localize( $var ) // {{{
	{
		return isset( $_REQUEST[$var] ) ? $_REQUEST[ $var ] : '';
	} // }}}
	
// Just to be safe ;o)
if (!defined("ENT_COMPAT")) define("ENT_COMPAT", 2);
if (!defined("ENT_NOQUOTES")) define("ENT_NOQUOTES", 0);
if (!defined("ENT_QUOTES")) define("ENT_QUOTES", 3);

	/**
	 *	Log a line to a file
	 *
	 *	If not passed a destination, this function will try to log to /tmp/dlog-hostname
	 *
	 *	@param string $msg The line to log
	 *	@param string $dest destination file to log to
	 *	@return boolean success
	 */
	function dlog( $msg, $dest = '' )
	{
		if( empty( $dest ) )
			$dest = '/tmp/dlog-'.$_SERVER['HTTP_HOST'];
		return error_log( $msg."\n", 3, $dest );
	}

	function quote_walk( &$val, $key )
	{
		$val = '"'.addslashes($val).'"';
	}

	function db_prep_walk( &$val, $key )
	{
		$val = '"'.addslashes($val).'"';
	}

	function add_backticks_array(&$val)
	{
		$value = ($z = strstr($val, " ")) ? substr($val, 0, strpos($val, " ")) : $val;
		$value = explode('.', $value);
		foreach ($value as $v)
		{
			$v2[] = ($v != '*') ? '`'.$v.'`' : $v;
		}
		$val = implode (".", $v2) . $z;
	}
	
	function add_backticks($val)
	{
		if (!empty($val)) return ($val != "*") ? '`'.$val.'`' : $val;
		else return '';
	}
	
	/**
	 * Provides a nice, human-readable version of a size given in bytes
	 * @param integer $size
	 * @param integer $decimal how many decimal places to provide
	 * @return string English-formatted filesize
	 */
	function format_bytes_as_human_readable( $size, $decimal = 2 )
	{
		if($size == 0) {
		   return("0 Bytes");
	   }
	   $filesizename = array(" bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
	   return round($size/pow(1024, ($i = floor(log($size, 1024)))), $decimal) . $filesizename[$i];
	
	}
	
	/**
	 * Provides a php size setting (like for max upload) in bytes
	 *
	 * php size settings are in g (gigabytes), m (megabytes), or k (kilobytes).
	 * This function makes it easier to compare filesizes to php settings by transforming
	 * a requested setting to simple byte counts
	 *
	 * @param string $setting_name
	 * @return integer $size in bytes
	 */
	function get_php_size_setting_as_bytes($setting_name)
	{
		$size = trim(ini_get($setting_name));
		$letter = strtolower($size{strlen($size)-1});
		switch($letter)
		{
			case 'g':
				$size *= 1024;
			case 'm':
				$size *= 1024;
			case 'k':
				$size *= 1024;
		}
		return $size;
	}
	
	/**
	 * Provides a nice, human-readable version of a duration given in seconds
	 *
	 * takes duration as 67.67s and returns 1 minute 18 seconds
	 * this is apparently the standard(?) format for the exif duration field
	 *
	 * Note that this does not work for negative time; convert inputs to positive values before using this function.
	 *
	 * @param $duration seconds
	 */
	function format_seconds_as_human_readable($duration, $format = 'words')
	{
		// allow trailing "s" as this is a common format in exif
		$duration = trim($duration);
		$duration = str_replace('s','',$duration);
		$seconds = round($duration);
		
		$days = floor($seconds/60/60/24);
		$hours = $seconds/60/60%24;
		$mins = $seconds/60%60;
		$secs = $seconds%60;
		
		$ret_array = array();
		
		$letter_format = 'letters' == $format ? true : false;
		
		if(!empty($days))
		{
			if($letter_format) $word = 'd';
			elseif($days == 1) $word = ' day';
			else $word = ' days';
			$ret_array[] = $days.$word;
		}
		if(!empty($hours))
		{
			if($letter_format) $word = 'h';
			elseif($hours == 1) $word = ' hour';
			else $word = ' hours';
			$ret_array[] = $hours.$word;
		}
		if(!empty($mins))
		{
			if($letter_format) $word = 'm';
			elseif($mins == 1) $word = ' minute';
			else $word = ' minutes';
			$ret_array[] = $mins.$word;
		}
		if(!empty($secs) || $duration < 1)
		{
			if(!empty($duration) && $duration < 1) $ret_array[] = $letter_format ? '&lt;1s' : 'less than 1 second';
			elseif($letter_format) $ret_array[] = $secs.'s';
			elseif($secs == 1) $ret_array[] = $secs.' second';
			else $ret_array[] = $secs.' seconds';
		}
		
		return implode(' ',$ret_array);
	}
	
	function array_diff_assoc_recursive($array1, $array2)
	{
		foreach($array1 as $key => $value)
		{
			if(is_array($value))
			{
				if(!isset($array2[$key]) || !is_array($array2[$key])) // added !isset to reduce warnings
				{
					//echo 'hitit';
					//echo $array2[$key];
					$difference[$key] = $value;
				}
				else
				{
					$new_diff = array_diff_assoc_recursive($value, $array2[$key]);
					if($new_diff != FALSE)
					{
						$difference[$key] = $new_diff;
					}
				}
			}
			elseif(!isset($array2[$key]) || $array2[$key] != $value)
			{
				$difference[$key] = $value;
			}
		}
		return !isset($difference) ? 0 : $difference;
	}
	
	function array_merge_recursive2($paArray1, $paArray2)
	{
		if (!is_array($paArray1) or !is_array($paArray2)) { return $paArray2; }
		foreach ($paArray2 AS $sKey2 => $sValue2)
   		{
       		$paArray1[$sKey2] = array_merge_recursive2(@$paArray1[$sKey2], $sValue2);
   		}
   		return $paArray1;
	}
	/**
	 * Clone an object.
	 *
	 * This function allows for compatibility with both php4 and php5.
	 * It should be used rather than using the = operator when object duplication is desired
	 *
	 * Note that there is some shared code between carl_clone and carl_clone_if_needed.
	 * This duplication exists because profiling indicated that abstracting out
	 * the determination of current version into a separate function caused a 2x - 4x slowdown
	 * in execution time -- not insignificant in a script where many clones occur.
	 *
	 * @author Matt Ryan
	 * @link http://php.net/language.oop5.cloning
	 * @param object $object
	 * @return object $cloned_object
	 */
	function carl_clone($object)
	{
		static $version_checked = false;
		static $version_supports_cloning;
		
		if(!$version_checked)
		{
			if(version_compare(phpversion(), '5.0') < 0) // php version 4
			{
				$version_supports_cloning = false;
			}
			else // php version 5
			{
				$version_supports_cloning = true;
			}
			$version_checked = true;
		}
		if($version_supports_cloning)
		{
			return clone($object);
		}
		else
		{
			/* The PEAR compat library does this.
			   I'm not sure what benefits this method offers,
			   since the object should already be a copy at this point in php4. */
			//$object = unserialize(serialize($object));
			
			// If there is a __clone method call it on the copied object
			// ...crashes php 5 - nwhite
			//if (method_exists($object, '__clone')) {
			//	//clone $object;
			//	//$object->__clone();
			//}
			return $object;
		}
	}
	/**
	 * Clone an object if the current version does not automatically duplicate objects when they passed to functions.
	 *
	 * This function allows for compatibility with both php4 and php5.
	 * It should be used either inside or outside a function when passing by reference is NOT desired.
	 * Note that the results of this function should be assigned by reference for least work by php
	 *
	 * Note that there is some shared code between carl_clone and carl_clone_if_needed.
	 * This duplication exists because profiling indicated that abstracting out
	 * the determination of current version into a separate function caused a 2x - 4x slowdown
	 * in execution time -- not insignificant in a script where many clones occur.
	 *
	 * @author Matt Ryan
	 * @link http://php.net/language.oop5.cloning
	 * @param object $object
	 * @return object $possibly_cloned_object
	 */
	function carl_clone_if_needed(&$object)
	{
		static $version_checked = false;
		static $version_supports_cloning;
		
		if(!$version_checked)
		{
			if(version_compare(phpversion(), '5.0') < 0) // php version 4
			{
				$version_supports_cloning = false;
			}
			else // php version 5
			{
				$version_supports_cloning = true;
			}
			$version_checked = true;
		}
		if($version_supports_cloning)
		{
			return clone($object);
		}
		else
		{
			return $object;
		}
	}
	
	/**
	 * Returns true if the version of php is php5
	 * @author Nathan White
	 */
	function carl_is_php5()
	{
		static $is_php5;
		if (!isset($is_php5))
		{
			if(version_compare(phpversion(), '5.0') < 0) $is_php5 = false;
			else $is_php5 = true;
		}
		return $is_php5;
	}
	
	/**
	 * Convert a MySQL TIMESTAMP field to a UNIX timestamp.
	 * @param string
	 * @return int or false if conversion fails
	 * @see carl_mktime
	 * @author Eric Naeseth
	 */
	function mysql_timestamp_to_unix($timestamp)
	{
		// MySQL 4.1 backwards compatible fix - MySQL 4.1 returns timestamps in datetime format
		// only execute if $dt is a numeric timestamp, otherwise try prettify_mysql_datetime
		if (is_numeric($timestamp)) {
			$year = substr( $dt, 0, 4 );
			$month = substr( $dt, 4, 2 );
			$day = substr( $dt, 6, 2 );
			$hour = substr( $dt, 8, 2);
			$min = substr( $dt, 10, 2);
			$sec = substr( $dt, 12, 2);
			
			return ((int) $year && (int) $month && (int) $day)
				? carl_mktime($hour, $min, $sec, $month, $day, $year)
				: false;
		} else {
			return mysql_datetime_to_unix($timestamp);
		}
	}
	
	/**
	 * Convert a MySQL DATETIME field to a UNIX timestamp.
	 * @param string
	 * @return int or false if conversion fails
	 * @see carl_mktime
	 * @author Eric Naeseth
	 */
	function mysql_datetime_to_unix($dt)
	{
		$year = $month = $day = $hour = $minute = $second = '';
		// y/m/d: if zero, subsequent values must be empty
		if($year = substr( $dt, 0, 4 ))
		{
			if($month = substr( $dt, 5, 2 ))
			{
				if($day = substr( $dt, 8, 2 ))
				{
					// h/m/s: can be zero with subsequent items being nonzero integers
					$hour = substr( $dt, 11, 2);
					$minute = substr( $dt, 14, 2);
					$second = substr( $dt, 17, 2);
				}
			}
		}
		
		// check for all 0s (an empty date)
		return ((int) $year && (int) $month && (int) $day)
			? carl_mktime($hour, $minute, $second, $month, $day, $year)
			: false;
	}
			
	/**
	 * Make a good looking date from a mysql timestamp
	 * @param string $dt format of mysql timestamp
	 * @param string $format the format string according to {@link http://us2.php.net/manual/en/function.date.php php's date function}
	 * @return string The formatted date or the empty string on conversion failure
	 */ 
	function prettify_mysql_timestamp( $dt , $format = 'M jS, Y') // {{{
	{
		return (false !== ($time = mysql_timestamp_to_unix($dt)))
			? carl_date($format, $time)
			: '';
	} // }}}
	/**
	 * Make a good looking date from a mysql datetime
	 * @param string $dt format of mysql datetime
	 * @param string $format the format string according to {@link http://us2.php.net/manual/en/function.date.php php's date function}
	 * @return string The formatted date or the empty string on conversion failure
	 */ 
	function prettify_mysql_datetime( $dt , $format = 'M jS, Y' ) // {{{
	{
		return (false !== ($time = mysql_datetime_to_unix($dt)))
			? carl_date($format, $time)
			: '';
	} // }}}
	
	/**
	 * Takes a typical field name or array key (e.g. all lower case, underscores) and makes it look better by replacing underscores with spaces and capitalizing the first letters of words.
	 *
	 * @param string $s
	 * @return string $pretty_string
	 */
	function prettify_string( $s ) // {{{
	{
		// If the string contains HTML, only prettify the parts outside of tags.
		if (preg_match('/(<[^>]+>)/', $s))
		{
			$parts = preg_split('/(<[^>]+>)/', $s, null, PREG_SPLIT_DELIM_CAPTURE);
			foreach($parts as $key => $part)
			{
				if (!preg_match('/(<[^>]+>)/', $part))
					$parts[$key] = prettify_string($part);
			}
			return implode('', $parts);			
		}
		$parts = explode( '_', $s );
		foreach( $parts AS $part )
			$new_parts[] = strtoupper( substr( $part, 0, 1 ) ).substr( $part, 1 );
		return implode( ' ', $new_parts );
	} // }}}

	/**
	 * Recursively prettify all values of an array using prettify_string
	 * @author Matt Ryan
	 * @date 2006-05-18
	 * @param array $array
	 * @return array $prettified_array
	 */
	function prettify_array( $array )
	{
		foreach($array as $k=>$v)
		{
			if(is_array($v))
			{
				$array[$k] = prettify_array($v);
			}
			else
			{
				$array[$k] = prettify_string($v);
			}
		}
		return $array;
	}
	function is_mixed_case( $str )
	{
		// quick check to see if the string is equal to it's lower case self or upper cased self
		if( ( $str == strtoupper( $str ) ) || ( $str == strtolower( $str ) ) )
			return false;
		else
			return true;
	}

	function securest_available_protocol()
	{
		return (HTTPS_AVAILABLE) ? "https" : "http";
	}
	
	/**
	 * Windows behaves differently from other systems in a variety of ways
	 * This function wraps up the logic to determine if we are running under Windows
	 *
	 * NOTE: The applications in the Reason/carl_util package do not yet run under Windows --
	 * this function may allow us to make that happen, but do not assume that everyhing is
	 * using it yet.
	 *
	 * @return boolean
	 */
	function server_is_windows()
	{
		static $is_windows = false;
		static $tested = false;
		if(!$tested)
		{
			if(strtoupper(substr(PHP_OS,0,3) == 'WIN') )
				$is_windows = true;
			$tested = true;
		}
		return $is_windows;
	}
	
	/**
	 * strtolower replacement that uses mb_strtolower where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string - defaults to UTF-8, pass a null value to use the current mb_internal_encoding
	 */
	function carl_strtolower($str, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strtolower') ? mb_strtolower($str, $encoding) : strtolower($str);
	}

	/**
	 * strtoupper replacement that uses mb_strtoupper where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string - defaults to UTF-8, pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strtoupper($str, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strtoupper') ? mb_strtoupper($str, $encoding) : strtoupper($str);
	}

	/**
	 * substr replacement that uses mb_substr where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string; pass a null value to use the current mb_internal_encoding
	 */		
	function carl_substr($str, $start, $length = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_substr') ? mb_substr($str, $start, ($length) ? $length : mb_strlen($str), $encoding) : substr($str, $start, $length);
	}

	/**
	 * strlen replacement that uses mb_strlen where possible
	 *
	 * @param string str
	 * @param string encoding the encoding of the string; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strlen($str, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strlen') ? mb_strlen($str, $encoding) : strlen($str);
	}
	
	/**
	 * strpos replacement that uses mb_strpos where possible
	 *
	 * @param string haystack to search
	 * @param string needle to find
	 * @param string offset the search offset
	 * @param encoding the encoding of the strings; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strpos($haystack, $needle, $offset = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strpos') ? mb_strpos($haystack, $needle, $offset, $encoding) : strpos($haystack, $needle, $offset);
	}

	/**
	 * stripos replacement that uses mb_stripos where possible
	 *
	 * @param string haystack to search
	 * @param string needle to find
	 * @param string offset the search offset
	 * @param encoding the encoding of the strings; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_stripos($haystack, $needle, $offset = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_stripos') ? mb_stripos($haystack, $needle, $offset, $encoding) : stripos($haystack, $needle, $offset);
	}
	
	/**
	 * strrpos replacement that uses mb_strrpos where possible
	 *
	 * @param string haystack to search
	 * @param string needle to find
	 * @param string offset the search offset
	 * @param encoding the encoding of the strings; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strrpos($haystack, $needle, $offset = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strrpos') ? mb_strrpos($haystack, $needle, $offset, $encoding) : strrpos($haystack, $needle, $offset);
	}
	
	/**
	 * strripos replacement that uses mb_strripos where possible
	 *
	 * @param string haystack to search
	 * @param string needle to find
	 * @param string offset the search offset
	 * @param encoding the encoding of the strings; pass a null value to use the current mb_internal_encoding
	 */	
	function carl_strripos($haystack, $needle, $offset = NULL, $encoding = 'UTF-8')
	{
		if(!$encoding) $encoding = function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8';
		return function_exists('mb_strripos') ? mb_strripos($haystack, $needle, $offset, $encoding) : strripos($haystack, $needle, $offset);
	}
	
	/**
	 * Return unicode char by its code
	 *
	 * @param int $u
	 * @return char
	 */
	function carl_unichr($u)
	{
		if(function_exists('mb_convert_encoding'))
		{
			return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
		}
		else
		{
			if ($u <= 0x7F)
				return chr($u);
			else if ($u <= 0x7FF)
				return chr(0xC0 | $u >> 6) . chr(0x80 | $u & 0x3F);
			else if ($u <= 0xFFFF)
				return chr(0xE0 | $u >> 12) . chr(0x80 | $u >> 6 & 0x3F) . chr(0x80 | $u & 0x3F);
			else if ($u <= 0x10FFFF)
				return chr(0xF0 | $u >> 18) . chr(0x80 | $u >> 12 & 0x3F) . chr(0x80 | $u >> 6 & 0x3F) . chr(0x80 | $u & 0x3F);
			else
				return false;
		}
	}
	
	/**
	 * This is a replacement for referencing $_REQUEST directly, and gives us a consistent result that includes just $_GET and $_POST.
	 *
	 * It should be used instead of $_REQUEST, as the makeup of $_REQUEST depends upon the PHP version and settings.
	 *
	 * If $_POST and $_GET are set for a named item, $_POST is preferred.
	 *
	 * @param boolean run_conditional_stripslashes - defaults to true ... makes sure stripslashes gets run if magic_quotes is on.
	 * @author Nathan White
	 * @return array
	 */
	function carl_get_request( $run_conditional_stripslashes = true )
	{
		$merged = array_merge_recursive2( (array) $_GET, (array) $_POST);
		return ($run_conditional_stripslashes) ? conditional_stripslashes($merged) : $merged;
	}
	
	/**
	 * A standardized function for counting the number of characters in a string that might 
	 * contain HTML that we don't want to include in our count. 
	 *
	 * @author Nick Jones
	 * @param string $text - the text whose characters we want to count
	 * @return int the number of characters in the string
	 */
	function carl_util_count_html_text_characters($text)
	{
	    $tidied_text = tidy($text);
	    return carl_strlen(html_entity_decode(strip_tags($tidied_text),ENT_QUOTES,'UTF-8'),'UTF-8');;
	}
	
	/**
	 * Returns the path components after some directory in the path.
	 *
	 * @author Nathan White
	 *
	 * @param string full_path - absolute path
	 * @param string suffix - extension to strip
	 * @param string dir - some directory
	 *
	 * @return string
	 */
	function carl_basename( $full_path, $suffix, $dir )
	{
		$strlength = carl_strlen($dir);
		$strpos = carl_strpos($full_path, $dir);
		if (is_numeric($strpos)) // found the string
		{
			$partial_path = carl_substr($full_path, $strpos + $strlength);
			$filebasename = basename($partial_path, $suffix);
			$dirname = dirname($partial_path);
			return $dirname . '/' . $filebasename;			
		}
		else
		{
			trigger_error('The directory ' . $dir . ' was not found in the full path string ' . $full_path . ' - returning just the file basename');
			return basename($full_path, $suffix);
		}
	}
	
	if(!function_exists('http_response_code'))
	{
		function http_response_code()
		{
			static $code_cache;
			if(!isset($code_cache))
			{
				if(isset($_SERVER['REDIRECT_STATUS']))
					$code_cache = (integer) $_SERVER['REDIRECT_STATUS'];
				else
					$code_cache = 200;
			}
			$passed_code = (integer) @func_get_arg(0);
			if(!empty($passed_code))
			{
				$message = get_message_for_http_status($passed_code);
				if(empty($message))
				{
					trigger_error('Unrecognized http response code '.$passed_code);
					return $code_cache;
				}
				header('HTTP/1.1 '.$passed_code.' '.$message);
				$code_cache = $passed_code;
			}
			return $code_cache;
		}
	}
	
	function get_message_for_http_status($status_code)
	{
		$codes = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			103 => 'Checkpoint',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			208 => 'Already Reported',
			226 => 'IM Used',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect',
			308 => 'Resume Incomplete',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			425 => 'Unordered Collection',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			508 => 'Loop Detected',
			509 => 'Bandwidth Limit Exceeded',
			510 => 'Not Extended',
			511 => 'Network Authentication Required',
		);
		if(isset($codes[$status_code]))
			return $codes[$status_code];
		trigger_error('Unrecognized http response code '.$status_code);
		return false;
	}
	
	/**
	 * An exec replacement that supports an optional timeout parameter.
	 *
	 * - On Windows the timeout is ignored.
	 * - Exit status appears to be unreliable. Don't use this if you depend on it.
	 */
	function exec_with_timeout($cmd, $timeout = NULL, &$output = NULL, &$exit_status = NULL)
	{
		if (!server_is_windows())
		{
			$exit_status = proc_with_timeout($cmd, null, $stdout, $stderr, $timeout);
			if (is_array($output))
			{
				$output = array_merge($output, preg_split("/\r\n|\n|\r/", $stdout));
			}
		}
		else exec($cmd, $output, $exit_status);
	}

	/**
	 * A proc_open based function that appears to work and lets me set a timeout.
	 *
	 * In my testing it seems like the exitcode is not properly returned on some systems.
	 *
	 * Where possible, use stdout or a more definitive test (like file existence) to determine if things worked.
	 *
	 * from http://stackoverflow.com/questions/3407939/shell-exec-timeout-management-exec
	 */
	function proc_with_timeout($cmd, $stdin=null, &$stdout, &$stderr, $timeout=false)
	{
		$pipes = array();
		$process = proc_open(
			$cmd,
			array(array('pipe','r'),array('pipe','w'),array('pipe','w')),
			$pipes
		);
		$start = time();
		$stdout = '';
		$stderr = '';

		if(is_resource($process))
		{
			stream_set_blocking($pipes[0], 0);
			stream_set_blocking($pipes[1], 0);
			stream_set_blocking($pipes[2], 0);
			fwrite($pipes[0], $stdin);
			fclose($pipes[0]);
		}

		while(is_resource($process))
		{
			//echo ".";
			$stdout .= stream_get_contents($pipes[1]);
			$stderr .= stream_get_contents($pipes[2]);

			if($timeout !== false && time() - $start > $timeout)
			{
				proc_terminate($process, 9);
				return 1;
			}

			$status = proc_get_status($process);
			if(!$status['running'])
			{
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
				return $status['exitcode'];
			}

			usleep(100000);
		}
		return 1;
	}
	
	// checks the various settings to return an actual value for maximum file upload size
	function reason_get_actual_max_upload_size()
	{
		$sizes = array();
		$post_max_size = get_php_size_setting_as_bytes('post_max_size');
		$upload_max_filesize = get_php_size_setting_as_bytes('upload_max_filesize');
		$reason_max_media_upload = MEDIA_MAX_UPLOAD_FILESIZE_MEGS*1024*1024;

		if($post_max_size < $reason_max_media_upload || $upload_max_filesize < $reason_max_media_upload)
		{
			if($post_max_size < $upload_max_filesize)
			{
				trigger_error('post_max_size in php.ini is less than Reason setting MEDIA_MAX_UPLOAD_FILESIZE_MEGS; using post_max_size as max upload value');
				return $post_max_size;
			}
			else
			{
				trigger_error('upload_max_filesize in php.ini is less than Reason setting MEDIA_MAX_UPLOAD_FILESIZE_MEGS; using upload_max_filesize as max upload value');
				return $upload_max_filesize;
			}
		}
		else
		{
			return $reason_max_media_upload;
		}
	}
}
?>
