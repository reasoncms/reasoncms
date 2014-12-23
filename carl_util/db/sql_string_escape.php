<?php
/**
 * @package carl_util
 * @subpackage db
 */

/**
 * Escape a string for use in a SQL statement
 *
 * Internally uses mysql_real_escape_string(), so a DB connection should be established to ensure
 * proper encoding before calling this function.
 *
 * @param string $str
 * @return string
 */
function carl_util_sql_string_escape($str)
{
	return mysql_real_escape_string($str);
}