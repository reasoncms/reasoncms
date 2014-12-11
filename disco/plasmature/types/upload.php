<?php

/**
 * File upload type library.
 *
 * @package disco
 * @subpackage plasmature
 * @author Eric Naeseth <enaeseth+disco@gmail.com>
 */

require_once CARL_UTIL_INC.'basic/image_funcs.php';
require_once CARL_UTIL_INC.'basic/mime_types.php';
require_once CARL_UTIL_INC.'basic/filesystem.php';
require_once CARL_UTIL_INC.'basic/cleanup_funcs.php';
require_once CARL_UTIL_INC.'basic/misc.php';
require_once CARL_UTIL_INC.'cache/object_cache.php';

/**
 * Generic file upload type.
 */
class uploadType extends defaultType
{
	/**
	 * The type name of this type.
	 * @var string
	*/
	var $type = "upload";
	
	/**
	 * An internal representation of the upload state.
	 * @access protected
	 * @var string
	 * @see $state
	 */
	var $_state = "ready";
	
	/**
	 * The state of the upload.
	 * Possible values: "ready", "received", "pending", "existing"
	 * @var string
	 * @see $_state
	 */
	var $state = "ready";
	
	/**
	 * The path to an existing file occupying this field.
	 * If this is populated, {@link $existing_file_web} should be, too.
	 * @var string
	 */
	var $existing_file;
	
	/**
	 * The URL of the existing file occupying this field.
	 * If this is populated, {@link $existing_file} should be, too.
	 * @var string
	 */
	var $existing_file_web;
	
	/**
	 * A value to use as the displayed name of the existing file.
	 * If empty, the {@link $existing_file real filename} will be used.
	 * @var string
	 */
	var $file_display_name;
	
	/**
	 * An array of acceptable MIME type patterns.
	 * If empty, all file types will be accepted.
	 * Examples of patterns: "application/pdf", "image/*".
	 * @var array
	 */
	var $acceptable_types = array();

	/**
	 * An array of acceptable extensions.
	 *
	 * If empty, all extensions will be accepted.
	 *
	 * Use lowercase version of extensions -- extension-checking will be case-insensitive.
	 * Examples of extensions: "pdf", "jpg".
	 *
	 * @var array
	 */
	var $acceptable_extensions = array();
	
	/**
	 * Whether or not users may upload a new file when editing the entity.
	 * @var boolean
	 */
	var $allow_upload_on_edit;
	
	/**
	 * The maximum-allowed upload size, in bytes.
	 * @var int
	 */
	var $max_file_size = 20971520; // 20 MB

	/**
	 * should the element show type/extension/size restrictions? defaults to false for pre-existing forms. forms
	 * created using thor/formbuilder override this to true by default (see reason_package/thor/thor.php)
	 * @var boolean
	 */
	var $show_restriction_explanation = false;
	
	/** @access private */
	var $type_valid_args = array(
		'existing_file',
		'existing_file_web',
		'original_path',
		'file_display_name',
		'acceptable_types',
		'acceptable_extensions',
		'allow_upload_on_edit',
		'max_file_size',
		'show_restriction_explanation'
	);
	
	/**
	 * Information on the uploaded file.
	 * Compatible with an entry in the PHP $_FILES array, but with some extra
	 * elements possibly added.
	 * @var array
	 */
	var $file;
	
	/**
	 * The temporary URL of the uploaded file.
	 * @var string
	 */
	var $tmp_web_path;
	
	/**
	 * The temporary full filesystem path of the uploaded file.
	 * 
	 * Code using this element in a form is responsible for moving the uploaded
	 * file from this location to somewhere permanent.
	 * 
	 * @var string
	 */
	var $tmp_full_path;
	
	/**
	 * The temporary full filesystem path of an original version of the file.
	 * 
	 * If the uploaded file is modified somehow in the process (e.g. rescaled
	 * to some maximum size), a copy of the original file may be preserved. The
	 * base upload type performs no modifications; if you are using a subclass,
	 * check its documentation to see what modifications it can perform and if
	 * there is any way to get it to preserve the original file.
	 * 
	 * If no modifications have been made to the uploaded file, or no original
	 * copy was preserved, the value of <code>original_path</code> will be
	 * <code>NULL</code>.
	 * 
	 * Code using this element in a form is responsible for moving the uploaded
	 * file from this location to somewhere permanent.
	 *
	 * @var string
	 */
	var $original_path;
	
