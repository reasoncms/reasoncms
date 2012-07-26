<?php

/**
 * Discovery Camps Module
 *
 * @author Steve Smith
 * @since 2011-01-26
 * @package Local Modules
 */
/**
 * needs default module
 */
reason_include_once('minisite_templates/modules/default.php');


$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'StudySkillsAssessmentModule';

class StudySkillsAssessmentModule extends DefaultMinisiteModule {

    /**
     * Before we clean the request vars, we need to init the controller so we know what we're initing
     */
    function pre_request_cleanup_init() {
        include_once( DISCO_INC . 'controller.php' );
        reason_include_once('minisite_templates/modules/study_skills_assessment/page1.php');
        reason_include_once('minisite_templates/modules/study_skills_assessment/page2.php');

        $this->controller = new FormController;
        $this->controller->set_session_class('Session_PHP');
        $this->controller->set_session_name('REASON_SESSION');
        $this->controller->set_data_context('study_skills_assessment');
        $this->controller->show_back_button = false;
        $this->controller->clear_form_data_on_finish = true;
        $this->controller->allow_arbitrary_start = true;

        $forms = array(
            'StudySkillsAssessmentOneForm' => array(
                'next_steps' => array('StudySkillsAssessmentTwoForm' => array('label' => 'Submit'),
                ),
                'step_decision' => array('type' => 'user'),
            ),
            'StudySkillsAssessmentTwoForm' => array(
                'final_step' => true,
                'final_button_text' => 'Done'
            )
        );
        $this->controller->add_forms($forms);
        $this->controller->init();
    }

    function init($args = array()) { //{{{
        parent::init($args);
        if ($head_items = & $this->get_head_items()) {
            $head_items->add_stylesheet('/reason/css/studyskillsform.css');
        }
    }

    /**
     * Set up the request for the controller and run the sucker
     * @return void
     */
    function run() {
        $this->controller->run();
    }
}

?>
