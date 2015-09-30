<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Register the model with Reason
 */
$GLOBALS[ '_classified_module_model' ][ module_basename( __FILE__) ] = 'ClassifiedModel';

include_once(TYR_INC.'email.php');

/**
 * Classified model - primarily returns data. The controlling module and the views may use this model.
 * @author Nathan White
 */
 
class ClassifiedModel
{
	var $classified_id;
	var $site_id;
	var $head_items;
	var $category_names;
	var $category_names_by_classified_id;
	
	/**
	 * If approval is required, saved entities will be marked at pending
	 * @var boolean
	 */
	var $classified_requires_approval = false;
	
	/**
	 * If notification is required, notice of new postings will be sent to the 
	 * recipient designated by the view. $classified_requires_approval will override
	 * this and always send a notification.
	 * @var boolean
	 */
	var $send_posting_notification = true;
	
	/**
	 * Limit availability of items to a certain number of days
	 * @var int
	 */
	var $classified_duration_days;
	
	/**
	 * Whether or not front-end deletion of ads is available to the person listed in the
	 * contact email. Requires that contact email and email of authenticated user match.
	 * @var int
	 */
	var $allow_contact_to_delete = false;
	
	function ClassifiedModel()
	{
	}
	
	function set_classified_id($id)
	{
		$this->id = $id;
	}
	
	function get_classified_id()
	{
		return $this->id;
	}
	
	function set_site_id($site_id)
	{
		$this->site_id = $site_id;
	}
	
	function get_site_id()
	{
		return $this->site_id;
	}
	
	function set_head_items(&$head_items)
	{
		$this->head_items =& $head_items;
	}
	
	function &get_head_items()
	{
		return $this->head_items;
	}
	
	function alter_es(&$es)
	{
	}
	
	/**
	 * @return array classified category names indexed by classified category entity id
	 */
	function &get_classified_category_names()
	{
		if (!isset($this->category_names))
		{
			$this->category_names = array();
			$es = new entity_selector($this->get_site_id());
			$es->add_type(id_of('classified_category_type'));
			$result = $es->run_one();
			if (!empty($result))
			{
				foreach ($result as $id=>$category)
				{
					$this->category_names[$id] = $category->get_value('name');
				}
			}
		}
		return $this->category_names;
	}

	/**
	 * Returns a string containing the classified categories for a classified item
	 * @param classified entity id
	 * @return array classified categories for a classified entity
	 */
	function &get_category_names_for_classified($entity_id)
	{
		if (!isset($this->category_names_by_classified_id[$entity_id]))
		{
			$this->category_names_by_classified_id = array();
			$es = new entity_selector($this->site_id);
			$es->add_type(id_of('classified_category_type'));
			$es->add_right_relationship($entity_id, relationship_id_of('classified_to_classified_category'));
			$es->limit_tables();
			$es->limit_fields();
			$result = $es->run_one();
			if (!empty($result))
			{
				foreach ($result as $id=>$category)
				{
					$this->category_names_by_classified_id[$entity_id][$id] = $category->get_value('name');
				}
			}
		}
		return $this->category_names_by_classified_id[$entity_id];
	}
	
	/**
	 * @param int classified entity id
	 * @return string comma separated list of classified categories related to a classified entity
	 */
	function get_category_names_for_classified_as_string($entity_id)
	{
		$category_names =& $this->get_category_names_for_classified($entity_id);
		return (!empty($category_names)) ? implode(", ", $category_names) : '';
	}
	
	function get_header_text_as_string()
	{
		return '';
	}
	
	function get_footer_text_as_string()
	{
		return '';
	}
	
	function get_form_header_text_as_string()
	{
		return '';
	}
	
	function get_form_footer_text_as_string()
	{
		return '';
	}
	
	function get_submit_link()
	{
		$empty_request =& $this->get_empty_request_array();
		return carl_make_link(array_merge($empty_request, array('classified_mode' => 'add_item')));
	}
	
	function get_delete_link()
	{
		return carl_make_link(array('classified_mode' => 'delete_item'));
	}
	
	function get_return_to_listing_link()
	{
		$empty_request =& $this->get_empty_request_array();
		return carl_make_link($empty_request);
	}
	
	function get_successful_submission_redirect()
	{
		$empty_request =& $this->get_empty_request_array();
		return carl_make_redirect(array_merge($empty_request, array('classified_mode' => 'submit_success')));
	}
	
	function get_classified_duration_days()
	{
		$dd = (isset($this->classified_duration_days)) ? $this->classified_duration_days : '';
		return $dd;
	}
	
	function get_classified_requires_approval()
	{
		return $this->classified_requires_approval;
	}
	
	function get_user_can_delete($id)
	{
		if ($id && $this->allow_contact_to_delete)
		{
			if ($item = new entity($id))
			{
				if ($user_netid = reason_check_authentication())
				{
					$dir = new directory_service();
					$dir->search_by_attribute( 'ds_username', $user_netid, array('ds_email'));
					$record = $dir->get_first_record();
					if (in_array($item->get_value('classified_contact_email'), $record['ds_email']))
						return true;					
				}
			}
		}
		return false;
	}
	
