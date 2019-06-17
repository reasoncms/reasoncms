<?php
/**
 * @package reason
 */

include_once( 'paths.php' );
require_once( INCLUDE_PATH . 'xml/xmlparser.php' );
include_once( CARL_UTIL_INC . 'basic/email_funcs.php' );
reason_include_once( "function_libraries/file_utils.php" );

class reasonFormToGravityJson {
	private $gforms_version = '2.4.10.3';

	protected $messages = array();

	function get_json( $form ) {
		$json_data          = $this->get_initial_form_data();
		$json_data['title'] = $form->get_value( 'name' );

		if ( $form->get_value( 'thor_content' ) ) {
			$xml_object = new XMLParser( $form->get_value( 'thor_content' ) );
			$xml_object->Parse();
			if ( $xml_object ) {
				$json_data['button']['text'] = $this->get_button_label_from_parsed_xml( $xml_object, $form );
				$json_data['fields']         = $this->get_field_data_from_parsed_xml( $xml_object, $form );
			}
		}
		$json_data = $this->add_notifications( $json_data, $form );
		$json_data = $this->add_confirmations( $json_data, $form );
		$json_data = $this->add_access_restrictions( $json_data, $form );
		$json_data = $this->add_scheduling( $json_data, $form );
		$json_data = $this->add_submission_limit( $json_data, $form );
		$json_data = $this->add_editable_rules( $json_data, $form );
		$json_data = $this->modify_custom_form( $json_data, $form );

		return json_encode( array(
			0         => $json_data,
			'version' => $this->gforms_version,
		), JSON_PRETTY_PRINT );
	}

	function add_message( $message ) {
		$this->messages[] = $message;
	}

	function get_messages() {
		return $this->messages;
	}

	function clear_messages() {
		$this->messages = array();
	}

	protected function get_button_label_from_parsed_xml( $xml_object ) {
		return ( ! empty( $xml_object->document->tagAttrs['submit'] ) ) ? $xml_object->document->tagAttrs['submit'] : 'Submit';
	}

	protected function get_field_data_from_parsed_xml( $xml_object, $form ) {
		$json_data = array();
		$index     = 0;
		foreach ( $xml_object->document->tagChildren as $element ) {
			$method = 'get_json_data_for_' . $element->tagName;
			if ( method_exists( $this, $method ) ) {
				$json_data[] = $this->$method( $element, $form, ( $index + 1 ) );
				$index ++;
			} else {
				$this->add_message( 'Not able to include "' . $this->get_label_for_element( $element ) . '". Code has not yet been written to support the export of ' . $element->tagName . ' fields.' );
			}
		}

		return $json_data;
	}

	protected function get_initial_form_data() {
		return array(
			'title'                      => '',
			'description'                => '',
			'labelPlacement'             => 'top_label',
			'descriptionPlacement'       => 'below',
			'button'                     => array(
				'type'     => 'text',
				'text'     => 'Submit',
				'imageUrl' => '',
			),
			'version'                    => $this->gforms_version,
//			'id' => 1, // Not sure if this matters
			'useCurrentUserAsAuthor'     => true,
			'postContentTemplateEnabled' => false,
			'postTitleTemplateEnabled'   => false,
			'postTitleTemplate'          => '',
			'postContentTemplate'        => '',
			'lastPageButton'             => null,
			'pagination'                 => null,
			'firstPageCssClass'          => null,
			'subLabelPlacement'          => 'below',
			'cssClass'                   => '',
			'enableHoneypot'             => true,
			'enableAnimation'            => false,
			'save'                       => array(
				'enabled' => false,
				'button'  => array(
					'type' => 'link',
					'text' => 'Save and Continue Later',
				),
			),
			'limitEntries'               => false,
			'limitEntriesCount'          => '',
			'limitEntriesPeriod'         => '',
			'limitEntriesMessage'        => '',
			'scheduleForm'               => false,
			'scheduleStart'              => '',
			'scheduleStartHour'          => '',
			'scheduleStartMinute'        => '',
			'scheduleStartAmpm'          => '',
			'scheduleEnd'                => '',
			'scheduleEndHour'            => '',
			'scheduleEndMinute'          => '',
			'scheduleEndAmpm'            => '',
			'schedulePendingMessage'     => '',
			'scheduleMessage'            => '',
			'requireLogin'               => false,
			'requireLoginMessage'        => '',
			'nextFieldId'                => 21, // need to figure out what is is about
			'confirmations'              => array(),
			'notifications'              => array(),
		);
	}

