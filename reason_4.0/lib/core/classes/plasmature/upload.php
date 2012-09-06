<?php

/**
 * Enhanced file upload Plasmature types for Reason.
 * 
 * @package reason
 * @subpackage classes
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

reason_require_once('classes/entity.php');
reason_require_once('classes/head_items.php');
reason_require_once('function_libraries/asset_functions.php');
reason_require_once('function_libraries/image_tools.php');
reason_require_once('function_libraries/upload.php');
require_once CARL_UTIL_INC.'basic/cleanup_funcs.php';
include_once( DISCO_INC.'plasmature/plasmature.php' );

if (!defined('REASON_FLASH_UPLOAD_URI')) {
	define('REASON_FLASH_UPLOAD_URI', REASON_HTTP_BASE_PATH.'flash_upload/');
}

$GLOBALS['_disco_upload_session'] = null;
$GLOBALS['_disco_upload_session_sent'] = false;
$GLOBALS['_disco_upload_head_items_shown'] = false;

// There is some unavoidable code repetition here, due to PHP 4/5's lack of
// either traits (mixins) or multiple inheritance. If PHP 6 does implement the
// horizontal reuse RFC and we drop support for PHP 5, this code can be
// reorganized, and you should probably celebrate such a fantastic occurrence
// if you haven't already thoroughly done so.

/**
 * Reason asset uploads.
 */
class ReasonUploadType extends uploadType
{
	var $type = "ReasonUpload";
	var $type_valid_args = array('authenticator', 'existing_entity',
		'head_items');

	/** @access private */
	var $upload_sid = null;
	/** @access private */
	var $head_items;
	/** @access private */
	var $existing_entity;

	function additional_init_actions($args=array())
	{
		$auth = @$args['authenticator'];
		$this->upload_sid = _get_disco_async_upload_session($auth);
		
		$constraints = array(
			'mime_type' => $this->acceptable_types,
			'extension' => $this->acceptable_extensions,
			'max_size' => $this->max_file_size
		);
		reason_add_async_upload_constraints($this->upload_sid, $this->name,
			$constraints);
		
		if (isset($args["head_items"]))
			$this->get_head_items($args["head_items"]);
		
		_reason_upload_handle_entity($this, "asset",
			"reason_get_asset_filesystem_location", "reason_get_asset_url");
		
		return parent::additional_init_actions($args);
	}
	
	function register_fields()
	{
		return array_merge(_get_disco_async_upload_internal_field_names(),
			parent::register_fields());
	}
	
	function _get_uploaded_file()
	{
		return _reason_get_disco_uploaded_file($this->name, $this->upload_sid);
	}
	
	function get_head_items(&$head)
	{
		_populate_flash_upload_head_items($head);
	}
	
	function _get_hidden_display($current)
	{
		return _ensure_async_upload_head_items_shown().
			_get_disco_async_upload_hidden_fields($this->upload_sid).
			parent::_get_hidden_display($current);
	}
	
	function _get_current_file_display($current)
	{
		if (!$current) {
			// force an (empty) current file display to be shown
			$current = new stdClass;
			$current->path = false;
		}
		return parent::_get_current_file_display($current);
	}
}

/**
 * Reason image uploads.
 */
class ReasonImageUploadType extends image_uploadType
{
	var $type = "ReasonImageUpload";
	var $type_valid_args = array('authenticator', 'existing_entity',
		'head_items', 'obey_no_resize_flag', 'convert_to_image');
	
	/**
	 * Set this flag to true if this upload type should obey the
	 * "do_not_resize" POST flag.
	 * @var boolean
	 */
	var $obey_no_resize_flag = false;
	
	/**
	 * Set this flag to false if you want to prevent conversion of files (like .pdf and .tiff) to .png
	 */
	var $convert_to_image = true;
	
	/** @access private */
	var $upload_sid = null;
	/** @access private */
	var $head_items;
	/** @access private */
	var $existing_entity;

	function additional_init_actions($args=array())
	{
		$auth = @$args['authenticator'];
		$this->upload_sid = _get_disco_async_upload_session($auth);
		
		$constraints = array(
			'mime_type' => $this->acceptable_types,
			'max_file_size' => $this->max_file_size
		);
		if ($this->resize_image) {
			$constraints['max_dimensions'] = array($this->max_width,
				$this->max_height);
		}
		
		if ($this->convert_to_image) {
			$constraints['convert_to_image'] = true;
		}
		
		reason_add_async_upload_constraints($this->upload_sid, $this->name,
			$constraints);
			
		if (isset($args["head_items"]))
			$this->get_head_items($args["head_items"]);
		
		_reason_upload_handle_entity($this, "image",
		    "reason_get_image_path", "reason_get_image_url");
		
		return parent::additional_init_actions($args);
	}
	
	function register_fields()
	{
		return array_merge(_get_disco_async_upload_internal_field_names(),
			parent::register_fields());
	}
	
	function _get_uploaded_file()
	{
		$vars = $this->get_request();
		return _reason_get_disco_uploaded_file($this->name, $this->upload_sid,
			isset($vars['do_not_resize']) && $this->obey_no_resize_flag);
	}
	
	function _upload_success($path, $url)
	{
		if (!empty($this->file) && !empty($this->file["original_path"])) {
			$orig_path = $this->file["original_path"];
			if ($orig_path != $this->file["path"]) {
				$this->original_path = $orig_path;
			}
		}
		return parent::_upload_success($path, $url);
	}
	
	function _needs_resizing($image_path)
	{
		// XXX: super hacky; potential for abuse
		$vars = $this->get_request();
		return (isset($vars['do_not_resize']))
			? false
			: parent::_needs_resizing($image_path);
	}
	
