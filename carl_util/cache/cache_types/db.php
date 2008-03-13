<?php
include_once(CARL_UTIL_INC . 'cache/cache_types/default.php');
include_once(CARL_UTIL_INC . 'db/connectDB.php');
include_once(CARL_UTIL_INC . 'db/db_query.php');

/**
 *	Cache type that uses a mysql database - the constants OBJECT_CACHE_DB_CONN and OBJECT_CACHE_DB_TABLE must be setup.
 *
 *  The table must have the following columns:
 * 	- "id" primary key
 *	- "content" column (with plenty of storage)
 *  - "date_created" timestamp that updates with modifications.
 *
 *  Something like the following should get the right table:
 *
 * 	CREATE TABLE `_cache` (`id` VARCHAR( 32 ) NOT NULL, 
 *						   `content` TEXT NULL, 
 *						   `date_created` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *							PRIMARY KEY ( `id` ));
 *
 *	A drawback of this using mysql is that large caches may not work well, particularly if the amount of data mysql will accept
 *  in a single transcation is set at a low value. It is preferably to get memcached up and running or to use file system caching.
 *
 *	@package carl_util
 * 	@subpackage cache
 *  @author Nathan White
 */

class DbObjectCache extends DefaultObjectCache
{	
	var $error_msg = ' make sure that the OBJECT_CACHE_DB_CONN and OBJECT_CACHE_DB_TABLE constants are properly setup in object_cache_settings.php.';
	
	function &fetch()
	{
		$ret = false;
		$cache_id = $this->get_cache_id();
		$lifespan = $this->get_cache_lifespan();
		
		$this->_cache_conn(true);
		$qry = 'SELECT * from ' . OBJECT_CACHE_DB_TABLE . ' WHERE id="'.$cache_id.'"';
		$result = db_query($qry, 'Select query failed -' . $this->error_msg, false);
		$this->_cache_conn(false);
		
		if ($result)
		{
			$row = mysql_fetch_assoc($result);
			$created = get_unix_timestamp($row['date_created']);
			$ret = ((time() - $created) < $lifespan)
				   ? unserialize($row['content']) 
				   : false;
		}
		return $ret;
	}
	
	function set(&$object)
	{
		$cache_id = $this->get_cache_id();
		$content = mysql_real_escape_string(serialize($object));
		$qry = 'INSERT INTO ' . OBJECT_CACHE_DB_TABLE . ' (id, content) VALUES ("'.$cache_id.'", "'.$content.'") ON DUPLICATE KEY UPDATE content="'.$content.'";';
		$this->_cache_conn(true);
		$result = db_query($qry, 'Insert failed -' . $this->error_msg, false);
		$this->_cache_conn(false);
		return ($result);
	}
	
	function clear()
	{
		$cache_id = $this->get_cache_id();
		$qry = 'DELETE FROM ' . OBJECT_CACHE_DB_TABLE . ' WHERE id = "'.$cache_id.'" LIMIT 1';
		$this->_cache_conn(true);
		$result = db_query($qry, 'Delete failed -' . $this->error_msg, false);
		$this->_cache_conn(false);
		return ($result);
	}
	
	/**
	 * Make sure that the database and table exist
	 */
	function validate()
	{
		$db_conn_test = (defined('OBJECT_CACHE_DB_CONN') && OBJECT_CACHE_DB_CONN );
		$db_table_test = (defined('OBJECT_CACHE_DB_TABLE') && OBJECT_CACHE_DB_TABLE );
		if (!$db_conn_test) trigger_error('You need to populate OBJECT_CACHE_DB_CONN in object_cache_settings.php to use database caching');
		if (!$db_table_test) trigger_error('You need to populate OBJECT_CACHE_DB_TABLE in object_cache_settings.php to use database caching');
		return ($db_conn_test && $db_table_test);
	}
	
	/**
	 * Private function to handle database connections - only makes a new connection when needed
	 * @access private
	 */
	function _cache_conn($bool)
	{
		static $orig;
		static $curr;
		if (empty($orig)) $orig = $curr = get_current_db_connection_name();
		if ($bool && ($curr != OBJECT_CACHE_DB_CONN))
		{
			connectDB(OBJECT_CACHE_DB_CONN);
			$curr = OBJECT_CACHE_DB_CONN;
		}
		elseif (!$bool && ($curr != $orig))
		{
			connectDB($orig);
			$curr = $orig;
		}
	}
}
?>
