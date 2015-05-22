<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once( THOR_INC .'thor.php' );
	include_once( THOR_INC .'thor_admin.php' );
	
	/**
	 * Thor Data Manager Module
	 *
	 * @author Nathan White
	 */
	
	class ThorDataModule extends DefaultModule // {{{
	{
		var $_thor_admin; // thor viewer object
		var $_form; // form entity
		
		function ThorDataModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
			$this->admin_page->show['leftbar'] = false;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * @return void
		 */
		function init()
		{
			parent::init();
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$form =& $this->get_form();
			if ($form)
			{
				$this->admin_page->title = 'Data Manager for Form "' . $form->get_value('name').'"';
			}
			$ta =& $this->get_thor_admin();
			
			// biobooks use a non-standard thor db structure ... until this is fixed we want to disable
			// row creation and editing for bio book thor forms. After this is fixed this check should 
			// be zapped for a little performance boost.
			if ($ta)
			{
				$tc = $ta->get_thor_core();
				$allow_edit_and_new = ($tc->column_exists('formkey')) ? false : true;
				$ta->set_allow_delete(true);
				if ($allow_edit_and_new)
				{
					$ta->set_allow_edit(true);
					$ta->set_allow_new(true); // can we make sure its valid first?
				}
				$ta->set_allow_row_delete(true);
				$ta->set_allow_download_files(true);
				$ta->init_thor_admin();
			}
		}
		
		function &get_thor_admin()
		{
			if (!isset($this->_thor_admin))
			{
				$form =& $this->get_form();
				if ($form)
				{
					$id = $form->id();
					$xml = $form->get_value('thor_content');
					$tc = new ThorCore($xml, 'form_'.$id);
					$af = new DiscoThorAdmin();
					$af->show_hidden_fields_in_edit_view = true;
					$this->_thor_admin = new ThorAdmin();
					$this->_thor_admin->set_thor_core($tc);
					$this->_thor_admin->set_admin_form($af);
				}
				else $this->_thor_admin = false;
			}
			return $this->_thor_admin;
		}
		
		function &get_form()
		{
			if (!isset($this->_form))
			{
				$form = new entity($this->admin_page->id);
				if ($form->get_values() && ($form->get_value('type') == id_of('form')))
				{
					$owner = $form->get_owner();
					if ($owner->id() == $this->admin_page->site_id) $this->_form = $form;
				}
				if (!isset($this->_form)) $this->_form = false;
			}
			return $this->_form;
		}
		
		/**
		 * @return void
		 */
		function run() // {{{
		{
			$form =& $this->get_form();
			$ta =& $this->get_thor_admin();
			if ($ta)
			{
				$link_return = $this->admin_page->make_link( array( 'cur_module' => 'Editor'));
				$this->gen_menu(array('Edit "'.$form->get_value('name').'" (Form)' => $link_return));
				$ta->run();
			}
			else
			{
				echo '<h4>Invalid Request</h4>';
				echo '<p>The form you want to work with is not a valid form, or could not be loaded. You may only edit a form if it is owner by a site to which you have access.</p>';
			}
		}
		
		function gen_menu($link_array)
		{
			foreach ($link_array as $k=>$v)
			{
				if (!empty($v)) $links[] = '<a href="'.$v.'">'.$k.'</a>';
				else $links[] = '<strong>'.$k.'</strong>';
			}
			echo '<p>' . implode(' | ', $links) . '</p><hr />';
		}
		
	} // }}}
?>
