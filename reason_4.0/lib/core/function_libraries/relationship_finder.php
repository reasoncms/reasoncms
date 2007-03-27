<?php
/*
    Relationship Finder:
    a function to find the id of a relationship given two entities' unique names. 
    relationship_finder will return false if zero, or multiple relationships are found. 
    
    for example: 
    
    echo relationship_finder( 'site', 'minisite_page', 'owns' ) . '<br />';
    
    gives you:
    78
*/
include_once( 'reason_header.php' );

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


function relationship_finder( $entity_a, $entity_b, $name = 'owns' )
{
    if( !isset( $entity_a ) && !isset( $entity_b ) )
    {
        echo 'How am I supposed to find a relationship if you don\'t give me anything to work with?';
    }
    else
    {
    
        $query = 'SELECT id FROM allowable_relationship WHERE ' . 
                    'relationship_a="' . id_of( $entity_a ) . '" ' .
                    'AND relationship_b="' . id_of( $entity_b ) . '" ' .
                    'AND name="' . $name . '"';
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
			trigger_error('Multiple relationships exist for "'.$entity_a.'" to "'.$entity_b.'" under name "'.$name.'"');
		}
		$results = mysql_fetch_array( $results );
		return $results['id'];
    }
}
        

?>
