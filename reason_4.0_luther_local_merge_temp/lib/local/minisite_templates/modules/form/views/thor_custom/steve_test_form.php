<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'SteveTestThorForm';

/**
 * DefaultThorForm is an extension of the DefaultForm used for Thor
 *
 * 1. Uses the custom HTML from the thor form instead of the controller default.
 *
 * @author Nathan White
 */

class SteveTestThorForm extends DefaultThorForm
{
	var $elements = array('extra_field', 'extra_field2');
	
	// if defined none of the default actions will be run (such as email_form_data) and you need to define the custom method and a
	// should_custom_method in the view (if they are not in the model).
	var $process_actions = array('my_custom_process');
	
	function custom_init()
	{
	
	}
	
	function on_every_time()
	{
		$username = reason_check_authentication();
		
		if ($username)
		{
			echo '<p>Your username is ' . $username . '</p>';
			$user_id = get_user_id($username);
			$user_entity = new entity($user_id);
			pray ($user_entity);
			$your_name = $user_entity->get_value('user_given_name');
			
			echo '<p>Welcome to the form ' . $your_name . '</p>';
		}
	
		$food_stuff_field_name = $this->get_element_name_from_label('Food Stuff');
		$this->set_comments($food_stuff_field_name, '<p>The list of foods has been carefully selected.</p>');
		$this->change_element_type('extra_field', 'textarea');
		$this->add_required($this->get_element_name_from_label('Last Name'));
	}	
	
	function run_error_checks()
	{
		$val = $this->get_value('extra_field');
		if (empty($val)) $this->set_error('extra_field', 'The field must have content');
	}
	
	function process()
	{
		// getting value from a disco field
		$field_value = $this->get_value('extra_field');
		
		// getting disco field name from thor
		$food_stuff_field_name = $this->get_element_name_from_label('Food Stuff');
		$food_stuff_value = $this->get_value($food_stuff_field_name);
		echo $food_stuff_value;
	}
	
	function should_my_custom_process()
	{
		return true;
	}
	
	function my_custom_process()
	{
		echo 'hello';
	}
	
	function where_to()
	{
		return false;
	}
}
?>
