<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');

$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'KboxRequestsForm';

/**
 * 
 * @author Steve Smith
 */

class KboxRequestsForm extends LutherDefaultThorForm
{   
    var $email_body = '';

    function process()
    {
        // send an email to helpdesk for auto ticket creation
        // kbox@helpdesk.luther.edu
        $txt = "Software Development Emergency Auto-Generate";
        $txt .= "\n";
        $txt .= $this->get_value_from_label('Emergency');
        $hd_mail = new Email('kbox@help.luther.edu', $this->get_value_from_label('Username'), $this->get_value_from_label('Username'), $this->get_value_from_label('Emergency'), $txt, $txt);
        $hd_mail->send();
    }

    function get_email_txt()
    {
    
        $values = $this->get_values();
        $this->email_body .= '';
    }
}