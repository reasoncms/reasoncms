<?php
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'reason/local/stock/pfproclass.php');
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'StudentTreatsThorForm';

class StudentTreatsThorForm extends CreditCardThorForm {
    // if defined none of the default actions will be run (such as email_form_data) and you need to define the custom method and a
    // should_custom_method in the view (if they are not in the model).
    var $process_actions = array('email_form_data','save_submitted_data_to_session', 'save_form_data', 'email_form_data_to_submitter');

    function custom_init(){
        parent::custom_init();
	   $model =& $this->get_model();
	   $head_items = $model->get_head_items();
	   $head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/student_treats.js');
	}
   function should_my_custom_process(){
	return true;
    }

    function on_every_time() {
        parent::on_every_time();

        //import js
        echo '<script type="text/javascript" src="'.REASON_HTTP_BASE_PATH.'js/student_treats.js"></script>';

        // radio header
        $this->add_element('radio_header', 'comment', array('text' => '<h4> </br> How many treat orders would you like to place?  All treat orders are $20 each.</h4>'));
        $this->move_element('radio_header', 'after', $this->get_element_name_from_label('Cell Phone'));

        $this->change_element_type($this->get_element_name_from_label('#1 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#2 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#3 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#4 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#5 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#6 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#7 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#8 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#9 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#10 Date For Delivery'), 'textDate');


        //$this->is_in_testing_mode = true;
    }