	protected function get_initial_field_data() {
		return array(
			'type'                 => '',
			'id'                   => 0,
			'label'                => '',
			'adminLabel'           => '',
			'isRequired'           => false,
			'size'                 => '',
			'errorMessage'         => '',
			'visibility'           => 'visible',
			'inputs'               => null,
			'formId'               => 1,
			'description'          => '',
			'allowsPrepopulate'    => false,
			'inputMask'            => false,
			'inputMaskValue'       => '',
			'maxLength'            => '',
			'inputType'            => '',
			'labelPlacement'       => '',
			'descriptionPlacement' => '',
			'subLabelPlacement'    => '',
			'placeholder'          => '',
			'cssClass'             => '',
			'inputName'            => '',
			'noDuplicates'         => false,
			'defaultValue'         => '',
			'choices'              => '',
			'conditionalLogic'     => '',
			'productField'         => '',
			'enablePasswordInput'  => '',
			'multipleFiles'        => false,
			'maxFiles'             => '',
			'calculationFormula'   => '',
			'calculationRounding'  => '',
			'enableCalculation'    => '',
			'disableQuantity'      => false,
			'displayAllCategories' => false,
			'useRichTextEditor'    => false,
			'fields'               => '',
			'displayOnly'          => '',
		);
	}

	protected function add_notifications( $data, $form ) {
		if ( $form->get_value( 'email_of_recipient' ) ) {
			$unique_id             = uniqid();
			$data['notifications'] = array(
				$unique_id => array(
					'isActive'          => true,
					'id'                => $unique_id,
					'name'              => 'Notification',
					'service'           => 'wordpress',
					'event'             => 'form_submission',
					'to'                => prettify_email_addresses( $form->get_value( 'email_of_recipient' ), 'mixed', 'string' ),
					'toType'            => 'email',
					'cc'                => '',
					'bcc'               => '',
					'subject'           => 'New submission from {form_title}',
					'message'           => '{all_fields}',
					'from'              => '', // Does this work to leave empty? Is there a reasonable fallback in WP?
					'fromName'          => '',
					'replyTo'           => '',
					'routing'           => null,
					'conditionalLogic'  => null,
					'disableAutoformat' => false,
					'enableAttachments' => false,
				)
			);
		}
		$errors      = prettify_email_addresses( $form->get_value( 'email_of_recipient' ), 'mixed', 'errors' );
		$error_count = count( $errors );
		if ( $error_count ) {
			$this->add_message( 'Recipient email(s) included ' . $error_count . ' username(s) that could not be resolved to an email address. These usernames have been replaced with the webmaster email address. Please resolve this in Gravity Forms.' );
		}

		return $data;
	}

	protected function add_confirmations( $data, $form ) {
		$unique_id             = uniqid();
		$data['confirmations'] = array(
			$unique_id => array(
				'id'                => $unique_id,
				'name'              => 'Default Confirmation',
				'isDefault'         => true,
				'type'              => 'message',
				'message'           => $form->get_value( 'thank_you_message' ),
				'url'               => '',
				'pageId'            => 0,
				'queryString'       => '',
				'disableAutoformat' => false,
				'conditionalLogic'  => array(),
			)
		);
		if ( $form->get_value( 'email_submitter' ) ) {
			$this->add_message( 'This form is set up to email the submitter. Export code not yet written to transfer this automatically, so it will need to be manually configured in Gravity Forms.' );
		}

		return $data;
	}

	protected function add_access_restrictions( $data, $form ) {
		$groups = $form->get_left_relationship( 'form_to_authorized_viewing_group' );
		if ( count( $groups ) > 0 ) {
			$data['requireLogin'] = true;
			$this->add_message( 'This Reason form is set to limit access to a group. Note that the Gravity Forms import process only supports broad access control -- anyone who can log in will be able to access this form until more specific access control is manually applied in WordPress on the page the form is placed on.' );
		}

		return $data;
	}

