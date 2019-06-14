<?php
/**
 * @package reason
 */

include_once('paths.php');
require_once( INCLUDE_PATH . 'xml/xmlparser.php' );

class reasonFormToGravityJson
{
	protected $messages = array(
		'Not yet implemented: notification emails',
		'Not yet implemented: confirmation message',
		'Not yet implemented: access restrictions',
		'Not yet implemented: scheduling',
		'Not yet implemented: entry limiting',
		'Not yet implemented: prepopulation from ldap',
		'Not yet implemented: payment forms or other custom thor forms',
	);
	function get_json($form)
	{
		$json_data = $this->get_initial_form_data();
		$json_data['title'] = $form->get_value('name');
		
		if ($form->get_value('thor_content'))
		{
			$xml_object = new XMLParser($form->get_value('thor_content'));
			$xml_object->Parse();	
			if($xml_object)
			{
				$json_data['button']['text'] = $this->get_button_label_from_parsed_xml($xml_object, $form);
				$json_data['fields'] = $this->get_field_data_from_parsed_xml($xml_object, $form);
			}
		}
		return json_encode(array(
			0 => $json_data,
			'version' => '2.4.5.10',
		), JSON_PRETTY_PRINT);
	}
	function add_message($message)
	{
		$this->messages[] = $message;
	}
	function get_messages()
	{
		return $this->messages;
	}
	function clear_messages()
	{
		$this->messages = array();	
	}
	protected function get_button_label_from_parsed_xml($xml_object)
	{
		return (!empty($xml_object->document->tagAttrs['submit'])) ? $xml_object->document->tagAttrs['submit'] : 'Submit';
	}
	protected function get_field_data_from_parsed_xml($xml_object, $form)
	{
		$json_data = array();
		$index = 0;
		foreach ($xml_object->document->tagChildren as $element)
		{
			$method = 'get_json_data_for_' . $element->tagName;
			if(method_exists($this, $method))
			{
				$json_data[] = $this->$method($element, $form, ($index + 1) );
				$index++;
			}
			else
			{
				$this->add_message( 'Not able to include "' . $this->get_label_for_element($element) . '". Code has not yet been written to support the export of ' . $element->tagName . ' fields.' );
			}
		}
		return $json_data;
	}
	protected function get_initial_form_data()
	{
		return array(
			'title' =>  '',
			'description' => '',
			'labelPlacement' => 'top_label',
			'descriptionPlacement' => 'below',
			'button' => array(
				'type' => 'text',
				'text' => 'Submit',
				'imageUrl' => '',
			),
			'version' => '2.4.0.1',
			'id' => 1, // Not sure if this matters
			'useCurrentUserAsAuthor' => true,
			'postContentTemplateEnabled' => false,
			'postTitleTemplateEnabled' => false,
			'postTitleTemplate' => '',
			'postContentTemplate' => '',
			'lastPageButton' => null,
			'pagination' => null,
			'firstPageCssClass' => null,
			'subLabelPlacement' => 'below',
			'cssClass' => '',
			'enableHoneypot' => true,
			'enableAnimation' => false,
			'save' => array(
				'enabled' => false,
				'button' => array(
					'type' => 'link',
					'text' => 'Save and Continue Later',
				),
			),
			'limitEntries' => false,
			'limitEntriesCount' => '',
			'limitEntriesPeriod' => '',
			'limitEntriesMessage' => '',
			'scheduleForm' => false,
			'scheduleStart' => '',
			'scheduleStartHour' => '',
			'scheduleStartMinute' => '',
			'scheduleStartAmpm' => '',
			'scheduleEnd' => '',
			'scheduleEndHour' => '',
			'scheduleEndMinute' => '',
			'scheduleEndAmpm' => '',
			'schedulePendingMessage' => '',
			'scheduleMessage' => '',
			'requireLogin' => false,
			'requireLoginMessage' => '',
			'nextFieldId' => 21, // need to figure out what is is about
			'confirmations' => array(),
			'notifications' => array(),
		);
	}
	protected function get_initial_field_data()
	{
		return array(
			'type' => '',
			'id' => 0,
			'label' => '',
			'adminLabel' => '',
			'isRequired' => false,
			'size' => '',
			'errorMessage' => '',
			'visibility' => 'visible',
			'inputs' => null,
			'formId' => 1,
			'description' => '',
			'allowsPrepopulate' => false,
			'inputMask' => false,
			'inputMaskValue' => '',
			'maxLength' => '',
			'inputType' => '',
			'labelPlacement' => '',
			'descriptionPlacement' => '',
			'subLabelPlacement' => '',
			'placeholder' => '',
			'cssClass' => '',
			'inputName' => '',
			'noDuplicates' => false,
			'defaultValue' => '',
			'choices' => '',
			'conditionalLogic' => '',
			'productField' => '',
			'enablePasswordInput' => '',
			'multipleFiles' => false,
			'maxFiles' => '',
			'calculationFormula' => '',
			'calculationRounding' => '',
			'enableCalculation' => '',
			'disableQuantity' => false,
			'displayAllCategories' => false,
			'useRichTextEditor' => false,
			'fields' => '',
			'displayOnly' => '',
		);
	}
	protected function get_label_for_element($element)
	{
		return (!empty($element->tagAttrs['label'])) ? $element->tagAttrs['label'] : '';
	}
	protected function get_json_data_for_input($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'text'; 
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['maxLength'] = (!empty($element->tagAttrs['maxlength'])) ? $element->tagAttrs['maxlength'] : '';
		$data['defaultValue'] = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';

		$data['size'] = 'medium';		
		if(!empty($element->tagAttrs['size']))
		{
			if($element->tagAttrs['size'] > 40) $data['size'] = 'large'; // @todo: check on potential values in Gravity Forms
			elseif($element->tagAttrs['size'] < 20) $data['size'] = 'small'; // @todo: check on potential values in Gravity Forms
		}
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function get_json_data_for_textarea($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'textarea'; 
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['maxLength'] = (!empty($element->tagAttrs['maxlength'])) ? $element->tagAttrs['maxlength'] : '';
		$data['defaultValue'] = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';

		$data['size'] = 'medium';	
		if(!empty($element->tagAttrs['rows']) || !empty($element->tagAttrs['cols']))
		{
			$this->add_message('Did not set rows and columns on "' . $data['label'] . '". This functionality does not exist in Gravity Forms.');
		}
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function get_json_data_for_radiogroup($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'radio';
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['defaultValue'] = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$choices = array();
		foreach($element->tagChildren as $child)
		{
			$value = (!empty($child->tagAttrs['value'])) ? $child->tagAttrs['value'] : '';
			$choices[] = array(
				'text' => $value,
				'value' => $value,
				'isSelected' => (!empty($child->tagAttrs['selected'])) ? true : false,
				'price' => '',
			);
		}
		$data['choices'] = $choices;
		$data = $this->modify_values_for_prefill($data, $element, $form, $gravity_id);
		return $data;
	}
	protected function get_json_data_for_optiongroup($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'select';
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['defaultValue'] = '';
		$choices = array();
		foreach($element->tagChildren as $child)
		{
			$value = (!empty($child->tagAttrs['value'])) ? $child->tagAttrs['value'] : '';
			$choices[] = array(
				'text' => $value,
				'value' => $value,
				'isSelected' => (!empty($child->tagAttrs['selected'])) ? true : false,
				'price' => '',
			);
		}
		$data['choices'] = $choices;
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function get_json_data_for_checkboxgroup($element, $form, $gravity_id) {
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'checkbox';
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['defaultValue'] = '';
		$choices = array();
		$inputs = array();
		$index = 1;
		foreach($element->tagChildren as $child)
		{
			$value = (!empty($child->tagAttrs['value'])) ? $child->tagAttrs['value'] : '';
			$choices[] = array(
				'text' => $value,
				'value' => $value,
				'isSelected' => (!empty($child->tagAttrs['selected'])) ? true : false,
				'price' => '',
			);
			$inputs[] = array(
				'id' => $gravity_id . '.' . $index,
				'label' => $value,
				'name' => '',
			);
			$index++;
		}
		$data['choices'] = $choices;
		$data['inputs'] = $inputs;
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function standardize_upload_restrictions($stuff)
	{
		$rv = Array();
		$explodedStuff = explode(",",$stuff);
		foreach ($explodedStuff as $stuffChunk) {
			$rv[] = strtolower(trim($stuffChunk));
		}
		return $rv;
	}
	protected function get_json_data_for_upload($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'fileupload'; 
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		if (!empty($element->tagAttrs['restrict_extensions'])) {
			$data['allowedExtensions'] = $this->standardize_upload_restrictions($element->tagAttrs['restrict_extensions']);
		}

		if (!empty($element->tagAttrs['restrict_types'])) {
			$this->add_message('Unable to restrict mime types on "' . $data['label'] . '". This restriction is not supported in Gravity forms.');
		}
		if (!empty($element->tagAttrs['restrict_maxsize'])) {
			// @todo figure out the format of this. "1MB" seems to resolve to "1" but need to figure out more
			$this->add_message('Unable to restrict maximum size on "' . $data['label'] . '". Export not yet supported.');
		}
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function get_json_data_for_hidden($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'hidden'; 
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['defaultValue'] = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function get_json_data_for_comment($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'html'; 
		$data['label'] = $this->get_label_for_element($element);
		$data['content'] = $element->tagData;
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function get_json_data_for_date($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'date'; 
		$data['dateType'] = 'datepicker';
		$data['calendarIconType'] = 'calendar';
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['defaultValue'] = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$data = $this->modify_values_for_prefill($data, $element, $form);
		if(!empty($element->tagAttrs['date_field_time_enabled']))
		{
			$this->add_message('Gravity forms does not support a shared date/time field. "'. $data['label'] . '" added as a date field without time component.');
		}
		return $data;
	}
	protected function get_json_data_for_time($element, $form, $gravity_id)
	{
		$data = $this->get_initial_field_data();
		$data['id'] = $gravity_id;
		$data['type'] = 'time'; 
		$data['timeFormat'] = '12';
		$data['label'] = $this->get_label_for_element($element);
		$data['isRequired'] = (!empty($element->tagAttrs['required'])) ? true : false;
		$data['defaultValue'] = (!empty($element->tagAttrs['value'])) ? $element->tagAttrs['value'] : '';
		$data['inputs'] = array(
			array(
				'id' => $gravity_id.'.1',
				'label' => 'HH',
				'name' => '',
			),
			array(
				'id' => $gravity_id.'.2',
				'label' => 'MM',
				'name' => '',
			),
			array(
				'id' => $gravity_id.'.3',
				'label' => 'AM/PM',
				'name' => '',
			),
		);
		$data = $this->modify_values_for_prefill($data, $element, $form);
		return $data;
	}
	protected function does_form_prefill($form)
	{
		switch($form->get_value('magic_string_autofill'))
		{
			case 'editable':
			case 'not_editable':
				return $form->get_value('magic_string_autofill');
			default:
				return false;
		}
	}
	protected function modify_values_for_prefill($data, $element, $form)
	{
		if($this->does_form_prefill($form))
		{
			$label = $this->get_label_for_element($element);
			$match_label = strtolower($label);
			$match_label = trim($match_label);
			$match_label = trim($match_label, ':');
			$match_label = trim($match_label);
			$match_label = str_replace(' ', '_', $match_label);
			$function = 'modify_json_data_for_prefill_' . $match_label;
			if(method_exists($this, $function))
			{
				$data = $this->$function($data, $element, $form);
			}
		}
		return $data;
	}
	protected function modify_json_data_for_prefill_your_name($data, $element, $form)
	{
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'fullName';
		return $data;
	}
	protected function modify_json_data_for_prefill_your_full_name($data, $element, $form)
	{
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'fullName';
		return $data;
	}
	protected function modify_json_data_for_prefill_your_first_name($data, $element, $form)
	{
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'firstName';
		return $data;
	}
	protected function modify_json_data_for_prefill_your_last_name($data, $element, $form)
	{
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'lastName';
		return $data;
	}
	protected function modify_json_data_for_prefill_your_department($data, $element, $form)
	{
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'department';
		return $data;
	}
	protected function modify_json_data_for_prefill_your_email($data, $element, $form)
	{
		$data['type'] = 'email';
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'email';
		return $data;
	}
	protected function modify_json_data_for_prefill_your_home_phone($data, $element, $form)
	{
		$data['type'] = 'phone';
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'phone';
		$data['phoneFormat'] = 'international';
		$this->add_message('"Your Home Phone" given generic "phone" prefill due to limitations in Gravity forms.');
		return $data;
	}
	protected function modify_json_data_for_prefill_your_work_phone($data, $element, $form)
	{
		$data['type'] = 'phone';
		$data['allowsPrepopulate'] = true;
		$data['inputName'] = 'phone';
		$data['phoneFormat'] = 'international';
		$this->add_message('"Your Work Phone" given generic "phone" prefill due to limitations in Gravity forms.');
		return $data;
	}
	protected function modify_json_data_for_prefill_your_title($data, $element, $form)
	{
		$this->add_message('Unable to set prefill for "' . $this->get_label_for_element($element) . '". This prefill is not currently supported in Gravity Forms.');
		return $data;
	}
}