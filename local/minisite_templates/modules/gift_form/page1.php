<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Matt Ryan
//    2005-02-16
//
//    Work on the first page of the giving form
//
////////////////////////////////////////////////////////////////////////////////

class GiftPageOneForm extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
		'gift_note' => array(
			'type' => 'comment',
			'text' => '<h3>Gift Amount</h3>',
		),
		'gift_amount' => array(
			'type' => 'money',
			'display_name' => '&nbsp;',
			'size'=>12,
		),
		'installment_type' => array(
			'type' => 'radio_no_sort',
			'display_name' => '&nbsp;',
			'options' => array('Onetime'=>'One time','Monthly'=>'Every month','Quarterly'=>'Every quarter','Yearly'=>'Every year'),
			'default' => 'Onetime',
			),
		'installment_start_date' => array(
			'display_name' => 'Starting',
			'type' => 'textDate',
			'prepopulate'=>false,
		),
		'installment_end_date' => array(
			'type' => 'select_no_sort',
			'display_name' => 'Ending',
			'options' => array('indefinite'=>'No end date'),
		),
/*
		'matching_gift_note' => array(
			'type' => 'comment',
			'text' => '<h3>Will your gift be matched by your employer?</h3>
				  <p><a href="http://www.matchinggifts.com/carleton/" 
				  title="Matching Gift Search Site" target="_new"
				  >Click to see if your employer has a matching program.</a></p>',
		),
*/
		'match_gift' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'My (or my spouse\'s) employer will match my gift',
		),
		'employer_name' => array(
			'type' => 'text',
			'size'=>35,
			'display_name'=>'&nbsp;',
			'comments'=>'<div class="smallText comment">Employer name</div>',
		),
		'additional_instructions_note' => array(
			'type' => 'comment',
			'text' => '<h3>Additional Gift Instructions</h3>
				   <p>Please indicate any special instructions for handling your gift.
				   Include joint giving information, alternate designations, or whether your gift is 
				   in memory or in honor of someone.</p>',
		),
		'additional_instructions' => array(
			'type' => 'textarea',
			'rows' => 5,
			'cols' => 35,
			'display_name' => '&nbsp;',
		),
		'refby'=>array(
			'type'=>'hidden',
		),
	);

	/**
	* Stores all the information necessary to instantiate each element group.
	* Format: element_group_name => element info
	* @var array
	*/
	var $element_group_info = array(	
		'recur_group' => array ('type' => 'table',
					'elements' =>  array( 'installment_start_date', 'installment_end_date' ),
					'args' => array('use_element_labels' => true,
							'use_group_display_name' => false,
							'rows' => array('','')
							),
			),
	/*	'phone_group' => array ('type' => 'inline',
					'elements' =>  array( 'phone', 'phone_type' ),
					'args' => array('use_element_labels' => false,
							'display_name' => 'Phone',),
			),*/
		);

	var $display_name = 'Gift Info';
	var $error_header_text = 'Please check your form.';

	// style up the form and add comments et al
	function on_every_time()
	{
		if(!$this->get_value('installment_start_date'))
		{
			$this->set_value('installment_start_date', date('Y-m-d', time()+60*60*24));
		}

		$this->add_comments('installment_start_date','<div class="smallText comment">Enter date as month-day-year, e.g. 7/23/2005</div>');
		$options['indefinite'] = 'No End Date';
		for($i = 0; $i <= 72; $i++)
		{
			
			$timestamp = strtotime(date('Y-m').'-1 +'.$i.' months');
			$options[date('Y-m-t',$timestamp)] = date('F Y',$timestamp);
		}
		$this->change_element_type('installment_end_date','select_no_sort',array('options'=>$options));

		if(!$this->get_value('refby') && !empty($this->_request['refby']))
		{
			$refby = strip_tags(turn_into_string($this->_request['refby']));
			$this->set_value('refby', $refby);
		}

		foreach($this->element_group_info as $name => $info)
		{
			$this->add_element_group( $info['type'], $name, $info['elements'], $info['args']);
		}
		$this->move_element('recur_group','after','installment_type');
		
	}

	function pre_show_form()
	{
		echo '<div id="giftForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
	function run_error_checks()
	{
		if($this->get_value('gift_amount') <= 0)
		{
			$this->set_error('gift_amount', 'Please enter a gift amount that is greater than zero');
		}
		if(!$this->get_value('installment_type') )
		{
			$this->set_error('installment_type','Please indicate whether this is a one time or installment gift (monthly, quarterly, or yearly)');
		}
		if($this->get_value('installment_type') != 'Onetime')
		{
			$start_stamp = strtotime($this->get_value('installment_start_date'));
			$end_stamp = strtotime($this->get_value('installment_end_date'));
			if(!$this->get_value('installment_start_date') )
			{
				$this->set_error('installment_start_date','Please enter the date you would like your first installment gift to occur');
			}
			elseif($start_stamp < strtotime(date('Y-m-d').' +1 day') )
			{
				$this->set_error('installment_start_date','Please make sure the date of your first installment is tomorrow\'s date or later (please use the form "month-day-year;" tomorrow is '.date('n-j-Y', time()+60*60*24).')');
			}
			if( $this->get_value('installment_end_date') != 'indefinite' && $start_stamp > $end_stamp
			)
			{
				$this->set_error('installment_start_date','Please make sure the ending date is on or after the starting date for your gift installments');
			}
		}		
		if($this->get_value('match_gift') && $this->get_value('match_gift') == 'Yes' && !$this->get_value('employer_name') )
		{
			$this->set_error('match_gift','Please enter the name of your employer if you would like it to match your gift');
		}
	}
}

?>
