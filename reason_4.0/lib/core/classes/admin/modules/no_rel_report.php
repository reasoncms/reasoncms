<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
reason_include_once('classes/admin/modules/default.php');
include_once(DISCO_INC.'disco.php');
/**
 * Report on entities that don't have a relationship of a particular type
 */
class NoRelReportModule extends DefaultModule
{
	protected $form;
	function NoRelReportModule( &$page )
	{
		$this->admin_page =& $page;
	}
	
	function init()
	{
		$this->admin_page->title = 'List Entities With/Without Particular Relationships';               	
	}
	
	function run()
	{
		echo '<div class="noRelReport">';
		$form = $this->get_form();
		$form->run();
		
		if($form->successfully_submitted())
			echo $this->get_report($form);
		
		echo '</div>';
	}
	
	function get_form()
	{
		if(isset($this->form))
			return $this->form;
		
		
		$form = new Disco;
		$form->set_form_method('get');
		$form->add_element('type_id','select', array('options'=>$this->entities_to_options($this->get_types())));
		$form->add_required('type_id');
		$alrels = array();
		foreach($this->get_allowable_relationships() as $id => $name)
			$alrels[$name] = $name;
		$form->add_element('allowable_relationship_name','select', array('options' => $alrels ));
		$form->add_required('allowable_relationship_name');
		$form->add_element('direction','radio_no_sort', array('options'=>array('left' => 'left','right' => 'right')));
		$form->add_required('direction');
		$form->add_element('site_id','select', array('options'=>$this->entities_to_options($this->get_sites())));
		$form->add_element('cur_module','hidden');
		$form->add_element('report','radio_no_sort', array('options'=>array('norel' => 'No Relationship','rel' => 'Relationship')));
		$form->set_value('report','norel');
		$form->set_value('cur_module','NoRelReport');
		$form->set_actions(array('get_report'=>'Get Report'));
		$this->form = $form;
		return $this->form;
		
	}
	
	function get_data($form)
	{
		$type_id = (integer) $form->get_value('type_id');
		$site_id = (integer) $form->get_value('site_id');
		$alrel_name = (string) $form->get_value('allowable_relationship_name');
		$direction = $form->get_value('direction');
		
		if(empty($direction) || !in_array($direction,array('left','right')))
		{
			echo $direction;
			trigger_error('Direction must be "left" or "right"');
			return array();
		}
		
		if(empty($type_id))
		{
			trigger_error('Type id must be an integer');
			return array();
		}
		
		if(empty($alrel_name))
		{
			
			trigger_error('Allowable relationship name is needed');
			return array();
		}
		
		if($site_id && !array_key_exists($site_id, $this->get_sites()))
		{
			return array();
		}
		
		//$alrel_id = relationship_id_of($alrel_name)
		
		if($site_id)
			$es = new entity_selector($site_id);
		elseif(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			$es = new entity_selector(array_keys($this->get_sites()));
		else
			$es = new entity_selector();
		
		$es->add_type($type_id);
		switch($direction) {
			case 'left':
				$rel_field_info = $es->add_left_relationship_field( $alrel_name , 'entity', 'id', 'rel_id');
				break;
			case 'right':
				$rel_field_info = $es->add_right_relationship_field( $alrel_name , 'entity', 'id', 'rel_id');
				break;
			default:
				trigger_error('Unrecognized value for direction. Unable to produce report.');
				return array();
		}
		//pray($rel_field_info);
		//die();
		// return array( $alias => array( 'table_orig' => $table, 'table' => $t , 'field' => $field ) )
		//$es->add_relation($rel_field_info['table'].'.'.$rel_field_info['field'].' IS NULL');
		
		if($form->get_value('report') == 'rel')
		{
			return $es->run_one();
		}
		
		$es->limit_tables();
		$es->limit_fields();
		
		$ids_with_rel = array_keys($es->run_one());
		
		if($site_id)
			$es = new entity_selector($site_id);
		elseif(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			$es = new entity_selector(array_keys($this->get_sites()));
		else
			$es = new entity_selector();
		
		$es->add_type($type_id);
		
		if(!empty($ids_with_rel))
			$es->add_relation('`entity`.`id` NOT IN ("'.implode('","',$ids_with_rel).'")');
		
		return $es->run_one();
	}
	
	function get_report($form)
	{
		$ret = '';
		$data = $this->get_data($form);
		
		if(empty($data))
			return '<p>No matching entities found.</p>';
		
		$ret .= '<ul>';
		foreach($data as $id => $e)
		{
			$ret .= '<li>'.$e->get_display_name().' (ID: <a href="?entity_id_test='.urlencode($e->id()).'&cur_module=EntityInfo">'.$e->id().'</a>)</li>';
		}
		$ret .= '</ul>';
		return $ret;
	}
	
	function entities_to_options($entities)
	{
		$ret = array();
		foreach($entities as $id => $e)
			$ret[$id] = $e->get_value('name');
		return $ret;
	}
	
	function get_types()
	{
		static $types;
		if(!isset($types))
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$types = $es->run_one();
		}
		return $types;
	}
	
	function get_sites()
	{
		static $types;
		if(!isset($types))
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
				$es->add_left_relationship($this->admin_page->user_id, relationship_id_of('site_to_user'));
			$types = $es->run_one();
		}
		return $types;
	}
	
	function get_allowable_relationships()
	{
		return array_flip(reason_get_relationship_names());
	}
}
