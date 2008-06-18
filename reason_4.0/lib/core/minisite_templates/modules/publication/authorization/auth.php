<?php
/**
 * A base class for publication authorization logic
 *
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * In publication authorization classes, make sure to include a line like this to indicate aht class name should be used
  */
$GLOBALS[ '_reason_publication_auth_classes' ][ 'minisite_templates/modules/publication/authorization/auth.php' ] = 'reason_publication_authorization';

/**
 * A base class for publication authorization logic
 *
 * Defines an API for authorization of viewing posts and lists on publications, and implements some basic setters for info used by the class
 *
 * @author Matt Ryan
 *
 * Example usage:
 *
 * $auth = new $GLOBALS[ '_reason_publication_auth_classes' ][$path]]();
 * $auth->set_username($netid);
 * $auth->set_item_id($item_id);
 * $auth->set_issue_id($issue_id);
 * $auth->set_most_recent_issue_id($recent_issue_id);
 * if(!$auth->authorized_to_view())
 * 	...
 */
class reason_publication_authorization
{
	/**
	 * Username of logged-in user, if current user is logged in
	 *
	 * Otherwise NULL
	 *
	 * @var mixed
	 */
	var $_username;
	
	/**
	 * Reason id of current post
	 *
	 * NULL if not viewing a given post
	 *
	 * @var mixed
	 */
	var $_item_id;
	
	/**
	 * Reason id of current issue
	 *
	 * NULL if not viewing a given issue (or in a non-issued publication)
	 *
	 * @var mixed
	 */
	var $_issue_id;
	
	/**
	 * Reason id of the most recently published issue
	 *
	 * NULL if in a non-issued publication or there are no published issues
	 *
	 * @var mixed
	 */
	var $_most_recent_issue_id;
	
	/**
	 * Set the username of the currently logged-in user
	 *
	 * Pass NULL if not viewing a given issue (or in a non-issued publication) (or don't call at all)
	 *
	 * @param mixed $username The username or NULL/false/empty string if no user currently logged in
	 * @return void
	 */
	function set_username($username)
	{
		if(!empty($username))
			$this->_username = $username;
		else
			$this->_username = NULL;
	}
	
	/**
	 * Set the id of the post currently being requested
	 *
	 * Pass NULL if not viewing a given post (e.g. when viewing a list)
	 *
	 * @param mixed $item_id The id or NULL/false/empty string if no post requested
	 * @return void
	 */
	function set_item_id($item_id)
	{
		if(!empty($item_id))
			$this->_item_id = $item_id;
		else
			$this->_item_id = NULL;
	}
	
	/**
	 * Set the id of the issue currently being requested
	 *
	 * Pass NULL if not viewing a given issue (e.g. if publication not issue-based, or viewing search results across multiple issues)
	 *
	 * @param mixed $issue_id The id or NULL/false/empty string if no issue requested
	 * @return void
	 */
	function set_issue_id($issue_id)
	{
		$this->_issue_id = $issue_id;
	}
	
	/**
	 * Set the id of the most recently-published issue
	 *
	 * Pass NULL if publication is not issue-based, or no issues have been published
	 *
	 * @param mixed $issue_id The id or NULL/false/empty string
	 * @return void
	 */
	function set_most_recent_issue_id($issue_id)
	{
		$this->_most_recent_issue_id = $issue_id;
	}
	
	/**
	 * Is the current user/post/issue OK to view?
	 *
	 * Note: this method is designed to be overloaded by more concrete classes.
	 *
	 * It returns true by default.
	 *
	 * @return boolean
	 */
	function authorized_to_view()
	{
		return true;
	}
	
	/**
	 * Of a user needs to be prompted to log in, what message should they see?
	 *
	 * Returns NULL if no specific message, or a string matching a text blurb unique name if a specific message should be used.
	 *
	 * @return mixed
	 */
	function get_login_message_unique_name()
	{
		return NULL;
	}
	
	/**
	 * Of a user is logged in an is still unauthorized, what message should the publication module present them with?
	 *
	 * @return string
	 */
	function get_unauthorized_message()
	{
		return 'Sorry. You are not authorized to view this content.';
	}
}

?>
