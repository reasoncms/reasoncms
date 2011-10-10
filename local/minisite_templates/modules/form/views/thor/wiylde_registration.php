<?php

include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once('disco/boxes/boxes.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'WiyldeRegistrationForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */
class WiyldeRegistrationForm extends DefaultThorForm {

		function where_to() {
				parent::where_to();
				$payment_amount = $this->get_element_name_from_label('Payment Amount');
				if ($payment_amount != 'I am sending a check' || $payment_amount != 'My church is paying the full amount'){
						$url = '';
				}
				return $url;
		}

}

?>
