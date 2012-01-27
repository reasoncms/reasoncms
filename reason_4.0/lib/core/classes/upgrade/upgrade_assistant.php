<?php
/**
 * @package reason 
 * @subpackage upgrade
 */

include_once('reason_header.php');
reason_include_once('function_libraries/file_finders.php');

class reasonUpgradeAssistant
{
	function get_upgraders($upgrade_string, $name = NULL)
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
							$upgraders[$name] = new $classname;
						else
							trigger_error('Upgraders must implement the reasonUpgraderInterface; '.$classname.' appears not to.');
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
		ksort($upgraders);
		return $upgraders;
	}
}

?>
