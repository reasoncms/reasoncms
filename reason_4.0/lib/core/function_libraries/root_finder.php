<?php
/*
    Root finder:
    Takes a site id and returns the id of the root page. 
*/

include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );

function root_finder( $site_id )
{
    $es = new entity_selector( );
    $es->add_type( id_of( 'minisite_page') );
    $es->add_relation( 'entity.state = "Live"' );
    $es->add_right_relationship( $site_id, relationship_finder( 'site', 'minisite_page', 'owns' ) );
    $results = $es->run_one();
    foreach( $results as $page )
    {
        $page_id = $page->get_value( 'id' );
        if( is_site_root( $page_id ) )
            return $page_id;
    }
}
/*
    Takes a page_id and determines if it is the root of a site
    04/07/04 probably need to add a check to make sure that the page is live.
*/
function is_site_root( $page_id )
{
    $query = 'SELECT * FROM relationship WHERE entity_a="' . $page_id . '" AND type="' . relationship_id_of( 'minisite_page_parent' ) . '"';
    $results = db_query( $query );
    while( $row = mysql_fetch_array( $results ) )
    {   
        if( $row['entity_b'] == $page_id )
            return true;
    }
    return false;
}

?>
