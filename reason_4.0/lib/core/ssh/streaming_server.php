<?php
/**
 * @package reason
 * @subpackage ssh
 */
 
/**
 * Streaming Server Media Management Object
 * 
 * @author Matthew Bockol
 * @version .002
 * @date 01.31.2006
 *
 * Manages the transfer of media files from a user's 'reason_import_only' folder on HOME/webpub to a streamed folder on REASON_BASE_STREAM_URL via ssh.
 *
 * NOTE: this class requires a passwordless private key be in the userid's .ssh directory. Otherwise these functions will fail.
 *
 * Usage: 
 *
 * Initialize the object with the user's netid, host and user (for the remote ssh connection) are optional.
 * $stream = new streaming_server( $netid , [ local_media_file , streaming_host , streaming_user ]) ; 
 * 
 * The array contains all the available files in the users streaming_media directory
 * $file_array = $stream->view_available_files();
 *
 * Get the MD5SUM of the source file
 * $md5sum = $stream->get_source_md5sum($media_file); 
 *
 * Transfer the media into the heirarchy under the Content/reason directory on the streaming server
 * $stream->place_media($entity_id , $media_file); 
 * 
 * Transfer a locally uploaded media file into the heirarchy under the Content/reason directory on the streaming server
 * $stream->place_local_media($entity_id , $full_path_to_media_file);
 *
 * Get the url (sans protocol) of the entities media file
 * $streamable_url = "rtsp://" . $stream->get_media_url($entity_id); 
 *
 * Get entity's meta info
 * $string = get_meta_info($entity_id)
 *
 * Set entity's meta info
 * set_meta_info($entity_id, $meta_info) // where meta_info is a string
 *      
 * Get the media file size in bytes
 * $string = get_media_size_bytes($entity_id) // returns the value in bytes
 * 
 * Get the media length ( only works with quicktime files, returns null if it can't get the duration ) 
 * $string = get_media_duration($entity_id) // returns the value in seconds (145.02s = 145 seconds, 02 1/100ths of a second)
 *
 * Returns the full remote path to an entity's media
 * $string = media_file_path($entity_id)
 *
 * Returns the remote host storing the media file
 * $string = media_host()
 * 
 * returns the path to the user's source file (their $HOME/streaming_server/ folder) 
 * $string = get_source_path()
 * 
 * @todo this is really Carleton specific and should be generalized or moved to local 
 */
 
include_once('reason_header.php');
reason_require_once('ssh/ssh.php');

class streaming_server{

    var $ssh ; 
    var $netid = ""; 
    var $entity = ""; 
    var $source_media_path ; 
    var $last_error = ""; 
		var $local_file = ""; 

// initialize streaming_server object
function streaming_server($source_netid="" , $local_file="" , $streaming_server=REASON_STREAMING_HOST , $streaming_user=REASON_STREAMING_USER ){

    if($source_netid == ""){ 
	$this->last_error = "streaming_server.php: streaming_server() netid must be specified.";
        error_log($this->last_error);
        return 0 ; 
        }

    // start with our connection to the remote server
    $this->ssh = new ssh($streaming_server , $streaming_user , "/tmp/" ); 

    // define the netid within the streaming_server object for later use
    $this->netid = $source_netid ; 

    // define the path to the user's streaming media folder for later use
    $this->source_media_path = REASON_REMOTE_HOME_PATH . $this->netid . "/WebPub/reason_import_only/" ;

		// a "local file" is one which has been uploaded to the apps server rather than existing in the netware mount on the streaming server.
		// it will need to be moved to the streaming server via scp and the source_media_path changed to see it
		// we return here to skip the remote directory check below.  It doesn't matter if the user has a streaming_media directory if they're
		// using a file uploaded to the apps server.
		// the $this->local_file value is used in the place_media function
		if(strcmp($local_file,"") != 0){ $this->local_file = $local_file ; return ; }

    // if the user lacks a streaming_media folder, return an error
		if(!$this->ssh->remote_directory_exists($this->source_media_path)){
			$this->last_error = "streaming_server.php: streaming_server() netid \"" . $this->netid . "\" does not have a streaming media directory on " . $this->ssh->host ; 
			error_log($this->last_error);
			$this->ssh = null ; 
			return 0 ; 
			}
 
    }

// return an array of available media files in the user's directory
function view_available_files(){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)

