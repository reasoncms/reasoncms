<?php
/**
 * Catalog: Subject Page
 *
 * Module for displaying and front-end editing a subject page for a course catalog
 *
 * @author Mark Heiman
 * @since 2014-08-20
 * @package MinisiteModule
 *
 *
 */
$GLOBALS[ '_module_class_names' ][ 'catalog/'.basename( __FILE__, '.php' ) ] = 'CatalogSubjectPageModule';

reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/course_functions.php' );
reason_include_once( 'classes/admin/rel_sort.php' );

class CatalogSubjectPageModule extends DefaultMinisiteModule
{
	public $cleanup_rules = array(
		'block_id' => array( 'function' => 'turn_into_int' ),
		'block_move' => array( 'function' => 'turn_into_string' ),
		'block_row' => array( 'function' => 'turn_into_int' ),
		'block_delete' => array( 'function' => 'turn_into_int' ),
		'module_api' => array( 'function' => 'turn_into_string' ),
		'module_identifier' => array( 'function' => 'turn_into_string' ),
		'get_course' => array( 'function' => 'turn_into_string' ),
		);

	protected $courses = array();
	protected $year = 2015;
	protected $helper;

	public function init( $args = array() )
	{
		parent::init($args);

		if (preg_match('/\d{4}/', unique_name_of($this->site_id), $matches))
			$this->year = (int) $matches[0];

		$this->helper = new $GLOBALS['catalog_helper_class']($this->year);

		// If we're in ajax mode, we just return the data and quit the module.
		$api = $this->get_api();
		if ($api && ($api->get_name() == 'standalone'))
		{
			// This call is used to provide course descriptions that pop up when course
			// codes are clicked on.
			if (isset($this->request['get_course']))
			{
				$this->handle_ajax_course_request();
			}
		}

		// If a request has been made to move a block, do the move and reload
		if (!empty($this->request['block_move']) && !empty($this->request['block_id']))
		{
			$this->move_block($this->request['block_id'], $this->request['block_row'], $this->request['block_move']);
			header( 'Location: '. carl_make_link(array('block_id'=>null,'block_row'=>null,'block_move'=>null)));
			exit;
		}

		// If a request has been made to delete a block, do the delete and reload
		if (!empty($this->request['block_delete']) && !empty($this->request['block_id']))
		{
			$this->delete_block($this->request['block_id']);
			header( 'Location: '. carl_make_link(array('block_id'=>null,'block_delete'=>null)));
			exit;
		}

		if($head_items = $this->get_head_items())
		{
			$head_items->add_stylesheet(JQUERY_UI_CSS_URL);
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/courses/course_description_modal.css');
			$head_items->add_javascript(JQUERY_UI_URL);
			$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/courses/course_description_modal.js');
			$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js');
		}

		$this->get_catalog_blocks();

		// register myself as editable
		$inline_edit =& get_reason_inline_editing($this->page_id);
		$inline_edit->register_module($this, $this->user_can_inline_edit());
	}

	public function run()
	{
		echo '<div id="subjectPageModule" class="reason_course_modals '.$this->get_api_class_string().'" year="'.$this->year.'">'."\n";

		$inline_editing =& get_reason_inline_editing($this->page_id);
		$editing_available = $inline_editing->available_for_module($this);
		$editing_active = $inline_editing->active_for_module($this);

		// Display each block (or if editing, show the edit form)
		$row = 1;
		foreach ($this->blocks as $block)
		{
			echo '<a name="block'.$row.'"></a>'."\n";

			$editable = ( $editing_available && $this->_block_is_editable($block) );
			$editing_item = ( $editing_available && $editing_active && ($this->request['block_id'] == $block->id()) );

			$classes = array('catalog_block');
			if( $editable ) $classes[] = 'editable';
			if( $editing_item ) $classes[] = 'editing';
			echo '<div class="'.join(' ', $classes).'">'."\n";

			if($editing_item)
			{
				echo $this->get_editing_form($block);
			}
			else
			{
				if( $editable ) echo '<div class="editRegion">'."\n";

				if ($title = $block->get_value('title'))
					echo '<h3>'.$title.'</h3>'."\n";
				echo $this->helper->expand_catalog_tags($block->get_value('content'))."\n";

				// Add editing options
				if( $editable )
				{
					$params = array_merge(array('block_id' => $block->id()), $inline_editing->get_activation_params($this));
					echo '<p class="edit"><a class="editThis" href="'.carl_make_link($params).'#block'.$row.'">Edit Block</a> ';
					$params = array('block_id' => $block->id(),'block_row' => $row, 'block_move' => 'up');
					if ($row != 1)
						echo ' <a class="editThis" href="'.carl_make_link($params).'">Move Block Up</a>';
					$params = array('block_id' => $block->id(),'block_delete' => 1);
					echo ' <a class="editThis" href="'.carl_make_link($params).'">Delete Block</a>';
					echo '</p>'."\n";
					echo '</div>'."\n";
				}
			}
			echo '</div>'."\n";
			$row++;
		}

		// If in editing mode, add an additional region where a new block may be added.
		if( $editing_available )
		{
			echo '<a name="block0"></a><div class="editable">'."\n";
			if ( $editing_active && ($this->request['block_id'] === 0) )
			{
				echo $this->get_editing_form();
			}
			else
			{
				echo '<div class="editRegion">'."\n";
				$params = array_merge(array('block_id' => 0), $inline_editing->get_activation_params($this));
				echo '<p class="edit"><a class="editThis" href="'.carl_make_link($params).'#block0">Add a Block</a></p>';
				echo '</div>'."\n";
			}
			echo '</div>'."\n";
		}

		echo '</div>'."\n";
	}

