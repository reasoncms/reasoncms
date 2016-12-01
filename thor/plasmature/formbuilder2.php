<?php

/**
 * Formbuilder2 plasmature type for disco integration
 * @package thor
 * @subpackage plasmature
 */
/*
include("reason_header.php");
require_once("disco/plasmature/plasmature.php");

echo "INCLUDE_PATH: " . INCLUDE_PATH . "<p>";
echo "PLASMATURE_TYPES: " . PLASMATURE_TYPES_INC . "<p>";
echo "DISCO_INC: " . DISCO_INC . "<p>";
echo "CARL_UTIL_INC: " . CARL_UTIL_INC . "<p>";
echo "TYR_INC: " . TYR_INC . "<p>";
 */

require_once PLASMATURE_TYPES_INC."default.php";
include_once (XML_PARSER_INC . 'xmlparser.php');
include_once ('translators.php');
require_once 'reason_header.php';
reason_include_once( 'function_libraries/event_tickets.php' );

/**
 * Formbuilder2 plasmature type for disco integration
 * @package thor
 * @subpackage plasmature
 */
		ini_set("xdebug.var_display_max_children", -1);
		ini_set("xdebug.var_display_max_data", -1);
		ini_set("xdebug.var_display_max_depth", -1);

class formbuilder2Type extends textareaType
{
	var $type = 'formbuilder2';

	var $fieldMapXtJ = array(
		"comment" => "text_comment",
		"input" => "text",
		"textarea" => "paragraph",
		"radiogroup" => "radio",
		"checkboxgroup" => "checkboxes",
		"optiongroup" => "dropdown",
		"hidden" => "hidden_field",
		"upload" => "file",
		"event_tickets" => "event_tickets"
	);

	var $optionMap = array(
		"checkboxgroup" => "checkbox",
		"radiogroup" => "radio",
		"optiongroup" => "option"
	);

	var $fieldMapJtX;

	var $USE_MINIFIED_SCRIPTS = false;
/*
	var $scripts = array(
		'vendorpath'   => '/reason_package/www/formbuilder2/vendor/js/vendor.js',
		'fbpath'       => '/reason_package/www/formbuilder2/formbuilder.js',
		'fbinitpath'   => '/reason_package/www/formbuilder2/formbuilder-init.js'
	);

	var $style = array(
		'vendorpath' => '/reason_package/www/formbuilder2/vendor/css/vendor.css',
		'fbpath' => '/reason_package/www/formbuilder2/formbuilder.css'
	);
*/

	// var $optionMemoryKey = "maxUsedOptionId";
	
	var $mainFieldTranslators;
	var $optionTranslators;

	function formbuilder2Type() {
		$this->scripts = array(
			'vendorpath'   => REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder2/vendor/js/vendor.js',
			'fbpath'       => REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder2/formbuilder.js',
			'fbinitpath'   => REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder2/formbuilder-init.js'
		);

		$this->style = array(
			'vendorpath' => REASON_PACKAGE_HTTP_BASE_PATH . 'formbuilder2/vendor/css/vendor.css',
			'fbpath' => REASON_PACKAGE_HTTP_BASE_PATH .'formbuilder2/formbuilder.css'
		);

		$this->useMinified();

		$this->fieldMapJtX = array_flip($this->fieldMapXtJ);

		$this->mainFieldTranslators = array(
			new AttributeTranslator("label"),
			new RestrictableAttributeTranslator("file", "file_upload_extension_restrictions", "restrict_extensions"),
			new RestrictableAttributeTranslator("file", "file_upload_type_restrictions", "restrict_types"),
			new RestrictableAttributeTranslator("file", "file_upload_size_restriction", "restrict_maxsize"),
			// new AttributeTranslator("cid", "id"),
			new RegexTranslator("/^c(\w*)/",'id_${1}',"/^id_(\w*)/",'c${1}',"cid", "id"),
			new RestrictableAttributeTranslator("text,paragraph", "default_value", "value"),
			new IdentityTranslator("required"),
			new DescriptionPropagatorTranslator("hidden_field", "value"),
			new DescriptionPropagatorTranslator("text_comment", ""),
			new RestrictableAttributeTranslator("event_tickets", "event_tickets_event_id", "event_id"),
			new RestrictableAttributeTranslator("event_tickets", "event_tickets_num_total_available", "num_total_available"),
			new RestrictableAttributeTranslator("event_tickets", "event_tickets_max_per_person", "max_per_person"),
			new RestrictableAttributeTranslator("event_tickets", "event_tickets_event_close_datetime", "event_close_datetime"),
		);

		$this->optionTranslators = array(
			new IdentityTranslator("checked", "selected"),
			new AttributeTranslator("label"),
			new AttributeTranslator("label", "value"),
			// new RegexTranslator("/^([0-9]*)/",'o${1}',"/^o([0-9]*)/",'${1}',"reasonOptionId", "id")
			new RegexTranslator("/^c(\w*)/",'id_${1}',"/^id_(\w*)/",'c${1}',"reasonOptionId", "id")
		);
	}

