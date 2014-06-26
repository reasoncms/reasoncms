<?
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
include_once(WEB_PATH.'stock/pfproclass.php'); //<<<< Change this
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'CreditCardThorForm';


/**
 * @todo  ! take out Scott's code regarding Item List, etc. If this is done take change how
 * getdowngiveback is handles
  * /

/**
 * CreditCardThorForm provides a simple method for adding credit card processing to a Thor form
 *
 * Various special fields can be used in the Thor form to pass data to the credit card processor:
 *
 * - Payment Amount
 *    (If hidden)  Payment value will be displayed but not editable
 *    (If visible) Will be used as defined by the form
 *    (If absent)  A simple text-entry field will be generated for the user to enter an amount
 *
 * - Budget Number
 *    (required)  Must be present and preset to a 10-0000-0000-0000 format budget number
 *****Changed Budget Number to two seperate numbers, expense and revenue, for Luther specific billing needs
 *****0818201 Steve Smith
 *
 * - Confirmation Sender
 *    (optional)  If present, the contents of this field will be used as the sender for the 
 *                confirmation message. If absent, Email of Recipient in the content manager will be
 *                used. If that's not set, a generic address will be used.
 *
 * - Confirmation Subject
 *    (optional)  If present, the contents of this field will be used as the subject for the 
 *                confirmation message.
 *
 * This view also makes use of other thor form settings:
 *
 * - Email of Recipient (if defined) will be used as the return address for confirmation emails (first 
 *                address only)
 * - Thank You Message will be used as the introductory text for confirmation emails
 *
 * - The form name is used in the transaction record, so please use descriptive naming
 *
 * @package reason_package_local
 * @subpackage thor_view
 * @author Mark Heiman
 * @author Steve Smith
 * 
 */

class CreditCardThorForm extends LutherDefaultThorForm
{
	var $_log_errors = true;
	var $no_session = array( 'credit_card_number' );
	var $database_transformations = array('credit_card_number'=>'obscure_credit_card_number',);
	var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer
	var $payment_element;
	//var $budget_number_element; 
	var $expense_budget_number;
	var $revenue_budget_number;
	var $transaction_comment;
	var $pfresult;  // added so child classes could reference the results

	
	var $elements = array(
		'payment_note' => array(
			'type' => 'comment',
			'text' => '<strong>Payment Method</strong>',
		),
		'payment_amount' => array(
			'type' => 'text',
			'size'=>10,
			'display_name'=>'Payment Amount Placeholder',
		),
		'credit_card_type' => array(
			'type' => 'radio_no_sort',
			'options' => array('Visa'=>'Visa','MasterCard'=>'MasterCard','American Express'=>'American Express','Discover'=>'Discover'),
		),
		'credit_card_number' => array(
			'type' => 'text',
			'size'=>35,
		),
		'credit_card_expiration_month' => array(
			'type' => 'month',
			'display_name' => 'Expiration Month',
		),
		'credit_card_expiration_year' => array(
			'type' => 'numrange',
			'start' => 2020,
			'end' => 2020,
			'display_name' => 'Expiration Year',
		),
		'credit_card_name' => array(
			'type' => 'text',
			'display_name' => 'Name as it appears on card',
			'size'=>35,
		),
		'billing_street_address' => array(
			'type' => 'textarea',
			'rows' => 2,
			'cols' => 35,
			'display_name' => '<nobr>Billing Street Address</nobr>',
		),
		'billing_city' => array(
			'type' => 'text',
			'size'=>35,
			'display_name' => 'Billing City',
		),
		'billing_state_province' => array(
			'type' => 'state_province',
			'display_name' => 'Billing State/Province',
		),
		'billing_zip' => array(
			'type' => 'text',
			'display_name' => 'Billing Zip/Postal Code',
			'size'=>35,
		),
		'billing_country' => array(
			'type' => 'text',
			'default' => 'United States',
			'size'=>35,
			'display_name' => 'Billing Country',
		),
		'confirmation_text' => array(
			'type' => 'hidden',
		),
		'result_refnum' => array(
			'type' => 'hidden',
		),
		'result_authcode' => array(
			'type' => 'hidden',
		),
	);
	var $required = array(
		'payment_amount',
		'credit_card_type',
		'credit_card_number',
		'credit_card_expiration_month',
		'credit_card_expiration_year',
		'credit_card_name',
		'billing_street_address',
		'billing_city',
		'billing_zip',
		'billing_state_province',


	);

