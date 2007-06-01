<?php
	/*
	 *	Admin Page
	 *
	 *	Wraps up most of the funcionality of the Reason Admin
	 *
	 *	@package Reason_Core
	 */

	include_once( 'reason_header.php' );
	reason_include_once( 'classes/viewer.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'classes/admin/admin_module.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	
	/*
	 *	Admin Page
	 *
	 *	Wraps up most of the funcionality of the Reason Admin
	 *
	 *	@todo completely overhaul internal workings of this class -- it is a real mess
	 *	@todo get add'l head items (e.g. css, js, etc) from modules rather than having hard-coded/special-cased ways of including them here
	 *	@todo separate output and logic -- fold most of html into either a single separate template or into several different templates that work similarly
	 *
	 *	@author dave hendler, brendon stanton, matt ryan, nate white, probably others
	 */
	class AdminPage
	{
		// title of page
		var $title = 'Web Administration';

		// items to show
		var $show = array();

		// array of url => name pairs for breadcrumbs
		var $breadcrumbs;

		// admin state variables
		var $user_id;
		var $site_id;
		var $type_id;
		var $id;
		var $rel_id;
		var $viewer_id;
		var $cur_module;

		// current AdminModule
		var $module;

		// logged in user
		var $authenticated_user_id;

		//customizing stylesheets for different modules
		var $style_sheet = array( 'Archive' => 'archive.css' ,
  		    						  'Associator' => 'assoc.css' ,
		 						  'ReverseAssociator' => 'assoc.css' ,
		 						  'Sharing' => 'share.css' ,
								);

		var $script = array( 'Associator' => 'table_update.js' );
  
		//default args will be always passed on admin pages
		var $default_args = 			    array('site_id',
								  'type_id',
								  'id',
								  'rel_id',
								  'cur_module',
								  'new_entity',
								  'user_id',
								  'open',
								  );

		
		function AdminPage( ) // {{{
		{
			// init what to show.  by default, show everything.
			$this->show = array(
				'leftbar' => true,
				'rightbar' => true,
				'sites' => true,
				'types' => true,
				'leftbar_other' => true,
				'stats' => true,
				'breadcrumbs' => true,
				'title' => true,
				'main' => true,
				'banner' => true,
				'admin_tools' => false,
				'sitebar' => true,
				'sharing' => true,
				'site_tools' => true,
				'themes' => ALLOW_REASON_SITES_TO_SWITCH_THEMES,
			);
		} // }}}
	
		// handle all initialization and sessioning stuff
		// takes the current request state as variable. 
		function load_params( $authenticated_user_id, $request ) // {{{
		//first function that is called.  It sets up all the proper variables in admin page and sets user id.
		{
			$param_cleanup_rules = array( 'site_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')),
						 'type_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')),
						 'user_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')),
						 'id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')),
						 'rel_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')),
						 'cur_module' => array('function' => 'check_against_regexp', 'extra_args' => array('safechars')),
						 'viewer_id' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')), 
						 'entity_a' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')), 
						 'entity_b' => array('function' => 'turn_into_int', 'extra_args' => array('zero_to_null' => 'true')),
						 'new_entity' => array('function' => 'check_against_array', 'extra_args' => array(0, 1)),	 
						 'debugging' => array('function' => 'check_against_array', 'extra_args' => array('true', 'false')),
						 'state' => array('function' => 'check_against_array', 'extra_args' => array('deleted', 'pending', 'live')));

			$params_to_localize = array( 'site_id','user_id','type_id','id',
						     'rel_id','cur_module','viewer_id',
						     'entity_a','entity_b','debugging' );			 

			$this->authenticated_user_id = $authenticated_user_id; 

			$this->request = array_merge($request, clean_vars($request, $param_cleanup_rules));

			foreach ( $params_to_localize as $v )
			{
				if (isset($this->request[$v]))
					$this->$v = $this->request[$v]; 
			}
		
			if( isset( $this->request[ 'PHPSESSID' ] ) )
				unset( $this->request[ 'PHPSESSID' ] );			
			
			$this->select_user();
		} // }}}

		function set_show( $section, $val ) // {{{
		//turns on or off $section from being shown
		{
			$this->show[ $section ] = $val;
		} // }}}
		//breadcrumbs aren't currently being used, but we kept the code just in case we decide to go back
		function set_breadcrumbs( $crumbs ) // {{{
		{

			if( is_array( $crumbs ) ) 
				$this->breadcrumbs = $crumbs;
			else
				trigger_error('AdminPage :: set_breadcrumbs :: The argument to breadcrumbs must be an array of the form array("url"=>"crumb_title")');
		} // }}}
		function get_breadcrumbs() // {{{
		{
			return $this->breadcrumbs;
		} // }}}
		function get_name( $id ) // {{{
		//gets name of entity with id = $id
		{
			if( !empty( $this->names[ $id ] ) )
				return $this->names[ $id ];
			else
			{
				$e = new entity( $id );
				$this->names[ $id ] = $e->get_value( 'name' );
				return $this->names[ $id ];
			}
		} // }}}

		// IN_MODULE
		function leftbar() // {{{
		//there are two basic left bars that can be shown.  There's the standard one which comes up if there is no id which lists
		//types and such.  The other is if there is an id, this shows all the options for the entity being edited.
		{
			if( empty( $this->id ) )
				$this->leftbar_normal();
			else
				$this->leftbar_item();
		} // }}}
		// IN_MODULE
		function leftbar_normal() // {{{
		//no id leftbar
		{
			echo '<div class="leftNav">';
			if( $this->show[ 'sites' ] )
			{
				$this->sites();
			}
			if(!empty($this->site_id))
			{
				if( $this->show[ 'types' ] )
				{
					$this->types();
				}
				if( $this->show[ 'sharing' ] )
				{
					$this->sharing();
				}
				if( $this->show[ 'leftbar_other' ] )
				{
					$this->leftbar_other();
				}
				if( $this->show[ 'site_tools' ] )
				{
					$this->site_tools();
				}
			}
			$this->admin_tools();
			echo '</div>';
		} // }}}
		// IN_MODULE
		function leftbar_item() // {{{
		//leftbar if id is present
		{
		?>
			<div class="managerNav">
							<div class="roundedTop"> <img src="<?php echo REASON_ADMIN_IMAGES_DIRECTORY; ?>nw.gif" alt="" class="roundedCorner" /> 
							</div>
							<div class="managerList">
		<?php
			if( !empty( $this->request[ CM_VAR_PREFIX . 'id' ] ) )
			{
				$old_name = new entity( $this->request[ CM_VAR_PREFIX . 'id' ] );
			}
			$item = new entity( $this->id );
			$name = isset( $old_name ) ? $old_name->get_value( 'name' ) : $item->get_value( 'name' );
			$name = ($name OR (strlen($name) > 0)) ? $name : '<em>New Item</em>';
			echo '<strong>' . $name . '</strong><br />';
			echo '<ul class="leftList">';
			if(	site_owns_entity( $this->site_id , $this->id ) )
			{
				$this->show_owns_links();
				echo '</ul>';
				if( empty( $this->request[ CM_VAR_PREFIX . 'type_id' ] ) )
					$this->show_other_links_item();
			}
			else
			{
				$this->show_borrows_links();
				echo '</ul>';
			}
		?>
							</div>
							<div class="roundedBottom"> <img src="<?php echo REASON_ADMIN_IMAGES_DIRECTORY; ?>sw.gif" alt="" class="roundedCorner" /> 
							</div>	
			</div> 
		<?php
		} // }}}
		
		function is_selected( $item ) // {{{
		//helper for leftbar_item.  tells you if current item on list is selected
		{
			if( $this->cur_module == 'Editor' ) // {{{
			{
				if( $item == 'Edit' )
					return true;
				else return false;
			} // }}}
			if( $this->cur_module == 'Preview' ) // {{{
			{
				if( $item == 'Preview' )
					return true;
				else return false;
			} // }}}
			if( $this->cur_module == 'DoBorrow' ) // {{{
			{
				if( $item == 'Borrow' || $item == "Don't Borrow" )
					return true;
				else return false;
			} // }}}
			if( $this->cur_module == 'Associator' ) // {{{
			{
				foreach( $this->associations AS $ass )
				{
					if( $this->rel_id == $item )
						return true;
					else
						return false;
				}
			} // }}}
			if( $this->cur_module == 'ReverseAssociator' ) // {{{
			{
				foreach( $this->reverse_associations AS $ass )
				{
					if( $this->rel_id == $item )
						return true;
					else
						return false;
				}
			} // }}}
			return false;
		} // }}}
		function is_second_level() // {{{
		//tells you if you are "in the second level" of editing
		{
			if( isset( $this->request[ CM_VAR_PREFIX . 'type_id' ] ) )
				return true;
			else
				return false;
		} // }}}
		/**
		 * Gets all relationships where current item is on left side
		 * @param $var makes sure current type is in ar.relationship_a by default...not sure if this is non-default somewhere
		 */
		function get_rels( $var = 'default' ) // {{{
		//gets all the allowable relationships for the current entity.  It sets them up in $this->associations.  However,
		//$this->associations is not reliable if you're in the second level.  This might be worth fixing at some point.
		{
			// get allowable relationships
			$q = new DBSelector();
			$q->add_table( 'ar', 'allowable_relationship' );
			$q->add_table( 'e', 'entity' );
			$q->add_table( 'site_own_alrel', 'allowable_relationship' );
			$q->add_table( 'r', 'relationship' );

			$q->add_field( 'ar', '*' );
			$q->add_field( 'e', 'name', 'entity_name' );
			if( $var == 'default' )
				$q->add_relation( 'ar.relationship_a = '.$this->type_id );
			else
				$q->add_relation( 'ar.relationship_a = '.$this->request[ CM_VAR_PREFIX . 'type_id' ] );
			$q->add_relation( 'ar.relationship_b = e.id' );
			$q->add_relation( 'ar.name != "owns"' );
			$q->add_relation( 'ar.name != "borrows"' );
			$q->add_relation( 'ar.name NOT LIKE "%archive%"' );
			// make sure this site has access to the related type
			// we don't want to be able to associate with types that a site does not have access to
			$q->add_relation( 'site_own_alrel.relationship_a = '.id_of( 'site' ) );
			$q->add_relation( 'site_own_alrel.relationship_b = '.id_of( 'type' ) );
			$q->add_relation( 'site_own_alrel.name = "site_to_type"' );
			$q->add_relation( 'r.entity_a = '.$this->site_id );
			$q->add_relation( 'r.entity_b = ar.relationship_b' );
			$q->add_relation( 'r.type = site_own_alrel.id' );

			$q->add_relation('(ar.custom_associator IS NULL OR ar.custom_associator = "")');

			$r = db_query( $q->get_query(), 'Unable to get allowable relationships for this type.' );
			$x = array();
			while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
				$x[] = $row;
			$this->associations = $x;
			return $x;
		} // }}}
		/**
		 * Gets all relationships where current item is on left side
		 * @param $var makes sure current type is in ar.relationship_a by default...not sure if this is non-default somewhere
		 */
		function get_backward_rels( $var = 'default' ) // {{{
		//gets all the allowable relationships for the current entity.  It sets them up in $this->associations.  However,
		//$this->associations is not reliable if you're in the second level.  This might be worth fixing at some point.
		{

			// get allowable relationships
			$q = new DBSelector();
			$q->add_table( 'ar', 'allowable_relationship' );
			$q->add_table( 'e', 'entity' );
			$q->add_table( 'site_own_alrel', 'allowable_relationship' );
			$q->add_table( 'r', 'relationship' );

			$q->add_field( 'ar', '*' );
			$q->add_field( 'e', 'name', 'entity_name' );
			if( $var == 'default' )
				$q->add_relation( 'ar.relationship_b = '.$this->type_id );
			else
				$q->add_relation( 'ar.relationship_b = '.$this->request[ CM_VAR_PREFIX . 'type_id' ] );
			$q->add_relation( 'ar.relationship_a = e.id' );
			$q->add_relation( 'ar.directionality = "bidirectional"' );
			$q->add_relation( 'ar.name != "owns"' );
			$q->add_relation( 'ar.name != "borrows"' );
			$q->add_relation( 'ar.name NOT LIKE "%archive%"' );
			// make sure this site has access to the related type
			// we don't want to be able to associate with types that a site does not have access to
			$q->add_relation( 'site_own_alrel.relationship_a = '.id_of( 'site' ) );
			$q->add_relation( 'site_own_alrel.relationship_b = '.id_of( 'type' ) );
			$q->add_relation( 'site_own_alrel.name = "site_to_type"' );
			$q->add_relation( 'r.entity_a = '.$this->site_id );
			$q->add_relation( 'r.entity_b = ar.relationship_a' );
			$q->add_relation( 'r.type = site_own_alrel.id' );

			$q->add_relation('(ar.custom_associator IS NULL OR ar.custom_associator = "")');
			$r = db_query( $q->get_query(), 'Unable to get allowable relationships for this type.' );
			$x = array();
			while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
				$x[] = $row;
			$this->reverse_associations = $x;
			return $x;
		} // }}}
		function show_owns_links() // {{{
		//the main links to be shown in the leftbar if an id is present
		{
			if( empty( $this->request[ CM_VAR_PREFIX . 'type_id' ] ) )
				$this->show_owns_links_no_second_level();
			else
				$this->show_owns_links_second_level();
		} // }}}
		function show_owns_links_no_second_level( $links = false ) // {{{
		//main links for id.  Although the name says no_second_level, these links are actually used to display the furthest 
		//inside links.  In other words, we don't have to worry about there being a second level outside of this.
		{
			if( $links == false )
				$links = $this->get_main_links();
			foreach( $links AS $key => $value )
			{
				if( $this->is_selected( $key ) )
					echo '<li class="navItem navSelect"><strong>' . $value[ 'title' ] . '</strong></li>' . "\n";
				else
					echo '<li class="navItem"><a href="' . $value[ 'link' ] . '" class="nav">'.$value[ 'title' ].'</a></li>' . "\n";
			}
		} // }}}
		function show_owns_links_second_level() // {{{
		//the outside links for second level
		{
			$outside = $this->get_second_level_links();
			$inside = $this->get_main_links();
			foreach( $outside AS $key => $value )
			{
				if( $this->request[ CM_VAR_PREFIX . 'rel_id' ] == $key )
				{
					$e = new entity( $this->id );
					echo '<li class="navItem"><strong>' . $value[ 'title' ] . '(' .$e->get_value( 'name' ) . ')</strong></li>' . "\n";
					echo '<ul>';
					$this->show_owns_links_no_second_level( $inside );
					echo '</ul>';
				}
				else
					echo '<li class="navItem">' . $value[ 'title' ] . '</li>' . "\n";
			}
		} // }}}
		function get_main_links( $second = false ) // {{{
		//returns an array of the main links
		{
			$links = array();
			$links[ 'Preview' ] = array( 'title' => 'Preview' , 'link' => $this->make_link( array( 'cur_module' => 'Preview' ) ) );
			$links[ 'Edit' ] = array( 'title' => 'Edit' , 'link' => $this->make_link( array( 'cur_module' => 'Editor' ) ) );
			if( $second )
				$rels = $second;
			else
				$rels = $this->get_rels();
			foreach( $rels AS $rel )
			{
				$ass_name = !empty( $rel[ 'display_name' ] ) ? $rel[ 'display_name' ] : $rel[ 'entity_name' ];
				$index = $rel[ 'id' ];
				if( empty( $_SESSION[ 'assoc' ][ $this->site_id ][ $this->type_id ][ $rel[ 'id' ] ][ $this->id ] ) )
					$_SESSION[ 'assoc' ][ $this->site_id ][ $this->type_id ][ $rel[ 'id' ] ][ $this->id ] = $this->make_link( array( 
								'site_id' => $this->site_id, 
								'type_id' => $this->type_id,
								'rel_id' => $rel[ 'id' ],
								'id' => $this->id,
								'user_id' => $this->user_id,
								'cur_module' => 'Associator' ) );
				$links[ $index ] = array( 'title' => $ass_name , 
										  'link' => $_SESSION[ 'assoc' ][ $this->site_id ][ $this->type_id ][ $rel[ 'id' ] ][ $this->id ], 
										  'rel_info' => $rel );
			}
			if($second)
				$rels = $this->get_backward_rels( 'I AM A GOLDEN GOD!!!' );
			else
				$rels = $this->get_backward_rels();
			foreach( $rels AS $rel )
			{
				$ass_name = !empty( $rel[ 'display_name_reverse_direction' ] ) ? $rel[ 'display_name_reverse_direction' ] : $rel[ 'entity_name' ];
				$index = $rel[ 'id' ];
				if( empty( $_SESSION[ 'reverse_assoc' ][ $this->site_id ][ $this->type_id ][ $rel[ 'id' ] ][ $this->id ] ) )
					$_SESSION[ 'reverse_assoc' ][ $this->site_id ][ $this->type_id ][ $rel[ 'id' ] ][ $this->id ] = $this->make_link( array( 
								'site_id' => $this->site_id, 
								'type_id' => $this->type_id,
								'rel_id' => $rel[ 'id' ],
								'id' => $this->id,
								'user_id' => $this->user_id,
								'cur_module' => 'ReverseAssociator' ) );
				$links[ $index ] = array( 'title' => $ass_name , 
										  'link' => $_SESSION[ 'reverse_assoc' ][ $this->site_id ][ $this->type_id ][ $rel[ 'id' ] ][ $this->id ], 
										  'rel_info' => $rel );
			}
			$links[ 'Finish' ] = array( 'title' => '<strong>Finish</strong>' , 'link' => $this->make_link( array( 'cur_module' => 'Finish' ) ) );

			// if the entity is new, give the link to cancel its creation
			$e = new entity( $this->id );
			if( $e->get_value( 'new' ) )
			{
				$links[ 'Cancel' ] = array( 'title' => 'Cancel', 'link' => $this->make_link( array( 'cur_module' => 'Cancel' ) ) );
			}

			// This is a hack because the 'new_entity' variable is getting passed around a little too promiscuously.  Really newness should be stored in the db with the entity and removed upon finish. MR 3/11/2004 */
			/*$e = new entity( $this->id );
			$created = prettify_mysql_timestamp( $e->get_value('creation_date'), 'Y-m-d' );
			$today = date('Y-m-d');
			if( !empty( $this->request[ 'new_entity' ] ) && $created == $today )
				$links[ 'Cancel' ] = array( 'title' => 'Cancel' ,
											'link' => $this->make_link( array( 'cur_module' => 'Cancel' ) ) ); */
			return $links;
		} // }}}
		function get_second_level_links() // {{{
		//returns second level links
		{
			$rels = $this->get_rels( 'second' );
			$links = $this->get_main_links( $rels );
			return $links;
		} // }}}
		function show_borrows_links() // {{{
		//links to be shown for a borrowed item
		{
			$links = array();
			$links[ 'Preview' ] = $this->make_link( array( 'cur_module' => 'Preview' ) );
			$e = new entity( $this->id );
			if( !$e->get_value( 'no_share' ) )
			{
				if( site_borrows_entity( $this->site_id , $e->id() ) )
					$links[ 'Don\'t Borrow' ] = $this->make_link( array( 'cur_module' => 'DoBorrow' , 'unborrow' => 1 ) );
				else
					$links[ 'Borrow' ] = $this->make_link( array( 'cur_module' => 'DoBorrow' ) );
			}
			if( !empty( $this->request[ CM_VAR_PREFIX . 'id' ] ) )
			{
				foreach( $this->request AS $key => $val )
					if( substr( $key, 0, strlen( CM_VAR_PREFIX ) ) == CM_VAR_PREFIX )
					{
						$old_vars[ substr( $key, strlen( CM_VAR_PREFIX ) ) ] = $val;
						$old_vars[ $key ] = '';
					}
				$link = $this->make_link( $old_vars );
				$links[ 'Back to Associate Page' ] = $link;
			}
			else
				$links[ 'Back to Sharing Page' ] = $this->make_link( array( 'cur_module' => 'Sharing' , 'id' => '' ) );
			/*
			$rels = $this->get_rels();
			foreach( $rels AS $rel )
				$links[ $rel[ 'entity_name' ] ] = $this->make_link( array( 'cur_module' => 'Associator' , 'rel_id' => $rel[ 'id' ] ) );
*/
			foreach( $links AS $key => $value )
			{
				if( $this->is_selected( $key ) )
					echo '<li class="navItem navSelect"><strong>' . $key . '</strong></li>' . "\n";
				else
					echo '<li class="navItem"><a href="' . $value . '" class="nav">'.$key.'</a></li>' . "\n";
			}
		} // }}}
		function show_other_links_item() // {{{
		//other links, delete, finish, and cancel.  Cancel is only shown if new_entity is true.  
		//should put in logic so that delete is not always shown as well.  
		{
			echo '<p class="otherActionItems"><strong>Other Action Items</strong></p>';
			echo '<ul class="leftList">';
			if( $this->is_deletable() )
			{
				echo '<li class="navItem';
				if( $this->cur_module == 'Delete' )
					echo ' navSelect';
				echo '">';
				$page_name = 'Delete';
				if( $this->cur_module == 'Delete' )
					echo '<strong>'.$page_name.'</strong>';
				else
					echo '<a href="' . $this->make_link( array( 'cur_module' => 'Delete' ) ) . '" class="nav">'.$page_name.'</a>';
				echo '</li>';
			}
			else
			{
				echo '<li class="navItem';
				if( $this->cur_module == 'NoDelete' )
					echo ' navSelect';
				echo '">';
				$link = $this->make_link( array( 'cur_module' => 'NoDelete' ) );
				if( $this->cur_module != 'NoDelete' )
					echo 'Deletion Not Available <span class="smallText">(<a href="' . $link . '">Explain</a>)</span>';
				else echo 'Deletion Not Available';
				echo '</li>';
			}
			//echo '<li class="navItem"><a href="' . $this->make_link( array( 'cur_module' => 'Archive' ) ) . '" class="nav">History</a></li>';

			// get archive relationship id
			$q = 'SELECT id FROM allowable_relationship WHERE name LIKE "%archive%" AND relationship_a = '.$this->type_id.' AND relationship_b = '.$this->type_id;
			$r = db_query( $q, 'Unable to get archive relationship.' );
			$row = mysql_fetch_array( $r, MYSQL_ASSOC );
			mysql_free_result( $r );
			$rel_id = $row['id'];

			$es = new entity_selector();
			$es->add_type( $this->type_id );
			$es->add_right_relationship( $this->id, $rel_id );
			$es->set_order( 'last_modified DESC' );
			$archived = $es->run_one(false,'Archived','show_archived error in CM');

			$num_arch = count( $archived );
			if( $num_arch > 0 )
			{
				$selected = $this->cur_module == 'Archive' ? true : false;
				$page_name = 'History ('.$num_arch.' edit'.($num_arch == 1 ? '' : 's' ).')';
				echo '<li class="navItem';
				if( $selected )
					echo ' navSelect';
				echo '">';
				if( $selected )
					echo '<strong>'.$page_name.'</strong>';
				else
					echo '<a href="'.$this->make_link( array( 'cur_module' => 'Archive' ) ).'" class="nav">'.$page_name.'</a>';
			}
			else
				echo '<li class="navItem">No Edits</li>';
			echo '</ul>';
		} // }}}
		function is_deletable() // {{{
		{
			//get all one-to-many required relationships that the current item is a part of
			$dbq = $this->get_required_ar_dbq();
			$subject_of_required_rels = $dbq->run();
			$sites = get_sites_that_are_borrowing_entity($this->id);
			if( $subject_of_required_rels || !empty($sites) )
				return false;
			else
				return true;
		} // }}}
		function get_required_ar_dbq() // {{{
		{
			$dbq = new DBSelector;
			$dbq->add_table( 'ar' , 'allowable_relationship' );
			$dbq->add_table( 'r' , 'relationship' );
			$dbq->add_table( 'entity' );

			$dbq->add_field( 'ar' , '*' );
			$dbq->add_field( 'r' , 'entity_a' );
			$dbq->add_field( 'r' , 'entity_b' );
			$dbq->add_field( 'entity' , 'id' , 'e_id' );
			$dbq->add_field( 'entity' , 'name' , 'e_name' );
			
			$dbq->add_relation( 'ar.connections = "one_to_many"' );
			$dbq->add_relation( 'ar.required = "yes"' );

			$dbq->add_relation( 'r.entity_b = ' . $this->id );
			$dbq->add_relation( 'r.type = ar.id' );
			$dbq->add_relation( 'entity.id = r.entity_a' );
			$dbq->add_relation( 'entity.state = "Live"' );
			$dbq->add_relation( 'r.entity_b != r.entity_a' );

			return $dbq;
		} // }}}

	//main functions used for displaying page	
		// IN_MODULE
		function get_sites() // {{{
		//gets a list of sites.  used for sites() and sitebar()
		{
			$es = new entity_selector();
			$es->add_type( id_of('site') );
			$es->add_left_relationship( $this->user_id, relationship_id_of('site_to_user') );
			$es->set_order('entity.name ASC');
			$es->limit_tables();
			$es->limit_fields('entity.name');
			return $es->run_one();
		} // }}}
		// IN_MODULE
		function sites() // {{{
		//function is now only used if a site is not selected.  Otherwise, sitebar is used to show the site at the top of the page.
		{
			$sites = $this->get_sites();
			if( $sites )
			{
				echo '<div class="typeNav"><strong>Your Sites</strong>'."\n";
				echo '<ul class="leftList">'."\n";
				$master_admin_id = id_of('master_admin');
				if(array_key_exists($master_admin_id,$sites))
				{
					$this->show_site_list_item($sites[$master_admin_id],'masterAdmin');
					unset($sites[$master_admin_id]);
				}
				foreach( array_keys($sites) AS $site_id )
				{
					$this->show_site_list_item($sites[$site_id]);
				}
				echo '</ul>'."\n".'</div>'."\n";
			}
			else
			{
				echo 'You do not currently have access to your site(s) because you have not yet completed training.';
			}
		} // }}}
		function show_site_list_item($site, $class='')
		{
			echo '<li class="navItem';
			if(!empty($class))
				echo ' '.$class;
			echo '">';

			echo '<a href="'.$this->make_link( 
				array( 
						'site_id' => $site->id()
					 )
				).'" class="nav">'.$site->get_value('name').'</a></li>';
		}
		function sitebar() // {{{
		//if and entity is not selected, it shows a list of all the users sites in an option menu, otherwise just prints out
		//the name of the current site.  This is seen in the bar at the top of the page
		{
			?>
				<div class="sites"> 
				<?php
					if( !$this->id )
					{
						$sites = $this->get_sites();
						?>
						<form name="form1">
						Site: 
						<select name="menu1" onChange="MM_jumpMenu('parent',this,0)" class="siteMenu">
							<option value="">--</option>
						<?php
						$placeholder = '__this_is_the_site_id_placeholder__';
						$link_parts = explode($placeholder,$this->make_link( array( 'site_id' => $placeholder, 'type_id' => '', 'id' => '', 'rel_id' => '', 'lister' => '', 'cur_module' => '' ) , true ));
						if(empty($link_parts[1]))
							$link_parts[1] = '';
						foreach( array_keys($sites) AS $site_id )
						{
							echo '<option value="'.$link_parts[0].$site_id.$link_parts[1].'"';
							if( $site_id == $this->site_id )
								echo ' selected="selected"';
							echo '>' . $sites[$site_id]->get_value( 'name' ) . '</option>' . "\n";
						}
						$this->show[ 'sites' ] = false;
						?>
						</select>
						<?php
						$cur_site = $sites[ $this->site_id ];
						if( $cur_site->get_value( 'base_url' ) )
							echo '<a href="http://'.REASON_HOST.$cur_site->get_value('base_url').'" '.($this->user->get_value('site_window_pref') == 'Popup Window' ? 'target="_blank" ' : '').'class="publicSiteLink">Go to public site</a>';
						?>
						</form>
						<?php
					}
					else
					{
						$site = new entity($this->site_id);
						if($site->get_values())
						{
							echo 'Site: <strong>' . $site->get_value( 'name' ) . '</strong>' . "\n";
							if( $this->type_id )
							{
								$e = new entity( $this->type_id );
									echo '<strong> :: </strong>' . prettify_string( $e->get_value( 'name' ) );
								if( $this->id )
								{
									$e = new entity( $this->id );
										echo '<strong> :: </strong>' . $e->get_value( 'name' ) ;
								}
							}
						}
					}
				?>
				</div>
				<?php
		} // }}}
		function user_has_site_admin_privileges() //{{{
		{
			if( user_is_a( $this->user_id, id_of( 'contribute_only_role' ) ) )
				return false;
			return true; 
		} // }}}
		// IN_MODULE
		function types() // {{{
		//shows a list of types for a current site.  is called in leftbar_normal().
		{
			$es = new entity_selector( );
			$es->add_type( id_of('type') );
			$es->add_right_relationship( $this->site_id, relationship_id_of( 'site_to_type' ) );
			$es->set_order( 'entity.name ASC' );
			$types = $es->run_one();
			
			//remove the site_cannot_edit_type types
			$nes = new entity_selector( );
			$nes->add_type( id_of('type') );
			$nes->add_right_relationship( $this->site_id, relationship_id_of( 'site_cannot_edit_type' ) );
			$remove = $nes->run_one();
			
			foreach($remove as $id=>$vals)
			{
				unset($types[$id]);
			}

			if( $types )
			{
				?>
					<div class="typeNav"><strong>Add/Edit</strong>
					<ul class="leftList">		
				<?php
				
				$mpid = id_of('minisite_page');
				if(array_key_exists($mpid,$types))
				{
					$page_type_array[$mpid] = $types[id_of('minisite_page')];
					unset($types[$mpid]);
					$types = array_merge($page_type_array, $types);
				}
				
				reset( $types );
				while( list( ,$type ) = each( $types ) )
				{
					if( $type->id() == $this->type_id && $this->cur_module != 'Sharing' )
					{
						$cur_type = true;
					}
					else
						$cur_type = false;
						
					echo '<li class="navItem';
					if( $cur_type )
						echo ' navSelect';
					echo ' uid_'.$type->get_value('unique_name');
					echo '">';

					if(empty($_SESSION[ 'listers' ][ $this->site_id ][ $type->id() ]) )
						$_SESSION[ 'listers' ][ $this->site_id ][ $type->id() ] = $this->make_link( array( 
									'site_id' => $this->site_id, 
									'type_id' => $type->id() ,
									'cur_module' => 'Lister' ) );
					echo '<a href="'.$_SESSION[ 'listers' ][ $this->site_id ][ $type->id()  ].
						'" class="nav">'.($type->get_value('plural_name') ? $type->get_value( 'plural_name' ) : $type->get_value( 'name' )).'</a></li>';
				}
				echo '</ul></div>';
			}
		} // }}}
		// IN_MANAGER
		function leftbar_other() // {{{
		//other links for a current site.  The main ones appear in master admin, but there are some in other sites as well.
		{
			$es = new entity_selector();
			$es->add_type( id_of('admin_link') );
			$es->add_right_relationship( $this->site_id, relationship_id_of( 'site_to_admin_link' ) );
			$es->set_order( 'entity.name ASC' );
			$links = $es->run_one();
			if( $links )
			{
				echo '<div class="typeNav"><strong>Other Links</strong>';
				echo '<ul class="leftList">';
				foreach( $links AS $link )
				{	
					$url = ($link->get_value('relative_to_reason_http_base') == 'true') ?
						REASON_HTTP_BASE_PATH . $link->get_value('url') :
						$link->get_value('url');
					echo '<li class="navItem"><a href="'.$url.'" class="nav">'.$link->get_value('name').'</a></li>';
				}
				echo '</ul></div>'."\n";
			}
		} // }}}
		function site_tools() //{{{
		{
			$show_site_admin = false;
			$stats_link = $this->stats_link();
			if( $this->user_has_site_admin_privileges() )
				$show_site_admin = true;

			/* if( $show_site_admin OR ($stats_link AND $this->show[ 'stats' ] ) )
			{ */
				echo '<div class="typeNav"><strong>Site Tools</strong>';
				echo '<ul class="leftList">';
				if( $show_site_admin && $this->show[ 'themes' ]  )
				{
					$l = $this->make_link( array( 'cur_module' => 'ChooseTheme', 'type_id' => '' ) );
					echo '<li class="navItem';
					if( $this->cur_module == 'ChooseTheme' )
						echo ' navSelect';
					echo '"><a href="'.$l.'" class="nav">Themes</a></li>'."\n";
				}	
				if( $stats_link AND $this->show[ 'stats' ] )
				{
					echo '<li class="navItem"><a href="'.$stats_link.'" class="nav">Statistics</a></li>'."\n";
				}
				echo '<li class="navItem';
				if( $this->cur_module == 'ViewUsers' )
					echo ' navSelect';
				echo '"><a href="'.$this->make_link( array( 'cur_module' => 'ViewUsers', 'type_id' => '' ) ).'" class="nav">Users</a></li>'."\n";
				echo '</ul></div>'."\n";
			//}
		} // }}}
		// IN_MANAGER
		function stats_link() // {{{
		//generates link to stats page if there is one
		{
			if(defined('REASON_STATS_URI_BASE') && REASON_STATS_URI_BASE != '')
			{
				$site = new entity ( $this->site_id );
				if( $site->get_value( 'site_state' ) == 'Live' && $site->get_value( 'unique_name' ) )
				{
					$link = REASON_STATS_URI_BASE;
					$uname = posix_uname();
					$link .=  strtolower($uname['nodename']).'/';
					$link .= $_SERVER['HTTP_HOST'].'/';
					$link .= $site->get_value( 'unique_name' ).'/';
					return $link;
				}
			}
			return false;
		} // }}}
		// IN_MANAGER
		function sharing() // {{{
		//creates a list of all types a site can borrow from other sites
		{
			$sharables = $this->get_sharable_relationships();
			if( $sharables )
			{
				?>
					<div class="typeNav"><strong>Borrow</strong>
					<ul class="leftList">		
				<?php
					
				
				foreach( $sharables AS $type )
				{
					if( $type->id() == $this->type_id && !empty( $this->request[ 'cur_module' ] ) && ( $this->request[ 'cur_module' ] == 'Sharing' || $this->request[ 'cur_module' ] == 'DoBorrow' ) )
					{
						$cur_type = true;
					}
					else
						$cur_type = false;

					if(empty($_SESSION[ 'sharing_main' ][ $this->site_id ][ $type->id() ]) )
						$_SESSION[ 'sharing_main' ][ $this->site_id ][ $type->id() ] = $this->make_link( array( 
									'site_id' => $this->site_id, 
									'type_id' => $type->id() ,
									'user_id' => $this->user_id,
									'cur_module' => 'Sharing' ) );
						
					echo '<li class="navItem';
					if( $cur_type )
						echo ' navSelect';
					echo '">';

					echo '<a href="'. $_SESSION[ 'sharing_main' ][ $this->site_id ][ $type->id() ].
						'" class="nav">'.($type->get_value('plural_name') ? $type->get_value( 'plural_name' ) : $type->get_value( 'name' )).'</a></li>';
				}
				echo '</ul></div>';
			}
		} // }}}
		// IN_MANAGER
		function site_is_live() // {{{
		{
			$e = new entity( $this->site_id );
			if( $e->get_value( 'site_state' ) == "Live" )
				return true;
			return false;		
		} // }}}
		// IN_MANAGER
		function get_sharable_relationships() // {{{
		//returns an array of all sharable relationships.   This is based on two conditions. 1) The current site has access
		//to that type, 2) Some site that is not the current site shares this same type.  
		{
			$es = new entity_selector;
			$es->add_type( id_of( 'type' ) );

			$es->add_table( 'access' , 'allowable_relationship' );
			$es->add_table( 'access_rel' , 'relationship' );
			$es->add_table( 'shares' , 'allowable_relationship' );
			$es->add_table( 'shares_rel' , 'relationship' );

			//linking relations
			$es->add_relation( 'entity.id = access_rel.entity_b' );
			$es->add_relation( 'entity.id = shares_rel.entity_b' );
			$es->add_relation( 'access_rel.type = access.id' );
			$es->add_relation( 'shares_rel.type = shares.id' );
			
			//access relations
			$es->add_relation( 'access.name = "site_to_type"' );
			$es->add_relation( 'access_rel.entity_a = ' . $this->site_id );
			$es->add_relation( 'access_rel.entity_b = entity.id' );

			//sharing relations
			$es->add_relation( 'shares.name = "site_shares_type"' );
			$es->add_relation( 'shares_rel.entity_a != ' . $this->site_id );
			$es->add_relation( 'shares_rel.entity_b = entity.id' );

			if( $this->site_is_live() )
			{
				$es->add_table( 'site_table' , 'site' );
				$es->add_relation( 'shares_rel.entity_a = site_table.id' );
				$es->add_relation( 'site_table.site_state = "Live"' );
			}

			return $es->run_one();
		} // }}}
		function admin_tools() // {{{
		//only shown to admin users.  shows yet more links in the non-id sidebar.
		{
			if( $this->show[ 'admin_tools' ] )
			{
				// nwhite - should we really check this again??? - should not be set to true
				// 	    unless we have already verified the user is an admin
				// 	    if the if clause was removed admin users would see
				//   	    admin tools even when posing as a different user
				// 	    the main function right now is just to hide admin tools
				// 	    if an admin is posing as another user

				if( user_is_a( $this->user_id, id_of( 'admin_role' ) ) )
				{
					?>
					<div class="typeNav"><strong>Other Tools</strong>
					<ul class="leftList">		
					<?php
					$urls = array(
							'User Information' => 'user_info',
							'Show Session Vars' => 'show_session',
							'Kill Session Vars' => 'kill_session',
							'About Reason' => 'about_reason',
					);
					foreach( $urls AS $name => $module_name )
					{
						echo '<li class="navItem"><a href="'.$this->make_link( array( 'cur_module' => $module_name ) ).'" class="nav">'.$name.'</a></li>';
					}
					echo '</ul></div>';
				}
			}
		} // }}}
		function breadcrumbs() // {{{
		//not currently being used, but the code is here just in case we go back
		{
			$crumbs = $this->get_breadcrumbs();
			$num_crumbs = count( $crumbs );
			$seperator = '&nbsp;&raquo;&nbsp;';
			echo '<div class="smallText"><strong>You are here: <a href="'.$this->make_link(array('site_id' => '', 'type_id' => '', 'id' => '', 'cur_module' => '')).'">Reason Home</a>';
			if( $crumbs )
			{
				$i = 1;
				foreach( $crumbs AS $url => $name )
				{
					echo $seperator;

					if( $i == $num_crumbs )
						echo $name;
					else
						echo '<a href="'.$url.'">'.$name.'</a>';

					$i++;
				}
			}
			echo '</strong></div>';
		} // }}}
		function title() // {{{
		//title of the page
		{
			echo '<div class="header3">'.$this->title.'</div>';
		} // }}}
		function main_area() // {{{
		//displays the main area of the page.  First does it's own stuff then calls the module to do it's stuff.
		{
			?>
			<div class="contentArea">
			<?php
/*
			if( $this->show[ 'breadcrumbs' ] )
			{
				$this->breadcrumbs();
				echo '<br />';
			}
*/			
			
			if( $this->show[ 'title' ] )
			{
				$this->title();
			}

			$this->module->run();
			
			?>
			</div>
			<?php
		} // }}}
		function banner() // {{{
		//the top banner.  doesn't really do much except display some stuff and the show the user
		{
		?>
			<table width="100%" border="0" cellpadding="8" cellspacing="0" class="banner">
				<tr>
					
      <td class="crumbs"> <?php echo REASON_ADMIN_LOGO_MARKUP; ?> 
        <span style="white-space:nowrap"><strong>:: <a href="<?php echo $this->make_link(array('cur_module'=>'about_reason')); ?>" class="bannerLink">Reason <?php echo REASON_VERSION; ?></a> ::</strong> <?php echo REASON_TAGLINE; ?></span></td>
					<td class="id">
					<?php
						$this->show_user();
					?>
					</td>
				</tr>
			</table>
		<?php
		} // }}}
		function show_user() // {{{
		//if logged in user is an admin, displays a drop down of all users so that they can log in as that person (for debugging purposes).
		//otherwise, tells the user who they are
		{
			$show_logout = true;

			// if behind HTTP authentication the session lasts until the browser is closed and logout will not do anything
			if (isset($_SERVER['REMOTE_USER'])) $show_logout = false; 

			$this->user = $user = new entity( $this->authenticated_user_id );
			if( $user->has_left_relation_with_entity( new entity( id_of( 'admin_role') ) ) )
			{
				$es = new entity_selector();
				$es->add_type( id_of( 'user' ) );
				$es->set_order( 'name ASC' );
				$es->limit_tables();
				$es->limit_fields('name');
				$users = $es->run_one();
				
				echo '<form action="?" method="get">'."\n";
				echo '<label for="user_switch_select">User</label>: ';
				echo '<select name="user_id" class="siteMenu" id="user_switch_select">'."\n";
				echo '<option value="">--</option>'."\n";
				foreach( array_keys($users) AS $user_id )
				{
					//echo '<option value="'.$this->make_link( array( 'cur_module' => 'KillSession' , 'user_id' => $user->id() ) , false ) .'"';
					echo '<option value="'. $user_id . '"';
					if( $user_id == $this->user_id )
						echo ' selected="selected"';
					echo '>' . $users[$user_id]->get_value( 'name' ) . '</option>' . "\n";
				}
				echo '</select>';
				// TODO: support arrays in GET
				if(!empty($_GET))
				{
					foreach($_GET as $key=>$value)
					{
						if($key != 'user_id')
						{
							if(!is_array($value))
							{
								echo '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'" />'."\n";
							}
						}
					}
				}
				echo ' <input type="submit" name="go" value="go" />'."\n";
				if ($show_logout) echo ' <strong><a href="'.REASON_LOGIN_URL.'?logout=true" class="bannerLink">Logout</a></strong>';
				echo '</form>';
			}
			else
			{
				//You are  echo '<a href="'.$this->make_link(array('cur_module'=>'user_info')).'" class="idLink">'.$user->get_value( 'name' );</a>
				echo 'You are <strong>' . $user->get_value( 'name' ) .'</strong>';
				if ($show_logout) echo ': <strong><a href="'.REASON_LOGIN_URL.'?logout=true" class="bannerLink">Logout</a></strong>';
			}

		} // }}}
		function head() // {{{
		//page head.  prints out basic top html stuff
		{
			echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
			echo '<html>'."\n";
			echo '<head>'."\n";
			echo '<title>Reason';
			if( !empty( $this->site_id ) )
				echo ': '.strip_tags( $this->get_name( $this->site_id ) );
			if( !empty( $this->title ) AND !empty( $this->site_id ) AND $this->title != $this->get_name( $this->site_id ) )
				echo ': '.strip_tags($this->title);
			echo '</title>'."\n";
			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
			if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
			{
				echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
			}
			echo '<link rel="stylesheet" type="text/css" href="'.REASON_ADMIN_CSS_DIRECTORY.'admin.css" />'."\n";
			if ($this->cur_module != 'Sorting')
			{
				//echo '<script language="JavaScript" type="text/javaScript" src="'.WEB_JAVASCRIPT_PATH.'modified_form.js"></script>'."\n";
			}
			if( !empty( $this->style_sheet[ $this->cur_module ] ) )
				echo '<link rel="stylesheet" type="text/css" href="'.REASON_ADMIN_CSS_DIRECTORY.$this->style_sheet[ $this->cur_module ].'" />' . "\n";
			if( !empty( $this->script[ $this->cur_module ] ) )
				echo '<script language="JavaScript" type="text/javaScript" src="'.WEB_JAVASCRIPT_PATH.$this->script[ $this->cur_module ] .'"></script>'."\n";
			if (!isset($_SERVER['REMOTE_USER']) && USE_JS_LOGOUT_TIMER) // if we are not logged in via http authentication
			{
				echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'css/timer.css'.'" />'. "\n";
				echo '<script language="JavaScript" type="text/javaScript" src="'.WEB_JAVASCRIPT_PATH.'timer/timer.js.php"></script>'."\n";
			}
			echo '<script language="JavaScript" type="text/javaScript" src="'.WEB_JAVASCRIPT_PATH.'collapse.js"></script>'."\n";
			
		?>
		<script language="JavaScript" type="text/JavaScript">
		<!--
		function MM_jumpMenu(targ,selObj,restore){ //v3.0
		  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
		  if (restore) selObj.selectedIndex=0;
		}
		//-->
		</script>
	</head>
	<body>
		<?php
		if( $this->show[ 'banner' ] )
			$this->banner();
		if( $this->show[ 'sitebar' ] )
			$this->sitebar();
		?>
		<table cellpadding="0" cellspacing="0" border="0" id="adminLayoutTable">
			<tr>
				<td valign="top" width="20%">
			<?php
		} // }}}
		function finish_page() // {{{
		{
			
		} // }}}
		function foot() // {{{
		//bottow o' page
		{
			?>
					</td>
				</tr>
			</table>
		</body>
	</html>
			<?php
		} // }}}
		function new_column() // {{{
		//creates a new column
		{
			?>
				</td>
				<td valign="top">
			<?php
		} // }}}

		function make_link( $params = '', $pass_rest = false ) // {{{
		//this function should ALWAYS be used when creating a link in the admin site.  
		//the first parameter should be an array of the form array( id => 4 , lister => 4532 , ... ).  This function 
		//will make a link out of the these items.  There are some default args set up at the top of this page.  
		//These links will always be passed through regardless.  All other variables will only be passed if they have a value.
		//the second parameter determines whether or not to keep the rest of the request variables (those that are not 
		//in default) as well.  Using this function guarantees that you will keep all essential info from page to page.
		{
			$default_args = array();
			foreach( $this->default_args AS $arg )
				$default_args[ $arg ] = isset( $this->request[ $arg ] ) ? $this->request[ $arg ] : '';

			$old_args = array();
			foreach( $this->request AS $k => $v )
			{
				if( substr( $k , 0 , strlen( CM_VAR_PREFIX ) ) == CM_VAR_PREFIX ) 
				{
					if( !empty( $v ) )
						$old_args[ $k ] = $v;
				}
			}
								  
			$params = array_merge( $default_args, $old_args , $params );
			if( $pass_rest )
				$params = array_merge( $this->request, $params );
			if( empty( $params ) )
				$params = array();

			$link = '';
			foreach( $params AS $key => $val )
			{
				if( isset( $default_args[ $key ] ) OR !empty( $val ) ) //we need to get anything through that is in default args or has a value
				{
					$link .= '&amp;'.$key.'='.$val;
				}
			}
			$link = substr( $link, strlen( '&amp;' ) );
			return $_SERVER['PHP_SELF'].'?'.$link;
		} // }}}
		/**
		 * Initializes the admin page.
		 * 
		 * Identifies which admin module to use, instantiates, and initializes it.
		 * This method uses the $GLOBALS['_reason_admin_modules'] array defined in admin_module.php
		 * to determine which module to run.
		 *
		 * @return void
		 */
		function init() // {{{
		//basic init function.  called before anything is displayed.  does its own stuff and then calls the modules
		//init function
		{
			if( !empty($this->cur_module) )
			{
				if(
					array_key_exists($this->cur_module, $GLOBALS['_reason_admin_modules'])
					&&
					!empty($GLOBALS['_reason_admin_modules'][$this->cur_module]['file'])
				)
				{
					reason_include_once('classes/admin/modules/'.$GLOBALS['_reason_admin_modules'][$this->cur_module]['file']);
					if( !empty($GLOBALS['_reason_admin_modules'][$this->cur_module]['class']) && class_exists( $GLOBALS['_reason_admin_modules'][$this->cur_module]['class'] ) )
					{
						$module_name = $GLOBALS['_reason_admin_modules'][$this->cur_module]['class'];
					}
					else
					{
						trigger_error('Class '.$this->cur_module.'Module not found');
					}
				}
			}
			if( empty($module_name) )
			{
				if( $this->site_id )
				{
					if( $this->type_id )
					{
						if( $this->id OR $this->cur_module == 'Editor')
						{
							reason_include_once( 'classes/admin/modules/'.$GLOBALS['_reason_admin_modules']['Editor']['file'] );
							$module_name = $GLOBALS['_reason_admin_modules']['Editor']['class'];
						}
						else
						{
							reason_include_once( 'classes/admin/modules/'.$GLOBALS['_reason_admin_modules']['Lister']['file'] );
							$module_name = $GLOBALS['_reason_admin_modules']['Lister']['class'];
						}
					}
					else
					{
						reason_include_once( 'classes/admin/modules/'.$GLOBALS['_reason_admin_modules']['Site']['file'] );
						$module_name = $GLOBALS['_reason_admin_modules']['Site']['class'];
					}
				}
				else
				{
					reason_include_once( 'classes/admin/modules/'.$GLOBALS['_reason_admin_modules']['Default']['file'] );
					$module_name = $GLOBALS['_reason_admin_modules']['Default']['class'];
				}
			}
			if(class_exists( $module_name ) )
			{
				$this->module = new $module_name( $this );
				$this->module->init();
			}
			else
			{
				trigger_error('Class '.$module_name.' not found', HIGH);
			}
		} // }}}
		function check_errors( $user ) // {{{
		//checks to make sure user has access to current site, otherwise, sends him home.
		{
			$error_messages = array(
									'site_to_user' => 'You do not have access to this site.',
									'site_to_type' => 'This site does not have access to this type.',
									'type_to_id' => 'The entity you have chosen does not match the type.',
									'site_to_id' => 'This site does not have access to this entity.',
									'site_owns_id' => 'This site does not own this entity.',
								   );
			$message = '';
			if( !$this->verify_user( $user ) )
				$message = $error_messages[ 'site_to_user' ];
			elseif( !$this->site_to_type() )
				$message = $error_messages[ 'site_to_type' ];
			elseif( !$this->type_to_id() )
				$message = $error_messages[ 'type_to_id' ];
			//elseif( !$this->site_to_id() )
			//	$message = $error_messages[ 'site_to_id' ];
			elseif( !$this->site_owns_id() )
				$message = $error_messages[ 'site_owns_id' ];
				
			if( $message )
			{
				ob_flush();
				$link = 'index.php';
				if( $this->user_id ) 
					$link .= '?user_id=' . $this->user_id;
				die( $message . '  <a href="'.$link.'">Reason Home</a>.' );
			}
		} // }}}
		// method to find oldest pending item for a site and type
		function get_oldest_pending_entity( $sid, $tid, $id = '', $start_datetime = '' ) // {{{
		{
			// note: get items that are pending and NOT new.  most items that are pending and new
			// are just garbage.

			$es = new entity_selector( $sid );			// select site
			$es->add_type( $tid );						// select type
			$es->add_relation( 'new != 1' );			// make sure it's not new
			if( !empty( $id ) )
				$es->add_relation( 'entity.id > '.$id );
			if( !empty( $start_datetime ) )
				$es->add_relation( 'last_modified >= "'.$start_datetime.'"' );
			$es->set_num( 1 );							// just get one result
			$es->set_order( 'last_modified ASC, entity.id ASC' );		// order by last modified to get oldest
			$tmp = $es->run_one(false,'Pending', 'Unable to get oldest pending entity for this type' );
			list( ,$e ) = each( $tmp );
			return $e;
		} // }}}
		
		function verify_user( $user ) // {{{
		{
			return user_can_edit_site( $user->id(), $this->site_id );
		} // }}}
		function site_to_type() // {{{
		{
			if( $this->site_id && $this->type_id )
			{
				$d = new DBSelector;
				$d->add_table( 'ar' , 'allowable_relationship' );
				$d->add_table( 'r' , 'relationship' );

				$d->add_relation( 'ar.id = r.type' );

				$d->add_relation( 'ar.name = "site_to_type"' );
				$d->add_relation( 'r.entity_a = ' . $this->site_id );
				$d->add_relation( 'r.entity_b = ' . $this->type_id );
				if( $d->run() )
					return true;
				else
					return false;
			}
			return true;
		} // }}}
		function type_to_id() // {{{
		{
			if( $this->type_id && $this->id )

			{
				$e = new entity( $this->id );
				return ( $e->get_value( 'type' ) == $this->type_id );
			}
			return true;
		} // }}}
		function site_to_id() // {{{
		{
			if( $this->site_id && $this->id )
			{
				$d = new DBSelector;
				$d->add_table( 'ar' , 'allowable_relationship' );
				$d->add_table( 'r' , 'relationship' );

				$d->add_relation( 'ar.id = r.type' );
				$d->add_relation( 'r.entity_b = ' . $this->id );
				$d->add_relation( '( (ar.name = "owns" AND r.entity_a = '. $this->site_id. 
										') OR ( ar.name = "borrows" AND r.entity_a != '.$this->site_id.' ) )' );
				//the last relation makes sure the entity is owned by the site or is shared by another
				if( $d->run() )
					return true;
				else
					return false;
			}
			return true;
		} // }}}
		function site_owns_id() // {{{
		{
			if( $this->id && $this->site_id && empty( $this->request[ 'new_entity' ] ) && $this->cur_module 
					&& ( $this->cur_module == 'Editor' || $this->cur_module == 'Associator' ) )
			{
				$es = new entity_selector( $this->site_id );
				$es->add_type( $this->type_id );
				$es->add_relation( 'entity.id = ' . $this->id );
				$es->set_sharing( 'owns' );
				if( $es->run_one('','All') )
					return true;
				else
					return false;
			}
			return true;
		} // }}}
		
		function run() // {{{
		//does it's thang
		{
			$this->init();

			$this->head();
		
			if( $this->show[ 'leftbar' ] )
			{
				$this->leftbar();
				$this->new_column();
			}

			if( $this->show[ 'main' ] )
				$this->main_area();
			$this->foot();
		} // }}}
		function select_user() // {{{
		//changes user id to the requested user if user is an admin.  
		//otherwise sets $this->user_id to $this->authenticated_user_id
		{
			// FORMER WAY OF DOING THINGS - DEPRECATED
			// just set this up initially.
			// $_SESSION[ 'ORIG_REMOTE_USER' ] = $_SERVER[ 'REMOTE_USER' ];
			// if( !setcookie( 'REMOTE_USER', $this->authenticated_user_id, 0, '/',$_SERVER[ 'HTTP_HOST' ], 0 ) )
			// trigger_error( 'Unable to set remote user cookie' );

			$user = new entity( $this->authenticated_user_id );
			$is_admin = $user->has_left_relation_with_entity( new entity( id_of( 'admin_role' ) ) );
			if ($is_admin) $this->set_show( 'admin_tools', true );

			// if user id specified in query is not authenticated user
			if (!empty($this->user_id) && ($this->authenticated_user_id != $this->user_id ) && ($is_admin))
			{
					$user = new entity($this->user_id);
			}
			else $this->user_id = $this->authenticated_user_id;

			if( empty( $this->site_id ) )
				$this->show[ 'sitebar' ] = false;
			else
				$this->check_errors( $user );
		} // }}}

		function show_page() // {{{
		//for debugging.  displays all vars in admin page.
		{
			echo '<pre>';
			print_r($this);
			echo '</pre>';
		} // }}}
	}
?>
