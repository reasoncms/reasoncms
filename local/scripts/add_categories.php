<?php
include_once('reason_header.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('function_libraries/admin_actions.php');
// get site owns category relationship
//$owns_id = relationship_finder(id_of('site'), id_of('category_type'));

// select all categories on master admin site
//$es = new entity_selector();
//$es->add_type(id_of('category_type'));
//$es->add_right_relationship(id_of('admissions'), $owns_id);
//$result = $es->run_one();
//pray ($result);

// simpler way
//$es = new entity_selector(id_of('admissions'));
//$es->add_type(id_of('category_type'));
//$result2 = $es->run_one();
//pray ($result2);

// select all sites with category ids
// relationship is "owns" entity a is site, entity b is category
// ummm ... this does not work because of the issue where tons of relationships are named "owns"
// it will work in Reason 4 Beta 9 ...
//$es = new entity_selector();
//$es->add_type(id_of('site'));
//$es->enable_multivalue_results();
//$es->limit_tables();
//$es->limit_fields();
//$es->add_left_relationship_field('owns', 'entity', 'id', 'cat_id');
//$es->add_left_relationship_field('owns', 'meta', 'description', 'cat_description');
//$result3 = $es->run_one();
//pray($result3);
//echo 'hi';

// this code causes each site to borrow all categories from the master admin site
$es = new entity_selector();
$es->add_type(id_of('site'));
$es->add_relation('entity.id != ' . id_of('master_admin'));
$sites = $es->run_one();

$cat_es = new entity_selector(id_of('master_admin'));
$cat_es->add_type(id_of('category_type'));
$cat_result = $cat_es->run_one();
$cats = array_keys($cat_result);

$borrows_rel_id = get_borrow_relationship_id(id_of('category_type'));

foreach ($sites as $site_id => $site)
{
	$es2 = new entity_selector($site_id);
	$es2->add_type(id_of('category_type'));
	$result2 = $es2->run_one();
	$site_categories = array_keys($result2);
	
	$my_cats = array_diff($cats, $site_categories);
	
	foreach ($my_cats as $new_cat_id)
	{
		create_relationship($site_id, $new_cat_id, $borrows_rel_id);
	}
}

?>