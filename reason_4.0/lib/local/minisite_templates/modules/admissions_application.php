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
                    'ApplicationPageOne' => array(
                        'label' => 'One',
                    ),
                    'ApplicationPageTwo' => array(
                        'label' => 'Two',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Three',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Four',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Five',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Six',
                    ),
                    'ApplicationConfirmation' => array(
                        'label' => 'Submit Your Application'
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageTwo' => array(
                'next_steps' => array(
                    'ApplicationPageOne' => array(
                        'label' => 'One',
                    ),
                    'ApplicationPageTwo' => array(
                        'label' => 'Two',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Three',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Four',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Five',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Six',
                    ),
                    'ApplicationConfirmation' => array(
                        'label' => 'Submit Your Application'
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageThree' => array(
                'next_steps' => array(
                    'ApplicationPageOne' => array(
                        'label' => 'One',
                    ),
                    'ApplicationPageTwo' => array(
                        'label' => 'Two',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Three',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Four',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Five',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Six',
                    ),
                    'ApplicationConfirmation' => array(
                        'label' => 'Submit Your Application'
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageFour' => array(
                'next_steps' => array(
                    'ApplicationPageOne' => array(
                        'label' => 'One',
                    ),
                    'ApplicationPageTwo' => array(
                        'label' => 'Two',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Three',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Four',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Five',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Six',
                    ),
                    'ApplicationConfirmation' => array(
                        'label' => 'Submit Your Application'
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageFive' => array(
                'next_steps' => array(
                    'ApplicationPageOne' => array(
                        'label' => 'One',
                    ),
                    'ApplicationPageTwo' => array(
                        'label' => 'Two',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Three',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Four',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Five',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Six',
                    ),
                    'ApplicationConfirmation' => array(
                        'label' => 'Submit Your Application'
                    ),
                ),
                'step_decision' => array(
                    'type' => 'user',
                ),
            ),
            'ApplicationPageSix' => array(
                'next_steps' => array(
                    'ApplicationPageOne' => array(
                        'label' => 'One',
                    ),
                    'ApplicationPageTwo' => array(
                        'label' => 'Two',
                    ),
                    'ApplicationPageThree' => array(
                        'label' => 'Three',
                    ),
                    'ApplicationPageFour' => array(
                        'label' => 'Four',
                    ),
                    'ApplicationPageFive' => array(
                        'label' => 'Five',
                    ),
                    'ApplicationPageSix' => array(
                        'label' => 'Six',
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
            $head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/giftform.css');
            $head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/admissions_application.2.js');
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
            $this->sess =& get_reason_session();
            $openid_id = $this->sess->get('openid_id');

            if(preg_match('/ApplicationConfirmation$/', get_current_url()) == 0){
                //if on confirmation page, don't show navigation
                echo $this->generate_navigation();
            }

            $this->controller->set_request($this->request);
            $this->controller->run();

            if(preg_match('/ApplicationConfirmation$/', get_current_url()) == 0){
                //if on confirmation page, don't show navigation
                echo $this->generate_navigation(2);
            }
        }
    }
    function generate_navigation($nav_group=1) {
        $this->sess =& get_reason_session();
        if(!$this->sess->get('openid_id')){
                return;
        }

        if($nav_group==1){
            $output = '<div id="formNavigation">';
        }else{
            $output = '<div id="formNavigation' . $nav_group . '">';
        }
            $output .= '<ul class="formSteps">';
        
        $error_div = "";
        $i = 0;
        foreach ($this->controller->forms as $name => $form) {
            $i++;
            $class = 'formStep';
            if (isset($form->display_name)) {

                /* Highlight incomplete tabs if app was submitted */
                $app_submitted = $this->sess->get('submitted');
                //$app_submitted = True;
                //changed to generate required field list on every page before being submitted
                if ($app_submitted){
//                    $error_header = "<div style='width:655px;border:1px solid red;border-radius:5px;background-color:#FFB2B2;padding:5px;'><span style='font-weight:bold;'>Required fields:</span>&nbsp;&nbsp;";
                    $error_header = "<div style='width:655px;border:1px solid black;border-radius:5px;back`ground-color:#afd0ef;padding:5px;'><span style='font-weight:bold;'>Required fields:</span>&nbsp;&nbsp;";
                    $error_footer = "</div>";
                    switch($i){
                        case 1:
                            $validation = validate_page1($form);
                            break;
                        case 2:
                            $validation = validate_page2($form);
                            break;
                        case 3:
                            $validation = validate_page3($form);
                            break;
                        case 4:
                            $validation = validate_page4($form);
                            break;
                        case 5:
                            $validation = validate_page5($form);
                            break;
                        case 6:
                            $validation = validate_page6($form);
                            break;
                    }
                    if (!$validation['valid']){
                        $class .= ' error';
                        if ($this->controller->get_current_step() == $name){
                            $error_div = $error_header;
                            foreach($validation as $val_key => $val_value){
                                $error_div .= " <a href='#" . $val_key . "_error'>" . $val_value . "</a>&nbsp;&nbsp; ";
                            }
                            $error_div .= $error_footer;
                        }
                    }
                }
                
                if ($this->controller->get_current_step() == $name)
                    $class .= ' current';

                $output .= '<li class="' . $class . '"><a href="?_step=' . $name . '">' . htmlspecialchars($form->display_name) . '</a></li>';
            }
        }
        $output .= '</ul></div>';
//        if($nav_group==1){
//            $output .= $error_div;
//        }
//        $submitted = is_submitted($this->openid_id);
//        if(!$submitted){
            if($nav_group==1){
            $output .= '<div id="requiredNote" style="float:right">* = required</div>';
            }
//        }
                
        return $output;
    }
}
?>
