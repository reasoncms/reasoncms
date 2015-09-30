<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'faculty_staff_handler';

	/**
	 * A content manager for faculty/staff (e.g. people in directory service)
	 *
	 * Note that the name of the type (and therefore this content manager)
	 * is somewhat more restrictive than the actual capabilities of this
	 * type. See the faculty_staff module for more info.
	 *
	 */
	class faculty_staff_handler extends ContentManager 
	{
		var $ldap_field_mapping = array(
										'ds_fullname'=>'Name',
										'ds_phone'=>'Phone',
										'ds_email'=>'Email Address'
									);
		var $ldap_function_mapping = array(
										'ds_email'=>'make_email_link',
									);
		function alter_data()  // {{{
		{
			$this->set_display_name ('name', 'Username');
			$this->change_element_type( 'degrees' , 'hidden' );
			$this->remove_element( 'author' );
			$this->hide_elements();
			
			$info = $this->get_person_info();
			$pre = '';
			$post = '';
			if(!empty($info))
			{
				$this->add_element('directory_info','commentWithLabel',array('text'=>$info));
				$pre = 'Additional ' ;
				$post = '<br /><span class="smallText">(This will appear below directory info)</span>';
			}
			$this->set_display_name('content',$pre.'Personal Information'.$post);
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			if( !reason_user_has_privs( $this->admin_page->user_id , 'manage_integration_settings' ) )
				$this->change_element_type( 'ldap_created' , 'hidden' );
			else
				$this->set_display_name ('ldap_created', 'Automatically Created');

			$this->set_order( array(
				'comment',
				'name',
				'directory_info',
				'degrees',
				'affiliation',
				'content',
			) );
		} // }}}

		function hide_elements()
		{
			$this->change_element_type( 'affiliation' , 'hidden' );
		}

		function get_person_info() //{{{
		{
			$ret = '';
			if($this->get_value('name'))
			{
				include_once(CARL_UTIL_INC.'dir_service/directory.php');
				$dir = new directory_service();
				if ($dir->search_by_attribute('ds_username',$this->get_value('name'),
						array_keys($this->ldap_field_mapping)))
				$person = $dir->get_first_record();
				foreach($this->ldap_field_mapping as $ldap_term=>$english_term)
				{
					if(!empty($person[$ldap_term]))
					{
						$vals = array();
						foreach($person[$ldap_term] as $val)
						{
							$vals[] = $this->_get_modified_ldap_value($ldap_term, $val);
						}
						$lines[] = '<li><strong>'.$english_term.':</strong> '.implode(', ',$vals).'</li>';
					}
				}
				if(!empty($lines))
				{
					$ret = '<ul>'."\n".implode("\n",$lines).'</ul>'."\n";
				}
			}
			return $ret;
		} // }}}
		function _get_modified_ldap_value($ldap_term, $value)
		{
			if(!empty($this->ldap_function_mapping[$ldap_term]))
			{
				$function = $this->ldap_function_mapping[$ldap_term];
				return $this->$function($value);
			}
			else
			{
				return htmlspecialchars($value, ENT_QUOTES);
			}
		}
		function make_email_link($email) //{{{
		{
			return '<a href="mailto:'.urlencode($email).'">'.htmlspecialchars($email, ENT_QUOTES).'</a>';
		} // }}}
		
		function run_error_checks()
		{
			// make sure an entity with the same name is not already on the site - if so, throw an error
			$es = new entity_selector($this->admin_page->site_id);
			$es->add_type(id_of('faculty_staff'));
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$result = $es->run_one();
			if (!empty($result))
			{
				foreach ($result as $k=>$v)
				{
					if ( ($v->get_value('name') == $this->get_value('name')) && ($k != $this->admin_page->id) )
					{
						$link = carl_make_link(array('id' => $k, 'new_entity' => '', 'entity_saved' => ''));
						$this->set_error('name', 'There is already a faculty / staff member on the site with the name ' . $this->get_value('name') . ' (<a href="'.$link.'">go to existing record</a>). To save this record, you need to choose a different name.');
						break;
					}
				}
			}
		}
	}
?>
