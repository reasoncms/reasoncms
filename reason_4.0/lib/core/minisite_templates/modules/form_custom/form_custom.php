<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
	/**
	 * Include parent class and other dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/file_finders.php' );
	include_once( CARL_UTIL_INC . 'db/table_admin.php' );
	
	/**
	 * Register module with Reason
	 */
	$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'FormCustomMinisiteModule';

	/**
	 * A module which runs custom discoDB forms - and provides an interface for data viewing and editing of the form contents
	 *
	 * Typical usage would be to invoke with module on a page type that also passes a parameter custom_form consisting of the
	 * custom form filename without the .php extension.
	 *
	 * An admin form name can also be passed. This form will be used by the table_viewer to customize the adminstrative options
	 * that are available, hide/show columns, set comments in editing fields, etc. If an admin form name is not passed, the
	 * form will not offer an administrative interface.
	 *
	 * @deprecated - use form module with appropriate model
	 * @author Nathan White
	 */ 
	class FormCustomMinisiteModule extends DefaultMinisiteModule
	{
		var $custom_form = false;
		var $admin_form = false;
		
		// will get set to the table admin object if it needs to exist
		var $table_admin = false;
		
		var $acceptable_params = array('custom_form' => false,
									   'admin_form' => false,
									   'enter_admin_view_text' => 'Enter administrative view',
									   'exit_admin_view_text' => 'Exit administrative view',
									   'show_lister_view_text' => 'Show summary view');
									   
		var $cleanup_rules = array('form_admin_view' => array('function' => 'check_against_array', 'extra_args' => array('true')));
		
		function init( $args=array() )
		{
 			force_secure_if_available();
 			$this->init_head_items();
 			$this->init_custom_form();
 			$this->init_admin_form();
 			$method = ($this->is_admin_view()) ? 'init_admin_view' : 'init_form_view';
 			$this->$method();
		}
		
		function init_custom_form()
		{
			// custom form setup
 			$custom_form_object =& $this->get_custom_form_object();
	 		if ($custom_form_object)
	 		{
	 			$custom_form =& $custom_form_object->get_custom_form();
 				$custom_form->head_items =& $this->parent->head_items;
 				$this->set_custom_form($custom_form);
 			}
		}
		
		function init_admin_form()
		{
			$admin_form_object =& $this->get_admin_form_object();
 			if ($admin_form_object)
 			{
 				$admin_form =& $admin_form_object->get_custom_form();
 				$admin_form->head_items =& $this->parent->head_items;
 				$this->set_admin_form($admin_form);
 			}	
		}
		
		function init_head_items()
		{
			$head_items =& $this->parent->head_items;
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_error.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
		}
		
		function init_form_view()
		{
			$custom_form =& $this->get_custom_form();
			if ($custom_form) 
			{
				$custom_form->init();
				$this->apply_custom_title();
			}
 			else trigger_error('The form view cannot be initialized because no form object has been setup using set_custom_form');
		}

		/**
		 * The way this is setup filename_frag and filename_real are not set for any admin view of a custom form ...
		 */ 		
		function init_admin_view()
		{	
			$tt = new TableAdmin();
			$af =& $this->get_admin_form();
			$cf =& $this->get_custom_form();
			$db_conn = ($af->get_db_conn() != '') ? $af->get_db_conn() : $cf->get_db_conn();
			$table_name = ($af->get_table_name() != '') ? $af->get_table_name() : $cf->get_table_name();
			$tt->set_admin_form($af);
			$tt->set_privileges_from_admin_form();
			$tt->init($db_conn, $table_name);
			$this->set_table_admin($tt);
		}
		/**
		 * @return boolean true if the user has access to and has requested the administrative view
		 */
		function is_admin_view()
		{
			return (isset($this->request['form_admin_view']) && $this->has_admin_access());
		}
		
		function has_admin_access()
		{
			$admin_form =& $this->get_admin_form();
			return ($admin_form) ? $admin_form->authenticate() : false;
		}
		
		function &get_custom_form_object()
		{
			if (!empty($this->params['custom_form']))
			{
				reason_include_once( 'minisite_templates/modules/form_custom/'.$this->params['custom_form'].".php");
	 			$form_name = $GLOBALS[ '_custom_form_class_names' ][ $this->params['custom_form'] ];		
 				$custom_form_object = new $form_name();
 			}
 			else
 			{
 				$custom_form_object = false;
 				trigger_error('the form custom minisite module must be provided with a custom form to operate', FATAL);
 			}
 			return $custom_form_object;
		}
		
		function &get_admin_form_object()
		{
			if (!empty($this->params['admin_form']))
			{
				reason_include_once( 'minisite_templates/modules/form_custom/'.$this->params['admin_form'].".php");
 				$admin_form_name = $GLOBALS[ '_custom_form_class_names' ][ $this->params['admin_form'] ];
 				$admin_form_object = new $admin_form_name();
 			}
 			else $admin_form_object = false;
 			return $admin_form_object;
		}
		
		function set_custom_form($custom_form)
		{
			$this->custom_form =& $custom_form;
		}
		
		function &get_custom_form()
		{	
			return $this->custom_form;
		}
		
		function set_admin_form($admin_form)
		{
			$this->admin_form =& $admin_form;
		}
		
		function &get_admin_form()
		{	
			return $this->admin_form;
		}
		
		function set_table_admin($table_admin)
		{
			$this->table_admin =& $table_admin;
		}
		
		function &get_table_admin()
		{	
			return $this->table_admin;
		}
		
		function apply_custom_title()
		{
			$custom_form =& $this->get_custom_form();
			$custom_title = ($custom_form) ? $custom_form->get_custom_title() : false;
			if ($custom_title) $this->parent->title = $custom_title;
		}
		
		function has_content()
		{
			$custom_form =& $this->get_custom_form();
			return ($custom_form && method_exists($custom_form, 'has_content')) ? $custom_form->has_content() : true;
		}
		
		function run()
		{
			$method = ($this->get_table_admin()) ? 'run_admin_view' : 'run_form_view';
			$this->$method();
		}
		
		function run_form_view()
		{
			$this->show_admin_control_box();
			$form =& $this->get_custom_form();
			$form->run();
		}
		
		function show_admin_control_box()
		{
			$table_admin =& $this->get_table_admin();
			$custom_form =& $this->get_custom_form();
			
			if ($table_admin) // show exit administive view link as we are in administrative view
			{
				$url = carl_construct_link(array('form_admin_view' => ''), array('textonly'));
				$link[] = '<a href="'.$url.'">'.$this->params['exit_admin_view_text'].'</a>';
				if ($table_admin->get_table_row_action())
				{
					$url2 = carl_make_link($table_admin->get_menu_links_base_with_filters());
					$link[] = '<a href="'.$url2.'">'.$this->params['show_lister_view_text'].'</a>';
				}
			}
			// show administrative view entry link if the user has access and the form allows
			elseif ($this->has_admin_access() && $custom_form->allow_show_admin_control_box())
			{
				$url = carl_construct_link(array('form_admin_view' => 'true'), array('textonly'));
				$link[] = '<a href="'.$url.'">'.$this->params['enter_admin_view_text'].'</a>';
			}
			if (!empty($link))
			{
				echo '<div id="formAdminControlBox">';
				echo '<p>'.implode(' | ', $link).'</p>';
				echo '</div>';
			}
		}
		
		function run_admin_view()
		{
			$table_admin = $this->get_table_admin();
			$this->show_admin_control_box();
			$table_admin->run();
		}
	}
?>
