<?php

/*
 * encapsulates the complexity around translating individual attributes on JSON/XML data structures between
 * Reason and Formbuilder. For instance FB might do "{something: { id:1}}" and we might want xml to
 * be <somethingElse cid="o1"/>. formbuilder2.php handles the conversion between "something" and "somethingElse",
 * but the classes here that conform to IReasonFormbuilderTranslator handle the complexity around "id:1" and "cid='o1'".
 *
 * this might work nicely with more of a Decorator pattern so that something could be both restrictable and regex'able, etc...
 *
 */

interface IReasonFormbuilderTranslator {
	public function translateAndAttachToXml($xmlEl, $jsonField);
	public function translateAndAttachToJson(&$jsonField, $xmlEl);
}

// basic functionality - supports straight carryover and optionally different names in json/xml
class AttributeTranslator implements IReasonFormbuilderTranslator {
	protected $jsonFieldName;
	protected $xmlFieldName;

	public function __construct($_jsonFieldName, $_xmlFieldName = null) {
		$this->jsonFieldName = $_jsonFieldName;

		if (!isset($_xmlFieldName)) {
			$this->xmlFieldName = $_jsonFieldName;
		} else {
			$this->xmlFieldName = $_xmlFieldName;
		}
	}

	protected function getJsonFieldName() {
		return $this->jsonFieldName;
	}

	protected function getXmlFieldName() {
		return $this->xmlFieldName;
	}

	// $xmlEl is a SimpleXMLElement
	// $jsonField is a stdClass representing some JSON
	public function translateAndAttachToXml($xmlEl, $jsonField) {
		if (isset($jsonField->{$this->getJsonFieldName()})) {
			// $userInput = $jsonField->{$this->getJsonFieldName()};
			// $sanitized = strip_tags($userInput, '<b><i>');
			// echo "user input was [" . $userInput . "]; sanitized to [" . $sanitized . "]<br>";
			// die();

			$xmlEl->addAttribute($this->getXmlFieldName(), $jsonField->{$this->getJsonFieldName()});
		}
	}

	protected function getDbData($xmlEl) {
		return (string)$xmlEl->attributes()->{$this->getXmlFieldName()};
	}

	public function translateAndAttachToJson(&$jsonField, $xmlEl) {
		$dataFromDb = $this->getDbData($xmlEl);

		if (isset($dataFromDb)) {
			$jsonField[$this->getJsonFieldName()] = $dataFromDb;
		}
	}
}

// works like AttributeTranslator, but also can be restricted to particular fields. constructor accepts a comma-delimited list of 
// formbuilder field types that will be considered when choosing to execute this translator.
class RestrictableAttributeTranslator extends AttributeTranslator {
	protected $fieldRestrictions;

	public function __construct($_fieldRestrictions, $_jsonFieldName, $_xmlFieldName = null) {
		parent::__construct($_jsonFieldName, $_xmlFieldName);

		$this->fieldRestrictions = $_fieldRestrictions == null ? null : explode(",", $_fieldRestrictions);
	}

	public function translateAndAttachToXml($xmlEl, $jsonField) {
		if ($this->fieldRestrictions == null || (isset($jsonField->{'field_type'}) && in_array($jsonField->{'field_type'}, $this->fieldRestrictions))) {
			parent::translateAndAttachToXml($xmlEl, $jsonField);
		}
	}

	public function translateAndAttachToJson(&$jsonField, $xmlEl) {
		if ($this->fieldRestrictions == null || (isset($jsonField["field_type"]) && in_array($jsonField["field_type"], $this->fieldRestrictions))) {
			parent::translateAndAttachToJson($jsonField, $xmlEl);
		}
	}
}

// useful for stuff like:
// "if json includes 'foo=true', xml should include attribute 'bar=bar'"
// for instance, if json includes "checked=true" on an option, we want resulting xml to have attribute "selected=selected" on it.
class IdentityTranslator extends AttributeTranslator {
	public function translateAndAttachToXml($xmlEl, $jsonField) {
		if (isset($jsonField->{$this->getJsonFieldName()}) && $jsonField->{$this->getJsonFieldName()} == true) {
			$xmlEl->addAttribute($this->getXmlFieldName(), $this->getXmlFieldName());
		}
	}

	public function translateAndAttachToJson(&$jsonField, $xmlEl) {
		$dataFromDb = $this->getDbData($xmlEl);

		if (isset($dataFromDb) && $dataFromDb == $this->getXmlFieldName()) {
			// echo $xmlEl->getName() . " is set (" . $this->getXmlFieldName() . ")/(" . $dataFromDb . ")!!!<br>";
			$jsonField[$this->getJsonFieldName()] = true;
		} else {
			$jsonField[$this->getJsonFieldName()] = false;
		}
	}
}

// another approach, this time using an optional passed in argument. At the moment there's only one of these customized
// versions so could just as easily be its own subclass but this might give us flexibility down the line.
// the only client of this approach was reworked in favor of the RegexTranslator
// new CustomFxnTranslator("formatOptionIdForXml", "reasonOptionId", "id")
/*
class CustomFxnTranslator extends AttributeTranslator {
	private $customFunctionName;
	private $validCustomInstance;

	private function formatOptionIdForXml($jsonField) {
		return "o" . $jsonField->{$this->getJsonFieldName()};
	}

	public function __construct($_customFunctionName, $_jsonFieldName, $_xmlFieldName = null) {
		parent::__construct($_jsonFieldName, $_xmlFieldName);
		$this->customFunctionName = $_customFunctionName;

		if (!method_exists($this, $_customFunctionName)) {
			trigger_error("Supplied CustomFxnTranslator method '" . $_customFunctionName . "' " .
							"does not exist for translating between json field '" . $_jsonFieldName . "' and xml field '" . $_xmlFieldName . "'.");
			$this->validCustomInstance = false;
		} else {
			$this->validCustomInstance = true;
		}
	}

	public function translateAndAttachToXml($xmlEl, $jsonField) {
		if ($this->validCustomInstance) {
			if (isset($jsonField->{$this->getJsonFieldName()})) {
				$variableFxn = $this->customFunctionName;
				$xmlEl->addAttribute($this->getXmlFieldName(), $this->$variableFxn($jsonField));
			}
		}
	}
}
*/

