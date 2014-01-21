<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include various dependencies
 */
include_once('reason_header.php');
include_once(DISCO_INC . 'disco.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/head_items.php');
reason_include_once('classes/object_cache.php');
reason_include_once('function_libraries/user_functions.php');

/**
 * Reason instance-wide find and replace version 1.
 *
 * This is a quickly hacked up utility based mostly on the same code model as the publication migrator. It allows you to select
 * a type, fields from the type, a search term, and a replacement term. Something like this was needed at Carleton to update
 * phone numbers when the 3 digit prefix changes. This code can only be run by someone with database maintenace privileges, and
 * has not been overly scrubbed through to make sure it is secure. 
 *
 * It basically works as follows:
 * 
 * - Screen 1: Allow selection of a type in which to perform a find and replace
 * - Screen 2: Allow selection of fields to consider
 * - Screen 3: Enter find and replace terms
 * - Screen 4: Show all instances with checkboxes as to whether or not to find / replace the instance
 * - Screen 5: Confirm the work was done
 *
 * The script has a somewhat nifty "remember excluded" that uses a cache to remember by type, fields, and search term what was excluded. The memory lasts 
 * for 24 hours but can be cleared manually. It makes it easier to do large operations in blocks without exluding the same items over and over
 * again. Trust me - this is useful.
 *
 * @todo support for multiple types at once
 * @todo security enhancements - turn_into_string and turn_into_array are not sufficient cleanup rules for real user input filtering
 * @todo add case-sensitive/case-insensitive toggler in UI and support both
 * 
 * @version .2.0 - September 6, 2013
 * @author Nathan White
 * @author Matt Ryan
 */
class FindReplaceWizard extends Disco
{
	var $helper;
	
	function init( $args = array() )
	{
		$this->step_init();
		parent::init(true);
		ini_set('memory_limit', '256M');
	}
	
	function step_init()
	{
	}
	
	function show_memory()
	{
		if ($this->helper->get_type_id() && $this->helper->get_search_term())
		{
			$excluded = $this->helper->get_excluded();
			$excluded_string = (!empty($excluded)) ? implode(",", $excluded) : false;
			echo '<div id="excluded" style="float:right; border:1px solid #000; padding: 10px;">';
			echo '<h4>Excluded IDs</h4>';
			if ($excluded_string)
			{
				echo '<p>'.$excluded_string.'</p>';
				$clear_link = carl_make_link(array('clear_exclude' => "true"));
				echo '<p><a href="'.$clear_link.'">Clear excluded ids</a></p>';
			}
			else
			{
				echo '<p>No ids currently excluded for this type and set of fields.</p>';
			}
			echo '</div>';
		}
	}
	
	function pre_show_form()
	{
		echo '<h3>Find Replace Wizard</h3>';
		$this->step_pre_show_form();
	}
	
	function step_pre_show_form()
	{
	}
	
	function where_to()
	{
		$values =& $this->get_values_to_pass();
		return carl_make_redirect($values);
	}
	
	function redirect_to_screen($screen)
	{
		$redirect = carl_make_redirect(array('active_screen' => $screen));
		header("Location: " . $redirect );
		exit;
	}
	
	function show_completed_list()
	{
		$choose_another_term = carl_construct_link(array('type_id' => $this->helper->get_type_id(), 'site_id' => $this->helper->get_site_id(), 'active_screen' => "3"), array('type_fields'));
		$start_over = carl_construct_link(array(''));
		$find_more = carl_construct_link(array('type_id' => $this->helper->get_type_id(),
											   'site_id' => $this->helper->get_site_id(),
											   'type_fields' => $this->helper->get_type_fields(),
											   'limit' => $this->helper->get_limit(),
											   'active_screen' => "3",
											   'find' => $this->helper->get_search_term(),
											   'replace' => $this->helper->get_replace_term()));
		echo '<ul>';
		echo '<li><a href="'.$find_more.'">Find more</a> for these fields and terms</li>';
		echo '<li><a href="'.$choose_another_term.'">Choose another search term</a> with these fields to find and replace</li>';
		echo '<li><a href="'.$start_over.'">Start over</a></li>';
		echo '</ul>';
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => '');
		return $values;
	}
}