	function additional_init_actions($args=array())
	{
		if (!empty($this->existing_file)) {
			$this->_state = $this->state = 'existing';
			$this->value = $this->existing_file;
		}
	}
	
	/**
	 * Gets information about the uploaded file (if there was one).
	 * @access protected
	 * @return array a description of the uploaded file, or NULL if no file
	 *         was uploaded
	 */
	function _get_uploaded_file()
	{
		if (empty($_FILES[$this->name]))
			return null;
		
		$file = $_FILES[$this->name];
		if (empty($file['name']))
			return null;
		
		return array(
			'name' => basename($file['name']),
			'path' => $file['tmp_name'],
			'tmp_name' => $file['tmp_name'], // former name of this field
			'original_path' => null,
			'size' => (int) $file['size'],
			'type' => get_mime_type($file["tmp_name"],
				"application/octet-stream", $file["name"])
		);
	}
	
	/**
	 * Gets a code for the upload error.
	 * @access protected
	 * @return mixed the error code, or NULL if there was no error
	 */
	function _get_upload_error()
	{
		if (empty($_FILES[$this->name]))
			return null;
		
		$file = $_FILES[$this->name];
		if ($file['error'] === 0 && !is_uploaded_file($file['tmp_name'])) {
			// Possible file upload attack; return the literal 0.
			return 0;
		}
		
		return (!empty($file['error'])) ? $file['error'] : null;
	}
	
	/**
	 * Gets a user-friendly string describing the upload error.
	 *
	 * If there was an error, this method will always return some message;
	 * a generic one will be used if no specific message is relevant.
	 *
	 * @access protected
	 * @return string a description of the error, or NULL if there was no error
	 */
	function _get_upload_error_message()
	{
		static $messages = null;
		
		if ($messages === null) {
			$symbolic_messages = array(
				'UPLOAD_ERR_INI_SIZE' => 
					"The file you are trying to upload is too large.",
				'UPLOAD_ERR_FORM_SIZE' =>
					"The file you are trying to upload is too large.",
				'UPLOAD_ERR_PARTIAL' =>
					"Only part of the file was uploaded successfully.",
				'UPLOAD_ERR_NO_FILE' =>
					"No file was received.",
				'UPLOAD_ERR_NO_TMP_DIR' =>
					"Your file was received, but it could not be saved.",
				'UPLOAD_ERR_CANT_WRITE' =>
					"Your file was received, but it could not be saved.",
				'UPLOAD_ERR_EXTENSION' =>
					"A server component blocked your file upload."
			);
		
			$messages = array();
			foreach ($symbolic_messages as $constant => $message) {
				if (defined($constant))
					$messages[constant($constant)] = $message;
			}
		}
		
		$error = $this->_get_upload_error();
		if ($error === null) // why, there's no error at all!
			return null;
		
		return (!empty($messages[$error]))
			? $messages[$error]
			: "There was a problem with your upload.";
	}
	
	/**
	 * Examines the state of the upload and generates relevant PHP warnings.
	 *
	 * If there is anything wrong with the upload that should be communicated
	 * to the site admins, this function should raise that information as PHP
	 * error messages.
	 *
	 * @access protected
	 * @return void
	 */
	function _generate_warnings()
	{
		static $admin_warnings = null;
		
		if ($admin_warnings === null) {
			$symbolic_warnings = array(
				"UPLOAD_ERR_NO_TMP_DIR" =>
					"PHP has no temporary directory to save the upload to.",
				"UPLOAD_ERR_CANT_WRITE" =>
					"PHP could not write the uploaded file to disk.",
				"UPLOAD_ERR_EXTENSION" =>
					"The file upload was stopped by a PHP extension."
			);
		
			$admin_warnings = array();
			foreach ($symbolic_warnings as $constant => $message) {
				if (defined($constant))
					$admin_warnings[constant($constant)] = $message;
			}
		}
		
		$error = $this->_get_upload_error();
		if (isset($admin_warnings[$error]))
			trigger_warning($admin_warnings[$error], 2);
	}
	/**
	 * Overrides defaultType's implementation of get
	 * 
	 * Returns an array (as opposed to stdobject) because Disco requires string/array values
	 * @return array storing path, name, size, and uri of image
	 */
	function get()
	{
		$current_file_info = $this->_get_current_file_info();
		if(is_object($current_file_info))
			return get_object_vars($current_file_info);
		else
			return null;
	}
	
