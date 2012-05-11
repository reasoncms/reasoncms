<?php
/**
 * @package reason
 * @subpackage scripts
 */
/**
 * Modify page types
 */
class MigratorScreen4 extends MigratorScreen
{
	function step_init()
	{
		$this->site_id = $this->helper->get_site_id();
		if (!$this->site_id) $this->redirect_to_screen(1);

		$this->site_publication_names_by_id = $this->helper->get_site_publication_names_by_id();
		if (!$this->site_publication_names_by_id) $this->redirect_to_screen(3);
		
		$this->user_id = $this->helper->get_user_id();
		$this->pages_using_news_modules = $this->helper->get_pages_using_news_modules();
		$this->publication_module_page_types = $this->helper->get_publication_module_page_types();
		$this->recommended_page_type_map = $this->helper->get_recommended_page_type_mapping();
	}
	
	function step_pre_show_form()
	{
		echo '<h2>Modify Page Types</h2>';
		if (!empty($this->pages_using_news_modules))
		{
			echo '<p>This phase does some analysis of page types and allows you to modify the pages that currently use old-style news to use page types 
				  compatible with the new publications module. This process is imperfect - many custom page types cannot be mapped directly onto a 
			      publication page type and will require the creation of new page types. Also, CSS files may need to be updated depending upon whether 
			      or not the site was using custom CSS for the display of news pages/sidebars.</p>';
		}
		else
		{
			echo '<p>There are no pages on the site using old-style news. You are probably finished!</p>';
			$options[] = '<a href="'.carl_construct_link().'">Choose another site</a>';
			echo '<ul><li>'.implode('</li><li>',$options).'</li></ul>';
		}	  
	}
	
	function on_every_time()
	{
		if (!empty($this->pages_using_news_modules))
		{
			foreach ($this->pages_using_news_modules as $k=>$page)
			{
				$grp_name = 'page' . $k;
				$cpt_name = 'cpt_page'.$k;
				$npt_name = 'npt_page'.$k;
				$pt_value = $page->get_value('custom_page');
				
				$this->add_element($cpt_name, 'solidtext');
				$this->set_value($cpt_name, $page->get_value('custom_page'));
				$this->add_element($npt_name, 'select_no_sort', array('options' => $this->publication_module_page_types, 'add_empty_value_to_top' => true));
				$this->add_element_group('table', $grp_name, array($cpt_name, $npt_name), array('use_element_labels' => false, 
																								'rows' => array('Current Page Type: ', 'New Page Type: ')) );
				$pt_comments =& $this->helper->get_page_type_comments();
				
				$page_header = '<h3>'.$page->get_value('name').'</h3>';
				if (isset($pt_comments[$pt_value])) $page_header .= '<p>'.$pt_comments[$pt_value].'</p>';
				
				$this->set_display_name($grp_name, $page_header);
				
				if (isset($this->recommended_page_type_map[$pt_value]))
				{
					$this->set_value($npt_name, $this->recommended_page_type_map[$pt_value]);
				}
				$this->add_element('pubs_for_page'.$k, 'checkboxgroup', array('options' => $this->site_publication_names_by_id, 'display_name' => 'Publication(s) to relate to page'));
			}
		}
		else
		{
			$this->actions = array();
		}
	}
	
	function pre_error_check_actions()
	{
		foreach ($this->pages_using_news_modules as $k=>$page)
		{
			
		}
	}
	
	function run_error_checks()
	{
		foreach ($this->pages_using_news_modules as $k=>$page)
		{
			$npt_name = 'npt_page'.$k;
			$ar_name = $this->helper->get_allowable_relationship_for_page_type($this->get_value($npt_name));
			$pubs_for_page = $this->get_value('pubs_for_page'.$k);
			if (empty($pubs_for_page))
			{
				$this->set_error('pubs_for_page'.$k, 'You must choose a publication to relate to ' . $page->get_value('name'));
			}
			elseif ( (count($pubs_for_page) > 1) && ($this->helper->get_allowable_relationship_for_page_type($this->get_value($npt_name)) == 'page_to_publication') )
			{
				$this->set_error('pubs_for_page'.$k, 'You can only choose one publication to related to the page for the new page type ' . $npt_name);
			}
		}
	}
	
	function process()
	{
		foreach ($this->pages_using_news_modules as $k=>$page)
		{
			$npt_name = 'npt_page'.$k;
			$ar_name = $this->helper->get_allowable_relationship_for_page_type($this->get_value($npt_name));
			$pubs_for_page = $this->get_value('pubs_for_page'.$k);
			
			reason_update_entity($k, $this->user_id, array('custom_page' => $this->get_value($npt_name)));
			foreach ($pubs_for_page as $pub_id)
			{
				create_relationship($k, $pub_id, relationship_id_of($ar_name)); 
			}
		}
	}
	
	function &get_values_to_pass()
	{
		$values = array('active_screen' => "4", 'site_id' => $this->site_id);
		return $values;
	}
}
?>