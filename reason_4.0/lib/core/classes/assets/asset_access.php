<?php
/**
 * Asset Access Viewer
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include the Reason libraries & other dependencies
 */
include_once( 'reason_header.php' );
require_once CARL_UTIL_INC.'basic/mime_types.php';
reason_include_once( 'classes/group_helper.php');
reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'function_libraries/user_functions.php');
reason_include_once( 'function_libraries/asset_functions.php');
reason_include_once( 'function_libraries/util.php');

/**
 * Asset Access Viewer
 *
 * This class is the gatekeeper for assets that are associated with a viewing group
 *
 * Given a valid asset_id the asset will be delivered to the user provided they have permissions,
 * or the user will be redirected to the 403 forbidden page.
 *
 * If a valid asset_id of type 'asset' has not been set run() will return false.
 *
 * Sample usage:
 *
 * <code>
 * $asset = new ReasonAssetAccess( $asset_id );
 * if (!$asset->run()) echo '<h2>There was an error</h2>';
 * </code>
 *
 * @author Matt Ryan and Nathan White
 */
class ReasonAssetAccess
{
	var $site;
	var $asset;
	var $_username;
	
	function ReasonAssetAccess($asset_id = '')
	{
		if (!empty($asset_id)) $this->set_asset_id((int) $asset_id);
	}
	
	/**
	 * sets $site entity given a site_id
	 */ 
	function set_site_id($site_id)
	{
		$this->site = new entity($site_id);
	}
	
	/**
	 * sets $asset entity given an asset_id
	 */
	function set_asset_id($asset_id)
	{
		$e = new entity($asset_id);
		if (reason_is_entity($e, 'asset')) $this->asset = $e;
	}
	
	/**
	 * sets $site entity to the owner of $asset
	 */
	function set_site_id_from_asset_id()
	{
		$owner_asset = $this->asset->get_owner();
		$this->set_site_id($owner_asset->id());
	}
	
	function set_username($username)
	{
		$this->_username = $username;
	}
	
	function get_username()
	{
		if($this->_username === NULL)
		{
			$this->_username = reason_check_authentication();
		}
		return $this->_username;
	}
	
	/**
	 * does everything - ensures page is secure, checks for access, delivers file or headers user to forbidden page
	 */ 
	function run()
	{
		if (empty($this->asset)) return false;
		if (empty($this->site)) $this->set_site_id_from_asset_id();
		if( ($this->asset->get_value('state') != 'Deleted') && $this->access_allowed())
		{
			$this->_send_file();
		}
		elseif ($this->asset->get_value('state') == 'Deleted')
		{
			http_response_code(404);
			if(defined('ERROR_404_PATH') && file_exists(WEB_PATH.ERROR_404_PATH) && is_readable(WEB_PATH.ERROR_404_PATH))
			{
				include(WEB_PATH.ERROR_404_PATH);
			}
			else
			{
				echo '<!DOCTYPE html>'."\n";
				echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
				echo '<head><title>File Not Found (HTTP 404)</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>'."\n";
				echo '<body>'."\n";
				echo '<h2>File Not Found (HTTP 404)</h2>'."\n";;
				echo '<p>The file you are trying to access is not available.</p>'."\n";
				echo '</body>'."\n";
				echo '</html>'."\n";
			}
			exit();
		}
		else
		{
			http_response_code(403);
			if(defined('ERROR_403_PATH') && file_exists(WEB_PATH.ERROR_403_PATH) && is_readable(WEB_PATH.ERROR_403_PATH))
			{
				include(WEB_PATH.ERROR_403_PATH);
			}
			else
			{
				echo '<!DOCTYPE html>'."\n";
				echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
				echo '<head><title>Access Forbidden (HTTP 403)</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>'."\n";
				echo '<body>'."\n";
				echo '<h2>Access Forbidden (HTTP 403)</h2>'."\n";;
				echo '<p>The file you are trying to access is access-controlled and you do not have proper privileges.</p>'."\n";
				echo '</body>'."\n";
				echo '</html>'."\n";
			}
			exit();
		}
	}
	
