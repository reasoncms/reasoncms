<?php
	include ('paths.php');
	include_once( CARL_UTIL_INC . 'cache/cache.php' );
	
	echo '<html><head><title>Discover a Cache File for a particular URL</title></head><body>';
	echo '<h1>Discover a Cache File for a particular URL</h1>';
	echo '<p>You might want to double check before zapping a cache file.</p>';
	echo '<form method="get"><input type="text" name="url"><input type="submit"></form></body></html>';
	if(!empty($_REQUEST['url']))
	{
		$pc = new PageCache;
		echo '<p>URL: '.$_REQUEST['url'].'</p>';
		echo '<p>Cache Filename: '.$pc->_get_cache_file( $_REQUEST['url'] ).'</p>';
	}
?>
