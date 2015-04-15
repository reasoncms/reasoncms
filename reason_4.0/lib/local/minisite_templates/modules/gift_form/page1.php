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
		'gift_amount_header' => array(
			'type' => 'comment',
			'text' => '<h3>Gift Amount</h3>',
		),
		'gift_amount' => array(
			'type' => 'money',
			'display_name' => '',
			'size'=>12,
		),
		'installment_type' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => '&nbsp;',
			'options' => array('Monthly'=>'Every month','Quarterly'=>'Every quarter','Onetime'=>'One time'),
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
         'text' => '<h3>Designation</h3><p><em>If you choose more than one designation, you may choose how to split the gift</em></p>',
        ),
		'annual_fund' => array(
			'type' => 'checkboxfirst',
            'comments' => '(please use my gift where it is needed most)'
		),
        'annual_fund_amount' => 'money',
  //      'specific_fund' => array(
		// 	'type' => 'checkboxfirst',
  //           'comments' => '(I have something specific in mind)',
		// ),
  //       'designation_note' => array(
  //               'type' => 'comment',
  //               'text' => 'If more than one designation is specified, your gift
  //                          will be divided equally unless you indicate otherwise
  //                          in the comments section below.'
  //       ),
        'norse_athletic_association' => array(
        		'type' => 'checkboxfirst',
        ),
        'norse_athletic_association_details' => array(
                'type' => 'select_no_sort',
                'display_name' => '&nbsp;',
                'options' => array(
                    'General'                      => 'General',
                    'Baseball'                     => 'Baseball',
                    'Basketball (men)'             => 'Basketball (men)',
                    'Basketball (women)'           => 'Basketball (women)',
                    'Cross Country (men)'          => 'Cross Country (men)',
                    'Cross Country (women)'        => 'Cross Country (women)',
                    'Football'                     => 'Football',
                    'Golf (men)'                   => 'Golf (men)', 
                    'Golf (women)'                 => 'Golf (women)',
                    'Soccer (men)'                 => 'Soccer (men)',
                    'Soccer (women)'               => 'Soccer (women)',
                    'Softball'                     => 'Softball',
                    'Swimming & Diving (men)'      => 'Swimming & Diving (men)',
                    'Swimming & Diving (women)'    => 'Swimming & Diving (women)',
                    'Tennis (men)'                 => 'Tennis (men)',
                    'Tennis (women)'               => 'Tennis (women)',
                    'Track & Field (men)'          => 'Track & Field (men)',
                    'Track & Field (women)'        => 'Track & Field (women)',
                    'Volleyball'                   => 'Volleyball',
                    'Wrestling'                    => 'Wrestling',
                ),
        ),
        'norse_athletic_association_amount' => 'money',
        // 'baseball_stadium' => array(
        // 		'type' => 'checkboxfirst',
        // ),
        // 'baseball_stadium_amount' => 'money',
        // 'softball_stadium' => array(
        //         'type' => 'checkboxfirst',
        // ),
        // 'softball_stadium_amount' => 'money',
        'scholarship_fund' => array(
                'type' => 'checkboxfirst',
                'comments' => 'general',
        ),
        'scholarship_fund_amount' => 'money',
        'other' => 'checkboxfirst', 
        'other_amount' => 'money',
        'other_designation_details' => array(
            'type' => 'text',
            'display_name' => '&nbsp;',
        ),
        'comments_special_instructions' => array(
                'type' => 'textarea',
                'display_name' => 'Comments / Special Instructions',
        ),
        'matching_gift_header' => array(
			'type' => 'comment',
			'text' => '<h3>Will your gift be matched by your employer?</h3>'
		),
		'match_gift' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'My (or my spouse\'s) employer will match my gift',
            'comments' => 
                '<p><a data-reveal-id="matchingGiftsIframe">Click to see if your employer has a matching program.</a></p>
                <div class="reveal-modal medium" id="matchingGiftsIframe" data-reveal="">
                    <iframe height="500px" width="100%" src="//www.matchinggifts.com/luther_iframe"></iframe>
                    <a class="close-reveal-modal">×</a>
                </div>',
		),
		'employer_name' => array(
			'type' => 'text',
			'size'=>35,
			'display_name'=>'&nbsp;',
			'comments'=>'<div class="smallText comment">Employer name</div>',
		),
        'gift_prompt' => array(
        	'type' => 'select_no_sort',
			'display_name' => '<h3>What prompted you to make this gift?</h3>',
            'add_null_value_to_top' => true,
            'options' => array(
                'mailing'       => 'Recieved a mailing',
                'email'         => 'Recieved an email',
                'staff_visit'   => 'Development staff visit',
                'other'         => 'Other')
        ),
        'gift_prompt_details' => array(
            'type' => 'text',
            'display_name' => '&nbsp;',
		),
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
			'args' => array('use_element_labels' => true,'use_group_display_name' => false, 'rows' => array('','') ),
		),
        'annual_fund_group' => array('type' => 'inline',
            'elements' => array('annual_fund', 'annual_fund_amount'),
            'args' => array('use_element_labels' => false, 'use_group_display_name' => false)
        ),
        'norse_athletic_association_group' => array('type' => 'inline',
            'elements' => array('norse_athletic_association', 'norse_athletic_association_amount'),
            'args' => array('use_element_labels' => false, 'use_group_display_name' => false)
        ),
        // 'baseball_group' => array('type' => 'inline',
        //     'elements' => array('baseball_stadium', 'baseball_stadium_amount'),
        //     'args' => array('use_element_labels' => false, 'use_group_display_name' => false)
        // ),
        // 'softball_group' => array('type' => 'inline',
        //     'elements' => array('softball_stadium', 'softball_stadium_amount'),
        //     'args' => array('use_element_labels' => false, 'use_group_display_name' => false)
        // ),
        'scholarship_group' => array('type' => 'inline',
            'elements' => array('scholarship_fund', 'scholarship_fund_amount'),
            'args' => array('use_element_labels' => false, 'use_group_display_name' => false)
			),
        'other_group' => array('type' => 'inline',
            'elements' => array('other', 'other_amount'),
            'args' => array('use_element_labels' => false, 'use_group_display_name' => false)
		),
   	);

	var $display_name = 'Gift Info';
	var $error_header_text = 'Please check your form.';

	// style up the form and add comments et al
	function on_every_time()
	{
        $this->box_class = 'StackedBox';

        // if a text blurb with the unique name convention of giving_program_hover_blurb
        // exists, then replace the display name with a tooltip 
        foreach ($this->elements as $element => $value) {
            $blurb_unique_name = "{$element}_hover_blurb";
            if ( $blurb_unique_name == reason_unique_name_exists($blurb_unique_name) ){
                $blurb = get_text_blurb_content($blurb_unique_name);
                $display_name = $this->get_display_name($element);
                $this->set_element_properties($element, array('display_name' => '<span data-tooltip aria-haspopup="true" class="has-tip" title="'.$blurb.'">'.$display_name.'</span>'));
            }
        }
        // this one is a special case, so we'll just call it explicitly
        // the name of the element is actually, specific_fund. If we changed 
        // it the element name to match the display name we'd have to do a lot of 
        // db rewrites
        // if (reason_unique_name_exists('designated_giving_hover_blurb')) {
        //     $display_name = $this->get_display_name('specific_fund');
        //     $this->set_display_name('specific_fund', '<span data-tooltip aria-haspopup="true" class="has-tip" title="'.get_text_blurb_content('designated_giving_hover_blurb').'">Designated Giving</span>');
        // }
        // same goes for scholarship fund
        // if (reason_unique_name_exists('scholarship_fund_hover_blurb')) {
        //     $display_name = $this->get_display_name('scholarship_fund');
        //     $this->set_display_name('scholarship_fund', '<span data-tooltip aria-haspopup="true" class="has-tip" title="'.get_text_blurb_content('designated_giving_hover_blurb').'">Scholarship / Endowment</span>');
        // }

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
        $this->move_element('annual_fund_group', 'after', 'designation_header');
        $this->move_element('norse_athletic_association_group', 'after', 'annual_fund_group');
        // $this->move_element('baseball_group', 'after', 'norse_athletic_association_group');
        // $this->move_element('softball_group', 'after', 'baseball_group');
        $this->move_element('scholarship_group', 'after', 'norse_athletic_association_group');
        $this->move_element('other_group', 'after', 'scholarship_group');
        $this->move_element('norse_athletic_association_details', 'after', 'norse_athletic_association_group');
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
