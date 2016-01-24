<?php

	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_require_once( 'minisite_templates/modules/events_gallery/item.php');

	$GLOBALS[ '_module_class_names' ][ 'events_gallery' ] = 'EventsGalleryModule';
	
	class EventsGalleryModule extends DefaultMinisiteModule
	{
		protected $events;
		protected $template;
		var $acceptable_params = array(
			'show' => array('current','upcoming'),
			'template' => 'big_list',
			'order' => '`last_occurence` ASC',
			'no_events_message' => '',
			'model' => 'item',
			'max' => 0,
			'categories' => '', // comma-separated unique names
		);
		function _get_events()
		{
			if(!isset($this->events))
			{
				$es = new entity_selector($this->site_id);
				$es->add_type(id_of('event_type'));
				
				if(!in_array('archived',$this->params['show']))
				{
					$es->add_relation('`last_occurence` >= "'.reason_sql_string_escape(date('Y-m-d')).'"');
				}
				if(!in_array('upcoming',$this->params['show']))
				{
					$es->add_relation('`datetime` < "'.reason_sql_string_escape(date('Y-m-d',time() + (60*60*24))).'"');
				}
				if(!in_array('current',$this->params['show']))
				{
					$es->add_relation('(`last_occurence` < "'.reason_sql_string_escape(date('Y-m-d')).'" OR `datetime` >= "'.reason_sql_string_escape(date('Y-m-d',time() + (60*60*24))).'")');
				}
				if(!empty($this->params['categories']))
				{
					if($cats = $this->get_categories_from_param($this->params['categories']))
					{
						$es->add_left_relationship(array_keys($cats),relationship_id_of('event_to_event_category'));
					}
					else
					{
						$es->add_relation('1 = 2');
					}
				}
				$es->add_relation('`show_hide` = "show"');
				$es->set_order($this->params['order']);
				if(!empty($this->params['max']))
					$es->set_num($this->params['max']);
				$this->_modify_events_es($es);
				$events = $es->run_one();
				$class = $this->get_model_class($this->params['model']);
				foreach($events as $id => $event)
				{
					$this->events[$id] = new $class($event);
				}
				if(empty($this->events))
					$this->events = array();
			}
			return $this->events;
		}
		function get_categories_from_param($param_string)
		{
			$unames = explode(',',$param_string);
			$cats = array();
			foreach($unames as $uname)
			{
				$uname = trim($uname);
				if(reason_unique_name_exists($uname))
				{
					$id = id_of($uname);
					$cats[$id] = new entity($id);
				}
				else
				{
					trigger_error('Category with unique name '.$uname.' not found');
				}
			}
			return $cats;
		}
		function get_model_class($model_name)
		{
			reason_include_once('minisite_templates/modules/events_gallery/'.$model_name.'.php');
			if(!empty($GLOBALS['reason_event_gallery_models'][$model_name]))
			{
				if(class_exists($GLOBALS['reason_event_gallery_models'][$model_name]))
				{
					return $GLOBALS['reason_event_gallery_models'][$model_name];
				}
				else
				{
					trigger_error('Model of class '.$_GLOBALS['reason_event_gallery_models'][$model_name].' not found -- badly formatted model name or model not properly registered', HIGH);
				}
			}
			else
			{
				trigger_error('No model found -- badly formatted model name or model not properly registered', HIGH);
			}
		}
		function _modify_events_es($es)
		{
		}
		function init($args = array())
		{
			parent::init($args);
			if($head_items = $this->get_head_items())
			{
				if($template = $this->_get_template())
					$template->add_head_items($head_items);
			}
		}
		function has_content()
		{
			if(!empty($this->params['no_events_message']))
				return true;
			
			$events = $this->_get_events();
			return !empty($events);
		}
		
		protected function _get_template()
		{
			if(!isset($this->template))
			{
				reason_include_once( 'minisite_templates/modules/events_gallery/templates/'.str_replace('../','',$this->params['template']).'.php');
				$classname = 'eventsGallery'.ucfirst($this->params['template']).'Template';
				if(class_exists($classname))
				{
					$this->template = new $classname;
				}
				else
				{
					trigger_error('Unable to load a template from '.$this->params['template']);
				}
			}
			return $this->template;
		}

		function run()
		{
			$events = $this->_get_events();
			if(!empty($events))
			{
				if($template = $this->_get_template())
					echo $template->get_markup($events);
			}
			elseif(!empty($this->params['no_events_message']))
			{
				echo '<div id="eventsGalleryModule" class="noEventsMessage">'."\n";
				echo $this->params['no_events_message']."\n";
				echo '</div>'."\n";
			}
		} // }}}
	}
?>
