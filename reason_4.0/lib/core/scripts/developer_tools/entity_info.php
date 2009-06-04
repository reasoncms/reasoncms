<?php
/**
 * A tool that provides an easy way to get info on a reason entity.
 *
 * @author Nathan White
 */

include_once('reason_header.php');
reason_include_once('function_libraries/user_functions.php');
if (reason_require_authentication() && reason_check_privs('view_sensitive_data'))
{
	$id = (isset($_POST['id'])) ? $_POST['id'] : '';
	echo '<h3>Enter an entity id</h3>';
	echo '<form method="post">';
	echo '<p>entity id: <input name="id" value="'.$id.'" /></p>';
	echo '<input type="submit">';
	echo '</form>';
	if ($id)
	{
		$e = new entity($id);
		$e->get_values();
		pray ($e);
	}
}
?>
