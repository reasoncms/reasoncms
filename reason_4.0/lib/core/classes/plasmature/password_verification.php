<?php
/**
 * This plasmature type integrates with the directory service library to provide a password 
 * verification field -- if this field is added to a form, the password entered
 * will be verified against the user's account, and an error set if the check fails.
 *
 * @package reason
 * @subpackage classes
 */
require_once CARL_UTIL_INC.'dir_service/directory.php';
include_once( DISCO_INC.'plasmature/plasmature.php' ); 
 
class password_verificationType extends passwordType
{	
	function grab()
	{
		$this->run_error_checks();
	}
	
	function run_error_checks()
	{
		$name = trim($this->display_name);
		if (empty($name)) $name = $this->name;
		$name = prettify_string($name);
		
		$username = reason_require_authentication();
		$password = $this->grab_value();
		
		$dir = new directory_service();
		if (!$dir->authenticate($username, $password))
			$this->set_error( $name.':  Please check your password.' );
	}	
}


?>
