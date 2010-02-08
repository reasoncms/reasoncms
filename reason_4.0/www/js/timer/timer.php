<?php
/**
 * Reason session login timer v2.0 - 
 *
 * Helper for timer.js.
 *
 * @author Nathan White
 *
 * @package reason
 * @subpackage js
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'basic/json.php');

/**
 * Right now all I do is return a bit of JSON that contains these settings:
 *
 * - timeout => REASON_SESSION_TIMEOUT
 * - warning => REASON_SESSION_TIMEOUT_WARNING
 * - logout_page => URL of the logout page, derived from REASON_LOGIN_PATH
 * - session_is_active => whether or not we have an active session
 *
 * @author Nathan White
 */
class ReasonTimer
{
	function get_settings_json()
	{
		$ret['timeout'] = REASON_SESSION_TIMEOUT;
		$ret['warning'] = REASON_SESSION_TIMEOUT_WARNING;
		$ret['logout_page'] = "/" . REASON_LOGIN_PATH . '?msg_uname=expired_login&popup=true';
		$ret['popup_alert'] = $this->get_popup_alert_pref();
		$ret['session_is_active'] = $this->is_session_active();
		$json = json_encode($ret);
		return $json;
	}
	
	function is_session_active()
	{
		$sess =& get_reason_session();
		return ($sess->exists() && !$sess->has_expired()) ? 'true' : 'false';
	}
	
	function get_popup_alert_pref()
	{
		$sess =& get_reason_session();
		$popup_alert = 'false';
		if (DEFAULT_TO_POPUP_ALERT) $popup_alert = 'true';
		if($sess->exists())
		{
			if( !$sess->has_started() )
			{
				$sess->start();
			}
			if ($sess->get( '_user_popup_alert_pref' ) == 'yes') $popup_alert = 'true';
			elseif ($sess->get( '_user_popup_alert_pref' ) == 'no') $popup_alert = 'false';
		}
		return $popup_alert;
	}
}
$rt = new ReasonTimer();
echo $rt->get_settings_json();
?>