class FindReplaceWizard1 extends FindReplaceWizard
{
	var $actions = array('Continue');
	var $site_names_by_id;
	
	function step_init()
	{
		$this->type_names_by_id = $this->helper->get_type_names_by_id();
		$this->site_names_by_id = $this->helper->get_site_names_by_id();
	}
	
	function on_every_time()
	{
		$this->add_element('active_screen', 'hidden');
		$this->set_value('active_screen', 1);		
		$this->add_element('type_id', 'select_no_sort', array('options' => $this->type_names_by_id, 'display_name' => 'Choose a Type'));	
		$this->add_element('site_id', 'select_no_sort', array('options' => $this->site_names_by_id, 'display_name' => 'Choose a Site', 'add_empty_value_to_top' => true));
	}
	
	function step_pre_show_form()
	{
		echo '<h4>Step 1 - Select a Type</h4>';
	}
	
	function &get_values_to_pass()
	{
		$values = array(
			'active_screen' => "2",
			'type_id' => $this->get_value('type_id'),
			'site_id' => $this->get_value('site_id'),
		);
		return $values;
	}
}

class FindReplaceWizard2 extends FindReplaceWizard
{
	function step_pre_show_form()
	{
		echo '<h4>Step 2 - Choose Fields</h4>';
	}
	
	function step_init()
	{
		if (!($this->helper->get_type_id())) $this->redirect_to_screen("1");
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		$options =& $this->helper->get_fields_for_type();
		$this->add_element('type_fields', 'checkboxgroup', array('options' => $options, 'display_name' => 'Choose Fields to Search Across'));
		$this->set_value('type_fields', array_keys($options)); // check all by default - should exclude ID!
	}
	
	function run_error_checks()
	{
		if (!$this->get_value('type_fields'))
		{
			$this->set_error('type_fields', 'You must select at least one field in the type to search across.');
		}
	}
	
	// we'll jump to next screen in case there are others to associate ... if finished, the init will bounce us on to the next phase
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "3", 'type_id' => $this->helper->get_type_id(), 'type_fields' => $this->get_value('type_fields'));
		return $values;
	}
}

class FindReplaceWizard3 extends FindReplaceWizard
{
	function step_init()
	{
		if (!$this->helper->get_type_id()) $this->redirect_to_screen("1");
		elseif (!$this->helper->get_type_fields()) $this->redirect_to_screen("2");
	}
	
	function step_pre_show_form()
	{
		echo '<h4>Step 3 - Search and Replace Terms</h4>';
	}

	function on_every_time()
	{
		$this->add_element('find', 'text', array('display_name' => 'Search Term'));
		$this->add_required('find');
		$this->add_element('replace', 'text', array('display_name' => 'Replacement Term'));
		$this->add_element('limit', 'select_no_sort', array('options' => array('10' => '10','50' => '50', '100' => '100', '500' => '500'),
															'add_null_value_to_top' => true));
		
		if ($this->helper->get_search_term()) $this->set_value('find', $this->helper->get_search_term());
		if ($this->helper->get_replace_term()) $this->set_value('replace', $this->helper->get_replace_term());
		if ($this->helper->get_limit()) $this->set_value('limit', $this->helper->get_limit());
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "4", 
						'type_id' => $this->helper->get_type_id(), 
						'site_id' => $this->helper->get_site_id(), 
						'type_fields' => $this->helper->get_type_fields(), 
						'find' => $this->get_value('find'),
						'replace' => $this->get_value('replace'),
						'limit' => $this->get_value('limit'));
		return $values;
	}
}

// Notice a pattern? This could be engineered better since these all built on the previous but this is a quick and dirty job ...

class FindReplaceWizard4 extends FindReplaceWizard
{
	var $num_changes = 0;
	
