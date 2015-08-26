<?php
// introduced in early 2015 for embedded entity support in publication news/posts. Simple almost-struct that
// represents the embedded tags like:
// [[ IMG ]]
// [[ IMG="2" caption="test" ]]
// [[ ID="999" ]]
// etc.
// 
// this is completely unaware of what any of those things MEANS, so doesn't enforce a legal grammar or anything.
// Also doesn't know how to parse them; that is currently the job of modules/publication/default_embed_handler
// although that responsibility might better be factored out at some point.
class EmbeddedTag {
	public $originalText;
	public $tag;
	public $tagValue;
	public $params;

	function __construct($_orig, $_t, $_tv, $_p = Array()) {
		$this->originalText = $_orig;
		$this->tag = strtoupper($_t);
		$this->tagValue = $_tv;
		$this->params = $_p;
	}

	function addParam($key, $val) {
		$this->params[$key] = $val;
	}

	function hasValue() {
		return !empty($this->tagValue);
	}

	function hasParam($key) {
		return isset($this->params[$key]);
	}

	function getParam($key) {
		// need to unescape any escaped quotes...
		return isset($this->params[$key]) ? str_replace("\\\"", "\"", $this->params[$key]) : "";
	}

	function __toString() {
		$paramRep = "";
		foreach ($this->params as $k => $v) {
			$paramRep .= ($paramRep == "" ? "" : ", ") . $k . "=[" . $v . "]";
		}
		return "{ tag type=[" . $this->tag . "], val=[" . $this->tagValue . "], params=[" . $paramRep . "] (orig=[" . $this->originalText . "]) }";
	}
}
?>