    function pre_error_check_actions() {
        parent::pre_error_check_actions();
        
        $date1 = $this->get_element_name_from_label('#1 Date For Delivery');
        $occassion1 = $this->get_element_name_from_label('#1 Occasion Type: birthday, holiday, etc.');
        $type1 = $this->get_element_name_from_label('#1 Treat Type');

        $date2 = $this->get_element_name_from_label('#2 Date For Delivery');
        $occassion2 = $this->get_element_name_from_label('#2 Occasion Type: birthday, holiday, etc.');
        $type2 = $this->get_element_name_from_label('#2 Treat Type');

        $date3 = $this->get_element_name_from_label('#3 Date For Delivery');
        $occassion3 = $this->get_element_name_from_label('#3 Occasion Type: birthday, holiday, etc.');
        $type3 = $this->get_element_name_from_label('#3 Treat Type');

        $date4 = $this->get_element_name_from_label('#4 Date For Delivery');
        $occassion4 = $this->get_element_name_from_label('#4 Occasion Type: birthday, holiday, etc.');
        $type4 = $this->get_element_name_from_label('#4 Treat Type');

        $date5 = $this->get_element_name_from_label('#5 Date For Delivery');
        $occassion5 = $this->get_element_name_from_label('#5 Occasion Type: birthday, holiday, etc.');
        $type5 = $this->get_element_name_from_label('#5 Treat Type');

        $date6 = $this->get_element_name_from_label('#6 Date For Delivery');
        $occassion6 = $this->get_element_name_from_label('#6 Occasion Type: birthday, holiday, etc.');
        $type6 = $this->get_element_name_from_label('#6 Treat Type');
        
        $date7 = $this->get_element_name_from_label('#7 Date For Delivery');
        $occassion7 = $this->get_element_name_from_label('#7 Occasion Type: birthday, holiday, etc.');
        $type7 = $this->get_element_name_from_label('#7 Treat Type');
        
        $date8 = $this->get_element_name_from_label('#8 Date For Delivery');
        $occassion8 = $this->get_element_name_from_label('#8 Occasion Type: birthday, holiday, etc.');
        $type8 = $this->get_element_name_from_label('#8 Treat Type');
        
        $date9 = $this->get_element_name_from_label('#9 Date For Delivery');
        $occassion9 = $this->get_element_name_from_label('#9 Occasion Type: birthday, holiday, etc.');
        $type9 = $this->get_element_name_from_label('#9 Treat Type');
        
        $date10 = $this->get_element_name_from_label('#10 Date For Delivery');
        $occassion10 = $this->get_element_name_from_label('#10 Occasion Type: birthday, holiday, etc.');
        $type10 = $this->get_element_name_from_label('#10 Treat Type');


        if ($this->get_value_from_label('Payment Amount') == '$20 - 1 treat') {
          
            $this->remove_required($date2);
            $this->remove_required($occassion2);
            $this->remove_required($type2);

            $this->remove_required($date3);
            $this->remove_required($occassion3);
            $this->remove_required($type3);

            $this->remove_required($date4);
            $this->remove_required($occassion4);
            $this->remove_required($type4);

            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$40 - 2 treats') {
            
            $this->remove_required($date3);
            $this->remove_required($occassion3);
            $this->remove_required($type3);

            $this->remove_required($date4);
            $this->remove_required($occassion4);
            $this->remove_required($type4);

            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$60 - 3 treats') {
            
            $this->remove_required($date4);
            $this->remove_required($occassion4);
            $this->remove_required($type4);

            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$80 - 4 treats') {
            
            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$100 - 5 treats') {
           
            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$120 - 6 treats') {
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$140 - 7 treats') {
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$160 - 8 treats') {
          
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$180 - 9 treats') {
            
                     
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        
    }

function run_error_checks(){	

	$iterator = 0;
	$date_now = date("Y-m-d");


	if($this->get_value_from_label('Payment Amount') == '$20 - 1 treat') {
		$iterator = 1;
	}

	if($this->get_value_from_label('Payment Amount') == '$40 - 2 treats') {
		$iterator = 2;
	}

	if($this->get_value_from_label('Payment Amount') == '$60 - 3 treats') {
		$iterator = 3;
	}

	if($this->get_value_from_label('Payment Amount') == '$80 - 4 treats') {
		$iterator = 4;
	}

	if($this->get_value_from_label('Payment Amount') == '$100 - 5 treats') {
		$iterator = 5;
	}

	if($this->get_value_from_label('Payment Amount') == '$120 - 6 treats') {
		$iterator = 6;
	}

	if($this->get_value_from_label('Payment Amount') == '$140 - 7 treats') {
		$iterator = 7;
	}

	if($this->get_value_from_label('Payment Amount') == '$160 - 8 treats') {
		$iterator = 8;
	}

	if($this->get_value_from_label('Payment Amount') == '$180 - 9 treats') {
		$iterator = 9;
	}

	if($this->get_value_from_label('Payment Amount') == '$200 - 10 treats') {
		$iterator = 10;
	}

	for($i = 1; $i <= $iterator; $i++) {
		$date_entered = $this->get_value_from_label('#' . $i . ' Date For Delivery');

		$date_diff = strtotime($date_entered) - strtotime($date_now);

		if($date_diff < (14*24*60*60)) {
			$this->set_error(($this->get_element_name_from_label('#' . $i . ' Date For Delivery')), ('The delivery date must be at least two weeks later than the order date: #' . $i . ' Date For Delivery.')); 

		}
    }
	parent::run_error_checks();
}

function email_form_data(){ 
	$model =& $this->get_model();
	$sender = 'www@luther.edu';
	$recipient = $model->get_email_of_recipient();
	$recipients = explode(',',$recipient);
 
	if (in_array('@', $recipients)===FALSE){
           foreach($recipients as $recipient){
	  	 $recipient .= '@luther.edu';
	   }	
	}

	$subject = 'Response to Form: ' . $model->get_form_name();
	$heading = "<h2><strong>".$model->get_form_name()."</strong></h2>";
	$email_data = $model->get_values_for_email();
	$values = "\n";
	if ($model->should_email_data()){
	   foreach($email_data as $key => $val){

	     if (!empty($this->get_value_from_label($val['label']))){

		if ($val['label'] == '#2 Date For Delivery'){	
				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#2 Date For Delivery';	
		}
		if ($val['label'] == '#3 Date For Delivery'){
				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#3 Date For Delivery';	
		}		
		if($val['label'] == '#4 Date For Delivery'){

				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#4 Date For Delivery';	
		}
		if ($val['label'] == '#5 Date For Delivery'){

				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#5 Date For Delivery';	
		}
		if ($val['label'] == '#6 Date For Delivery'){

				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#6 Date For Delivery';	
		}
		if ($val['label'] == '#7 Date For Delivery'){

				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#7 Date For Delivery';	
		}
		if ($val['label'] == '#8 Date For Delivery'){

				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#8 Date For Delivery';	
		}
		if ($val['label'] == '#9 Date For Delivery'){

				$val['label']='Name';		
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#9 Date For Delivery';	
		}
		if ($val['label'] == '#10 Date For Delivery'){

				$val['label']='Name';	
				$values .= sprintf("\n<hr><strong>%s:</strong></hr>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='Student Name';		
				$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$this->get_value_from_label($val['label']));

				$val['label']='#10 Date For Delivery';	
		}	
		if(($val['label']!= 'Revenue Budget Number') && ($val['label']!='Expense Budget Number'))
		$values .= sprintf("\n<strong>%s:</strong>\t	%s\n", $val['label'],$val['value']);
				}
			}
			}
	$submission_time = date("Y-m-d H:i:s"); 

	$values .= sprintf("\n<strong>%s:</strong>\t	%s\n",'Form Submission Time', $submission_time);
	$vl = nl2br($values); 
	$html_body =$heading . $vl;
	$txt_body = html_entity_decode(strip_tags($html_body));
	$mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
	$mailer->send();

	}  //close function*/
}//close class

?>




