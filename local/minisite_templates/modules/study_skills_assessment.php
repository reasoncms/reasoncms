<?php

//reason_include_once( 'minisite_templates/modules/default.php' );

//include_once(DISCO_INC . 'disco.php');

//$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'StudySkillsAssessment';

class StudySkillsAssessmentForm extends Disco {
    
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
		'sss_header' => array(
			'type' => 'comment',
			'text' => '<h3>TESTT1</h3>',
		)
            );
        
        
        
 
}

//class StudySkillsAssessmentModule extends DefaultMinisiteModule {
 
//}
    $my_form = new StudySkillsAssessmentForm();

    $my_form->run();




?>
