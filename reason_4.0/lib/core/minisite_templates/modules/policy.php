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
		var $cleanup_rules = array(
			'policy_id' => array('function' => 'turn_into_int'),
			'audience_id' => array('function' => 'turn_into_int'),
			'a' => array('function' => 'turn_into_string'),
			'show_all' => array( 'function' => 'check_against_array', 
								 'extra_args' => array( 'true', 'false' ) ),
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
		protected $current_audience;
		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$head =& $this->get_head_items();
			$head->add_javascript('/reason_package/reason_4.0/www/js/policy_selector.js');
			
			if(!$this->_in_show_all_mode())
			{

				if( !empty( $this->request[ 'policy_id' ] ) )
				{
					$roots = $this->get_root_nodes();
					if(array_key_exists($this->request[ 'policy_id' ], $roots))
					{
						$this->policy = $roots[$this->request[ 'policy_id' ]];
						$this->_add_crumb( $this->policy->get_value('name'), get_current_url() );
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
		} // }}}
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
		function run() // {{{
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
		} // }}}
		function get_root_nodes() // {{{
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
		} // }}}
		function show_root_list() // {{{
		{
			$roots = $this->get_root_nodes();
			if(!empty($roots))
			{
				if($audience = $this->_get_current_audience())
					echo '<h3 class="audienceNote">Items for '.$audience->get_value('name').'</h3>'."\n";
				echo "<ul class='rootPolicyList'>\n";
				foreach( $roots AS $root )
				{
					echo '<li class="rootPolicyItem"><a href="'.$this->page_link( $root ).'" class="rootPolicyLink">'.strip_tags( $root->get_value( 'name' ), "em,i" ).'</a> '.$root->get_value( 'description' ).'</li>';
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

		} // }}}
		function display_navigation() // {{{
		{
			if( !empty( $this->request[ 'policy_id' ] ) )
			{
				if(!empty($this->policy))
				{
					$this->display_policy($this->policy);
				}
				else
				{
					echo '<h3>Policy not found</h3>'."\n";
					echo '<p>This policy is not available. It is possible that it has been removed from the site.</p>'."\n";
					echo '<p>Please contact the maintainer of this site if you have any questions.</p>'."\n";
				}
			}
		} // }}}
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
		} // }}}
		function display_policy($policy) // {{{
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
			
			echo '<div class="policy">'."\n";
			echo '<a id="'.$policy->id().'"></a>';
			/*	if ( !in_array( $item->id(), $this->root_node() ) )
				{
					echo '<li class="'.$this->li_class.'">'."\n";
					$header_type = "h4";
				} */
			echo "<h3 class='policyName'>" . 	$policy->get_value('name') . "</h3>\n";
			echo '<div class="policyContent">'.$policy->get_value( 'content' ) . '</div>';
			$sub_policies = $this->get_policy_children($policy);
			if(!empty($sub_policies))
			{
				echo '<ol class="'.$this->get_class_for_children($policy).'">'."\n";
				foreach($sub_policies as $p)
				{
					echo '<li>'."\n";
					$this->display_sub_policy($p);
					echo '</li>'."\n";
				}
				echo '</ol>'."\n";
			}
			/*	if ( !in_array( $item->id(), $this->root_node() ) ) echo '</li>'; */
			if ($policy->get_value( 'approvals' ))
			{
				echo '<div class="approvals">';
				echo $policy->get_value( 'approvals' );
				echo "</div>\n";
			}
			if ($policy->get_value( 'last_revised_date' ) > '0000-00-00' )
			{
				echo '<div class="revised">';
				echo '<p>Last revised '.prettify_mysql_datetime($policy->get_value('last_revised_date'),'F j, Y').'</p>';
				echo '</div>'."\n";
			}
			$audiences = $this->get_audiences($policy);
			if(!empty($audiences))
			{
				$audience_names = array();
				foreach($audiences as $audience)
					$audience_names[] = '<a href="'.$this->get_audience_link($audience).'">'.$audience->get_value('name').'</a>';
				echo '<div class="audiences">';
				echo '<p>For '.implode(', ',$audience_names).'</p>';
				echo "</div>\n";
			}
			$depts = $this->get_departments($policy);
			if(!empty($depts))
			{
				$dept_names = array();
				foreach($depts as $dept)
					$dept_names[] = $dept->get_value('name');
				echo '<div class="departments">';
				echo '<p>Maintained by '.implode(', ',$dept_names).'</p>';
				echo "</div>\n";
			}
			echo '</div>'."\n";
		} // }}}
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
			echo '<a id="'.$policy->id().'"></a>';
			echo "<h4 class='policyName'>" . 	$policy->get_value('name') . "</h4>\n";
			echo '<div class="policyContent">'.$policy->get_value( 'content' ) . '</div>';
			$sub_policies = $this->get_policy_children($policy);
			if(!empty($sub_policies))
			{
				echo '<ol class="'.$this->get_class_for_children($policy).'">'."\n";
				foreach($sub_policies as $p)
				{
					echo '<li>'."\n";
					$this->display_sub_policy($p);
					echo '</li>'."\n";
				}
				echo '</ol>'."\n";
			}
		}
		function display_back_link() // {{{
		{
			echo '<div class="listLink"><p><a href="'.$this->get_no_policy_link().'" class="rootPolicyListLink">List of policies</a></p></div>'."\n";
		} // }}}
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
	}
?>
