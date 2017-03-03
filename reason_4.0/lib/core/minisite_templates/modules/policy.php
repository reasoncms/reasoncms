<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Register module with Reason and include dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'classes/group_helper.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PolicyModule';

	/**
	 * A minisite module that displays the policies on the current site
	 *
	 * Note: Policies in this context refers to Reason entities of the type Policy, e.g. a nice way to manage the organization's
	 * rules and regulations. This does not refer to any internal-to-reason rules enforced by machine code.
	 */
	class PolicyModule extends DefaultMinisiteModule
	{
		// in general, edit_id refers to the policy id for inline editing. But, there are some magic values
		// that are used for things like triggering metadata (approvals, last revised date, audiences) and
		// the "I have reviewed this policy" special form.
		const CUSTOM_EDIT_ID_METADATA = '-1';
		const CUSTOM_EDIT_ID_REVIEWED_DATE = '-2';

		// navigation anchors
		const ANCHOR_LAST_REVIEWED = "lastReviewedSection";

		// disco form actions
		const ACTION_CANCEL = "cancel";
		const ACTION_DEBUG = "debug";
		const ACTION_MARK_POLICY_REVIEWED = "mark_policy_as_reviewed";

		const UNSET_DATE = "never";

		var $cleanup_rules = array(
			'policy_id' => array('function' => 'turn_into_int'),
			'audience_id' => array('function' => 'turn_into_int'),
			'a' => array('function' => 'turn_into_string'),
			'show_all' => array( 'function' => 'check_against_array', 
								 'extra_args' => array( 'true', 'false' ) ),
			'edit_id' => array('function' => 'turn_into_int'),
		);
		/**
		 * Parameters this module can take in its page type definition
		 *
		 * audience_aliases allows you to have nicer URLs. Provide key-value pairs where the key is
		 * the slug and the value is the unique name of the audience.
		 */
		var $acceptable_params = array(
			'audience_aliases' => array(),
		);
		protected $_access_results = array();
		protected $values;
		protected $roots;
		protected $policy;
		protected $is_root;
		protected $related_audiences;
		protected $all_audiences;
		protected $edit_policy;
		protected $audience_opts;
		protected $current_audience;


		function init( $args = array() ) // {{{
		{
			parent::init( $args );
			
			// REASON_USES_DISTRIBUTED_AUDIENCE_MODEL
			if (REASON_USES_DISTRIBUTED_AUDIENCE_MODEL)
			{
				$es = new entity_selector($this->site_id);
			}
			else
			{
				$es = new entity_selector();
			}
			$es->add_type(id_of('audience_type'));
			$this->all_audiences = $es->run_one();

			$head =& $this->get_head_items();
			$head->add_javascript(WEB_JAVASCRIPT_PATH.'policy_selector.js');
			
			if(!$this->_in_show_all_mode())
			{

				if( !empty( $this->request[ 'policy_id' ] ) )
				{
					$roots = $this->get_root_nodes();
					if(array_key_exists($this->request[ 'policy_id' ], $roots))
					{
						$this->policy = $roots[$this->request[ 'policy_id' ]];
						$this->_add_crumb( $this->policy->get_value('name'), get_current_url() );
						$head_items = $this->get_head_items();
						if($this->policy->get_value('keywords'))
						{
							$head_items->add_head_item('meta', array('name'=>'keywords','value'=>reason_htmlspecialchars($this->policy->get_value('keywords'))));
						}
						if($audience = $this->_get_current_audience())
						{
							$head_items->add_head_item('link', array('rel'=>'canonical','href'=>$this->get_no_audience_link()));
						}
						if($pages =& $this->get_page_nav())
							$pages->make_current_page_a_link();
						$access = $this->_get_access_result($this->policy);
						if(false === $access)
							http_response_code(403);
						elseif(null === $access)
							http_response_code(401);
					}
					else
					{
						http_response_code(404);
					}
				}
				
			}
			
			// register the module for inline editing
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$inline_edit->register_module($this, $this->user_can_inline_edit($this->policy));
			
			if ($inline_edit->available_for_module($this))
			{
				$head->add_stylesheet(REASON_HTTP_BASE_PATH.'css/policy/policy_editable.css','',true);
			}
			
		}
		
		protected function _get_current_audience()
		{
			if(!isset($this->current_audience))
			{
				if(!empty($this->request[ 'a' ]) && !empty($this->params['audience_aliases']) && isset($this->params['audience_aliases'][ $this->request[ 'a' ] ]) )
				{
					if($id = id_of($this->params['audience_aliases'][ $this->request[ 'a' ] ]))
					{
						$a = new entity($id);
						if($a->get_values() && $a->get_value('type') == id_of('audience_type'))
							$this->current_audience = $a;
					}
				}
				
				if(!isset($this->current_audience) && !empty($this->request[ 'audience_id' ]))
				{
					$a = new entity($this->request[ 'audience_id' ]);
					if($a->get_values() && $a->get_value('type') == id_of('audience_type'))
						$this->current_audience = $a;
				}
			}
			if(!isset($this->current_audience))
				$this->current_audience = false;
			return $this->current_audience;
		}
		protected function _in_show_all_mode()
		{
			if(!empty($this->request['show_all']) && 'true' == $this->request['show_all'])
				return true;
			return false;
		}
		
		protected function _get_all_policies()
		{
			if(is_null($this->values))
			{
				$es = $this->_get_es();
				$this->values = $es->run_one();
			}
			return $this->values;
		}
		
		protected function _get_es()
		{
			$es = new entity_selector( $this->site_id );
			$es->add_type( id_of( 'policy_type' ) );
			//$es->set_order( 'sortable.sort_order ASC' );
			$es->set_order( 'entity.name ASC' );
			$es->set_env( 'site' , $this->site_id );
			
			$es->add_relation( table_of('show_hide', id_of( 'policy_type' )) .' != "hide"' );
			$es->add_left_relationship_field( 'policy_parent' , 'entity' , 'id' , 'parent_id' );
			
			if($audience = $this->_get_current_audience())
			{
				$es->add_left_relationship($audience->id(),relationship_id_of('policy_to_relevant_audience'));
			}
			
			return $es;
		}
		
		protected function _get_access_result($policy)
		{
			if(!isset($this->access_results[$policy->id()]))
			{
				$helper = $this->_get_access_group_helper($policy);
				if(!empty($helper))
				{
					$this->access_results[$policy->id()] = $helper->is_username_member_of_group(reason_check_authentication());
				}
				else
				{
					$this->access_results[$policy->id()] = true;
				}
			}
			return $this->access_results[$policy->id()];
		}
		
		function run()
		{
			echo '<div id="policyModule">'."\n";
			$roots = $this->get_root_nodes();
			
			if($this->_in_show_all_mode())
			{
				foreach($roots as $root)
				{
					$this->display_policy($root);
				}
			}
			else
			{
			
				if ( empty( $this->request[ 'policy_id' ] ) && count( $roots ) == 1 && !$this->_get_current_audience())
				{
					foreach ( $roots as $k=>$v )
					{
						$this->request[ 'policy_id' ] = $v->get_value( 'id' );
						$this->policy = $v;
					}
				}
				if ( !empty( $this->request[ 'policy_id' ] ) )
				{
					if ( count( $roots ) != 1 )
						$this->show_root_option_menu();
					$this->display_navigation();
					if ( count( $roots ) != 1 )
						$this->display_back_link();
				}
				else
				{
					$this->show_root_list();
				}
			}
			echo '</div>'."\n";
		}
		
		function get_root_nodes()
		{
			if(is_null($this->roots))
			{
				$this->roots = array();
				foreach( $this->_get_all_policies() AS $policy )
				{
					if( $policy->id() == $policy->get_value( 'parent_id' ) )
						$this->roots[$policy->id()] = $policy;
				}
			}
			return $this->roots;
		}
		function show_root_list()
		{
			$roots = $this->get_root_nodes();
			if(!empty($roots))
			{
				if($audience = $this->_get_current_audience())
					echo '<h3 class="audienceNote">Items for '.$audience->get_value('name').'</h3>'."\n";
				echo "<ul class='rootPolicyList'>\n";
				foreach( $roots AS $root )
				{
					echo '<li class="rootPolicyItem"><a href="'.$this->page_link( $root ).'" class="rootPolicyLink">'.strip_tags( $root->get_value( 'name' ), "em,i" ).'</a>';
					if($root->get_value( 'description' ))
					 echo ' <span class="description">'.$root->get_value( 'description' ).'</span>';
					echo '</li>';
				}
				echo "</ul>\n";
			}
			elseif($audience = $this->_get_current_audience())
			{
				echo '<h3 class="audienceEmptyNote">Sorry - this page contains no items for '.$audience->get_value('name').'.</h3>'."\n";
				echo '<p><a href="'.$this->get_no_audience_link().'">Show all items</a></p>'."\n";
			}
			else
			{
				echo '<h3 class="noItemsNote">Sorry - this page contains no items.</h3>'."\n";
			}
		} // }}}
		
		function get_no_audience_link()
		{
			return carl_make_link(array('audience_id'=>'','a'=>'',));
		}

		function get_no_policy_link()
		{
			return carl_make_link(array('policy_id'=>'',));
		}

		function show_root_option_menu() // {{{
		{
			$e = new entity($this->page_id);
			$title = $e->get_value('name');
			
			echo '<form name="policy_form" method="get" class="policyForm">' .
					'<select name="policy_id" class="rootMenu">'.
					'<option value="">' . $title . "</option>\n";
			foreach( $this->get_root_nodes() AS $root )
			{
				echo '<option value="'.$root->id().'"';
				if ( $root->id() == $this->request[ 'policy_id' ] ) echo " selected='selected' ";
				echo '>'.prettify_string( $root->get_value( 'name' ) ).'</option>'."\n";
			}
			echo '';
			if ($this->textonly)
				echo '<input type="hidden" name="textonly" value="1"/>';
			if(!empty($this->request['a']))
				echo '<input type="hidden" name="a" value="'.htmlspecialchars($this->request['a']).'" />';
			if(!empty($this->request['audience_id']))
				echo '<input type="hidden" name="audience_id" value="'.htmlspecialchars($this->request['audience_id']).'" />';
			echo '</select><input type="submit" class="rootMenuSubmit" value="Go"></form>';

		}
		function display_navigation()
		{
			if( !empty( $this->request[ 'policy_id' ] ) )
			{
				if(!empty($this->policy))
				{
					$this->display_policy($this->policy);
					$this->display_proximate($this->policy);
					
				}
				else
				{
					echo '<h3>Policy not found</h3>'."\n";
					echo '<p>This policy is not available. It is possible that it has been removed from the site.</p>'."\n";
					echo '<p>Please contact the maintainer of this site if you have any questions.</p>'."\n";
				}
			}
		}
		protected function get_proximate_policies($policy)
		{
			$policies = $this->get_root_nodes();
			$prev = false;
			$next = false;
			reset($policies);
			while($p = current($policies))
			{
				if($p->id() == $policy->id())
				{
					$next = next($policies);
					break;
				}
				$prev = $p;
				next($policies);
			}
			return array($prev,$next);
		}
		protected function display_proximate($policy)
		{
			list($prev, $next) = $this->get_proximate_policies($policy);
			if(!empty($prev) || !empty($next))
			{
				echo '<div class="proximatePolicies">'."\n";
				if(!empty($next))
				{
					echo '<div class="next"><strong>Next:</strong> <a href="'.$this->page_link( $next ).'">'.$next->get_value('name').'</a></div>'."\n";
				}
				if(!empty($prev))
				{
					echo '<div class="previous"><strong>Previous:</strong> <a href="'.$this->page_link( $prev ).'">'.$prev->get_value('name').'</a></div>'."\n";
				}
				echo '</div>'."\n";
			}
		}
		protected function _get_access_group_helper($policy)
		{
			$rel = relationship_id_of('policy_to_access_group');
			if(!$rel)
				return null;
			$es = new entity_selector();
			$es->add_type(id_of('group_type'));
			$es->add_right_relationship($policy->id(), relationship_id_of('policy_to_access_group'));
			$es->set_num(1);
			$groups = $es->run_one();
			if(empty($groups))
				return null;
			$group = current($groups);
			$gh = new group_helper();
			$gh->set_group_by_entity($group);
			return $gh;
		}
		function get_policy_children($policy)
		{
			$es = new entity_selector();
			$es->add_type(id_of('policy_type'));
			$es->add_relation('entity.id != "'.$policy->id().'"');
			$es->add_relation( table_of('show_hide', id_of( 'policy_type' )) .' != "hide"' );
			$es->add_left_relationship($policy->id(),relationship_id_of('policy_parent'));
			$es->set_order('sortable.sort_order ASC');
			return $es->run_one();
		}
		function get_audiences($policy)
		{
			$es = new entity_selector();
			$es->add_type(id_of('audience_type'));
			$es->add_right_relationship($policy->id(),relationship_id_of('policy_to_relevant_audience'));
			$es->set_order('entity.name ASC');
			return $es->run_one();
		}
		function get_departments($policy)
		{
			$es = new entity_selector();
			$es->add_type(id_of('office_department_type'));
			$es->add_right_relationship($policy->id(),relationship_id_of('policy_to_responsible_department'));
			$es->set_order('entity.name ASC');
			return $es->run_one();
		}

		function page_link( $policy ) // {{{
		{
			if( !is_object( $policy ) )
				$policy = new entity( $policy );
			
			$link = carl_make_link(array('policy_id'=>$policy->id()));

			return $link;
		}
		
		function display_policy($policy)
		{
			$access = $this->_get_access_result($policy);
			if(false === $access)
			{
				echo '<h3>'.$policy->get_value('name').': Access Denied</h3>'."\n";
				echo '<p>Sorry. You do not have permission to view '.$policy->get_value('name').', which is restricted-access.</p>'."\n";
				return;
			}
			elseif(null === $access)
			{
				echo '<h3>'.$policy->get_value('name').': Authentication Required</h3>'."\n";
				echo '<p>'.$policy->get_value('name').' requires login. Please <a href="/'.REASON_LOGIN_PATH.'">sign in</a> to see if you have access.</p>'."\n";
				return;
			}
			
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$editable = $inline_edit->available_for_module($this);
			$cur_policy_edit_id = !empty($this->request['edit_id']) ? $this->request['edit_id'] : false;
			if ($editable)
			{
				$active = $inline_edit->active_for_module($this);
				$class = ($active) ? 'editable editing' : 'editable';
				echo '<div class="'.$class.'">'."\n";
				
				if ($inline_edit->active_for_module($this) && $cur_policy_edit_id == $policy->id())
				{
					echo '<div class="policy root_policy">'."\n";
					$this->run_policy_data_form($policy, true);
					echo '</div>';
				}
				else
				{		
					echo '<div class="policy root_policy editRegion">'."\n";
					echo '<a id="'.$policy->id().'"></a>';
					echo "<h3 class='policyName'>" . 	$policy->get_value('name') . "</h3>\n";
					echo '<div class="policyContent">'.$policy->get_value( 'content' ) . '</div>';
					
					$activation_params = $inline_edit->get_activation_params($this);
					$activation_params['edit_id'] = $policy->id();
					$url = carl_make_link($activation_params);
					echo '<p><a href="'.$url.'#'.$policy->id().'" class="editThis">Edit Policy</a></p>'."\n";
					
					echo '</div>'."\n";
				}
			}
			else 
			{
				echo '<div class="policy root_policy">'."\n";
				echo '<a id="'.$policy->id().'"></a>';
				echo "<h3 class='policyName'>" . 	$policy->get_value('name') . "</h3>\n";
				echo '<div class="policyContent">'.$policy->get_value( 'content' ) . '</div>';
			}
			
			$sub_policies = $this->get_policy_children($policy);
			if(!empty($sub_policies))
			{
				echo '<ol class="'.$this->get_class_for_children($policy).'">'."\n";
				foreach($sub_policies as $p)
				{
					echo '<li>'."\n";
					if ($editable)
						$this->display_sub_policy_editable($p, $cur_policy_edit_id, $inline_edit);
					else
						$this->display_sub_policy($p);
					echo '</li>'."\n";
				}
				echo '</ol>'."\n";
			}
			
			if ($editable)
			{
				if ($inline_edit->active_for_module($this) && self::CUSTOM_EDIT_ID_METADATA == $cur_policy_edit_id)
				{
					$this->run_policy_metadata_form($policy);
				}
				else
				{
					echo '<div class="editRegion">'."\n";
					$this->display_metadata($policy);
					$activation_params = $inline_edit->get_activation_params($this);
					$activation_params['edit_id'] = self::CUSTOM_EDIT_ID_METADATA;
					$url = carl_make_link($activation_params);
					echo '<p><a href="'.$url.'#metadataEdit" class="editThis">Edit Policy Approvals, Etc.</a></p>'."\n";
					echo '</div>'."\n";
				}
			}
			else
			{
				$this->display_metadata($policy);
			}

			if ($editable)
			{
				if ($inline_edit->active_for_module($this) && self::CUSTOM_EDIT_ID_REVIEWED_DATE == $cur_policy_edit_id)
				{
					$this->run_policy_reviewed_date_form($policy);
				}
				else
				{
					$this->display_last_reviewed_date($policy, true);
				}
			}
			else
			{
				$this->display_last_reviewed_date($policy, false);
			}
			
			$depts = $this->get_departments($policy);
			if(!empty($depts))
			{
				$dept_names = array();
				foreach($depts as $dept)
					$dept_names[] = $dept->get_value('name');
				echo '<div class="departments">';
				echo 'Maintained by '.implode(', ',$dept_names);
				echo "</div>\n";
			}
			echo '</div>'."\n";
		}

		protected function prettifyEvenEmptyMysqlDatetime($d)
		{
			$prettyDate = prettify_mysql_datetime($d);
			if ("" == $prettyDate) { $prettyDate = self::UNSET_DATE; }
			return $prettyDate;
		}

		private function createLastReviewedDateHeader($policy) {
			$prettyDate = $this->prettifyEvenEmptyMysqlDatetime($policy->get_value("last_reviewed_date"));
			$title = "";
			if ($prettyDate == self::UNSET_DATE) {
				$title = "Not Reviewed";
			} else {
				$title = "Last Reviewed: " . $prettyDate;
			}
			return "<h4>" . $title . "</h4>";
		}
		
		protected function display_last_reviewed_date($policy, $showEditLink)
		{
			$prettyDate = $this->prettifyEvenEmptyMysqlDatetime($policy->get_value("last_reviewed_date"));
			echo "<div class=\"reviewInfo\">\n";
			echo "<a name=\"" . self::ANCHOR_LAST_REVIEWED . "\"></a>\n";
			echo $this->createLastReviewedDateHeader($policy);
			/*
			echo "<p>" . (($prettyDate == self::UNSET_DATE)
				? "This policy does not have a last reviewed date."
				: "This policy was last reviewed on $prettyDate") . ".</p>\n";
			 */
			if ($showEditLink) {
				$inline_edit =& get_reason_inline_editing($this->page_id);

				$activation_params = $inline_edit->get_activation_params($this);
				$activation_params['edit_id'] = self::CUSTOM_EDIT_ID_REVIEWED_DATE;
				$url = carl_make_link($activation_params);

				echo '<p><a href="'.$url.'#' . self::ANCHOR_LAST_REVIEWED . '" class="editThis">Update policy reviewed date</a></p>'."\n";
			}
			echo "</div>\n";
		}

		protected function display_metadata($policy)
		{
			if ($approvals = $this->get_approvals($policy))
			{
				foreach($approvals as $approval_id => $approval_policy)
				{
					echo '<div class="approvals">';
					echo $this->get_approval_text($approval_policy, ($approval_id != $policy->id()));
					echo "</div>\n";
				}
			}
			if ($policy->get_value( 'last_revised_date' ) > '0000-00-00' )
			{
				echo '<div class="revised">';
				echo 'Last revised '.prettify_mysql_datetime($policy->get_value('last_revised_date'),'F j, Y');
				echo "</div>\n";
			}
			$audiences = $this->get_audiences($policy);
			if(!empty($audiences))
			{
				$audience_names = array();
				foreach($audiences as $audience)
					$audience_names[] = '<a href="'.$this->get_audience_link($audience).'">'.$audience->get_value('name').'</a>';
				echo '<div class="audiences">';
				echo 'For '.implode(', ',$audience_names);
				echo "</div>\n";
			}
			if($policy->get_value( 'keywords' ))
			{
				echo '<div class="keywords">Keywords: '.$policy->get_value( 'keywords' ).'</div>'."\n";
			}
		}
		
		
		protected function get_approval_text($policy, $use_name_as_label = false)
		{
			$ret = $policy->get_value('approvals');
			if($use_name_as_label)
			{
				if(strpos($ret,'<p>') === 0)
					$ret = '<p><span class="policyName">'.$policy->get_value('name').':</span> '.substr($ret,3);
				else
					$ret = '<span class="policyName">'.$policy->get_value('name').':</span> '.$ret;
			}
			return $ret;
		}
		protected function get_audience_link($audience)
		{
			if(!empty($this->params['audience_aliases']) && $audience->get_value('unique_name'))
			{
				if($alias = array_search($audience->get_value('unique_name'),$this->params['audience_aliases']))
				{
					return carl_make_link(array('policy_id'=>'','a'=>$alias,'audience_id'=>''));
				}
			}
			return carl_make_link(array('policy_id'=>'','audience_id'=>$audience->id(),'a'=>''));
		}
		function display_sub_policy($policy)
		{
			echo '<div class="policy sub_policy">'."\n";
			echo '<a id="'.$policy->id().'"></a>';
			echo "<h4 class='policyName'>" . 	$policy->get_value('name') . "</h4>\n";
			if($policy->get_value( 'content' ))
			{
				echo '<div class="policyContent">'.demote_headings($policy->get_value( 'content' ),2) . '</div>';
			}
			$this->display_sub_policies($policy, false);
		}
		
		function display_sub_policy_editable($policy, $cur_policy_edit_id, $inline_edit)
		{
			echo '<div class="editable">';
			
			// if this policy is the one currently being edited, we display a disco form.  Othewise,
			// we display the policy normally with inline editing css elements.
			if ($inline_edit->active_for_module($this) && $cur_policy_edit_id == $policy->id())
			{
				echo '<div class="policy sub_policy">'."\n";
				$this->run_policy_data_form($policy, false);
				echo '</div>'."\n";
			}
			else
			{
				echo '<div class="policy sub_policy editRegion">'."\n";
				echo '<a id="'.$policy->id().'" name="'.$policy->id().'"></a>';
				echo "<h4 class='policyName'>" . 	$policy->get_value('name') . "</h4>\n";
				echo '<div class="policyContent">'.$policy->get_value('content') . '</div>';
				
				$activation_params = $inline_edit->get_activation_params($this);
				$activation_params['edit_id'] = $policy->id();
				$url = carl_make_link($activation_params);
				echo '<p><a href="'.$url.'#'.$policy->id().'" class="editThis">Edit Section</a></p>'."\n";
				echo '</div>'."\n";	
			}
			$this->display_sub_policies($policy, true, $cur_policy_edit_id, $inline_edit);
			echo '</div>';
		}
		
		function display_sub_policies($policy, $editable, $cur_policy_edit_id = null, $inline_edit = null)
		{
			$sub_policies = $this->get_policy_children($policy);
			if(!empty($sub_policies))
			{
				echo '<ol class="'.$this->get_class_for_children($policy).'">'."\n";
				foreach($sub_policies as $p)
				{
					echo '<li>'."\n";
					if ($editable)
					{
						$this->display_sub_policy_editable($p, $cur_policy_edit_id, $inline_edit);
					}
					else
					{
						$this->display_sub_policy($p, false);
					}
					echo '</li>'."\n";
				}
				echo '</ol>'."\n";
			}
		}

		function get_approvals($policy)
		{
			$ret = array();
			if ($policy->get_value( 'approvals' ))
				$ret[$policy->id()] = $policy;
			
			$sub_policies = $this->get_policy_children($policy);
			if(!empty($sub_policies))
			{
				foreach($sub_policies as $p)
				{
					foreach($this->get_approvals($p) as $id => $p)
					{
						$ret[$id] = $p;
					}
				}
			}
			return $ret;
		}
		function display_back_link() // {{{
		{
			echo '<div class="listLink"><p><a href="'.$this->get_no_policy_link().'" class="rootPolicyListLink">List of policies</a></p></div>'."\n";
		}
		function get_class_for_children($policy)
		{
			switch($policy->get_value( 'numbering_scheme' ))
			{
				case 'Uppercase Roman':
					return 'upperRoman';
				case 'Lowercase Roman':
					return 'lowerRoman';
				case 'Uppercase Alpha':
					return 'upperAlpha';
				case 'Lowercase Alpha':
					return 'lowerAlpha';
				case 'Decimal':
				default:
					return 'decimal';
			}
		}
		
		function run_policy_data_form($policy, $is_root)
		{
			echo '<a name="'.$policy->id().'"></a>'."\n";
			$this->is_root = $is_root;
			$this->edit_policy = $policy;
			
			$form = new Disco();
			$form->strip_tags_from_user_input = true;
			$form->allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;
			$form->actions = array('save' => 'Save', 'save_and_finish' => 'Save and Finish Editing');
				
			$this->init_field($form, 'name', $policy, 'text', 'solidtext');
			$form->set_value('name', $policy->get_value('name'));
			$form->set_display_name('name', 'Title');
			$form->add_required('name');
			
			if ($this->is_root)
			{
				$this->init_field($form, 'description', $policy, 'text', 'solidtext');
				$form->set_value('description', $policy->get_value('description'));
			}
			
			//$form->add_element('content', html_editor_name($this->site_id), html_editor_params($this->site_id, $this->get_html_editor_user_id()));
			$this->init_field($form, 'content', $policy, html_editor_name($this->site_id), 'wysiwyg_disabled', html_editor_params($this->site_id, $this->get_html_editor_user_id()));
			$form->set_value('content', $policy->get_value('content'));
			$form->set_display_name('content', 'Section Content');
			
			$sub_policies = $this->get_policy_children($policy);
			if (!empty($sub_policies))
			{
				// Populate the numbering_scheme elements
				$es = new entity_selector();
				$es->add_type(id_of('content_table'));
				$es->add_relation('entity.name = "policies"');
				$policy_table = current($es->run_one());
				unset($es);
				
				$es = new entity_selector();
				$es->add_type( id_of('field') );
				$es->add_left_relationship($policy_table->id(), relationship_id_of('field_to_entity_table'));
				$es->add_relation('entity.name = "numbering_scheme"');
				$field = current($es->run_one());
				
				$args = array();
				$type = '';
				// set the plasmature type if specified by the field, otherwise look up the default for the database type
				list( $type, $args ) = $form->plasmature_type_from_db_type( $field->get_value('name'), $field->get_value('db_type') );
				if ( $field->get_value( 'plasmature_type' ) )
					$type = $field->get_value( 'plasmature_type' );
				// hook for plasmature arguments here
				//$form->add_element('numbering_scheme', $type, $args, $field->get_value('db_type'));
				$this->init_field($form, 'numbering_scheme', $policy, $type, 'solidtext', $args);
				$form->set_value('numbering_scheme', $policy->get_value('numbering_scheme'));
				$form->set_display_name('numbering_scheme', 'Sub-Policy Numbering Style');
			}
			
			$form->add_callback(array(&$this, '_process_policy'),'process');
			$form->add_callback(array(&$this, '_where_to_policy'), 'where_to');
			$form->run();	
		}	
		
		/**
		* Inits a disco form element as a locked or unlocked field.
		*/ 
		function init_field($form, $field_name, $policy, $type, $lock_type, $params = null)
		{
			if (!$policy->field_has_lock($field_name))
			{
				$form->add_element($field_name, $type, $params);
			}
			else
			{
				$form->add_element($field_name, $lock_type);
				$form->set_comments($field_name, '');
				$form->set_comments($field_name, '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" />', 'before' );
			}
		}
		
		function _process_policy(&$disco)
		{
			$values['name'] = tidy($disco->get_value('name'));
			$values['description'] = tidy($disco->get_value('description'));
			$values['content'] = tidy($disco->get_value('content'));
			$values['numbering_scheme'] = tidy($disco->get_value('numbering_scheme'));
			
			$archive = ($disco->get_chosen_action() == 'save_and_finish') ? true : false;
			reason_update_entity( $this->request['edit_id'], $this->get_update_entity_user_id(), $values, $archive );
		}
		
		function _where_to_policy(&$disco)
		{
			if( $disco->get_chosen_action() == 'save' )
			{
				$url = get_current_url();
				$url .= '#'.$this->edit_policy->id();
			}
			else
			{
				$inline_edit =& get_reason_inline_editing($this->page_id);
				$deactivation_params = $inline_edit->get_deactivation_params($this);
				$deactivation_params['edit_id'] = '';
				$url = carl_make_redirect($deactivation_params);
			}
			return $url;
		}		
		
		function run_policy_metadata_form($policy)
		{
			echo '<a name="metadataEdit"></a>'."\n";
			$this->edit_policy = $policy;
			
			$form = new Disco();
			$form->strip_tags_from_user_input = true;
			$form->allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;
			$form->actions = array('save' => 'Save', 'save_and_finish' => 'Save and Finish Editing');
			

			$this->init_field($form, 'approvals', $policy, html_editor_name($this->site_id), 'wysiwyg_disabled', html_editor_params($this->site_id, $this->get_html_editor_user_id()));
			$form->set_value('approvals', $policy->get_value('approvals'));
			$form->set_element_properties( 'approvals', array('rows'=>5));	
	
			$this->init_field($form, 'last_revised_date', $policy, 'textDate', 'solidtext');
			$form->set_value('last_revised_date', $policy->get_value('last_revised_date'));
		
			// $this->init_field($form, 'last_reviewed_date', $policy, 'checkbox', 'solidtext');
			// $form->set_display_name('last_reviewed_date', 'I have reviewed this policy');				
		
			$this->init_field($form, 'keywords', $policy, 'text', 'solidtext');
			$form->set_value('keywords', $policy->get_value('keywords'));
		
			/* This next chunk of code handles the logic for setting the initial values
			 of the audience checkboxes. */
			// First, build an array [audience name => index] for all possible audiences
			$this->audience_opts = array();
			$i = 0;
			foreach ($this->all_audiences as $audience)
			{
				$this->audience_opts[$audience->get_value('name')] = $i++;
			}
			
			// Now, retrieve the audiences that are currently associated with the policy we are currently editing
			$es = new entity_selector();
			$es->add_type(id_of('audience_type'));
			$es->add_right_relationship($policy->id(), relationship_id_of('policy_to_relevant_audience'));
			$this->related_audiences = $es->run_one();				
			
			// Lastly, build an array of the indexes of the audiences that are currently associated with this policy
			$checked_audiences = array();
			foreach ($this->related_audiences as $audience)
			{
				$checked_audiences[] = $this->audience_opts[$audience->get_value('name')];
			}
			// Then, add the names of the audiences to the checkboxgroup plasmature
			if ($policy->user_can_edit_relationship('policy_to_relevant_audience', $user = null, 'right'))
			{
				$form->add_element('audiences', 'checkboxgroup', array('options' => array_keys($this->audience_opts)));
				$form->set_value('audiences', $checked_audiences);
			}
			else
			{
				$form->add_element('audiences', 'solidtext');
				$names = array();
				foreach ($this->audience_opts as $name => $val)
				{
					if (in_array($val, $checked_audiences))
						$names[] = $name;
				}
				sort($names);
				$form->set_value('audiences', implode(' ', $names));
			}
			$form->add_required('audiences');
			
			$form->add_callback(array(&$this, '_process_policy_metadata'),'process');
			$form->add_callback(array(&$this, '_where_to_policy_metadata'), 'where_to');
			$form->run();	
		}

		function run_policy_reviewed_date_form($policy)
		{
			$prettyDate = $this->prettifyEvenEmptyMysqlDatetime($policy->get_value("last_reviewed_date"));

			echo "<div class=\"reviewInfo\">\n";
			echo "<a name=\"" . self::ANCHOR_LAST_REVIEWED . "\"></a>\n";
			echo $this->createLastReviewedDateHeader($policy);

			/*
			echo "<p>" . (($prettyDate == self::UNSET_DATE)
				? "This item does not have a last reviewed date."
				: "This item was last reviewed on $prettyDate") . ".</p>\n";
			 */

			echo "<p>By clicking \"I Agree\", you affirm that you have reviewed this policy and that it is up-to-date. Clicking \"I Agree\" will set the policy's last reviewed date to " . date("M jS, Y") . ".</p>\n";

			$this->edit_policy = $policy;
			
			$form = new Disco();
			$form->set_box_class("StackedBox");
			$form->actions = array(self::ACTION_CANCEL => 'Cancel', self::ACTION_MARK_POLICY_REVIEWED => 'I Agree');
			if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE) {
				$form->actions[self::ACTION_DEBUG] = 'Set back to 0000-00-00 for testing purposes.';
			}
			
			$form->add_callback(array(&$this, '_process_policy_reviewed_date'),'process');
			$form->add_callback(array(&$this, '_where_to_policy_reviewed_date'), 'where_to');
			$form->run();	
			echo "</div>\n";
		}

		function _process_policy_reviewed_date(&$disco)
		{
			$chosenAction = $disco->get_chosen_action();

			if (self::ACTION_CANCEL == $chosenAction)
			{
				// user cancelled; no need to do anything, the where_to callback will just bounce them back to the back with inline editing disabled
			}
			else if (self::ACTION_DEBUG == $chosenAction)
			{
				$saveVals['last_reviewed_date'] = "0000-00-00";
				reason_update_entity($this->request['policy_id'], $this->get_update_entity_user_id(), $saveVals, false);
			}
			else if (self::ACTION_MARK_POLICY_REVIEWED == $chosenAction)
			{
				$saveVals['last_reviewed_date'] = carl_date('Y-m-d');
				reason_update_entity($this->request['policy_id'], $this->get_update_entity_user_id(), $saveVals, true);
			}
		}

		function _where_to_policy_reviewed_date(&$disco)
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$deactivation_params = $inline_edit->get_deactivation_params($this);
			$deactivation_params['edit_id'] = '';
			$url = carl_make_redirect($deactivation_params);
			$url .= '#' . self::ANCHOR_LAST_REVIEWED; // keep them viewing this section so they can see the result of their edit
			return $url;
		}		
		
		function _process_policy_metadata(&$disco)
		{
			$values['approvals'] = tidy($disco->get_value('approvals'));
			$values['last_revised_date'] = tidy($disco->get_value('last_revised_date'));
			// moved this into it's own section. Also, this appears to not have taken the checkbox into account at all? bug.
			// $values['last_reviewed_date'] = carl_date('Y-m-d');
			$values['keywords'] = tidy($disco->get_value('keywords'));

			foreach ($this->all_audiences as $audience)
			{	
				// if the audience is checked
				if (in_array($this->audience_opts[$audience->get_value('name')], $disco->get_value('audiences')))
				{
					if (!in_array($audience, $this->related_audiences))
					{
						create_relationship($this->edit_policy->id(), $audience->id(), relationship_id_of('policy_to_relevant_audience'));
					}
				}
				// if the audience was unchecked by the user
				elseif (in_array($audience, $this->related_audiences))
				{
					$conditions = array(
						'entity_a'=> $this->edit_policy->id(),
						'entity_b'=> $audience->id(),
						'type'=> relationship_id_of('policy_to_relevant_audience'),
					);
					delete_relationships($conditions);
				}
			}
			$archive = ($disco->get_chosen_action() == 'save_and_finish') ? true : false;
			$succes = reason_update_entity( $this->request['policy_id'], $this->get_update_entity_user_id(), $values, $archive );
		}

		function _where_to_policy_metadata(&$disco)
		{
			if( $disco->get_chosen_action() == 'save' )
			{
				$url = get_current_url();
				$url .= '#metadataEdit';
			}
			else
			{
				$inline_edit =& get_reason_inline_editing($this->page_id);
				$deactivation_params = $inline_edit->get_deactivation_params($this);
				$deactivation_params['edit_id'] = '';
				$url = carl_make_redirect($deactivation_params);
			}
			return $url;
		}		
	
		/**
		 * Determines whether or not the user can inline edit. Only admin users and the 
		 * policy maintaner may perform inline editing for policies.
		 *
		 * @return boolean;
		 */
		function user_can_inline_edit()
		{
			if (!isset($this->_user_can_inline_edit))
			{
				$this->_user_can_inline_edit = false;
				if($cur_user = reason_check_authentication())
				{
					if (isset($this->policy))
					{
						$owner = $this->policy->get_owner();
						if($owner && reason_check_access_to_site($owner->id()))
						{
							$this->_user_can_inline_edit = true;
						}
						else
						{
							$departments = $this->policy->get_left_relationship( 'policy_to_responsible_department' );
							if(!empty($departments))
							{
								foreach($departments as $department)
								{
									if($department->get_value('policy_maintainer') == $cur_user)
									{
										$this->_user_can_inline_edit = true;
										break;
									}
								}
							}
						}
					}
				}
			}
			return $this->_user_can_inline_edit;
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
		 * @return int reason user entity or id of site_user entity that corresponds to logged in user
		 */
		function get_update_entity_user_id()
		{
			if ($net_id = reason_check_authentication())
			{
				$reason_id = get_user_id($net_id);
				if (!empty($reason_id)) return $reason_id;
				elseif ($site_user = $this->get_site_user()) return $site_user->id();
			}
			return false;
		}	
	}
?>
