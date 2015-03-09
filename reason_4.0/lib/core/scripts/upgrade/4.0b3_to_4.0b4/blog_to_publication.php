<?php
/**
 * Part 1 of the publication framework setup
 *
 * This script is part of the 4.0 beta 3 to beta 4 upgrade
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('classes/amputee_fixer.php');

force_secure_if_available();

$user_netID = check_authentication();

$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

echo '<h2>Reason Publication Setup</h2>';
if (!isset ($_POST['verify']))
{
        echo '<p>This script changes various references to "blog" to "publication".</p>';
        echo '<h2>Changes database as follows:</h2>';
		echo '<h3>Updates entities:</h3>';
		echo '<ul><li>unique name of blog_type => publication_type</li>';
		echo '<li>name of blog_type => Blog / Publication</li></ul>';
		echo '<h3>Updates allowable relationships:</h3>';
		echo '<ul>';	
		echo '<li>blog_type_archive => publication_type_archive</li>';
		echo '<li>page_to_blog => page_to_publication</li>';
		echo '<li>news_to_blog => news_to_pubication</li>';
		echo '<li>blog_to_authorized_posting_group => publication_to_authorized_posting_group</li>';
		echo '<li>blog_to_authorized_commenting_group => publication_to_authorized_commenting_group</li>';
		echo '<li>issue_to_blog => issue_to_publication</li>';
		echo '<li>news_section_to_blog => news_section_to_publication</li>';
		echo '<li>blog_to_featured_post => publication_to_featured_post</li>';
		echo '</ul>';
		echo '<h3>Changes blog pages to use publications page types</h3>';
		echo '<ul>';
		foreach(get_blog_to_publication_page_type_map() as $old=>$new)
		{
			echo '<li>'.$old.' => '.$new.'</li>';
		}
		echo '</ul>';
}

if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run'))
{
	$the_type = (id_of('blog_type')) ? id_of('blog_type') : id_of('publication_type');
	
	if ($the_type > 0)
	{
		if (reason_update_entity( $the_type, get_user_id($user_netID), array('name' => 'Blog / Publication', 'unique_name' => 'publication_type', 'plural_name' => 'Blogs / Publications'), false))
		{
			echo '<p>updated blog/publication type with correct unique_name, plural_name, and name, but more work is needed</p>';
			echo_form();
		}
		else
		{
			echo '<p>blog/publication type did not need updating.</p>';
		}
	}
	else
	{
		echo '<p>could not find either blog_type or publication_type in the database - cannot proceed</p>';
		die;
	}
	
	relationship_find_and_update('publication_type', 'publication_type', 'blog_type_archive', array ('name' => 'publication_type_archive', 'description' => 'Blog / Publication Archive Relationship'));
	relationship_find_and_update('minisite_page', 'publication_type', 'page_to_blog', array ('name' => 'page_to_publication', 
																					'description' => 'Places a blog / publication on a page', 
																					'display_name' => 'Places a blog / publication on a page',
																					'display_name_reverse_direction' => 'Pages where this blog / publication has been placed',
																					'description_reverse_direction' => 'Pages where this blog / publication has been placed'));
	relationship_find_and_update('news', 'publication_type', 'news_to_blog', array (	'name' => 'news_to_publication',
																			'description' => 'News is part of blog / publication', 
																			'display_name' => 'Post this news item to a blog / publication',
																		   	'display_name_reverse_direction' => 'Manage Posts',
																		   	'description_reverse_direction' => 'Posts on this blog / publication'));
	relationship_find_and_update('publication_type', 'group_type', 'blog_to_authorized_posting_group', array (	
																			'name' => 'publication_to_authorized_posting_group',
																			'description' => 'publication to authorized posting group (i.e. who can post)', 
																			'display_name_reverse_direction' => 'Blogs / Publications use this group to determine posting permissions',
																		   	'description_reverse_direction' => 'Blogs / Publications use this group to determine posting permissions'));
	relationship_find_and_update('publication_type', 'group_type', 'blog_to_authorized_commenting_group', array (	
																			'name' => 'publication_to_authorized_commenting_group',
																			'description' => 'publication to authorized commenting group (i.e. who can comment)', 
																			'display_name_reverse_direction' => 'Blogs / Publications use this group to determine commenting permissions',
																		   	'description_reverse_direction' => 'Blogs / Publications use this group to determine commenting permissions'));
	relationship_find_and_update('issue_type', 'publication_type', 'issue_to_blog', array('name' => 'issue_to_publication', 'description' => 'Issue to Publication'));
	relationship_find_and_update('news_section_type', 'publication_type', 'news_section_to_blog', array('name' => 'news_section_to_publication', 'description' => 'News Section to Publication'));
	relationship_find_and_update('publication_type', 'news', 'blog_to_featured_post', array('name' => 'publication_to_featured_post', 'description' => 'Publication to Featured Post'));
	
	$qs = array();
	$total_affected = 0;
	echo '<h3>Updating page types</h3>';
	foreach(get_blog_to_publication_page_type_map() as $old=>$new)
	{
		$q = 'UPDATE page_node SET custom_page = "'.reason_sql_string_escape($new).'" WHERE page_node.custom_page = "'.reason_sql_string_escape($old).'"';
		if($r = db_query($q, 'Problem changing '.$old.' page types to '.$new.' page types'))
		{
			$num_updated = mysql_affected_rows();
			$total_affected += $num_updated;
			echo '<p>'.$old.' => '.$new.': '.$num_updated.' pages updated</p>';
		}
	}
	if($total_affected)
	{
		echo '<p>'.$total_affected.' pages changed in total.</p>';
	}
	else
	{
		echo '<p>No pages changed.  This script has probably already been run.</p>';
	}
	echo '<p><a href="index.php">Continue beta 3 to beta 4 upgrade</a></p>';
	
	}
else
{
	echo_form();
}

function relationship_find_and_update($a_type, $b_type, $name, $updates = array())
{
	$existing_rel_id = relationship_finder($a_type, $b_type, $name);
	if (!empty($existing_rel_id) && !empty($updates))
	{
		// build criteria clause - only want to update if it is actually needed
		$set_str = $where_str_body = '';
		$where_str_start = " AND (";
		foreach ($updates as $k=>$v)
		{
			$set_str .= (!empty($set_str)) ? ", " : '';
			$where_str_body .= (!empty($where_str_body)) ? ") OR (" : "(";
			$where_str_body .=  $k . ' != "' . reason_sql_string_escape($v) .'"';
			$set_str .= $k .' = "'. reason_sql_string_escape($v) . '"';
		}
		$where_str_end = "))";
		$q = 'UPDATE allowable_relationship SET ' . $set_str . ' WHERE ID='.$existing_rel_id.$where_str_start.$where_str_body.$where_str_end;
		db_query($q, 'could not update the places a blog on a page relationship');
		$num_rows = mysql_affected_rows();
		if (!empty($num_rows))
		{
			echo '<p>updated relationship ' . $name .'</p>';
			pray ($updates);
		}
	}
}

function echo_form()
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<p><input type="submit" name="verify" value="Run" /></p>';
	echo '</form>';
}

function get_blog_to_publication_page_type_map()
{
	return array('blog'=>'publication','blog_with_events_sidebar'=>'publication_with_events_sidebar','blog_with_events_sidebar_and_content'=>'publication_with_events_sidebar_and_content');
}

?>
