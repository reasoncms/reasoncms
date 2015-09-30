<?php
/**
 * Upgrade the DB from beta 3 to beta 4 for the publications framework
 *
 * This script creates and modifies the database for the new publication system.
 * It does not migrate existent publications into the new system.
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
        echo '<p>This script creates and modifies the database for the new publication system. It does not migrate existant publications into the new system.</p>';
        echo '<h2>Changes database as follows:</h2>';
	echo '<h3>New entities:</h3>';
	echo '<ul><li>nobody_group group</li></ul>';
        echo '<h3>New entity tables:</h3>';
        echo '<ul>';
        echo '<li>date_format</li>';
        echo '<li>news_section</li>';
        echo '</ul>';
        echo '<h3>New fields:</h3>';
        echo '<ul>';
        //echo "<li>enable_comment_notification in entity table commenting_settings - enum('yes','no')</li>";
        echo "<li>date_format in entity table date_format - tinytext</li>";
        echo "<li>group_has_members in entity table user_group - enum('true','false')</li>";
        echo "<li>hold_posts_for_review in entity table blog - enum('yes','no')</li>";
//        echo "<li>enable_front_end_posting in entity table blog - enum('yes','no')</li>";
//        echo "<li>pagination_state in entity table blog - enum('on','off')</li>";
//        echo "<li>publication_type in entity table blog - enum('Blog','Newsletter','Issued Newsletter')</li>";
        echo "<li>publication_type in entity table blog - enum('Blog','Newsletter')</li>";
        echo "<li>has_issues in entity table blog - enum('yes','no')</li>";
        echo "<li>notify_upon_post in entity table blog - tinytext</li>";
        echo "<li>notify_upon_comment in entity table blog - tinytext</li>";
        echo "<li>posts_per_section_on_front_page in entity table news_section - tinyint DEFAULT 2</li>";
        echo '</ul>';
	echo '<h3>New allowable relationships:</h3>';
	echo '<ul>';
	echo '<li>issue_to_blog</li>';
	echo '<li>issue_to_image</li>';
	echo '<li>issue_to_text_blurb</li>';
	echo '<li>news_section_to_blog</li>';
	echo '<li>news_section_to_image</li>';
	echo '<li>blog_to_featured_post</li>';
	echo '<li>event_to_news</li>';
	echo '</ul>';
	echo '<h3>Updates allowable relationship:</h3>';
	echo '<ul>';
	echo '<li>Places a blog on a page -> page_to_blog</li>';
	echo '<li>Changes required to "no" for news_to_issue</li>';
	echo '<li>Changes required to "no" for news_to_news_section</li>';
	echo '</ul>';
}

if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run'))
{
	//check if nobody group exists	
	$e = id_of('nobody_group');
	if ($e)
	{
        	echo '<p>nobody group already exists - not created</p>';
	}
	else
	{
		$user_id = get_user_id($user_netID);
		$site_id = id_of('master_admin');
		$type_id = id_of('group_type');
        	$name = "Nobody Group";
        	$values = array('unique_name' => 'nobody_group',
                        	'group_has_members' => 'false');

        	$new_e_id = reason_create_entity( $site_id, $type_id, $user_id, $name, $values, $testmode = false);
        	if (!empty($new_e_id))
        	{
                	echo '<p>created nobody group entity</p>';
		}
	}
	
	//check if blog_type exists - if it does not this database has probably already been upgraded and converted
	//
	
	$test = id_of('blog_type');
	$blog_type_exists = (empty($test)) ? false : true;
		
	//echo '<p>There is no type with unique name blog_type, which means ';
	//echo 'the script cannot continue to run and this database has ';
	//echo 'probably already been upgraded.</p>';
	//die;

	//check if entity table date_format exists
	if ($blog_type_exists && create_entity_table("date_format", "blog_type", get_user_id($user_netID)))
	{
		echo '<p>created entity table date_format and added to blog type</p>';
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees(id_of('blog_type'));
		$fixer->generate_report();
	}
	else echo '<p>date_format entity table already exists - not created</p>';
	
	//check if entity table news_section exists
	if (create_entity_table("news_section", "news_section_type", get_user_id($user_netID)))
	{
		echo '<p>created entity table news_section and added to news section type</p>';
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees(id_of('news_section_type'));
		$fixer->generate_report();
	}
	else echo '<p>news_section entity table already exists - not created</p>';
	
	//$entity_table_name = 'commenting_settings';
	//$fields = array('enable_comment_notification' => array('db_type' => "enum('yes','no')"));
	//$updater = new FieldToEntityTable($entity_table_name, $fields);
	//$updater->update_entity_table();
	//$updater->report();
	
	$entity_table_name = 'date_format';
	$fields = array('date_format' => array('db_type' => 'tinytext'));
	$updater2 = new FieldToEntityTable($entity_table_name, $fields);
	$updater2->update_entity_table();
	$updater2->report();
	
	$entity_table_name = 'user_group';
	$fields = array('group_has_members' => array('db_type' => "enum('true','false')"));
	$updater3 = new FieldToEntityTable($entity_table_name, $fields);
	$updater3->update_entity_table();
	$updater3->report();
	
	$entity_table_name = 'blog';
	$fields = array('hold_posts_for_review' => array('db_type' => "enum('yes','no')"),
					//'enable_front_end_posting' => array('db_type' => "enum('yes','no')"),
					//'pagination_state' => array('db_type' => "enum('yes','no')"),
					'publication_type' => array('db_type' => "enum('Blog','Newsletter')"),
					'has_issues' => array('db_type' => "enum('yes','no')"),
					'has_sections' => array('db_type' => "enum('yes','no')"),
					'has_sections' => array('db_type' => "enum('yes','no')"),
					'notify_upon_post' => array('db_type' => "tinytext"),
					'notify_upon_comment' => array('db_type' => "tinytext"),
				);
	$updater4 = new FieldToEntityTable($entity_table_name, $fields);
	$updater4->update_entity_table();
	$updater4->report();
	
	$entity_table_name = 'news_section';
	$fields = array('posts_per_section_on_front_page' => array('db_type' => "tinyint DEFAULT 2"));
	$updater5 = new FieldToEntityTable($entity_table_name, $fields);
	$updater5->update_entity_table();
	$updater5->report();

	$blog_type = id_of('blog_type');
	$issue_type = id_of('issue_type');
	$news_type = id_of('news');
	$news_section_type = id_of('news_section_type');
	$text_blurb_type = id_of('text_blurb');
	$image_type = id_of('image');
	
	
	// we only report success - error triggering will report failure and reason for failure
	if ($blog_type_exists && create_allowable_relationship($issue_type,$blog_type,'issue_to_blog',array(	'connections' => 'one_to_many', 
																					'description' => 'Issue to Publication',
																					'display_name' => 'Assign this issue to a publication')))
	{
		echo '<p>Created issue_to_blog allowable relationship</p>';
	}
	if (create_allowable_relationship($issue_type,$image_type,'issue_to_image',array(	'connections' => 'one_to_many',
																						'description' => 'Issue to Image',
																						'display_name' => 'Associate an image with this issue')))
	{
		echo '<p>Created issue_to_image allowable relationship</p>';
	}
	if (create_allowable_relationship($issue_type,$text_blurb_type,'issue_to_text_blurb',array(	'connections' => 'many_to_many',
																								'description' => 'Issue to Text Blurb',
																								'display_name' => 'Associate text blurbs with this issue')))
	{
		echo '<p>Created issue_to_text_blurb allowable relationship</p>';
	}
	
	if ($blog_type_exists && create_allowable_relationship($news_section_type,$blog_type,'news_section_to_blog',array(	'connections' => 'one_to_many',
																									'description' => 'News Section to Publication',
																									'display_name' => 'Assign this section to a publication')))
	{
		echo '<p>Created news_section_to_blog allowable relationship</p>';
	}
	if (create_allowable_relationship($news_section_type,$image_type,'news_section_to_image',array(	'connections' => 'one_to_many',
																									'description' => 'News Section to Image',
																									'display_name' => 'Associate image with this news section')))
	{
		echo '<p>Created news_section_to_image allowable relationship</p>';
	}
	if ($blog_type_exists && create_allowable_relationship($blog_type,$news_type,'blog_to_featured_post',array(	'connections' => 'many_to_many',
																							'description' => 'Publication to Featured Post',
																							'display_name' => 'Assign Featured Posts',
																							'is_sortable' => 'yes',
																							'directionality' => 'bidirectional',
																							'display_name_reverse_direction' => 'Feature on Publiction(s)',
																							'description_reverse_direction' => 'Featured on Publication(s)')))
	{
		echo '<p>Created blog_to_featured_post allowable relationship</p>';
	}
	if (create_allowable_relationship(id_of('event_type'),$news_type,'event_to_news',array(	'description' => 'Event to News / Post',
																							'connections' => 'many_to_many',
																							'display_name' => 'Associate with a News Item',
																							'directionality' => 'bidirectional',
																							'is_sortable' => 'yes',
																							'display_name_reverse_direction' => 'Assign to event(s)',
																							'description_reverse_direction' => 'Events for this news items')))
	{
		echo '<p>Created event_to_news allowable relationship</p>';
	}
	
	//Update Places a blog on a page relationship to proper format
	if ($blog_type_exists)
	{
		$existing_rel_id = relationship_finder('minisite_page', 'blog_type', 'Places a blog on a page');
		if(!empty($existing_rel_id))
		{
			$q = 'UPDATE allowable_relationship SET name="page_to_blog" WHERE ID='.$existing_rel_id;
			db_query($q, 'could not update the places a blog on a page relationship');
			echo '<p>Renamed "Places a blog on a page" relationship to "page_to_blog"</p>';
		}
		else
		{
			echo '<p>The "Places a blog on a page" relationship has already been updated</p>';
		}
	}
	
	$news_to_issue_rel = relationship_finder('news', 'issue_type', 'news_to_issue');
	$news_to_news_section_rel = relationship_finder('news', 'news_section_type', 'news_to_news_section');
	
	if (check_required($news_to_issue_rel))
	{
		if (remove_required_relationship($news_to_issue_rel)) echo '<p>Set required to "no" for news_to_issue relationship</p>';
	}
	else echo '<p>Required is already set to "no" for news_to_issue relationship - no changes made</p>';
	
	if (check_required($news_to_news_section_rel))
	{
		if (remove_required_relationship($news_to_news_section_rel)) echo '<p>Set required to "no" for news_to_news_section relationship</p>';
	}
	else echo '<p>Required is already set to "no" for news_to_news_section relationship - no changes made</p>';
	//if (check_required($news_to_news_section_rel)) echo 'required for news_to_news_section_rel';
	
	zap_field('commenting_settings', 'enable_comment_notification', $reason_user_id);
	zap_field('blog', 'pagination_state', $reason_user_id);
	zap_field('blog', 'enable_front_end_posting', $reason_user_id);
	
	$pub_type_id = id_of('publication_type');
	if ($pub_type_id)
	{
		//check if type is related to commenting_settings entity table and if so, kill the rel
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "commenting_settings"');
		$es->add_right_relationship($pub_type_id, relationship_id_of('type_to_table'));
		$es->set_num(1);
		$es->limit_tables();
		$es->limit_fields();
		$result = $es->run_one();
		if (!empty($result))
		{
			$table = current($result);
			delete_relationships( array('entity_a' => $pub_type_id, 'entity_b' => $table->id(), 'type' => relationship_id_of('type_to_table')));
			// grab all publication entities
			$es2 = new entity_selector();
			$es2->add_type(id_of('publication_type'));
			$es2->limit_tables();
			$es2->limit_fields();
			$result2 = $es2->run_one();
			$ids = implode(",", array_keys($result2));
			$q = "DELETE from commenting_settings WHERE id IN(".$ids.")";
			db_query($q);
			echo '<p>zapped the relationship between the publication type and the commenting settings entity table.</p>';
			// delete rows from commenting_settings where id corresponds to a publication id		
		}
	}
}

else
{
	echo_form();
}

/**
 * If the enable_comment_notification field exists, then zap it - will only be the case if an earlier version of this script was run
 *
 */
