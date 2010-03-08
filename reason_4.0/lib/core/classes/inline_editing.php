<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );

/**
 * Allows the getting and setting of information related to inline editing.
 *
 * - disable / enable inline editing
 * - get information on whether or not inline editing is enabled
 * - do any modules allow inline editing?
 * - does the user have permission for inline editing?
 *
 * If the constant REASON_ALLOWS_INLINE_EDITING is set to false, modules can still register but the following methods will always return false
 *
 * - is_inline_editing_enabled
 * - has_inline_edit_privs
 * - enable_inline_editing
 * - disable_inline_editing
 *
 * @author Nathan White
 */
class ReasonInlineEditing
{
	var $modules;
	
	/**
	 * Constructor - should never be used directly - user get_reason_inline_editing() to get an instance of the class for a reason page_id
	 *
	 * @see get_reason_inline_editing($page_id)
	 */
	function ReasonInlineEditing($str = NULL)
	{
		if ($str != 'i_am_the_secret_code')
		{
			trigger_error('ReasonInlineEditing is a singleton class that should only be grabbed with get_reason_inline_editing()');
		}
	}
	
	/**
	 * During the init phase, modules should register themselves with the class if they support inline editing
	 *
	 * @param obj module
	 * @param boolean user_can_inline_edit
	 * @return int key
	 */
	function register_module(&$module, $user_can_inline_edit)
	{
		$item['module'] =& $module;
		$item['user_can_inline_edit'] = ($this->reason_allows_inline_editing()) ? $user_can_inline_edit : false;
		$this->modules[] = $item;
		$keys = array_keys($this->modules);
		return array_pop($keys);
	}
	
	/**
	 * Return true if user can inline edit the module identified by key. If a key is not provided, return true if any module allows inline editing.
	 *
	 * @param int key - a key referencing a module in $this->modules - the key was probably by the return value of register_module.
	 * @return boolean whether or not user can inline edit
	 */
	function has_inline_edit_privs( $key = NULL )
	{
		if (!$this->reason_allows_inline_editing()) return false;
		$modules =& $this->get_registered_modules();
		if ($key != NULL) return (isset($modules[$key]['user_can_inline_edit'])) ? $modules[$key]['user_can_inline_edit'] : false;
		elseif ($modules) foreach ($modules as $k => $v)
		{
			if ($v['user_can_inline_edit']) return true;
		}
		return false;
	}

	/**
	 * @access private
	 * @return array
	 */	
	function &get_registered_modules()
	{
		return $this->modules;
	}
	
	/**
	 * Checks to see if reason allows inline editing and if inline editing is enabled for the reason session.
	 *
	 * @return boolean or NULL if session is not started
	 */
	function is_inline_editing_enabled()
	{
		if (!$this->reason_allows_inline_editing()) return false;
		$session =& get_reason_session();
		if ($session->exists() && $session->has_started())
		{
			return ($session->get('inline_editing') == 'enabled');
		}
		return NULL;
	}

	/**
	 * Returns the value of the constant REASON_ALLOWS_INLINE_EDITING or true if the constant is undefined.
	 *
	 * @return boolean
	 */
	function reason_allows_inline_editing()
	{
		if (!isset($this->_reason_allows_inline_editing))
		{
			if (defined('REASON_ALLOWS_INLINE_EDITING'))
			{
				$this->_reason_allows_inline_editing = REASON_ALLOWS_INLINE_EDITING;	
			}
			else
			{
				$path_to_script = REASON_HTTP_BASE_PATH . '/scripts/upgrade/4.0b7_to_4.0b8/index.php';
				trigger_error('REASON_ALLOWS_INLINE_EDITING not defined in reason_settings.php - reason_allows_inline_editing will return true (the default value) but please add the constant to remove this warning. For more information, see ' . $path_to_script.'.');
				$this->_reason_allows_inline_editing = true;
			}
		}
		return $this->_reason_allows_inline_editing;
	}
	
	/**
	 * Enable inline editing - sets session value inline_editing to enabled.
	 *
	 * @return boolean success or failure
	 */	
	function enable_inline_editing()
	{
		if (!$this->reason_allows_inline_editing()) return false;
		$session =& get_reason_session();
		if ($session->exists() && $session->has_started())
		{
			$session->set('inline_editing', "enabled");
			return true;
		}
		return false;
	}

	/**
	 * Disable inline editing - sets session value inline_editing to disabled.
	 *
	 * @return boolean success or failure
	 */	
	function disable_inline_editing()
	{
		if (!$this->reason_allows_inline_editing()) return false;
		$session =& get_reason_session();
		if ($session->exists() && $session->has_started())
		{
			$session->set('inline_editing', "disabled");
			return true;
		}
		return false;
	}
}

/**
 * Returns a singleton instance of the ReasonInlineEditing class for any reason page_id
 *
 * @param int page_id - reason page id
 * @return object
 */
function &get_reason_inline_editing($page_id)
{
	static $reason_inline_editing;
	if (!empty($page_id) && !isset($reason_inline_editing[$page_id])) // we don't have one
	{
		$reason_inline_editing[$page_id] = new ReasonInlineEditing('i_am_the_secret_code');
	}
	elseif (empty($page_id))
	{
		trigger_error('get_reason_inline_editing must be given a reason page id as a parameter');
		$ret = false;
		return $ret;
	}
	return $reason_inline_editing[$page_id]; 
}