	protected function add_scheduling( $data, $form ) {
		if ( $form->get_value( 'open_date' ) && $form->get_value( 'open_date' ) != '0000-00-00 00:00:00' ) {
			$data['scheduleForm']        = true;
			$data['scheduleStart']       = prettify_mysql_datetime( $form->get_value( 'open_date' ), 'm/d/Y' );
			$data['scheduleStartHour']   = (integer) prettify_mysql_datetime( $form->get_value( 'open_date' ), 'g' );
			$data['scheduleStartMinute'] = (integer) prettify_mysql_datetime( $form->get_value( 'open_date' ), 'i' );
			$data['scheduleStartAmpm']   = prettify_mysql_datetime( $form->get_value( 'open_date' ), 'a' );
		}
		if ( $form->get_value( 'close_date' ) && $form->get_value( 'close_date' ) != '0000-00-00 00:00:00' ) {
			$data['scheduleForm']      = true;
			$data['scheduleEnd']       = prettify_mysql_datetime( $form->get_value( 'close_date' ), 'm/d/Y' );
			$data['scheduleEndHour']   = (integer) prettify_mysql_datetime( $form->get_value( 'close_date' ), 'g' );
			$data['scheduleEndMinute'] = (integer) prettify_mysql_datetime( $form->get_value( 'close_date' ), 'i' );
			$data['scheduleEndAmpm']   = prettify_mysql_datetime( $form->get_value( 'close_date' ), 'a' );
		}

		return $data;
	}

	protected function add_submission_limit( $data, $form ) {
		if ( $form->get_value( 'submission_limit' ) > 0 ) {
			$data['limitEntries']      = true;
			$data['limitEntriesCount'] = (integer) $form->get_value( 'submission_limit' );
		}

		return $data;
	}

	protected function modify_custom_form( $data, $form ) {
		if ( $form->get_value( 'thor_view' ) ) {
			$this->add_message( 'This form has custom behavior. Export code not yet written to automatically transfer custom behavior to Gravity Forms.' );
		}

		return $data;
	}

	protected function add_editable_rules( $data, $form ) {
		if ( $form->get_value( 'is_editable' ) == 'yes' ) {
			$this->add_message( 'This form allows submitters to come back and edit previously submitted entries. This export does not yet support automatically transferring that functionality to Gravity Forms, so it will need to be manually configured in WordPress.' );
			if ( $form->get_value( 'allow_multiple' ) == 'yes' ) {
				// stub for when we add support for this
			}
			if ( $form->get_value( 'email_link' ) == 'yes' ) {
				// stub for when we add support for this
			}
		}

		return $data;
	}

	protected function get_label_for_element( $element ) {
		return ( ! empty( $element->tagAttrs['label'] ) ) ? $element->tagAttrs['label'] : '';
	}

