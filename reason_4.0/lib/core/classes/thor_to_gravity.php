<?php
/**
 * @package reason
 */

include_once('paths.php');
require_once( INCLUDE_PATH . 'xml/xmlparser.php' );

class reasonFormToGravityJson
{
	protected $messages = array();
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
		));
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
	protected function get_field_type_map()
	{
		return array(
			'input' => 'text',
			'date' => '',
			'time' => '',
			'textarea' => '',
			'radiogroup' => '',
			'checkboxgroup' => '',
			'optiongroup' => 'select',
			'hidden' => '',
			'comment' => '',
			'upload' => '',
			'event_tickets' => '',
		);
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
				$json_data[] = $this->$method($element, $form);
				$index++;
			}
			else
			{
				// @todo include info about the not-included field
				$this->messages[] = 'Field not included in data: ' . $this->get_label_for_element($element) . ' (' . $element->tagName . ')';
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
	protected function get_json_data_for_input($element, $form)
	{
		$data = $this->get_initial_field_data();
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
		return $data;
	}
}