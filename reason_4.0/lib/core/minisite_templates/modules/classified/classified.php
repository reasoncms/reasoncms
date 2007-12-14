<?php
reason_include_once('minisite_templates/modules/generic3.php');
reason_include_once('minisite_templates/modules/classified/classified_model.php');

$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'ClassifiedModule';
/**
 * The classified module is based upon Generic3, and uses something of an MVC approach to handle various scenarios
 *
 * Both the model and the view can be specified in the page type - the module acts as the controller
 *
 * The model responds to requests for information (ie - categories, classified entities, etc) and is available to view and the controller
 *
 * @package reason
 * @subpackage minisite_modules
 * 
 * @author Nathan White and Dan Ehrenberg
 */
class ClassifiedModule extends Generic3Module
{
	// generic3 variable overrides
	var $type_unique_name = 'classified_type';
	var $use_pagination = true;
	var $use_filters = true;
	var $filter_types = array('category'=>array(  'type'=>'classified_category_type','relationship'=>'classified_to_classified_category'));
	var $search_fields = array('entity.name', 'classified_table.price', 'chunk.content', 'chunk.author', 'classified_table.location');

	// default model name
	var $model_name = 'classified_model';
	var $model;
	
	// default view name
	var $view_name = 'classified_view';
	var $view;
	
	var $jump_to_item_if_only_one_result = false;
	
	var $filter_displayer = 'listnav.php';
	
	var $acceptable_params = array('view' => NULL,'model' => NULL);
	
	/**
	 * Set the model and view from parameters before we grab the request
	 * @author Nathan White
	 */
	function pre_request_cleanup_init()
	{
		// check page type for custom model and view
		if (isset($this->params['model'])) $this->model_name = $this->params['model'];
		if (isset($this->params['view'])) $this->view_name = $this->params['view'];
		
		$model_path = '/minisite_templates/modules/classified/'.$this->model_name.'.php';
		$view_path = '/minisite_templates/modules/classified/'.$this->view_name.'.php';

		if (reason_file_exists($model_path)) reason_include_once($model_path);
		else trigger_error('The classified module model could not be found at ' . $model_path, FATAL);
	
		if (reason_file_exists($view_path)) reason_include_once($view_path);
		else trigger_error('The classified module view could not be found at ' . $view_path, FATAL);
		
		// initialize the class names
		$model_classname = $GLOBALS['_classified_module_model']['classified/'.$this->model_name];
		$view_classname = $GLOBALS['_classified_module_view']['classified/'.$this->view_name];
		
		// create the model and pass it some basic info
		$this->model = new $model_classname();
		$this->model->set_site_id($this->site_id);
		$this->model->set_head_items($this->parent->head_items);
		
		// create the view and pass a reference to the model
		$this->view = new $view_classname();
		$this->view->set_model($this->model);
	}
	
	function get_cleanup_rules()
	{
		$cleanup_rules = array('classified_mode' => array('function' => 'check_against_array',
														  'extra_args' => array('add_item', 'submit_success')));
														  
		$extra_cleanup_rules =& $this->model->get_extra_cleanup_rules();
		return array_merge(parent::get_cleanup_rules(), $cleanup_rules, $extra_cleanup_rules);
	}

	function init( $options = array() )
	{
		if (empty($this->request['classified_mode']))
		{
			$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$this->view->init_view();
			parent::init();
		}
		elseif ($this->request['classified_mode'] == 'add_item')
		{
			$this->view->init_view_and_form();
		}
		elseif ($this->request['classified_mode'] == 'submit_success')
		{
			$this->view->init_view();
		}
	}
	
	function run()
	{
		if (empty($this->request['classified_mode']))
		{
			$this->view->show_submit_classified_text();
			$this->view->show_header_text();
			parent::run();
			$this->view->show_footer_text();
		}
		elseif ($this->request['classified_mode'] == 'add_item')
		{
			$this->view->show_return_to_listing_text();
			$this->view->show_form_header_text();
			$this->view->show_disco_form();
			$this->view->show_form_footer_text();
		}
		elseif ($this->request['classified_mode'] == 'submit_success')
		{
			$this->view->show_return_to_listing_text();
			$this->view->show_successful_submit_text();
		}
	}

	// Grab the sort field and the sort order - can these happen in the init?
	function get_sort_field() {
		return empty($this->request['table_sort_field']) ? 'datetime' : $this->request['table_sort_field'];
	}
	
	function get_sort_order() {
		return empty($this->request['table_sort_order']) ? 'desc' : $this->request['table_sort_order'];
	}

	function alter_es()
	{	
		$sort_order = $this->get_sort_field() . ' ' . $this->get_sort_order();
		
		$this->es->set_order($sort_order);
		if (!empty($this->request['category']))
			$this->es->add_left_relationship($this->request['category'], relationship_id_of('classified_to_classified_category'));
		$this->es->add_relation('classified_duration_days < NOW() - datetime');
	}

	/**
	 * Ask the view to generate the item_list instead of using the generic3 do_list method
	 */
	function do_list()
	{	
		$this->view->show_list($this->items);
	}

	function show_item_content($entity)
	{
		$this->view->show_item($entity);
	}
	
	/**
	 * Uses carl_make_link instead of the construct_link method used in generic3 - allows us to preserve sort order
	 */
	function get_pages_for_pagination_markup()
	{
		for ($i=1; $i<=$this->total_pages; $i++)
			$pages[$i] = array('url' => carl_make_link(array('page' => $i, 'item_id' => '')));
		return $pages;
	}
}

?>