	protected function get_json_data_for_input( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'text';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['maxLength']    = ( ! empty( $element->tagAttrs['maxlength'] ) ) ? $element->tagAttrs['maxlength'] : '';
		$data['defaultValue'] = ( ! empty( $element->tagAttrs['value'] ) ) ? $element->tagAttrs['value'] : '';

		$data['size'] = 'medium';
		if ( ! empty( $element->tagAttrs['size'] ) ) {
			if ( $element->tagAttrs['size'] > 40 ) {
				$data['size'] = 'large';
			} // @todo: check on potential values in Gravity Forms
			elseif ( $element->tagAttrs['size'] < 20 ) {
				$data['size'] = 'small';
			} // @todo: check on potential values in Gravity Forms
		}
		$data = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function get_json_data_for_textarea( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'textarea';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['maxLength']    = ( ! empty( $element->tagAttrs['maxlength'] ) ) ? $element->tagAttrs['maxlength'] : '';
		$data['defaultValue'] = ( ! empty( $element->tagAttrs['value'] ) ) ? $element->tagAttrs['value'] : '';

		$data['size'] = 'medium';
		if ( ! empty( $element->tagAttrs['rows'] ) || ! empty( $element->tagAttrs['cols'] ) ) {
			$this->add_message( 'Did not set rows and columns on "' . $data['label'] . '". This functionality does not exist in Gravity Forms.' );
		}
		$data = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function get_json_data_for_radiogroup( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'radio';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['defaultValue'] = ( ! empty( $element->tagAttrs['value'] ) ) ? $element->tagAttrs['value'] : '';
		$choices              = array();
		foreach ( $element->tagChildren as $child ) {
			$value     = ( ! empty( $child->tagAttrs['value'] ) ) ? $child->tagAttrs['value'] : '';
			$choices[] = array(
				'text'       => $value,
				'value'      => $value,
				'isSelected' => ( ! empty( $child->tagAttrs['selected'] ) ) ? true : false,
				'price'      => '',
			);
		}
		$data['choices'] = $choices;
		$data            = $this->modify_values_for_prefill( $data, $element, $form, $gravity_id );

		return $data;
	}

	protected function get_json_data_for_optiongroup( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'select';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['defaultValue'] = '';
		$choices              = array();
		foreach ( $element->tagChildren as $child ) {
			$value     = ( ! empty( $child->tagAttrs['value'] ) ) ? $child->tagAttrs['value'] : '';
			$choices[] = array(
				'text'       => $value,
				'value'      => $value,
				'isSelected' => ( ! empty( $child->tagAttrs['selected'] ) ) ? true : false,
				'price'      => '',
			);
		}
		$data['choices'] = $choices;
		$data            = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function get_json_data_for_checkboxgroup( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'checkbox';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['defaultValue'] = '';
		$choices              = array();
		$inputs               = array();
		$index                = 1;
		foreach ( $element->tagChildren as $child ) {
			$value     = ( ! empty( $child->tagAttrs['value'] ) ) ? $child->tagAttrs['value'] : '';
			$choices[] = array(
				'text'       => $value,
				'value'      => $value,
				'isSelected' => ( ! empty( $child->tagAttrs['selected'] ) ) ? true : false,
				'price'      => '',
			);
			$inputs[]  = array(
				'id'    => $gravity_id . '.' . $index,
				'label' => $value,
				'name'  => '',
			);
			$index ++;
		}
		$data['choices'] = $choices;
		$data['inputs']  = $inputs;
		$data            = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function standardize_upload_restrictions( $stuff ) {
		$rv            = Array();
		$explodedStuff = explode( ",", $stuff );
		foreach ( $explodedStuff as $stuffChunk ) {
			$rv[] = strtolower( trim( $stuffChunk ) );
		}

		return $rv;
	}

	protected function get_json_data_for_upload( $element, $form, $gravity_id ) {
		$data               = $this->get_initial_field_data();
		$data['id']         = $gravity_id;
		$data['type']       = 'fileupload';
		$data['label']      = $this->get_label_for_element( $element );
		$data['isRequired'] = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		if ( ! empty( $element->tagAttrs['restrict_extensions'] ) ) {
			$data['allowedExtensions'] = $this->standardize_upload_restrictions( $element->tagAttrs['restrict_extensions'] );
		}

		if ( ! empty( $element->tagAttrs['restrict_types'] ) ) {
			$this->add_message( 'Unable to restrict mime types on "' . $data['label'] . '". This restriction is not supported in Gravity forms.' );
		}
		if ( ! empty( $element->tagAttrs['restrict_maxsize'] ) ) {
			if ( strpos( '.', $element->tagAttrs['restrict_maxsize'] ) !== false ) {
				$this->add_message( 'Unable to apply fractional file size restriction in Gravity Forms on "' . $data['label'] . '"' );
			} else {
				if ( substr( $element->tagAttrs['restrict_maxsize'], - 2 ) != 'MB' ) {
					$this->add_message( 'Gravity Forms only supports file size restrictions in whole megabytes. Size restriction on "' . $data['label'] . '" is not in megabytes. Rounded up to a whole megabyte value.' );
				}
				$bytes = convertFormattedSizeToNumberOfBytes( $element->tagAttrs['restrict_maxsize'] );
				if ( $bytes > 0 ) {
					$megabytes           = ceil( $bytes / 1048576 );
					$data['maxFileSize'] = $megabytes;
				} else {
					$this->add_message( 'Unable to apply file size restriction on "' . $data['label'] . '"' );
				}
			}
		}
		$data = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function get_json_data_for_hidden( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'hidden';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['defaultValue'] = ( ! empty( $element->tagAttrs['value'] ) ) ? $element->tagAttrs['value'] : '';
		$data                 = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function get_json_data_for_comment( $element, $form, $gravity_id ) {
		$data            = $this->get_initial_field_data();
		$data['id']      = $gravity_id;
		$data['type']    = 'html';
		$data['label']   = $this->get_label_for_element( $element );
		$data['content'] = $element->tagData;
		$data            = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function get_json_data_for_date( $element, $form, $gravity_id ) {
		$data                     = $this->get_initial_field_data();
		$data['id']               = $gravity_id;
		$data['type']             = 'date';
		$data['dateType']         = 'datepicker';
		$data['calendarIconType'] = 'calendar';
		$data['label']            = $this->get_label_for_element( $element );
		$data['isRequired']       = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['defaultValue']     = ( ! empty( $element->tagAttrs['value'] ) ) ? $element->tagAttrs['value'] : '';
		$data                     = $this->modify_values_for_prefill( $data, $element, $form );
		if ( ! empty( $element->tagAttrs['date_field_time_enabled'] ) ) {
			$this->add_message( 'Gravity forms does not support a shared date/time field. "' . $data['label'] . '" added as a date field without time component.' );
		}

		return $data;
	}

	protected function get_json_data_for_time( $element, $form, $gravity_id ) {
		$data                 = $this->get_initial_field_data();
		$data['id']           = $gravity_id;
		$data['type']         = 'time';
		$data['timeFormat']   = '12';
		$data['label']        = $this->get_label_for_element( $element );
		$data['isRequired']   = ( ! empty( $element->tagAttrs['required'] ) ) ? true : false;
		$data['defaultValue'] = ( ! empty( $element->tagAttrs['value'] ) ) ? $element->tagAttrs['value'] : '';
		$data['inputs']       = array(
			array(
				'id'    => $gravity_id . '.1',
				'label' => 'HH',
				'name'  => '',
			),
			array(
				'id'    => $gravity_id . '.2',
				'label' => 'MM',
				'name'  => '',
			),
			array(
				'id'    => $gravity_id . '.3',
				'label' => 'AM/PM',
				'name'  => '',
			),
		);
		$data                 = $this->modify_values_for_prefill( $data, $element, $form );

		return $data;
	}

	protected function does_form_prefill( $form ) {
		switch ( $form->get_value( 'magic_string_autofill' ) ) {
			case 'editable':
			case 'not_editable':
				return $form->get_value( 'magic_string_autofill' );
			default:
				return false;
		}
	}

	protected function modify_values_for_prefill( $data, $element, $form ) {
		if ( $this->does_form_prefill( $form ) ) {
			$label       = $this->get_label_for_element( $element );
			$match_label = strtolower( $label );
			$match_label = trim( $match_label );
			$match_label = trim( $match_label, ':' );
			$match_label = trim( $match_label );
			$match_label = str_replace( ' ', '_', $match_label );
			$function    = 'modify_json_data_for_prefill_' . $match_label;
			if ( method_exists( $this, $function ) ) {
				$data = $this->$function( $data, $element, $form );
			}
		}

		return $data;
	}

	protected function modify_json_data_for_prefill_your_name( $data, $element, $form ) {
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'fullName';

		return $data;
	}

	protected function modify_json_data_for_prefill_your_full_name( $data, $element, $form ) {
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'fullName';

		return $data;
	}

	protected function modify_json_data_for_prefill_your_first_name( $data, $element, $form ) {
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'firstName';

		return $data;
	}

	protected function modify_json_data_for_prefill_your_last_name( $data, $element, $form ) {
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'lastName';

		return $data;
	}

	protected function modify_json_data_for_prefill_your_department( $data, $element, $form ) {
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'department';

		return $data;
	}

	protected function modify_json_data_for_prefill_your_email( $data, $element, $form ) {
		$data['type']              = 'email';
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'email';

		return $data;
	}

	protected function modify_json_data_for_prefill_your_home_phone( $data, $element, $form ) {
		$data['type']              = 'phone';
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'phone';
		$data['phoneFormat']       = 'international';
		$this->add_message( '"Your Home Phone" given generic "phone" prefill due to limitations in Gravity forms.' );

		return $data;
	}

	protected function modify_json_data_for_prefill_your_work_phone( $data, $element, $form ) {
		$data['type']              = 'phone';
		$data['allowsPrepopulate'] = true;
		$data['inputName']         = 'phone';
		$data['phoneFormat']       = 'international';
		$this->add_message( '"Your Work Phone" given generic "phone" prefill due to limitations in Gravity forms.' );

		return $data;
	}

	protected function modify_json_data_for_prefill_your_title( $data, $element, $form ) {
		$this->add_message( 'Unable to set prefill for "' . $this->get_label_for_element( $element ) . '". This prefill is not currently supported in Gravity Forms.' );

		return $data;
	}
}