    return $this->ssh->directory_listing($this->source_media_path); 

    }


function get_source_md5sum($media_file=""){

	// if operating on a local file, return hash of local file
	if(!empty($this->local_file) )
	{
		return md5_file($this->local_file);
	}
	
	if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
	
    if($media_file == ""){ return 0 ; }
    
    // path to the original file
    $source_path = $this->source_media_path . $media_file ; 
	
    // return the md5sum of the file as it exists in the user's home folder
    return $this->ssh->remote_md5sum($source_path); 

    }


// copies the selected media file into the Content/reason heirarchy on the remote server
function place_media($entity_id="",$media_file=""){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }
    if($media_file == ""){ return 0 ; }
    
    $entity_prefix = $this->_entity_prefix($entity_id);

    $this->_create_entity_directory($entity_id); 
    $this->_move_old_media($entity_id); 


    $source_path = $this->source_media_path . $media_file ; 
    $destination_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id . "/" . sanitize_filename_for_web_hosting($media_file) ; 

    if(!$this->_check_for_allowed_path($source_path)){
        $this->last_error = "streaming_server.php: place_media() source path \"$source_path\" forbidden."; 
        error_log($this->last_error);
        return 0 ; 
        }

    if(!$this->_check_for_allowed_path($destination_path)){
        $this->last_error = "streaming_server.php: place_media() destination path \"$destination_path\" forbidden."; 
        error_log($this->last_error);
        return 0 ; 
        }

    if(!$this->ssh->remote_file_copy($source_path , $destination_path)){ return 0 ; }
    
    return 1 ;     
 
    }

function place_local_media($entity_id="" , $new_local_file=""){
    
		if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }
		if(strcmp($new_local_file, "") != 0){ $this->local_file = $new_local_file ; } // if passed a new file, use it instead
    if($this->local_file == ""){ return 0 ; }
    
    $entity_prefix = $this->_entity_prefix($entity_id);

    $this->_create_entity_directory($entity_id); 
    $this->_move_old_media($entity_id); 
		

		// determine file path details
		$local_path_parts = pathinfo($this->local_file);	// chop up the local path into an array
		$local_path = $local_path_parts['dirname'];			// full path sans filename
		$local_filename = $local_path_parts['basename']; // filename only
		$remote_filename = sanitize_filename_for_web_hosting($local_filename); // filename only

    $destination_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id . "/" . $remote_filename ; 

    if(!$this->_check_for_allowed_path($destination_path)){
        $this->last_error = "streaming_server.php: place_media() destination path \"$destination_path\" forbidden."; 
        error_log($this->last_error);
        return 0 ; 
        }

		// copy the file to it's entity location
		$remote_path_parts = pathinfo($destination_path);	// chop up the remote path into an array
		$this->ssh->local_path = $local_path_parts['dirname'];
		$this->ssh->remote_path = $remote_path_parts['dirname'];
		$this->ssh->_scp_exec_to($local_path_parts['basename'] , $remote_path_parts['basename']);

		// verify that it exists there

		if(!$this->ssh->remote_file_exists($destination_path)){
        $this->last_error = "streaming_server.php: place_local_media() copy to destination path \"$destination_path\" failed."; 
				error_log($this->last_error);
				return 0 ;
			}

    return 1 ;     

	}





// returns the rtsp URL to the entity's media object, sans protocol.
function get_media_url($entity_id=""){
    
    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }
    
    $entity_prefix = $this->_entity_prefix($entity_id);

    return REASON_BASE_STREAM_URL . REASON_STREAM_DIR . "/" . $entity_prefix . "/" . $entity_id . "/" . $this->_get_current_file($entity_id); 

    }


