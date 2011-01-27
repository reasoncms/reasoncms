<?php
	/**
	 *	A simple whole-page caching tool
	 *
	 *	First stab at an implementation of a Page Cache for the public site.
	 *	- Basic implementation stores a cached version of the page keyed by the MD5 of the URI.
	 *
	 *  In Reason, the canonical value of the current URI is retrieved by using the
	 *  get_current_uri() function.
	 *
	 * @package carl_util
	 * @subpackage cache
	 * @author Dave Hendler
	 */
	
	/** 
	 * require dependencies
	 */
	include_once('paths.php');
	include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );
	include_once( CARL_UTIL_INC . 'basic/cleanup_funcs.php' );
	
	/**
	 * Set up necessary constants
	 */
	define( 'CACHE_LOG_MSG_FETCH_OLD', 'fetch_old' );
	define( 'CACHE_LOG_MSG_STORE', 'store' );
	define( 'CACHE_LOG_MSG_STORE_DIFF', 'diff' );
	define( 'CACHE_LOG_MSG_STORE_NOT_DIFF', 'not diff' );
	define( 'CACHE_LOG_MSG_CLEAR', 'clear' );
	define( 'CACHE_LOG_MSG_CLEAR_ALL', 'clear all' );
	define( 'CACHE_LOG_MSG_HIT', 'hit' );
	define( 'CACHE_LOG_MSG_MISS', 'miss' );
	define( 'CACHE_LOG_MSG_MISS_AGE', 'age' );
	define( 'CACHE_LOG_MSG_MISS_NO_CACHE', 'no cache' );
	
	/*
	 *	A basic caching system
	 *
	 *	Basic implementation stores a cached version of the page keyed by the MD5 of the URI.  In Reason, the canonical
	 *	value of the current URI is retrieved by using the get_current_uri() function.
	 */
	class PageCache
	{
		// time to regen a page, in seconds
		var $timeout = 1800;
		// full path starts and ends with a / character
		var $dir = '/tmp/';
		var $instance = '';
		var $log_file = '';
		var $_logging = false;
		var $page_gen_time = 0;
		
		function PageCache() // {{{
		{
			if( !defined( 'PAGE_CACHE_LOG' ) )
				$this->log_file = '/tmp/page_cache_log';
			else
				$this->log_file = PAGE_CACHE_LOG;
		} // }}}
		public function fetch( $key ) // {{{
		{
			if( $this->is_cached( $key ) )
			{
				return $this->_do_fetch( $key );
			}
			else
			{
				return false;
			}
		} // }}}
		function fetch_old( $key ) // {{{
		{
			$this->_log( $key, CACHE_LOG_MSG_FETCH_OLD );
			return $this->_do_fetch( $key );
		} // }}}
		function _do_fetch( $key ) // {{{
		{
			$f = $this->_get_cache_file( $key );
			if( file_exists( $f ) )
			{
				$file = file( $f );
				return join( $file, "" );
			}
			else
			{
				return false;
			}
		} // }}}
		public function store( $key, $cache_val ) // {{{
		{
			$url = get_current_url();
			$tmpfile = $this->get_dir().uniqid('reason_cache_'.md5($url), true);
			$fp = fopen( $tmpfile, 'w' ) OR die( 'unable to open cache tmp' );
			fwrite( $fp, $cache_val, strlen( $cache_val ) ) OR die( 'unable to write to cache tmp' );
			fclose( $fp ) OR die( 'unable to close cache tmp' );
			if (file_exists($tmpfile))
			{
				rename( $tmpfile, $this->_get_cache_file( $key ) ) OR trigger_error( 'unable to rename cache file');
				// _log() does check the _logging variable, but check it again here so we don't needlessly run the diff
				if( $this->_logging )
				{
					$cmd = 'diff '.$tmpfile.' '.$this->_get_cache_file( $key );
				
					if(file_exists( $this->_get_cache_file( $key ) ) ){ 
						$diff = shell_exec( $cmd );
						}
					else { 
						$diff = true ; // if the cache file does not exist, difference is inherent.
						}
					
					// DH 5/6/2005
					// we're not using the diff stored in the table.  it's only taking up time and space right now.
					$this->_log( $key, CACHE_LOG_MSG_STORE, $diff ? CACHE_LOG_MSG_STORE_DIFF : CACHE_LOG_MSG_STORE_NOT_DIFF );
					//$this->_log( $key, CACHE_LOG_MSG_STORE, $diff ? CACHE_LOG_MSG_STORE_DIFF : CACHE_LOG_MSG_STORE_NOT_DIFF, $diff );
				}
			}
			else
			{
				trigger_error( 'unable to rename cache file ' . $tmpfile . ' - the file does not exist' );
			}	
		} // }}}
		
		/**
		 * @param dir string absolute path to cache directory (must be writable by web server, include trailing slash)
		 */
		public function set_cache_dir( $dir )
		{
			$this->dir = '/' . trim_slashes($dir) . '/';
		}
		
		public function get_cache_dir()
		{
			return $this->dir;
		}
		
		/**
		 * Allows extensions to utilize additional organizational schemes for cached files.
		 */
		protected function get_dir()
		{
			return $this->get_cache_dir();
		}
		
		function set_page_generation_time( $time )
		{
			$this->page_gen_time = $time;
		}
		public function clear( $key ) // {{{
		{
			$f = $this->_get_cache_file( $key );
			if( file_exists( $f ) )
				unlink( $f );
			$this->_log( $key, CACHE_LOG_MSG_CLEAR );
		} // }}}
		function clear_all() // {{{
		{
			$this->_log( '*', CACHE_LOG_MSG_CLEAR_ALL );
		} // }}}
		
		/**
		 * Determines a couple things about a page:
		 * 
		 * - if a page has a cache
		 * - if that cache is still valid (meaning, it has not timed out)
		 *
		 * @param string cache key
		 * @param boolean logging_enabled - defaults to true, will not call _log if false
		 */
		function is_cached( $key, $logging_enabled = true )
		{
			$f = $this->_get_cache_file( $key );
			if( file_exists( $f ) )
			{
				$age = time() - filemtime( $f );
				if( $age < $this->timeout )
				{
					if ($logging_enabled) $this->_log( $key, CACHE_LOG_MSG_HIT );
					return true;
				}
				else
				{
					if ($logging_enabled) $this->_log( $key, CACHE_LOG_MSG_MISS, CACHE_LOG_MSG_MISS_AGE  );
				}
			}
			else
			{
				if ($logging_enabled) $this->_log( $key, CACHE_LOG_MSG_MISS, CACHE_LOG_MSG_MISS_NO_CACHE );
			}
			return false;
		}
		
		function _get_cache_file( $key ) // {{{
		{
			$file = $this->get_dir().md5($key).'.cache';
			return $file;
		} // }}}
		function _clean_key( $key )
		{
			// if the last char is a ?, remove it.
			if( substr( $key, -1 ) == '?' )
				$key = substr( $key, 0, -1 );
			return $key;
		}
		function _check_dir() // {{{
		{
			if( !is_writable( $this->get_dir() ) )
			{
				trigger_error( 'Cache directory is not writable so caching is not available' );
			}
		} // }}}
		function _log( $key, $type, $extra1 = '',$extra2 = '')
		{
			if( $this->_logging )
			{
				$sep = "\t";
				$time = date('Y-m-d H:i:s',time());
				$log_parts = array();
				$log_parts[] = $time;
				$log_parts[] = $key;
				$log_parts[] = $type;
				$log_parts[] = $extra1;
				$log_parts[] = $extra2;
				$log_parts[] = $this->page_gen_time;
				$log_parts[] = !empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
				array_walk( $log_parts, 'db_prep_walk' );
				$msg = join( $log_parts, ',' );
				$q = 'INSERT INTO page_cache_log (dt,cache_key,action_type,extra1,extra2,page_gen_time,user_agent) VALUES ('.$msg.')';
				db_query( $q, 'Unable to update page cache log' );
			}
		}
	}
	
	/*
	if( __FILE__ == $_SERVER['SCRIPT_FILENAME'] )
	{
		include_once( 'reason_header.php' );
		connectDB( REASON_DB );
		$c = new PageCache();
		$page = $c->fetch( get_current_url() );
		if( !empty( $page ) )
		{
			echo('THIS IS THE CACHED VERSION');
		}
		else
		{
			echo 'NO CACHE<br/>';
			ob_start();
			echo 'this is the page that is awesome.';
			$page = ob_get_contents();
			ob_end_clean();
			$c->store( get_current_url(), $page );
		}
		echo $page;
	}
	*/
?>
