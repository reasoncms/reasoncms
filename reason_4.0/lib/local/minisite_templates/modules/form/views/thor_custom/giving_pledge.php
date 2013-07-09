<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
include_once(DISCO_INC.'disco.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'GivingPledgeForm';

/**
 * 
 * @author Steve Smith
 */


class GivingPledgeForm extends DefaultThorForm
{
	var $model;

    function custom_init()
	{
	  $model =& $this->get_model();
	  $head_items = $model->get_head_items();
	  $head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/pledge_form.js');
	}

	function on_every_time()
	{	
		$date = $this->get_element_name_from_label('Please begin this pledge on');
		$this->change_element_type($date, 'textdate');
		
		$amount = $this->get_element_name_from_label('Pledge Amount');
		$this->change_element_type($amount, 'money');
		
		$designation = $this->get_element_name_from_label('Designation');
		$this->change_element_type($designation, 'checkboxgroup_with_other', array('sort_options'=>false,
			'display_name'=>'Designation - If more than one designation is specified, your gift will be divided equally unless you indicate otherwise in the comments section below.'));
		
		$sports_designation = $this->get_element_name_from_label('Select a sports designation, if desired');
		$this->change_element_type($sports_designation, 'checkboxgroup_no_sort', array(
			'options' => array(
				'Baseball'=>'Baseball',
				'Basketball, men\'s'=>'Basketball, men\'s',
				'Basketball, women\'s'=>'Basketball, women\'s',
				'Cross Country, men\'s'=>'Cross Country, men\'s',
				'Cross Country, women\'s'=>'Cross Country, women\'s',								
				'Football'=>'Football',
				'Golf, men\'s'=>'Golf, men\'s',
				'Golf, women\'s'=>'Golf, women\'s',
				'Soccer, men\'s'=>'Soccer, men\'s',
				'Soccer, women\'s'=>'Soccer, women\'s',				
				'Softball'=>'Softball',
				'Swimming & Diving, men\'s'=>'Swimming & Diving, men\'s',
				'Swimming & Diving, women\'s'=>'Swimming & Diving, women\'s',
				'Tennis, men\'s'=>'Tennis, men\'s',
				'Tennis, women\'s'=>'Tennis, women\'s',
				'Track & Field, men\'s'=>'Track & Field, men\'s',
				'Track & Field, women\'s'=>'Track & Field, women\'s',
				'Volleyball'=>'Volleyball',		
				'Wrestling'=>'Wrestling',
				),
			)
		);
		
		$more_info = $this->get_element_name_from_label('Please send additional information regarding');
		$this->change_element_type($more_info, 'checkboxgroup');
		
		$prompt = $this->get_element_name_from_label('What prompted you to make this pledge?');
		$this->change_element_type($prompt, 'radio_with_other_no_sort');
		
		$connection = $this->get_element_name_from_label('Luther Connection');
		$this->change_element_type($connection, 'checkboxgroup_with_other', array('sort_options'=>false, 'display_name'=>'Please check all that apply'));
		
		
		$class_year = $this->get_element_name_from_label('Class Year');
		$this->change_element_type($class_year, 'numrange', array('start'=>1924,'end'=>date('Y'), 'comments' => '<br />if Alum'));		

	}
}
?>
