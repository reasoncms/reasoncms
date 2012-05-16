<?php
/**
 * @package reason
 * @subpackage ssh
 */
 
/**
 * Luther Streaming Server Media Management Object
 * 
 * @author Steve Smith
 * @date 05.15.2012
 */
 
include_once('reason_header.php');
// reason_require_once('ssh/ssh.php');
reason_include_once('ssh/streaming_server.php');

class luther_streaming_server extends streaming_server{

    var $ssh ; 
    var $netid = ""; 
    var $entity = ""; 
    var $source_media_path ; 
    var $last_error = ""; 
	var $local_file = ""; 


// determine whether the path should be writable
function _check_for_allowed_path($remote_path=""){
    
    if($remote_path == ""){ return 0 ; }
 
    // must not contain /..$ or /../
    if(preg_match('/\.\.$/' , $remote_path)){ return 0 ; }
    if(preg_match('/\.\.\//' , $remote_path)){ return 0 ; }

    // must be within certain directories
    $acceptable_directories = array( "/home/smitst01/" );

    $match = 0 ; 
    foreach($acceptable_directories as $dir){
        if(substr_count($remote_path , $dir ) > 0){ $match = 1; }
        } 
 
    if($match == 0){
        $this->last_error = "ssh.php: check_for_allowed_path() -> write to \"$remote_path\" on remote host " . $this->ssh->host . " is forbidden." ; 
        error_log($this->last_error);
        }
 
    return $match ; 

    }
}
?>

