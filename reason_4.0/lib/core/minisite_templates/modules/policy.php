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
			'audience' => array('function' => 'turn_into_string'),
		);
		protected $_acccess_result_fetched = false;
		protected $_access_result = true;
		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$head =& $this->get_head_items();
			$head->add_javascript('/reason_package/reason_4.0/www/js/policy_selector.js');

			$es = $this->_get_es();

			$this->values = $es->run_one();
			
			/*
			
			$this->pages = new PolicyNavigation;
			$this->pages->request =& $this->request;
			// small kludge - just give the tree view access to the site info.  used in the show_item function to show the root node of the navigation
			if ( !empty ( $this->site_info ) )
				$this->pages->site_info = $this->site_info;
			$this->pages->order_by = 'sortable.sort_order ASC';
			$this->pages->init( $this->site_id, id_of('policy_type') );
			
			*/

			if( !empty( $this->request[ 'policy_id' ] ) )
			{
				if(isset($this->values[$this->request[ 'policy_id' ]]))
				{
					$this->policy = $this->values[$this->request[ 'policy_id' ]];
					$this->_add_crumb( $this->values[$this->request[ 'policy_id' ]]->get_value('name'), get_current_url() );
					if($pages =& $this->get_page_nav())
						$pages->make_current_page_a_link();
				}
				else
				{
					http_response_code(404);
				}
			}
			if(false === $this->_get_access_result())
				http_response_code(403);
			elseif(null === $this->_get_access_result())
				http_response_code(401);
		} // }}}
		protected function _get_es()
		{
			$es = new entity_selector( $this->site_id );
			$es->add_type( id_of( 'policy_type' ) );
			//$es->set_order( 'sortable.sort_order ASC' );
			$es->set_order( 'entity.name ASC' );
			
			$es->add_relation( table_of('show_hide', id_of( 'policy_type' )) .' != "hide"' );
			$es->add_left_relationship_field( 'policy_parent' , 'entity' , 'id' , 'parent_id' );
			
			return $es;
		}
		protected function _get_access_result()
		{
			if(!$this->_acccess_result_fetched)
			{
				if(!empty($this->policy))
				{
					$helper = $this->_get_access_group_helper($this->policy);
					if(!empty($helper))
					{
						$this->_access_result = $helper->is_username_member_of_group(reason_check_authentication());
					}
				}
				$this->_acccess_result_fetched = true;
			}
			return $this->_access_result;
		}
		function run() // {{{
		{
			$this->get_root_nodes();
			//pray ($this->roots);
			if ( empty( $this->request[ 'policy_id' ] ) && count( $this->roots ) == 1)
			{
				foreach ( $this->roots as $k=>$v )
				{
					$this->request[ 'policy_id' ] = $v->get_value( 'id' );
				}
			}
			if ( !empty( $this->request[ 'policy_id' ] ) )
			{
				if ( count( $this->roots ) != 1 )
					$this->show_root_option_menu();
				$this->display_navigation();
				if ( count( $this->roots ) != 1 )
					$this->display_back_link();
			}
			else
			{
				$this->show_root_list();
			}
		} // }}}
		function get_root_nodes() // {{{
		{
			$r = array();
			foreach( $this->values AS $v )
			{
				if( $v->id() == $v->get_value( 'parent_id' ) )
					$r[] = $v;
			}
			$this->roots = $r;
		} // }}}
		function show_root_list() // {{{
		{
			echo "<ul class='rootPolicyList'>\n";
			foreach( $this->roots AS $root )
			{
				echo '<li class="rootPolicyItem"><a href="'.$this->page_link( $root ).'" class="rootPolicyLink">'.strip_tags( $root->get_value( 'name' ), "em,i" ).'</a> '.$root->get_value( 'description' ).'</li>';
			}
			echo "</ul>\n";
		} // }}}
		function show_root_option_menu() // {{{
		{
			$e = new entity($this->page_id);
			$title = $e->get_value('name');
			
			echo '<form name="policy_form" method="get" class="policyForm">' .
					'<select name="policy_id" class="rootMenu">'.
					'<option value="">' . $title . "</option>\n";
			foreach( $this->roots AS $root )
			{
				echo '<option value="'.$root->id().'"';
				if ( $root->id() == $this->request[ 'policy_id' ] ) echo " selected='selected' ";
				echo '>'.prettify_string( $root->get_value( 'name' ) ).'</option>'."\n";
			}
			echo '';
			if ($this->textonly) echo '<input type="hidden" name="textonly" value="1"/>';
			echo '</select><input type="submit" class="rootMenuSubmit" value="Go"></form>';

		} // }}}
		function display_navigation() // {{{
		{
			if( !empty( $this->request[ 'policy_id' ] ) )
			{
				if(!empty($this->policy))
				{
					if(false === $this->_get_access_result())
					{
						echo '<h3>Access Denied</h3>'."\n";
						echo '<p>Sorry. You do not have permission to view this restricted-access policy.</p>'."\n";
					}
					elseif(null === $this->_get_access_result())
					{
						echo '<h3>Authentication Required</h3>'."\n";
						echo '<p>This policy requires login. Please <a href="/'.REASON_LOGIN_PATH.'">sign in</a> to see if you have access to view this policy.</p>'."\n";
					}
					else
					{
						$this->display_policy($this->policy);
					}
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
			$es->add_left_relationship($policy->id(),relationship_id_of('policy_to_relevant_audience'));
			$es->set_order('entity.name ASC');
			return $es->run_one();
		}
		function get_departments($policy)
		{
			$es = new entity_selector();
			$es->add_type(id_of('office_department_type'));
			$es->add_left_relationship($policy->id(),relationship_id_of('policy_to_responsible_department'));
			$es->set_order('entity.name ASC');
			return $es->run_one();
		}
		function page_link( $page ) // {{{
		{
			if( !is_object( $page ) )
				$page = new entity( $page );

			$link = '?policy_id=' . $page->id();
			if (!empty($this->textonly))
				$link .= '&amp;textonly=1';

			return $link;
		} // }}}
		function display_policy($policy) // {{{
		{
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
				echo 'Last revised '.prettify_mysql_datetime($policy>get_value('last_revised_date'),'F j, Y');
				echo "</div>\n";
			}
			$audiences = $this->get_audiences($policy);
			if(!empty($audiences))
			{
				$audience_names = array();
				foreach($audiences as $audience)
					$audience_names[] = $audience->get_value('name');
				echo '<div class="audiences">';
				echo 'For '.implode(', ',$audience_names);
				echo "</div>\n";
			}
			$depts = $this->get_departments($policy);
			if(!empty($depts))
			{
				$dept_names = array();
				foreach($departments as $dept)
					$dept_names[] = $dept->get_value('name');
				echo '<div class="departments">';
				echo 'Maintained by '.implode(', ',$dept_names);
				echo "</div>\n";
			}
			echo '</div>'."\n";
		} // }}}
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
			$list_link = '?';
			if (!empty($this->textonly))
				$list_link .= '&amp;textonly=1';
			echo "<p><a href='".$list_link."' class='rootPolicyListLink'>List of policies</a></p>\n";
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