	function step_init()
	{
		if (!$this->helper->get_type_id()) $this->redirect_to_screen("1");
		elseif (!$this->helper->get_type_fields()) $this->redirect_to_screen("2");
		elseif (!$this->helper->get_search_term()) $this->redirect_to_screen("3");
		$matches =& $this->helper->get_matches();
		$this->show_memory();
		if (!$matches)
		{
			$this->show_form = false;
			echo '<h3>No matches were found</h3>';
			$this->show_completed_list();
		}
	}
	
	function step_pre_show_form()	
	{
		$matches =& $this->helper->get_matches();
		$count = count($matches);
		echo '<h3>Step 4 - Confirm Replacements</h3>';
		echo '<p>The table below shows ' . $count . ' instance(s) of the term <strong>"'.htmlspecialchars($this->helper->get_search_term()).'"</strong> that will be replaced with <strong>"'.htmlspecialchars($this->helper->get_replace_term()).'"</strong>.</p>';
		echo '<p>Uncheck anything you do not want replaced and proceed.</p>';	  
	}
	
	function on_every_time()
	{
		$matches =& $this->helper->get_matches();
		$search_term = $this->helper->get_search_term();
		$replace_term = $this->helper->get_replace_term();
		// each option should contain the field - the value is the table that outlines all fields and replacements
		
		if ($matches)
		{
			foreach ($matches as $id => $e)
			{
				$type_fields_keys = array_flip($this->helper->get_type_fields());
				//pray(array_diff( array_keys($e->get_values()),array_keys( $type_fields_keys ) ) );
				foreach($e->get_values() as $key=>$value)
				{
					$encoded_value = htmlspecialchars($value, ENT_QUOTES);
					$encoded_search_term = htmlspecialchars($search_term);
					$encoded_replace_term = htmlspecialchars($replace_term);
					if (isset($type_fields_keys[$key]))
					{
						if(strstr($value,$search_term))
						{
							$search_value = str_replace($encoded_search_term,'<span style="font-weight: bold; color: red;">'.$encoded_search_term.'</span>',$encoded_value);			
							$replace_value = str_replace($encoded_search_term, '<span style="font-weight: bold; color: red;">'.$encoded_replace_term.'</span>',$encoded_value);
							$option_info[$id.'|'.$key] = array ('id' => $id, 'values' => array('Field' => $key, 'Search' => $search_value, 'Replace' => $replace_value));
							$options[$id.'|'.$key] = $id.'|'.$key;
						}
					}
				}	
			}
			if(empty($options))
				pray($e->get_values());
			$this->add_element('replace_list', 'confirmFindReplace', array('options' => $options,'option_info'=>$option_info));
			$replace_list = $this->get_element('replace_list');
		
			$this->set_value('replace_list', array_keys($options)); // check all by default - should exclude ID!
			foreach ($option_info as $k=>$v)
			{
				$this->add_element('original_list['.$k.']', 'hidden');
				$this->set_value('original_list['.$k.']', $v['id']);
			}
		}
	}
	
	function process()
	{
		$replace_list = $this->helper->get_replace_list();
		$original_list = array_flip(array_unique($this->helper->get_original_list())); // all ids
		$search_term = $this->helper->get_search_term();
		$replace_term = $this->helper->get_replace_term();
		$user_id = $this->helper->get_user_id();
		
		// lets build the replacement arrays per entity
		if (!empty($replace_list))
		{
			foreach ($replace_list as $id_and_field)
			{
				$parsed = explode("|", $id_and_field);
				if (isset($parsed[0]) && isset($parsed[1]))
				{
					$entity = new entity($parsed[0]);
					$replace[$parsed[0]][$parsed[1]] = str_replace($search_term, $replace_term, $entity->get_value($parsed[1]));
				}
			}
		}
		if (isset($replace) && !empty($replace))
		{
			foreach ($replace as $eid => $values)
			{
				reason_update_entity($eid, $user_id, $values, false); // we are not archiving ... maybe we should - nah
				if (isset($original_list[$eid])) unset($original_list[$eid]);
				$this->num_changes++;
			}
		}
		
		if (!empty($original_list))
		{
			$this->helper->add_to_excluded($original_list);
		}
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "5", 
						'type_id' => $this->helper->get_type_id(), 
						'site_id' => $this->helper->get_site_id(), 
						'type_fields' => $this->helper->get_type_fields(), 
						'find' => $this->helper->get_search_term(),
						'replace' => $this->helper->get_replace_term(),
						'changes' => $this->num_changes);
		return $values;
	}
}

