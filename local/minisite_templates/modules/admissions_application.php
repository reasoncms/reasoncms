<?php

/**
 * Admissions Application Module
 *
 * @author Steve Smith
 * @author Lucas Welper
 * @since 2011-02-11
 * @package MinisiteModule
 */
/**
 * needs default module
 */
reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'AdmissionsApplicationModule';

/**
 * Run the online gift.
 * @author Steve Smith
 * @package MinisiteModule
 */
class AdmissionsApplicationModule extends DefaultMinisiteModule {

    var $acceptable_params = array(
        'kiosk_mode' => false,
    );

    /**
     * Before we clean the request vars, we need to init the controller so we know what we're initing
     */
    function pre_request_cleanup_init() {
        include_once( DISCO_INC . 'controller.php' );
        reason_include_once('minisite_templates/modules/admissions_application/page1.php');
        reason_include_once('minisite_templates/modules/admissions_application/page2.php');
        reason_include_once('minisite_templates/modules/admissions_application/page3.php');
        reason_include_once('minisite_templates/modules/admissions_application/page4.php');
        reason_include_once('minisite_templates/modules/admissions_application/page5.php');
        reason_include_once('minisite_templates/modules/admissions_application/page6.php');
        reason_include_once('minisite_templates/modules/admissions_application/confirmation.php');

        $this->controller = new FormController;
        $this->controller->set_session_class('Session_PHP');
        $this->controller->set_session_name('REASON_SESSION');
        $this->controller->set_data_context('admissions_application');
        $this->controller->show_back_button = false;
        $this->controller->clear_form_data_on_finish = true;
        $this->controller->allow_arbitrary_start = true;
        
        $forms = array(
            'ApplicationPageOne' => array(
                'next_steps' => array(
                    'ApplicationPageTwo' => array(
                        'label' => 'Next',
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageTwo' => array(
                'next_steps' => array(
                    'ApplicationPageOne' => array(
                        'label' => 'Previous',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Next',
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageThree' => array(
                'next_steps' => array(
                    'ApplicationPageTwo' => array(
                        'label' => 'Previous',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Next',
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageFour' => array(
                'next_steps' => array(
                    'ApplicationPageThree' => array(
                        'label' => 'Previous',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Next',
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageFive' => array(
                'next_steps' => array(
                    'ApplicationPageFour' => array(
                        'label' => 'Previous',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Next',
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageSix' => array(
                'next_steps' => array(
                    'ApplicationPageFive' => array(
                        'label' => 'Previous',
                    ),
                    'ApplicationConfirmation' => array(
                        'label' => 'Submit Your Application'
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user'
                ),
            ),
            'ApplicationConfirmation' => array(
                'final_step' => true,
            )
        );
        $this->controller->add_forms($forms);
        $this->controller->init();
    }

    /**
     * Add possible forms variables that may come through to the list of vetted request vars
     * @return void
     */
    function get_cleanup_rules() {
        $rules = array();
        // debug var - resets form and destroys session
        $rules['ds'] = array('function' => 'turn_into_string');
        // vars for confirmation page to let through
        $rules['r'] = array('function' => 'turn_into_string');
        $rules['h'] = array('function' => 'turn_into_string');
        // Allows form to be put into testing mode through a query string
        $rules['tm'] = array('function' => 'turn_into_int');
        // add all cleanup rules from the form controller
        $rules = array_merge($rules, $this->controller->get_cleanup_rules());
        return $rules;
    }
    /**
     * For kiosk mode; set a timeout on the second and third pages.
     * @return void
     */
    function init($args = array()) { // {{{
        parent::init($args);
//        $this->actions = array_reverse($this->actions, true);
        $url = get_current_url();
        $parts = parse_url($url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        // Handle session reset
        if (!empty($this->request['ds'])) {

            $this->controller->session->destroy();
            header("Location: " . $url);
            return;
        }
        
        if ($head_items = & $this->get_head_items()) {
            $head_items->add_stylesheet('/reason/jquery-ui-1.8.12.custom/css/redmond/jquery-ui-1.8.12.custom.css');
            $head_items->add_stylesheet('/reason/css/giftform.css');
            $head_items->add_javascript('/reason/js/admissions_application.2.js');
            
        }
        // Insert refresh headers when in kiosk mode
        if ($this->params['kiosk_mode']) {
            // There must be a better way to suppress the session expired notice when timing out
            $this->controller->sess_timeout_msg = '';

            if (isset($this->request['_step']) && $this->request['_step'] == 'ApplicationPageThree')
                $seconds = 300;
            elseif (!empty($this->request['r']) AND !empty($this->request['h']))
                $seconds = 60;
            else
                return;

            $this->parent->add_head_item('meta', array('http-equiv' => 'refresh', 'content' => $seconds . ';URL=' . $url . '?ds=1'));
        }
    }
    /**
     * Set up the request for the controller and run the sucker
     * @return void
     */
    function run() { // {{{
        if (!empty($this->request['r']) AND !empty($this->request['h'])) {
            reason_include_once('minisite_templates/modules/admissions_application/application_confirmation.php');
            $ac = new ApplicationConfirmation;
            $ac->set_ref_number($this->request['r']);
            $ac->set_hash($this->request['h']);
            if ($ac->validates()) {
                echo $ac->get_confirmation_text();
            } else {
                echo $ac->get_error_message();
            }
            // MUST reconnect to Reason database.  GiftConfirmation connects to reason_gifts for info.
            connectDB(REASON_DB);
        } else {
            echo $this->generate_navigation();

            $this->controller->set_request($this->request);
            $this->controller->run();
        }
    }
    function generate_navigation() {
        $output = '<div id="formNavigation">';
        $output .= '<ul class="formSteps">';
        $i = 0;
        foreach ($this->controller->forms as $name => $form) {
            $i++;
            $class = 'formStep';
            if (isset($form->display_name)) {

                $this->sess =& get_reason_session();
                

                switch($i){
                    case 1:
                        if (!validate_page1()){ $class .= ' error'; }
                        break;
                    case 2:
                        if (!validate_page2()){ $class .= ' error'; }
                        break;
                    case 3:
                        if (!validate_page3()){ $class .= ' error'; }
                        break;
                    case 4:
                        if (!validate_page4()){ $class .= ' error'; }
                        break;
                    case 5:
                        if (!validate_page5()){ $class .= ' error'; }
                        break;
                    case 6:
                        if (!validate_page6()){ $class .= ' error'; }
                        break;
                }
                
                if ($this->controller->get_current_step() == $name)
                    $class .= ' current';

                $output .= '<li class="' . $class . '"><a href="?_step=' . $name . '">' . htmlspecialchars($form->display_name) . '</a></li>';
            }
        }
        $output .= '</ul></div>';
        return $output;
    }
}
?>