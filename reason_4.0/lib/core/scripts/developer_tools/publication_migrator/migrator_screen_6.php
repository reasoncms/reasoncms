<?php
/**
 * @package reason
 * @subpackage scripts
 */
/**
 * Issues
 */
class MigratorScreen6 extends MigratorScreen
{
function step_pre_show_form()
	{
		echo '<h2>Attach Issues</h2>';
		if (!empty($this->unattached_news_item_names_by_id))
		{
			echo '<p>In this step, choose a publication and select unattached issues to attach to the publication.</p>';
		}
	}
	
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_name = $this->helper->get_site_name();
		$this->new_publication_link = carl_make_link(array('active_screen' => "3"));
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->unattached_issue_names_by_id = $this->helper->get_unattached_issue_names_by_id();
		if (empty($this->site_publication_names_by_id)) $this->redirect_to_screen("3");
		if (empty($this->unattached_issue_names_by_id)) $this->redirect_to_screen("2");
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		$this->add_element('publication_id', 'select_no_sort', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Choose a Publication'));
		$this->set_comments('publication_id', form_comment('<p>...Or <a href="'.$this->new_publication_link.'">create a new publication<a/></p>'));
		$this->add_element('issues', 'checkboxgroup', array('options' => $this->unattached_issue_names_by_id, 'display_name' => 'Choose Issues to Attach'));
		$this->set_value('issues', array_keys($this->unattached_issue_names_by_id)); // check all by default
	}
	
	function run_error_checks()
	{
		if (!$this->get_value('issues'))
		{
			$this->set_error('issues', 'You must select at least one issue to attach to the publication.');
		}
	}
	
	function process()
	{
		$pub_id = $this->get_value('publication_id');
		$issues_to_link = $this->get_value('issues');
		foreach ($issues_to_link as $issue_id)
		{
			create_relationship($issue_id, $pub_id, relationship_id_of('issue_to_publication'));
		}
		
		// update the publication - set has_issues to "Yes"
		reason_update_entity($pub_id, $this->user_id, array('has_issues' => 'yes'));
	}
	
	// we'll jump to same screen in case there are others to associate ... if finished, the init will bounce us on to the next phase
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->site_id);
		return $values;
	}
}
?>