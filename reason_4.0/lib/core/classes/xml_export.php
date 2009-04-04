<?php
/**
 * XML export
 * @package reason
 * @subpackage classes
 */
 
/**
 * Class for handling standard Reason exports
 *
 * Takes an array of entities and generates an xml description of the entities
 *
 * THIS IS STILL EXPERIMENTAL CODE. Don't expect the XML from this module to be in exactly
 * this form for now.
 *
 * @author Matt Ryan
 *
 * Sample code:
 * $es = new entity_selector();
 * // ... some rules go in here ...
 * $entities = $es->run_one();
 * $export = new reason_xml_export();
 * $xml = $export->get_xml($entities);
 */
 
class reason_xml_export
{
	/** array of versions supported by the class
	 * @var array keys=version name, values=class method to run for this version
	 */
	var $versions = array('0.1'=>'get_xml_version_point_one');
	
	/** the default version used if no version is provided to this class
	 * This should be one of the keys in the $versions class variable
	 * @var string
	 */
	var $default_version = '0.1';
	
	/** Method for finding out which versions are supported by the class
	 * @return array
	 */
	function versions_supported()
	{
		return array_keys($this->versions);
	}
	
	/** Method for finding out which version is the current default
	 * @return string
	 */
	function get_default_version()
	{
		return $this->default_version;
	}
	
	/** Determine is a particular version is supported by this class
	 * @return bool
	 */
	function version_is_supported($version)
	{
		if(empty($this->versions[$version]))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/** Determine what function should be used for a particular version
	 * @return string (NULL returned if none available)
	 */
	function function_name_for_version($version)
	{
		if(!empty($this->versions[$version]))
		{
			return $this->versions[$version];
		}
		else
		{
			return NULL;
		}
	}
	
	/** The main public function for this class
	 * Mostly other classes should just use this function
	 * It is up to the controlling class to serve contents or to store in file
	 * It is also up to the controlling class to determine exactly which entities are to be included
	 * @return string contents of XML file
	 */
	function get_xml($entities, $version = '')
	{
		if(empty($version))
		{
			$version = $this->get_default_version();
		}
		if($this->version_is_supported($version))
		{
			$function = $this->function_name_for_version($version);
			if(method_exists($this, $function))
			{
				return $this->$function($entities);
			}
			else
			{
				trigger_error('XML version '.$version.' wants to run a method called "'.$function.'" on the xml export class, but no method with that name is available.');
			}
		}
		else
		{
			trigger_error('Unsupported xml version requested: '.$version);
		}
	}
	
	/** Generates reason data version 0.1
	 * This is a very rough implementation of the Reason XML exporting scheme
	 * It's probably best to use it only if there is nothing better available
	 * @return string contents of XML file
	 */
	function get_xml_version_point_one($entities)
	{
		$lines[] = '<'.'?'.'xml version="1.0" encoding="utf-8"'.'?'.'>';
		$lines[] = '<reason_data version="0.1" from="http://'.REASON_WEB_ADMIN_PATH.'">';
		foreach($entities as $e)
		{
			$type = new entity($e->get_value('type'));
			$site = $e->get_owner();
			$line = "\t".'<entity id="'.$e->id().'"';
			$line .= ' type="'.$type->get_value('unique_name').'" ';
			$line .= ' site="'.$site->get_value('unique_name').'"';
			if($e->get_value('unique_name'))
			{
				$line .= ' unique_name="'.$e->get_value('unique_name').'"';
			}
			$line .= '>';
			$lines[] = $line;
			foreach($e->get_values() as $k=>$v)
			{
				$lines[] = "\t\t".'<value name="'.$k.'">'.$v.'</value>';
			}
			$lines[] = "\t\t".'<relationships>';
			foreach($e->get_left_relationships() as $name=>$rels)
			{
				if(!is_numeric($name) && !empty($rels))
				{
					$lines[] = "\t\t\t".'<alrel alrel_name="'.$name.'" dir="left">';
					foreach($rels as $rel)
					{
						$lines[] = "\t\t\t\t".'<rel to_entity_id="'.$rel->id().'" site_id="" />';
					}
					$lines[] = "\t\t\t".'</alrel>';
				}
			}
			
			foreach($e->get_right_relationships() as $name=>$rels)
			{
				if(!is_numeric($name) && !empty($rels))
				{
					$lines[] = "\t\t\t".'<alrel alrel_name="'.$name.'" dir="right">';
					foreach($rels as $rel)
					{
						$lines[] = "\t\t\t\t".'<rel to_entity_id="'.$rel->id().'" />';
					}
					$lines[] = "\t\t\t".'</alrel>';
				}
			}
			$lines[] = "\t\t".'</relationships>';
			$lines[] = "\t".'</entity>';
		}
		$lines[] = "</reason_data>";
		return implode("\n",$lines);
	}
}
?>