	function custom_init()
	{
	  $model =& $this->get_model();
	  $head_items = $model->get_head_items();
	  $head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/disable_submit.js');
	}
			
	
	function on_every_time()
	{
		
		parent :: on_every_time();
		
		// Don't take credit cards on an unencrypted connection!+
		if( !on_secure_page() )
		{		
		
			header( 'Location: '.get_current_url( 'https' ) );
			exit;
		}
		







		// If we have a field called Item List, then make it and the payment amount read only
		if ( strlen($this->get_element_name_from_label('Item List')) > 0 )
		{
			$this->change_element_type('payment_amount', 'solidtext');
			$this->change_element_type($this->get_element_name_from_label('Item List'), 'solidtext');
		}


		// Scott Bassford 7/6/2009 - if we have a field called "Item List" then prepopulate fields, if no data then push the to the first page somehow
		if ($this->budget_number_element = $this->get_element_name_from_label('Item List'))
		{
			

			if ( !isset( $_POST['ccprepopulate'] ) && ( !isset( $_POST['credit_card_name'] )))  { header("Location: /getdowngiveback/register/"); echo 'Redirecting to list.'; die ; }

			if ( isset( $_POST['ccprepopulate'] ))	
			{
						$this->set_value($this->get_element_name_from_label('Item List'), $_POST['ccpaymentdetail']);
						$this->set_value('payment_amount', $_POST['ccpaymentamount']);
						$this->change_element_type('payment_amount', 'solidtext');
						$this->change_element_type($this->get_element_name_from_label('Item List'), 'solidtext');
			}
			

		}
		//if (!preg_match('/\d{2}-\d{4}-\d{4}-\d{4}/', $this->get_value($this->budget_number_element)))
		//	{	
		//		$this->set_error('credit_card_type','Form Setup Error: Hidden "Budget Number" field must contain a number in the form: 10-0000-0000-0000');
		//	}
		//} else {
		//	$this->set_error('credit_card_type','Form Setup Error: Hidden "Budget Number" field is required in Reason form.');		
		//}









		// Turn on test mode when appropriate
		// if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) )
		// {
			$this->is_in_testing_mode = true;
		// }
		// else
		// {
		// 	$this->is_in_testing_mode = false;
		// }

                $user = reason_check_authentication();
                if ($user == 'smitst01'){
                    $this->is_in_testing_mode = true;
                }



		// If the form creator added a visible Payment Amount field of their own, remove the
		// placeholder field from the payment section.  If they added a hidden field to
		// pass a single payment amount, make that the value of the placeholder field,
		// rename it, and make it uneditable. If they didn't add a Payment
		// Amount field, rename and use the placeholder one.
		
		/**
		 * @todo  ! ignore case when looking for payment amount
		 */
		if ($this->payment_element = $this->get_element_name_from_label('Payment Amount'))
		{
			$type = $this->get_element_property($this->payment_element, 'type');
			if ($type == 'hidden')
			{
				$payment_value = $this->get_value($this->payment_element);
				// Make sure there's a number in the payment amount value
				if (preg_match('/([\d\.,]+)/',$payment_value, $match))
				{
					$this->change_element_type('payment_amount', 'solidtext');
					$this->set_value('payment_amount', '$'.$match[1]);
					$this->set_display_name('payment_amount', 'Payment Amount');
				} else {
					$this->set_error('payment_amount','Form Setup Error: Hidden "Payment Amount" field in Reason form does not contain any numbers.');		
				}
			} else {
				$this->remove_element('payment_amount');
			}
		} else {
			$this->set_display_name('payment_amount', 'Payment Amount');
			$this->payment_element = 'payment_amount';
		}
		
		// Make sure the form creator has included an expense_budget_number and a revenue_budget_number field, and that they contain 
		// properly formatted budget numbers.
		// Modified by SLS
		
		/**
		 * @todo  ! ignore case when looking for expense and revenue budget numbers
		 */
		
		if ($this->expense_budget_number = $this->get_element_name_from_label('Expense Budget Number'))
		{
			// scott 9/4/2009 - Chuck Rhia says we want to use open text on some of the Budget Numbers.... 
			// if (!preg_match('/\d{2}-\d{4}-\d{4}-\d{4}/', $this->get_value($this->budget_number_element)))
			if ( strlen( $this->get_value( $this->expense_budget_number) ) <1 )
			{
				$this->set_error('credit_card_type','Form Setup Error: Hidden "Expense Budget Number" field must contain a number in the form: 10-0000-0000-0000');
			}
		} else {
			$this->set_error('credit_card_type','Form Setup Error: Hidden "Expense Budget Number" field is required in Reason form.');		
		}
		
		if ($this->revenue_budget_number = $this->get_element_name_from_label('Revenue Budget Number'))
		{
			// scott 9/4/2009 - Chuck Rhia says we want to use open text on some of the Budget Numbers.... 
			// if (!preg_match('/\d{2}-\d{4}-\d{4}-\d{4}/', $this->get_value($this->budget_number_element)))
			if ( strlen( $this->get_value( $this->revenue_budget_number) ) <1 )
			{
				$this->set_error('credit_card_type','Form Setup Error: Hidden "Revenue Budget Number" field must contain a number in the form: 10-0000-0000-0000');
			}
		} else {
			$this->set_error('credit_card_type','Form Setup Error: Hidden "Revenue Budget Number" field is required in Reason form.');		
		}

		// Make the date range for card expiration sane
		$this->change_element_type('credit_card_expiration_year','numrange',array('start'=>date('Y'),'end'=>(date('Y')+15),'display_name' => 'Expiration Year'));
	}
		