	/**
	  * Determine whether the current user can edit the requested block.
	  *
	  * @param object $block
	  */
	function _block_is_editable($block)
	{
		$user = reason_get_current_user_entity();
		if( !empty($user) && get_owner_site_id( $block->id() ) == $this->site_id && $block->user_can_edit_field('content',$user) )
			return true;
		return false;
	}

	/**
	  * Change the sort order of a given block
	  *
	  * @param integer $id ID of block to be moved
	  * @param integer $row_id Current position of block in list
	  * @param string $direction Direction to move (up, down)
	  */
	protected function move_block($id, $row_id, $direction)
	{
		$sort = new RelationshipSort();
		$sort->init($this->site_id,
					relationship_id_of('page_to_course_catalog_block'),
					$this->page_id,
					$id,
					$row_id,
					'move'.$direction,
					reason_check_authentication(),
					'no');
		if ($sort->validate_request()) $sort->run();
	}

	/**
	  * Delete a given block if the user has permission. Doesn't expunge the entity, just
	  * changes its state and disconnects it from the page, so it can be recovered from
	  * the Reason admin.
	  *
	  *	@param integer $id ID of block to be deleted
	  */
	protected function delete_block($id)
	{
		$block = new entity($id);
		if ($this->_block_is_editable($block))
		{
			reason_update_entity( $id, reason_get_current_user_entity(), array('state'=>'deleted'), false);
			delete_relationships_by_entities($id, $this->page_id);
		}
	}

	/**
	  * Get the block entities attached to this page.
	  * Sets $this->blocks to an array of objects
	  */
	protected function get_catalog_blocks()
	{
		$this->es = new entity_selector();
		$this->es->description = 'Selecting catalog blocks for this page';
		$this->es->add_type( id_of('course_catalog_block_type') );
		$page_id = $this->cur_page->id();
		$this->es->add_right_relationship( $page_id, relationship_id_of('page_to_course_catalog_block') );
		$this->es->add_rel_sort_field( $page_id, relationship_id_of('page_to_course_catalog_block'), 'rel_sort_order');
		$this->es->set_order( 'rel_sort_order ASC' );
		$this->blocks = $this->es->run_one();
	}

	/**
	 * Determines whether or not the user can inline edit.
	 *
	 * @return boolean;
	 */
	protected function user_can_inline_edit()
	{
		if (!isset($this->_user_can_inline_edit))
		{
			$this->_user_can_inline_edit = reason_check_access_to_site($this->site_id);
		}
		return $this->_user_can_inline_edit;
	}