	function useMinified() {
		if ($this->USE_MINIFIED_SCRIPTS) {
			$probeVal = "formbuilder.";
			$probeReplacement = "formbuilder-min.";

			$groupIdx = 0;
			foreach (array($this->scripts, $this->style) as $includeArray) {
				foreach ($includeArray as $arrayIdx => $includeFilePath) {
					$probePos = strpos($includeFilePath, "formbuilder.");
					if ($probePos !== false) {
						$includeFilePath = substr($includeFilePath,0,$probePos) .
											$probeReplacement . 
											substr($includeFilePath,$probePos+strlen($probeVal));

						if ($groupIdx == 0) {
							$this->scripts[$arrayIdx] = $includeFilePath;
						} else {
							$this->style[$arrayIdx] = $includeFilePath;
						}
					}
				}
				$groupIdx++;
			}
		}
	}

	function display()
	{
		$xml = $this->get();
		// strip leading spaces?
		// $xml = preg_replace("/^\s+<\s*\?xml +/", "<?xml ", $xml);
		$json = $this->xml_to_json($xml);
		// var_dump("XML", $xml, "JSON", $json);
		// var_dump("JSON", json_decode($json));
		echo '<textarea name="'.$this->name.'" rows="'.$this->rows.'" cols="'.$this->cols.'">'.htmlspecialchars($json,ENT_QUOTES,'UTF-8').'</textarea>';
		// output necessary css
		foreach($this->style as $sheet) echo '<link rel="stylesheet" type="text/css" href="' . $sheet . '">';
		// output necesssary javascript
		foreach($this->scripts as $scriptPath) echo '<script src="' . $scriptPath . '"></script>';
		echo '<script language="JavaScript">$(window).load(function(){ /*console.log("JQuery is [" + $ + "]");*/ initializeFormbuilder($, Formbuilder, "thorcontentItem");});</script>';

		// echo '<style> #thorcontentItem { background-color:grey; } </style>';

		// ini_set('xdebug.var_display_max_depth', '8');
	}

	function grab_value()
	{
		return $this->json_to_xml(parent::grab_value());
	}
	
	function xml_to_json($xml) {
		// echo "<PRE>"; var_dump("XML FROM DB", $xml); echo "</PRE>";
		$rootXml = $xml == null ? null : simplexml_load_string($xml);

		// $maxOptionIdUsedInDB = 0;
		if ($rootXml != null) {
			$form_json_obj = array("fields" => array());
			foreach ($rootXml->children() as $childEl) {
				$childAttribs = $childEl->attributes();

				$jsonFieldName = $this->fieldMapXtJ[$childEl->getName()];
				// echo "<br>childEl [" . $childEl->getName() . "]->[" .$jsonFieldName . "]";

				$jsonObj = array("field_type" => $jsonFieldName);
				// echo "BEFORE: id was [" . (string)$childEl->attributes()->{'id'} . "]";
				foreach ($this->mainFieldTranslators as $translator) { $translator->translateAndAttachToJson($jsonObj, $childEl); }
				// echo "...AFTER TRANSLATORS: id is [" . $jsonObj["cid"] . "]<P>";

				// Mark found this bug - pre-existing forms with text comments were complaining about missing labels
				// on text comments. When retrieving from the db let's explicitly add a label to match what Formbuilder uses
				// so that Reason saving works ok. --tjf 20141024
				if ($jsonFieldName == "text_comment" && $jsonObj["label"] == "") {
					$jsonObj["label"] = "Text Comment";
				}
				

				// Event ticket types don't use a label, add a dummy label
				if ($jsonFieldName == "event_tickets" && $jsonObj["label"] == "") {
					$jsonObj["label"] = "dynamic_field_not_set_by_user_here";
				}

				if (isset($this->optionMap[$childEl->getName()])) {
					$expectedChildNodeName = $this->optionMap[$childEl->getName()];
					// echo "<p>look for child nodes of [" . $childEl->getName() . "]; should be [" . $expectedChildNodeName . "]";
					foreach ($childEl->children() as $optionEl) {
						if ($optionEl->getName() == $expectedChildNodeName) {
							$optionObj = array();
							foreach ($this->optionTranslators as $translator) {
								$translator->translateAndAttachToJson($optionObj, $optionEl);
							}
							$jsonObj["field_options"]["options"][] = $optionObj;

							// $probeOptionId = $optionObj["reasonOptionId"];
							// $maxOptionIdUsedInDB = max($maxOptionIdUsedInDB, $probeOptionId);
						}
					}
				}

				$form_json_obj["fields"][] = $jsonObj;
			}
		}
		
		//add bottom submit
		$form_json_obj["fields"][] = array(
			'field_type' => 'submit_button',
			'label' => ($rootXml != null && isset($rootXml->attributes()->submit) ? (string)$rootXml->attributes()->submit : "Submit")
		);

		/*
		$optionMemoryKeyLowered = strtolower($this->optionMemoryKey);
		if ($rootXml != null && isset($rootXml->attributes()->{$this->optionMemoryKey})) {
			$form_json_obj[$this->optionMemoryKey] = (string)$rootXml->attributes()->{$this->optionMemoryKey};
		} else {
			// pre-existing forms in the database won't have the maxUsedOptionId attribute set on them. For these we need to fake it by just
			// looking at the actual highest value in use in the db
			$form_json_obj[$this->optionMemoryKey] = $maxOptionIdUsedInDB;
		}
		 */

		// var_dump("---NEW METHOD---", json_decode(json_encode($form_json_obj)));
		// var_dump("---OLD METHOD---", json_decode($this->_OLD_xml_to_json($xml)));

		return json_encode($form_json_obj);
	}

