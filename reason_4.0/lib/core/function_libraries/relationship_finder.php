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
 * @param string $entity_a The unique name of the type on the "A" side of the relationship
 * @param string $entity_b The unique name of the type on the "B" side of the relationship
 * @return mixed The ID of the allowable relationship or NULL if not found
 */
function relationship_finder( $entity_a, $entity_b, $name = 'owns' )
{
	$entity_a = (string) $entity_a;
	$entity_b = (string) $entity_b;
	$name = (string) $name;
	
    if( empty( $entity_a ) || empty( $entity_b ) || empty($name) )
    {
        trigger_error( '$entity_a, $entity_b, and $name are all required for relationship_finder() to work');
        return;
    }
    $a_id = id_of( $entity_a );
    $b_id = id_of( $entity_b );
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
	$query = 'SELECT id FROM allowable_relationship WHERE ' . 
				'relationship_a="' . $a_id . '" ' .
				'AND relationship_b="' . $b_id . '" ' .
				'AND name="' . addslashes($name) . '"';
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
		trigger_error('Multiple relationships exist for "'.$entity_a.'" to "'.$entity_b.'" under name "'.$name.'"; returning only first result.');
	}
	$results = mysql_fetch_array( $results );
	return (integer) $results['id'];
}
        

?>
