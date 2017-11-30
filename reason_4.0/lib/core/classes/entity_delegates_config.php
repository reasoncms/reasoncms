<?php
/**
 * A singleton utility class that represents the current configuration for entity delegates
 *
 * NOTE: Actual configuration occurs in the files config/entity_delegates/config.php
 * and onfig/entity_delegates/config_local.php
 *
 * @package reason
 * @author Matt Ryan
 */
 
/**
 * Get the singleton entityDelegatesConfig object
 * @return object entityDelegatesConfig
 */
function get_entity_delegates_config()
{
	static $config;
	if(!isset($config))
		$config = new entityDelegatesConfig();
	return $config;
}

/**
 * A singleton class that represents the current configuration for entity delegates
 *
 * This can be used in core and local configs, or can be added to by any code in Reason
 *
 * Note that once this is modified all entity delegates will be set up based on the
 * modified config. If you only want to give specific entities (not all of a given type)
 * a particular entity delegate, add them directly with entity::add_delegate($path, $delegate) instead.
 * 
 * Basic usage:
 *
 * $config = get_entity_delegates_config();
 * $config->prepend('minisite_page', 'path/to/delegate.php');
 * $config->append('image', 'path/to/delegate.php');
 * $config->remove('minisite_page', 'path/to/delegate.php');
 * $config->replace('image', 'path/to/delegate.php', 'path/to/new/delegate.php');
 * $def = $config->get($type_id); // provides definition for a given type (array of paths to delegates)
 * $delegates = $config->get_delegates($entity); // provides actual delegate objects for a given entity
 *
 * prepend(), append(), and remove() also support passing arrays of paths, i.e.:
 *
 * $config->prepend('minisite_page', array('path/to/delegate1.php','path/to/delegate2.php'));
 * $config->append('image', array('path/to/delegate1.php','path/to/delegate2.php'));
 * $config->remove('minisite_page', array('path/to/delegate1.php','path/to/delegate2.php'));
 * 
 * The process() method supports a declarative style of defining a set of delegate changes:
 *
 * $set = array( 'minisite_page' => array( 'append' => array('path/1.php','path/2.php'), 'prepend' => 'path/3.php'));
 * $config->process($set);
 *
 * Note that this class is generally quiet. If you want error messages thrown, watch for 
 * return values of false and fetch errors as needed, e.g.:
 *
 * if(!$config->remove('minisite_page', 'path/to/delegate.php'))
 * {
 *     foreach($config->get_error_messages() as $msg)
 *         trigger_error($msg);
 * }
 */
class entityDelegatesConfig
{
	/**
	 * The current delegate configuration
	 *
	 * Format: array(type_id => array('path1.php','path2.php'), type_id ...)
	 *
	 * @var array
	 */
	protected $delegates = array();
	
	/**
	 * Error messages thrown during processing
	 *
	 * @var array
	 */
	protected $error_messages = array();
	
	/**
	 * An array containing hashes for each type's current config
	 *
	 * Format: array(type_id => hash, type_id => hash ...)
	 *
	 * @var array
	 */
	protected $hashes = array();
	
	/**
	 * 
	 */
	protected $types_to_classes = array();
	
	protected $append_only = false;
	
	/**
	 * Construct the singleton
	 *
	 * Note that a fatal error will be thrown if more than one object is instantiated.
	 *
	 * Use get_entity_delegates_config() to get the one legal instance of this class.
	 */
	function __construct()
	{
		static $instance_number = 0;
		if($instance_number > 0)
			trigger_error('Use get_entityDelegatesConfig() function to retrieve the entityDelegatesConfig singleton', E_USER_ERROR);
		$instance_number++;
	}
	
	function enter_append_only_mode()
	{
		$this->append_only = true;
	}
	
