<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('function_libraries/url_utils.php');
include_once(CARL_UTIL_INC . 'db/table_admin.php');

/**
 * An administrative module that displays summary information about all site pages.
 *
 * Built for a site administrator who wanted to view all pages in an exportable format with links to the front end - this is quick and barebones but works.
 * 
 * @author Nathan White
 *
 * @todo make me more useful
 * @todo handle corrupt pages (no parent) in a clever way.
 */
class ReasonSitePagesModule extends DefaultModule// {{{
{
	function SitePagesModule( &$page )
	{
		$this->admin_page =& $page;
	}

	function init()
	{
		parent::init();
		$this->admin_page->title = 'Site Pages';
		$this->admin_page->set_breadcrumbs( array(''=> 'Site Pages' ) );
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
		$this->_get_site_page_data(); // lets do the work in admin
	}
	
	/**
	 * Return the current site id if
	 *
	 * - site_id is valid and refers to a reason site entity
	 * - the logged in user has access to the site
	 * - the logged in user has "edit" privs
	 * @return int site_id
	 */
	function _get_validated_site_id()
	{
		$apparent_site_id = (int) $this->admin_page->site_id;
		if ($apparent_site_id)
		{
			$apparent_site = new entity($apparent_site_id);
			if (reason_is_entity($apparent_site, 'site') && reason_check_access_to_site($apparent_site_id) && reason_check_privs('edit'))
			{
				return $apparent_site_id;
			}
		}
		return false;
	}
	
	function _get_site_page_data()
	{
		if (!isset($this->_site_page_data))
		{
			$site_pages = $this->_get_site_pages();
			if ($site_pages) foreach ($site_pages as $k => $v)
			{
				$user_entity_lm = ($v->get_value('last_edited_by')) ? new entity($v->get_value('last_edited_by')) : false;
				$username_lm = ($user_entity_lm && reason_is_entity($user_entity_lm, 'user')) ? $user_entity_lm->get_value('name') : '(unknown)';
				$user_entity_cb = ($v->get_value('created_by')) ? new entity($v->get_value('created_by')) : false;
				$username_cb = ($user_entity_cb && reason_is_entity($user_entity_cb, 'user')) ? $user_entity_cb->get_value('name') : '(unknown)';
				$pretty_date = prettify_mysql_datetime($v->get_value('last_modified'));
				$page_url = $v->get_value('page_url');
				if (!empty($page_url))
				{
					$this->_site_page_data[$k]['page_url'] = ($this->_should_provide_html_link()) ? '<a href="'.$page_url.'">'.$page_url.'</a>' : $page_url;
					$this->_site_page_data[$k]['author'] = $v->get_value('author');
					$this->_site_page_data[$k]['created_by'] = $username_cb;
					$this->_site_page_data[$k]['last_edited_by'] = $username_lm;
					$this->_site_page_data[$k]['last_modified'] = $pretty_date;
				}
			}
			else $this->_site_page_data = false;
		}
		return $this->_site_page_data;
	}
	
	/**
	 * Grab the site page entities respect the table_sort_order field if table_sort_field is equal to last_modified
	 */
	function _get_site_pages()
	{
		if (!isset($this->_site_pages))
		{
			if ($site_id = $this->_get_validated_site_id())
			{
				$sort_order_string = $this->_get_sort_order_string();
				$es = new entity_selector($site_id);
				$es->add_type(id_of('minisite_page'));
				$es->add_relation('(entity.name != "") AND ((url.url = "") OR (url.url IS NULL))'); // only pages, not custom urls
				$es->set_order('last_modified ' . $sort_order_string);
				$this->_site_pages = $es->run_one();
				if ($this->_site_pages) $this->_augment_page_entities($this->_site_pages);
			}
			else $this->_site_pages = false;
		}
		return $this->_site_pages;
	}

	/**
	 * If a .csv export has been requested we just want the link in the data - not the html link
	 */
	function _should_provide_html_link()
	{
		if ( isset($_REQUEST['table_action']) && ($_REQUEST['table_action'] == 'export') ) return false;
		return true;
	}
	
	/**
	 * If table_sort_field in the request is equal to last_modified and table_sort_order is DESC or ASC - return the table_sort_order - otherwise DESC
	 */
	function _get_sort_order_string()
	{
		if (isset($_REQUEST['table_sort_field']) && isset($_REQUEST['table_sort_order']))
		{
			if ($_REQUEST['table_sort_field'] === 'last_modified')
			{
				if ( (strtolower($_REQUEST['table_sort_order']) === 'asc') || (strtolower($_REQUEST['table_sort_order']) === 'desc') )
				{
					return strtoupper($_REQUEST['table_sort_order']);
				}
			}
		}
		return 'DESC';
	}
	
	/**
	 * Lets set some extra values - specifically ... the URL!
	 * 
	 * @todo instead of just supressing errors in reason_get_page_url perhaps we should flag these pages somehow?
	 */
	function _augment_page_entities(&$entities)
	{
		foreach ($entities as $k => $a_fun_page_entity)
		{
			$url = @reason_get_page_url($a_fun_page_entity);
			$entities[$k]->set_value('page_url', $url);
		}
	}

	function run()
	{	
		$site_pages = $this->_get_site_page_data();
		if ($site_pages)
		{
			$ta = new TableAdmin();
			$ta->init_view_no_db(array('page_url' => 'URL', 
									   'created_by' => 'Created By Username',
									   'author' => 'Author',
									   'last_edited_by' => 'Last Edited Username',
									   'last_modified' => 'Last Modified'), true);
			$ta->set_data_from_array($site_pages);
			$ta->set_show_actions_first_cell(false);
			$ta->set_show_actions_last_cell(false);
			$ta->set_fields_that_allow_sorting(array('last_modified'));
			$ta->set_fields_to_entity_convert(array('created_by','author','last_edited_by','last_modified'));
			$ta->run();
		}
		elseif (!$this->_get_validated_site_id())
		{
			echo '<p>You can only use this module in the context of a Reason site to which you have proper access privileges.</p>';
		}
		else
		{
			echo '<p>The site does not have any valid pages.</p>';
		}
	}
}
?>