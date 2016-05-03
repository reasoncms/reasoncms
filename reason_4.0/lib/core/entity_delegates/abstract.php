<?php
abstract class EntityDelegate
{    
    protected $entity;

    // Common method
    public function __construct($entity)
    {
    	if(empty($entity))
    	{
    		trigger_error('EntityDelegate objects must be instantiated with their entities');
    	}
    	//add error checking & handling for being passed an ID or unique name
        $this->entity = $entity;
    }
    
    public function get_url()
    {
    	
    }
}