<?php
/**
 * Functions for building regular expressions 
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Builds a regular expressions designed to be used to extract requests for a particular Reason minisite from the entire server's access log
 *
 *  Gets an array of directories and an array of actions (e.g. GET, POST) 
 *  Returns a regular expression of the form:
 *   /\"(ACTION1|ACTION2|...|ACTIONn) ((\/DIRECTORY1\/|\/DIRECTORY2\/|...|\/DIRECTORYn\/).[^\"]+)/
 *  
 * which matches a resource request in an apache log file
 *
 * @param array $directories
 * @param array $actions
 *
 * @todo rework to remove out-of-date Carleton-specific code specific to htdig
 */

function build_access_log_regex( $directories = array( '' ), $actions = array( 'GET', 'POST', 'PUT', 'DELETE' ) )
{
    $regex = '';
    $acts = '';
    $dirs = '';

    //Sets up OR cases appropriately
    $max = count( $actions );
    if( $max > 1 )
    {           
        $acts = '(';
        $x = 0;
        foreach( $actions as $action )
        {
            $acts .= $action;
            $x++;
            if( $x == $max )
                $acts .= ')';
            else
                $acts .= '|';
        }
    }
    else
    {
        $acts = $actions[0];
    }
    
    //escapes slashes in directory names for use in the regex
    for( $x = 0; $x < count( $directories ); $x++ )
    {
        //ignore blank directories
        if( $directories[$x] != '' )
        {
            if( $directories[$x][0] != '/' )
            {
                $directories[$x] = '/' . $directories[$x];
            }
            
            if( $directories[$x][ strlen( $directories[$x] ) - 1 ]  != '/' )
            {
                $directories[$x] = $directories[$x] . '/';
            }
            
            $directories[$x] = str_replace( '/', '\/', $directories[$x] );
        }
    }
    
    //Again, sets up OR cases
    $max = count( $directories );
    if( $max > 1 )
    {           
        $dirs = '(';
        
        $x = 0;
        foreach( $directories as $directory )
        {
            $dirs .= $directory;
            $x++;
            if( $x == $max )
                $dirs .= ')';
            else
                $dirs .= '|';
        }
    }
    else
    {
        $dirs = $directories[0];
    }
    
    $filters = array();
    array_push( $filters, agent_filter( 'htdig', '3.1.6', '(mheiman@carleton.edu)', false) );
    $filter_string = '';
    
    foreach( $filters as $filter )
    {
        $filter_string = $filter . ' ';
    }
    $filter_string = trim( $filter_string );
    //the actual regex
    $regex = "/\"" . $acts . " (" . $dirs . ".[^\"]+).*\" " . $filter_string . "/";

    return "$regex";
}
/*
Adds a regex to filter for/out the agent string
"htdig/3.1.6 (mheiman@carleton.edu)"
*/
function agent_filter( $agent, $version, $misc, $exists = false )
{
    $version = str_replace( '.', '\.', $version );
    if( $exists )
        $filter = '('. $agent . '\\/' . $version . ')';
    else
        $filter = "(?!". $agent .  ").*"; //'\\/' . $version .
    return "$filter";
}

?>
