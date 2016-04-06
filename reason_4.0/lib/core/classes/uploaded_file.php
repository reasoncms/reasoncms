<?php

/**
 * Reason uploaded file management.
 *
 * @package reason
 * @subpackage classes
 * @since Reason 4.0 beta 8
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

require_once CARL_UTIL_INC.'basic/filesystem.php';
require_once CARL_UTIL_INC.'basic/mime_types.php';

/**
 * A file that was uploaded by the client and stored somewhere temporarily,
 * either by PHP's uploaded file handling or by the background-upload
 * processing script.
 */
class UploadedFile
{
	/**#@+
	 * @access private
	 */
	var $_filename;
	var $_temporary_path;
	var $_size;
	var $_original_path;
	var $_file_mode;
	/**#@-**/
	
	/**
	 * Creates a new uploaded file object.
	 * @param string $filename the original basename of the uploaded file
	 * @param string $temp_path the full filesystem path where the file can be
	 *        temporarily found
	 * @param int $known_size the size of the uploaded file, in bytes, if it
	 *        is known by the code calling the constructor
	 * @param string $orig_path if the file was modified by the upload handler,
	 *        provide the path to any preserved unmodified copy of the file
	 *        here
	 * @param octal $file_mode four digit octal value to pass to chmod
	 */
	function UploadedFile($filename, $temp_path, $known_size=null,
		$orig_path=null, $file_mode = 0644)
	{
		$this->_filename = $filename;
		$this->_temporary_path = $temp_path;
		$this->_size = $known_size;
		$this->_original_path = $orig_path;
		$this->_file_mode = $file_mode;
	}
	
	/**
	 * Gets the original basename of the uploaded file on the client's system.
	 * @return string
	 */
	function get_filename()
	{
		return $this->_filename;
	}
	
	/**
	 * Gets the temporary path at which the uploaded file was stored.
	 * @return string
	 */
	function get_temporary_path()
	{
		return $this->_temporary_path;
	}
	
	/**
	 * Gets the path to an unmodified version of the file (if any).
	 * @return string
	 */
	function get_original_path()
	{
		return $this->_original_path;
	}

	/**
	 * Gets the default file mode
	 * @return octal
	 */
	function get_file_mode()
	{
		return $this->_file_mode;
	}

	/**
	 * Gets the size of the uploaded file. This function will only work if
	 * the uploaded file is still in its temporary location.
	 * @return int the size of the file in bytes, or <code>false</code> if
	 *         the size could not be determined for any reason
	 * @see filesize()
	 */
	function get_size()
	{
		if ($this->_size === null || $this->_size === false)
			$this->_size = @filesize($this->get_temporary_path());
		return $this->_size;
	}
	
	/**
	 * Gets the MIME type of the uploaded file.
	 * @param string $default what to return if the type cannot be determined
	 * @return string the determined MIME type, or <code>$default</code> if the
	 *         type could not be determined
	 * @uses ::get_mime_type() the underlying implementation
	 */
	function get_mime_type($default=null)
	{
		return get_mime_type($this->get_temporary_path(), $default,
			$this->get_filename());
	}
	
	/**
	 * Checks to see if the MIME type of the uploaded file is acceptable.
	 * @param string|array a MIME type pattern, or an array of such patterns
	 * @return boolean true if the MIME type matched any of the patterns;
	 *         false if otherwise
	 * @uses ::mime_type_matches() the underlying implementation
	 */
	function mime_type_matches($pattern)
	{
		return mime_type_matches($pattern,
			$this->get_mime_type('application/octet-stream'));
	}
	
	/**
	 * Moves the uploaded file from its temporary path to the specified
	 * destination.
	 * 
	 * If the given destination is an existing directory, the file will be
	 * moved into this directory under {@link get_filename its original name}.
	 *
	 * @param string $destination destination filename or containing directory
	 * @return string the absolute path to which the file was moved, or
	 *         <code>null</code> if the move failed
	 * @uses rename()
	 */
	function move($destination)
	{
		if (is_dir($destination)) {
			$destination = dir_join($destination, $this->get_filename());
		}
		
		$source = $this->get_temporary_path();
		$file_was_moved = move_uploaded_file($source, $destination);
		if ($file_was_moved) {
			$mode = $this->get_file_mode();
			chmod($destination, $mode);
		} else {
			return null;
		}
		return $destination;
	}
}
