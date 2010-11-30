<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'stock/pfproclass.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'SwimmingCampThorForm';

class SwimmingCampThorForm extends CreditCardThorForm
{				
	function on_every_time()
	{
		parent::on_every_time();
		
		$p_element = $this->get_element_name_from_label('payment_amount');
		
		$this->change_element_type($p_element,'radio_no_sort', array(
			'display_name' => 'Camper Type',
			'options' => array(
				'$'.number_format(385,2,'.','')=>'Full Resident - $385.00 (All meals, housing, materials)',
				'$'.number_format(240,2,'.','')=>'Commuter - $240.00 (materials only - no meals)')
			)
		);
	}
}