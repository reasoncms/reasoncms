<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2011-02-16
//
//    Work on the first page of the All Band form
//
////////////////////////////////////////////////////////////////////////////////

class AllBandOne extends FormStep
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
                'graduation_name' => array(
                    'type' => 'text',
		),
                'class_year' => array(
                    'type' => 'text',
                    'size' => 4,
                ),
		'address' => array(
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
                    'display_name' => 'Zip'
		),
		'phone' => array(
                    'type' => 'text',
                    'size' => 20,
		),
                'cell_phone' => array(
                    'type' => 'text',
                    'size' => 20,
		),
                'e-mail' => array(
                    'type' => 'text',
                    'size' => 35,
		),
                't-shirt_size' => array(
                    'type' => 'radio_no_sort',
                    'options' => array(
                        's' => 'Small',
                        'm' => 'Medium',
                        'l' => 'Large',
                        'xl' => 'X-Large',
                        'xxl' => 'XX-Large',
                        ),
                ),
                'instrument' => array(
                    'type' => 'text',
                    'size' => 20,
                ),
                'guest_information_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Guest Information</h3>',
		),
                'guest_comment' => array(
                    'type' => 'comment',
                    'text' => 'If you are bringing one non-participant guest, please fill in the following information'
                ),
		'guest_first_name' => array(
                    'type' => 'text',
                    'size' => 35,
		),
		'guest_last_name' => array(
                    'type' => 'text',
                    'size'=> 35,
		),
                'guest_graduation_name' => array(
                    'type' => 'text',
                    'comments' => 'if applicable'
		),
                'registration_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Registration Information</h3>',
                ),
                'registration_type' => array(
                    'type' => 'radio_no_sort',
                    'display_name' => 'Registration&nbsp;Type',
                    'options' => array(
                        'Participant' => 'Participant - $75',
                        'Participant&Guest' => 'Participant & guest - $110',
                    )
                ),
                'housing_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Residence Hall Accommodations Fees</h3>',
                ),
                'housing_comment' => array(
                    'type' => 'comment',
                    'text' => '(nights of Friday, July 29, and Saturday, July 30)'
                ),
                'housing_comment_2' => array(
                    'type' => 'comment',
                    'text' => '<em>Accomodations are limited, please make your reservations as soon as possible and before Monday, May 9. First-come, first served.</em>'
                ),
                'room_type' => array(
                    'type' => 'radio_no_sort',
//                    'comments' => '$32 per bed <br>
//                        Single - one twin bed<br>
//                        Double - two twin beds',
                    'options' => array(
                        'single' => 'Single room <br>(one person/one bed), $72',
                        'double' => 'Double room <br>(two persons/two beds), $124 ($62/person)')
                ),
                'roommate_name' => array(
                    'type' => 'text',
                ),
                'housing_comment_3' => array(
                    'type' => 'comment',
                    'text' => 'If you have special accommodation needs, call Jud Barclay in the Summer Conferences Office for assistance, 563-387-1538'
                ),
//                'additional_meal_header' => array(
//                    'text' => '<h3>Additional Meal Tickets</h3>',
//                    'type' => 'comment',
//                ),
//                'additional_meal_comment' => array(
//                    'type' => 'comment',
//                    'text' => 'If you would like to bring a guest, who is not attending the conference, to meals, please indicate below',
//                ),
//                'additional_meal_tickets' => array(
//                    'type' => 'checkboxgroup',
//                    'display_name' => '&nbsp;',
//                    'options' => array(
//                        'Reception' => 'Reception - $15',
//                        'Barbecue' => 'Barbecue - $25',
//                        'Banquet' => 'Banquet - $35',
//                    )
//                ),
//                'shuttle_header' => array(
//                    'type' => 'comment',
//                    'text' => '<h3>Shuttle</h3>'
//                ),
//                'shuttle_comment' => array(
//                    'type' => 'comment',
//                'text' => 'Please indicate if you need round trip <a href="/150/events/conference/travel/" target=__blank>shuttle service</a>
//                    from Minneapolis, MN.'
//                ),
//                'shuttle_tickets' => array(
//                    'type' => 'radio_no_sort',
//                    'display_name' => '&nbsp;',
//                    'options' => array(
//                        1 => 'One ticket - $50',
//                        2 => 'Two tickets - $100',
//                    )
//                ),
//                'dietary_header' => array(
//                    'type' => 'comment',
//                    'text' => '<h3>Dietary Needs</h3>'
//                ),
//                'dietary_comment' => array(
//                    'type' => 'comment',
//                    'text' => 'Please list any dietary restrictions or needs for you or your guest'
//                ),
//                'dietary_needs' => array(
//                    'type' => 'textarea',
//                    'display_name' => '&nbsp;',
//                ),
                'submitter_ip' => 'hidden',
	);

	var $required = array('first_name', 'last_name', 'phone', 'e-mail', 'address', 'city',
                't-shirt_size', 'instrument', 'registration_type');

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