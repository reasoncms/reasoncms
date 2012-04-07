<?php
/**
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'function_libraries/util.php' );

/**
 * Root finder:
 * Takes a site id and returns the id of the root page. 
 * @param integer $site_id
 * @return mixed page id integer if found; NULL if not found
 */
function root_finder( $site_id )
{
    $es = new entity_selector( );
    $es->add_type( id_of( 'minisite_page') );
    $es->add_relation( 'entity.state = "Live"' );
    $es->add_right_relationship( $site_id, get_owns_relationship_id(id_of('minisite_page')) );
    $results = $es->run_one();
    foreach( $results as $page )
    {
        $page_id = $page->get_value( 'id' );
        if( is_site_root( $page_id ) )
            return $page_id;
    }
}
/*
 * Takes a page_id and determines if it is the root of a site
 * @todo Add a check to make sure that the page is live.
 * @todo Use standard Reason entity selector API
 * @param integer $page_id
 * @return boolean
 */
function is_site_root( $page_id )
{
    $query = 'SELECT * FROM relationship WHERE entity_a="' . addslashes($page_id) . '" AND type="' . relationship_id_of( 'minisite_page_parent' ) . '"';
    $results = db_query( $query );
    while( $row = mysql_fetch_array( $results ) )
    {   
        if( $row['entity_b'] == $page_id )
            return true;
    }
    return false;
}

?>
