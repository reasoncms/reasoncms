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
                'type' => 'select_no_sort',
                'options' => array('pK'=>'pre-K','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5',
                    '6'=>'6','7'=>'7','8'=>'8','9'=>'9'),
                'add_null_value_to_top' => true,
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
                    for more than one week, as our programming will be different each week. <br><br>Priority registration due by <strong>Friday, April 19.</strong>'
            ),
            'ww_grade_1' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    June 10-11
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Grade 1
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $90',
                'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Wild Wonderers</em>',
            ),
           'ww_grade_2' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    June 12-14
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Grade 2
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $100',
                'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Wild Wonderers</em>',
            ),
            'adventurers' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    June 17-21*
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Grades 3-6
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $150',
				'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Adventurers</em>',
            ),
            'kindernature_1' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    June 24-28
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    pre-K
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $55',
                'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Kindernature</em>'
            ),
            'river_expeditioners' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    June 24-28
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Grades 7-9
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $170',
				'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    River Expeditioners</em>'
            ),
            'energy_camp' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    July 8-12
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Grades 4-7
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $150',
                'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Energy Camp</em>'
            ),
            'survival_camp' => array(
               'type' => 'checkboxfirst',
               'display_name' => '
                    July 8-12
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Grades 5-8
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $175',
               'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Survival Camp</em>'
            ),
            'kindernature_2' => array(
                'type' => 'checkboxfirst',
                'display_name' => '
                    July 15-19
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    pre-K
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    $55',
                'comments' => '<br><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Kindernature</em>'
            ),
            'shuttle_comment' => array(
                'type' => 'comment',
                'text' => '*Shuttle service to be offered from Monona to Decorah for camps during the week of June 17-21.
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

            $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

            $april19 = 109; // April 19 == day 109 (111 on a leap year) on a 0 - 364 scale
                if (date('L')) // if this year is a leap year
                    $april19 = 110;
                                
                if ($date['yday'] > $april19){
                    // $this->set_display_name('ww_grade_1', "June 11-15  Grades 6-9     $190");
                    $this->set_display_name('ww_grade_1', 'June 10-11'.$spaces.'Grade 1'.$spaces.'$105');
                    $this->set_display_name('ww_grade_2', 'June 12-14'.$spaces.'Grade 2'.$spaces.'$115');
                    $this->set_display_name('adventurers', 'June 17-21'.$spaces.'Grades 3-6'.$spaces.'$165');
                    $this->set_display_name('kindernature_1', 'June 24-28'.$spaces.'pre-K'.$spaces.'$70');
                    $this->set_display_name('river_expeditioners', 'June 24-28'.$spaces.'Grades 7-9'.$spaces.'$185');
					$this->set_display_name('energy_camp', 'July 8-12'.$spaces.'Grades 4-7'.$spaces.'$165');
                    $this->set_display_name('survival_camp', 'July 8-12'.$spaces.'Grades 5-8'.$spaces.'$190');
                    $this->set_display_name('kindernature_2', 'July 15-19'.$spaces.'pre-K'.$spaces.'$70');
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