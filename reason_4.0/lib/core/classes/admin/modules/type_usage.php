<?php
/**
 * @package reason
 * @subpackage admin
 */

include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php' );
/**
 * Include the default module
 */
reason_include_once('classes/admin/modules/default.php');
	
/**
 * Exports reason images in a zip file
 */
class ReasonTypeUsageModule extends DefaultModule
{
	protected $errors = array();
	function init()
	{
		$this->admin_page->title = 'Type Usage';
	}
	
	function run()
	{
		if(empty($this->admin_page->request['site_id']) || $this->admin_page->request['site_id'] != id_of('master_admin'))
		{
			echo 'This module only runs in the master admin.';
			return;
		}
		
		$d = new Disco();
		$d->add_element('report_type', 'radio_no_sort', array('options'=>['provisioned'=>'Availability', 'counts' => 'Counts']));
		$d->set_value('report_type', 'provisioned');
		$d->set_actions(['run'=>'Run']);
		$d->run();
		
		if($d->successfully_submitted())
		{
			$this->output($d);
		}
	}
	
	function output($form)
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('site'));
		$es->add_relation('site_state = "Live"');
		$sites = $es->run_one();
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('type'));
		$types = $es->run_one();
		
		echo '<table>';
		echo '<tr><th>Site Name</th><th>Site ID</th>';
		foreach($types as $type) {
			echo '<th>' . $type->get_value('name') . '</th>';
		}
		echo '</tr>';
		foreach($sites as $site) {
			echo '<tr>';
			echo '<td>' . $site->get_value('name') . '</td>';
			echo '<td>' . $site->id() . '</td>';
			foreach($types as $type) {
				echo '<td>';
				switch($form->get_value('report_type'))
				{
					case 'counts':
						echo $this->get_count($site, $type);
						break;
					default:
						if($site->has_left_relation_with_entity( $type , 'site_to_type')) {
							echo 'x';
						}
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
	
	function get_count($site, $type)
	{
		$es = new entity_selector($site->id());
		$es->add_type($type->id());
		return $es->get_one_count();
	}
}
