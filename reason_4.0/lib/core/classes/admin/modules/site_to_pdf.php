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

/**
 * An administrative module that can generate a PDF from all or part of a site.
 * The server must have wkhtmltopdf installed and accessible to the web user.
 *
 * @author Mark Heiman
 *
 */
class ReasonSiteToPDFModule extends DefaultModule// {{{
{
	protected $_site_pages;
	protected $error;

	function ReasonSiteToPDFModule( &$page )
	{
		$this->admin_page =& $page;
	}

	function init()
	{
		parent::init();
		$this->admin_page->title = 'Site to PDF';
		$this->admin_page->set_breadcrumbs( array(''=> 'Site to PDF' ) );
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/modules/site_to_pdf.js');
		$this->_get_site_pages();

		if (isset($_POST['pages']))
		{
			if ( ($temp_file_path = $this->build_pdf()) && file_exists($temp_file_path))
			{
				$site = new entity($this->admin_page->site_id);

				header('Content-type: application/pdf');
				header('Content-disposition: attachment; filename="'. $site->get_value('name').'.pdf"');
				header('Content-length: ' . filesize($temp_file_path));
				readfile($temp_file_path);
				exit;
			}
			else
			{
				$this->error = 'Failed to build PDF file.';
			}
		}
	}

	function run()
	{
		echo '<p>This module will allow you to generate a PDF of all or some of the pages in this site.
				The pages are listed below; check or uncheck pages to include or exclude them from the
				PDF.</p>
				<p>By default, pages are unchecked if they are hidden from the site navigation, or if
				they are restricted by an access group. You can check a restricted page, but you\'ll
				probably just end up with a copy of the login page in your PDF.<p>';

		if ($this->_site_pages)
		{
			if ($this->error) echo '<p class="error">'.$this->error.'</p>';

			$root = $this->find_site_root();
			$page_tree[$root] = $this->build_page_tree($root);

			echo '<form method="POST">';
			$this->draw_page_tree($page_tree);
			echo '<p>Depending on the number of pages, PDFs can take a minute or two to complete.</p>';
			echo '<input type="submit" value="Build PDF" />';
			echo '<form>';

			//pray($this->_site_pages);
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

	/**
	 * Grab the site page entities
	 */
	function _get_site_pages()
	{
		if (!isset($this->_site_pages))
		{
			if ($site_id = $this->_get_validated_site_id())
			{
				$es = new entity_selector($site_id);
				$es->add_type(id_of('minisite_page'));
				$es->add_relation('(entity.name != "") AND ((url.url = "") OR (url.url IS NULL))'); // only pages, not custom urls
				$es->add_left_relationship_field('minisite_page_parent', 'entity', 'id', 'parent_id');
				$es->add_right_relationship_field('page_to_access_group', 'entity', 'id', 'access_group_id', false);
				$es->set_order('sort_order ASC');
				$this->_site_pages = $es->run_one();
				if ($this->_site_pages) $this->_augment_page_entities($this->_site_pages);
			}
			else $this->_site_pages = false;
		}
		return $this->_site_pages;
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

	function build_page_tree($root)
	{
		foreach ($this->_site_pages as $page)
		{
			if ($page->get_value('parent_id') == $root && $page->id() != $root)
			{
				$children[$page->id()] = $this->build_page_tree($page->id());
			}
		}

		if (isset($children))
			return $children;
		else
			return $root;
	}

	function draw_page_tree($tree)
	{
		echo '<ul class="pageTree">'."\n";
		foreach ($tree as $key => $val)
		{
			$notes = array();
			if ($this->_site_pages[$key]->get_value('access_group_id'))
				$notes[] = 'restricted';
			if ($this->_site_pages[$key]->get_value('nav_display') == 'No')
				$notes[] = 'no nav';

			if (empty($_REQUEST['pages']) && empty($notes))
				$checked = 'checked';
			else if (!empty($_REQUEST['pages']) && in_array($key, $_REQUEST['pages']))
				$checked = 'checked';
			else
				$checked = '';

			echo '<li>';
			echo '<input type="checkbox" name="pages[]" value="'.$key.'" '.$checked.'/> ';
			echo $this->_site_pages[$key]->get_value('name');

			if ($notes)
				echo ' <span class="pageNote">('.join(', ', $notes).')</span>';
			echo '</li>'."\n";
			if (is_array($val))
			{
				$this->draw_page_tree($val);
			}
		}
		echo '</ul>'."\n";
	}

	function find_site_root()
	{
		foreach ($this->_site_pages as $page)
		{
			if ($page->id() == $page->get_value('parent_id'))
			{
				return $page->id();
			}
		}
	}

	function build_pdf()
	{
		$url_string = '';
		foreach($_REQUEST['pages'] as $page_id)
		{
			if ($url = $this->_site_pages[$page_id]->get_value('page_url'))
				$url_string .= $url . ' ';
		}

		// Figure out what to call and where to place the temporary pdf file.
		$temp_file_path = sys_get_temp_dir() . "/" . $this->admin_page->site_id . ".pdf";

		// Download the pages as a pdf.
		$command = 'wkhtmltopdf -l --print-media-type  ' . $url_string . $temp_file_path;
		$output = shell_exec($command);

		// Ensure that a non-empty file was created.
		if (filesize($temp_file_path) > 1000) {
			return $temp_file_path;
		}
	}
}
