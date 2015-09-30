<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/xml_export.php');
	
	/**
	 * Export Reason data
	 */
	class ReasonExportModule extends DefaultModule// {{{
	{
		function EntityInfoModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Export';
		} // }}}
		function run() // {{{
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			{
				echo '<p>Sorry; use of this module is restricted.</p>'."\n";
				return;
			}
			if(!empty($this->admin_page->request['export_site_id']))
			{
				$site_id = $this->admin_page->request['export_site_id'];
				settype($site_id, 'integer');
			}
			if(empty($site_id))
			{
				$site_id = '';
			}
			if(!empty($this->admin_page->request['export_type_id']))
			{
				$type_id = $this->admin_page->request['export_type_id'];
				settype($type_id, 'integer');
			}
			if(empty($type_id))
			{
				$type_id = '';
			}
			$es = new entity_selector();
			$es->set_order('entity.name ASC');
			$sites = $es->run_one(id_of('site'));
			$types = $es->run_one(id_of('type'));
			echo '<form method="get" action="?">';
			echo '<label for="export_site_id">Site:</label>';
			echo '<select name="export_site_id" id="export_site_id">';
			foreach($sites as $site)
			{
				echo '<option value="'.$site->id().'"';
				if($site->id() == $site_id) echo ' selected="selected"';
				echo '>'.$site->get_value('name').'</option>'."\n";
			}
			echo '</select><br />'."\n";
			
			echo '<label for="export_type_id">Type:</label>';
			echo '<select name="export_type_id" id="export_type_id">';
			echo '<option value="">All</option>'."\n";
			foreach($types as $type)
			{
				echo '<option value="'.$type->id().'"';
				if($type->id() == $type_id) echo ' selected="selected"';
				echo '>'.$type->get_value('name').'</option>'."\n";
			}
			echo '</select><br />'."\n";
			
			echo '<input type="submit" value="submit" /><input type="hidden" name="cur_module" value="Export" />';
			echo '</form>'."\n";
			if(!empty($site_id))
			{
				if(!empty($type_id) && isset($types[$type_id]))
				{
					$query_types= array($type_id => $types[$type_id]);
				}
				else
				{
					$query_types= $types;
				}
				
				$es = new entity_selector($site_id);
				$entities = array();
				foreach($query_types as $type)
				{
					$entities = array_merge($entities, $es->run_one( $type->id() ) );
				}
				
				$export = new reason_xml_export();
				
				echo '<textarea rows="40">'.htmlspecialchars($export->get_xml($entities), ENT_QUOTES).'</textarea>'."\n";
			}
		} // }}}
	} // }}}
?>