<?php
/**
 * PRint arrAY function
 *
 * nice debug function for uncovering the contents of arrays in an (x)HTML context
 *
 * @package carl_util
 * @subpackage dev
 * 
 * @todo remove old method of enforcing include_once
 */

/**
 * old method of enforcing include_once
 */
if( !defined( '__PRAY' ) )
{
	define( '__PRAY', true );
 
 	/**
 	 * pray - short for print_array
 	 *
 	 * Traverse the argument array/object recursively and print an ordered list.
 	 * Optionally show function names (in an object)
 	 *
 	 * NB: This is a *** HUGE SECURITY HOLE *** in the wrong hands!!
 	 * It prints all the variables it can find
 	 * If the argument is $GLOBALS this will include your database connection 
 	 * information, magic keys and session data!!
 	 *
 	 * This function should only be used when debugging or trying to understand the 
 	 * structure of some array-like data. It should not show up in production code.
 	 * 
 	 * Changelog:
 	 *
 	 * sometime earlier
 	 *
 	 * added $escape param.  will htmlentities() all values if set to true instead of displaying HTML
 	 * 13 Dec 02
 	 *
 	 * modified to show actual "empty" value.  If something doesn't have a value, it will display 0, NULL, false, or (none) depending on type.
 	 * added strict check for true.  shows 'true' if var is true and of type bool
 	 *
 	 * 28 Jan 03
 	 *
 	 * infinite recursion is bad. added level and max_depth vars
 	 *
 	 * @param mixed $data Whatever you want to inspect
 	 * @param boolean $escape true = htmlentities() all strings; false = output raw
 	 * @param boolean $functions Unclear purpose
 	 * @param integer $level How deep has pray() recursed so far?
 	 * @param integer $max_depth How deep should pray() recurse?
 	 *
 	 * @todo figure out what the $functions parameter is all about
 	 */
	function pray ($data, $escape=false, $functions=false, $level = 0, $max_depth = 5 )
	{
		if (is_object($data))
			$data = carl_clone($data);
		
		if( $level >= $max_depth )
			echo 'Max Depth reached.';
		else
		{
			if($functions!=0) { $sf=1; } else { $sf=0 ;}    // This kluge seemed necessary on one server.
			if (isset ($data))
			{
				if (is_array($data) || is_object($data)) 
				{
					if (count ($data))
					{
						echo '<ul>'."\n";
						while (list ($key,$value) = each ($data))
						{
							echo '<li>';
							$type=gettype($value);
							if ($type=="array" || $type == "object")
							{
								if($type == 'object')
								{
									$type = $type.' - '.get_class($value);
								}
								if( $escape )
								{
									$type = htmlentities( $type );
									$type = get_class( $value );
									$key = htmlentities( $key );
								}
								printf ("(%s)<strong>%s</strong>:\n",$type, $key);
								pray ($value,$escape,$sf,$level + 1);
							}
							elseif (preg_match ("/function/i", $type))
							{
								if ($sf) 
								{
									if( $escape )
									{
										$type = htmlentities( $type );
										$key = htmlentities( $key );
										$value = htmlentities( $value );
									}
									printf ("(%s) <strong>%s</strong>",$type, $key, $value);
									//    There doesn't seem to be anything traversable inside functions.
								}
							}
							else 
							{
								if (!$value)
								{
									// show actual non-value
									switch( gettype( $value ) )
									{
										case 'integer': case 'double':
											$value = '0';
											break;
										case 'boolean':
											$value = 'false';
											break;
										case 'NULL':
											$value = 'NULL';
											break;
										default:
											$value="(none)";
											break;
									}
								}
								// check for strict equivalance to true
								if( $value === true )
									$value = 'true';
								if( $escape )
								{
									$type = htmlentities( $type );
									$key = htmlentities( $key );
									$value = nl2br(htmlentities( $value ));
								}
								printf ("(%s) <strong>%s</strong> = %s",$type, $key, $value);
							}
							echo '</li>'."\n";
						}
						echo "</ul>\n";
					}
					else
					{
						echo '(empty)'."\n";
					}
				}
			}
		}
	}    // function

	// like pray, but returns a string with the info in it
	function spray ($data, $escape=false, $functions=false, $level = 0, $max_depth = 5 )
	{
		ob_start();
		pray( $data, $escape, $functions, $level, $max_depth );
		$sprayed = ob_get_contents();
		ob_end_clean();

		return $sprayed;
	}

	function sprint_r( $arr )
	{
		ob_start();
		print_r( $arr );
		$sprinted = ob_get_contents();
		ob_end_clean();

		return $sprinted;
	}
}
?>
