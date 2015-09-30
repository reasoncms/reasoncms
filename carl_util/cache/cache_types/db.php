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
 * @package carl_util
 * @subpackage cache
 * @author Nathan White
 * @todo support multiple databases
 */

class DbObjectCache extends DefaultObjectCache
{	
	private $db_table;
	private $db_conn;
	
	function &fetch()
	{
		$ret = false;
		$cache_id = $this->get_cache_id();
		$lifespan = $this->get_cache_lifespan();
		
		$this->_cache_conn(true);
		$qry = 'SELECT * from ' . $this->get_db_table() . ' WHERE id="'.$cache_id.'"';
		$result = db_query($qry, 'Select query failed.', false);
		$this->_cache_conn(false);
		if ($result)
		{
			$row = mysql_fetch_assoc($result);
			$created = get_unix_timestamp($row['date_created']);
			$ret = (($lifespan == -1) || ((time() - $created) < $lifespan))
				   ? unserialize($row['content']) 
				   : false;
		}
		return $ret;
	}
	
	function set(&$object)
	{
		$cache_id = $this->get_cache_id();
		$content = mysql_real_escape_string(serialize($object));
		$qry = 'INSERT INTO ' . $this->get_db_table() . ' (id, content) VALUES ("'.$cache_id.'", "'.$content.'") ON DUPLICATE KEY UPDATE content="'.$content.'", date_created="'.get_mysql_datetime().'";';
		$this->_cache_conn(true);
		$result = db_query($qry, 'Insert failed.', false);
		$this->_cache_conn(false);
		return ($result);
	}
	
	function clear()
	{
		$cache_id = $this->get_cache_id();
		$qry = 'DELETE FROM ' . $this->get_db_table() . ' WHERE id = "'.$cache_id.'" LIMIT 1';
		$this->_cache_conn(true);
		$result = db_query($qry, 'Delete failed.', false);
		$this->_cache_conn(false);
		return ($result);
	}
	
	/**
	 * Make sure that the database and table exist
	 */
	function validate()
	{
		if (!$this->get_db_conn() || !$this->get_db_table())
		{
			if (!$this->get_db_conn()) trigger_error('You need to set the instance param db_conn OR populate OBJECT_CACHE_DB_CONN in object_cache_settings.php to use database caching.');
			if (!$this->get_db_table()) trigger_error('You need to set the instance param db_table OR populate OBJECT_CACHE_DB_TABLE in object_cache_settings.php to use database caching.');
		}
		else return true;
	}
	
	/**
	 * The DB Cache class will accept these params:
	 *
	 * - db_conn
	 * - get_db_table
	 */
	function setup_params($params)
	{
		if (isset($params['db_conn'])) $this->db_conn = $params['db_conn'];
		if (isset($params['db_table'])) $this->db_table = $params['db_table'];
	}
	
	function get_db_conn()
	{
		if (isset($this->db_conn)) return $this->db_conn;
		elseif (defined("OBJECT_CACHE_DB_CONN")) return OBJECT_CACHE_DB_CONN;
		else return false;
	}
	
	function get_db_table()
	{
		if (isset($this->db_table)) return $this->db_table;
		elseif (defined("OBJECT_CACHE_DB_TABLE")) return OBJECT_CACHE_DB_TABLE;
		else return false;
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
		if ($bool && ($curr != $this->get_db_conn()))
		{
			connectDB($this->get_db_conn());
			$curr = $this->get_db_conn();
		}
		elseif (!$bool && ($curr != $orig))
		{
			connectDB($orig);
			$curr = $orig;
		}
	}
}
?>