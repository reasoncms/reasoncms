<?php
/**
 * Reason API Class
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'api/api.php');

/**
 * Reason API sets a few defaults on CarlUtilAPI - specifically:
 *
 * - Add support for dynamically setting the content_type_request_key if "format" is in the request.
 * - Set up json as the only supported content type.
 *
 * NOTE: This API framework is very new to Reason, considered beta, and subject to change.
 *
 * This class should not be extended - it is designed for a a Reason Module or other use case
 * where application logic outside the class looks at the user request, sets up the appropriate
 * status code and content.
 *
 * A more full featured API should extend the CarlUtilAPI base class.
 *
 * @version .1 
 * @author Nathan White
 */
final class ReasonAPI extends CarlUtilAPI
{
	protected $content_type_request_key = 'format';
	protected $supported_content_types = array('json');
}
?>