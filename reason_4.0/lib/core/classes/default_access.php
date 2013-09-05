<?php
/**
 * A class for managing default access groups for various types and sites
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
 
 /**
  * A class for managing default access for sites
  * 
  * This class allows Reason to build and understand collections of modules.
  * 
  * This class is intended to be used as a singleton; call reason_get_module_sets() to get the
  * single module_sets object.
  *
  * Note that this is not implemented at the db interaction level; it is implemented at the content
  * manager and module level. Therefore, there may be cases where support has not yet been implemented.
  *
  * Example usage:
  *
  * (In setup_local.php)
  * $da = reason_get_default_access();
  *
  * $da->add('site_unique_name','type_unique_name','allowable_relationship_name','group_unique_name');
  * $da->add('site_unique_name_2','type_unique_name_2','allowable_relationship_name_2','group_unique_name_2');
  *
  * (Elsewhere, as in a content manager)
  * if($this->is_new_entity() && ($group_id = $da->get($site_id, $type_id, $alrel_id)))
  * {
  * 	$this->set_value('access_group',$group_id);
  * }
  *
  */
class reason_default_access
{
	/**
	 * Internal storage
	 */
	protected $_data = array();
	
	/**
	 * Constructor
	 *
	 * Should only be called once, by the factory function reason_get_default_access().
	 *
	 * @access public
	 */
	function __construct($setup)
	{
		if($setup != 'This setup string should only be in the factory function')
		{
			trigger_error('default_access is a singleton class; please use the reason_get_default_access() function to get a reference');
		}
		
	}
	/**
	 * Set a group as the default group for a given site-type combination
	 *
	 * @access public
	 * @param mixed $site A site id, entity, or unique name
	 * @param mixed $type A type id, entity, or unique name
	 * @param mixed $alrel A an allowable relationship id or name
	 * @param mixed $group A group id, entity, or unique name
	 * @return boolean success
	 */
	function set($site, $type, $alrel, $group)
	{
		if(empty($site))
		{
			trigger_error('Unable to set default access control (no site provided)');
			return false;
		}
		elseif(!($site_id = $this->resolve_to_id($site)))
		{
			trigger_error('Unable to set default access control (site not a valid value)');
			return false;
		}
		if(empty($type))
		{
			trigger_error('Unable to set default access control (no type provided)');
			return false;
		}
		elseif(!($type_id = $this->resolve_to_id($type)))
		{
			trigger_error('Unable to set default access control (type not a valid value)');
			return false;
		}
		if(empty($alrel))
		{
			trigger_error('Unable to set default access control (no allowable relationship provided)');
			return false;
		}
		elseif(!($alrel_id = $this->resolve_alrel_to_id($alrel)))
		{
			trigger_error('Unable to set default access control (allowable relationship not a valid value)');
			return false;
		}
		if(empty($group))
		{
			trigger_error('Unable to set default access control (no group provided)');
			return false;
		}
		elseif(!($group_id = $this->resolve_to_id($group)))
		{
			trigger_error('Unable to set default access control (group not a valid value)');
			return false;
		}
		$this->_data[$site_id][$type_id][$alrel_id] = $group_id;
		return true;
	}
	/**
	 * Get the default group id for a given site and type
	 *
	 * @access public
	 * @param mixed $site entity, id, or unique name
	 * @param mixed $type entity, id, or unique name
	 * @return integer id
	 */
	function get($site, $type, $alrel)
	{
		if(empty($site))
		{
			trigger_error('Unable to get default access control (no site provided)');
			return false;
		}
		elseif(!($site_id = $this->resolve_to_id($site)))
		{
			trigger_error('Unable to get default access control (site not a valid value)');
			return false;
		}
		if(empty($type))
		{
			trigger_error('Unable to get default access control (no type provided)');
			return false;
		}
		elseif(!($type_id = $this->resolve_to_id($type)))
		{
			trigger_error('Unable to get default access control (type not a valid value)');
			return false;
		}
		if(empty($alrel))
		{
			trigger_error('Unable to get default access control (no allowable relationship provided)');
			return false;
		}
		elseif(!($alrel_id = $this->resolve_alrel_to_id($alrel)))
		{
			trigger_error('Unable to get default access control (allowable relationship not a valid value)');
			return false;
		}
		if(isset($this->_data[$site_id][$type_id][$alrel_id]))
		{
			return $this->_data[$site_id][$type_id][$alrel_id];
		}
		else
		{
			return null;
		}
	}
	
	protected function resolve_to_id($item)
	{
		if(is_object($item))
			return $item->id();
		if(is_numeric($item))
			return $item;
		if($id = id_of($item, true, false))
			return $id;
		return false;
	}
	protected function resolve_alrel_to_id($item)
	{
		if(is_numeric($item))
			return $item;
		if($id = relationship_id_of($item))
			return $id;
		return false;
	}
}
/**
 * Get the singleton reason default access object
 *
 * @return object
 */
function reason_get_default_access()
{
	static $da;
	if(empty($da))
	{
		$da = new reason_default_access('This setup string should only be in the factory function');
		if(reason_file_exists('config/default_access/setup.php'))
			reason_include_once('config/default_access/setup.php');
		if(reason_file_exists('config/default_access/setup_local.php'))
			reason_include_once('config/default_access/setup_local.php');
	}
	return $da;
}

?>