	/**
	 * Overrides defaultType's implementation of set
	 * 
	 * This plasmature type is a little screwy, in that get() returns an array, even
	 * though the internal value of the element is a string (the full path of the
	 * file). That makes it difficult to reassign the value you previous got from the element
	 * (as when using the multistep form controller). This method tries to address that
	 * by allowing you to pass the array you got from get() and put the element back into
	 * the state it was in during the get().
	 *
	 * @param array or string
	 */
	function set($value)
	{
		// Implement the default behavior, in case anything is relying on that
		$this->value = $value;
		
		// Provide extra handling for passed arrays
		if (is_array($value))
		{
			$this->file = $value;
			if (isset($value['name'])) $this->file_display_name = $value['name'];
			if (isset($value['path'])) $this->existing_file = $value['path'];
			if (isset($value['uri'])) $this->existing_file_web = $value['uri'];
		}
	}
	
	/**
	 * Populates the {@link $value} field of the upload type.
	 * @return void
	 */
	function grab()
	{
		$this->file = $this->_get_uploaded_file();
		$this->_generate_warnings();
		$vars = $this->get_request();
		
		if ($this->file && !empty($this->file["name"])) {
			$this->value = $this->_grab_value_from_upload();
		} else if ($id = @$vars[$this->_get_upload_id_field()]) {
			$this->value = $this->_grab_value_from_limbo($id);
		} else if (!empty($this->existing_file)) {
			$this->value = $this->_grab_value_from_existing_file();
		}
		$this->state = $this->_state;
	}
	
	/** @access private */
	function _grab_value_from_upload()
	{
		$error_code = $this->_get_upload_error();
		if ($error_code !== null) {
			// There was a problem uploading the file.
			$this->set_error($this->_get_upload_error_message());
		}
		
		if (!$this->has_error) {
			// No errors so far; check the size of the uploaded file.
			
			$max_size = $this->max_file_size;
			
			if ($this->file["size"] <= 0) {
				$this->set_error("It doesn't look like that file has ".
					"anything in it.");
			} else if ($this->file["size"] > $max_size) {
				$readable_size = format_bytes_as_human_readable($max_size);
				$filename = strip_tags(htmlspecialchars($this->file["name"]));
				
				$this->set_error("The file you want to upload ".
					"($filename) exceeds the maximum upload size of ".
					"$readable_size.");
			}
		}
		
		if (!$this->has_error && !empty($this->acceptable_types)) {
			$mime_type = get_mime_type($this->file["path"],
				'application/octet-stream');
			if (!mime_type_matches($this->acceptable_types, $mime_type)) {
				$this->set_error("The file you want to upload is not ".
					"in an acceptable format.");
			}
		}
		
		// check acceptable extensions if provided.
		if (!$this->has_error && !empty($this->acceptable_extensions)) {
			$filename_parts = explode('.', $this->file["name"]);
			$extension = strtolower(end($filename_parts));
			
			if (!in_array($extension, $this->acceptable_extensions)) {
				$this->set_error("The file you want to upload is not ".
					"in an acceptable format.");
			}
		}
		
		$value = null;
		if (!$this->has_error) {
			$this->_state = "received";
			$filename = $this->file["name"];
			$this->tmp_web_path = $this->_generate_temp_name($filename);
			$value = $this->tmp_full_path =
				$_SERVER['DOCUMENT_ROOT'].$this->tmp_web_path;
			
			if (!rename($this->file["path"], $value)) {
				$this->set_error("Your file was received, but could not ".
					"be saved on the server.");
			} else {
				if (!empty($this->file["modified_path"])) {
					if ($this->file["path"] != $this->file["modified_path"]) {
						// We used the original file, so we remove the
						// modified (e.g., rescaled) file.
						// TAKE NOTE: This removal is not optional!
						if (@unlink($this->file["modified_path"])) {
							unset($this->file["modified_path"]);
						}
					}
				} else if (!empty($this->file["original_path"])) {
					if ($this->file["path"] != $this->file["original_path"]) {
						// Bring the original file along for the ride as well.
						$new_orig = $this->_generate_temp_name($filename,
							"-original");
						if (rename($this->file["original_path"], $new_orig)) {
							$this->file["original_path"] = $new_orig;
						}
					}
				}
			    $this->file["path"] = $this->file["tmp_name"] =
			        $value;
				$this->_upload_success($value, $this->tmp_web_path);
			}
		}
		
		return $value;
	}
	
