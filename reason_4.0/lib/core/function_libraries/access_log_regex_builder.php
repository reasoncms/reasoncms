<?
/**
 * @package reason
 */

/**
 * Include dependencies
 */

include_once( 'reason_header.php' );
include_once( REASON_INC . 'function_libraries/regex_builder.php' );

/**
 * access log regex builder -- creates regular expressions for parsing the apache logs to grab minisite stats
 
Example line from apache log
bstanton34490.publications.carleton.edu - - [15/Mar/2004:09:36:16 -0600] "GET /test/joswald/ HTTP/1.1" 200 7519 "-" "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.6) Gecko/20040113"
(.*\..*\..*\..*) (.*) (.*) [.*] "ACTION DIR METHOD" (3-DIGIT RESPONSE) (BYTES) (\".*\") (\"AGENT\/VERSION (.*)\")
host usr1 usr2 time request response length something agent

 */

class access_log_regex_builder extends regex_builder
{
    var $host = '.*\..*\..*\..*';
    var $usr1 = '.*';
    var $usr2 = '.*';
    var $timestamp = '\[.*\]';
    var $request_array = array( 'actions' => '.*', 'directories' => '.*', 'method' => '.*' );
    var $request_string;
    var $response = '\d\d\d';
    var $size = '\d*';
    var $unknown = '.*';
    var $agent;
    
    function build_request_string()
    {
        $this->request_string = '\"' . $this->request_array['actions'] . ' ' . $this->request_array['directories'] . ' ' . $this->request_array['method'] . '\"';
    }
    
    function run()
    {
        $this->open_regex();
        $this->add_pattern( $this->host, false );
        $this->add_pattern( $this->usr1 );
        $this->add_pattern( $this->usr2 );
        $this->add_pattern( $this->timestamp );
        $this->build_request_string();
        $this->add_pattern( $this->request_string );
        $this->add_pattern( $this->response );
        $this->add_pattern( $this->size );
        $this->add_pattern( $this->unknown );
        $this->add_pattern( $this->agent );
        $this->close_regex();
    }
    
    function action_filter( $actions )
    {
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
        elseif( $max == 1 )
        {
            $acts = $actions[0];
        }
        else
        {
            echo 'No actions supplied!<br />';
        }
        
        $this->request_array['actions'] = $acts;
    }
    
    function directory_filter( $directories )
    {
            
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
                $dirs .= $directory . '.*';
                $x++;
                if( $x == $max )
                    $dirs .= ')';
                else
                    $dirs .= '|';
            }
        }
        elseif( $max == 1)
        {
            $dirs = $directories[0] . '.*';
        }
        else
        {
            echo 'No directories supplied!<br />';
        }
        
        $this->request_array['directories'] = $dirs;
    }
    
    function agent_filter( $agents, $exists = false )
    {
        //Sets up OR cases appropriately
        $max = count( $agents );
        
        if( $max > 0 )
        {           
/*            if( $max == 1 )
                $str = '\"';
            else*/
                $str = '\"(';

            if( !$exists )
            {
                $str = $str . '?!';
            }                

            $x = 0;
            foreach( $agents as $agent ) //=> $exists )
            {
//                $str .= '(';    
                


                $agent = str_replace( '/', '\/', $agent );
                $agent = str_replace( '.', '\.', $agent );
                
                $str .= $agent;
                $x++;
                if( $x < $max )
//                    $str .= ')';
//                else
                    $str .= '|';
            }
//            if( $max != 1 )
                $str .= ')';
            
            $str .= '.*\"';
        }
        else
        {
            echo 'No agents supplied!<br />';
            $str = '.*';
        }
        
        $this->agent = $str;
    }


}