<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    Lucas Welper
//    2011-01-26
//
//    Work on the first page of the dorian camp form
//
////////////////////////////////////////////////////////////////////////////////

class DorianJHCampsOneForm extends FormStep
{
    var $_log_errors = true;
    var $error;
    var $display_name = 'Camper Information';
    var $required = array('first_name', 'last_name', 'gender', 'home_phone', 'e-mail', 'address', 'city', 'state_province', 'zip', 'school', 'grade');
    var $error_header_text = 'Please check your form.';


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
        'home_phone' => array(
            'type' => 'text',
            'size' => 20,
        ),
        'e-mail' => array(
            'type' => 'text',
            'size' => 35,
        ),
        'school' => 'text',
        'grade' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => 'Grade completed by June YEAR',
        ),
        'roomate_requested' => array(
            'type' => 'text',
            'comments' => '<ul><li>Males, one name</li><li>Females, one or two names</li></ul>',
           'display_name' => 'Requested Roomate',
        ),
        'submitter_ip' => 'hidden',
    );

    function on_every_time()
    {
        $this->box_class = 'StackedBox';
        $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);

        $year = date('Y');
        $this->change_element_type('grade', 'text', array('size'=>2, 'display_name'=>'Grade completed by June ' . $year));

    }
    // style up the form and add comments et al
    function pre_show_form()
    {
        echo '<div id="dorianJHCampForm" class="pageOne">'."\n";
    }
    function post_show_form()
    {
        echo '</div>'."\n";
    }
}
?>
