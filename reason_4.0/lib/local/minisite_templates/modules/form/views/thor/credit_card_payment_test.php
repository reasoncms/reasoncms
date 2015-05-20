<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'reason/local/stock/pfproclass.php'); //<<<< Change this
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'CreditCardTestThorForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */

class CreditCardTestThorForm extends CreditCardThorForm
{
    function on_every_time()
    {
        parent::on_every_time();
        $this->is_in_testing_mode = true;
    }
}
?>