	/** @access private */
	function _generate_temp_name($filename, $suffix='')
	{
		$id = sha1(uniqid(mt_rand(), true));
		list(, $extension) = get_filename_parts($filename);
		if (!empty($extension))
			$extension = strtolower(".$extension");
		return WEB_TEMP."{$id}{$suffix}{$extension}";
	}
	
	/**
	 * Called when a successfully-uploaded file is first received.
	 * The default implementation of this callback does nothing.
	 * @param string $path the filesystem path to the uploaded file
	 * @param string $url the Web path to the uploaded file
	 * @return void
	 * @access protected
	 */
	function _upload_success($path, $url)
	{
		
	}
	
	/** @access private */
	function _persist_filename($value, $original=null, $display_name=null)
	{
		$cache_id = uniqid('upload_'.mt_rand().'_', true);
		$cache = new ObjectCache($cache_id, '360');
		$store = new stdClass;
		$store->value = $value;
		$store->path_to_original = $original;
		$store->display_name = $display_name;
		$cache->set($store);
		return $cache_id;
	}
	
	/** @access private */
	function _restore_filename($cache_id)
	{
		$cache = new ObjectCache($cache_id, '360');
		$store =& $cache->fetch();
		if (!$store)
			return array(null, null, null);
		return array($store->value, $store->path_to_original,
			$store->display_name);
	}
	
	/** @access private */
	function _get_upload_id_field()
	{
		return "{$this->name}_pending_id";
	}
	
	/** @access private */
	function _grab_value_from_limbo($upload_id)
	{
		list($value, $original, $display_name) =
			$this->_restore_filename($upload_id);
		if ($value) {
			$this->tmp_web_path = $value;
			if ($display_name)
				$this->file_display_name = $display_name;
			$this->_state = "pending";
			$this->tmp_full_path = $_SERVER['DOCUMENT_ROOT'].$value;
			$this->original_path = $original;
			
			$filename = ($display_name)
			    ? $display_name
			    : basename($this->tmp_full_path);
			$this->file = array(
				"name" => $filename,
				"path" => $this->tmp_full_path,
				"tmp_name" => $this->tmp_full_path, // old name for this field
				"original_path" => $original,
				"size" => filesize($this->tmp_full_path),
    			"type" => get_mime_type($this->tmp_full_path,
    				"application/octet-stream", $filename)
			);
			return $value;
		}
		
		return null;
	}
	
	/** @access private */
	function _grab_value_from_existing_file()
	{
		$this->_state = "existing";
		return $this->existing_file;
	}
	
	/** @access private */
	function _can_add_file($current)
	{
		return (!$this->existing_file || $this->allow_upload_on_edit);
	}
	
	/**
	 * Gets information on the current file.
	 * The "current file" can be an existing file or the file uploaded in the
	 * current form session.
	 * @access protected
	 * @return object path, name, and size of the current file
	 */
	function _get_current_file_info()
	{
		$info = new stdClass;
		
		if (!empty($this->tmp_full_path)) {
			$info->path = $this->tmp_full_path;
			$info->uri = $this->tmp_web_path;
		} else if (!empty($this->existing_file)) {
			$info->path = $this->existing_file;
			$info->uri = $this->existing_file_web;
		} else {
			return null;
		}
		
		if (!empty($this->file))
			$info->name = $this->file["name"];
		else
			$info->name = basename($info->path);
		$info->name = $this->_clean_filename($info->name);
		$info->size = @filesize($info->path);
		return $info;
	}
	