	/**
	 * Preps values for the database and and creates new entity
	 * @todo the cleaning methods should be available to the view as well outside of save so that the view can filter
	 *       the values in the same way for preview purposes (trim/strip tags in most cases)
	 */
	function save_classified($values)
	{
		if ($user_netid = reason_check_authentication())
			$user = get_user_id($user_netid);
		if (!isset($user) || !$user)
			$user = get_user_id('classified_user');
			
		$name = trim(strip_tags($values['name']));
		$category = turn_into_int($values['category']);
		$duration_days = $this->get_classified_duration_days();
		$requires_approval = $this->get_classified_requires_approval();
		
		if (!empty($values['classified_date_available']))
		{
			$ts = get_unix_timestamp($values['classified_date_available']);
			if ($ts) $clean_values['classified_date_available'] = get_mysql_datetime($ts);
		}
		if (!empty($duration_days)) $clean_values['classified_duration_days'] = $duration_days;
		if (!empty($values['location'])) $clean_values['location'] = trim(strip_tags($values['location']));
		if (!empty($values['content'])) $clean_values['content'] = trim(strip_tags($values['content']));
		if (!empty($values['author'])) $clean_values['author'] = trim(strip_tags($values['author']));
		if (!empty($values['classified_contact_email'])) $clean_values['classified_contact_email'] = trim(strip_tags($values['classified_contact_email']));
		if (!empty($values['price'])) $clean_values['price'] = turn_into_int($values['price']);
		if (!empty($clean_values['content'])) $clean_values['description'] = $this->string_summary($values['content']);
		
		$clean_values['display_contact_info'] = turn_into_int($values['display_contact_info']); // always either 0 or 1
		$clean_values['datetime'] = get_mysql_datetime();
		$clean_values['state'] = ($requires_approval) ? 'Pending' : 'Live';
		$clean_values['new'] = 0;
		
		$entity_id = reason_create_entity($this->get_site_id(), id_of('classified_type'), $user, $name, $clean_values);
		create_relationship($entity_id, $category, relationship_id_of('classified_to_classified_category'));
		
		$this->set_classified_id($entity_id);
	}

	function delete_classified($id)
	{
		$user_netid = reason_check_authentication();
		$user = get_user_id($user_netid ? $user_netid : 'classified_user');		
		$q = 'UPDATE entity SET state = "Deleted", last_edited_by = "'.$user.'" where id = ' . $id;
		db_query( $q , 'Error deleting classified' );
	}

	function email_classified($email_info)
	{
		$mailer = new Email($email_info['to'], $email_info['from'], $email_info['reply_to'], $email_info['subject'], $email_info['txt'], $email_info['html']);
		$mailer->send();
	}
	
	function get_email_subject()
	{
		$entity = new entity($this->get_classified_id());
		$entity_title = $entity->get_value('name');
		return 'New Classified Submission - ' . $entity_title;
	}
	
	function get_email_reply_to()
	{
		$entity = new entity($this->get_classified_id());
		$contact_email = $entity->get_value('classified_contact_email');
		return $contact_email;
	}
	
	/**
	 * Retuns an array of netids or e-mail addresses to whom notification of classified submissions will be sent
	 *
	 * If this is not populated, email notification will not be sent
	 */
	function get_email_to()
	{
		return array();
	}
	
	function get_email_from()
	{
		$entity = new entity($this->get_classified_id());
		$contact_email = $entity->get_value('classified_contact_email');
		return $contact_email;
	}
	
	function get_classified_entity_link_string()
	{
		$protocol = securest_available_protocol();
		$site_id = $this->get_site_id();
		$classified_id = $this->get_classified_id();
		$type_id = id_of('classified_type');
		
		$link = $protocol . '://' . REASON_WEB_ADMIN_PATH . '?' . 'site_id='.$site_id.'&type_id='.$type_id.'&id='.$classified_id.'&cur_module=Editor';
		return $link;
	}
	
	function &get_classified_type_fields()
	{
		static $classified_type_fields;
		if (!isset($classified_type_fields))
		{
			$classified_type_fields = get_fields_by_type(id_of('classified_type'));
		}
		return $classified_type_fields;
	}
	
	function string_summary($string) {
		return trim(implode(' ', array_slice(explode(' ', $string, 30), 0, -1))).'...';
	}

	/**
	 * Returns an empty request array - used to make links and redirects that clear classified request variable
	 * but do not interfere with other modules.
	 */
	function &get_empty_request_array()
	{
		$empty_request_array = array(
			'category' => '',
			'search' => '',
			'Go' => '',
			'classified_mode' => '',
			'sorted' => '',
			'item_id' => ''
		);
		return $empty_request_array;
	}
}