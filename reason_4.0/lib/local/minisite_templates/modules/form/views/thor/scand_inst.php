<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'reason/local/stock/pfproclass.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'ScandInstThorForm';

class ScandInstThorForm extends CreditCardThorForm {

    function on_every_time() {
        parent::on_every_time();

        $expense_element = $this->get_element_name_from_label('Expense Budget Number');
        $revenue_element = $this->get_element_name_from_label('Revenue Budget Number');

        // changing expense numbers for ofs reporting
        // 5/17/2011
        $this->set_value($expense_element, '10-000-08504-12121');
        $this->set_value($revenue_element, '10-000-08504-22000');
    }
}
?>