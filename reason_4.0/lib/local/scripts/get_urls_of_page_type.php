<?php
/**
 * Script gets a list of urls using a particular page type
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Get all URLs of a given page type</title>
</head>

<body>

<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('classes/page_types.php');


force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}
?>

<h2>Reason: Get all URLs of a given page type</h2>


<form method="post">
<input type="text" name="page_type" />
<input type="submit" name="go" value="run" />
</form>

<?php
if(!empty($_POST['page_type']))
{
	$pt_name = $_POST['page_type'];
	$rpts =& get_reason_page_types();
	$pt = $rpts->get_page_type($_POST['page_type']);
	//$pt->get_as_html(null, null);
	
	$es = new entity_selector();
	$es->add_type(id_of('minisite_page'));
	$es->limit_tables(array('page_node', 'url'));
	$es->limit_fields('entity.name, page_node.custom_page, page_node.url_fragment, url.url');
	$es->add_right_relationship_field( 'owns', 'entity' , 'id' , 'owner_id' );
	$es->add_left_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
	
	$es->add_relation('page_node.custom_page = "' . $pt_name .'"');
	$result = $es->run_one();
	
	echo '<h4>Page type: '.$pt_name."<br></h4>";
	foreach ($result as $page)
	{
		echo reason_get_page_url($page)."<br>";	
	}
}

?>
</body>
</html>