	function json_to_xml($json) {
		if ($json == null) {
			return "";
		}

		$decodedJson = json_decode($json);

		// var_dump("DECODED JSON", $decodedJson);

		$rootXml = new SimpleXMLElement("<form></form>");

		$jsonFieldArray = $decodedJson->fields;

		$rootXml->addAttribute("submit", end($jsonFieldArray)->field_type == "submit_button" ? end($jsonFieldArray)->label : "Submit");
		// $rootXml->addAttribute($this->optionMemoryKey, $decodedJson->maxUsedOptionId);

		$fieldIdx = 0;
		$formErrors = "";
		foreach ($jsonFieldArray as $field) {
			if ($field->field_type == 'submit_button') { continue; }

			$emptyOrWhitespaceRegex = '/^\s*$/';
			// echo "field type: [" . $field->field_type . "], label [" . $field->label . "]<br>";
			if (preg_match($emptyOrWhitespaceRegex, $field->label) === 1 && $field->field_type != 'event_tickets') {
				$formErrors .= ($formErrors == "" ? "" : "<br>") . "Form field #" . ($fieldIdx+1) . " does not have a label";
			}

			if ($field->field_type == "checkboxes" || $field->field_type == "radio" || $field->field_type == "dropdown") {
				$optionsArray = $field->field_options->options ? $field->field_options->options : array();
				if (count($optionsArray) == 0) {
					$formErrors .= ($formErrors == "" ? "" : "<br>") . "Form field #" . ($fieldIdx+1) . ", '" . $field->label . "', does not have any options";
				} else {
					/*
					foreach ($optionsArray as $optIdx => $loopOption) {
						if (preg_match($emptyOrWhitespaceRegex, $loopOption->label) === 1) {
							$formErrors .= ($formErrors == "" ? "" : "<br>") . "Form field #" . ($fieldIdx+1) . ", '" . $field->label . "',  option #" . ($optIdx+1) . " does not have a name";
						}
					}
					 */

					for ($optIdx = count($optionsArray) - 1 ; $optIdx >= 0 ; $optIdx--) {
						$loopOption = $optionsArray[$optIdx];
						if (preg_match($emptyOrWhitespaceRegex, $loopOption->label) === 1) {
							if ($optIdx == count($optionsArray) - 1 && $optIdx != 0) {
								// if it's the last element, and it's not the first element, and it's empty
								// we'll be forgiving - chop it off and keep going
								array_splice($optionsArray, -1);
								$field->field_options->options = $optionsArray;
							} else {
								$formErrors .= ($formErrors == "" ? "" : "<br>") . "Form field #" . ($fieldIdx+1) . ", '" . $field->label . "',  option #" . ($optIdx+1) . " does not have a name";
							}
						}
					}
				}
			}

			if ($field->field_type == 'event_tickets') {
				if (!isset($field->event_tickets_event_id)) {
					$formErrors .= ($formErrors == "" ? "" : "<br>") . "Ticket Slot (field #" . ($fieldIdx + 1) . ") does not have an event selected";
				} else {
					// Event ticket types don't use a label in the form builder,
					// recheck the label on each save. The recheck every time is
					// necessary to accomodate duplicating forms and the event element
					$field = $this->add_event_ticket_title_to_field($field);
				}
			}

			$fieldNode = $rootXml->addChild($this->fieldMapJtX[$field->field_type]);

			foreach ($this->mainFieldTranslators as $translator) { $translator->translateAndAttachToXml($fieldNode, $field); }
			
			//add options if necessary
			if (isset($field->field_options) && (isset($field->field_options->options))) {
				foreach ($field->field_options->options as $option) {
					$optionNode = $fieldNode->addChild($this->optionMap[$fieldNode->getName()]);
					foreach ($this->optionTranslators as $translator) { $translator->translateAndAttachToXml($optionNode, $option, $field); }
				}
			}
			$fieldIdx++;
		}

		// if ($formErrors == "") { $formErrors = "HARDCODED TEST ERROR"; }
		if ($formErrors != "") {
			$this->set_error($formErrors);
		}

		/*
		echo "XML DOC:<TEXTAREA ROWS=10 COLS=50>"; echo $rootXml->asXML(); echo "</TEXTAREA>";
		var_dump($rootXml->asXML());
		// var_dump("-------OLD METHOD", $this->_OLD_json_to_xml($json));
		die();
		*/

		return $rootXml->asXML();
	}
	function add_event_ticket_title_to_field($field)
	{
		$label = get_pretty_ticketed_event_name($field->event_tickets_event_id);
		$field->label = $label;
		return $field;
	}
}
