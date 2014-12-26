<?php
/**
 * @package reason
 * @subpackage classes
 */
/**
 * Include Reason libraries
 */
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * A class for building and altering themes in upgrade scripts
 *
 * @todo separate doing and reporting
 */
class reasonTheme
{
	var $id;
	var $entity;
	var $template;
	var $css = array();
	var $test_mode = false;
	var $report = '';
	function create($unique_name,$name,$template_id,$user_id)
	{
		if($this->get_id())
		{
			trigger_error('Create method may only be called on a reasonTheme that does not yet have an ID assigned.');
			return false;
		}
		$test_id = id_of($unique_name);
		if($test_id)
		{
			trigger_error('Theme with unique name '.$unique_name.' already exists. Could not create.');
		}
		$theme_id = reason_create_entity(id_of('master_admin'), id_of('theme_type'), $user_id, $name, array('unique_name'=>$unique_name,'new'=>0),$this->test_mode);
		if($theme_id)
		{
			$this->set_id($theme_id);
			$this->attach_template($template_id);
			return $theme_id;
		}
		return false;
	}
	function set_test_mode($test_mode)
	{
		$this->test_mode = $test_mode;
	}
	function attach_css($css_id)
	{
		if($this->test_mode)
		{
			$this->report .= '<p>Would have attached css id '.$css_id.'</p>';
			return true;
		}
		if(!empty($this->id))
		{
			return(create_relationship( $this->id, $css_id, relationship_id_of('theme_to_external_css_url')));
		}
		else
		{
			trigger_error('could not add css, as theme does not yet have an id');
			return false;
		}
	}
	function attach_template($template_id)
	{
		if($this->test_mode)
		{
			$this->report .= '<p>Would have attached template id '.$template_id.'</p>';
			return true;
		}
		if(!empty($this->id))
		{
			$old_template_id = $this->get_template_id();
			if($old_template_id != $template_id)
			{
				if($old_template_id)
				{
					delete_relationships(
array( 'entity_a' => $this->id , 'entity_b' => $old_template_id , 'type' => relationship_id_of('theme_to_minisite_template') ) );
				}
				return(create_relationship( $this->id, $template_id, relationship_id_of('theme_to_minisite_template')));
			}
			else
			{
				return true;
			}
		}
		else
		{
			trigger_error('could not add template, as theme does not yet have an id');
			return false;
		}
	}
	function set_id($id)
	{
		$this->id = $id;
		$this->entity = new entity($id);
	}
	function get_id()
	{
		return $this->id;
	}
	function get_value($key)
	{
		if(is_object($this->entity))
		{
			return $this->entity->get_value($key);
		}
		return false;
	}
	function get_template_id()
	{
		$template = $this->get_template();
		if(!empty($template))
			return $template->id();
		else
			return false;
	}
	function get_template()
	{
		if($this->get_id())
		{
			if(empty($this->template))
			{
				return $this->_query_for_template();
			}
			else
			{
				return $this->template;
			}
		}
		elseif(!$this->test_mode)
		{
			trigger_error('Unable to get template; no theme ID set');
		}
		return false;
	}
	function _query_for_template()
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_template'));
		$es->set_num(1);
		$es->add_right_relationship( $this->get_id(), relationship_id_of('theme_to_minisite_template'));
		$templates = $es->run_one();
		if(!empty($templates))
		{
			$this->template = current($templates);
			return $this->template;
		}
		return false;
	}
	function get_css()
	{
		if($this->get_id())
		{
			if(empty($this->css))
			{
				return $this->_query_for_css();
			}
			else
			{
				return $this->css;
			}
		}
		elseif(!$this->test_mode)
		{
			trigger_error('Unable to get css; no theme ID set');
		}
		return array();
	}
	function _query_for_css()
	{
		$es = new entity_selector();
		$es->add_type(id_of('css'));
		$es->set_order('sortable.sort_order ASC');
		$es->add_right_relationship( $this->get_id(), relationship_id_of('theme_to_external_css_url'));
		$css = $es->run_one();
		if(!empty($css))
		{
			$this->css = $css;
		}
		return $this->css;
	}
	function add_complete($unique_name,$name,$css = array(),$template_name,$user_id)
	{
		if($this->get_id())
		{
			trigger_error('add_complete method may only be called on a reasonTheme that does not yet have an ID assigned.');
			return false;
		}
		$all_ok = true;
		$output =  '<h4>Adding theme: '.$unique_name.'</h4>';
		$output .= '<ol>';
		if(empty($template_name))
		{
			$template_name = 'default';
		}
		if(empty($name))
		{
			$name = prettify_string($unique_name);
		}
		$template = get_template_by_name($template_name);
		if(empty($template))
		{
			$all_ok = false;
			$template_id = reason_add_template($template_name);
			if(!empty($template_id))
			{
				$template = new entity($template_id);
				$output .= '<li>Template created ('.$template_name.', id '.$template->id().')</li>';
			}
			else
			{
				$output .= '<li>Unable to create template '.$template_name.'. The template file may not be placed correctly.';
				if($this->test_mode)
				{
					$output .= ' Would abort theme addition.';
				}
				else
				{
					$output .= ' Aborting theme addition.';
				}
				$output .= '</li></ol>';
				return array('success'=>false,'report'=>$output);
			}
		}
		else
		{
			$output .= '<li>Template found ('.$template_name.', id '.$template->id().')</li>';
		}
		
		$theme_id = id_of($unique_name);
		if(!$theme_id)
		{
			$all_ok = false;
			$output .= '<li>Theme with unique name '.$unique_name.' needs to be created</li>';
			if(!$this->test_mode)
			{
				$theme_id = $this->create($unique_name,$name,$template->id(),$user_id);
				if(!$theme_id)
				{
					$output .= '<li>Theme '.$name.' unable to be created; aborting theme addition</li></ol>';
					return array('success'=>false,'report'=>$output);
				}
				else
				{
					$output .= '<li>Theme with unique name '.$unique_name.' created</li>';
				}
			}
			else
			{
				$output .= '<li>Would have attempted to create theme entity.</li>';
			}
		}
		else
		{
			$this->set_id($theme_id);
		}
		
		if($this->get_template_id() != $template->id())
		{
			$all_ok = false;
			$output .= '<li>Current theme template id ('.$this->get_template_id().') not the same as specified in update ('.$template->id().').</li>';
			if($this->test_mode)
			{
				$output .= '<li>Would have set theme template to be id '.$template->id().'</li>';
			}
			else
			{
				if($this->attach_template($template->id()))
				{
					$output .= '<li>Attached template id '.$template->id().' to '.$unique_name.'.</li>';
				}
				else
				{
					$output .= '<li>Unable to attach template for some reason. Aborting theme addition.</li>';
					return array('success'=>false,'report'=>$output);
				}
			}
				
		}
		
		$retrieved_css_entities = array();
		
		foreach($css as $css_name=>$css_info)
		{
			$output .= '<li>CSS: '.$css_name.'<ol>';
			if(empty($css_info['url']))
			{
				$output .= '<li>'.$css_name.' has no url specified; skipping this css item</li></ol></li>';
				continue;
			}
			else
			{
				$css_url = $css_info['url'];
			}
			if(empty($retrieved_css_entities[$css_url]))
			{
				$es = new entity_selector();
				$es->add_type(id_of('css'));
				$es->add_relation('url = "'.reason_sql_string_escape($css_url).'"');
				$es->set_num(1);
				$css_ents = $es->run_one();
				if(!empty($css_ents))
				{
					$retrieved_css_entities[$css_url] = current($css_ents);
				}
			}
			if(empty($retrieved_css_entities[$css_url]))
			{
				$all_ok = false;
				//create css & get id of css
				if($this->test_mode)
				{
					$output .= '<li>Would have created a css entity for '.$css_url.' and attached it to the theme</li>';
				}
				else
				{
					$css_id = reason_create_entity(id_of('master_admin'), id_of('css'), $user_id, $css_name, $css_info);
					if($css_id)
					{
						$output .= '<li>Created a css entity for '.$css_url.'</li>';
					}
					else
					{
						$output .= '<li>Tried to create a css entity for '.$css_url.' but was unsuccessful. Aborting creation of this css item.</li>';
						continue;
					}
				}
			}
			else
			{
				$css_id = $retrieved_css_entities[$css_url]->id();
				$output .= '<li>A css entity exists for '.$css_url.' at Reason id '.$css_id.'</li>';
			}
			if(!empty($css_id))
			{
				// attach css
				$attached_css = $this->get_css();
				if(empty($attached_css[$css_id]))
				{
					$all_ok = false;
					if($this->test_mode)
					{
						$output .= '<li>Would have attached css at '.$css_url.' to '.$unique_name.'.</li>';
					}
					else
					{
						if($this->attach_css($css_id))
						{
							$output .= '<li>Attached css at '.$css_url.' to '.$unique_name.'.</li>';
						}
						else
						{
							$output .= '<li>Unable to attach css at '.$css_url.' to '.$unique_name.'; perhaps there is an unknown problem.</li>';
						}
					}
				}
				else
				{
					$output .= '<li>Css at '.$css_url.' already attached to '.$unique_name.'</li>';
				}
				$output .= '</ol></li>';
			}
		}
		if($all_ok)
		{
			$output .= '<li><strong>Everything OK.</strong> The theme '.$unique_name.' appears to be set up correctly.  No database changes are needed.</li>';
		}
		$output .= '</ol>';
		return array('success'=>true,'report'=>$output);
	}
}
?>
