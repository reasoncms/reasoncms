<?
$GLOBALS[ '_classified_module_model' ][ module_basename( __FILE__) ] = 'ClassifiedModel';

include_once(TYR_INC.'email.php');

/**
 * Classified model - primary returns data. The controlling module and the views may use this model.
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
	var $requires_approval = false;
	
	/**
	 * Limit availability of items to a certain number of days
	 * @var int
	 */
	var $classified_duration_days;
	
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
	
	function &get_extra_cleanup_rules()
	{
		$extra_cleanup_rules = array();
		return $extra_cleanup_rules;
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
		return implode(", ", $category_names);
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
	
	/**
	 * Creates new entity
	 */
	function save_classified($values)
	{
		$user_netid = reason_check_authentication();
		$user = get_user_id($user_netid ? $user_netid : 'classified_user');
		if (!$user) trigger_error('User does not exist', FATAL);
		
		$name = reason_htmlspecialchars($values['name']);
		$category = reason_htmlspecialchars($values['category']);
		
		$values['location'] = reason_htmlspecialchars($values['location']);
		$values['content'] = reason_htmlspecialchars($values['content']);
		$values['author'] = reason_htmlspecialchars($values['author']);
		$values['classified_contact_email'] = reason_htmlspecialchars($values['classified_contact_email']);
		$values['display_contact_info'] = reason_htmlspecialchars($values['display_contact_info']);
		$values['price'] = reason_htmlspecialchars($values['price']);
		$values['classified_date_available'] = reason_htmlspecialchars($values['classified_date_available']);
		$values['description'] = $this->string_summary($values['content']);
		$values['datetime'] = get_mysql_datetime();
		$values['classified_duration_days'] = 30;
		if ($this->requires_approval == false) $values['state'] = 'Live';
		else $values['state'] = 'Pending';
		$values['new'] = 0;
		
		$entity_id = reason_create_entity($this->get_site_id(), id_of('classified_type'), $user, $name, $values);
		create_relationship($entity_id, $category, relationship_id_of('classified_to_classified_category'));
		
		$this->set_classified_id($entity_id);
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
	
	function string_summary($string) {
		return trim(tidy(implode(' ', array_slice(explode(' ', $string, 30), 0, -1)))).'...';
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