	/**
	 * Generates the editing interface for a catalog block.
	 *
	 * @param object $block Optional catalog_block entity
	 * @return string
	 */
	protected function get_editing_form($block = null)
	{
		$form = new disco();
		$form->strip_tags_from_user_input = true;
		$form->allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;

		$form->add_element( 'block_edit_title', 'text', array('display_name' => 'Block Title'));
		$value = ($block) ? $block->get_value('title') : null;
		$form->set_value( 'block_edit_title', $value );

		// Get the plasmature options for the block type field from the db definition
		$fields = get_fields_by_type(id_of('course_catalog_block_type'), true);
		list($block_type_type, $block_type_options) =  $form->plasmature_type_from_db_type('block_type', $fields['block_type']['Type']);
		$form->add_element( 'block_edit_type', $block_type_type, $block_type_options);
		$form->set_display_name('block_edit_type','Type');
		$value = ($block) ? $block->get_value('block_type') : null;
		$form->set_value( 'block_edit_type', $value );

		// If we're adding a new block, we need them to identify the subject
		if ($this->request['block_id'] === 0)
		{
			$subjects = $this->helper->get_course_subjects();
			$form->add_element( 'block_edit_org_id' , 'select', array('options' => $subjects) );
			$form->set_display_name('block_edit_org_id','Subject');
		}

		$form->add_element( 'block_edit_text' , html_editor_name($this->site_id) , html_editor_params($this->site_id, $this->get_html_editor_user_id()) );
		$form->set_display_name('block_edit_text','&nbsp;');
		$value = ($block) ? $block->get_value('content') : null;
		$form->set_value( 'block_edit_text', $value );

		$form->add_required('block_edit_type');
		$form->set_actions(array('save' => 'Save','save_and_finish' => 'Save and Finish Editing',));
		$form->add_callback(array(&$this,'save_block_callback'),'process');
		$form->add_callback(array(&$this,'where_to_callback'),'where_to');
		ob_start();
		$form->run();
		$form_output = ob_get_clean();
		return $form_output;
	}

	/**
	 * Actions to take when the form process phase is invoked.
	 *
	 * @param object $form Block editing Disco object
	 */
	public function save_block_callback(&$form)
	{
		if ($this->request['block_id'] === 0)
			$values['org_id'] = tidy($form->get_value( 'block_edit_org_id' ));

		$values['title'] = tidy($form->get_value( 'block_edit_title' ));
		$values['block_type'] = tidy($form->get_value( 'block_edit_type' ));
		$values['content'] = tidy($form->get_value( 'block_edit_text' ));
		$archive = ($form->chosen_action == 'save_and_finish') ? true : false;

		if ($this->request['block_id'] === 0)
		{
			$name = $values['org_id'] . ' ' . $values['block_type'];
			$block_id = reason_create_entity( $this->site_id, id_of('course_catalog_block_type'), $this->get_html_editor_user_id(), $name, $values);
			create_relationship( $this->page_id, $block_id, relationship_id_of('page_to_course_catalog_block'), array('rel_sort_order'=>count($this->blocks) + 1), false);
		}
		else
			reason_update_entity( $this->request['block_id'], $this->get_html_editor_user_id(), $values, $archive );
	}

	/**
	 * Actions to take when the form where_to phase is invoked.
	 *
	 * @param object $form Block editing Disco object
	 */
	public function where_to_callback(&$form)
	{
		if( $form->chosen_action == 'save' )
		{
			return get_current_url();
		}
		else
		{
			$inline_editing =& get_reason_inline_editing($this->page_id);
			$params = array_merge(array('block_id' => null), $inline_editing->get_deactivation_params($this));
			return carl_make_redirect($params);
		}
	}

	/**
	 * @return int reason user entity that corresponds to logged in user or 0 if it does not exist
	 */
	protected function get_html_editor_user_id()
	{
		if ($net_id = reason_check_authentication())
		{
			$reason_id = get_user_id($net_id);
			if (!empty($reason_id)) return $reason_id;
		}
		return 0;
	}

	/**
	 * This method handles ajax requests for course information. The course identifier comes in
	 * through the get_course parameter, and can have two formats:
	 *
	 * course_NNNNNN_NNNN
	 *   where the first number is a course object id and the second is the catalog year
	 *
	 * SUBJ_NNN_NNN
	 *   where SUBJ is an org_id, the first number is a course_number, and the second is the year.
	 *
	 * The method outputs information about the requested course in JSON array(title, description)
	 * and exits.
	 */
	protected function handle_ajax_course_request()
	{
		list($label,$number,$this->year) = explode('_', $this->request['get_course']);
		if ($label == 'course')
		{
			$course_id = $number;
		}
		else if ($courses = $this->helper->get_courses_by_subject_and_number($label, $number, $this->site_id))
		{
			// @todo Handle mutiple matches
			$course = reset($courses);
			$course_id = $course->id();
		}

		if (!empty($course_id) && $course = new $GLOBALS['course_template_class']($course_id))
		{
			$course->set_academic_year_limit($this->year);
			$description_title = '<span class="courseTitle">'.$course->get_value('title').'</span> ';
			echo json_encode(array(
				'title'=>$course->get_value('org_id').' '.$course->get_value('course_number'),
				'description'=>$description_title . $this->helper->get_course_extended_description($course)
				));
		}
		else
		{
			echo json_encode(array(
				'title'=>'Course not found',
				'description'=>''
				));
		}
		exit;
	}

}
