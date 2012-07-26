<?
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');

include_once(WEB_PATH . 'stock/pfproclass.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'LucasViewThorForm';

class LucasViewThorForm extends LutherDefaultThorForm {

    function on_every_time() {
        parent::on_every_time();

        $herp_element = $this->get_element_name_from_label('herp');

        $this->change_element_type($herp_element, 'commentWithLabel', array('text'=>'Herpedee'));

        $this->add_element('blah', 'country');

        $this->set_display_name($herp_element, 'blong');
        
    }
}
?>