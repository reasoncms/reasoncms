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
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ModuleGroupModule';
	
class ModuleGroupModule extends DefaultMinisiteModule {
	public $acceptable_params = array(
		'submodules' => array()
	);

	private $args;
	private $subModulesConfig;
	private $submodules;

	function init( $args = array() ) {	
		echo "<div style='background-color:pink'>initing...<p>";
		$this->args = $args;
		$this->submodules = Array();

		$this->subModulesConfig = $this->params['submodules'];
		$this->loadSubModules();
		echo "</div>";
	}
	
	function run() {
		echo "<div style='background-color:pink'>running...<p>";
		foreach ($this->submodules as $module) {
			echo "run module...<br>";
			$module->run();
		}
		echo "</div>";
	}

	function prepAndInitSubModule($module, $params) {
		$args = array();
		// pass through some items as-is
		$args['parent'] =& $this->args['parent'];
		$args['page_id'] = $this->args['page_id'];
		$args['site_id'] = $this->args['site_id'];
		$args['cur_page'] = $this->args['cur_page'];
		$args['textonly'] = $this->args['textonly'];
		$args['page_is_public'] = $this->args['page_is_public'];

		// need to figure out what to do with these guys....
		$args['identifier'] = "?????";	// FIX ME!!!
		$args['api'] = false;	// FIX ME!!!

		$module->prep_args($args);
		$module->set_page_nav($this->_pages);
		$module->set_head_items($this->_head_items);
		$module->set_crumbs($this->_crumbs);

		$module->handle_params($params);

		$module->pre_request_cleanup_init();

		// FIX ME!!!
		// $module->request = $this->CLEAN_EXTERNAL_VARS($module->get_cleanup_rules());

		$module->init($args);
	}

	// this method and the helpers it calls are basically a reworking of 
	// MinisiteTemplate::load_modules. Reads the config, sets up the various 
	// specific/passthru configuration values, and creates the modules that 
	// the ModuleGroup module is managing.
	function loadSubModules() {
		echo "submodules:<BR>";
		foreach ($this->subModulesConfig as $smCfg) {
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
					echo "<LI>" . $moduleName . " ($moduleFilename)";

					$moduleClass = (!empty($GLOBALS['_module_class_names'][$moduleName])) ? $GLOBALS['_module_class_names'][$moduleName] : '';
					if (!empty($moduleClass)) {
						echo " ($moduleClass)";
						// region_name, module_name, module_filename, module_params
						$module = new $moduleClass;
						$this->prepAndInitSubModule($module, $moduleParams);
						$this->submodules[] = $module;
					} else {
						trigger_error('Badly formatted module (' . $moduleName . ') - module class not set.');
					}
				} else {
					trigger_error('Unable to find php file for module (' . $moduleName . ')');
				}

				echo "</LI>";
			}
		}
	}
	
}
?>
