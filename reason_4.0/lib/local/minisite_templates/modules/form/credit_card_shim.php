<?php

class creditCardShim
{
	
	function init_form(&$disco)
	{
		$model =& $disco->get_model();
		$head_items = $model->get_head_items();
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/disable_submit.js');
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'local/luther_2014/javascripts/creditcard.js');
		
	}

	function show_credit_card(&$disco)
	{
		$disco->add_element('payment_amount', 'hidden');
		$disco->add_element('payment_note', 'comment', array('text' => '<h3>Payment Method</h3>'));
		$disco->add_element('credit_card_number', 'text', array('size' => 35));
		$disco->add_element('credit_card_type', 'radio_no_sort', array('options' => array('Visa'=>'Visa','MasterCard'=>'MasterCard','American Express'=>'American Express','Discover'=>'Discover','none'=>'none')));
		$disco->add_element('credit_card_type_icon', 'comment', array('text' => "<i class='fa fa-cc-visa formCCType' id='visaIcon'></i><i class='fa fa-cc-mastercard formCCType' id='mastercardIcon'></i><i class='fa fa-cc-amex formCCType' id='amexIcon'></i><i class='fa fa-cc-discover formCCType' id='discoverIcon'></i>"));
		$disco->add_element('credit_card_expiration_month', 'month', array('display_name' => 'Expiration Month'));
		$disco->add_element('credit_card_expiration_year', 'text');
		$disco->add_element('credit_card_security_code', 'text', array('size' => 4));
		$disco->set_comments('credit_card_security_code', form_comment('The card security code is located on the back of MasterCard, Visa and Discover credit or debit cards and is typically a separate group of 3 digits to the right of the signature strip. On American Express cards, the card security code is a printed, not embossed, group of four digits on the front towards the right.'));
		
		$disco->add_element('credit_card_name', 'text', array('display_name' => 'Name as it appears on card', 'size' => 35));
		// Currently the billing address is not needed since a product is not being delivered.
		//$disco->add_element('billing_address', 'radio_no_sort', array('display_name' => 'Billing Address', 'default' => 'entered', 'options' => array('entered' => 'Use address provided on previous page', 'new' => 'Use a different address')));
		//$disco->add_element('billing_street_address', 'textarea', array('display_name' => 'Street Address', 'rows' => 3, 'cols' => 35));
		//$disco->add_element('billing_city', 'text', array('display_name' => 'City', 'size' => 35));
		//$disco->add_element('billing_state_province', 'state_province', array('display_name' => 'State/Province', 'include_military_codes' => true));
		//$disco->add_element('billing_zip', 'text', array('display_name' => 'Zip/Postal Code', 'size' => 35));
		//$disco->add_element('billing_country', 'country', array('display_name' => 'Country'));
		$disco->add_element('confirmation_text', 'hidden');
		$disco->add_element('result_refnum', 'hidden');
		$disco->add_element('result_authcode', 'hidden');
		
		$disco->add_required('payment_amount');
		$disco->add_required('credit_card_type');
		$disco->add_required('credit_card_number');
		$disco->add_required('credit_card_expiration_month');
		$disco->add_required('credit_card_expiration_year');
		$disco->add_required('credit_card_security_code');
		$disco->add_required('credit_card_name');
		//$disco->add_required('billing_address');
		
		$year = date('Y');
		$disco->change_element_type('credit_card_expiration_year', 'numrange', array('start' => $year, 'end' => $year + 15, 'display_name' => 'Expiration Year'));
		
		
	}

}

?>