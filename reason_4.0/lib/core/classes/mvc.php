<?php
/**
 * ReasonMVC base classes
 *
 * - ReasonMVCController
 * - ReasonMVCModel
 * - ReasonMVCView
 *
 * This is considered beta but should be fully baked by Reason 4.2.
 *
 * @package reason
 * @subpackage mvc
 */
 
/**
 * ReasonMVCController class
 *
 * Provides a configurable controller. Implements 3 methods that are declared final:
 *
 * - The config method is used to get and set controller configuration.
 * - The model method is used to get or set the model.
 * - the view method is used to get or set the view.
 *
 * The run method is extensible. The provided one, suitable for many cases, does this:
 *
 * - If a model and view are provided, grab the model data, set it on the view, output the view get() method.
 * - If just a view is provided, output the view get() method.
 *
 * For the basic use case, just provide a model and view, and run() it.
 *
 * In an advanced case you might extend the run() method, and use it to pick and configure a model and view.
 * 
 * @version .9b
 * @author Nathan White
 */
class ReasonMVCController
{
	protected $config;
	private $model;
	private $view;
	
	/**
	 * ReasonMVCController Constructor
	 *
	 * @param object ReasonMVCModel
	 * @param object ReasonMVCView
	 */
	function __construct($model = NULL, $view = NULL)
	{
		if ($model) $this->model($model);
		if ($view) $this->view($view);
	}
	
	/**
	 * Set or get configuration paramaters for the controller.
	 *
	 * @param string configuration key
	 * @param mixed configuration value - stored for the provided key.
	 * @return mixed value for provided key or NULL if it is not set.
	 */
	final function config($key, $value = NULL)
	{
		if (isset($value)) $this->config[$key] = $value;
		if (isset($this->config[$key])) return $this->config[$key];
		return NULL;
	}
	
	/**
	 * Get or set the model.
	 *
	 * @param object optionally provide a ReasonMVCModel based object to set the model.
	 * @return object ReasonMVCModel
	 */
	final function model($model = NULL)
	{
		if (isset($model))
		{
			$this->model = $model;
		}
		return $this->model;
	}
	
	/**
	 * Get or set the view.
	 *
	 * @param object optionally provide a ReasonMVCView based object to set the view.
	 * @return object ReasonMVCView
	 */
	final function view($view = NULL)
	{
		if (isset($view))
		{
			$this->view = $view;
		}
		return $this->view;
	}
	
	/**
	 * Gets the data from the model, sets it on the view, and returns the output of the view get method.
	 */
	function run()
	{
		$view = $this->view();
		$model = $this->model();
		if (!is_null($view) && !is_null($model))
		{
			$view->data($model->get());
			return $view->get();
		}
		elseif (!is_null($view))
		{
			return $view->get();
		}
	}
}

/**
 * ReasonMVCModel class
 *
 * Provides a configurable model. Implements 2 methods that are declared final:
 *
 * - The config method is used to get and set model configuration.
 * - The get method builds (if needed) the model data and returns it.
 *
 * The abstract build method must be implemented when creating a model - a few things to keep in mind.
 *
 * - Return a well defined data structure such that it is easy to implement views that use it.
 * - One good strategy it to have a model return an object that implements an interface.
 * - Implement object caching in build so that multiple instances of a model with the same config do not have to do unneeded work.
 *
 * @version .9b
 * @author Nathan White
 * @todo should be force rebuild if config changes or leave that to the user?
 */
abstract class ReasonMVCModel
{
	protected $config;
	protected $data;
	
	/**
	 * The build method returns the model data. It is a private function called by the model get() method.
	 *
	 * @return the built copy of the data -= this is where we do the heavy hitting.
	 */
	abstract protected function build();
	
	/**
	 * Set or get configuration paramaters for the model.
	 *
	 * @param string configuration key
	 * @param mixed configuration value - stored for the provided key.
	 * @return mixed value for provided key or NULL if it is not set.
	 */
	final function config($key, $value = NULL)
	{
		if (isset($value)) $this->config[$key] = $value;
		if (isset($this->config[$key])) return $this->config[$key];
		return NULL;
	}
	
	/**
	 * A model should clearly document what it returns and it should never be NULL
	 *
	 * Some examples of what a model might return:
	 *
	 * - an object that implements an interface.
	 * - an array with a predictable set of contents.
	 * - an xml string.
	 *
	 * @param boolean force the build method to be called again for the model - default false.
	 */
	final function get( $force_rebuild = false )
	{
		if (is_null($this->data) || $force_rebuild )
		{
			$this->data = $this->build();
			if (is_null($this->data))
			{
				trigger_fatal_error('The model build() method must return a non null value.');
			}
		}
		return $this->data;
	}
}

/**
 * ReasonMVCView class
 *
 * Provides a configurable view. Implements 2 methods that are declared final:
 *
 * - The config method is used to get and set view configuration.
 * - The data method can be used to get or set the data used by the view.
 *
 * The abstract get method must be implemented when creating a view - a few things to keep in mind.
 *
 * - A view often gets it data from a controller, but not necessarily.
 * - Don't be afraid to use a little PHP in a view - especially for control structures.
 * - In the documentation for a view, make sure to clearly describe what it returns.
 *
 * A view is quite abstract - it cares only about its data and possibly its configuration. A view could
 * very easily instantiate and get() the content of another view.
 * 
 * @version .9b
 * @author Nathan White
 */
abstract class ReasonMVCView
{	
	protected $config;
	protected $data;

	/**
	 * Set or get configuration paramaters for the view.
	 *
	 * @param string configuration key
	 * @param mixed configuration value - stored for the provided key.
	 * @return mixed value for provided key or NULL if it is not set.
	 */
	final function config($key, $value = NULL)
	{
		if (isset($value)) $this->config[$key] = $value;
		if (isset($this->config[$key])) return $this->config[$key];
		return NULL;
	}
	
	/**
	 * Get or set the data used by the view.
	 */
	final function data($data = NULL)
	{
		if (isset($data))
		{
			$this->data = $data;
		}
		return $this->data;
	}
	
	/**
	 * A view should clearly document what it returns
	 *
	 * Some examples of what a view might return:
	 *
	 * - html
	 */
	abstract function get();
}
?>