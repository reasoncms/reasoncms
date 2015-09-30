<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * include dependencies
 */
reason_include_once('minisite_templates/modules/generic3.php');
reason_include_once('minisite_templates/modules/classified/classified_model.php');

/**
 * Register the module with Reason
 */
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'ClassifiedModule';
/**
 * The classified module is based upon Generic3, and uses something of an MVC approach to handle various scenarios
 *
 * Both the model and the view can be specified in the page type - the module acts as the controller
 *
 * The model responds to requests for information (ie - categories, classified entities, etc) and is available to view and the controller
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
														  'extra_args' => array('add_item', 'submit_success','delete_item')));
		
		return array_merge(parent::get_cleanup_rules(), $cleanup_rules);
	}

	function init( $options = array() )
	{
		if (isset($this->request['item_id'])) $this->model->set_classified_id($this->request['item_id']);

		if (empty($this->request['classified_mode']))
		{
			$this->view->init_view();
			
			// set various generic 3 instance variables based upon the view
			$this->show_list_with_details = $this->view->get_show_list_with_details();
			$this->use_filters = $this->view->get_use_filters();
			
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
		elseif ($this->request['classified_mode'] == 'delete_item')
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
		elseif ($this->request['classified_mode'] == 'delete_item')
		{
			$this->view->show_return_to_listing_text();
			if ($this->model->get_user_can_delete($this->model->get_classified_id()))
			{
				$this->model->delete_classified($this->model->get_classified_id());
				$this->view->show_successful_delete_text();
			} else {
				$this->view->show_submit_classified_text();
				$this->view->show_header_text();
				parent::run();
				$this->view->show_footer_text();
			}				
		}
	}

	/**
	 * Provide the view and model with an opportunity to alter the entity selector
	 * Typically the model might add an additional limited (such as limiting the selection based on the classified_days_duration value
	 * The view typically applies the sort field and direction.
	 */
	function alter_es()
	{	
		$this->model->alter_es($this->es);
		$this->view->alter_es($this->es);
	}

	/**
	 * Instruct the view to generate the summary list of items
	 */
	function do_list()
	{	
		$this->view->show_summary_list($this->items);
	}

	/**
	 * Instruct the view to generate the item detail view.
	 */
	function show_item_content($entity)
	{
		$this->view->show_item($entity);
	}
	
	/**
	 * We are going to zap the normal functionality of construct_link in favor of carl_make_link
	 */
	function construct_link($item, $other_args=array() )
	{
		$other_args['item_id'] = (!empty($item)) ? $item->id() : '';
		return carl_make_link($other_args);
	}
}

?>
