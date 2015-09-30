<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include Reason library
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'basic/html_funcs.php');

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Change blurb page types to demote headings</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * Change pages of page type "blurb" that have blurbs with headings to a page type that demotes heading levels.
 */
class changeBlurbPageType
{
	var $mode;
	var $new_blurb_page_type; // DEFINE ME!
	
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
		$this->change_blurb_page_types();
	}


	function change_blurb_page_types()
	{
		$to_change = $this->get_pages_needing_change();
		if (!$to_change)
		{
			echo '<p>There are no pages with the blurb page type that contain blurbs with headers</p>';
		}
		elseif ($this->mode == 'test')
		{
			echo '<p>Would change these pages:</p>';
			pray ($to_change);
		}
		elseif ($this->mode == 'run')
		{
			if (!isset($this->new_blurb_page_type))
			{
				trigger_error('The variable new_blurb_page_type must be defined in this class for this script to actually function.');
			}
			else
			{
				foreach ($to_change as $entity_id)
				{
					$values = array('custom_page' => $this->new_blurb_page_type);
					reason_update_entity($entity_id, $this->reason_user_id, $values);
				}
			}
		}
	}

	function get_pages_needing_change()
	{
		if (!isset($this->pages_needing_change))
		{
			$es = new entity_selector();
			$es->add_type(id_of('minisite_page'));
			$es->enable_multivalue_results();
			$es->limit_tables('page_node');
			$es->limit_fields('custom_page');
			$es->add_relation('page_node.custom_page = "blurb"');
			$es->add_left_relationship_field('minisite_page_to_text_blurb', 'entity', 'id', 'blurb_id');
			$result = $es->run_one();
			foreach ($result as $k => $page)
			{
				$blurbs = (is_array($page->get_value('blurb_id'))) ? $page->get_value('blurb_id') : array($page->get_value('blurb_id'));
				foreach ($blurbs as $blurb_id)
				{
					$blurb = new entity($blurb_id);
					$content = $blurb->get_value('content');
					$demoted_content = demote_headings($content, 1);
					if ($content == $demoted_content) $pages_needing_page_type_change[$k] = $k;
				}
			}
			$this->pages_needing_change = (isset($pages_needing_page_type_change)) ? array_keys($pages_needing_page_type_change) : false;
		}
		return $this->pages_needing_change;
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
<h2>Reason: Change Blurb Page Types to Demote Headings</h2>
<?php
$updater = new changeBlurbPageType();

if (empty($_POST['go']) || (isset($_POST['go']) && ($_POST['go'] == 'test')) )
{
	$str = '<p><strong>What will this update do?</strong></p>';
	$str .= '<p>In Reason 4 Beta 7, the blurb page type instructs the blurb module to not change heading tags in the content area of text blurbs. ';
	$str .= 'Previously, the blurb module would reduce the heading level of h3 tags to h4. ';
	if (!$updater->get_pages_needing_change())
	{
		$str .= 'Your instance of Reason does not have any pages of type "blurb" with blurbs that contain headings, so you do not need to run this script.</p>';
	}
	else
	{
 		$str .= '</p><p><strong>You do have pages of page type "blurb" with blurbs that contain headings.</strong></p><p>If you have custom style sheets that reference h4 tags in text blurbs in the main content area of a page, you should probably run this script so that those pages continue to behave in the same way.</p>';
		$str .= '<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>';
	}
	echo $str;
}

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="./index.php">Return to 4.0b6 to 4.0b7 Update Scripts</a></p>
</body>
</html>