// allows use of regexes when converting to/from a format. For instance we store 
// id's on checkbox/radio/dropdown options with a "o" prefix in the xml in the reason db,
// but just as numeric in the formbuilder json. This is overkill for such a thing, but
// should allow us to support more complicated stuff in the future.
class RegexTranslator extends AttributeTranslator {
	private $toXmlSearch;
	private $toXmlReplace;
	private $toJsonSearch;
	private $toJsonReplace;

	public function __construct($_toXmlSearch, $_toXmlReplace, $_toJsonSearch, $_toJsonReplace, $_jsonFieldName, $_xmlFieldName = null) {
		parent::__construct($_jsonFieldName, $_xmlFieldName);

		$this->toXmlSearch = $_toXmlSearch;
		$this->toXmlReplace = $_toXmlReplace;
		$this->toJsonSearch = $_toJsonSearch;
		$this->toJsonReplace = $_toJsonReplace;

		/*
		$jsonData = '125'; $xmlData = 'o125';
		$toXmlFind = "/^([0-9]*)/"; $toXmlReplace = 'o${1}';
		$toJsonFind = '/^o([0-9]*)/'; $toJsonReplace = '${1}';

		echo "data from json is [" . $jsonData . "], regex to use is [" . $toXmlFind . "]/[" . $toXmlReplace . "]<br>";
		$result = preg_replace($toXmlFind, $toXmlReplace, $jsonData);
		echo "match result is [" . $result . "]<br>";
		if ($result != $xmlData) { echo "<hr><font color=red>MATCH FAILED!</font><hr>"; }

		echo "data from xml/db is [" . $xmlData . "], regex to use is [" . $toJsonFind . "]/[" . $toJsonReplace . "]<br>";
		$result = preg_replace($toJsonFind, $toJsonReplace, $xmlData);
		echo "match result is [" . $result . "]<br>";
		if ($result != $jsonData) { echo "<hr><font color=red>MATCH FAILED!</font><hr>"; }
		 */
	}

	private function applyRegexForJsonToXml($dataFromJson) {
		return preg_replace($this->toXmlSearch, $this->toXmlReplace, $dataFromJson);
	}

	private function applyRegexForXmlToJson($dataFromXml) {
		return preg_replace($this->toJsonSearch, $this->toJsonReplace, $dataFromXml);
	}

	public function translateAndAttachToXml($xmlEl, $jsonField) {
		if (isset($jsonField->{$this->getJsonFieldName()})) {
			$massagedData = $this->applyRegexForJsonToXml($jsonField->{$this->getJsonFieldName()});
			$xmlEl->addAttribute($this->getXmlFieldName(), $massagedData);
		}
	}

	public function translateAndAttachToJson(&$jsonField, $xmlEl) {
		$dataFromDb = $this->getDbData($xmlEl);

		if (isset($dataFromDb)) {
			$jsonField[$this->getJsonFieldName()] = $this->applyRegexForXmlToJson($dataFromDb);
		}
	}
}

// some fields (hidden_field and text_comment) include their values in description in 
// the field_options and we need to snatch them out of there...
// you can restrict this translator to particular field types, and stuff/extract the actual
// data either in an attribute name on the xml, or as the content of the node itself.
// class DescriptionPropagatorTranslator extends AttributeTranslator {
class DescriptionPropagatorTranslator implements IReasonFormbuilderTranslator {
	private $restrictToField;
	private $attribName;

	public function __construct($_restrictToField, $_attribName = "") {
		// parent::__construct("", "");
		$this->restrictToField = $_restrictToField;
		$this->attribName = $_attribName;
	}

	public function translateAndAttachToXml($xmlEl, $jsonField) {
		if ($jsonField->field_type == $this->restrictToField) {
			$desc = "";
			if (isset($jsonField->field_options) && isset($jsonField->field_options->description)) {
				$desc = $jsonField->field_options->description;
			}

			if ($this->attribName != "") {
				$xmlEl->addAttribute($this->attribName, $desc);
			} else {
				$xmlEl[0] = $desc;
			}
		}
	}

	public function translateAndAttachToJson(&$jsonField, $xmlEl) {
		// echo "json field [" . $jsonField['field_type'] . "]...<br>";
		if ($jsonField['field_type'] == $this->restrictToField) {
			// $dataFromDb = $this->getDbData($xmlEl);
			if ($this->attribName != "") {
				$dataFromDb = (string)$xmlEl->attributes()->{$this->attribName};
			} else {
				$dataFromDb = (string)$xmlEl; // is this the right way to get the content (with <foo>hello</foo> and xmlEl is the whole node, I am after "hello")? Seems to work ok...
			}

			$extractedVal = "";
			if (isset($dataFromDb)) {
				// echo $xmlEl->getName() . " is set (" . $this->getXmlFieldName() . ")/(" . $dataFromDb . ")!!!<br>";
				$extractedVal = $dataFromDb;
			}
			$jsonField["field_options"]["description"] = $extractedVal;
		}
	}
}

?>
