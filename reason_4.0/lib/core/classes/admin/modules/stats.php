<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
include_once( DISCO_INC . 'disco.php' );

/**
 * An administrative module that displays basic type-by-type statistics about usage by type
 * 
 * @author Matt Ryan
 */
class StatsModule extends DefaultModule// {{{
{
	protected $form;
	protected $sites;
	protected $types;
	protected $live_sites;
	function ReasonStatsModule( &$page )
	{
		$this->admin_page =& $page;
	}

	function init()
	{
		parent::init();
		$this->admin_page->title = 'Stats';
	}
	function get_module_info()
	{
		return '<p>This module produces a report of how many live entities exist in Reason for each content type.</p>';
	}
	function run()
	{
		echo $this->get_module_info();
		$d = $this->get_form();
		
		$d->run();
		
		if($d->successfully_submitted())
		{
		   echo '<ul>';
		   foreach($this->get_types() as $type)
		   {
			   echo '<li>';
			   echo $type->get_value('name').': ';
			   echo $this->get_count_of_items($type, $d->get_value('site_state'));
			   echo '</li>';
		   }
		   echo '</ul>';
		}
	}

	function get_count_of_items($type, $site_liveness = 'all')
	{
		switch($site_liveness){
			case 'live':
				$es = new entity_selector(array_keys($this->get_live_sites()));
				break;
			case 'notlive':
				$es = new entity_selector(array_keys($this->get_non_live_sites()));
				break;
			case 'all':
			default:
				$es = new entity_selector();
		}
		$es->add_type($type->id());
		$es->limit_tables();
		$es->limit_fields();
		return $es->get_one_count();
	}
	
	function get_live_sites()
	{
		if(!isset($this->live_sites))
		{
			$es = new entity_selector(id_of('master_admin'));
			$es->add_type(id_of('site'));
			$es->add_relation('site_state = "Live"');
			$this->live_sites = $es->run_one();
		}
		return $this->live_sites;
	}
	
	function get_non_live_sites()
	{
		if(!isset($this->non_live_sites))
		{
			$es = new entity_selector(id_of('master_admin'));
			$es->add_type(id_of('site'));
			$es->add_relation('site_state = "Not Live"');
			$this->non_live_sites = $es->run_one();
		}
		return $this->non_live_sites;
	}

	function get_types()
	{
		if(!isset($this->types))
		{
			$es = new entity_selector(id_of('master_admin'));
			$es->add_type(id_of('type'));
			$this->types = $es->run_one();
		}
		return $this->types;
	}
	
	function get_form()
	{
		if(!isset($this->form))
		{
			$d = new Disco();
			$d->add_element('site_state','radio',array('options'=>array('all'=>'All','live'=>'Live','notlive'=>'Not Live')));
			$d->set_value('site_state','all');
			$d->set_actions(array('Run'));
			$this->form = $d;
		}
		return $this->form;
	}
}
