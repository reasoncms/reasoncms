<?
include_once('reason_header.php');
//include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once(DISCO_INC.'disco.php');
//include_once(DISCO_INC.'plasmature/plasmature.php');
reason_include_once('classes/user.php');
              


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'WebworkForm';

/**
 * 
 * @author Steve Smith
 */
class WebworkForm extends DefaultThorForm
{
	function on_every_time()
	{	
		$date = $this->get_element_name_from_label('Date needed');
		$this->change_element_type($date, 'textdate', array('display_name'=>'Desired "go live" date',));

//		$username = reason_check_authentication();
//
//		reason_include_once('classes/user.php');
//		$tempuser = new user;
//		$tempuser2 = $tempuser->get_user('smitst01');
//		pray($tempuser2);

		
//		echo($tempuser->get_user_sites($username));


//		echo $username;
//		$user_id = get_user_id($username);
//		$user_entity = new entity($user_id);
//		pray ($user_entity);
//		$your_name = $user_entity->get_value('user_given_name');
		
//		$sites = $user_entity->get_values();	
//		pray ($sites);
		

		
//		$site = $this->get_element_name_from_label('Your website URL');
	}
}
?>
