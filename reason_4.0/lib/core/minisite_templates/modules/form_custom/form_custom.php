<?php

	reason_include_once( 'minisite_templates/modules/default.php' );
	include_once( CARL_UTIL_INC . 'db/table_admin.php' );
	
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
	 * @author Nathan White
	 */ 
	class FormCustomMinisiteModule extends DefaultMinisiteModule
	{
		var $custom_form;
		var $admin_form;
		var $table_viewer;
		var $form_name;
		var $admin_form_name;
		var $acceptable_params = array('custom_form' => false,
									   'admin_form' => false,
									   'enter_admin_view_text' => 'Enter administrative view',
									   'exit_admin_view_text' => 'Exit administrative view',
									   'show_lister_view_text' => 'Show summary view');
									   
		var $cleanup_rules = array('form_admin_view' => array('function' => 'check_against_array', 'extra_args' => array('true')));
		
		function init( $args )
		{
 			force_secure_if_available();
 			if (empty($this->params['custom_form'])) 
 			{
 				trigger_error('the form custom minisite module must be provided with a custom form to operate');
 			}
 			else
 			{
	 			reason_include_once( 'minisite_templates/modules/form_custom/'.$this->params['custom_form'].".php");
	 			$form_name = $GLOBALS[ '_custom_form_class_names' ][ $this->params['custom_form'] ];
 				$this->custom_form = new $form_name();
 				$this->custom_form->head_items =& $this->parent->head_items;
 				
 				if (!empty($this->params['admin_form'])) // if an admin form is specified instantiate it...if not 
 				{
 					reason_include_once( 'minisite_templates/modules/form_custom/'.$this->params['admin_form'].".php");
 					$admin_form_name = $GLOBALS[ '_custom_form_class_names' ][ $this->params['admin_form'] ];
 					$this->admin_form = new $admin_form_name();
 					$this->admin_form->head_items =& $this->parent->head_items;
 				}
 			
 				// CSS - this needs work
 				$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
 				$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/reason_admin/admin.css');
 						
 				if (!empty($this->request['form_admin_view']) && !empty($this->admin_form) && $this->admin_form->authenticate())
 				{	
 					$this->init_admin_view();
 				}
 				else $this->init_form_view();
 			}
		}
		
		function init_form_view()
		{
			$this->custom_form->init();
 			$this->apply_custom_title();
		}
		
		function init_admin_view()
		{
			$this->table_admin = new TableAdmin();
			$tt =& $this->table_admin;
			$af =& $this->admin_form;
			$cf =& $this->custom_form;
			$db_conn = ($af->get_db_conn() != '') ? $af->get_db_conn() : $cf->get_db_conn();
			$table_name = ($af->get_table_name() != '') ? $af->get_table_name() : $cf->get_table_name();
			$tt->set_admin_form($af);
			$tt->set_privileges_from_admin_form();
			$tt->init($db_conn, $table_name);
			// ignoring filtering for the moment
			//$this->table_viewer->set_options($this->admin_form->get_options());
		}
		
		function apply_custom_title()
		{
			$custom_title = $this->custom_form->get_custom_title();
			if (!empty($custom_title)) $this->parent->title = $custom_title;
		}
		
		function has_content()
		{
			return $this->custom_form->has_content();
		}
		
		function run()
		{
			if (!empty($this->table_admin)) $this->run_admin_view();
			else $this->run_form_view();
		}
		
		function run_form_view()
		{
			$this->show_admin_control_box();
			$form =& $this->custom_form;
			$form->run();
		}
		
		function show_admin_control_box()
		{
			if (!empty($this->table_admin)) // show exit administive view link as we are in administrative view
			{
				$url = carl_construct_link(array('form_admin_view' => ''), array('textonly'));
				$link[] = '<a href="'.$url.'">'.$this->params['exit_admin_view_text'].'</a>';
				if ($this->table_admin->get_table_row_action())
				{
					$url2 = carl_make_link($this->table_admin->get_menu_links_base_with_filters());
					$link[] = '<a href="'.$url2.'">'.$this->params['show_lister_view_text'].'</a>';
				}
			}
			// show administrative view entry link if the user has access and the form allows
			elseif (!empty($this->admin_form) && $this->admin_form->authenticate() && $this->custom_form->allow_show_admin_control_box())
			{
				$url = carl_construct_link(array('form_admin_view' => 'true'), array('textonly'));
				$link[] = '<a href="'.$url.'">'.$this->params['enter_admin_view_text'].'</a>';
			}
			if (!empty($link))
			{
				echo '<div id="formAdminControlBox" style="background: #dfdfdf;">';
				echo '<p style="padding: 2px;">'.implode(' | ', $link).'</p>';
				echo '</div>';
			}
		}
		
		function run_admin_view()
		{
			$this->show_admin_control_box();
			$this->table_admin->run();
		}
	}
?>
