<?php
function get_entity_delegates($entity, $type_id = NULL)
{
	if(empty($type_id))
		$type_id = $entity->get_value('type'));
	
	$delegates = array();
	
	// use a config or some other way to figure out which delegates to use
	include_once('entity_delegates/image.php');
	$delegates['entity_delegates/image.php'] = new imageEntityDelegate($entity);
	
	return $delegates;
}