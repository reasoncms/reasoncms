<?php
/**
 * XML export
 * @package reason
 * @subpackage classes
 */
 
 include_once('reason_header.php');
 reason_include_once('function_libraries/asset_functions.php');
 reason_include_once('function_libraries/images.php');
 
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
		$gen = new reason_xml_export_generator_version_point_one();
		return $gen->get_xml($entities);
	}
	
}

/**
 * An abstract class that defines the API of an XML export generator
 */
class reason_xml_export_generator
{
	/**
	 * Get an XML representation for a set of Reason entities
	 * @param array $entities
	 * @return string (XML)
	 */
	function get_xml($entities)
	{
		trigger_error('This method must be overloaded');
		return '';
	}
}

/**
 * A class that generates the 0.1 version of the Reason XML export data format
 */
class reason_xml_export_generator_version_point_one extends reason_xml_export_generator
{
	/**
	 * Get an XML representation for a set of Reason entities
	 * @access public
	 * @param array $entities
	 * @return string (XML)
	 */
	function get_xml($entities)
	{
		$lines[] = '<'.'?'.'xml version="1.0" encoding="utf-8"'.'?'.'>';
		$lines[] = '<reason_data version="0.1" from="http://'.REASON_HOST.'/">';
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
				$lines[] = "\t\t".'<value name="'.$k.'">'.htmlspecialchars($v).'</value>';
			}
			$method = '_get_custom_values_for_'.$type->get_value('unique_name');
			if(method_exists($this, $method) )
			{
				$lines = array_merge($lines, $this->$method($e, "\t\t") );
			}
			$lines[] = "\t\t".'<relationships>';
			$left_rel_info = $e->get_left_relationships_info();
			foreach($e->get_left_relationships() as $alrel_id=>$rels)
			{
				if(is_numeric($alrel_id) && !empty($rels))
				{
					$lines = array_merge($lines, $this->_get_rel_xml_lines($rels, $left_rel_info[$alrel_id], $alrel_id, 'left', "\t\t\t") );
				}
			}
			$right_rel_info = $e->get_right_relationships_info();
			foreach($e->get_right_relationships() as $alrel_id=>$rels)
			{
				if(is_numeric($alrel_id) && !empty($rels))
				{
					
					$lines = array_merge($lines, $this->_get_rel_xml_lines($rels, $right_rel_info[$alrel_id], $alrel_id, 'right', "\t\t\t") );
				}
			}
			$lines[] = "\t\t".'</relationships>';
			$lines[] = "\t".'</entity>';
		}
		$lines[] = "</reason_data>";
		return implode("\n",$lines);
	}
	
	/**
	 * @access private
	 * @param array $rels
	 * @param array $rels_info
	 * @param integer $alrel_id
	 * @param string $dir
	 * @param string $indent
	 * @return array lines
	 */
	function _get_rel_xml_lines($rels, $rels_info, $alrel_id, $dir, $indent)
	{
		$lines = array();
		$lines[] = $indent.'<alrel name="'.relationship_name_of($alrel_id).'" id="'.$alrel_id.'" dir="'.$dir.'">';
		foreach($rels as $position=>$rel)
		{
			$uname = $rel->get_value('unique_name') ? ' to_uname="'.htmlspecialchars($rel->get_value('unique_name')).'"' : '';
			$lines[] = $indent."\t".'<rel to_entity_id="'.$rel->id().'" '.$uname.'>';
			if(isset($rels_info[$position]))
			{
				foreach($rels_info[$position] as $key=>$val)
				{
					if($key != 'type' && $key != 'entity_a' && $key != 'entity_b')
						$lines[] = $indent."\t\t".'<attr name="'.$key.'">'.htmlspecialchars($val).'</attr>';
				}
			}
			$lines[] = $indent."\t".'</rel>';
		}
		$lines[] = $indent.'</alrel>';
		return $lines;
	}
	
	/**
	 * Get custom computed values for an asset
	 * @access private
	 * @param object (entity) $e
	 * @param string $indent
	 * @return array lines
	 */
	function _get_custom_values_for_asset($e,$indent)
	{
		$lines = array();
		$lines[] = $indent.'<value name="url" type="computed">'.htmlspecialchars(reason_get_asset_url($e)).'</value>';
		$lines[] = $indent.'<value name="filesystem_location" type="computed">'.htmlspecialchars(reason_get_asset_filesystem_location($e)).'</value>';
		return $lines;
	}
	
	/**
	 * Get custom computed values for an image
	 * @access private
	 * @param object (entity) $e
	 * @param string $indent
	 * @return array lines
	 */
	function _get_custom_values_for_image($e,$indent)
	{
		$lines = array();
		$lines[] = $indent.'<value name="url" type="computed">'.htmlspecialchars(reason_get_image_url($e)).'</value>';
		$lines[] = $indent.'<value name="thumb_url" type="computed">'.htmlspecialchars(reason_get_image_url($e,'thumbnail')).'</value>';
		return $lines;
	}
}
?>