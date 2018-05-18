<?php
/**
 * @package reason
 * @subpackage admin
 */

include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php' );
ini_set('max_execution_time', 300);

/**
 * Include the default module
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('classes/page_types.php');
reason_include_once('function_libraries/url_utils.php');
	
/**
 * Exports reason images in a zip file
 */
class ReasonTypeUsageModule extends DefaultModule
{
	protected $errors = array();
	function init()
	{
		$this->admin_page->title = 'Site Type/Module Usage';
	}
	
	function run()
	{
		if(empty($this->admin_page->request['site_id']) || $this->admin_page->request['site_id'] != id_of('master_admin'))
		{
			echo 'This module only runs in the master admin.';
			return;
		}
		
		$d = new Disco();
		$d->add_element('report_on', 'radio_no_sort', array('options'=>['types'=>'Type usage', 'modules' => 'Module usage']));
		$d->set_value('report_on', 'types');
		$d->add_element('report_type', 'radio_no_sort', array('options'=>['provisioned'=>'Availability (for types) or Presence (for modules)', 'counts' => 'Counts (Get actual numbers; this may be a slower report)']));
		$d->set_value('report_type', 'provisioned');
		$d->add_element('ownership', 'checkboxgroup', array('options'=>['owns' => 'Owns', 'borrows' => 'Borrows']));
		$d->set_value('ownership', ['owns','borrows']);
		$d->add_comments('ownership', form_comment('(Only applies to the types report)') );
		$d->add_element('site_types', 'checkboxgroup', array('options'=>$this->entities_to_options($this->get_site_types())));
		$d->add_comments('site_types', form_comment('Leave empty to report on all live sites') );
		$d->add_element('url_start');
		$d->add_comments('url_start', form_comment('Limit sites by their base URL') );
		$d->set_actions(['run'=>'Run']);
		$d->run();
		
		if($d->successfully_submitted())
		{
			$this->output($d);
		}
	}
	
	function entities_to_options($entities)
	{
		$ret = array();
		foreach($entities as $e)
		{
			$ret[$e->id()] = $e->get_value('name');
		}
		return $ret;
	}
	
	function get_site_types()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('site_type_type'));
		return $es->run_one();
	}
	
	function output($form)
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('site'));
		$es->add_relation('site_state = "Live"');
		$site_types = $form->get_value('site_types');
		if(!empty($site_types))
		{
			$es->add_left_relationship($site_types, relationship_id_of('site_to_site_type'));
		}
		$url_start = $form->get_value('url_start');
		if(!empty($url_start))
		{
			$es->add_relation('site.base_url LIKE "' . reason_sql_string_escape($url_start) . '%"');
		}
		$sites = $es->run_one();
		
		$columns = $this->get_columns( $form );
		
		echo '<table>';
		echo '<tr><th>Site Name</th><th>Site ID</th><th>Site URL</th>';
		foreach($columns as $column_name) {
			echo '<th>' . $column_name . '</th>';
		}
		echo '</tr>';
		foreach($sites as $site) {
			echo '<tr>';
			echo '<td>' . $site->get_value('name') . '</td>';
			echo '<td>' . $site->id() . '</td>';
			echo '<td>' . reason_get_site_url( $site ) . '</td>';
			foreach($columns as $column_key => $column_name) {
				echo '<td>';
				switch($form->get_value('report_type'))
				{
					case 'counts':
						echo $this->get_count($site, $column_key, $form);
						break;
					default:
						if($this->get_availability($site, $column_key, $form)) {
							echo 'x';
						}
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
	
	function get_columns( $form )
	{
		switch( $form->get_value('report_on') )
		{
			case 'modules':
				$ret = array();
				$rpts = new ReasonPageTypes();
				foreach($rpts->get_all_modules() as $module)
				{
					$ret[$module] = $module;
				}
				return $ret;
				break;
			case 'types':
			default:
				$es = new entity_selector(id_of('master_admin'));
				$es->add_type(id_of('type'));
				return $this->entities_to_options($es->run_one());
		}
	}
	
	function get_availability($site, $column_key, $form)
	{
		switch( $form->get_value('report_on') )
		{
			case 'modules':
				$es = $this->get_page_types_es($site, $column_key);
				if(empty($es))
				{
					return 0;
				}
				$es->set_num(1);
				return $es->get_one_count();
				break;
			case 'types':
			default:
				return $site->has_left_relation_with_entity( $column_key, 'site_to_type');
		}
	}
	
	function get_count($site, $column_key, $form)
	{
		switch( $form->get_value('report_on') )
		{
			case 'modules':
				$es = $this->get_page_types_es($site, $column_key);
				if(empty($es))
				{
					return 0;
				}
				return $es->get_one_count();
			case 'types':
			default:
				$es = new entity_selector($site->id());
				$es->add_type($column_key);
				if(count($form->get_value('ownership')) == 1) // both is the same as none, so we only have to add this when a single option is chosen
				{
					$es->set_sharing( $ownership );
				}
				return $es->get_one_count();
		}
	}
	
	function get_page_types_es($site, $module)
	{
		$rpts = new ReasonPageTypes();
		$page_types = $rpts->get_page_type_names_that_use_module($module);
		if(empty($page_types))
		{
			return 0;
		}
		$es = new entity_selector($site->id());
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables();
		$es->limit_fields();
		array_walk($page_types, 'reason_sql_string_escape');
		$es->add_relation('custom_page IN ("'.implode('","', $page_types).'")');
		return $es;
	}
}