	function get_head_items(&$head)
	{
		_populate_flash_upload_head_items($head);
	}
	
	function _get_hidden_display($current)
	{
		return _ensure_async_upload_head_items_shown().
			_get_disco_async_upload_hidden_fields($this->upload_sid).
			parent::_get_hidden_display($current);
	}
	
	function _get_current_file_display($current)
	{
		if (!$current) {
			// force an (empty, hidden) current image display to be shown
			$current = new stdClass;
			$current->path = false;
		}
		return parent::_get_current_file_display($current);
	}
}

/** @access private */
function _populate_flash_upload_head_items(&$head)
{
	if ($GLOBALS['_disco_upload_head_items_shown'])
		return;
	
	$flash_uri = REASON_FLASH_UPLOAD_URI.'swfupload.swf';
    $scripts = array(
		'swfupload.js',
		'upload_support.js',
		'jquery.swfupload.js',
		'jquery.uploadbutton.js',
		'jquery.uploadqueue.js',
		'rich_upload.js?swf='.urlencode($flash_uri)
	);
	
	$head->add_javascript(JQUERY_URL);
	$head->add_stylesheet(REASON_FLASH_UPLOAD_URI.'rich_upload.css');
	foreach ($scripts as $script) {
		$head->add_javascript(REASON_FLASH_UPLOAD_URI.$script);
	}
	
	$GLOBALS['_disco_upload_head_items_shown'] = true;
}

/** @access private */
function _reason_upload_handle_entity(&$element, $expected_type,
	$filesystem_translator, $web_translator)
{
	if (!$element->existing_entity)
		return;
	
	if (is_numeric($element->existing_entity)) {
		$id = (int) $element->existing_entity;
		$entity = new entity($id);
	} else if (is_object($element->existing_entity)) {
		$entity = $element->existing_entity;
	} else {
		trigger_error("a $expected_type entity object or ID must be passed ".
			"as the existing entity to a Reason upload type; got ".
			var_export($element->existing_entity, true)." instead", WARNING);
		return;
	}
	
	if (!reason_is_entity($entity, $expected_type)) {
		trigger_error("an invalid existing entity was passed to a Reason ".
			"upload type", WARNING);
		return;
	}
	
	$element->existing_file = call_user_func($filesystem_translator, $entity);
	$element->existing_file_web = call_user_func($web_translator, $entity);
}

/** @access private */
function _get_disco_async_upload_session($authenticator)
{
	$sid = null;
	if (!empty($_REQUEST['_reason_upload_transfer_session'])) {
		$sid = $_REQUEST['_reason_upload_transfer_session'];
	} else if (!empty($_REQUEST['transfer_session'])) {
		$sid = $_REQUEST['transfer_session'];
	}
	
	if ($sid && reason_async_upload_session_exists($sid))
		return $sid;
	
	// We need to generate a new upload session if: none has yet been
	// created (obviously), or if the upload session was already sent in
	// a plasmature element's get_display() code (because if that's true and
	// we're in a plasmature init() method again, then this must be a different
	// Disco form than the one that created the existing upload session).
	$need_new_session = (!$GLOBALS['_disco_upload_session'] ||
		$GLOBALS['_disco_upload_session_sent']);
	
	if ($need_new_session) {
		$sid = reason_create_async_upload_session($authenticator);
		$GLOBALS['_disco_upload_session'] = $sid;
		$GLOBALS['_disco_upload_session_sent'] = false;
	} else {
		$sid = $GLOBALS['_disco_upload_session'];
	}
	
	return $sid;
}

/** @access private */
function _get_disco_async_upload_internal_field_names()
{
	static $fields = array('user_session', 'transfer_session', 'receiver',
		'remover', 'user_id');
	$decorated_fields = array();
	foreach ($fields as $name)
		$decorated_fields[] = "_reason_upload_$name";
	return $decorated_fields;
}

/** @access private */
function _get_disco_async_upload_hidden_fields($upload_sid)
{
	if ($GLOBALS['_disco_upload_session_sent'])
		return '';
		
	$session =& get_reason_session();
	
	$user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
		
	// IMPORTANT NOTE: Keep this list of fields in sync with the list in
	// _get_disco_async_upload_internal_field_names() above.
	$fields = array(
		'user_session' => $session->get_id(),
		'transfer_session' => $upload_sid,
		'receiver' => reason_get_async_upload_script_uri('receive'),
		'remover' => reason_get_async_upload_script_uri('destroy'),
		'user_id' => turn_into_int($user_id)
	);
	
	$html = array();
	foreach ($fields as $name => $value) {
		$html[] = '<input type="hidden" name="_reason_upload_'.$name.'" '.
			'value="'.$value.'" />';
	}
	
	$GLOBALS['_disco_upload_session_sent'] = true;
	return implode('', $html);
}

/** @access private */
function _ensure_async_upload_head_items_shown()
{
	if ($GLOBALS['_disco_upload_head_items_shown'])
		return;
	
	$head_items = new HeadItems();
	_populate_flash_upload_head_items($head_items);
	return $head_items->get_head_item_markup();
}

/** @access private */
function _reason_get_disco_uploaded_file($name, $async_sid,
	$want_original=false)
{
	$upload = reason_get_uploaded_file($name, $async_sid);
	if (!$upload || !$upload->get_filename())
		return null;
	
	$path = null;
	if ($want_original)
		$path = $upload->get_original_path();
	if (!$path)
		$path = $upload->get_temporary_path();
	
	return array(
		"name" => $upload->get_filename(),
		"path" => $path,
		"tmp_name" => $path, // former name
		"original_path" => $upload->get_original_path(),
		"modified_path" => $upload->get_temporary_path(),
		"size" => filesize($path),
		"type" => $upload->get_mime_type("application/octet-stream")
	);
}