	/**
	 * determines whether or not authentication is necessary for a particular asset
	 * and whether the current user is a member of the group that has access
	 * @return boolean true if the user has access
	 */
	function access_allowed()
	{
		$es = new entity_selector();
		$es->add_right_relationship($this->asset->id(), relationship_id_of('asset_access_permissions_to_group'));
		$es->add_type(id_of('group_type'));
		$es->set_num(1);
		$groups = $es->run_one();
		
		if(empty($groups))
		{
			return true;
		}
		
		
		$group = current($groups);
		$gh = new group_helper();
		$gh->set_group_by_entity($group);
		
		$access = ($gh->is_username_member_of_group("")) // test if anonymous access is allowed
		          ? true // if so return true
		          : $gh->is_username_member_of_group( $this->get_username()); // else discover and check username
		
		
		
		if($access === NULL) // unknown due to non-logged-in-user
		{
			reason_require_authentication('login_to_access_file');
			die();
		}
		return $access; // true or false
	}
	
	/**
	 * _send_file delivers the asset to the client - if should be invoked using the run function of the class and not called directly
	 *
	 * @author nwhite
	 * @access private
	 */
	function _send_file()
	{
		//$extensions_to_display_inline = array('jpg', 'gif', 'htm', 'html', 'swf', 'txt');
		$file_name = $this->asset->get_value( 'file_name' );
		$file_ext = $this->asset->get_value( 'file_type' );
		$file_path = reason_get_asset_filesystem_location($this->asset);

		if (file_exists($file_path))
		{
			$file_size = filesize($file_path);
		
			// disposition needs some extensive testing - should it be attachment or inline?
			// $file_disposition = ($file_ext == 'pdf') ? 'attachment' : 'inline'; // download by default

			if ($file_ext == 'pdf') {
				if (REASON_PDF_DOWNLOAD_DISPOSITION_DEFAULT == 'inline' ||
					REASON_PDF_DOWNLOAD_DISPOSITION_DEFAULT == 'attachment') {
					$file_disposition = REASON_PDF_DOWNLOAD_DISPOSITION_DEFAULT;
				} else {
					$file_disposition = 'attachment';
				}
			} else {
				$file_disposition = 'inline';
			}
		
			$mime_type = $this->get_mime_type();
			if(!empty($mime_type) && strstr($mime_type, "text/")) {
				 $file_handle = fopen($file_path, "r"); }
			else $file_handle = fopen($file_path, "rb");
			//if (in_array($file_ext, $extensions_to_display_inline)) $file_disposition = 'inline';
			if (empty($mime_type)) $mime_type = 'application/octet-stream';
			ob_end_clean();
			header('Pragma: public');
			header('Cache-Control: max-age=0'); //added to squash weird IE 6 bug where pdfs say that the file is not found
			header('Content-Type: ' . $mime_type);
			header('Content-Disposition: ' . $file_disposition . '; filename="'.$file_name.'"');
			header('Content-Length: '.$file_size);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			fpassthru($file_handle);
			exit();
		}
		else
		{
			trigger_error ('The asset at ' . $file_path . ' could not be sent to the user because it does not exist');
		}
	}
	
	/**
	 * Determines the MIME type of the asset based on its file extension.
	 * Requires the {@link APACHE_MIME_TYPES} constant to be properly defined.
	 * @param string $fileext
	 * @return string the asset's MIME type, or an empty string if it could
	 *         not be determined
	 * @uses mime_type_from_extension
	 */
	function get_mime_type($fileext = '')
	{
		if ($this->asset && is_object($this->asset) && $this->asset->has_value('mime_type'))
		{
		 	return $this->asset->get_value('mime_type');
		}
	    
		$fileext = (empty($fileext))
			? $this->asset->get_value('file_type')
			: $fileext;
		
		return mime_type_from_extension($fileext, '');
	}
}