	/**
	 * Performs any necessary cleanup on a filename to make it safe to use.
	 * @access protected
	 * @param string $filename the name of an uploaded file
	 * @return string the cleaned-up filename
	 */
	function _clean_filename($filename)
	{
		return sanitize_filename_for_web_hosting($filename);
	}
	
	/**
	 * Gets the MIME type of the uploaded file.
	 * @param string $default what to return if the MIME type can not be
	 *		  determined for any reason
	 * @return string
	 */
	function get_mime_type($default=null)
	{
		$current = $this->_get_current_file_info();
		if (!$current || !is_readable($current->path))
			return $default;
		
		return get_mime_type($current->path, $default);
	}
	
	/**
	 * Gets the overall display of the upload field.
	 * This function should not normally be overridden; see
	 * {@link _get_hidden_display}, {@link _get_current_file_display}, and
	 * {@link _get_upload_display} instead.
	 * @return string
	 */
	function get_display()
	{
		$current = $this->_get_current_file_info();
		
		return $this->_get_hidden_display($current).
			$this->_get_restriction_display($current).
			$this->_get_current_file_display($current).
			$this->_get_upload_display($current);
	}
	
	/**
	 * Gets any hidden input fields to accompany the upload field.
	 * Subclasses that override this method should call the parent
	 * implementation and concatenate the two strings.
	 * @access protected
	 * @param object $current information on the current file
	 * @return string
	 */
	function _get_hidden_display($current)
	{
		$disp = '';
		$name = $this->name;
		$vars = $this->get_request();
		
		if ($this->_can_add_file($current)) {
			$disp .= '<input type="hidden" name="'.$name.'[MAX_FILE_SIZE]" '.
				'value="'.$this->max_file_size.'" />';
		}
		
		if (in_array($this->_state, array('received', 'pending'))) {
			$id_field = $this->_get_upload_id_field();
			// Reuse the ID we received only if the state is "pending";
			// otherwise we have received a new file and need to save its name
			// instead (under a new ID).
			$disp_name = (!empty($this->file_display_name))
				? $this->file_display_name
				: @$this->file["name"];
			$id = ($this->_state == "pending" && !empty($vars[$id_field]))
				? $vars[$id_field]
				: $this->_persist_filename($this->tmp_web_path,
					$this->original_path, $disp_name);
			$disp .= '<input type="hidden" name="'.$id_field.'" '.
				'value="'.$id.'" />';
		}
		
		return $disp;
	}

	function _get_restriction_display($current)
	{
		if (!$this->show_restriction_explanation) { return ""; }

		$rv = "";

		if (count($this->acceptable_types) > 0) {
			$rv .= ($rv == "" ? "" : "; ") . "type" . (count($this->acceptable_types) == 1 ? "" : "s") . " <i>" . implode(", ", $this->acceptable_types) . "</i>";
		}

		if (count($this->acceptable_extensions) > 0) {
			$rv .= ($rv == "" ? "" : "; ") . "extension" . (count($this->acceptable_extensions) == 1 ? "" : "s") . " <i>" . implode(", ", $this->acceptable_extensions) . "</i>";
		}

		if (in_array("max_file_size", $this->get_set_args())) {
			$rv .= ($rv == "" ? "" : "; ") . "maximum size <i>" . convertNumberOfBytesToFormattedSize($this->max_file_size) . "</i>";
		}

		if ($rv != "") {
			$rv = "<div class=\"file_upload_restriction_explanation\">(restricted to " . $rv . ")</div>";
		}

		return $rv;
	}
	
	/** @access private */
	function _get_display_filename($current=null)
	{
		if (!$current)
			$current = $this->_get_current_file_info();
		if (!$current)
			return null;
			
		
		if ($this->_state != "existing" && $this->_state != "pending")
			return $current->name;
		return (!empty($this->file_display_name))
			? $this->file_display_name
			: $current->name;
	}
	
