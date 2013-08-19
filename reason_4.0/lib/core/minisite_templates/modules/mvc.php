<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include the base class & dependencies, and register the module with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/mvc.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ReasonMVCModule';

/**
 * An MVC pluggable minisite module.
 *
 * This module allows one to use a page type to choose and configure ReasonMVC components.
 *
 * Often, this module can be used directly, but there are several instances where it makes sense to extend it.
 *
 * - Hard coded configuration parameters may be better than page type parameters for some controllers, models, and views.
 * - Module system specific concerns like breadcrumbs, inline editing, head items, standalone API, etc should be handled in a module.
 * - The configuration of an MVC combination is dependent upon the request variables.
 * - You want to specify default views and models so that page types can just pass in configuration params.
 * 
 * In effect, when extended the module itself becomes something of a super controller. It is generally a good idea to keep module specific 
 * logic out of a controller such that the MVC components can easily be used anywhere - not just in the module system. A controller should 
 * never have any dependencies on the module system or template itself.
 *
 * A page_type can use this module directly in a couple ways:
 *
 * Directly specify the path to component files - no controller specified so it uses the default.
 *
 * 'main_post' => array( 'module' => 'mvc',
 *				  		 'model' => '/path/to/my/model.php',
 *				  		 'view' => '/path_to/my/view.php' )
 *
 * Same as before, but we do a bit of configuration of the model, setting a config param we call "db_source."
 *
 * 'main_post' => array( 'module' => 'mvc',
 *				  		 'model' => array('file' => '/path/to/my/model.php', 'db_source' => 'my_db_source'),
 *				  		 'view' => '/path_to/my/view.php' )
 *
 * Note that controllers, models, and views could be used within some other context. In the last example page_type, you could duplicate
 * the functionality of this module in a template, content manager, or whatever with this code:
 *
 * $model = new MyModel;
 * $model->config('db_source', 'my_db_source');
 * $view = new MyView;
 * $controller = new ReasonMVCController($model, $view);
 * echo $controller->run();
 *
 * @version .9b
 *
 * @author Nathan White
 */
class ReasonMVCModule extends DefaultMinisiteModule
{
	var $acceptable_params = array('controller' => '',
								   'model' => '',
								   'view' => '');
	
	/**
	 * @param string file name of the default view
	 */
	var $view;
	
	/**
	 * @param string file name of the default model
	 */
	var $model;
	
	/**
	 * @param string file name of the default controller
	 */
	var $controller;
	
	protected $content;
	
	// set everything up and get the model data.
	function init( $args = array() )
	{
		$controller = $this->get_controller();
		$this->content = $controller->run();
	}

	function has_content()
	{
		return (!empty($this->content));
	}
	
	function run()
	{
		echo $this->content;
	}
	
	final protected function get_controller()
	{
		if (!isset($this->_controller))
		{
			if (!empty($this->params['controller']))
			{
				$params = (is_string($this->params['controller'])) ? array('file' => $this->params['controller']) : $this->params['controller'];
				$this->_controller = $this->_setup_mvc('controller', $params);
			}
			else $this->_controller = $this->_setup_mvc('controller');
			$this->_controller->model($this->get_model());
			$this->_controller->view($this->get_view());
		}
		return $this->_controller;
	}
	
	final private function get_model()
	{
		if (!isset($this->_model))
		{
			if (!empty($this->params['model']))
			{
				$params = (is_string($this->params['model'])) ? array('file' => $this->params['model']) : $this->params['model'];
				$this->_model = $this->_setup_mvc('model', $params);
			}
			else $this->_model = $this->_setup_mvc('model');
		}
		return $this->_model;
	}
	
	final private function get_view()
	{
		if (!isset($this->_view))
		{
			if (!empty($this->params['view']))
			{
				$params = (is_string($this->params['view'])) ? array('file' => $this->params['view']) : $this->params['view'];
				$this->_view = $this->_setup_mvc('view', $params);
			}
			else $this->_view = $this->_setup_mvc('view');
		}
		return $this->_view;
	}
	
	/**
	 * Include a file if needed - we allow a relative path from minisite_templates/modules/ or an absolute path.
	 */
	final private function _setup_mvc($type, $params = NULL)
	{
		if (!in_array($type, array('controller', 'model', 'view')))
		{
			trigger_warning('The type passed ('.$type.') to _setup_mvc must be "controller", "model", or "view"', 1);
			return NULL;
		}
		if (!empty($params['file']) || !empty($this->$type)) // we need to do an include
		{
			if (empty($params['file']) || !empty($this->$type)) $params['file'] = $this->$type;	
			if (reason_file_exists('minisite_templates/modules/'.$params['file']))
			{
				reason_include_once('minisite_templates/modules/'.$params['file']);
				$full_path = reason_resolve_path('minisite_templates/modules/'.$params['file']);
			}
			elseif (reason_file_exists($params['file']))
			{
				reason_include_once($params['file']);
				$full_path = reason_resolve_path($params['file']);
			}
			elseif (file_exists($params['file']))
			{
				include_once($params['file']);
				$full_path = realpath($params['file']);
			}
			else trigger_error('The mvc module was unable to load the ' . $type . ' ('.$params['file'].')', FATAL);
			if (isset($GLOBALS[ '_reason_mvc_'.$type.'_class_names' ][ reason_basename($full_path) ]))
			{
				$class_name = $GLOBALS[ '_reason_mvc_'.$type.'_class_names' ][ reason_basename($full_path) ];
			}
			else trigger_error('The mvc module was unable to determine the class name for the ' . $type . ' ('.$params['file'].') - check that the file properly registers itself.', FATAL);
			unset($params['file']);
		}
		else
		{
			if ($type == 'controller') $class_name = 'ReasonMVCController'; // setup the default controller
		}
		
		// instantiate and return it.
		if (isset($class_name))
		{
			$obj = new $class_name;
			if (!empty($params)) foreach($params as $k=>$v)
			{
				$obj->config($k, $v);
			}
			return $obj;
		}
	}
}
?>