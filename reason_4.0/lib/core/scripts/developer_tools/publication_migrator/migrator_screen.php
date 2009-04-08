<?php
/**
 * @package reason
 * @subpackage scripts
 */
/**
 * Base Migrator Screen Class
 * @author Nathan White
 */
class MigratorScreen extends Disco
{
	var $helper;
	var $redirect_after_process = true;
	
	function init( $externally_setup = true )
	{
		$this->step_init();
		parent::init( $externally_setup );
	}
	
	function step_init()
	{
	}
	
	function pre_show_form()
	{
		echo '<h1>Publication Migration Wizard</h1>';
		$this->show_site_status();
		$this->step_pre_show_form();
	}
	
	function step_pre_show_form()
	{
	}
	
	function where_to()
	{
		if ($this->redirect_after_process)
		{
			$values =& $this->get_values_to_pass();
			return carl_make_redirect($values);
		}
	}
	
	function redirect_to_screen($screen)
	{
		$redirect = carl_make_redirect(array('active_screen' => $screen));
		header("Location: " . $redirect );
		exit;
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => '');
		return $values;
	}
	
	/**
	 * Generate the status sidebar
	 */
	function show_site_status()
	{
		$site_id = $this->helper->get_site_id();
		if ($site_id > 0)
		{
			$status_html[] = '<strong>Active site is ' . $this->helper->get_site_name() . '</strong>';
			$publications = $this->helper->get_site_publication_names_by_id();
			$pub_count = (!empty($publications)) ? count($publications) : '0';
			$pub_string = ($pub_count == 1) ? 'publication' : 'publications';
			$status_html[] = 'Site has ' . $pub_count . ' ' . $pub_string;
			
			$unattached_news_items = $this->helper->get_unattached_news_item_names_by_id();
			
			if (!empty($unattached_news_items)) $status_html[] = 'Site has ' . count($unattached_news_items) . ' unattached news items';
			$attached_news_items = $this->helper->get_attached_news_item_names_by_id();
			
			
			if (!empty($attached_news_items)) $status_html[] = 'Site has ' . count($attached_news_items) . ' attached news items';
			
			$unattached_issues = $this->helper->get_unattached_issue_names_by_id();
			if (!empty($unattached_issues)) $status_html[] = 'Site has ' . count($unattached_issues) . ' unattached issues';
			$attached_issues = $this->helper->get_attached_issue_names_by_id();
			if (!empty($attached_issues)) $status_html[] = 'Site has ' . count($attached_issues) . ' attached issues';
			
			$unattached_sections = $this->helper->get_unattached_section_names_by_id();
			if (!empty($unattached_sections)) $status_html[] = 'Site has ' . count($unattached_sections) . ' unattached news sections';
			$attached_sections = $this->helper->get_attached_section_names_by_id();
			if (!empty($attached_sections)) $status_html[] = 'Site has ' . count($attached_sections) . ' attached news sections';
			
			
			$status_html = '<ul><li>' . implode('</li><li>', $status_html) . '</li></ul>';
			$start_over_link = true;
		}
		else
		{
			$status_html = '<ul><li>No site is selected</li></ul>';
		}
		echo '<div id="status">';
		echo '<h2>Status</h2>';
		echo $status_html;
		if (isset($start_over_link)) echo '<p><a href="'.carl_construct_link().'">Start Over</a></p>';
		echo '</div>';
	}
}
?>