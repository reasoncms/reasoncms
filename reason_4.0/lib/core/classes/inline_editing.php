<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/user_functions.php' );

/**
 * Allows the getting and setting of information related to inline editing.
 *
 * - disable / enable inline editing
 * - get information on whether or not inline editing is enabled
 * - do any modules allow inline editing?
 * - is inline editing available now for a module?
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
	 * Key on the query string used to identify the module that is active for inline editing
	 *
	 * @access private
	 * @var string
	 */
	var $_module_identifier_key = 'inline_edit';
	
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
	 * @param boolean available
	 * @return int key
	 */
	function register_module(&$module, $available)
	{
		$item['module'] =& $module;
		$item['available'] = ($this->reason_allows_inline_editing()) ? $available : false;
		$this->modules[$module->identifier] = $item;
		$keys = array_keys($this->modules);
		return array_pop($keys);
	}
		
	/**
	 * Returns true if a registered module allows inline editing (regardless of whether inline editing is enabled in the session or not).
	 *
	 * @return boolean
	 */
	function is_available()
	{
		$modules =& $this->get_registered_modules();
		if (!empty($modules))
		{
			foreach ($modules as $v)
			{
				if ($this->available_for_module($v['module'], false)) return true;
			}
		}
		return false;
	}

	/**
	 * Return true if a registered module is available for inline editing. By default, we additionally check if inline editing is enabled
	 * in the session, but this check can be turned off in order to check if a module "would" allow inline editing if enabled.
	 *
	 * @param object module
	 * @param boolean check_is_enabled default true
	 * @return boolean
	 */
	function available_for_module($module, $check_is_enabled = true)
	{
		if ($this->reason_allows_inline_editing())
		{
			if ( !$check_is_enabled || ($this->is_enabled()))
			{
				$modules =& $this->get_registered_modules();
				$identifier = $module->identifier;
				if ( !empty($identifier) && isset($modules[$identifier]['available'])) return $modules[$identifier]['available'];
			}
		}
		return false;
	}
	
	function active_for_module($module)
	{
		$identifier = $module->identifier;
		if (isset($_REQUEST[$this->_module_identifier_key]) && ($_REQUEST[$this->_module_identifier_key] == $identifier)) return true;
		return false;
	}	
	
	function get_activation_params($module)
	{
		$identifier = $module->identifier;
		return array($this->_module_identifier_key => $identifier);
	}
	
	function get_deactivation_params($module)
	{
		$identifier = $module->identifier;
		return array($this->_module_identifier_key => '');
	}
	
	/**
	 * Checks to see if reason allows inline editing and if inline editing is enabled for the reason session.
	 *
	 * @return boolean or NULL if session is not started
	 */
	function is_enabled()
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
	 * Enable inline editing - sets session value inline_editing to enabled.
	 *
	 * @return boolean success or failure
	 */	
	function enable()
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
	function disable()
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

	/**
	 * @access private
	 * @return array
	 */	
	function &get_registered_modules()
	{
		return $this->modules;
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
			if (reason_maintenance_mode() && !reason_check_privs('db_maintenance'))
			{
				$this->_reason_allows_inline_editing = false;
			}
			elseif (defined('REASON_ALLOWS_INLINE_EDITING'))
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
