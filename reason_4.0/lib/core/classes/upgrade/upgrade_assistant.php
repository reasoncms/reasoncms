<?php
/**
 * @package reason 
 * @subpackage upgrade
 */

include_once('reason_header.php');
reason_include_once('function_libraries/file_finders.php');

class reasonUpgradeAssistant
{
	protected $upgraders;
	protected $upgrade_info;
	
	function get_upgraders($upgrade_string, $name = NULL)
	{
		if (!isset($this->upgraders))
		{
			$this->_build_upgrader_info($upgrade_string, $name);
		}
		return $this->upgraders;
	}
	
	function get_upgrade_info($upgrade_string, $name = NULL)
	{
		if (!isset($this->upgrade_info))
		{
			$this->_build_upgrader_info($upgrade_string, $name);
		}
		return $this->upgrade_info;
	}
	
	function _build_upgrader_info($upgrade_string, $name = NULL)
	{
		$dir_path = 'scripts/upgrade/'.$upgrade_string.'/';
		$files = reason_get_merged_fileset($dir_path);
		$upgraders = array();
		if(!empty($files))
		{
			foreach($files as $file)
			{
				reason_include_once($dir_path.$file);
				$name = basename($file, '.php');
				if(!empty($GLOBALS['_reason_upgraders'][$upgrade_string][$name]))
				{
					$classname = $GLOBALS['_reason_upgraders'][$upgrade_string][$name];
					if(class_exists($classname))
					{
						$obj = new $classname;
						if($obj instanceof reasonUpgraderInterface)
							$upgraders['upgraders'][$name] = new $classname;
						elseif ($obj instanceof reasonUpgraderInfoInterface)
						{
							$upgraders['upgrade_info'][$name] = new $classname;
						}
						else
						{
							trigger_error('Upgraders must implement the reasonUpgraderInterface or the reasonUpgraderInfoInterface; '.$classname.' appears not to.');
						}
					}
					else
						trigger_error('Unable to instantiate upgrader class '.$classname.' -- it does not appear to exist');
				}
				else
				{
					trigger_error('The upgrader file '.$file.' does not appear to have registered itself properly. At the top of the file there should be'.
							' a declaration like this: $GLOBALS[\'_reason_upgraders\'][\''.$upgrade_string.'\'][\''.$name.'\'] = \'NameOfUpgraderClass\'');
				}
			}
		}
		if (!empty($upgraders['upgraders'])) ksort($upgraders['upgraders']);
		if (!empty($upgraders['upgrader_info'])) ksort($upgraders['upgrader_info']);
		$this->upgraders = (!empty($upgraders['upgraders'])) ? $upgraders['upgraders'] : array();
		$this->upgrade_info = (!empty($upgraders['upgrader_info'])) ? $upgraders['upgrade_info'] : array();
	}
}

?>
