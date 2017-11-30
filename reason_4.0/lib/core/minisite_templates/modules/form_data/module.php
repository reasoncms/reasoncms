<?php
/**
 * @package reason
 * @subpackage modules
 */
 
/**
 * Register module and include dependencies
 */
$GLOBALS[ '_module_class_names' ][ basename( 'form_data', '.php' ) ] = 'FormDataModule';
reason_include_once( 'minisite_templates/modules/default.php' );
include_once(THOR_INC.'thor_viewer.php');

/**
 * This module is designed to simplify the display of Thor form data
 *
 * To use this module:
 *
 * 1. Extend the default markup class. See the documentation in form_data/markup/default.php.
 * 2. Create a page type that specifies the markup class to use. The form can be specified in the
 *    page type or attached to the page.
 * 3. Apply this page type to a page.
 *
 * Note that the default markup class will simply report on the form's data structure, so you can use
 * it to know what fields you have to work with.
 * 
 * Example page type:
 *
 * 'my_form_display' => array(
 * 		'main_post' => array(
 * 			'module' => 'form_data',
 * 			'markup' => 'path/to/markup/class.php',
 * 		),
 * ),
 * 
 * @todo add ability to transform data before it is passed to the markup class
 */
class FormDataModule extends DefaultMinisiteModule
{
	var $acceptable_params = array(
			'form' => '', //unique name or id
			'markup' => 'minisite_templates/modules/form_data/markup/default.php',
		);
	var $form_entity;
	var $markup_object;
	
	/**
	 * Get the form whose data we are displaying
	 * @return mixed form entity object if form can be identified, otherwise FALSE
	 */
	function get_form_entity()
	{
		if(!isset($this->form_entity))
		{
			$this->form_entity = false;
			if(!empty($this->params['form']))
			{
				if(is_numeric($this->params['form']))
				{
					if($form_id = (integer) $this->params['form'])
					{
						$form = new entity($form_id);
						if($form->get_value('type') == id_of('form'))
						{
							$this->form_entity = $form;
						}
						else
						{
							trigger_error('Invalid form ID "'.$this->params['form'].'"');
						}
					}
					else
					{
						trigger_error('Invalid form ID "'.$this->params['form'].'"');
					}
				}
				else
				{
					if($form_id = id_of($this->params['form']))
					{
						$form = new entity($form_id);
						if($form->get_value('type') == id_of('form'))
						{
							$this->form_entity = $form;
						}
						else
						{
							trigger_error('Unique name provided "'.$this->params['form'].'" not a form');
						}
					}
					else
					{
						trigger_error('Invalid unique name "'.$this->params['form'].'"');
					}
				}
			}
			else
			{
				$forms = $this->cur_page->get_left_relationship(relationship_id_of('page_to_form'));
				if(!empty($forms))
				{
					$this->form_entity = reset($forms);
				}
				else
				{
					trigger_error('No form found to display. Please attach a form to this page or specify a form in the page type.');
				}
			}
		}
		return $this->form_entity;
	}
	
	/**
	 * Get the markup object to use
	 * @return mixed markup object if form can be identified, otherwise FALSE
	 */
	function get_markup_object()
	{
		if(!isset($this->markup_object))
		{
			$this->markup_object = false;
			if(!empty($this->params['markup']))
			{
				if(reason_file_exists($this->params['markup']))
				{
					reason_include_once($this->params['markup']);
					if(!empty($GLOBALS['form_data_markups'][$this->params['markup']]))
					{
						$class = $GLOBALS['form_data_markups'][$this->params['markup']];
						if(class_exists($class))
						{
							$this->markup_object = new $class;
							$this->initalize_markup_object($this->markup_object);
						}
						else
						{
							trigger_error('Class '.$class.' not found');
						}
					}
					else
					{
						trigger_error('Markup file does not register itself in $GLOBALS[\'form_data_markups\'][\''.$this->params['markup'].'\']');
					}
				}
				else
				{
					trigger_error('No markup file exists at '.$this->params['markup']);
				}
			}
			else
			{
				trigger_error('A markup path must be specified in the page type.');
			}
		}
		return $this->markup_object;
	}
	
	/**
	 * Perform initialization actions on the markup object
	 * @param object $markup_object
	 * @return void
	 */
	function initalize_markup_object($markup_object)
	{
		$markup_object->set_form($this->get_form_entity());
		
	}
	
	/**
	 * Initialize the module
	 * @return void
	 */
	function init( $args = array() )
	{	
		parent::init( $args );
		
		if($markup_object = $this->get_markup_object())
		{
			if($form = $this->get_form_entity())
			{
				$thor = new ThorViewer();
				$thor->init_thor_viewer($form->id());
				$markup_object->set_form_data($thor->get_data());
				$markup_object->set_label_map($thor->get_display_name_map());
			}
			$markup_object->add_head_items($this->get_head_items());
		}			
	}
	
	/**
	 * Run the module
	 * @return void
	 */
	function run()
	{
		echo '<div class="formDataModule">';
		if($markup_object = $this->get_markup_object())
		{
			echo $markup_object->get_markup();
		}
		echo '</div>';
	}
}