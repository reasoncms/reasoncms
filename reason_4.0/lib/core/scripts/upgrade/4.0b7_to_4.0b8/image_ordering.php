<?php
/**
 * Reason 4 Beta 8 image ordering fix
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
<title>Upgrade Reason to Beta 8: Image Ordering</title>
</head>

<body>
<?php

ini_set('max_execution_time', 1800);
ini_set('mysql_connect_timeout', 1200);
ini_set("memory_limit","256M");

include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/url_utils.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
connectDB( REASON_DB );

force_secure_if_available();
$current_user = $user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

$updater = new imagePageOrderUpdate();
$updater->set_user_id( $reason_user_id );
$page_types = $updater->get_page_types();
if(empty($page_types))
{
	die('No need to run this upgrade; no page types with the images module exist.');
}
$pages = $updater->get_pages($page_types);
if(empty($pages))
{
	die('No need to run this upgrade; no pages with the images module exist.');
}

/**
 * A class that encapsulates all the 
 */
class imagePageOrderUpdate
{
	var $_user_id;
	function set_user_id($user_id)
	{
		$this->_user_id = $user_id;
	}
	function get_page_types()
	{
		return page_types_that_use_module('images');
	}
	function get_pages($page_types)
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('custom_page IN ("'.implode('","',$page_types).'")');
		return $es->run_one();
	}
	function get_images_in_old_order($page_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('image'));
		$es->add_right_relationship( $page_id, relationship_id_of('minisite_page_to_image') );
		$es->set_order('dated.datetime ASC');
		return $es->run_one();
	}
	function get_images_in_new_order($page_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('image'));
		$es->add_right_relationship( $page_id, relationship_id_of('minisite_page_to_image') );
		$es->add_rel_sort_field( $page_id, relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
		$es->add_field( 'relationship', 'id', 'rel_id' );
		$es->set_order( 'rel_sort_order ASC, dated.datetime ASC, meta.description ASC, entity.id ASC' );
		return $es->run_one();
	}
	function update_image_relationships($images, $order)
	{
		$i = 1;
		foreach($order as $id)
		{
			if(!isset($images[$id]))
			{
				trigger_error('All images in $images must have their key as a value in $order, and vice versa!');
				continue;
			}
			if(!$images[$id]->get_value('rel_id'))
			{
				trigger_error('All images in $images must have a rel_id value.');
				continue;
			}
			update_relationship( $images[$id]->get_value('rel_id'), array('rel_sort_order'=>$i));
			$i++;
		}
		return true;
	}
}

?>
<h2>Reason: Update images module relationship sort</h2>
<p>In Reason Beta 8, the images module lists images as they are manually sorted in Reason. Previously it listed images in ascending order by date and time.</p>
<p>This update checks to see if there are any pages using the images module, and if so, checks to see if the order of their images will change when this update is applied.</p>
<p>(Note: the images module is neither the common image gallery module nor the image sidebar module. It shows a list of full-sized images, with captions, all together on a page.</p>
<p style="background-color:#eee;border:1px solid #aaa;padding:1em;"><strong>This update will change the sort order of images on the changed pages -- it will make the sort order match their datetime values. This script should only be run <em>once</em>. As Reason has no way of knowing if you have previously run this update, you will need to make sure to not run it again. It <em>will</em> report that it needs to be run again once any images have been moved around on these pages after its initial run. Ignore that report and do not run it again. If you need to, alter this script after it successfully runs and place a statement like <tt>die('Already run');</tt> at the top.</strong></p>
<p>What will this update do?</p>
<ul>
<li>In "test" mode, it will report on pages whose images's sort values need to be reordered to remain in the same order on the pages in question</li>
<li>In "run" mode, it will update the sort order of the images on the page to match the order the images appeared in before Beta 8.</li>
</ul>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
	{
		echo '<p>Updating these pages\' sort order:</p>'."\n";
	}
	else
	{
		echo '<p>Pages whose sort order needs to be updated:</p>'."\n";
	}
	$num = 0;
	echo '<ul>'."\n";
	foreach($pages as $id=>$page)
	{
		$old_order_images = $updater->get_images_in_old_order($id);
		reset($old_order_images);
		//pray($old_order_images);
		$new_order_images = $updater->get_images_in_new_order($id);
		reset($new_order_images);
		//pray($new_order_images);
		$equal = true;
		$i = 1;
		$count = count($old_order_images);
		$newcount = count($new_order_images);
		if($count != $newcount)
		{
			echo '<li>Page id '.$id.' is not returning the same number of images for the two orders. Something is quite wrong; not updating page.</li>';
			continue;
		}
		while($i <= $count && $equal)
		{
			$i++;
			$old = current($old_order_images);
			next($old_order_images);
			$new = current($new_order_images);
			next($new_order_images);
			//echo gettype($old);
			//echo gettype($new);
			if($old->id() != $new->id())
			{
				$equal = false;
			}
		}
		if(!$equal)
		{
			$num++;
			echo '<li><a href="'.reason_get_page_url( $id ).'" target="_new">'.$id.': '.$page->get_value('name').'</a>';
			if($_POST['go'] == 'run')
			{
				// 
				if($updater->update_image_relationships($new_order_images, array_keys($old_order_images)))
					echo '...Updated';
				else
					echo '...Not updated!';
			}
			echo '</li>'."\n";
		}
	}
	if($num == 0)
	{
		echo '<li>None</li>'."\n";
	}
	echo '</ul>'."\n";
}

?>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