// get entity's meta info
function get_meta_info($entity_id=""){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $meta_info_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id . "/" . $this->_get_current_file($entity_id) . ".txt" ; 

    $meta_info_string = $this->ssh->read_remote_textfile($meta_info_path); 

    if($meta_info_string == "0"){ 
        $this->last_error = "streaming_server.php: get_meta_info() failed to open \"$meta_info_path\" on server " . $this->ssh->host ; 
        error_log($this->last_error);
        return ""; 
        }

    return $meta_info_string ; 

    }



// set entity's meta info
function set_meta_info($entity_id="", $meta_info=""){
    
    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $media_file_name = $this->_get_current_file($entity_id) . ".txt" ; 

    $meta_info_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id  ;

    //echo "($media_file_name , $meta_info_path , $meta_info )" ; 
    $this->ssh->write_remote_textfile($media_file_name , $meta_info_path , $meta_info ); 

    }


// get the media file's size in bytes
function get_media_size_bytes($entity_id=""){
    
    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $media_file_name = $this->_get_current_file($entity_id); 
    $media_file_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id . "/" . $media_file_name ; 

    return $this->ssh->remote_file_size_bytes($media_file_path)  ;

    }


function get_media_duration($entity_id=""){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $media_file_name = $this->_get_current_file($entity_id); 
    $media_file_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id . "/" . $media_file_name ; 

    // query exiftool on remote host
    $duration = $this->ssh->_ssh_exec("/usr/bin/exiftool -s -Duration \"" . escapeshellarg($media_file_path) . "\""); 

    if(!empty($duration)){
        // regex out 'Duraction     : ' string from exiftool output
        $duration = preg_replace('/^Duration.*:\s+/' , '' , $duration); 
        return $duration[0];
        }

    // let the log know why we failed
    $this->last_error = "streaming_server.php: get_media_duration() unable to read duration on " . $this->ssh->host . ":" .  $media_file_path ;
    error_log($this->last_error);

    return null ;  

    }




