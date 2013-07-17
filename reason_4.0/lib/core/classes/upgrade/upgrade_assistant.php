<?php
/**
 * @package reason 
 * @subpackage upgrade
 */

include_once('reason_header.php');
include_once(DISCO_INC . 'disco.php');
reason_include_once('function_libraries/file_finders.php');

class reasonUpgradeAssistant
{
	protected $upgraders;
	protected $upgrade_info;
	
	function get_upgraders($upgrade_string)
	{
		if (!isset($this->upgraders))
		{
			$this->_build_upgrader_info($upgrade_string);
		}
		return $this->upgraders;
	}

	function get_standalone_upgraders($upgrade_string)
	{
		if (!isset($this->standalone_upgraders))
		{
			$this->_build_upgrader_info($upgrade_string);
		}
		return $this->standalone_upgraders;
	}
	
	function get_upgrade_info($upgrade_string)
	{
		if (!isset($this->upgrade_info))
		{
			$this->_build_upgrader_info($upgrade_string);
		}
		return $this->upgrade_info;
	}
	
	function get_active_upgraders($upgrade_string, $requested_upgrader = NULL)
	{
		if (empty($requested_upgrader)) return array();
		if ($requested_upgrader == '_all_')
		{
			return $this->get_upgraders($upgrade_string);
		}
		else
		{
			$upgraders = $this->get_upgraders($upgrade_string);
			if (isset($upgraders[$requested_upgrader])) return array($upgraders[$requested_upgrader]);
			$standalone_upgraders = $this->get_standalone_upgraders($upgrade_string);
			if (isset($standalone_upgraders[$requested_upgrader])) return array($standalone_upgraders[$requested_upgrader]);
		}
		return array();
	}
	
	/**
	 * Return the basic form.
	 */
	function get_runner_form($type)
	{
		if ($type == 'run' || $type == 'run_all' || $type == 'continue')
		{
			$rf = new Disco();
			if ($type == 'run_all')
			{
				$submit_button_text = 'Run Module(s)';
			}
			elseif ($type == 'run')
			{
				$submit_button_text = 'Run Module';
			}
			elseif ($type == 'continue')
			{
				$submit_button_text = 'Continue Upgrade';
				$rf->add_element('not_complete', 'comment', array('text' => '<strong>This upgrade is not yet complete.</strong>'));
			}
			$rf->add_element('mode', 'hidden');
			$rf->set_value('mode', 'run');
			$rf->set_actions(array($submit_button_text));
			return $rf;
		}
		else
		{
			trigger_error('The runner form type must be run, test, run_all, or continue');
		}
	}
	
	/**
	 * Wrap get_runner_form in a bit of output buffering.
	 */
	function get_runner_form_output($type)
	{
		ob_start();
		$form = $this->get_runner_form($type);
		$form->run();
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	/**
	 * Run upgraders - handle bulk or single seamlessly.
	 */
	function get_upgrader_output($upgrader, $user_id = NULL, $head_items = NULL)
	{
		$str = '';
		$upgraders = is_object($upgrader) ? array($upgrader) : $upgrader;
		$running_multiple = (count($upgraders) > 1);
		foreach ($upgraders as $upgrader)
		{
			if ($user_id) $upgrader->user_id($user_id);
			$str .= $this->_get_upgrader_output($upgrader, $running_multiple, $head_items);
		}
		if ($running_multiple)
		{
			if (!isset($_POST['mode']))
			{
				$str .= $this->get_runner_form_output('run_all');
			}
		}
		return $str;
	}

	/**
	 * Wrap running of an upgrader in an output buffer.
	 */
	function _get_upgrader_output($upgrader, $running_multiple = FALSE, $head_items = NULL)
	{
		$implements = class_implements($upgrader);
		ob_start();
		if (in_array('reasonUpgraderInterfaceAdvanced', $implements)) // we implement run and test as callbacks in this case instead of doing them here.
		{
			$returned_output = '';
			$rf = new Disco();
			$upgrader->init($rf, $head_items);
			$rf->run();
		}
		elseif (in_array('reasonUpgraderInfoInterface', $implements))
		{
			$returned_output = $upgrader->run();	
		}
		elseif (in_array('reasonUpgraderInterface', $implements))
		{
			$test_or_run = (isset($_POST['mode']) && ($_POST['mode'] == 'run')) ? 'run' : 'test';
			if($test_or_run == 'run')
			{
				$returned_output = '<h3>'.$upgrader->title().'</h3>';
				$returned_output .= $upgrader->run();
				if(method_exists($upgrader,'run_again'))
				{
					if($upgrader->run_again())
					{
						$returned_output .= $this->get_runner_form_output('continue');
					}
					else
					{
						$returned_output .= '<strong>The upgrade is finished -- no need to run again</strong>';
					}
				}
			}
			else
			{
				$returned_output = '<h3>'.$upgrader->title().' (<em>Testing Mode</em>)</h3>';
				$returned_output .= $upgrader->test();
				if (!$running_multiple) $returned_output .= $this->get_runner_form_output('run');
			}
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output . $returned_output;
	}
	
	function _build_upgrader_info($upgrade_string)
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
						{
							$upgrader = new $classname;
							if (method_exists($upgrader, 'standalone') && $upgrader->standalone())
							{
								$upgraders['standalone_upgraders'][$name] = $upgrader;
							}
							else $upgraders['upgraders'][$name] = $upgrader;
						}
						elseif($obj instanceof reasonUpgraderInterfaceAdvanced)
						{
							$upgraders['standalone_upgraders'][$name] = new $classname;
						}
						elseif ($obj instanceof reasonUpgraderInfoInterface)
						{
							$upgraders['upgrade_info'][$name] = new $classname;
						}
						else
						{
							trigger_error('Upgraders must implement the reasonUpgraderInterface, reasonUpgraderInterfaceAdvanced, or the reasonUpgraderInfoInterface; '.$classname.' appears not to.');
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
		if (!empty($upgraders['upgrade_info'])) ksort($upgraders['upgrade_info']);
		if (!empty($upgraders['standalone_upgraders'])) ksort($upgraders['standalone_upgraders']);
		$this->upgraders = (!empty($upgraders['upgraders'])) ? $upgraders['upgraders'] : array();
		$this->upgrade_info = (!empty($upgraders['upgrade_info'])) ? $upgraders['upgrade_info'] : array();
		$this->standalone_upgraders = (!empty($upgraders['standalone_upgraders'])) ? $upgraders['standalone_upgraders'] : array();
	}
}