class FindReplaceWizard5 extends FindReplaceWizard
{
	function step_init()
	{
		if (!$this->helper->get_type_id()) $this->redirect_to_screen("1");
		elseif (!$this->helper->get_type_fields()) $this->redirect_to_screen("2");
		elseif (!$this->helper->get_search_term()) $this->redirect_to_screen("3");
		elseif (!$this->helper->get_changes()) $this->redirect_to_screen("4");
		
		echo '<h3>Complete</h3>';
		$num_changes = $this->helper->get_changes();
		echo '<p>Completed ' . $num_changes . ' change(s).</p>';
		$this->show_completed_list();
		$this->show_form = false;
	}
}

/**
	* Display a confirmation table with a checkbox for each row
	*
	* @package disco
	* @subpackage plasmature
	*/
	class confirmFindReplaceType extends checkboxgroupType // {{{
	{
		var $type = 'checkboxgroup';
		var $use_display_name = false;
		var $type_valid_args = array('option_info',);
		protected $option_info = array();
		
		function do_includes()
		{
			include_once(CARL_UTIL_INC . 'db/table_admin.php');
		}
		
		function get_display() // {{{ // lets use table admin to display this guy
		{
			$i = 0;
			foreach ($this->options as $k => $val)
			{
				if(!isset($this->option_info[$k]))
				{
					trigger_error('Please set option_info for each option!');
					continue;
				}
				// build our checkbox
				//$store_key[$key] = "ture";
				$val = $this->option_info[$k]['values'];
				$id = $this->option_info[$k]['id'];
				$checkbox = '<input type="checkbox" id="'.$this->name.$i.'" name="'.$this->name.'[]" value="'.htmlspecialchars($k,ENT_QUOTES).'"';
				if ( in_array($k,$this->value) ) $checkbox .= ' checked="checked"';
				//if ( $this->_is_current_value($k) ) $checkbox .= ' checked="checked"';
				//if ( $this->_is_disabled_option($k) ) $checkbox .= ' disabled="disabled"';
				$checkbox .= ' /> ' . $id;
				$e = new entity($id);
				$checkbox .= '<p class="name">Name: '.$e->get_value('name').'</p>';
				$owner = get_owner_site_id($id);
				if($owner)
				{
					$site = new entity($owner);
					$checkbox .= '<p class="site">Site: '.$site->get_value('name').'</p>';
				}
				else
					$checkbox .= '<p class="site">(No owner site)</p>';
				$data[] = array('Find and Replace?' => $checkbox) + $val;
				$i++;
			}
			
			// data now contains the raw data needed for our table admin work;
			$entity_convert_fields = array_keys(reset($this->option_info));
			
			$ta = new TableAdmin();
			$ta->init_from_array($data);
			$ta->set_fields_that_allow_sorting(array());
			$ta->set_fields_to_entity_convert(array()); // we are using html in our fields
		 	
		 	ob_start();
		 	$ta->run();
		 	$str = ob_get_contents();
		 	ob_end_clean();
		 	return $str;
		} // }}}

		function get_cleanup_rule()
		{
			return array( 'function' => 'turn_into_array' );
		}
	} // }}}
	
class FindReplaceWizardHelper
{
	var $cleanup_rules = array('active_screen' => array('function' => 'check_against_array', 'extra_args' => array("1","2","3","4","5")),
							   'type_id' => array('function' => 'turn_into_int'),
							   'site_id' => array('function' => 'turn_into_int'),
							   'changes' => array('function' => 'turn_into_int'),
							   'type_fields' => array('function' => 'turn_into_array'),
							   'replace_list' => array('function' => 'turn_into_array'),
							   'original_list' => array('function' => 'turn_into_array'),
							   'find' => array('function' => 'turn_into_string'), // maybe a safechars check?
							   'replace' => array('function' => 'turn_into_string'),
							   'limit' => array('function' => 'turn_into_int'),
							   'clear_exclude' => array('function' => 'check_against_array', 'extra_args' => array('true')));
	
