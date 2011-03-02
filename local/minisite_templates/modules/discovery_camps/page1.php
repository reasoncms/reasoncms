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
            'grade' => array(
                'type' => 'numrange',
                'start' => 1,
                'end' => 12,
            ),
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
                'default' => 'IA'
            ),
            'zip' => array(
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
            'camp_information_header' => array(
                'type' => 'comment',
                'text' => '<h3>Camps</h3>',
            ),
            'camp_comment' => array(
                'type' => 'comment',
                'text' => 'The following weeks are available as indicated for each grade level. Campers may register
                    for more than one week, as our programming will be different each week.'
            ),
            'camp_1' => array(
                'type' => 'checkboxfirst',
                'display_name' => "June 6-7   Grade 1     $85",
            ),
           'camp_2' => array(
                'type' => 'checkboxfirst',
                'display_name' => "June 8-10  Grade 2   $95"
            ),
            'camp_3' => array(
                'type' => 'checkboxfirst',
                'display_name' => "June 6-10  Grades 7-9     $170",
                'comments' => '<br><em>Adventure Hunt</em>'
            ),
            'camp_4' => array(
                'type' => 'checkboxfirst',
                'display_name' => "June 13-17 Grades 3-6  $145"
            ),
            'camp_5' => array(
                'type' => 'checkboxfirst',
                'display_name' => "June 20-24 Grades 5-8  $170",
                'comments' => '<br><em>Survival Camp</em>'
            ),
            'camp_6' => array(
                'type' => 'checkboxfirst',
                'display_name' => "June 27-July 1 Grades 9-12 $150",
                'comments' => '<br><em>Local Food Warriors</em>'
            ),
            'camp_7' => array(
                'type' => 'checkboxfirst',
                'display_name' => "July 11-15 Grades 3-6 $145",
            ),
            'camp_8' => array(
                'type' => 'checkboxfirst',
                'display_name' => "July 18-22** Grades 6-9 $170",
                'comments' => '<br><em>Expeditioners</em>'
            ),
            'camp_9' => array(
                'type' => 'checkboxfirst',
                'display_name' => "July 18-22** Grades 6-9 $150",
                'comments' => '<br><em>The Edible Earth</em>'
            ),
            'shuttle_comment' => array(
                'type' => 'comment',
                'text' => '**Shuttle service to be offered from Monona to Decorah for camps during the week of July 18-22.
                    Register early as spots are limited! Other transportation options may be available&emdash;contact <a href="mailto:
                    nealem01@luther.edu">Emily Neal</a> for information.'
            ),
            'submitter_ip' => 'hidden',
	);

	var $required = array('first_name', 'last_name', 'gender', 'home_phone', 'e-mail', 
            'address', 'city', 'state_province', 'zip', 'age', 'grade', 't-shirt_size', 'parent_guardian_name',
            'work_phone');

	var $error_header_text = 'Please check your form.';

        function on_every_time()
        {
            $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);

            $date = getdate();
            $this->add_comments('grade', 'Fall '.$date['year'], 'after');

             $april15 = 104; // April 15 == day 104 (105 on a leap year) on a 0 - 364 scale
                if (date('L')) // if this year is a leap year
                    $june1 = 105;
                                
                if ($date['yday'] > $april15){
                    $this->set_display_name('camp_1', "June 6-7   Grade 1     $100");
                    $this->set_display_name('camp_2', "June 8-10  Grade 2   $110");
                    $this->set_display_name('camp_3', "June 6-10  Grades 7-9     $185");
                    $this->set_display_name('camp_4', "June 13-17 Grades 3-6  $160");
                    $this->set_display_name('camp_5', "June 20-24 Grades 5-8  $185");
                    $this->set_display_name('camp_6', "June 27-July 1 Grades 9-12 $165");
                    $this->set_display_name('camp_7', "July 11-15 Grades 3-6 $160");
                    $this->set_display_name('camp_8', "July 18-22 Grades 6-9 $185");
                    $this->set_display_name('camp_9', "July 18-22 Grades 6-9 $165");
                }
        }
	// style up the form and add comments et al
	function pre_show_form()
	{
		echo '<div id="discoveryCampsForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
}
?>