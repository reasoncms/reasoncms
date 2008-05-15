<?php
	reason_include_once('classes/admin/modules/default.php');
	include_once( THOR_INC .'thor_admin.php' );
	
	/**
	 * Thor Data Manager Module
	 *
	 * @author Nathan White
	 */
	
	class ThorDataModule extends DefaultModule // {{{
	{
		var $thor_admin; // thor viewer object
		var $form; // form entity
		
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
			$this->form = new entity( $this->admin_page->id );
			if ($this->validate_form())
			{		
				$form_id = $this->form->id();
				$form_xml = $this->form->get_value('thor_content');
				$this->admin_page->title = 'Data Manager for Form "' . $this->form->get_value('name').'"';
				
				$admin_form = new DiscoThorAdmin();
				$this->thor_admin = new ThorAdmin();
				$this->thor_admin->set_admin_form($admin_form);
				$this->thor_admin->set_allow_edit(true);
				$this->thor_admin->set_allow_delete(true);
				$this->thor_admin->set_allow_row_delete(true);
				$this->thor_admin->init_thor_admin($form_xml, 'form_'.$form_id);
			}
		}
		
		/**
		 * make sure the form id passed is valid and owned by the site
		 */
		function validate_form()
		{
			if ($this->form->get_values())
			{
				if ($this->form->get_value('type') == id_of('form'))
				{
					$owner = $this->form->get_owner();
					if ($owner->id() == $this->admin_page->site_id)
					{
						return true;
					}
				}
			}
			return false;
		}
		
		/**
		 * @return void
		 */
		function run() // {{{
		{
			if (!empty($this->thor_admin))
			{
				$link_return = $this->admin_page->make_link( array( 'cur_module' => 'Editor'));
				$this->gen_menu(array('Edit "'.$this->form->get_value('name').'" (Form)' => $link_return));
				$this->thor_admin->run();
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