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
                    for more than one week, as our programming will be different each week. Priority registration due by Friday, April 20.'
            ),
			'adventure_hunt' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'June 11-15  Grades 6-9     $175',
                'comments' => '<br><em>Adventure Hunt</em>'
            ),
            'grade_1' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'June 11-12   Grade 1     $90',
            ),
           'grade_2' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'June 13-15  Grade 2   $100'
            ),
            'adventurers' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'June 18-22 Grades 3-6  $150',
				'comments' => '<br><em>Adventurers</em>',
            ),
//            'survival_camp' => array(
//                'type' => 'checkboxfirst',
//                'display_name' => 'June 25-29 Grades 5-8  $175',
//                'comments' => '<br><em>Survival Camp</em>'
//            ),
            'energy_expedition' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'July 9-13 Grades 4-6 $150',
				'comments' => '<br><em>Energy Expedition Camp</em>'
            ),
			'edible_earth' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'July 16-20** Grades 2-5 $160',
                'comments' => '<br><em>The Edible Earth</em>'
            ),
            'expeditioners' => array(
                'type' => 'checkboxfirst',
                'display_name' => 'July 16-20** Grades 7-9 $170',
                'comments' => '<br><em>Expeditioners</em>'
            ),
            'shuttle_comment' => array(
                'type' => 'comment',
                'text' => '**Shuttle service to be offered from Monona to Decorah for camps during the week of July 16-20.
                    Register early as spots are limited! Other transportation options may be available&mdash;contact <a href="mailto:
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

             $april20 = 110; // April 20 == day 110 (111 on a leap year) on a 0 - 364 scale
                if (date('L')) // if this year is a leap year
                    $june1 = 111;
                                
                if ($date['yday'] > $april20){
                    $this->set_display_name('adventure_hunt', "June 11-15  Grades 6-9     $190");
                    $this->set_display_name('grade_1', "June 11-12   Grade 1     $105");
                    $this->set_display_name('grade_2', "June 13-15  Grade 2   $115");
                    $this->set_display_name('adventurers', "June 18-22 Grades 3-6  $165");
//                    $this->set_display_name('survival_camp', "June 25-29 Grades 5-8  $190");
                    $this->set_display_name('energy_expedition', "July 9-13 Grades 4-6 $165");
					$this->set_display_name('edible_earth', "July 16-20** Grades 2-5 $175");
                    $this->set_display_name('expeditioners', "July 16-20** Grades 7-9 $185");
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