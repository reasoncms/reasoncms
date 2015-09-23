<?php
include_once('designations_group.php');
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
    var $gift_types = array(
        'annual_fund'   =>  array(
            'name'  =>  'annual_fund',
            'desc'  =>  'Annual Fund'
        ),
        'norse_athletic_association'    =>  array(
            'name'  =>  'norse_athletic_association',
            'desc'  =>  'Norse Athletic Association'
        ),
        'scholarship_fund'  =>  array(
            'name'  =>  'scholarship_fund',
            'desc'  =>  'Scholarship Fund'
        ),
        'other' =>  array(
            'name'  =>  'other',
            'desc'  =>  'Other',
            'other_label'   =>  'Other'
        )
    );
    var $naa_opts = array(
        'general'               => 'General',
        'baseball'              => 'Baseball',
        'basketball_men'        => 'Basketball (men)',
        'basketball_women'      => 'Basketball (women)',
        'cross_country_men'     => 'Cross Country (men)',
        'cross_country_women'   => 'Cross Country (women)',
        'football'              => 'Football',
        'golf_men'              => 'Golf (men)',
        'golf_women'            => 'Golf (women)',
        'soccer_men'            => 'Soccer (men)',
        'soccer_women'          => 'Soccer (women)',
        'softball'              => 'Softball',
        'swimming_diving_men'   => 'Swimming & Diving (men)',
        'swimming_diving_women' => 'Swimming & Diving (women)',
        'tennis_men'            => 'Tennis (men)',
        'tennis_women'          => 'Tennis (women)',
        'track_field_men'       => 'Track & Field (men)',
        'track_field_women'     => 'Track & Field (women)',
        'volleyball'            => 'Volleyball',
        'wrestling'             => 'Wrestling'
    );

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
         'text' => '<h3>Designation</h3>',
        ),
        'split_gift' => array(
            'type'          =>'checkboxfirst',
            'display_name'  => 'I\'d like to split my gift',
        ),
        'gift_designation'       => array(
            'type'          =>  'hidden',
            'default'       =>  'annual_fund',
            'display_name'  =>  '&nbsp;'
        ),
        'comments_special_instructions' =>  array(
            'type'          =>  'textarea',
            'display_name'  =>  'Comments / Special instructions',
        ),
        'matching_gift_header' => array(
			'type' => 'comment',
			'text' => '<h3>Will your gift be matched by your employer?</h3>'
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
        'gift_prompt' => array(
        	'type' => 'select_no_sort',
			'display_name' => '<h3>What prompted you to make this gift?</h3>',
            'add_null_value_to_top' => true,
            'options' => array(
                'mailing'       => 'Received a mailing',
                'email'         => 'Received an email',
                'phonathon'     => 'Received a Phonathon call',
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
		'refby'=> 'protected',
		'submitter_ip'=>'protected',
        'split_designations'    =>  array(
            'type'  =>  'hidden',
            'userland_changeable'   =>  true,
        ),
	);
    public $box_class = 'StackedBox';

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
   	);

	var $display_name = 'Gift Info';
	var $error_header_text = 'Please check your form.';

    function init( $externally_set_up = false )
    {
        parent::init( $externally_set_up );
        $this->setup_gift_designations();
    }

    function get_tooltip_text($label) {
        $tool_label = strtolower($label);
        $tool_label = str_replace(' ', '_',$tool_label);
        $blurb_unique_name = "{$tool_label}_hover_blurb";
        if ( $blurb_unique_name == reason_unique_name_exists($blurb_unique_name) ){
            $blurb = get_text_blurb_content($blurb_unique_name);
            return '<span data-tooltip aria-haspopup="true" class="has-tip tip-right" title="'.$blurb.'">'.$label.'</span>';
        } else {
            return $label;
        }
    }

	// style up the form and add comments et al
	function on_every_time()
	{
        if ( isset($_SERVER['HTTP_REFERER']) && strrpos($_SERVER['HTTP_REFERER'], '/norse-athletic-association', -1) )
        {
            $this->set_element_properties('gift_designation', array( 'default' => 'norse_athletic_association' ));
        }
        if ($this->get_value('gift_amount') < '50' && $this->get_value('installment_type') == 'Onetime') {
            $this->change_element_type('split_gift', 'hidden');
        }

        $opts = array(
            'annual_fund'  => $this->get_tooltip_text('Annual Fund'),
            'naa'  => $this->get_tooltip_text('Norse Athletic Association'),
            'scholarship_fund'  => $this->get_tooltip_text('Scholarship Fund'));

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

        $this->get_element('match_gift')->set_comments(
            '<p><a data-reveal-id="matchingGiftsIframe">Click to see if your employer has a matching program.</a></p>
            <div class="reveal-modal medium" id="matchingGiftsIframe" data-reveal="">
                <iframe height="500px" width="100%" src="//www.matchinggifts.com/luther_iframe"></iframe>
                <a class="close-reveal-modal">×</a>
            </div>','before'
        );
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

    /**
     * Given the list of designations set by the module, set up the designation element. Based
     * on the designations available, the element can be a simple set of options, options with
     * a fill-in Other, or completely absent.
     */
    function setup_gift_designations()
    {
        // if ($types = $this->controller->module->gift_types)
        if ($types = $this->gift_types)
        {
            foreach ($types as $slug => $type)
            {
                if ($slug == 'other') continue;
                pray($slug);
                $options[$type['name']] = $this->get_tooltip_text($type['desc']);
            }

            $params = array('options'=>$options);

            if (isset($types['other']))
            {
                $params['other_label'] = $this->get_tooltip_text($type['desc']);
                $params['naa_options'] = $this->naa_opts;
                $element_type = 'designations';
            }
            else
            {
                $element_type = 'radio_inline_no_sort';
            }

            $this->change_element_type('gift_designation', $element_type, $params);
        }
        // If there aren't any types, hide the whole section
        else
        {
            $this->change_element_type('gift_designation_note', 'hidden');
        }
    }
}
