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
		"hidden" => "hidden_field"
	);

	var $optionMap = array(
		"checkboxgroup" => "checkbox",
		"radiogroup" => "radio",
		"optiongroup" => "option"
	);

	var $fieldMapJtX;

	var $USE_MINIFIED_SCRIPTS = false;
	var $scripts = array(
		'vendorpath'   => '/reason_package/www/formbuilder2/vendor/js/vendor.js',
		'fbpath'       => '/reason_package/www/formbuilder2/formbuilder.js',
		'fbinitpath'   => '/reason_package/www/formbuilder2/formbuilder-init.js'
	);

	var $style = array(
		'vendorpath' => '/reason_package/www/formbuilder2/vendor/css/vendor.css',
		'fbpath' => '/reason_package/www/formbuilder2/formbuilder.css'
	);

	// var $optionMemoryKey = "maxUsedOptionId";
	
	var $mainFieldTranslators;
	var $optionTranslators;

	function formbuilder2Type() {
		$this->useMinified();

		$this->fieldMapJtX = array_flip($this->fieldMapXtJ);

		$this->mainFieldTranslators = array(
			new AttributeTranslator("label"),
			// new AttributeTranslator("cid", "id"),
			new RegexTranslator("/^c(\w*)/",'id_${1}',"/^id_(\w*)/",'c${1}',"cid", "id"),
			new RestrictableAttributeTranslator("text,paragraph", "default_value", "value"),
			new IdentityTranslator("required"),
			new DescriptionPropagatorTranslator("hidden_field", "value"),
			new DescriptionPropagatorTranslator("text_comment", "")
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
		// var_dump("XML FROM DB", $xml);
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
			if (preg_match($emptyOrWhitespaceRegex, $field->label) === 1) {
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
	
	/*
	function _OLD_json_to_xml($json)
	{
		// var_dump("SUBMITTED JSON", $json);

		$form_xml_obj = new  XMLParser('<form></form>');
		$form_xml_obj->Parse();
		$xml_doc = $form_xml_obj->document;

		$decodedJson = json_decode($json);
		// var_dump("DECODED JSON", $decodedJson);
		$json_field_array = $decodedJson->fields;

		foreach ($json_field_array as $field) {
			// var_dump("JSON FIELD", $field);
			if ($field->field_type == 'submit_button') continue;
			
			//build attrs
			$attrs = (array) $field;

			//remove field_type and field_options (handled separately)
			unset($attrs['field_type'], $attrs['field_options']);
			//set the way 'required' works
			if ($attrs['required'] == true) $attrs['required'] = 'required';
			else unset($attrs['required']);
			//rename cid to id
			$attrs["id"] = $attrs['cid'];
			unset( $attrs['cid'] );

			//construct the field tag
			$xmlFieldTag = new XMLTag($this->fieldMapJtX[$field->field_type], $attrs);

			//add options if necessary
			if (isset($field->field_options) && (isset($field->field_options->options))) {// || property_exists($field->field_options->options))) {
				foreach ($field->field_options->options as $option) {
					// var_dump("INDIVIDUAL OPTION", $option);
					if ($option->checked == true) $optionAttrs["selected"] = "selected";
					$optionAttrs["label"] = $optionAttrs["value"] = $option->label;
					$optionAttrs["id"] = 'o' . $option->reasonOptionId;
					$xmlFieldTag->tagChildren[] = new XMLTag($this->optionMap[$xmlFieldTag->tagName], $optionAttrs);
				}
				//}

			// } else {
				// echo "<font color=red>dealing with options!</font><P>";
			}

			// special handling for hidden/comment fields...
			$description = (isset($field->field_options) && isset($field->field_options->description)) ? $field->field_options->description : "";
			if ($field->field_type == "hidden_field") {
				$xmlFieldTag->tagAttrs["value"] = $field->field_options->description;
			} else if ($field->field_type == "text_comment") {
				$xmlFieldTag->tagData = $field->field_options->description;
			}

			//add this new tag to the document
			$xml_doc->tagChildren[] = $xmlFieldTag;
		}
		$xml_doc->tagAttrs['submit'] = end($json_field_array)->field_type == 'submit_button'? end($json_field_array)->label : 'Submit';
		$xml_doc->tagAttrs[$this->optionMemoryKey] = $decodedJson->maxUsedOptionId;
		
		// want to see what the xml we're about to save looks like?
		// var_dump("XML", $xml_doc->GetXML());
		// die();
		
		return('<?xml version="1.0" ?>' . $xml_doc->GetXML());
	}

	function _OLD_xml_to_json($xml)
	{
		$form_xml_obj = new  XMLParser($xml);
		$form_xml_obj->Parse();

		if ($form_xml_obj->document)
		{
			// var_dump("XML_OBJ", $form_xml_obj, "-----");

			$maxOptionIdUsedInDB = 0;
			foreach ($form_xml_obj->document->tagChildren as $k=>$v)
			{
				if (is_object($v)) {
					$fieldName = $this->fieldMapXtJ[$v->tagName];
					$jsonField = $v->tagAttrs;
					$jsonField['field_type'] = $fieldName;

					//rename id to cid
					$jsonField["cid"] = $jsonField['id'];
					unset( $jsonField['id'] );

					//rename value of "required" to a boolean
					$jsonField["required"] = (isset($jsonField["required"]) && $jsonField["required"] == "required");

					//add options if necessary
					if (!empty($v->tagChildren)){
						foreach($v->tagChildren as $option){
							$probeOptionId = (int) substr($option->tagAttrs["id"], 1); // the id in the db is like "o9" and we want just the "9" in the JSON
							$maxOptionIdUsedInDB = max($maxOptionIdUsedInDB, $probeOptionId);

							$jsonField["field_options"]["options"][] = array(
								"checked" => isset($option->tagAttrs["selected"]) && $option->tagAttrs["selected"] == "selected" ? true : false, //to prevent 'null'
								"label" => $option->tagAttrs["label"],
								"reasonOptionId" => $probeOptionId
								);
						}
					}
					// var_dump("JSON", $jsonField, "-----");

					// special handling for hidden/comment fields...
					if ($jsonField['field_type'] == 'hidden_field') {
						$jsonField['field_options'] = array( 'description' => $jsonField['value'] );
						unset($jsonField['value']);
					} else if ($jsonField['field_type'] == 'text_comment') {
						$jsonField['field_options'] = array( 'description' => $v->tagData );
					}


					$form_json_obj[] = $jsonField; //push field onto form
				}
			}
		}
		else
		{
			// no xml - creating a new form
		}

		//add bottom submit
		$form_json_obj[] = array(
			'field_type' => 'submit_button',
			'label' => $form_xml_obj->document ? $form_xml_obj->document->tagAttrs['submit'] : "Submit"
		);

		//wrap json in fields: label

		$form_json_obj = array(
			"fields" => $form_json_obj
		);
	
		$optionMemoryKeyLowered = strtolower($this->optionMemoryKey);
		if (isset($form_xml_obj->document->tagAttrs[$optionMemoryKeyLowered])) {
			$form_json_obj[$this->optionMemoryKey] = $form_xml_obj->document->tagAttrs[$optionMemoryKeyLowered];
		} else {
			// pre-existing forms in the database won't have the maxUsedOptionId attribute set on them. For these we need to fake it by just
			// looking at the actual highest value in use in the db
			$form_json_obj[$this->optionMemoryKey] = $maxOptionIdUsedInDB;
		}

		// var_dump("CONVERTED TO JSON", $form_json_obj);

		return json_encode($form_json_obj);
	}
	 */
}
