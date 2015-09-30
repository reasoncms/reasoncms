<?php
/**
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Include the reason header
 */
include_once( 'reason_header.php' );

/**
 * @param string $entity_a The unique name of the type on the "A" side of the relationship
 * @param string $entity_b The unique name of the type on the "B" side of the relationship
 * @return mixed false if not found or if multiple rels available; else space delimited string data (a_to_b the_rel_name or b_to_a the_rel_name)
 * @deprecated since Reason 4.2
 */
function find_relationship_name( $entity_a, $entity_b )
{
    $total_results = 0;
    $found = false;
    
    $a_b_relationship_name = $entity_a . '_to_' . $entity_b;
    $b_a_relationship_name = $entity_b . '_to_' . $entity_a;
    
    $query = 'SELECT id FROM allowable_relationship WHERE name="' . $a_b_relationship_name . '"';
    $a_b_results = db_query( $query );
    $num_results = mysql_num_rows( $a_b_results );
    if( $num_results > 1 )
    {
        return false;
    }
    elseif( $num_results == 1 )
    {
        return 'a_to_b ' . $a_b_relationship_name;
    }

    $query = 'SELECT id FROM allowable_relationship WHERE name="' . $b_a_relationship_name . '"';
    $b_a_results = db_query( $query );
    $num_results = mysql_num_rows( $b_a_results );
    if( $num_results > 1 )
    {
        return false;
    }
    elseif( $num_results == 1 )
    {
        return 'b_to_a ' . $b_a_relationship_name;
    }
    else
    {
        return false;
    }    
}

/**
 *  Relationship Finder
 *
 *  A function to find the id of a relationship given two entities' unique names. 
 *  relationship_finder will return false if zero, or multiple relationships are found. 
 *  
 *  For example: 
 *  
 *  echo relationship_finder( 'site', 'minisite_page', 'owns' ) . '<br />';
 *  
 *  gives you:
 *  78
 *
 * @param mixed $entity_a The unique name, id, or entity of the type on the "A" side of the relationship
 * @param mixed $entity_b The unique name, id, or entity of the type on the "B" side of the relationship
 * @param string $name the name of the relationship
 * @return mixed The ID of the allowable relationship or NULL if not found
 * @deprecated since Reason 4.2 just use relationship_id_of with the name
 */
function relationship_finder( $entity_a, $entity_b, $name = 'owns' )
{
	if(is_object($entity_a))
		$a_id = $entity_a->id();
	elseif(is_numeric($entity_a))
		$a_id = (integer) $entity_a;
	else
		$a_id = id_of($entity_a);
		
	if(is_object($entity_b))
		$b_id = $entity_b->id();
	elseif(is_numeric($entity_b))
		$b_id = (integer) $entity_b;
	else
		$b_id = id_of($entity_b);
		
	$name = (string) $name;
	
	// if the name string passed in is simply "owns" or "borrows" and relationship uses unique relationship names, update the name we look for and trigger an error
	if ( ($name == 'owns' || $name == 'borrows') && reason_relationship_names_are_unique())
	{
		$a = new entity($a_id);
		$b = new entity($b_id);
		$name = $a->get_value('unique_name') . '_' . $name . '_' . $b->get_value('unique_name'); // this assumes unique names not cool
		trigger_error('The function relationship_finder was called to discover an owns or borrows relationship. The strings "owns" and "borrows"
			           are no longer used as relationship names. Calling this method is no longer necessary to find the relationship_id of owns
			           or borrows relationships. Use get_owns_relationship_id or get_borrows_relationship_id instead.');
	}
	
    if( empty($a_id))
    {
        trigger_error( '$entity_a ('.$entity_a.') is not a valid unique name');
        return;
    }
    if( empty($b_id))
    {
        trigger_error( '$entity_b ('.$entity_b.') is not a valid unique name');
        return;
    }
    if(empty($name))
    {
        trigger_error( 'An entity name must be provided for relationship_finder to work');
        return;
    }
	$query = 'SELECT id FROM allowable_relationship WHERE ' . 
				'relationship_a="' . $a_id . '" ' .
				'AND relationship_b="' . $b_id . '" ' .
				'AND name="' . reason_sql_string_escape($name) . '"';
	$results = db_query( $query );
	$num = mysql_num_rows( $results );
	if( $num < 1 )
	{
		//Relationship finder returned zero results.
		return false;
	}
	elseif( $num > 1 )
	{
		 //Relationship finder returned too many results!
		 if(is_object($entity_a))
		 	$a_name = $entity_a->get_value('name');
		 else
		 	$a_name = $entity_a;
		 
		 if(is_object($entity_b))
		 	$b_name = $entity_b->get_value('name');
		 else
		 	$b_name = $entity_b;
		trigger_error('Multiple relationships exist for "'.$a_name.'" to "'.$b_name.'" under name "'.$name.'"; returning only first result.');
	}
	$results = mysql_fetch_array( $results );
	return (integer) $results['id'];
}
?>