function zap_field($entity_table_name, $field_name, $reason_user_id)
{
	$es = new entity_selector();
	$es->add_type(id_of('content_table'));
	$es->add_relation('entity.name = "'.$entity_table_name.'"');
	$es->set_num(1);
	$es->limit_tables();
	$es->limit_fields();
	$result = $es->run_one();
	if (empty($result))
	{
		echo '<p>The entity table ' . $entity_table_name . ' does not exist - field ' . $field_name . ' could not be zapped</p>';
		return false;
	}
	else
	{
		$et = current($result);
		$es2 = new entity_selector();
		$es2->add_type(id_of('field'));
		$es2->add_relation('entity.name = "'.$field_name.'"');
		$es2->add_left_relationship($et->id(), relationship_id_of('field_to_entity_table'));
		$es2->set_num(1);
		//$es2->limit_tables();
		//$es2->limit_fields();
		$result2 = $es2->run_one();
		if (empty($result2))
		{
			echo '<p>The field ' . $field_name . ' does not exist in entity table ' . $entity_table_name . ' and could not be zapped</p>';
			return false;
		}
		else
		{
			$res = current($result2);
			$q = "ALTER TABLE ".$entity_table_name." DROP ".$field_name;
			db_query($q);
			reason_expunge_entity($res->id(), $reason_user_id); // also deletes all relationships to the field, which fixes the entity table
			echo '<p>Zapped field ' . $field_name . ' in entity table ' . $entity_table_name . '<p>';
		}
	}
}