	/**
	 * Gets the display for the current file.
	 * This method is always called; if there is no current file, $current
	 * will be NULL, and the method may return an empty string.
	 * @access protected
	 * @param object $current information on the current file
	 * @return string
	 */
	function _get_current_file_display($current)
	{
		if (!$current)
			return '';
		
		if ($current->path) {
			$filename = $this->_get_display_filename($current);
			$size = format_bytes_as_human_readable($current->size);
			$style = '';
		} else {
			$filename = $size = '';
			$style = ' style="display: none;"';
		}
		
		return '<div class="uploaded_file"'.$style.'>'.
			'<span class="filename">'.htmlspecialchars($filename).'</span> '.
			'<span class="size"><span class="filesize">'.$size.
			'</span></span></div>';
	}
	
	/**
	 * Gets the actual file upload element.
	 *
	 * This method will never directly be called with either the $add_text or 
	 * $replace_texts arguments populated; subclasses can override this method
	 * and call the parent with these arguments set to change the label text
	 * that accompanies the upload element.
	 * 
	 * @access protected
	 * @param object $current information on the current file
	 * @param string $add_text the text to be shown labeling the upload
	 *		  field when there is no current file
	 * @param string $replace_text the text to be shown labeling the upload
	 *		  field when there is a current file
	 * @return string
	 */
	function _get_upload_display($current, $add_text=null, $replace_text=null)
	{
		if (!$this->_can_add_file($current))
			return '';
		
		$label = null;
		if (!$current && $add_text) {
			$label = $add_text;
		} else if ($current) {
			$label = ($replace_text)
				? $replace_text
				: "Upload a different file:";
		}
		
		$upload = '<div class="file_upload">';
		if ($label) {
			$upload .= '<span class="smallText">'.$label."</span><br />";
		}
		$upload .= '<input type="file" name="'.$this->name.'" /></div>';
		return $upload;
	}
}

/**
 * An upload type specifically for images.
 * @package disco
 * @subpackage plasmature
 */
class image_uploadType extends uploadType
{
	var $type = 'image_upload';
	
	/**
	 * Default to only accepting certain image types.
	 * @var array
	 */
	var $acceptable_types = array(
		'image/jpeg',
		'image/pjpeg', // (Progressive JPEG)
		'image/gif',
		'image/png'
	);
	
	/**
	 * If true, the given file is too large for gd to process without running out of
	 * memory. An error will be set on this plasmature and the form will not be able
	 * to be fully submitted until a different image is uploaded.
	 * @var boolean
	 */
	var $too_big = false;
	
	/**
	 * If true, uploaded images will be resized to fit within constraints.
	 * @var boolean
	 * @see $max_width
	 * @see $max_height
	 */
	var $resize_image = true;
	
	/**
	 * The maximum allowed width of the image in pixels.
	 * If {@link $resize_image} is true, uploaded images that are wider than
	 * the value of this variable will be rescaled to be within this width.
	 * @var int
	 */
	var $max_width = 500;
	
	/**
	 * The maximum allowed height of the image in pixels.
	 * If {@link $resize_image} is true, uploaded images that are taller than
	 * the value of this variable will be rescaled to be within this height.
	 * @var int
	 */
	var $max_height = 500;
	
	/**
	 * Whether or not to preserve the original (unscaled) image.
	 * If true and {@link $resize_image} is also true, the original image will
	 * be copied to a new location before it is rescaled. The path to this
	 * original will be stored in the {@link $original_path} instance variable.
	 */
	var $preserve_original = true;
	
	/** @access private */
	var $type_valid_args = array(
		'resize_image',
		'max_width',
		'max_height'
	);
	
	
	function _upload_success($image_path, $image_url)
	{
		if ($res = image_is_too_big($image_path))
		{
			$this->too_big = true;
			$this->set_error('The chosen image is too large for the server to process. The uncompressed image is '. format_bytes_as_human_readable($res['image_size']).' bytes. Only '. format_bytes_as_human_readable($res['size_limit']).' bytes of memory may be used for processing images of this type.');	
		}
		else
		{
			if ($this->_needs_resizing($image_path)) {
				$this->_resize_image($image_path);
			}
		}
		parent::_upload_success($image_path, $image_url);
	}
	
	/**
	 * Checks to see if the image at the given path should be resized.
	 * @access protected
	 * @param string $image_path
	 * @return boolean
	 */
	function _needs_resizing($image_path)
	{
		if (!$this->resize_image)
			return false;
		
		$info = getimagesize($image_path);
		if (!$info)
			return false;
		list($width, $height) = $info;
		return ($width > $this->max_width || $height > $this->max_height);
	}
	
