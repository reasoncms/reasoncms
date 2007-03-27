<?php
/*
    Asset Import Class
    mryan
*/

include_once('reason_header.php');
require_once('XML/Unserializer.php');

//reason_include_once('function_libraries/xml_utils.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once( 'classes/url_manager.php' );
reason_include_once('content_managers/asset.php');
  
class asset_import_dc
{
	var $type_unique_name = 'asset';
	var $data = array();
	var $report;
	var $site_id;
	var $nodes_to_fields = array(
												'title'=>'name',
												'date'=>'datetime',
												'format'=>'mime_type',
												'identifier'=>'file_name',
												'subject'=>'keywords',
												'creator'=>'author',
												'description'=>'description',
												'rights'=>'rights',
											);
	var $nodes_to_functions = array( 'identifier'=>'handle_identifier' );
	var $multiple_values_not_ok = array( 'datetime','file_name','mime_type' );
	var $formats_to_extensions = array( 'application/pdf'=>'pdf' );
	var $testing_mode = false;
	var $curl_session;
	var $import_directory;
	var $asset_names = array();
	
   function set_file( $path )
   {
		$xml = file_get_contents($path);
		if(!empty($xml))
		{
			$unserializer = new XML_Unserializer();
			$unserializer->unserialize($xml);
			//echo implode("\n",$file_array);
			$this->data = $unserializer->getUnserializedData();
		}
   }
   function set_directory($import_dir)
   {
   		$this->import_directory = $import_dir;
   }
   function set_report( &$report )
   {
   	$this->report &= $report;
   }
   function set_site( $site_id )
   {
   	$this->site_id = $site_id;
   }
   function set_user( $user_id )
   {
   	$this->user_id = $user_id;
   }
   function enter_testing_mode()
   {
   		$this->testing_mode = true;
   }
   function run_import()
   {
		//$this->curl_session = curl_init();
		//curl_setopt ($this->curl_session, CURLOPT_RETURNTRANSFER, 1);
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of($this->type_unique_name));
		$assets = $es->run_one();
		foreach($assets as $asset)
		{
			$this->asset_names[] = $asset->get_value('file_name');
		}
		pray($this->asset_names);
		
   		prp($this->data);
		$root_node = current($this->data);
		$asset_data = array();
		if(is_numeric(key($root_node)))
		{
			foreach($root_node as $element)
			{
				//pray($element);
				$this->import($element);
			}
		}
		else
		{
			$this->import($root_node);
		}
		$um = new url_manager( $this->site_id, true);
		$um->update_rewrites();
		//curl_close ($this->curl_session);
   }
   function import($element)
   {
   		$prepped_data = $this->prep_for_import($element);
		//pray($element);
   		
		pray($prepped_data);
		if(!$this->testing_mode)
		{
			echo 'not in testing mode<br />'."\n";
			$type_id = id_of( $this->type_unique_name );
			$tables = get_entity_tables_by_type($type_id);
			$id = create_entity( $this->site_id, $type_id, $this->user_id, $prepped_data['name'], values_to_tables( $tables, $prepped_data )  );
			echo 'created id '.$id;
			if(file_exists($this->import_directory.$prepped_data['file_name']))
			{
				$success = copy($this->import_directory.$prepped_data['file_name'], ASSET_PATH.$id.'.'.$prepped_data['file_type']);
				if($success)
				{
					echo 'ok';
				}
				else
				{
					trigger_error('file at '.$this->import_directory.$prepped_data['file_name'].'couldn\'t be moved into reason asset directory');
				}
			}
		}
   }
   function determine_url($element)
   {
   		//'<a href="http://digitalcommons.carleton.edu/cgi/viewcontent.cgi?article=1003&context=lib_working" ><strong>Download the Document</strong></a>'
   		if(!empty($element['identifier']))
		{
			$url = $element['identifier'].'/';
			curl_setopt($this->curl_session, CURLOPT_URL,$url);
			$page = curl_exec ($this->curl_session);
			$chunk = strstr($page,'<a href="http://digitalcommons.carleton.edu/cgi/viewcontent.cgi?article=');
			$tail = strstr($chunk,'" ><strong>Download the Document</strong></a>');
			$url = str_replace(array($tail,'<a href="'),'',$chunk);
			if(!empty($url))
				return $url;
   		}
   		return false;
   }
   function prep_for_import($element)
   {
		//pray($element);
		$values = array();
   		foreach($element as $node_name => $node_value)
		{
		//pray($node);
			if(!empty($this->nodes_to_fields[$node_name]))
			{
				$field = $this->nodes_to_fields[$node_name];
				if(!empty($this->nodes_to_functions[$node_name]))
				{
					$func = $this->nodes_to_functions[$node_name];
					$this->$func($element, $values);
				}
				else
				{
					$values[$field] = $node_value;
				}
			}
		}
		$values['new'] = 0;
		if(file_exists($this->import_directory.$values['file_name']))
		{
			$values['file_size'] = filesize($this->import_directory.$values['file_name']);
		}
		return $this->flatten_values($values);
   }
   function flatten_values( $values )
   {
   		$flat_values = array();
		
		foreach($values as $field=>$vals)
		{
			if(is_array($vals))
			{
				if(in_array($field,$this->multiple_values_not_ok))
				{
					reset($vals);
					$flat_values[$field] = current($vals);
				}
				else
				{
					$flat_values[$field] = implode(', ',$vals);
				}
			}
			else
			{
				$flat_values[$field] = $vals;
			}
		}
		return $flat_values;
   }
   function handle_identifier( $element, &$values)
   {
   		if(!empty($element['identifier']))
		{
			$filename = str_replace(array('http://digitalcommons.carleton.edu/','/'),array('','_'),$element['identifier']);
			echo $filename;
			if(!empty($element['format']))
			{
				if(!empty($this->formats_to_extensions[$element['format']]))
				{
					$filename .= '.'.$this->formats_to_extensions[$element['format']];
					$values['file_type'] = $this->formats_to_extensions[$element['format']];
				}
			}
			//echo $filename;
			
			if(in_array($filename, $this->asset_names))
			{
				echo $filename;
				$filename = AssetManager::get_unique_filename($filename, $this->asset_names);
			}
			$filename = AssetManager::get_safer_filename($filename);
			$values[$this->nodes_to_fields['identifier']] = $filename;
		}
   }
}
?>    