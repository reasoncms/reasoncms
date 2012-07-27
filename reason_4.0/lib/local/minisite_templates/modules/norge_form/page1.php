<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2011-02-16
//
//    Work on the first page of the NAHA Norge form
//
////////////////////////////////////////////////////////////////////////////////

class NorgeFormOne extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Your Information';

	var $elements = array(
		'information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Your Information</h3>',
		),
		'first_name' => array(
			'type' => 'text',
			'size' => 35,
		),
		'last_name' => array(
			'type' => 'text',
			'size'=> 35,
		),
                'institution' => array(
                    'type' => 'text',
                    'size' => 45,
                ),
		'address_1' => array(
			'type' => 'text',
			'size' => 35,
		),
                'address_2' => array(
			'type' => 'text',
			'size' => 35,
		),
		'city' => array(
			'type' => 'text',
			'size' => 35,
		),
		'state_province' => array(
			'type' => 'state_province',
			'display_name' => 'State/Province',
		),
		'zip_postal' => array(
			'type' => 'text',
			'size' => 35,
                        'display_name' => 'Zip/Postal Code'
		),
                'country' => 'country',
		'phone' => array(
			'type' => 'text',
			'size' => 20,
		),
                'e-mail' => array(
			'type' => 'text',
			'size' => 35,
		),
                'registration_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Registration Information</h3>',
                ),
                'registration_type' => array(
                    'type' => 'radio_no_sort',
                    'display_name' => 'Registration&nbsp;Type',
                    'options' => array(
                        'Regular' => 'Regular - $150 usd',
                        'Senior' => 'Senior (over age 65) -$140 usd',
                        'Student' => 'Student - $100 usd'
                    )
                ),
                'school' => 'text',
                'attend_banquet' => array(
                    'type' => 'radio_inline_no_sort',
                    'display_name' => 'Will you attend the banquet?',
                    'options' => array('Yes' => 'Yes', 'No' => 'No'),
                    'comments' => 'Banquet ticket $35'
                ),
                'housing_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Baker Village Housing Requests</h3>',
                ),
                'housing_comment' => array(
                    'type' => 'comment',
                    'text' => 'Please see the <a href="http://www.luther.edu/150/events/conference/housing/" target=_blank>accommodations</a>
                        page for housing options and descriptions'
                ),
                'room_type' => array(
                    'type' => 'radio_inline_no_sort',
                    'comments' => '$32 per bed <br>
                        Single - one twin bed<br>
                        Double - two twin beds',
                    'options' => array('single' => 'Single', 'double' => 'Double')
                ),
                'arrival_date' => 'textdate',
                'departure_date' => 'textdate',
                'housemates_comment' => array(
                    'type' => 'comment',
                    'text' => 'If possible, please house me with'
                ),
                'housemates_requested' => array(
                    'type' => 'textarea',
                   'display_name' => '&nbsp;',
                ),
                'additional_meal_header' => array(
                    'text' => '<h3>Additional Meal Tickets</h3>',
                    'type' => 'comment',
                ),
                'additional_meal_comment' => array(
                    'type' => 'comment',
                    'text' => 'If you would like to bring a guest, who is not attending the conference, to meals, please indicate below',
                ),
                'additional_meal_tickets' => array(
                    'type' => 'checkboxgroup',
                    'display_name' => '&nbsp;',
                    'options' => array(
                        'Reception' => 'Reception - $15',
                        'Barbecue' => 'Barbecue - $25',
                        'Banquet' => 'Banquet - $35',
                    )
                ),
                'shuttle_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Shuttle</h3>'
                ),
                'shuttle_comment' => array(
                    'type' => 'comment',
                'text' => 'Please indicate if you need round trip <a href="/150/events/conference/travel/" target=__blank>shuttle service</a>
                    from Minneapolis, MN.'
                ),
                'shuttle_tickets' => array(
                    'type' => 'radio_no_sort',
                    'display_name' => '&nbsp;',
                    'options' => array(
                        1 => 'One ticket - $50',
                        2 => 'Two tickets - $100',
                    )
                ),
                'dietary_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Dietary Needs</h3>'
                ),
                'dietary_comment' => array(
                    'type' => 'comment',
                    'text' => 'Please list any dietary restrictions or needs for you or your guest'
                ),
                'dietary_needs' => array(
                    'type' => 'textarea',
                    'display_name' => '&nbsp;',
                ),
                'submitter_ip' => 'hidden',
	);

	var $required = array('first_name', 'last_name', 'phone', 'e-mail', 'address_1', 'city',  'registration_type');

	var $error_header_text = 'Please check your form.';

        function on_every_time()
        {
            $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);
        }
	// style up the form and add comments et al
	function pre_show_form()
	{
		echo '<div id="norgeForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
}
?>