	function pre_show_form()
	{
		if( $this->is_in_testing_mode )
		{
			echo '<div class="testNote"><hr />';
			echo '<h3>Testing mode on</h3>'."\n";
			echo '<p>Credit cards will not be charged in this mode.</p>'."\n";
			echo '<hr /></div>'."\n";
		}
	}
	function run_error_checks()
	{
		// Validate the e-mail address field if used
		if (($email_name = $this->get_element_name_from_label('Your Email')) && $this->get_value($email_name)) 
			if (!check_against_regexp($this->get_value($email_name), array('email'))) $this->set_error($email_name, 'Please enter a valid email address.');

		// Make sure we have a payment amount; look for a dollar sign first, then any number
		if (preg_match('/\$([\d,]+\.?\d{0,2})/',$this->get_value($this->payment_element), $match) ||
			preg_match('/([\d,]+\.?\d{0,2})/',$this->get_value($this->payment_element), $match))
		{
			// remove any extra characters from amount
			$payment_amount = preg_replace('/[^\d\.]/','',$match[1] );
		} else {
			$this->set_error($this->payment_element, 'Could not work out payment amount. Please contact the form maintainer.');
		}

		// This is where we process the credit card, so that the form can't be submitted unless the payment
		// goes through
		if( !$this->_has_errors() )
		{
			$pf = new pfpc;
			$expiration_mm = str_pad($this->get_value('credit_card_expiration_month'), 2, '0', STR_PAD_LEFT);
			$expiration_yy = substr($this->get_value('credit_card_expiration_year'), 2, 2);
			$expiration_mmyy = $expiration_mm.$expiration_yy;
			
			foreach($this->elements as $element_name => $vals)
			{
				if($this->get_value($element_name))
				{
					if(empty($this->database_transformations[$element_name]))
					{
						$pass_info[$element_name] = $this->get_value($element_name);
					}
					else
					{
						$pass_info[$element_name] = $this->database_transformations[$element_name]($this->get_value($element_name));
					}
				}
			}
			
			$model =& $this->get_model();

                        $pf->set_info(
				$payment_amount,
				$this->get_value('credit_card_number'),
				$expiration_mmyy,
				$this->get_value($this->revenue_budget_number),
				$this->get_value('credit_card_name'),
				$this->get_value($this->expense_budget_number),
				$model->get_form_name(),
                                $this->get_value('billing_street_address'),
                                $this->get_value('billing_city'),
                                $this->get_value('billing_state_province'),
                                $this->get_value('billing_zip'),
                                $this->get_value($email_name)
			);
						
			/* THIS IS WHERE THE TRANSACTION TAKES PLACE */
			// Test mode: $result = $pf->transact('test');
			// Live mode: $result = $pf->transact();
			if($this->is_in_testing_mode)
			{
				$this->pfresult = $pf->transact('test');
			}
			else
			{
				$this->pfresult = $pf->transact();
			}
			 if (!$pf->approved)
			 {
				$message = $pf->message;
				$this->set_error('credit_card_number',$message);
			}
			else
			{
			//// DO YOUR OWN LOGGING HERE
				connectDB('reason_transactions');
				
				$billing_address = $this->get_value('billing_street_address') . "\n" .
								$this->get_value('billing_city') . ", " .
								$this->get_value('billing_state_province') . "  " .
								$this->get_value('billing_zip') . "\n" .
								$this->get_value('billing_country') . "\n";
				
				$query = 'INSERT INTO transactions SET
					REFNUM = "'.$this->pfresult['PNREF'].'",
					source = "'.addslashes( $pf->comment2 ). '", 
					amount = "'.addslashes( $pf->amount ). '", 
					name_on_card = "'.addslashes( $this->get_value('credit_card_name') ). '", 
					billing_address = "'.addslashes( $billing_address ). '", 
					card_number = "'.addslashes( obscure_credit_card_number( $this->get_value('credit_card_number') ) ). '", 
					card_expiration = "'.addslashes( $expiration_mmyy ). '"'; 
					
				$dbresult = db_query($query, 'We were unable to record your transaction in our database. 
					Your credit card has been charged, but you should contact the owner of this form
					to verify that your payment was received.', false);
				
				connectDB(REASON_DB);
			}
		}
	}
	
	function email_form_data_to_submitter()
	{
		$model =& $this->get_model();
		
		// Figure out who would get an email confirmation (either through a 
		// Your Email field or by knowing the netid of the submitter
		if (!$recipient = $this->get_value_from_label('Your Email'))
		{
			if ($submitter = $model->get_email_of_submitter())
				$recipient = $submitter.'@luther.edu';
		}
		
		// If we're supposed to send a confirmation and we have an address...
		if ($recipient)
		{
			// Use the (first) form recipient as the return address if available
			if ($senders = $model->get_email_of_recipient())
			{
				list($sender) = explode(',',$senders, 1);
				if (strpos($sender, '@') === FALSE)
					$sender .= '@luther.edu';
			} else {
				$sender = 'noreply@luther.edu';
			}
			
			$thank_you = $model->get_thank_you_message();
			
			$email_values = $model->get_values_for_email_submitter_view();
	
			if (!($subject = $this->get_value_from_label('Confirmation Subject')))
				$subject = 'Thank you for your payment';
			
			$values = "\n";
			if ($model->should_email_data())
			{
				foreach ($email_values as $key => $val)
				{
					$values .= sprintf("\n%s:\n   %s\n", $val['label'], $val['value']);
				}
			}
			
			$html_body = $thank_you . nl2br($values);
			$txt_body = html_entity_decode(strip_tags($html_body));
			
			$mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
			$mailer->send();
		}		
	}
	
	
}

function obscure_credit_card_number( $cc_num )
{
	$char_count = strlen ( $cc_num );
	$obscure_end = $char_count-4;
	$obscured_num = '';
	for($i=0; $i<$obscure_end; $i++)
	{
		$obscured_num .= 'x';
	}
	$obscured_num .= substr( $cc_num, $char_count-4 );
	return $obscured_num;
}


?>