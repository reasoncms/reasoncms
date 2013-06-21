<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');

$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'KboxRequestsForm';

/**
 * This form view sends the content of the form to the kbox for auto ticket 
 * creation. The title of the form is the tilte of the request.
 * 
 * @author Steve Smith
 */

class KboxRequestsForm extends LutherDefaultThorForm
{   
    var $model;
    var $user_id;

    function custom_init()
    {
        $this->model =& $this->get_model();
        $this->user_id = $this->model->get_user_netid();
    }

    function on_every_time(){
        // pray($this->model);
        $this->user_info = $this->model->get_values_for_user();
    }

    function process()
    {
        $body = $this->get_email_txt();

        // $mail = new Email('kbox@help.luther.edu', $username, $username, $this->model->get_form_name(), $body['txt_body'], '');
        $kbox_labels = $this->get_kbox_labels();
        $mail = new Email('kbox@help.luther.edu', $kbox_labels['@submitter'], $kbox_labels['@submitter'], $this->model->get_form_name(), $body['txt_body'], '');
        $mail->send();

        parent::process();
    }

    function get_email_txt()
    {

        $email_values = $this->model->get_values_for_email_submitter_view();

        $values = "\n";
        foreach ($this->get_kbox_labels() as $key => $val)
        {
            $values .= sprintf("\n%s = %s\n", $key, $val);
        }   
        foreach ($email_values as $key => $val)
        {
            $values .= sprintf("\n%s:\n   %s\n", $val['label'], $val['value']);
        }
        // die(pray($this->get_kbox_labels()));
        $html_body = nl2br($values);
        $txt_body = html_entity_decode(strip_tags($html_body));

        $body = array('html_body' => $html_body, 'txt_body' => $txt_body);

        

        return $body;
    }
    /**
     * If the form builder wants to send specific information to the kbox,
     * they can do so be creating a hidden element with the kbox label and the 
     * label of the field which it should send.
     * 
     * For example, if the form looks like this:
     * 
     *      Your Username:  ___________
     *      Who is the responsible party? _______
     *      Date of Event:  __ / __ /__
     * 
     * then the form builder could add some hidden fields to the form to send to the kbox
     * 
     *      Kbox Field: @submitter=Who is the responsible party?
     *      Kbox Field: @due_date=Date of Event
     *      Kbox Field: @impact=high
     *      Kbox Field: @cc_list=Your Username
     * 
     * Notice that the form builder could hardset some values (see the @impact above).
     *  
     */
    function get_kbox_labels()
    {
        $kbox_labels = array(
            'title'         =>  '@title',
            'impact'        =>  '@impact',
            'category'      =>  '@category',
            'status'        =>  '@status',
            'owner'         =>  '@owner',
            'due_date'      =>  '@due_date',
            'cc_list'       =>  '@cc_list',
            'created'       =>  '@created',
            'modified'      =>  '@modified',
            'submitter'     =>  '@submitter', //requester
            'machine'       =>  '@machine', //workstation
            'asset'         =>  '@asset',
            'approval_info' =>  '@approval_info',
            'parent_info'   =>  '@parent_info', //parent ticket
            'see_also'      =>  '@see_also',
            'referrers'     =>  '@referrers',
            'priority'      =>  '@priority',
            'custom_1'      =>  '@custom_1', //software development queue
            'custom_2'      =>  '@custom_2', //lis team
            'custom_3'      =>  '@custom_3', //website url
            'custom_5'      =>  '@custom_5', //bomgar session (non-production - do not use!)
            'resolution'    =>  '@resolution');

        // get all the hidden elements
        $hidden_elements = array();
        foreach ($this->get_element_names() as $element) {
            if ($this->element_is_hidden($element)){
                $hidden_elements[$element] = $this->get_value($element); 
            }
        }

        $kbox_labels_for_email = array();
        // of the hidden elements, find the ones with kbox labels
        foreach ($hidden_elements as $hidden_element => $value){
            $piece = explode( '=', $value );
            $at_label = strtolower(trim($piece[0]));
            $at_value = trim($piece[1]);
            /* if there is a valid @kbox_label, check if the 
             * value after the '=' is a thor_label grab the value
             * of that label, otherwise use the content after the 
             * '='.
             */
            if (in_array($at_label, $kbox_labels) && $this->get_element_name_from_label($at_value) ){
                $kbox_labels_for_email[$at_label] = $this->get_value_from_label($at_value);
            } else {
                $kbox_labels_for_email[$at_label] = $at_value;
            }

        }

        // get the kbox submitter if "set" by the form creator (based on the 
        // answers of the form submitter), otherwise the form submitter is the 
        // kbox submitter
        if (array_key_exists('@submitter',$kbox_labels_for_email)){
            $username = $kbox_labels_for_email['@submitter'];
        } else {
            $username = $this->user_id;
        }
        $kbox_labels_for_email['@submitter'] = $username;
        
        return $kbox_labels_for_email;
    }
}