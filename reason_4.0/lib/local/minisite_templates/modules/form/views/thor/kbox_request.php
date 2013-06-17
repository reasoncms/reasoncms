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

    function process()
    {
        // kbox@helpdesk.luther.edu
        $model =& $this->get_model();
        $user_info = $model->get_values_for_user();
        $username = $user_info[1]['submitted_by'];
        $body = $this->get_email_txt();

        $mail = new Email('kbox@help.luther.edu', $username, $username, $model->get_form_name(), $body['txt_body'], '');
        $mail->send();

        parent::process();
    }

    function get_email_txt()
    {
        $model =& $this->get_model();

        $email_values = $model->get_values_for_email_submitter_view();

        $values = "\n";
        foreach ($email_values as $key => $val)
        {
            $values .= sprintf("\n%s:\n   %s\n", $val['label'], $val['value']);
        }        
        
        $html_body = nl2br($values);
        $txt_body = html_entity_decode(strip_tags($html_body));

        $body = array('html_body' => $html_body, 'txt_body' => $txt_body);

        

        return $body;
    }
}