<?php
/**
 * Change the news type feed from news to post - run rewrites on entire site.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Upgrade news feed generator</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/url_manager.php' );

class upgradeNewsFeedGenerator
{
	var $mode;
		
	//type_to_default_view
	
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->change_news_feed_url();
		$this->change_news_custom_feed();
		//$this->do_rewrites();
	}


	function change_news_feed_url()
	{
		$news_type = new entity(id_of('news', false));
		$feed_url_string = $news_type->get_value('feed_url_string');
		$link = REASON_HTTP_BASE_PATH . '/scripts/urls/update_urls.php';
				
		if ($feed_url_string == 'news')
		{
			if ($this->mode == 'run')
			{
				$values['feed_url_string'] = 'posts';
				reason_update_entity($news_type->id(), $this->reason_user_id, $values);
				
				$link = REASON_HTTP_BASE_PATH . '/scripts/urls/update_urls.php';
				echo '<p>Changed the news type feed_url_string from news to posts. You should <a href="'.$link.'">run rewrites</a>';
				echo ' on all sites in order to complete the upgrade.</p>';
			}
			else
			{
				echo '<p>Would change the news type feed_url_string from news to posts and run rewrites.</p>';
			}
		}
		else
		{
			echo '<p>The news type feed_url_string is not currently "news" - the script has probably already been run.</p>';
			echo '<p>If you did not already do this, you should <a href="'.$link.'">run rewrites</a> on all sites to complete the upgrade.</p>';
		}
	}
	
	function change_news_custom_feed()
	{
		$news_type = new entity(id_of('news', false));
		$custom_feed = $news_type->get_value('custom_feed');
				
		if ($custom_feed == 'news.php')
		{
			if ($this->mode == 'run')
			{
				$values['custom_feed'] = 'sitewide_news.php';
				reason_update_entity($news_type->id(), $this->reason_user_id, $values);
				
				$link = REASON_HTTP_BASE_PATH . '/scripts/urls/update_urls.php';
				echo '<p>Changed the news type custom_feed from news to sitewide news.</p>';
			}
			else
			{
				echo '<p>Would change the news type custom_feed from news to sitewide news.</p>';
			}
		}
		else
		{
			echo '<p>The news type custom feed has been changed from news.php - this script has probably already been run.</p>';
		}
	}

	/**
	 * We are not actually using this, instead we refer users to the url rewriting manager.
	 */
	function do_rewrites()
	{
		if ($this->mode == 'run')
		{
			$es = new entity_selector();
			$es->limit_tables();
			$es->limit_fields();
			$es->add_type(id_of('site'));
			$es->add_left_relationship(id_of('news'), relationship_id_of('site_to_type'));
			$result = $es->run_one();
			$ids = array_keys($result);
			foreach ($ids as $site_id)
			{
				$um = new url_manager( $site_id);
				$um->update_rewrites();
				$count = (isset($count)) ? $count++ : $count = 1;
			}
			echo '<p>Updated rewrite rules on all sites that have the news/post type - ' . $count . ' in total.</p>';
		}
		else
		{
			echo '<p>Would run rewrites on all the sites that have the news/post type</p>';
		}
	}

}
force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
if(empty($reason_user_id)) die('valid Reason user required');

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
        die('You must have upgrade privileges to run this script');
}

?>
<h2>Reason: Upgrade News Feed Generator</h2>
<p>The default news feeds used in previous versions of reason are not publication aware. This script modifies the news type so that is uses
a feed generator that is publication aware, and capable of creating a sitewide feed of posts with links to the appropriate publications. When
the rewrites are run, an additional rule is create to preserve the functionality of the old-style news feeds. The old-style news feeds will 
redirect to the feed for the oldest publication, so that the old links continue to function.</p>
<p>What will this update do?</p>
<ul>
<li>Change the news type feed_url_string from news to post.</li>
<li>Change the news type custom_feed from news to sitewide_news.</li>
</ul>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new upgradeNewsFeedGenerator();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