	/**
	 * Scales the image at the given path in place to fit size constraints.
	 * 
	 * If the {@link $preserve_original} instance variable is set to true, a
	 * copy of the image will be made before scaling, and the path to the copy
	 * will be saved in {@link $original_path}.
	 * 
	 * @access protected
	 * @param string $image_path
	 * @return boolean true if the resize was successful; false if otherwise
	 */
	function _resize_image($image_path)
	{
		if ($this->preserve_original) {
			// Preserve the unscaled image.
			
			$path_parts = pathinfo($image_path);
			if (isset($path_parts['extension']))
			{
				$ext = ".{$path_parts['extension']}";
				$ext_pattern = "/".preg_quote($ext, '/')."$/";
				$orig_path = preg_replace($ext_pattern, "-unscaled{$ext}",
					$image_path);
				if ($orig_path == $image_path) // in case the replace doesn't work
					$orig_path .= '.unscaled';
			} else {
				$orig_path = $image_path . '.unscaled';
			}
				
			if (copy($image_path, $orig_path)) {
				$this->original_path = $orig_path;
			}
		}
		
		$res = resize_image($image_path, $this->max_width, $this->max_height);
		if ($res && $this->file) {
			// file size will have (hopefully) changed after the resize
			$this->file["size"] = filesize($image_path);
		}
		return $res;
	}
	
	function _get_current_file_display($current)
	{
		if (!$current || $this->too_big)
			return '';
		
		if ($current->path && $current->uri) {
			list($width, $height) = getimagesize($current->path);
			$disk_size = format_bytes_as_human_readable($current->size);
			$dimensions = "$width&times;$height";
			$uri = htmlspecialchars($current->uri).'?_nocache='.time();
			$img_style = ' style="width: '.$width.'px; '.
				'height: '.$height.'px;"';
			$div_style = '';
		} else {
			$uri = $img_style = $dimensions = $disk_size = '';
			$div_style = ' style="display: none;"';
		}
		$image_size = '<span class="dimensions">'.$dimensions.'</span> '.
			'(<span class="filesize">'.$disk_size.'</span>)';
		
		return '<div class="uploaded_file uploaded_image"'.$div_style.'>'.
			'<span class="smallText">Uploaded image:</span><br />'.
			'<img src="'.$uri.'"'.$img_style.' class="representation" />'.
			'<br /><span class="size">'.$image_size.'</span></div>';
	}
	
	function _get_upload_display($current, $add_text=null, $replace_text=null)
	{
		if (!$replace_text)
			$replace_text = "Upload a different image:";
		return parent::_get_upload_display($current, $add_text, $replace_text);
	}
	
	/**
	 * Gets information on the current file.
	 * The "current file" can be an existing file or the file uploaded in the
	 * current form session.
	 * @access protected
	 * @return object path, name, size of the current file, and the path of the original file if the file is downsized
	 */
	function _get_current_file_info()
	{
		$info = parent::_get_current_file_info();
		
		if(isset($this->original_path))
			$info->original_path = $this->original_path;
		
		return $info;
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class image_upload_no_labelType extends image_uploadType
{
        var $_labeled = false;
}

/**
 * The old name for a generic file upload.
 * 
 * New applications should use the {@link uploadType new generic upload type}
 * instead.
 * 
 * This class will automatically translate the state member variable to
 * "uploaded" when it is set to "received" for backwards-compatibility.
 * @deprecated
 */
class AssetUploadType extends uploadType
{
	var $type = "AssetUpload";
	
	function additional_init_actions($args=array())
	{
		$error = "The 'AssetUpload' plasmature type is deprecated. Use the ".
		    "new base 'upload' type ";
		if (defined("REASON_HTTP_BASE_PATH")) {
			$error .= "or the 'ReasonUpload' type ";
		}
		$error .= "instead.";
		
		trigger_deprecation($error, 3);
		return parent::additional_init_actions($args);
	}
	
	function grab()
	{
		$result = parent::grab();
		if ($this->state == "received")
			$this->state = "uploaded"; // old, confusing name
		return $result;
	}
}
