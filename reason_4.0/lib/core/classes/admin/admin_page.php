<?php
/**
 * Admin Page
 *
 * Wraps up most of the functionality of the Reason Admin
 *
 * @package reason
 * @subpackage admin
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/viewer.php' );
reason_include_once( 'classes/entity_selector.php' );

/**
 * We check for the old style admin_module.php file and trigger an error if it is still being used
 * @todo remove this backwards compatibility check by Reason 4 RC 1
 */
if (reason_file_exists('classes/admin/admin_module.php'))
{
	trigger_error('You are using an admin_module.php file to define administrative modules. 
				   The classes/admin/admin_module.php file should be removed from the core and local classes/admin/ directories, 
				   and config/admin_modules/setup.php and config/admin_module/setup_local.php used instead. 
				   You may need to create a config/admin_modules/setup_local.php file in the local side of the core/local 
				   split if you have made any changes or additions to the now deprecated admin_module.php file. 
				   See this changelog post for further information: 
				   https://apps.carleton.edu/opensource/reason/developers/changes/?story_id=652065');
	reason_include_once( 'classes/admin/admin_module.php' );
}
else
{
	reason_include_once( 'config/admin_modules/setup.php' );
}
reason_include_once( 'classes/head_items.php' );
reason_include_once( 'function_libraries/user_functions.php' );

/**
 * Admin Page
 * 
 * Wraps up most of the functionality of the Reason Admin
 * 
 * @todo completely overhaul internal workings of this class -- it is a real mess
 * @todo separate output and logic -- fold most of html into either a single separate template or into several different templates that work similarly
 * 
 * @author Dave Hendler, Brendon Stanton, Matt Ryan, Nate White, probably others
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
	var $module_name;

	var $head_items; //head items object
	
	// logged in user
	var $authenticated_user_id = false;
	
	/**
	 * Sites the current user has access to
	 *
	 * Use method get_sites() for this information 
	 *
	 * @var array
	 */
	protected $user_access_sites;

	//default args will be always passed on admin pages
	var $default_args = array('site_id',
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
	
	/** 
	 * Sets up the authenticated_user_id class variable
	 * @return authenticated_user_id
	 */
	function authenticate()
	{
		if ($this->authenticated_user_id == false) 
		{
			$user_netid = reason_require_authentication();
			$this->authenticated_user_id = (empty($user_netid)) ? false : get_user_id($user_netid);
		}
		return $this->authenticated_user_id;
	}
	
	function load_params() // {{{
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

		$request = carl_get_request();
		$this->request = array_merge($request, carl_clean_vars($request, $param_cleanup_rules));
		
		foreach ( $params_to_localize as $v )
		{
			if (isset($this->request[$v]))
				$this->$v = $this->request[$v]; 
		}
		
		// verify that id corresponds to an entity
		if ($this->id > 0)
		{
			$e = new entity($this->id);
			$values = $e->get_values();
			if (empty($values))
			{
				trigger_error('Malformed request from '.$_SERVER['HTTP_REFERER'] .' (ID given does not correspond to an entity)');
				$this->id = '';
			}
		}
	
		if( isset( $this->request[ 'PHPSESSID' ] ) )
			unset( $this->request[ 'PHPSESSID' ] );			
		
		$old_id = !empty( $this->request[ CM_VAR_PREFIX . 'id' ])  ? $this->request[ CM_VAR_PREFIX . 'id' ] : false;
		$id = !empty($this->request['id']) ? $this->request['id'] : false;
		if( $old_id && $id && $id == $old_id )
		{
			$new_link = carl_construct_redirect($this->get_default_args());
			header('Location: '.$new_link);
			echo '<p>Attempted to redirect to <a href=' . htmlspecialchars($new_link,ENT_QUOTES) . '>here</a>, but seem to have failed.</p>';
			die();
		}
		
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
		if( empty( $this->id ) || empty($this->site_id))
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
			echo $this->_get_site_name_block();
			if( $this->show[ 'types' ] )
			{
				$this->types();
			}
			/* if( $this->show[ 'sharing' ] && reason_user_has_privs($this->user_id, 'borrow') )
			{
				$this->sharing();
			} */
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
		echo '<div class="managerNav">';
		echo '<div class="roundedTop"> <img src="'. REASON_ADMIN_IMAGES_DIRECTORY .'trans.gif" alt="" class="roundedCorner" />';
		echo '</div>'. "\n";
		echo '<div class="managerList">';
		if( !empty( $this->request[ CM_VAR_PREFIX . 'id' ] ) )
		{
			$old_name = new entity( $this->request[ CM_VAR_PREFIX . 'id' ] );
		}
		$item = new entity( $this->id );
		$name = isset( $old_name ) ? $old_name->get_value( 'name' ) : $item->get_value( 'name' );
		$name = ($name OR (strlen($name) > 0)) ? $name : '<em>New Item</em>';
		echo '<strong>' . $name . '</strong><br />';
		echo '<ul class="leftList'. ( isset( $old_name ) ? ' outer' : '' ) .'">';
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
		echo '</div>';
		echo '<div class="roundedBottom"> <img src="'. REASON_ADMIN_IMAGES_DIRECTORY .'trans.gif" alt="" class="roundedCorner" />'; 
		echo '</div>';	
		echo '</div>';
	}
	
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
			if( $this->rel_id == $item )
					return true;
				else
					return false;
		} // }}}
		if( $this->cur_module == 'ReverseAssociator' ) // {{{
		{
			if( $this->rel_id == $item )
					return true;
				else
					return false;
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
		if (reason_relationship_names_are_unique())
		{
			$q->add_relation( 'ar.type = "association"' );
			$q->set_order( 'ar.id ASC' );
		}
		else
		{
			$q->add_relation( 'ar.name != "owns"' );
			$q->add_relation( 'ar.name != "borrows"' );
			$q->add_relation( 'ar.name NOT LIKE "%archive%"' );
		}
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
		if (reason_relationship_names_are_unique())
		{
			$q->add_relation( 'ar.type = "association"' );
			$q->set_order( 'ar.id ASC' );
		}
		else
		{
			$q->add_relation( 'ar.name != "owns"' );
			$q->add_relation( 'ar.name != "borrows"' );
			$q->add_relation( 'ar.name NOT LIKE "%archive%"' );
		}
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
		$entity = new entity($this->id);
		$links[ 'Preview' ] = array( 'title' => 'Preview' , 'link' => $this->make_link( array( 'cur_module' => 'Preview' ) ) );
		$can_edit = ($entity->get_value('state') == 'Pending') ? reason_user_has_privs($this->user_id, 'edit_pending') : reason_user_has_privs($this->user_id, 'edit');
		if($can_edit && reason_site_can_edit_type($this->site_id, $this->type_id))
		{
			$links[ 'Edit' ] = array( 'title' => 'Edit' , 'link' => $this->make_link( array( 'cur_module' => 'Editor' ) ) );
			if( $second )
				$rels = $second;
			else
				$rels = $this->get_rels();
			foreach( $rels AS $rel )
			{
				$ass_name = !empty( $rel[ 'display_name' ] ) ? $rel[ 'display_name' ] : $rel[ 'entity_name' ];
				$index = $rel[ 'id' ];
				$links[ $index ] = array( 'title' => $ass_name , 
										  'link' => $this->make_link( array( 
											'site_id' => $this->site_id, 
											'type_id' => $this->type_id,
											'rel_id' => $rel[ 'id' ],
											'id' => $this->id,
											'user_id' => $this->user_id,
											'cur_module' => 'Associator' ) ),
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
				
				$links[ $index ] = array( 'title' => $ass_name , 
										  'link' => $this->make_link( array( 
											'site_id' => $this->site_id, 
											'type_id' => $this->type_id,
											'rel_id' => $rel[ 'id' ],
											'id' => $this->id,
											'user_id' => $this->user_id,
											'cur_module' => 'ReverseAssociator' ) ), 
										  'rel_info' => $rel );
			}
		}
		$links[ 'Finish' ] = array( 'title' => '<strong>Finish</strong>' , 'link' => $this->make_link( array( 'cur_module' => 'Finish' ) ) );

		// if the entity is new, give the link to cancel its creation
		$e = new entity( $this->id );
		if( $e->get_value( 'new' ) && $e->get_value('state') == 'Pending' && $can_edit && !$e->get_value('name') &&  $this->cur_module == 'Editor' )
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
		$show_delete = false;
		$show_history = false;
		
		$item = new entity($this->id);
		if($item->get_value('state') == 'Pending')
		{
			$show_delete = reason_user_has_privs($this->user_id, 'delete_pending');
			$show_history = reason_user_has_privs($this->user_id, 'edit_pending');
		}
		else
		{
			$show_delete = reason_user_has_privs($this->user_id, 'delete');
			$show_history = reason_user_has_privs($this->user_id, 'edit');
		}
		if(!$show_delete && !$show_history)
		{
			return;
		}
		echo '<div class="otherActionItems">'."\n";
		echo '<p class="otherActionItems"><strong>Other Action Items</strong></p>'."\n";
		echo '<ul class="leftList">'."\n";
		
		if($show_delete)
		{
			if( $this->is_deletable() )
			{
				echo '<li class="navItem';
				if( $this->cur_module == 'Delete' )
					echo ' navSelect';
				echo '">';
				$page_name = 'Delete';
				if( $this->cur_module == 'Delete' )
					echo '<strong>'.$page_name.'</strong>';
				elseif($item->get_value('state') == 'Deleted')
					echo '<a href="' . $this->make_link( array( 'cur_module' => 'Undelete' ) ) . '" class="nav">Undelete</a>';
				else
					echo '<a href="' . $this->make_link( array( 'cur_module' => 'Delete' ) ) . '" class="nav">'.$page_name.'</a>';
				echo '</li>' . "\n";
			}
			else
			{
				echo '<li class="navItem';
				if( $this->cur_module == 'NoDelete' )
					echo ' navSelect';
				echo '">';
				$link = $this->make_link( array( 'cur_module' => 'NoDelete' ) );
				if( $this->cur_module != 'NoDelete' )
					echo 'Deletion Not Available <span class="smallText">(<a href="' . $link . '"  class="inline">Explain</a>)</span>';
				else echo 'Deletion Not Available';
				echo '</li>' . "\n";
			}
		}
		
		if($show_history)
		{
			// get archive relationship id
			$num_arch = $this->_get_archived_item_count($this->id, $this->type_id);
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
				echo '</li>'."\n";
			}
			else
				echo '<li class="navItem">No Edits</li>' . "\n";
		}
		echo '</ul>'."\n";
		echo '</div>'."\n";
	} // }}}
	
	function _get_archived_item_count($id, $type_id)
	{
		$rel_id = reason_get_archive_relationship_id($type_id);

		$es = new entity_selector();
		$es->add_type( $type_id );
		$es->add_right_relationship( $id, $rel_id );
		$es->limit_tables();
		$es->limit_fields();
		$archived = $es->run_one(false,'Archived','show_archived error in CM');

		return count( $archived );
	}
	function is_deletable($id = 0) // {{{
	{
		$id = (integer) $id;
		if(empty($id))
			$id = $this->id;
		if(empty($id))
			return false;
		//get all one-to-many required relationships that the current item is a part of
		$subject_of_required_rels = array();
		$dbq = $this->get_required_ar_dbq($id);
		if(!empty($dbq))
			$subject_of_required_rels = $dbq->run();
		$sites = get_sites_that_are_borrowing_entity($id);
		if( $subject_of_required_rels || !empty($sites) )
			return false;
		else
			return true;
	} // }}}
	function get_required_ar_dbq($id = 0) // {{{
	{
		$id = (integer) $id;
		if(empty($id))
			$id = $this->id;
		if(empty($id))
			return false;
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

		$dbq->add_relation( 'r.entity_b = ' . $id );
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
		if(!isset($this->user_access_sites))
		{
			$es = new entity_selector();
			$es->add_type( id_of('site') );
			$es->add_left_relationship( $this->user_id, relationship_id_of('site_to_user') );
			$es->set_order('entity.name ASC');
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$this->user_access_sites = $es->run_one();
		}
		return $this->user_access_sites; 
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
			).'" class="nav">'.$site->get_value('name').'</a></li>' . "\n";
	}
	function sitebar() // {{{
	//if and entity is not selected, it shows a list of all the users sites in an option menu, otherwise just prints out
	//the name of the current site.  This is seen in the bar at the top of the page
	{
		echo '<div class="sites">'; 
		if( !$this->id )
		{
			$sites = $this->get_sites();
			echo '<form action="?" name="siteSwitchSelect" class="jumpNavigation" method="get">'. "\n";
			echo 'Site: <select name="site_id" class="jumpDestination siteMenu">' . "\n";
			echo '<option value="">--</option>'. "\n";
			foreach( array_keys($sites) AS $site_id )
			{
				echo '<option value="'.$site_id.'"';
				if( $site_id == $this->site_id ) echo ' selected="selected"';
				echo '>' . strip_tags($sites[$site_id]->get_value( 'name' )) . '</option>' . "\n";
			}
			$this->show[ 'sites' ] = false;
			echo '</select>';
			if(isset($_GET['user_id']) && !empty($_GET['user_id']))
			{
				$user_id = turn_into_int($_GET['user_id']);
				if (!empty($user_id)) echo '<input type="hidden" name="user_id" value="'.$user_id.'" />';
			}
			echo '<input type="submit" class="jumpNavigationGo" value="go" />';
			$cur_site = $sites[ $this->site_id ];
			$cur_site_base_url = $cur_site->get_value( 'base_url' );
			$cur_site_unique_name = $cur_site->get_value( 'unique_name' );
			$user = new entity($this->user_id);
			$target = ($user->get_value('site_window_pref') == 'Popup Window') ? 'target="_blank" ' : '';
			if(!empty($cur_site_base_url) && ($cur_site_unique_name != 'master_admin') ) 
			{
				echo '<a href="http://'.REASON_HOST.$cur_site_base_url.'" '.$target.'class="publicSiteLink">Go to public site</a>';
			}
			echo '</form>';
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
		echo '</div>';
	}
	/**
	 * @deprecated
	 */
	function user_has_site_admin_privileges() //{{{
	{
		trigger_error('admin_page->user_has_site_admin_privileges() is DEPRECATED. Please use reason_user_has_privs() function instead!');
		return true; 
	} // }}}
	function _get_site_name_block()
	{
		$site = new entity($this->site_id);
		if('SiteModule' == $this->module_name)
		{
			
			return '<h1 class="siteName"><strong>'.$site->get_value( 'name' ).'</strong></h1>'."\n";
		}
		$link = $this->make_link( array( 
				'site_id' => $this->site_id,
				'type_id' => '',
				'user_id' => $this->user_id, 
				'cur_module' => '', ) );
		return '<h1 class="siteName"><a href="'.$link.'">'.$site->get_value( 'name' ).'</a></h1>'."\n";
	}
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
		
		$sharables = $this->get_sharable_relationships();
		
		$can_borrow = reason_user_has_privs($this->user_id, 'borrow');
		foreach($remove as $id=>$vals)
		{
			if(!$can_borrow || !isset($sharables[$id]))
				unset($types[$id]);
		}

		if( $types )
		{
			echo '<div class="typeNav">'."\n";
			echo '<ul class="leftList">' . "\n";
			$mpid = id_of('minisite_page');
			if(array_key_exists($mpid,$types))
			{
				$page_type_array[$mpid] = $types[id_of('minisite_page')];
				unset($types[$mpid]);
				$types = array_merge($page_type_array, $types);
			}
			
			foreach( $types as $type )
			{
				if( $type->id() == $this->type_id )
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

				
				if(isset($remove[$type->id()]) )
				{
					$link_url = $this->get_borrowed_list_link($type->id());
				}
				else
				{
					$link_url = $this->get_owned_list_link($type->id());
				}
				echo '<a href="'.$link_url.'" class="nav">';
				echo '<img src="'.reason_get_type_icon_url($type).'" alt="" />';
				$type_name = $type->get_value('plural_name') ? $type->get_value( 'plural_name' ) : $type->get_value( 'name' );
				echo '<span class="typeName">' . $type_name . '</span>';
				echo '</a></li>' . "\n";
			}
			echo '</ul></div>';
		}
	} // }}}
	function get_borrowed_list_link($type_id)
	{
		return $this->make_link( array( 
				'site_id' => $this->site_id, 
				'type_id' => $type_id ,
				'user_id' => $this->user_id,
				'cur_module' => 'Sharing' ,
				'state' => 'live') );
	}
	function get_owned_list_link($type_id)
	{
		return $this->make_link( array( 
				'site_id' => $this->site_id, 
				'type_id' => $type_id ,
				'cur_module' => 'Lister' ,
				'state' => 'live') );
	}
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
				
				// lets add the current site_id if add_dynamic_site_id is set to true ... we do this in a very naive way.
				if ($link->has_value('add_dynamic_site_id') && ($link->get_value('add_dynamic_site_id') == 'true')) // if the script has been run and add_dynamic_site_id is true
				{
					$pos = carl_strpos($url, '?');
					if ($pos !== false) // we have an existing query string
					{
						$base = ($pos > 0) ? carl_substr($url, 0, $pos) : '';
						$qs = carl_substr($url, ($pos+1));
						$qs = str_replace('&amp;','&',$qs);
						parse_str($qs, $items);
					}
					else
					{
						$base = $url;
					}
					$items['site_id'] = $this->site_id;
					$qs = http_build_query($items);
					$url = $base . '?' . $qs;
				}					
				echo '<li class="navItem"><a href="'.$url.'" class="nav">'.$link->get_value('name').'</a></li>' . "\n";
			}
			echo '</ul></div>'."\n";
		}
	} // }}}
	function site_tools() //{{{
	{
		$stats_link = $this->stats_link();

		/* if( $show_site_admin OR ($stats_link AND $this->show[ 'stats' ] ) )
		{ */
			echo '<div class="typeNav"><strong>Site Tools</strong>';
			echo '<ul class="leftList">';
			if( $this->show[ 'themes' ]  )
			{
				$l = $this->make_link( array( 'cur_module' => 'ChooseTheme', 'type_id' => '' ) );
				echo '<li class="navItem';
				if( $this->cur_module == 'ChooseTheme' )
					echo ' navSelect';
				echo '"><a href="'.$l.'" class="nav"><img src="'.REASON_HTTP_BASE_PATH.'ui_images/types/theme_type.png" alt="" />Themes</a></li>'."\n";
			}	
			if( $stats_link AND $this->show[ 'stats' ] )
			{
				echo '<li class="navItem"><a href="'.$stats_link.'" class="nav"><img src="'.REASON_HTTP_BASE_PATH.'silk_icons/chart_bar.png" alt="" />Statistics</a></li>'."\n";
			}
			echo '<li class="navItem';
			if( $this->cur_module == 'ViewUsers' )
				echo ' navSelect';
			echo '"><a href="'.$this->make_link( array( 'cur_module' => 'ViewUsers', 'type_id' => '' ) ).'" class="nav"><img src="'.REASON_HTTP_BASE_PATH.'ui_images/types/user.png" alt="" />Users</a></li>'."\n";
			$master_admin_id = id_of('master_admin');
			if($this->site_id != $master_admin_id)
			{
				$sites = $this->get_sites();
				if(isset($sites[$master_admin_id]))
				{
					echo '<li><a href="'.$this->make_link(array('site_id'=>id_of('master_admin'),'type_id'=>id_of('site'),'id'=>$this->site_id,'cur_module'=>'Editor')).'" class="nav"><img src="'.REASON_HTTP_BASE_PATH.'silk_icons/pencil.png" alt="" /> Site Setup</a></li>'."\n";
				}
			}
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
			if( $site->get_value( 'unique_name' ))
			{
				$show = false;
				if($site->get_value( 'site_state' ) == 'Live')
				{
					$show = true;
				}
				else
				{
					$es = new entity_selector();
					$es->add_right_relationship($site->id(),relationship_id_of('site_archive'));
					$es->add_relation( 'site_state = "Live"' );
					$es->set_num(1);
					$sites = $es->run_one(id_of('site'), 'Archived');
					if(!empty($sites))
					{
						$show = true;
					}
				}
				if($show)
				{
					$link = REASON_STATS_URI_BASE;
					$uname = posix_uname();
					$link .=  strtolower($uname['nodename']).'/';
					$link .= $_SERVER['HTTP_HOST'].'/';
					$link .= $site->get_value( 'unique_name' ).'/';
					return $link;
				}
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
			echo '<div class="typeNav"><strong>Borrow</strong>';
			echo '<ul class="leftList">';
			
			foreach( $sharables AS $type )
			{
				if( $type->id() == $this->type_id && !empty( $this->request[ 'cur_module' ] ) && ( $this->request[ 'cur_module' ] == 'Sharing' || $this->request[ 'cur_module' ] == 'DoBorrow' ) )
				{
					$cur_type = true;
				}
				else
					$cur_type = false;
					
				echo '<li class="navItem';
				if( $cur_type )
					echo ' navSelect';
				echo '">';

				echo '<a href="' . 
					$this->make_link( array( 
								'site_id' => $this->site_id, 
								'type_id' => $type->id() ,
								'user_id' => $this->user_id,
								'cur_module' => 'Sharing' ) )
					. '" class="nav">'.($type->get_value('plural_name') ? $type->get_value( 'plural_name' ) : $type->get_value( 'name' )).'</a></li>' . "\n";
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
	// shows yet more links in the non-id sidebar.
	{
		if( $this->show[ 'admin_tools' ] )
		{

			if( reason_user_has_privs( $this->user_id, 'view_sensitive_data' ) )
			{
				echo '<div class="typeNav"><strong>Other Tools</strong>';
				echo '<ul class="leftList">';		
				$urls = array(
						'User Information' => 'user_info',
						'Show Session Vars' => 'show_session',
						'Kill Session Vars' => 'kill_session',
						'About Reason' => 'about_reason',
				);
				foreach( $urls AS $name => $module_name )
				{
					echo '<li class="navItem"><a href="'.$this->make_link( array( 'cur_module' => $module_name ) ).'" class="nav">'.$name.'</a></li>' . "\n";
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
	}
	
	function title()
	{
		echo '<h2 class="pageTitle">'.$this->title.'</h2>';
	}
	
	function main_area()
	//displays the main area of the page.  First does its own stuff then calls the module to do its stuff.
	{
		echo '<div class="contentArea">';		
		if( $this->show[ 'title' ] )
		{
			$this->title();
		}
		$this->module->run();
		
		echo '</div>';
	}
	
	function banner()
	//the top banner.  doesn't really do much except display some stuff and the show the user
	{
		echo '<table class="banner">' . "\n";
		echo '<tr>' . "\n";
		echo '<td class="crumbs"> '.REASON_ADMIN_LOGO_MARKUP;
		echo '<span>';
		echo '<strong> :: <a href="'.$this->make_link(array('cur_module'=>'about_reason')).'" class="bannerLink">Reason '.REASON_VERSION.'</a></strong>';
		if($this->site_id != id_of('master_admin'))
		{
			$sites = $this->get_sites();
			if(isset($sites[id_of('master_admin')]))
			{
				echo ' :: <a href="'.carl_construct_link(array('site_id'=>id_of('master_admin')), array('user_id')).'">Master Admin</a>';
			}
		}
		echo '</span></td>' . "\n";
		echo '<td class="id">';
		$this->show_user();
		echo '</td>' . "\n";
		echo '</tr>' . "\n";
		echo '</table>' . "\n";
	}
	
	function show_user()
	//if logged in user has pose_as_other_user privs, displays a drop down of all users so that they can log in as that person (for debugging purposes).
	//otherwise, tells the user who they are
	{
		// if behind HTTP authentication the session lasts until the browser is closed and logout will not do anything
		$show_logout = !isset($_SERVER['REMOTE_USER']);
		$user = new entity( $this->authenticated_user_id );
		
		if( reason_user_has_privs($this->authenticated_user_id, 'pose_as_other_user' ) )
		{
			$es = new entity_selector();
			$es->add_type( id_of( 'user' ) );
			$es->set_order( 'name ASC' );
			$es->limit_tables();
			$es->limit_fields('name');
			$users = $es->run_one();
			
			echo '<form action="?" class="jumpNavigation" name="userSwitchSelect" method="get">'."\n";
			echo '<label for="user_switch_select">User</label>: ';
			echo '<select name="user_id" class="jumpDestination siteMenu" id="user_switch_select">'."\n";
			echo '<option value="">--</option>'."\n";
			foreach( array_keys($users) AS $user_id )
			{
				echo '<option value="'. $user_id . '"';
				if( $user_id == $this->user_id )
					echo ' selected="selected"';
				echo '>' . $users[$user_id]->get_value( 'name' ) . '</option>' . "\n";
			}
			echo '</select>';
			$this->echo_hidden_fields('user_id');
			echo '<input type="submit" class="jumpNavigationGo" value="go" />'."\n";
			if($this->user_id != $this->authenticated_user_id)
			{
				if(isset($users[$this->authenticated_user_id]))
					$username = $users[$this->authenticated_user_id]->get_value('name');
				else
					$username = 'me';
				echo '<a class="stopPosing" href="'.$this->make_link(array('user_id'=>$this->authenticated_user_id)).'" title="Stop posing as another user">'.htmlspecialchars($username).'</a>'."\n";
			}
			if ($show_logout) echo ' <strong><a href="'.REASON_LOGIN_URL.'?logout=true" class="bannerLink">Logout</a></strong>';
			echo '</form>';
		}
		else
		{
			echo 'You are <strong>' . $user->get_value( 'name' ) .'</strong>';
			if ($show_logout) echo ': <strong><a href="'.REASON_LOGIN_URL.'?logout=true" class="bannerLink">Logout</a></strong>';
		}
	} // }}}
	
	function set_head_items()
	{
		
		// add universal css path
		if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '') $this->head_items->add_stylesheet(UNIVERSAL_CSS_PATH);
		
		// add admin CSS
		$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'admin.css');
					
		// add javascript logout timer
		if (!isset($_SERVER['REMOTE_USER']) && USE_JS_LOGOUT_TIMER) // if we are not logged in via http authentication
		{
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/timer.css');
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'timer/timer.js');
		}
		
		// add collapse javasript (should be moved to module method
		$this->head_items->add_javascript(JQUERY_URL, true);
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'jump_navigation.js');
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'disable_submit.js?id=disco_form&reset_time=60000');
		// add the charset information - this should maybe just be in the head function code since we really want it on top
		$this->head_items->add_head_item('meta',array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8' ), '', true );
	}
	
	function head()
	//page head.  prints out basic top html stuff
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
		echo '<head>'."\n";
		echo '<title>Reason';
		if( !empty( $this->site_id ) )
			echo ': '.strip_tags( $this->get_name( $this->site_id ) );
		if( !empty( $this->title ) AND ( empty( $this->site_id ) || $this->title != $this->get_name( $this->site_id ) ) )
			echo ': '.strip_tags($this->title);
		echo '</title>'."\n";
		echo $this->head_items->get_head_item_markup();
		echo '</head>' . "\n";
		echo '<body>' . "\n";
		echo '<div id="wrapper">'."\n";
		if( $this->show[ 'banner' ] ) $this->banner();
		if( $this->show[ 'sitebar' ] ) $this->sitebar();
		echo '<table cellpadding="0" cellspacing="0" border="0" id="adminLayoutTable">';
		echo '<tr><td valign="top" width="20%">';
	}
	
	function finish_page()
	{
		
	}
	
	function foot()
	//botton o' page
	{
		echo '</td></tr></table>' . "\n";
		echo '</div>'."\n";
		echo '</body>' . "\n";
		echo '</html>' . "\n";
	}
	
	function new_column()
	//creates a new column
	{
		echo '</td><td valign="top">';
	}

	/**
	 * Create a link within the admin site.
	 *
	 * Passes through the default args defined at the top of this file,
	 * other parameters if they have values, and all of the remaining
	 * request variables if $pass_rest is true.
	 * 
	 * @param array $params associative array of query parameters
	 * @param boolean $pass_rest if true, all remaining request variables
	 *        will be included
	 * @param boolean $html if true, use "&amp;" as the parameter
	 *        separator; if false, use "&"
	 */
	function make_link($params = array(), $pass_rest = false, $html = true)
	{
		$default_args = array();
		foreach ($this->default_args as $arg) {
			$default_args[$arg] = (isset($this->request[$arg]))
				? (string) $this->request[$arg]
				: "";
		}

		$old_args = array();
		$prefix_length = strlen(CM_VAR_PREFIX);
		foreach ($this->request as $name => $val) {
			if (substr($name, 0, $prefix_length) == CM_VAR_PREFIX) {
				if (!empty($val))
					$old_args[$name] = $val;
			}
		}

		$params = array_merge($default_args, $old_args, $params);
		if ($pass_rest)
			$params = array_merge($this->request, $params);

		$parts = array();
		foreach ($params as $key => $val) {
			// We need to include anything that is in default args or has a
			// value.
			if (isset($default_args[$key]) || !empty($val))
				$parts[] = urlencode($key).'='.urlencode($val);
		}

		$separator = ($html) ? "&amp;" : "&";
		return $_SERVER['PHP_SELF']."?".implode($separator, $parts);
	}
		
	function get_default_args() // {{{
	{
		$args = array_intersect_key($this->request,array_flip($this->default_args));
		return !empty($args) ? $args : false;
	} // }}}

	/**
	 * Initializes the admin page.
	 * 
	 * Identifies which admin module to use, instantiates, and initializes it.
	 * This method uses the $GLOBALS['_reason_admin_modules'] array defined in admin_module.php
	 * to determine which module to run.
	 *
	 * @return true if complete, false if used could not be authenticated
	 */
	function init() // {{{
	//basic init function.  called before anything is displayed.  does its own stuff and then calls the modules
	//init function
	{
		if ($this->authenticate() == false) return false;
		$this->load_params();
		$this->head_items = new HeadItems();
		$this->set_head_items();
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
						$redirect = carl_make_redirect(array('cur_module' => 'Editor'));
						header('Location: ' . $redirect);
						die;
					}
					else
					{
						$redirect = carl_make_redirect(array('cur_module' => 'Lister'));
						header('Location: ' . $redirect);
						die;
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
			$this->module_name = $module_name;
			$this->module = new $module_name( $this );
			$this->module->set_head_items($this->head_items);
			$this->module->init();
		}
		else
		{
			trigger_error('Could not determine a module to run in the admin page init method.', HIGH);
		}
		return true;
	} // }}}
	function check_errors( $user ) // {{{
	//checks to make sure user has access to current site, otherwise, sends him home.
	{
		$error_messages = array(
								'site_to_user' => 'You do not have access to this site.',
								'site_to_type' => 'This site does not have access to this type.',
								'type_to_id' => 'The entity you have chosen does not match the type.',
								'site_owns_id' => 'This site does not own this entity.',
							   );
		$message = '';
		if( !$this->verify_user( $user ) )
			$message = $error_messages[ 'site_to_user' ];
		elseif( !$this->site_to_type() )
			$message = $error_messages[ 'site_to_type' ];
		elseif( !$this->type_to_id() )
			$message = $error_messages[ 'type_to_id' ];
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
	function site_owns_id() // {{{
	{
		if( $this->id && $this->site_id && empty( $this->request[ 'new_entity' ] ) && $this->cur_module 
				&& ( $this->cur_module == 'Editor' || $this->cur_module == 'Associator' ) )
		{
			$es = new entity_selector( $this->site_id );
			$es->add_type( $this->type_id );
			$es->limit_tables();
			$es->limit_fields();
			$es->add_relation( 'entity.id = ' . $this->id );
			$es->set_sharing( 'owns' );
			$es->set_num(1);
			if( $es->run_one('','All') )
				return true;
			else
				return false;
		}
		return true;
	} // }}}
	
	function should_run_api()
	{
		return $this->module->should_run_api();
	}
	
	function run_api()
	{
		$this->module->run_api();
	}
	
	function run() // {{{
	//does its thang
	{
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
	{
		if ( empty($this->user_id) || ( $this->user_id != $this->authenticated_user_id && !reason_user_has_privs($this->authenticated_user_id, 'pose_as_other_user') ) )
		{
			$this->user_id = $this->authenticated_user_id;
		}
		
		$user = new entity($this->user_id);
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
	
	/**
	 * @todo work with arrays and move into a utility file
	 */
	function echo_hidden_fields($ignore_value, $values = NULL )
	{
		$values = (isset($values)) ? $values : $_GET;
		if(!empty($values))
		{
			foreach($_GET as $key=>$value)
			{
				if($key != $ignore_value)
				{
					if(!is_array($value))
					{
						echo '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'" />'."\n";
					}
				}
			}
		}
	}
}
?>
