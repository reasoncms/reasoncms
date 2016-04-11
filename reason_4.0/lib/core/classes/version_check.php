<?php
/**
 * A class that encapsulates version checking -- both on the client (installed
 * copy of Reason) side and on the server (i.e. "mothership") side
 * @package reason
 * @subpackage classes
 */

/**
 * Include necessary info
 */
include_once('paths.php');
include_once(CARL_UTIL_INC.'cache/object_cache.php');
include_once(CARL_UTIL_INC.'basic/url_funcs.php');

/**
 * A class that encapsulates version checking -- both on the client (installed
 * copy of Reason) side and on the server (i.e. "mothership") side
 *
 * Client-side usage:
 * <code>
 * $vc = new reasonVersionCheck;
 * $resp = $vc->check();
 * echo '<p>'.htmlspecialchars($resp['message'], ENT_QUOTES);
 * if(!empty($resp['url']))
 * 	echo ' <a href="'.htmlspecialchars($resp['url'], ENT_QUOTES).'">Link</a>';
 * echo ' (Response code: '.$resp['code'].')<p>'."\n";
 * </code>
 *
 * Unless you want to fork Reason, you probably won't need to worry about server-
 * side usage. Just in case, though, here goes:
 * <code>
 * $version = isset($_GET['version']) ? $_GET['version'] : '';
 * $vc = new reasonVersionCheck;
 * $resp = $vc->get_version_response($version);
 * http_response_code($resp['status']);
 * echo $resp['content'];
 * </code>
 *
 * Note for the maintainers of the Reason core:
 *
 * Immediately *before* a release, the following change will need to be made:
 *
 * -- A new bleeding-edge version will need to be added to the output of get_all_versions(),
 * and the current (stable) version will need to be shifted up one version
 *
 * Immediately *after* a release, the following change will need to be made:
 *
 * -- get_current_version_id() will need to be changed to reflect the next bleeding-edge
 * identifier.
 *
 * @author Matt Ryan <mryan@carleton.edu>
 */
class reasonVersionCheck
{
	/**
	 * Simply find out what the identifier string of the current version is
	 * @return string
	 */
	function get_current_version_id()
	{
		return '4.7';
	}

	/**
	 * Get an array of all versions
	 *
	 * Returns an array keyed by version ID and with values of "old", "current" (for the latest stable release), and "bleeding" (for the current bleeding-edge version) to identify what state that version is currently in.
	 * @return array
	 */
	function get_all_versions()
	{
		return array(
			'4.0b1'=>'old',
			'4.0b2'=>'old',
			'4.0b3'=>'old',
			'4.0b4'=>'old',
			'4.0b5'=>'old',
			'4.0b6'=>'old',
			'4.0b7'=>'old',
			'4.0b8'=>'old',
			'4.0b9'=>'old',
			'4.0'=>'old',
			'4.1'=>'old',
			'4.2'=>'old',
			'4.3'=>'old',
			'4.4'=>'old',
			'4.5'=>'old',
			'4.6'=>'old',
			'4.7'=>'current',
			'4.8'=>'bleeding',
		);
	}

	/**
	 * Find out information about whether the current version is up-to-date
	 *
	 * Returns an array in this format:
	 *
	 * array('code'=>'response_code','message'=>'Response message','url'=>'http://linktomoreinformation.com/')
	 *
	 * Possible codes returned:
	 *
	 * no_version_provided
	 *
	 * version_not_recognized
	 *
	 * version_out_of_date
	 *
	 * version_up_to_date
	 *
	 * internal_error
	 *
	 * @return array
	 */
	function check()
	{
		static $version_info = array();

		if(empty($version_info))
		{
			$version_info = $this->_get_version_info();
		}

		return $version_info;
	}

	function _get_version_info()
	{
		$version = $this->get_current_version_id();

		$cache = new ObjectCache();
		$cache->init('ReasonVersionCheckCache', 86400); // cache for 1 day
		$obj = $cache->fetch();
		if(empty($obj) || !$obj->get_data() || $obj->get_version() != $version)
		{
			$obj = new ReasonVersionCheckData;
			$obj->set_data($this->_fetch_response_from_remote_server($version));
			$obj->set_version($version);
			$cache->set($obj);
		}
		return $obj->get_data();
	}

	function _fetch_response_from_remote_server($version)
	{
		$url = 'https://reasoncms.org/reason/version_check.php?version='.urlencode($this->get_current_version_id());
		$response = carl_util_get_url_contents($url,false,'','',5); // 5 seconds max to try
		if (!empty($response))
		{
			list($version_info['code'],$version_info['message'],$version_info['url']) = explode("\n",$response);
			return $version_info;
		}
		return false;
	}

	/**
	 * Determine what data should be used in the response for a given version
	 * @param string $version
	 * @return array Format: array('code'=>'response_code','message'=>'Response message','url'=>'http://linktomoreinformation.com/','status'=>200)
	 */
	function _get_version_response_array($version)
	{
		$versions = $this->get_all_versions();

		if(empty($version))
		{
			return array('code'=>'no_version_provided','message'=>'No version provided','url'=>'','status'=>404);
		}

		if(!isset($versions[$version]))
		{
			return array('code'=>'version_not_recognized','message'=>'The version "'.$version.'" is not recognized','url'=>'','status'=>404);
		}

		$current = array_search('current',$versions);

		if(empty($current))
			trigger_error('No current version found in list of versions. This is a serious error.');

		switch($versions[$version])
		{
			case 'old':
				return array('code'=>'version_out_of_date','message'=>'You are running an out-of-date version of Reason ('.$version.'). Please update it to the latest stable version ('.$current.').','url'=>'http://reasoncms.org/get-started/download/','status'=>200);
				break;
			case 'current':
				return array('code'=>'version_up_to_date','message'=>'Your version of Reason is up to date.','url'=>'','status'=>200);
				break;
			case 'bleeding':
				return array('code'=>'version_up_to_date','message'=>'Your version of Reason is up to date. Note that you are running a bleeding-edge version ('.$version.'), which may not be as stable as the latest release ('.$current.').','url'=>'','status'=>200);
				break;
			default:
				trigger_error('An unexpected version state was encountered: '.$versions[$version]);
				return array('code'=>'internal_error','message'=>'Sorry, we encountered an internal error in the Reason version checker. (Version given: '.$version.')','url'=>'','status'=>500);
		}

	}

	/**
	 * Build the response for a given version
	 * @param string $version
	 * @return array Format: array('content'=>'HTTP content','status'=>200)
	 */
	function get_version_response($version)
	{
		$resp = $this->_get_version_response_array($version);

		return array('content'=>$resp['code']."\n".$resp['message']."\n".$resp['url'], 'status'=>$resp['status']);
	}
}

class ReasonVersionCheckData
{
	var $_data = NULL;
	var $_version = NULL;
	function set_version($version)
	{
		$this->_version = $version;
	}
	function get_version()
	{
		return $this->_version;
	}
	function set_data($data)
	{
		$this->_data = $data;
	}
	function get_data()
	{
		return $this->_data;
	}
}

?>
