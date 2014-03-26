<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Matt Ryan
//    2005-02-16
//
//    Work on the first page of the giving form
//
//    Modified for Luther - Steve Smith
//    search for SLS to find modifications
//    2010-09-13
//
////////////////////////////////////////////////////////////////////////////////

class GiftPageOneForm extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
		// 'gift_amount_header' => array(
		// 	'type' => 'comment',
		// 	'text' => '<h3>Gift Amount</h3>',
		// ),
		'gift_amount' => array(
			'type' => 'money',
			'display_name' => '<h3>Gift Amount</h3>',
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
		'designation_header' => array(
         'type' => 'comment',
         'text' => '<h3>Designation</h3>',
        ),
  //       'designation' => array(
		// 	'type' => 'radio_no_sort',
		// 	'display_name' => '<h3>Designation</h3>',
  //           'options'=>array(
  //               'annual_fund' => '<a href="/giving/annual/" target="_blank">Annual Fund<a/> (please use my gift where it is needed most)',
  //               'specific_fund' => '<a href="/giving/choices/" target="_blank">Designated Giving</a> (I have something specific in mind)' )
		// ),
  //       'specific_designation' => array(
  //           'type' => 'checkboxgroup_with_other_no_sort',
  //           'display_name' => 'If more than one designation is specified, your gift will be divided equally unless you indicate otherwise in the comments section below.',
  //           'options' => array(
  //               'baseball_stadium' => '<a href="/giving/priorities/stadiums/" target="_blank">Baseball Stadium</a>',
  //               'softball_stadium'  => '<a href="/giving/priorities/stadiums/" target="_blank">Softball Stadium</a>',
  //               'scholarship_fund' => '<a href="/giving/choices/scholarship/" target="_blank">Scholarship Support</a>, general',
  //           )
  //       ),
		'annual_fund' => array(
			'type' => 'checkboxfirst',
			'display_name' => '<a href="/giving/annual/" target="_blank">Annual Fund<a/> (please use my gift where it is needed most)',
		),
       'specific_fund' => array(
			'type' => 'checkboxfirst',
			'display_name' => '<a href="/giving/choices/" target="_blank">Designated Giving</a> (I have something specific in mind)',
		),
        'designation_note' => array(
                'type' => 'comment',
                'text' => 'If more than one designation is specified, your gift
                           will be divided equally unless you indicate otherwise
                           in the comments section below.'
        ),
        'baseball_stadium' => array(
        		'type' => 'checkboxfirst',
        		'display_name' => '<a href="/giving/priorities/stadiums/" target="_blank">Baseball Stadium</a>',
        ),
        'softball_stadium' => array(
                'type' => 'checkboxfirst',
                'display_name' => '<a href="/giving/priorities/stadiums/" target="_blank">Softball Stadium</a>',
        ),
        'scholarship_fund' => array(
                'type' => 'checkboxfirst',
                'display_name' => '<a href="/giving/choices/scholarship/" target="_blank">Scholarship Support</a>, general',
        ),
        'transform_teaching_fund' => array(
                'type' => 'checkboxfirst',
                'display_name' => '<a href="/giving/sesquicentennialfund/?story_id=268591" target="_blank">The Fund for Transformational Teaching and Learning</a>',
        ),
        'sustainable_communities' => array(
                'type' => 'checkboxfirst',
                'display_name' => '<a href="/giving/sesquicentennialfund/?story_id=268629" target="_blank">Luther Center for Sustainable Communities</a>',
        ),
        'norse_athletic_association' => array(
        		'type' => 'checkboxfirst',
        		'display_name' => '<a href="/naa/" target="_blank">Norse Athletic Association</a>',
        ),
        'naa_designation_details' => array(
                'type' => 'select_no_sort',
                'display_name' => '&nbsp;',
                'add_null_value_to_top' => true,
                'options' => array(
                    'Baseball' => 'Baseball',
                    'Basketball, men\'s' => 'Basketball, men\'s',
                    'Basketball, women\'s' => 'Basketball, women\'s',
                    'Cross Country, men\'s' => 'Cross Country, men\'s',
                    'Cross Country, women\'s' => 'Cross Country, women\'s',
                    'Football' => 'Football',
                    'Golf, men\'s' => 'Golf, men\'s', 
                    'Golf, women\'s' => 'Golf, women\'s',
                    'Soccer, men\'s' => 'Soccer, men\'s',
                    'Soccer, women\'s' => 'Soccer, women\'s',
                    'Softball' => 'Softball',
                    'Swimming & Diving, men\'s' => 'Swimming & Diving, men\'s',
                    'Swimming & Diving, women\'s' => 'Swimming & Diving, women\'s',
                    'Tennis, men\'s' => 'Tennis, men\'s',
                    'Tennis, women\'s' => 'Tennis, women\'s',
                    'Track & Field, men\'s' => 'Track & Field, men\'s',
                    'Track & Field, women\'s' => 'Track & Field, women\'s',
                    'Volleyball' => 'Volleyball',
                    'Wrestling' => 'Wrestling',
                ),
        ),
        'other_designation_note' => array(
                'type' => 'comment',
                'text' => 'Comments/Other Designation',             
        ),
        'other_designation_details' => array(
                'type' => 'textarea',
                'display_name' => '&nbsp;',
        ),
        'matching_gift_header' => array(
			'type' => 'comment',
			'text' => '<h3>Will your gift be matched by your employer?</h3>
				  <p><a href="http://www.matchinggifts.com/luther/" 
				  title="Matching Gift Search Site" target="_new"
				  >Click to see if your employer has a matching program.</a></p>',
		),
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
		'existing_pledge_header' => array(
			'type' => 'comment',
			'text' => '<h3>Is this a payment on an existing pledge?</h3>',
		),
		'existing_pledge' => array(
			'type' => 'radio_inline_no_sort',
			'options' => array('Yes'=>'Yes','No'=>'No'),
			'display_name'=>'&nbsp;',
		),
        // 'gift_prompt_header' => array(
        //         'type' => 'comment',
        //         'text' => '<h3>What prompted you to make this gift?</h3>'
        // ),
        'gift_prompt' => array(
        	'type' => 'textarea',
			'rows' => 5,
			'cols' => 35,
			'display_name' => '<h3>What prompted you to make this gift?</h3>',
        ),

  //       'dedication_header' => array(
		// 	'type' => 'comment',
		// 	'text' => '<h3>Dedication <em>(optional)</em></h3>'
		// ),
		'dedication' => array(
			'type' => 'radio_no_sort',
			'options' => array('Memory'=>'In memory of','Honor'=>'In honor of'),
			'display_name' => '<h3>Dedication <em>(optional)</em></h3>',
		),
		'dedication_details' => array(
			'type' => 'text',
			'display_name' => '&nbsp;',
		),
		'refby'=>array(
			'type'=>'hidden',
		),
		'submitter_ip'=>'hidden',
	);

	/**
	* Stores all the information necessary to instantiate each element group.
	* Format: element_group_name => element info
	* @var array
	*/
	var $element_group_info = array(	
		'recur_group' => array ('type' => 'inline',
			'elements' =>  array( 'installment_start_date', 'installment_end_date' ),
			'args' => array('use_element_labels' => true,'use_group_display_name' => false,
				'rows' => array('','')
			),
		),
   	);

	var $display_name = 'Gift Info';
	var $error_header_text = 'Please check your form.';

	// style up the form and add comments et al
	function on_every_time()
	{
        $this->box_class = 'StackedBox';
		$this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);

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
		// echo '<div id="giftForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		// echo '</div>'."\n";
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
