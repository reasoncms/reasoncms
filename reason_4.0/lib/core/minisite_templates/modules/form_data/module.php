<?php
$GLOBALS[ '_module_class_names' ][ basename( 'form_data', '.php' ) ] = 'FormDataModule';
reason_include_once( 'minisite_templates/modules/default.php' );
include_once(THOR_INC.'thor_viewer.php');

/**
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
				trigger_error('A form_id must be specified in the page type.');
			}
		}
		return $this->form_entity;
	}
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
	function initalize_markup_object($markup_object)
	{
		$markup_object->set_form($this->get_form_entity());
		
	}
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