function check_for_entity_table($et)
{
	$es = new entity_selector(id_of('master_admin'));
	$es->add_type(id_of('content_table'));
	$es->add_relation('entity.name = "'.$et.'"');
	$results = $es->run_one();
	$result_count = count($results);
	if ($result_count == 0) return false;
	else return true;
}

function create_entity_table($et, $type_unique_name, $userid)
{
	if (!check_for_entity_table($et))
	{
		$type_id = id_of($type_unique_name);
		create_reason_table($et, $type_id, $userid);
		return true;
	}
	return false;
}

function check_required($id)
{
	$q = 'SELECT required FROM allowable_relationship WHERE id='.$id;
	$result = db_query($q, 'Could not perform query');
	if (mysql_num_rows($result) > 0)
	{
		while ($v = mysql_fetch_assoc($result))
		{
			if ($v['required'] == 'yes') return true;
			else return false;
		}
	}
	else echo '<p>Did not find allowable relationship of id ' . $id . '</p>';
}

function remove_required_relationship($id)
{
	$q = 'UPDATE allowable_relationship SET required="no" WHERE id='.$id;
	$result = db_query($q, 'The allowable relationship could not be updated.');
	return true;
}

function echo_form()
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<p><input type="submit" name="verify" value="Run" /></p>';
	echo '</form>';
}

?>
