<?
include_once('reason_header.php');
//include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once(DISCO_INC.'disco.php');
//include_once(DISCO_INC.'plasmature/plasmature.php');
reason_include_once('classes/user.php');
reason_include_once('classes/admin/admin_page.php');
              


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
		$url_field = $this->get_element_name_from_label('Your website URL');
		$plain = 'www.luther.edu/page/to/work/on';
		$this->add_comments($url_field, '<br />e.g. <em>'.$plain.'</em>');

/*
		$username = reason_check_authentication();
		
		
		$es = new entity_selector($username);
		$es->add_type( id_of( 'site' ) );
		//$es->set_sharing( 'owns' ); //only get owned items
		$sites = $es->run_one();	
		//$site_names = $sites->get_value('name');	
		//pray($sites);
		
		echo 'lala';
		$z = new user();		
//		$x = get_user_sites($username);
		$s = $z->is_site_user($username);
		
		pray($s);
		echo 'lala';
		
*/
		
		//echo $sites->get_value('name');

 
		
//		$site = $this->get_element_name_from_label('Your website URL');
	}
}
?>
