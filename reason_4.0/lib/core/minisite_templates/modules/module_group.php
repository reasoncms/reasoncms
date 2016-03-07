<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/*
	 * 1. pass in params, head_items, etc.
	 * 2. support API's
 * 3. allow some kind of layout/templating? Do I need "page areas"?
 * 4. canonicalizer / registerer?
	 * 5. cleanup_rules
	 * 6. get including working!
 */
 
/**
 * Include the parent class & dependencies, and register the module with Reason
 */
reason_include_once( 'classes/module_grouper.php' );
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ModuleGroupModule';
	
class ModuleGroupModule extends DefaultMinisiteModule implements ModuleGrouper {
	public $acceptable_params = array(
		'submodules' => array()
	);

	private $args;
	private $subModulesConfig;
	private $submodules;

	function init( $args = array() ) {	
		$target = $this->get_api_submodule_target();

		// echo "<div style='background-color:pink'>initing...<p>";
		$this->args = $args;
		$this->submodules = Array();

		$this->subModulesConfig = $this->params['submodules'];
		$this->loadSubModules();

		// $templateRef = $this->args['parent'];
		// foreach ($templateRef->section_to_module as $region => $module_name) { echo "PARENT TEMPLATE [$region] => [$module_name]<BR>"; }
		// echo "</div>";
	}

	function run() {
		echo "<div style='background-color:pink'>";
		foreach ($this->submodules as $module) {
			$module->run();
		}
		echo "</div>";
	}

	function get_api_submodule_target() {
		$api = $this->get_api();
		if ($api) {
			// echo "api is: [" . $api->get_identifier() . "] / [" . $api->get_name() . "]\n";
			$re = "/submodule-(.*)-mcla-(.*)-mloc-(.*)-mpar-(.*)/"; 
			preg_match($re, $api->get_identifier(), $matches);
			return $matches[1];
		} else {
			return false;
		}
	}

	function run_api() {
		// echo "\n\n\n!!!!! CUSTOM run_AIP!!!!!\n\n\n";
		$apiTarget = $this->get_api_submodule_target();
		if (false !== $apiTarget) {
			$targetSubmodule = $this->submodules[$apiTarget];
			$targetSubmodule->run_api();
		}
	}

	function getGroupedModuleSupportedApis() {
		$rv = Array();
		foreach ($this->params["submodules"] as $smCfg) {
			$moduleName = "";
			if (is_array($smCfg)) {
				$moduleName = $smCfg['module'];
			} else if (is_string($smCfg)) {
				$moduleName = $smCfg;
			} else {
				trigger_error('Badly configured modules array for module_group module');
			}

			if ($moduleName != "") {
				$moduleFilename = ReasonPageTypes::resolve_filename($moduleName);
				if ($moduleFilename && reason_file_exists($moduleFilename)) {
					reason_include_once($moduleFilename);
					$moduleClass = (!empty($GLOBALS['_module_class_names'][$moduleName])) ? $GLOBALS['_module_class_names'][$moduleName] : '';
					if (!empty($moduleClass)) {
						// $module = new $moduleClass;
						$supportedApis = call_user_func(array($moduleClass, 'get_supported_apis'), $moduleClass);
						// echo "what apis exist for [" . $moduleName . "]?\n"; var_dump($supportedApis); echo "\n\n";
						$rv[$moduleClass] = $supportedApis;
					}
				}
			}
		}
		// echo "RETURNING!!!!!!\n\n"; var_dump($rv);

		$rv2 = Array();
		foreach ($rv as $moduleClass => $apis) {
			foreach ($apis as $apiName => $apiCfg) {
				if ($apiName != "standalone") {
					$rv2[$apiName] = $apiCfg;
				}
			}
		}

		// echo "RETURNING!!!!!!\n\n"; var_dump($rv2);
		return $rv2;
	}

	function prepAndInitSubModule($idx, $module, $params) {
		$args = array();
		// pass through some items as-is
		$args['parent'] =& $this->args['parent'];
		$args['page_id'] = $this->args['page_id'];
		$args['site_id'] = $this->args['site_id'];
		$args['cur_page'] = $this->args['cur_page'];
		$args['textonly'] = $this->args['textonly'];
		$args['page_is_public'] = $this->args['page_is_public'];

		$re = "/.*-mloc-(.*)-mpar.*/"; 
		preg_match($re, $this->identifier, $matches);
		$smId = "submodule-" . $idx . "-";
		$smId .= "mcla-" . get_class($module) . "-mloc-" . $matches[1] . "-mpar-" . md5(serialize($params));
		$args['identifier'] = $smId; // see ReasonAPIFactory::get_identifier_for_module();
		$args['api'] = $this->args['api'];

		$module->prep_args($args);
		$module->set_page_nav($this->_pages);
		$module->set_head_items($this->_head_items);
		$module->set_crumbs($this->_crumbs);

		$module->handle_params($params);

		$module->pre_request_cleanup_init();

		$templateRef = $this->args['parent'];
		$module->request = $templateRef->clean_external_vars($module->get_cleanup_rules());

		$module->init($args);
	}

	// this method and the helpers it calls are basically a reworking of 
	// MinisiteTemplate::load_modules. Reads the config, sets up the various 
	// specific/passthru configuration values, and creates the modules that 
	// the ModuleGroup module is managing.
	function loadSubModules() {
		// echo "submodules:<BR>";
		$submoduleCounter = 0;
		$apiTarget = $this->get_api_submodule_target();

		foreach ($this->subModulesConfig as $smCfg) {
			if ($apiTarget === false || $apiTarget == $submoduleCounter) {
				$moduleName = "";
				$moduleParams = Array();
				if (is_array($smCfg)) {
					$moduleName = $smCfg['module'];
					$moduleParams = $smCfg;
					unset($moduleParams["module"]);
				} else if (is_string($smCfg)) {
					$moduleName = $smCfg;
				} else {
					trigger_error('Badly configured modules array for module_group module');
				}

				if ($moduleName != "") {
					$moduleFilename = ReasonPageTypes::resolve_filename($moduleName);
					if ($moduleFilename && reason_file_exists($moduleFilename)) {
						reason_include_once($moduleFilename);
						// echo "<LI>" . $moduleName . " ($moduleFilename)";

						$moduleClass = (!empty($GLOBALS['_module_class_names'][$moduleName])) ? $GLOBALS['_module_class_names'][$moduleName] : '';
						if (!empty($moduleClass)) {
							// echo " ($moduleClass)";
							// region_name, module_name, module_filename, module_params
							$module = new $moduleClass;
							$this->prepAndInitSubModule($submoduleCounter, $module, $moduleParams);
							$this->submodules[] = $module;
						} else {
							trigger_error('Badly formatted module (' . $moduleName . ') - module class not set.');
						}
					} else {
						trigger_error('Unable to find php file for module (' . $moduleName . ')');
					}

					// echo "</LI>";
				}
			} else {
				$this->submodules[] = null;
			}
			$submoduleCounter++;
		}
	}

}
?>
