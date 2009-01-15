<?php

/**
 * Asset Access Viewer
 *
 * @package reason
 * @author Matt Ryan and Nathan White
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
 */

include_once( 'reason_header.php' );
reason_include_once( 'classes/group_helper.php');
reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'function_libraries/user_functions.php');

class ReasonAssetAccess
{
	var $site;
	var $asset;
	var $username;
	
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
	    if ($e) $type = $e->get_value( 'type' );
	    if ((id_of('asset')) == $type) $this->asset = $e;
	}
	
	/**
	 * sets $site entity to the owner of $asset
	 */
	function set_site_id_from_asset_id()
	{
		$owner_asset = $this->asset->get_owner();
		$this->set_site_id($owner_asset->id());
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
			header( 'Location: '.ERROR_404_PAGE );
			exit();
		}
		else
		{
			if (!defined('ERROR_403_PAGE'))
			{
				header('HTTP/1.0 403 Forbidden');
				{
					echo '<h2>Access Forbidden</h2>';
					echo '<p>The resource you are trying to access is access-controlled and you do not have proper privileges.</p>';
					exit();
				}
			}
			else
			{
				header( 'Location: '.ERROR_403_PAGE );
				exit();
			}
		}
	}
	
	/**
	 * determines whether or not authentication is necessary for a particular asset
	 * and whether the current user is a member of the group that has access
	 * @return boolean true if the user has access
	 */
	function access_allowed()
	{
		$es = new entity_selector($this->site->id());
		$es->add_right_relationship($this->asset->id(), relationship_id_of('asset_access_permissions_to_group'));
		$es->add_type(id_of('group_type'));
		$es->set_num(1);
		$groups = $es->run_one();
		if(empty($groups))
		{
			return true;
		}
		else
		{
			$group = current($groups);
			$gh = new group_helper();
			$gh->set_group_by_entity($group);
			if(!$gh->requires_login())
			{
				return true;
			}
			else
			{
				force_secure_if_available();
				$this->username = reason_require_authentication('login_to_access_file');
				if(!empty($this->username))
				{
					return $gh->is_username_member_of_group($this->username);
				}
			}
		}
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
		$file_path = ASSET_PATH.$this->asset->id().'.'.$this->asset->get_value('file_type');

		if (file_exists($file_path))
		{
			$file_size = filesize($file_path);
		
			// disposition needs some extensive testing - should it be attachment or inline?
			$file_disposition = ($file_ext == 'pdf') ? 'attachment' : 'inline'; // download by default
		
			$mime_type = $this->get_mime_type();
			if(!empty($mime_type) && strstr($mime_type, "text/")) {
				 $file_handle = fopen($file_path, "r"); }
			else $file_handle = fopen($file_path, "rb");
			//if (in_array($file_ext, $extensions_to_display_inline)) $file_disposition = 'inline';
			if (empty($mime_type)) $mime_type = 'application/octet-stream';
			ob_end_clean();
			header( 'Pragma: public');
			header( 'Cache-Control: max-age=0' ); //added to squash weird IE 6 bug where pdfs say that the file is not found
			header( 'Content-Type: ' . $mime_type );
			header( 'Content-Disposition: ' . $file_disposition . '; filename="'.$file_name.'"' );
			header( 'Content-Length: '.$file_size );
			header( 'Content-Transfer-Encoding: binary');
			header( 'Expires: 0');
			fpassthru($file_handle);
			exit();
		}
		else
		{
			trigger_error ('The asset at ' . $file_path . ' could not be sent to the user because it does not exist');
		}
	}

	/**
	 * get_mime_type determines the mime type based upon the extension by parsing the apache mime.types file
	 * it requires APACHE_MIME_TYPES to be a defined constant that contains the pathname to the apache mime.types file
	 * @author Nathan White
	 * @param string $fileext
	 * @return string the mime type or an empty string if not found
	 */
	 function get_mime_type($fileext = '')
	 {
	 	$fileext = (empty($fileext)) ? $this->asset->get_value( 'file_type' ) : $fileext;

	 	if (empty($fileext)) return '';
	 	$mime_path = APACHE_MIME_TYPES;
		if (empty($mime_path))
		{
			trigger_error ('APACHE_MIME_TYPES constant must be defined in the reason settings.php file for
					asset access to function.');
			
		}
		elseif (!file_exists($mime_path))
		{
			trigger_error ('APACHE_MIME_TYPES file ' . APACHE_MIME_TYPES . ' does not exist');
		}
	 	else
		{
			$myhandle = fopen(APACHE_MIME_TYPES, 'r');
	 		while (!feof($myhandle))
	 		{
	 			$matches = fscanf($myhandle, "%s\t%[^\n]");	
	 			if (($matches[0][0] != '#') && (!empty($matches[1])))
	 			{
	 				foreach (explode(' ', $matches[1]) as $item)
	 				{
	 					if (strcasecmp($item, $fileext) == 0) 
	 					{
	  						fclose($myhandle);
	 						return $matches[0];
	 					}
	 				}
	 			}
	 		}
	 		fclose($myhandle);
	 	}
		return '';
	 }
}
?>