	/**
	 * Add an entity delegate at the beginning of a type definition
	 *
	 * In the case of function name conflicts, entities earlier in the definition will be used.
	 *
	 * @param mixed $type
	 * @pa
	 */
	function prepend($type, $path_or_paths)
	{
		if($this->append_only)
		{
			$this->error_messages[] = 'Unable to prepend. In append only mode.';
			return false;
		}
		if($type_id = $this->mixed_to_id($type))
		{
			if(!isset($this->delegates[$type_id]))
				$this->delegates[$type_id] = array();
			if(is_array($path_or_paths))
				$this->delegates[$type_id] = array_merge($path_or_paths, $this->delegates[$type_id]);
			else
				array_unshift($this->delegates[$type_id], $path_or_paths);
			$this->type_hash($type_id, true);
			return true;
		}
		return false;
	}
	function append($type, $path_or_paths)
	{
		if($type_id = $this->mixed_to_id($type))
		{
			if(!isset($this->delegates[$type_id]))
				$this->delegates[$type_id] = array();
			if(is_array($path_or_paths))
				$this->delegates[$type_id] = array_merge($this->delegates[$type_id], $path_or_paths);
			else
				$this->delegates[$type_id][] = $path_or_paths;
			$this->type_hash($type_id, true);
			return true;
		}
		return false;
	}
	function remove($type, $path_or_paths)
	{
		if($this->append_only)
		{
			$this->error_messages[] = 'Unable to remove. In append only mode.';
			return false;
		}
		if($type_id = $this->mixed_to_id($type))
		{
			if(!isset($this->delegates[$type_id]))
				return false;
			
			if(is_array($path_or_paths))
			{
				$this->delegates[$type_id] = array_diff($this->delegates[$type_id], $path_or_paths);
			}
			else
			{
				$key = array_search($path_or_paths, $this->delegates[$type_id]);
				if(false !== $key)
					unset($this->delegates[$type_id][$key]);
				else
					return false;
			}
			$this->type_hash($type_id, true);
			return true;
		}
		return false;
	}
	function replace($type, $old_path, $new_path)
	{
		if($this->append_only)
		{
			$this->error_messages[] = 'Unable to replace. In append only mode.';
			return false;
		}
		if($type_id = $this->mixed_to_id($type))
		{
			$key = array_search($old_path, $this->delegates[$type_id]);
			if(false !== $key)
			{
				$this->delegates[$type_id][$key] = $new_path;
				$this->type_hash($type_id, true);
				return true;
			}
		}
		return false;
	}
	function process($setting_set)
	{
		$err = false;
		foreach($setting_set as $type => $actions)
		{
			foreach($actions as $action => $paths)
			{
				if('replace' == $action)
				{
					foreach($paths as $key => $path)
					{
						if(!$this->replace($type, $key, $path))
						{
							$err = true;
							$this->error_messages[] = 'Error replacing '.$key.' with '.$path;
						}
					}
				}
				elseif(in_array($action, array('append','prepend','remove')))
				{
					if(!$this->$action($type, $paths))
					{
						$err = true;
						$this->error_messages[] = 'Error performing entityDelegatesConfig::'.$action.'() using '.$type;
					}
				}
				else
				{
					$err = true;
					$this->error_messages[] = 'Invalid action ("'.$action.'") given to process()';
				}
			}
		}
		return !$err;
	}
	function get($type = NULL)
	{
		if($type)
		{
			if($id = $this->mixed_to_id($type))
				return isset($this->delegates[$id]) ? $this->delegates[$id] : array();
			$this->error_messages[] = 'Unable to identify type from '.$type;
			return false;
		}
		return $this->delegates;
	}
	function type_hash($type, $refresh = false)
	{
		if($id = $this->mixed_to_id($type))
		{
			if($refresh || !isset($this->hashes[$id]))
			{
				$this->hashes[$id] = $this->hash($this->get($id));
			}
			return $this->hashes[$id];
		}
		$this->error_messages[] = 'Bad type provided to hash()';
		return NULL;
	}
	function hash($paths)
	{
		return md5(implode(',',$paths));
	}
	/**
	 * Factory functions for entity delegates
	 * @todo Add ability to dynamically/programmatically add delegates to type at runtime, while still having good performance
	 * @return array of paths to delegate objects
	 */
	function get_delegates($entity, $type = NULL, $refresh = false)
	{
		if($type)
			$type_id = $this->mixed_to_id($type);
		if(!$type || !$type_id)
			$type_id = $entity->type_id();
		
		$delegates = array();
		
		if($refresh  || !isset($this->types_to_classes[$type_id]))
		{
			$this->types_to_classes[$type_id] = array();
			
			$config = $this->get($type_id);
			
			if(!empty($config))
			{
				foreach($config as $path)
				{
					reason_include_once($path);
					if(!empty($GLOBALS['entity_delegates'][$path]))
					{
						$class = $GLOBALS['entity_delegates'][$path];
						if(class_exists($class))
							$this->types_to_classes[$type_id][$path] = $class;
						else
							trigger_error('Unable to use entity delegate at '.$path.'; class '.$class.' does not exist');
					}
					else
					{
						trigger_error('Unable to use entity delegate; not registered in $GLOBALS["entity_delegates"]["'.$path.'"]');
					}
				}
			}
		}
		foreach($this->types_to_classes[$type_id] as $path=>$class)
		{
			$delegates[$path] = new $class($entity);
		}
		return $delegates;
	}
	
	
	function get_error_messages($clear = true)
	{
		$ret = $this->error_messages;
		if($clear)
			$this->error_messages = array();
		return $ret;
	}
	protected function mixed_to_id($mixed)
	{
		if(is_numeric($mixed))
			return (integer) $mixed;
		if(is_object($mixed))
			return $mixed->id();
		if(is_string($mixed) && reason_unique_name_exists($mixed))
			return id_of($mixed);
		trigger_error('Unable to identify item id -- passed "'.$mixed.'"');
		return NULL;
	}
}
