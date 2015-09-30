<?php
/**
 * @package reason
 * @subpackage scripts
 */
 /**
 * Attach posts to a publication
 */
class MigratorScreen2 extends MigratorScreen
{
	function step_pre_show_form()
	{
		echo '<h2>Attach News Items</h2>';
		if (!empty($this->unattached_news_item_names_by_id))
		{
			echo '<p>In this step, choose a publication and select unattached published news items to attach to the publication.</p>';
		}
		else
		{
			if (empty($this->unattached_news_items_names_by_id))
			{
				echo '<p>All news items on the site are attached to a publication.</p>';
			}
			if ($this->site_has_issue_type)
			{
				if (empty($this->unattached_issue_names_by_id)) echo '<p>All issues on the site are attached to a publication.</p>';
				else $options[] = '<a href="'.carl_make_link(array('active_screen' => 6)).'">Attach issues to a publication</a>';
			}
			if ($this->site_has_section_type)
			{
				if (empty($this->unattached_section_names_by_id)) echo '<p>All news sections attached to a publication.</p>';
				else $options[] = '<a href="'.carl_make_link(array('active_screen' => 7)).'">Attach news sections to a publcation</a>';
			}
			echo '<p>You may proceed do the following:</p>';
			$options[] = '<a href="'.carl_make_link(array('active_screen' => 4)).'">Modify page types on the site</a>';
			$options[] = '<a href="'.carl_make_link(array('active_screen' => 3)).'">Create a new publication</a>';
			$options[] = '<a href="'.carl_construct_link().'">Choose another site</a>';
			echo '<ul><li>'.implode('</li><li>',$options).'</li></ul>';
		}
	}
	
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);
		$this->user_id = $this->helper->get_user_id();
		$this->site_name = $this->helper->get_site_name();
		$this->site_has_issue_type = $this->helper->does_site_have_issue_type();
		$this->site_has_section_type = $this->helper->does_site_have_section_type();
		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		$this->new_publication_link = carl_make_link(array('active_screen' => "3"));
		if (empty($this->site_publication_names_by_id)) $this->redirect_to_screen("3");
		else
		{
			$this->unattached_news_item_names_by_id = $this->helper->get_unattached_news_item_names_by_id();
			if ($this->site_has_issue_type)
			{
				$this->unattached_issue_names_by_id = $this->helper->get_unattached_issue_names_by_id();
			}
			if ($this->site_has_section_type)
			{
				$this->unattached_section_names_by_id = $this->helper->get_unattached_section_names_by_id();
			}
		}
	}
	
	/**
	 * @todo add javascript hooks to check / uncheck all
	 */
	function on_every_time()
	{
		if (!empty($this->unattached_news_item_names_by_id))
		{
			$this->add_element('publication_id', 'select_no_sort', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Choose a Publication'));
			$this->set_comments('publication_id', form_comment('<p>...Or <a href="'.$this->new_publication_link.'">create a new publication<a/></p>'));
			$this->add_element('news_items', 'checkboxgroup', array('options' => $this->unattached_news_item_names_by_id, 'display_name' => 'Choose News Items to Attach'));
			$this->set_value('news_items', array_keys($this->unattached_news_item_names_by_id)); // check all by default
		}
		else
		{
			$this->actions = array();
		}
	}
	
	function run_error_checks()
	{
		if (!$this->get_value('news_items'))
		{
			$this->set_error('news_items', 'You must select at least one news item to attach to the publication.');
		}
	}
	
	function process()
	{
		if (!empty($this->unattached_news_item_names_by_id))
		{
			$pub_id = $this->get_value('publication_id');
			$news_items_to_link = $this->get_value('news_items');
			foreach ($news_items_to_link as $item_id)
			{
				create_relationship($item_id, $pub_id, relationship_id_of('news_to_publication'));
			}
		}
	}
	
	// we'll jump to same screen in case there are others to associate ... if finished, the init will bounce us on to the next phase
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "2", 'site_id' => $this->site_id);
		return $values;
	}
}
?>