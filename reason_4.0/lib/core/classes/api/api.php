<?php
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'api/api.php');

/**
 * Reason API contains application logic specific to providing APIs for Reason.
 *
 * - Sets content type according to $_REQUEST['format'] in the constructor.
 *
 * NOTE: This API framework is very new to Reason, considered beta, and subject to change.
 *
 * @version .1 
 * @author Nathan White
 */
class ReasonAPI extends CarlUtilAPI
{
	/**
	 * We set the content type according to $_REQUEST['format'] provided it contains safe characters.
	 *
	 * @param mixed array or string
	 * @return array
	 */
	function __construct($support_types = NULL)
	{
		static $validated_format_request;
		parent::__construct($support_types);
		if (!isset($validated_format_request))
		{
			$validated_format_request = '';
			if (isset($_REQUEST['format']) && check_against_regexp($_REQUEST['format'], array('safechars')))
			{
				$validated_format_request = $_REQUEST['format'];
			}
		}
		if (!empty($validated_format_request))
		{
			$this->set_content_type($validated_format_request);
		}
	}	
}
?>