// if files already exist for an entity, move them into an old_media directory
function _move_old_media($entity_id=""){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }
    
    $entity_prefix = $this->_entity_prefix($entity_id);

    $current_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $entity_prefix . "/" . $entity_id ; 
    $holding_location = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media" . "/" . $entity_prefix . "/" . $entity_id ; 

    // make certain the holding location exists
    
    // create the old_media directory, it's name is the last two digits of the entity id
    if(!$this->ssh->remote_directory_exists(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media")){
        if(!$this->ssh->create_remote_directory(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media")){ return 0 ; }
        } 
    
    // create the first level directory, it's name is the last two digits of the entity id
    if(!$this->ssh->remote_directory_exists(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media" . "/" . $entity_prefix)){
        if(!$this->ssh->create_remote_directory(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media" . "/" . $entity_prefix)){ return 0 ; }
        } 
 
    // create the second level directory, it's the full entity_id. 
    if(!$this->ssh->remote_directory_exists(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media" . "/" . $entity_prefix . "/" . $entity_id)){
        if(!$this->ssh->create_remote_directory(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . "old_media" . "/" . $entity_prefix . "/" . $entity_id)){ return 0 ; }
        } 

    // get the media file for the entity
    $media_file_to_move = $this->_get_current_file($entity_id); 
  
    // move any existing media for a particular entity to the 'old_media' directory 
    while($media_file_to_move != "0"){
    
        if($this->_check_for_allowed_path($current_path) && $this->_check_for_allowed_path($holding_location)){

            $production_path = $current_path . "/" . $media_file_to_move ; 
            $backup_path = $holding_location . "/" . $media_file_to_move ; 

            if($this->ssh->remote_file_exists($backup_path)){
                $this->ssh->remote_file_delete($backup_path) ; 
                }

            $this->ssh->remote_file_move($production_path , $backup_path );
            }
        else { 
            $this->last_error = "streaming_server.php: _move_old_media() not permitted to move files in the current path \"$current_path\" on " . $this->ssh->host ; 
            error_log($this->last_error);
            }
   
        $prev_file = $media_file_to_move ; 
        $media_file_to_move = $this->_get_current_file($entity_id); 

        // check if we've hit the same file twice, indicating that the move has failed, likely because the wsg user doesn't have permissions
        if("$prev_file" == "$media_file_to_move"){ 
            $this->last_error = "streaming_server.php: _move_old_media() move of file $current_path/$media_file_to_move has failed on host " . $this->ssh->host ; 
            error_log($this->last_error);
            return 0 ; 
            }

        }        

    }

function media_file_path($entity_id=""){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $media_file_name = $this->_get_current_file($entity_id); 
    $media_file_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $this->_entity_prefix($entity_id) . "/" . $entity_id . "/" . $media_file_name ; 

	return $media_file_path ; 

	}
	
function media_host(){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
	return $this->ssh->host ; 
	
	}

function get_source_path()
{
 	return $this->source_media_path ;
}


// return the first file in the entites directory
function _get_current_file($entity_id=""){

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $entity_prefix = $this->_entity_prefix($entity_id);
  
    // compose the path to the entity's directory 
    $entity_path = REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $entity_prefix . "/" . $entity_id . "/";  

    // get an array of the files which exist in the entity's directory
    $current_files = $this->ssh->directory_listing($entity_path); 

    // warn if we see more than two files, there should be only two per entity (source and meta).
    if(count($current_files) > 2){
        $this->last_error = "streaming_server.php: _get_current_file() returned > 2 files for entity $entity_id at $entity_path on host " . $this->ssh->host ;
        error_log($this->last_error);
        }

    // return if we have no files
    if(count($current_files) == 0){ return 0 ; }

    // return the file that doesn't end in .txt (that's our meta info file)
    foreach($current_files as $this_file){ if(!preg_match('/\.tmp$/', $this_file)){ return $this_file ; } }

    return $current_files[0] ; 

    }


// create a directory for the entity if it doesn't already exist
function _create_entity_directory($entity_id=""){ 

    if($this->ssh == null){ return ; } // ssh is null if the streaming object is incomplete (no netid or no streaming_media folder)
    if($entity_id == ""){ return 0 ; }

    $entity_prefix = $this->_entity_prefix($entity_id);

    // create the first level directory, it's name is the last two digits of the entity id
    if(!$this->ssh->remote_directory_exists(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $entity_prefix)){
        if(!$this->ssh->create_remote_directory(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $entity_prefix)){ return 0 ; }
        } 
 
    // create the second level directory, it's the full entity_id. 
    if(!$this->ssh->remote_directory_exists(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $entity_prefix . "/" . $entity_id)){
        if(!$this->ssh->create_remote_directory(REASON_STREAM_BASE_PATH . REASON_STREAM_DIR . '/' . $entity_prefix . "/" . $entity_id)){ return 0 ; }
        } 
    
    return 1 ; 

    }


// get the last two numbers in the entity_id, used as a directory name
function _entity_prefix($entity_id = ""){

    // split up the entity id to create our directory structure
    // the last two digits of the entity_id become the prefix directory name
    return substr($entity_id, strlen($entity_id)-2, 2); 
    
    }

// determine whether the path should be writable
function _check_for_allowed_path($remote_path=""){
    
    if($remote_path == ""){ return 0 ; }
 
    // must not contain /..$ or /../
    if(preg_match('/\.\.$/' , $remote_path)){ return 0 ; }
    if(preg_match('/\.\.\//' , $remote_path)){ return 0 ; }

    // must be within certain directories
    $acceptable_directories = array( "/usr/local/helix/Content/reason/" ,
					"/home/wsg/" ,
					"/mnt/people/home/",
					"/usr/local/helix/Content/reason-dev/",
					"/usr/local/helix/Content/reason-test/"
                                   );

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

function get_last_error(){

	return $this->last_error ; 
	
	}

}


?>

