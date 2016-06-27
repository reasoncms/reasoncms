<?php
/**
 * Abstract base entity delegate
 *
 * @package reason
 * @subpackage entity_delegates
 */
/**
 * Abstract base entity delegate
 *
 * All entity delegates should inherit from this class
 */
abstract class EntityDelegate
{
	/**
	 * The entity that the delegate is linked with
	 * @var object entity
	 */
    protected $entity;

    /**
     * Constructor
     *
     * @param mixed $entity Entity, integer, or unique_name
     */
    public function __construct($entity)
    {
    	if(empty($entity))
    	{
    		trigger_error('EntityDelegate objects must be instantiated with their entities');
    	}
    	if(!is_object($entity))
    	{
    		if(is_numeric($entity))
    		{
    			$id = (integer) $entity;
    			if($id)
    				$entity = new entity($id);
    			else
    				trigger_error('Unable to construct EntityDelegate -- not given a valid ID/Entity/Unique Name', HIGH);
    		}
    		elseif(is_string($entity))
    		{
    			if(reason_unique_name_exists($entity))
    				$entity = new entity(id_of($entity));
    			else
    				trigger_error('Unable to construct EntityDelegate -- not given a valid ID/Entity/Unique Name', HIGH);
    		}
    	}
        $this->entity = $entity;
    }
}