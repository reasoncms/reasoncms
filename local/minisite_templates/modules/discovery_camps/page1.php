<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2011-02-28
//
//    Work on the first page of the discovery camp form
//
////////////////////////////////////////////////////////////////////////////////

class DiscoveryCampsOne extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Camper Information';

	var $elements = array(
            'camper_information_header' => array(
                'type' => 'comment',
                'text' => '<h3>Camper Information</h3>',
            ),
            'first_name' => array(
                'type' => 'text',
                'size' => 35,
            ),
            'last_name' => array(
                'type' => 'text',
                'size'=> 35,
            ),
            'gender' => array(
                'type' => 'radio_inline',
                'options' => array('F'=>'Female', 'M'=>'Male'),
            ),
            'grade' => 'text',
            'age' => array(
                'type' => 'text',
                'size' => 2,
                'display_name' => 'Age now'
            ),
            'address' => array(
                'type' => 'text',
                'size' => 35,
            ),
            'city' => array(
                'type' => 'text',
                'size' => 35,
            ),
            'state_province' => array(
                'type' => 'state_province',
                'display_name' => 'State/Province',
            ),
            'zip' => array(
                'type' => 'text',
                'size' => 35,
            ),
            'parent_information_header' => array(
                'type' => 'comment',
                'text' => '<h3>Parent/Guardian Information</h3>',
            ),
            'parent_guardian_name' => array(
                'type' => 'text',
                'display_name' => 'name',
            ),
            'home_phone' => array(
                'type' => 'text',
                'size' => 20,
            ),
            'work_phone' => array(
                'type' => 'text',
                'size' => 20,
            ),
            'e-mail' => array(
                'type' => 'text',
                'size' => 35,
            ),
            't-shirt_size' => array(
                'type' => 'select_no_sort',
                'add_null_value_to_top' => true,
                'options' => array(
                    'YS' =>'Youth - Small',
                    'YM' => 'Youth - Medium',
                    'YL' => 'Youth - Large',
                    'AS' => 'Adult - Small',
                    'AM' => 'Adult - Medium',
                    'AL' => 'Adult - Large',
                    'AXL' => 'Adult - X-large',
                )
            ),
            'camp_information_header' => array(
                'type' => 'comment',
                'text' => '<h3>Camps</h3>',
            ),
            'june_6-7' => array(
                'type' => 'checkbox_first',
            ),
            'june_8-10' => array(
                'type' => 'checkbox_first',
            ),
            'june_6-10' => array(
                'type' => 'checkbox_first',
            ),
            'june_13-17' => array(
                'type' => 'checkbox_first',
            ),
            'june_20-24' => array(
                'type' => 'checkbox_first',
            ),
            'june_27-july_1' => array(
                'type' => 'checkbox_first',
            ),
            'july_11-15' => array(
                'type' => 'checkbox_first',
            ),
            'july_18-22_a' => array(
                'type' => 'checkbox_first',
            ),
            'july_18-22_b' => array(
                'type' => 'checkbox_first',
            ),
            'submitter_ip' => 'hidden',
	);

	var $required = array('first_name', 'last_name', 'gender', 'home_phone', 'e-mail', 'address', 'city', 'state_province', 'zip', 'school', 'grade');

	var $error_header_text = 'Please check your form.';

        function on_every_time()
        {
            $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);
        }
	// style up the form and add comments et al
	function pre_show_form()
	{
		echo '<div id="dorianSHCampForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
}
?>