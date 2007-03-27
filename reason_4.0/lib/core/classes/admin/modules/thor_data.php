<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once ( 'function_libraries/url_utils.php' );
	include_once( DISCO_INC . 'disco.php');
	/**
	 * Thor Data Manager Module
	 */
	
	class ThorDataModule extends DefaultModule // {{{
	{
		var $form_prefix = 'form_';
		var $pages;
		var $num_rows = '';
		var $acceptable_paramaters = array('mode' => array('function' => 'turn_into_int'));
		var $table;
		
		function ThorDataModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * Sets up page variables and runs the entity selector that grabs pages that use the form
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			// is this considering whether the form is used on other pages?
			parent::init();
			$this->form = new entity( $this->admin_page->id );
			$this->admin_page->title = 'Thor Data Manager for ' . $this->form->get_value('name');
			$es = new entity_selector();
			$es->add_type(id_of('minisite_page'));
			$es->add_left_relationship($this->admin_page->id, relationship_id_of('page_to_form'));
			$this->pages = $es->run_one();
			$this->table = $this->form_prefix . $this->admin_page->id;
			$this->num_rows = $this->db_table_exists_check() ? $this->db_count() : 0;
			$this->validate_input();
		} // }}}
		
		function validate_input()
		{
			if (!empty($this->admin_page->request['mode']))
				$this->mode = check_against_array($this->admin_page->request['mode'], array('delete'));
			else $this->mode = '';
		}
		/**
		 * Lists the users who currently have access to the site
		 * 
		 * @return void
		 */
		function run() // {{{
		{
			$link_return = $this->admin_page->make_link( array( 'cur_module' => 'Editor', 'mode' => '' ));
			switch ($this->mode)
			{
			case 'delete':
				$confirm = new DiscoConfirm($this->num_rows);
				$confirm->init();
				$confirm->alter_data();
				$confirm->run();
				if ($confirm->status == 'delete_forever')
				{
					$deleted = $this->delete_data();
					if ($deleted) echo '<p>' .$this->num_rows . ' row(s) successfully deleted.</p>';
					else
					{
						echo '<p>Deletion was unsuccessful. The web services group has been notified. Please try again later.</p>';
					}
					echo '<p><a href="'.$link_return.'">Return to form</a></p>';
				}
				if ($confirm->status != 'cancel') break;
			//case 'view':
				//$this->show_data;
				//break;
			default:
				$link_delete = $this->admin_page->make_link( array( 'mode' => 'delete' ));
				if($this->num_rows == '0')
				{
					echo '<p>There is no stored data associated with this form.</p>';
					echo '<p><a href="'.$link_return.'">Return to form</a>';
				}
				else
				{
					if(empty($this->pages))
					{
						echo '<p>This form is not associated with a live page but contains ' . $this->num_rows . ' row(s) of data.</p>'."\n";
					}
					else // what about borrowing?
					{
						echo '<p>There are <strong>' . $this->num_rows . '</strong> row(s) of stored data.</p>';
						echo '<p>To view stored data and export data in .csv format, you need to visit a page that contains this form. You may be required to log in, and you must be a part of a group that is defined as having access to the data entered in this form.</p>';
						echo '<p>The following pages are currently storing data entered through this form.</p>'."\n";
						echo '<ul>'."\n";
						foreach($this->pages as $page)
						{
							$owner_site = $page->get_owner();
							$owner_site_id = $owner_site->id();
							echo '<li><a href="'.get_minisite_page_link($owner_site_id, $page->get_value('id'), 'mode=data_view', true).'">'.$page->get_value('name').'</a></li>'."\n";
						}
						echo '</ul>'."\n";
					}
					echo '<p><a href="'.$link_return.'">Return to form</a> | ';
					echo '<a href="'.$link_delete.'">Delete stored data</a></p>';
				}
			} // }}}
		}
		
		function db_table_exists_check()
		{
			// connect with thor database defined in settings.php3
			connectDB(THOR_FORM_DB_CONN);
			$q = 'check table ' . $this->table . ' fast quick' or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  			$res = mysql_query($q);
  			$results = mysql_fetch_assoc($res);
  			if (strstr($results['Msg_text'],"doesn't exist") ) $ret = false;
  			else $ret = true;
 			connectDB(REASON_DB);
			return $ret;
		}
		
		function db_count()
		{
			// connect with thor database defined in settings.php3
			connectDB(THOR_FORM_DB_CONN);
			$q = 'SELECT count(*) FROM ' . $this->table;
			//print $q;
  			$res = mysql_query( $q ) or trigger_error( 'Error: mysql error in Thor: '.mysql_error() );
  			$results = mysql_fetch_row( $res );
  			connectDB(REASON_DB);
			return $results[0];
		}
		
		function delete_data()
		{
			// connect with thor database defined in settings.php3
			connectDB(THOR_FORM_DB_CONN);
			$q = 'DROP TABLE ' . $this->table;
			$res = mysql_query( $q ) or mysql_error();//or trigger_error( 'Error: mysql error in Thor Data delete - URL ' . get_current_url() . ': '.mysql_error() );
  			connectDB(REASON_DB);
			return $res;
		}
	} // }}}
	
	class DiscoConfirm extends Disco
	{
		var $num_rows;              
		var $elements = array('form_id');
		var $actions = array( 'delete_forever' => 'Delete Forever',
							  'cancel'         => 'Cancel' );
		var $status = '';
		
		function DiscoConfirm($num_rows = '')
		{
			$this->num_rows = $num_rows;
		}
		
		function alter_data()
		{	
			$this->change_element_type('form_id', 'hidden');
		}
		
		function pre_show_form()
		{
			if ($this->num_rows > 0)
			{
				echo '<p>If you choose to proceed to delete the stored data, ';
				echo 'all information that has been entered using this form on any page will be deleted from the database. If this information is important, ';
				echo 'it is highly recommend that you save the data to your local computer before proceeding with the delete!</p>'."\n";	
				echo '<p>Are you sure you want to <strong>delete '.$this->num_rows.' row(s)?</strong></h3>';
			}
			else
			{
				$this->show_form = false;
				echo '<p>There appear to be no rows to delete.</p>';
				$this->actions = array( 'cancel' => 'Cancel');
			}
		}
		
		function process()
		{
			$this->show_form = false;
			if ($this->chosen_action == 'delete_forever')
			{
				$this->status = 'delete_forever';
			}
			else
			{
				$this->status = 'cancel';
			}
		}
	}
?>