	var $fields_to_exclude = array('id', 'unique_name', 'type');
	
	/**
	 * Determine state and init the appropriate find replace wizard screen
	 */
	function init()
	{
		$this->request = carl_clean_vars($_REQUEST, $this->cleanup_rules);
		if (isset($this->request['clear_exclude']))
		{
			$this->clear_excluded();
			$redirect = carl_make_redirect(array('clear_exclude' => ''));
			header("Location:" . $redirect);
			exit;
		}
	}
	
	function get_replace_list()
	{
		return (isset($this->request['replace_list'])) ? $this->request['replace_list'] : false;
	}
	
	function get_original_list()
	{
		return (isset($this->request['original_list'])) ? $this->request['original_list'] : false;
	}
	
	function get_type_id()
	{
		return (isset($this->request['type_id'])) ? $this->request['type_id'] : false;
	}
	
	function get_type_fields()
	{
		return (isset($this->request['type_fields'])) ? $this->request['type_fields'] : false;
	}
	
	function get_search_term()
	{
		return (isset($this->request['find'])) ? $this->request['find'] : false;
	}
	
	function get_changes()
	{
		return (isset($this->request['changes'])) ? $this->request['changes'] : false;
	}
	
	function get_search_term_for_query()
	{
		return str_replace('_','\_', addslashes($this->get_search_term()));
	}
	
	function get_replace_term()
	{
		return (isset($this->request['replace'])) ? $this->request['replace'] : false;
	}
	
	function get_site_id()
	{
		return (isset($this->request['site_id'])) ? $this->request['site_id'] : false;
	}
	
	function get_limit()
	{
		return (isset($this->request['limit'])) ? $this->request['limit'] : false;
	}
	
	function get_user_id()
	{
		static $user_id;
		if (!isset($user_id))
		{
			$user_netid = reason_require_authentication();
			$user_id = get_user_id($user_netid);
		}
		return $user_id;
	}
	/**
 	 * @todo ensure that the collation works if you have your columns set up to use utf-8 character sets
	 */
	function &get_matches()
	{
		if (!isset($this->_matches))
		{
			$limit = $this->get_limit();
			$type_id = $this->get_type_id();
			$tables = get_entity_tables_by_type( $type_id );
			$table_array[] = 'entity';
			if($site_id = $this->get_site_id())
				$es = new entity_selector($site_id);
			else
				$es = new entity_selector();
			$es->add_type( $type_id );
			if (!empty($limit)) $es->set_num($limit);
			$relation_pieces = array();
			foreach($tables as $table)
			{
				$fields = array_intersect(get_fields_by_content_table( $table ), $this->get_type_fields());
				if ($fields)
				{
					$table_array[] = $table;
					foreach($fields as $field)
					{
						$relation_pieces[] = $table.'.'.$field.' LIKE "%'.$this->get_search_term_for_query().'%"';
					}
				}
			}
			$es->limit_tables(array_unique($table_array));
			$es->add_relation('( '.implode(' OR ',$relation_pieces).' )');
			if ($excluded_array = $this->get_excluded())
			{
				$es->add_relation('entity.id NOT IN (' . implode(",", $excluded_array) .')');
			}
			$this->_matches = $es->run_one();
		}
		return $this->_matches;
	}
	
	function &get_types()
	{	
		if (!isset($this->_types))
		{
			$es = new entity_selector();
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$es->add_type(id_of('type'));
			$es->set_order('entity.name ASC');
			$this->_types = $es->run_one();
		}
		return $this->_types;
	}
	function &get_sites()
	{	
		if (!isset($this->_sites))
		{
			$es = new entity_selector();
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$es->add_type(id_of('site'));
			$es->set_order('entity.name ASC');
			$this->_sites = $es->run_one();
		}
		return $this->_sites;
	}

