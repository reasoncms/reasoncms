<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include the base class & register module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
include_once( DISCO_INC . 'disco.php' );
reason_include_once( 'function_libraries/admin_actions.php' );
reason_include_once( 'classes/inline_editing.php' );
reason_include_once( 'function_libraries/user_functions.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlurbModule';

/**
 * A minisite module that displays text blurbs.
 *s
 * By default, this module displays blurbs attached to the page by relationship order.
 *
 * Via parameters, you can make the module display a set number, randomize, and more.
 */
class BlurbModule extends DefaultMinisiteModule
{
	var $cleanup_rules = array(
		'blurb_id' => array( 'function' => 'turn_into_int' ),
	);
	var $acceptable_params = array(
		'blurb_unique_names_to_show' => '',
		'num_to_display' => '',
		'rand_flag' => false,
		'exclude_shown_blurbs' => true,
		'demote_headings' => 1,
		'source_page' => '',
	);
	var $es;
	var $blurbs = array();
	
	function init( $args = array() ) // {{{
	{
		parent::init( $args );
		if (!empty($this->params['blurb_unique_names_to_show']))
		{
			$this->build_blurbs_array_using_unique_names();
		}
		else
		{
			$this->es = new entity_selector();
			$this->es->description = 'Selcting blurbs for this page';
			$this->es->add_type( id_of('text_blurb') );
			$page_id = $this->get_source_page_id();
			$this->es->add_right_relationship( $page_id, relationship_id_of('minisite_page_to_text_blurb') );
			$this->es->add_rel_sort_field( $page_id, relationship_id_of('minisite_page_to_text_blurb'), 'rel_sort_order');
			if ($this->params['rand_flag']) $this->es->set_order('rand()');
			else $this->es->set_order( 'rel_sort_order ASC' );
			if ($this->params['exclude_shown_blurbs'])
			{
				$already_displayed = $this->used_blurbs();
			}
			if (!empty($already_displayed))
			{
				$this->es->add_relation('entity.id NOT IN ('.join(',',$already_displayed).')');
			}
			if (!empty($this->params['num_to_display'])) $this->es->set_num($this->params['num_to_display']);
			$this->blurbs = $this->es->run_one();
		}
		$this->used_blurbs(array_keys($this->blurbs));
		
		// register myself as editable if there are any blurbs ...
		if (!empty($this->blurbs))
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$inline_edit->register_module($this, $this->user_can_inline_edit());
		}
	} // }}}
	
	protected function get_source_page_id()
	{
		if(!empty($this->params['source_page']))
		{
			if($page_id = id_of($this->params['source_page']))
				return $page_id;
			else
				trigger_error('source_page parameter to blurb module not a valid unique name');
		}
		return $this->cur_page->id();
	}
	
	/**
	 * Determines whether or not the user can inline edit.
	 *
	 * @return boolean;
	 */
	function user_can_inline_edit()
	{
		if (!isset($this->_user_can_inline_edit))
		{
			$this->_user_can_inline_edit = reason_check_access_to_site($this->site_id);
		}
		return $this->_user_can_inline_edit;
	}
	
	function build_blurbs_array_using_unique_names()
	{
		$blurb_array = array();
		$blurb_unique_name_array = (is_array($this->params['blurb_unique_names_to_show'])) 
								   ? $this->params['blurb_unique_names_to_show']
								   : array($this->params['blurb_unique_names_to_show']);
		
		if ($this->params['rand_flag'] == true) shuffle($blurb_unique_name_array);
		
		$max_count = (!empty($this->params['num_to_display'])) ? $this->params['num_to_display'] : count($blurb_unique_name_array);
		$count = 0;
		foreach($blurb_unique_name_array as $blurb_unique_name)
		{
			$blurb_id = id_of($blurb_unique_name, true, false);
			if(!$blurb_id)
			{
				trigger_error('Unable to find blurb with unique name '.$blurb_unique_name);
				continue;
			}
			if ($this->params['exclude_shown_blurbs'])
			{
				if (!isset($used_blurbs)) $used_blurbs = $this->used_blurbs();
				if (in_array($blurb_id, $used_blurbs)) continue; // it has been used do not add it to our array
			}
			$blurb_array[$blurb_id] = new entity($blurb_id);
			$count++;
			if ($count == $max_count) break;
		}
		$this->blurbs = $blurb_array;
	}
	
	function used_blurbs( $used = array() )
	{
		static $used_blurbs = array();
		$used_blurbs = array_merge($used_blurbs, $used);
		return $used_blurbs;
	}
			
	function has_content() // {{{
	{
		$inline_editing =& get_reason_inline_editing($this->page_id);
		if ($inline_editing->available_for_module($this))
			return true;
		elseif( !empty($this->blurbs) )
			return true;
		else
			return false;
	} // }}}
	function run() // {{{
	{
		$inline_editing =& get_reason_inline_editing($this->page_id);
		$editing_available = $inline_editing->available_for_module($this);
		$editing_active = $inline_editing->active_for_module($this);
		echo '<div class="blurbs">'."\n";
		$i = 0;
		foreach( $this->blurbs as $blurb )
		{
			$editable = ( $editing_available && $this->_blurb_is_editable($blurb) );
			$editing_item = ( $editing_available && $editing_active && ($this->request['blurb_id'] == $blurb->id()) );
			$i++;
			echo '<div class="blurb number'.$i;
			if($blurb->get_value('unique_name'))
				echo ' uname_'.htmlspecialchars($blurb->get_value('unique_name'));
			if( $editable )
				echo ' editable';
			if( $editing_item )
				echo ' editing';
			echo '">';
			
			if($editing_item)
			{
				if($pages = $this->_blurb_also_appears_on($blurb))
				{
					$num = count($pages);
					echo '<div class="note"><strong>Note:</strong> Any edits you make here will also change this blurb on the '.$num.' other page'.($num > 1 ? 's' : '').' where it appears.</div>';
				}
				echo $this->_get_editing_form($blurb);
			}
			else
			{
				echo demote_headings($blurb->get_value('content'), $this->params['demote_headings']);
				if( $editable )
				{
					$params = array_merge(array('blurb_id' => $blurb->id()), $inline_editing->get_activation_params($this));
					echo '<div class="edit"><a href="'.carl_make_link($params).'">Edit Blurb</a></div>'."\n";
				}
			}
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
	} // }}}
	
	function _blurb_is_editable($blurb)
	{
		$user = reason_get_current_user_entity();
		if( !empty($user) && get_owner_site_id( $blurb->id() ) == $this->site_id && $blurb->user_can_edit_field('content',$user) )
			return true;
		return false;
	}
	
	function _blurb_also_appears_on($blurb)
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->add_left_relationship( $blurb->id(), relationship_id_of('minisite_page_to_text_blurb') );
		$es->add_relation('entity.id != "'.addslashes($this->page_id).'"');
		return $es->run_one();
	}
	
	function _get_editing_form($blurb)
	{
		$form = new disco();
		$form->strip_tags_from_user_input = true;
		$form->allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;
		$form->add_element( 'blurb_edit_text' , html_editor_name($this->site_id) , html_editor_params($this->site_id, $this->get_html_editor_user_id()) );
		$form->set_display_name('blurb_edit_text',' ');
		$form->set_value( 'blurb_edit_text', $blurb->get_value('content') );
		$form->set_actions(array('save' => 'Save','save_and_finish' => 'Save and Finish Editing',));
		$form->add_callback(array(&$this,'save_blurb_callback'),'process');
		$form->add_callback(array(&$this,'where_to_callback'),'where_to');
		ob_start();
		$form->run();
		$form_output = ob_get_clean();
		return $form_output;
	}	
	
	function save_blurb_callback(&$form)
	{
		$values['content'] = tidy($form->get_value( 'blurb_edit_text' ));
		$archive = ($form->chosen_action == 'save_and_finish') ? true : false;
		reason_update_entity( $this->request['blurb_id'], $this->get_html_editor_user_id(), $values, $archive );
	}
	
	function where_to_callback(&$form)
	{
		if( $form->chosen_action == 'save' )
		{
			return get_current_url();
		}
		else
		{
			$inline_editing =& get_reason_inline_editing($this->page_id);
			$params = array_merge(array('blurb_id' => ''), $inline_editing->get_deactivation_params($this));
			return carl_make_redirect($params);
		}
	}
	
	/**
	 * @return int reason user entity that corresponds to logged in user or 0 if it does not exist
	 */
	function get_html_editor_user_id()
	{
		if ($net_id = reason_check_authentication())
		{
			$reason_id = get_user_id($net_id);
			if (!empty($reason_id)) return $reason_id;
		}
		return 0;
	}
	
	/**
	*  Template calls this function to figure out the most recently last modified item on page
	* This function uses the most recently modified blurb
	* @return mixed last modified value or false
	*/
	function last_modified() // {{{
	{
		if(!empty($this->blurbs))
		{
			$max = '0000-00-00 00:00:00';
			foreach(array_keys($this->blurbs) as $key)
			{
				if($this->blurbs[$key]->get_value('last_modified') > $max)
					$max = $this->blurbs[$key]->get_value('last_modified');
			}
			if($max != '0000-00-00 00:00:00')
				return $max;
		}
		return false;
	} // }}}
	
	/**
	 * Provides (x)HTML documentation of the module
	 * @return mixed null if no documentation available, string if available
	 */
	function get_documentation()
	{
		if(!empty($this->params['num_to_display']))
			$num = $this->params['num_to_display'];
		else
			$num = 'all';
		
		$ret = '<p>Displays '.$num.' blurbs attached to this page';
		if($this->params['rand_flag'])
		{
			$ret .= ', selected at random';
		}
		if($this->params['exclude_shown_blurbs'])
			$ret .= ' (excluding any that have been shown elsewhere on the same page)';
		$ret .= '</p>';
		return $ret;
	}
}
?>
