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
reason_include_once( 'classes/module_group/module_grouper.php' );
reason_include_once( 'classes/module_group/module_group_layout_manager.php' );
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ModuleGroupModule';

/*
	March 2016 - tfeiler - Module that lets you put multiple modules into the same page location
	of a template. Configure it in page_types with a "submodules" array that mirrors the layout of an
	entry in the page_types array. Optionally specify a layout manager that extends "ModuleGroupLayoutManager"
	to control the exact layout (default behavior will wrap each submodule's output in an indexed div).

	'main_post' => array(
		'module' => 'module_group',
		'submodules' => array(
			array(
				'module' => 'echo',
				'msg' => 'This is another instance of the echo module'
			),
			'random_number/random_number',
			array(
				'module' => 'echo',
				'msg' => 'instance numero dos'
			),
			'random_number/random_number',
		),
		'layout_manager' => array(
			'file' => 'classes/module_group_alt_layout_manager.php',
			'class' => 'FooLayoutManager'
		)
	),
*/
	
class ModuleGroupModule extends DefaultMinisiteModule implements ModuleGrouper {
	public $acceptable_params = array(
		'submodules' => array(),
		'layout_manager' => array()
	);

	private $args;
	private $subModulesConfig;
	private $submodules;
	private $lm;

	function init( $args = array() ) {	
		$target = $this->get_api_submodule_target();

		$this->args = $args;
		$this->loadSubModules();
	}

	function setupLayoutManager() {
		$defaultLayoutManagerClass = "ModuleGroupLayoutManager";
		$layoutManagerClass = $defaultLayoutManagerClass;
		if (isset($this->params['layout_manager'])) {
			$lmFile = @$this->params['layout_manager']['file'];
			$lmClass = @$this->params['layout_manager']['class'];

			if (!empty($lmFile) && !empty($lmClass)) {
				if (reason_file_exists($lmFile)) {
					reason_include_once($lmFile);
					$layoutManagerClass = $lmClass;
				} else {
					trigger_error("supplied custom layout manager file '$lmFile' does not exist!");
				}
			}
		}
		$this->lm = new $layoutManagerClass();

		if ($layoutManagerClass != $defaultLayoutManagerClass && !is_subclass_of($layoutManagerClass, $defaultLayoutManagerClass)) {
			trigger_error("supplied custom layout manager file '$lmFile'/'$lmClass' does not extend $defaultLayoutManagerClass; using default layout manager class instead.");
			$this->lm = new $defaultLayoutManagerClass();
		}
	}

	function run() {
		$this->setupLayoutManager();
		$this->lm->runModules($this->submodules);
	}

	function get_api_submodule_target() {
		$api = $this->get_api();
		if ($api) {
			$re = "/submodule-(.*)-mcla-(.*)-mloc-(.*)-mpar-(.*)/"; 
			preg_match($re, $api->get_identifier(), $matches);
			return $matches[1];
		} else {
			return false;
		}
	}

	function run_api() {
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
						$supportedApis = call_user_func(array($moduleClass, 'get_supported_apis'), $moduleClass);
						// $rv[$moduleClass] = $supportedApis;

						foreach ($supportedApis as $apiName => $rsnApiObj) {
							if ($apiName != "standalone") {
								$rv[$apiName] = $rsnApiObj;
							}
						}
					}
				}
			}
		}

		return $rv;
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
		$this->submodules = Array();
		$this->subModulesConfig = $this->params['submodules'];

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

						$moduleClass = (!empty($GLOBALS['_module_class_names'][$moduleName])) ? $GLOBALS['_module_class_names'][$moduleName] : '';
						if (!empty($moduleClass)) {
							$module = new $moduleClass;
							$this->prepAndInitSubModule($submoduleCounter, $module, $moduleParams);
							$this->submodules[] = $module;
						} else {
							trigger_error('Badly formatted module (' . $moduleName . ') - module class not set.');
						}
					} else {
						trigger_error('Unable to find php file for module (' . $moduleName . ')');
					}
				}
			} else {
				$this->submodules[] = null;
			}
			$submoduleCounter++;
		}
	}

}
?>