	function &get_type_names_by_id()
	{
		if (!isset($this->_type_names_by_id))
		{
			$types =& $this->get_types();
			if (!empty($types))
			{
				foreach ($types as $k=>$v)
				{
					$this->_type_names_by_id[$k] = $v->get_value('name');
				}
			}
			else $this->_type_names_by_id = array();
		}
		return $this->_type_names_by_id;
	}
	
	function &get_type_name()
	{
		$type_id = $this->get_type_id();
		$type_names =& $this->get_type_names_by_id();
		return $type_names[$type_id];
	}

	function &get_site_names_by_id()
	{
		if (!isset($this->_site_names_by_id))
		{
			$sites =& $this->get_sites();
			if (!empty($sites))
			{
				foreach ($sites as $k=>$v)
				{
					$this->_sites_names_by_id[$k] = $v->get_value('name');
				}
			}
			else $this->_sites_names_by_id = array();
		}
		return $this->_sites_names_by_id;
	}
	
	function &get_fields_for_type()
	{
		if (!isset($this->_fields_for_type))
		{
			$type_id = $this->get_type_id();
			$this->_fields_for_type = ($this->get_fields_to_exclude()) 
									  ? array_diff(get_fields_by_type($type_id), $this->get_fields_to_exclude()) 
									  : get_fields_by_type($type_id);
		}
		return $this->_fields_for_type;
	}
	
	function &get_fields_to_exclude()
	{
		return $this->fields_to_exclude;
	}
	
	/**
	 * @return object disco form
	 */
	function &get_form()
	{
		$active_form_num = (isset($this->request['active_screen'])) ? $this->request['active_screen'] : "1";
		$form_name = "FindReplaceWizard" . $active_form_num;
		$form = new $form_name;
		$form->helper = $this;
		return $form;
	}
	
	// excluded cache methods
	function clear_excluded()
	{
		$id = $this->_get_cache_id();
		$cache = new ReasonObjectCache($id);
		$cache->clear();
	}
	
	function get_excluded()
	{
		$id = $this->_get_cache_id();
		$cache = new ReasonObjectCache($id);
		return $cache->fetch();
	}
	
	function set_excluded($excluded_array)
	{
		$id = $this->_get_cache_id();
		$cache = new ReasonObjectCache($id);
		return $cache->set($excluded_array);
	}
	
	function add_to_excluded($to_exclude_array)
	{
		$exclude_ids = array_keys($to_exclude_array);
		$excluded = $this->get_excluded();
		foreach ($exclude_ids as $id => $exclude_id)
		{
			if (!$excluded || !isset($excluded[$exclude_id])) $excluded[$exclude_id] = $exclude_id;
		}
		$this->set_excluded($excluded);
	}
	
	/**
	 * Returns a cache id according to the type_id, search_term, and type fields
	 */
	function _get_cache_id()
	{
		$type_id = $this->get_type_id();
		$search_term = $this->get_search_term();
		$type_fields = $this->get_type_fields();
		if ($type_id && $search_term && $type_fields)
		{
			$type_fields_str = implode("-", $type_fields);
			$cache_id = md5($type_id . '_' . $search_term . '_' . $type_fields_str);
		}
		return (isset($cache_id)) ? $cache_id : false;
	}
}

// instantiate relevant classes
$head_items = new HeadItems();
$frwh = new FindReplaceWizardHelper();
// add needed head items
$head_items->add_head_item('meta',array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8'));
$head_items->add_head_item('title',array(),'Find / Replace Wizard',true);
$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
$html = '<!DOCTYPE html>'."\n";
$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
$html .= '<head>'."\n";
$html .= $head_items->get_head_item_markup();
$html .= '</head>'."\n";
$html .= '<body>'."\n";

reason_require_authentication();

if (!reason_check_privs('db_maintenance'))
{
	$html .= '<h3>Unauthorized</h3><p>You must have database maintenance privileges to use this tool.</p>';
}
else
{
	
	$frwh->init();
	$form =& $frwh->get_form();
	ob_start();
	$form->run();
	$html .= ob_get_contents();
	ob_end_clean();
}


$html .= '</body>';
$html .= '</html>';
echo $html;
?>
