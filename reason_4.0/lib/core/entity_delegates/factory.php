<?php
/**
 * Factory functions for entity delegates
 * @todo Add ability to dynamically/programmatically add delegates to type at runtime, while still having good performance
 */
function get_entity_delegates($entity, $type_id = NULL)
{
	if(empty($type_id))
		$type_id = $entity->get_value('type');
	
	static $types_to_classes = array();
	
	$delegates = array();
	
	if(!isset($types_to_classes[$type_id]))
	{
		$types_to_classes[$type_id] = array();
		
		$map = get_entity_delegate_map();
		
		// @todo actually implement a way to define different delegate sets for different types
		
		if(!empty($map[$type_id]))
		{
			$path = $map[$type_id];;
			reason_include_once($path);
			if(!empty($GLOBALS['entity_delegates'][$path]))
			{
				$class = $GLOBALS['entity_delegates'][$path];
				if(class_exists($class))
					$types_to_classes[$type_id][$path] = $class;
				else
					trigger_error('Unable to use entity delegate at '.$path.'; class '.$class.' does not exist');
			}
			else
			{
				trigger_error('Unable to use entity delegate; not registered in $GLOBALS["entity_delegates"]['.$path.']');
			}
		}
	}
	
	foreach($types_to_classes[$type_id] as $path=>$class)
		$delegates[$path] = new $class($entity);
	
	return $delegates;
}

function get_entity_delegate_map()
{
	static $map;
	if(!isset($map))
	{
		$map = array(
			id_of('image') => 'entity_delegates/image.php',
			id_of('site') => 'entity_delegates/site.php',
			id_of('publication_type') => 'entity_delegates/publication.php',
		);
	}
	return $map;
}