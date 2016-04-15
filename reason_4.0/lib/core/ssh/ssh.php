<?php
/**
 * @package reason
 * @subpackage ssh
 */
 
/**
 * SSH access Object
 *
 * @author Matthew Bockol
 * @version .003
 * @date 01.31.2006
 * 
 * Permits listing directories on remote hosts, transferring files to a temporary local directory.  Allows creating directories,
 * copying files, deleting files on the remote host.
 *
 * Usage:
 *
 *  ssh()
 *  $obj = new ssh('host' , 'userid' , '/full/remote/path', '/path/to/userid@host.sshkey');
 *
 *  directory_listing() 
 *  $array = $obj->directory_listing() // returns file names, not full path
 *
 *  directory_count() 
 *  $int = $obj->directory_count() // returns the number of files
 *
 *  temp_file_copy()
 *  $local_path = $obj->temp_file_copy() // copies all the files from the remote directory to the local_path returned
 *  $local_path = $obj->temp_file_copy("this_file.jpg") // copies only "this_file.jpg" from the remote host to the local_path returned
 *
 *  clear_temp_store()
 *  $obj->clear_temp_store() // remove local copy of files for just this object (others may exist and will not be touched unless the remote path matches)
 *
 *  remote_file_copy($original_remote_file_path,$new_remote_file_path)
 *  $obj->remote_file_copy("this_file.txt" , "that_file.txt");
 *
 *  remote_file_move($original_remote_file_path,$new_remote_file_path)
 *  $obj->remote_file_move("this_file.txt" , "that_file.txt");
 *
 *  remote_file_delete($remote_file_path)
 *  $obj->remote_file_delete("this_file.txt"); 
 *
 *  remote_md5sum($remote_file_path)
 *  $obj->remote_md5sum("this_file.txt") // returns the full md5sum sans file path.
 *
 *  remote_file_exists($remote_file_path)
 *  $obj->remote_file_exists("this_file.txt") // returns 1 or 0 
 *
 *  remote_directory_exists($remote_directory_path)
 *  $obj->remote_directory_exists("/tmp/") // returns 1 or 0
 *
 *  create_remote_directory($remote_directory_path)
 *  $obj->create_remote_directory("/home/wsg/foo/") // returns 1 or 0 , fails if the directory already exists 
 *
 *  return the contents of a remote file as a string
 *  $string = read_remote_textfile($file) // where file is the full path
 * 
 *  write the contents of a string to a remote file (overwriting anything that was there)
 *  write_remote_textfile($file,$remote_path,$string){ // file is the filename along, remote_path is the directory sans trailing "/" , string is the contents to be written
 *
 *  get the size of a file on the remote server in human readable format
 *  $string = remote_file_size($remote_file_path)
 *
 *  get the size of a file on the remote server in bytes
 *  $string = remote_file_size_bytes($remote_file_path)
 */


// These constants need to be defined in the Reason settings
// REASON_SSH_DEFAULT_HOST,REASON_SSH_DEFAULT_USERID,REASON_SSH_TEMP_STORAGE,REASON_IMAGE_IMPORT_SSHKEYFILE

class ssh{ 

var $host ; 
var $userid ;
var $ssh_key_file ;
var $remote_path ; 
var $local_path ; 

// constructor
function ssh($host=REASON_SSH_DEFAULT_HOST , $userid=REASON_SSH_DEFAULT_USERID , $remote_path="", $ssh_key_file = REASON_IMAGE_IMPORT_SSHKEYFILE ){
    
    if($remote_path == ""){ die("You must defined a remote_path when calling the ssh constructor."); }
    
    $this->host = $host ;
    $this->userid = $userid ;
    $this->ssh_key_file = $ssh_key_file ; 
    $this->remote_path = $remote_path ; 
    $this->local_path = REASON_SSH_TEMP_STORAGE . "/" . $this->userid . "/" . md5($this->remote_path) ;

    }


// return a listing of files on the remote host
function directory_listing($remote_path=""){

    // set a new remote path if specified
    if($remote_path != ""){ $this->remote_path = $remote_path ; }

    // find lists files in a particular directory
    // -maxdepth 1 -- do not search sub directories
    // -type f -- only show files
    // -printf '%f\n' -- exclude full path
    $command = "find " . escapeshellarg($this->remote_path) . " -maxdepth 1 -type f " ; 

    $result = $this->_ssh_exec($command);

    // remove the full path
    $stripped_result = array() ;
    foreach($result as $file_path){
        array_push($stripped_result , basename($file_path));
        }

    return $stripped_result ;

    }

// return the number of files in the path on the remote host
function directory_count($remote_path=""){

    // set a new remote path if specified
    if($remote_path != ""){ $this->remote_path = $remote_path ; }
    
    return count($this->directory_listing($this->remote_path)); 
    
    }


// copy files from remote host to temporary directory
function temp_file_copy($file="*"){

    if($file == ""){ return ; }

    if($this->userid == ""){ die("ssh.php: userid must be defined."); }
    if($this->remote_path == ""){ die("ssh.php: remote path must be defined");}

    $this->_init_temp_store();
    $this->_scp_exec_from($file);
    
    return $this->local_path ; 

    }


// erase temporary files
function clear_temp_store(){

    if(!file_exists($this->local_path)){ return ; }

    if ($handle = opendir($this->local_path)) {

        // loop over the directory
        while (false !== ($file = readdir($handle))) {
            if($file != "." && $file != ".."){
                unlink($this->local_path . "/" . $file); 
                }
            }

        closedir($handle);

        }

    // remove the directory as well
    if(is_dir($this->local_path)){ rmdir($this->local_path); } else { die("ssh.php: tried to delete $this->local_path, but it's not a directory and should be."); }

    }


// make sure the temp storage path actually exists. If not, create it. 
function _init_temp_store(){
    
    // make sure we've got something to work with
    if($this->userid == ""){ die("ssh.php: userid must be defined."); }
    if($this->remote_path == ""){ die("ssh.php: remote path must be defined");}

    // build up three levels of temp storage /tmp/import , userid , local_path)
    $dir_list = array(
        REASON_SSH_TEMP_STORAGE , 
        REASON_SSH_TEMP_STORAGE . "/" . $this->userid , 
        $this->local_path
        );
 
    foreach( $dir_list as $directory ){

        if(!file_exists($directory)){
            // create dir, check for success -- die on failure
            mkdir($directory);
            if(!file_exists($directory)){ die("ssh.php: failed to create $directory directory."); }
            }

        // be sure that REASON_SSH_TEMP_STORAGE is actually a directory
        if(!is_dir($directory)){ die("ssh.php: $directory already exists and is not a directory."); }

        }

    }


// Copy a file from one location on a remote server to a new location on the same server
function remote_file_copy($original_remote_file_path="",$new_remote_file_path=""){

    if($original_remote_file_path == "" || $new_remote_file_path == ""){ return 0 ; }

    // make sure there's something to copy in the first place
    if(!$this->remote_file_exists($original_remote_file_path)){ 
        error_log("ssh.php: remote_file_copy() remote file \"$original_remote_file_path\" does not exist.");
        return 0 ; 
        }

    // don't permit overwriting existing files. If you want to override you can explicitly erase the original
    if($this->remote_file_exists($new_remote_file_path)){ 
        error_log("ssh.php: remote_file_copy() overwriting the existing file \"$new_remote_file_path\" on " . $this->host . " is not permitted. Please remove the original manually.");
        return 0 ; 
        }

    // perform the copy
    $this->_ssh_exec("cp \"" . escapeshellarg($original_remote_file_path) . "\" \"" . escapeshellarg($new_remote_file_path) . "\""); 

    // take the md5 sum of the original and new copy
    $orig_md5sum = $this->remote_md5sum($original_remote_file_path);
    $new_md5sum = $this->remote_md5sum($new_remote_file_path);

    // be certain that the md5sums are the same, otherwise the copy has likely failed
    if(strncmp($orig_md5sum,$new_md5sum,32) != 0){
        error_log("ssh.php: remote_file_copy() md5sum mismatch, \"$original_remote_file_path\" is not the same as \"$new_remote_file_path\" on " . $this->host);
        return 0 ; 
        }

    return 1 ;

    }


// Move a file from one location on a remote server to a new location on the same server
function remote_file_move($original_remote_file_path="",$new_remote_file_path=""){

    // make sure there's something to copy in the first place
    if(!$this->remote_file_exists($original_remote_file_path)){ 
        error_log("ssh.php: remote_file_move() remote file \"$original_remote_file_path\" does not exist.");
        return 0 ; 
        }

    // don't permit overwriting existing files. If you want to override you can explicitly erase the original
    if($this->remote_file_exists($new_remote_file_path)){ 
        error_log("ssh.php: remote_file_move() overwriting the existing file \"$new_remote_file_path\" on " . $this->host . " is not permitted. Please remove the original manually.");
        return 0 ; 
        }

    // take the md5 sum of the original 
    $orig_md5sum = $this->remote_md5sum($original_remote_file_path);

    // perform the copy
    $this->_ssh_exec("mv \"" . escapeshellarg($original_remote_file_path) . "\" \"" . escapeshellarg($new_remote_file_path) . "\""); 

    // take the md5 sum of the new copy
    $new_md5sum = $this->remote_md5sum($new_remote_file_path);

    // be certain that the md5sums are the same, otherwise the move has likely failed
    if(strncmp($orig_md5sum,$new_md5sum,32) != 0){
        error_log("ssh.php: remote_file_move() md5sum mismatch, \"$original_remote_file_path\" is not the same as \"$new_remote_file_path\" on " . $this->host);
        return 0 ; 
        }

    return 1 ;

    }


// Oh happy slaughter ...
function remote_file_delete($remote_file_path=""){

    if($remote_file_path == ""){ return 0 ; }

    // don't try to delete something that doesn't actually exist
    if(!$this->remote_file_exists($remote_file_path)){
        error_log("ssh.php: remote_file_delete() file \"$remote_file_path\" on " . $this->host . " cannot be erased because it does not exist."); 
        return 0 ; 
        }

    // "A Million Voices Crying Out In Unison, Then Suddenly Silenced" -  Obi Wan Kenobi
    $this->_ssh_exec("rm \"" . escapeshellarg($remote_file_path) . "\"");
   
    // confirm the destruction 
    if($this->remote_file_exists($remote_file_path)){
        error_log("ssh.php: remote_file_delete() file \"$remote_file_path\" on " . $this->host . " was not deleted."); 
        return 0 ; 
        }

    return 1 ; 

    }


// take the md5 sum of a file on the remote host
function remote_md5sum($remote_file_path=""){

    if($remote_file_path == ""){ return 0 ; }

    // take the md5sum of the file
    $result = $this->_ssh_exec("md5sum \"" . escapeshellarg($remote_file_path) . "\" 2>/dev/null"); 

    // the result will be empty if the file doesn't exist or the test fails in some way
    if(empty($result)){ 
        error_log("ssh.php: remote_md5sum() unable to take the md5sum of \"$remote_file_path\" on " . $this->host); 
        return 0 ; 
        }

    // strip out the path information provided by the md5sum command
    $sum = substr($result[0],0,32) ;

    return $sum ; 

    }


// get the remote file size in bytes
function remote_file_size_bytes($remote_file_path=""){

    if($remote_file_path == ""){ return 0 ; }
    if(!$this->remote_file_exists($remote_file_path)){ return 0 ; }
    $result = $this->_ssh_exec("ls -l \"" . escapeshellarg($remote_file_path) . "\" | awk '{print $5}'");

    if(count($result) > 1){ error_log("ssh.php: remote_file_size() multiple results on ls -sh for file \"$remote_file_path\" on $this->host"); } 

    // chop of the result into size and filename
    $result_split = explode(" " , $result[0]) ; 

    // return the size
    return $result_split[0] ; 
    }



// check whether a file exists on the remote server
function remote_file_exists($remote_file_path=""){
   
    if($remote_file_path == ""){ return 0 ; }
    $result = $this->_ssh_exec("find \"" . escapeshellarg($remote_file_path) . "\" 2>/dev/null"); 
    if(!empty($result) && $result[0] == $remote_file_path){ return 1 ; }
    return 0 ; 
 
    }

// check whether a directory exists on the remote server
function remote_directory_exists($remote_directory_path=""){

    // this function is the same as remote_file_exists
    return $this->remote_file_exists($remote_directory_path);

    }


// create a directory on the remote host
function create_remote_directory($remote_directory_path=""){

    // the path must not be blank
    if($remote_directory_path == ""){ return 0 ; }

    // don't try to create a directory which already exists
    if($this->remote_directory_exists($remote_directory_path)){
        error_log("ssh.php: create_remote_directory() attempted to created directory \"$remote_directory_path\" on " . $this->host . ", but it already exists."); 
        return 0 ; 
        }

    // create the directory
    $this->_ssh_exec("mkdir \"" . escapeshellarg($remote_directory_path) . "\""); 

    // confirm that it was created
    return $this->remote_directory_exists($remote_directory_path);

    }


// Execute command on remote host, return array of results
function _ssh_exec($command=""){

    // NOP if command is not specified
    if($command == ""){ return ; }

    // connect and execute
    exec('ssh -x -i ' . $this->ssh_key_file. " " . $this->userid . "@" . $this->host . ' ' . $command , $result);

    // exec returns an array of the lines produced
    return $result ;

    }


// execute scp from remote host
function _scp_exec_from($file="*"){

    // NOP if command is not specified
    if($file == ""){ return ; }

    // connect and execute
    exec('scp -i ' . $this->ssh_key_file. " "  . $this->userid . "@" . $this->host . ':' . $this->remote_path . "/" . $file . " " . $this->local_path . "/"  , $result);
    
    // exec returns an array of the lines produced
    return $result ;

    }


// execute scp to remote host
function _scp_exec_to($file_from="",$file_to=""){

    // NOP if command is not specified
    if($file_to == ""){ return ; }
    if($file_from == ""){ return ; }
	$command = 'scp -i '. $this->ssh_key_file. ' "' . $this->local_path . "/" . $file_from . '" ' . $this->userid . "@" . $this->host . ':"' . $this->remote_path . "/" . $file_to.'"';
    // connect and execute
    $result = exec($command);
    
    // exec returns an array of the lines produced
    return $result ;

    }


// creates of overrites the contents of file and remote path with string
function write_remote_textfile($file="",$remote_path="",$string=""){
    
    if($file==""){ return ; }

    // do not permit directory traveral code in filenames
    if(preg_match('/\.\.\//' , $file)){ error_log("ssh.php: write_remote_textfile() file \"$file\" not permitted because of '../' in filename."); return 0 ;}
    if(preg_match('/\/\.\./' , $file)){ error_log("ssh.php: write_remote_textfile() file \"$file\" not permitted because of '/..' in filename."); return 0 ;}
    
    // do not permit directory traveral code in pathnames
    if($remote_path==""){ return ; }
    if(preg_match('/\.\.\//' , $file)){ error_log("ssh.php: write_remote_textfile() file \"$remote_path\" not permitted because of '../' in pathname."); return 0 ;}
    if(preg_match('/\/\.\./' , $file)){ error_log("ssh.php: write_remote_textfile() file \"$remote_path\" not permitted because of '/..' in pathname."); return 0 ;}
    
    // set our temp file path
    $tmp_path = "/tmp" ; 
    $tmp_filename = $file . "." . time() . ".tmp" ; 
    $tmp_fullpath = $tmp_path . "/" . $tmp_filename ;  

    // open our file
    if (!$handle = fopen($tmp_fullpath, 'w')) {
        error_log("ssh.php: write_remote_textfile cannot open file \"$tmp_fullpath\".");
        return 0 ; 
        }

    // write our string into it
    if (fwrite($handle, $string) === FALSE) {
        error_log("ssh.php: write_remote_textfile cannot write to file \"$tmp_fullpath\".");
        return 0 ;
        } 

    // close it
    fclose($handle); 

    // copy temp file to remote host
    $this->local_path = $tmp_path ; 
    $this->remote_path = $remote_path ; 
    $this->_scp_exec_to($tmp_filename , $file);

    // remote temp file
    if(file_exists($tmp_fullpath) && preg_match('/\.tmp$/',$tmp_fullpath) ){
        unlink($tmp_fullpath); 
        }
    else { 
        error_log("ssh.php: write_remote_textfile() unable to unlink file \"$tmp_fullpath\" because it either doesn't exist or doesn't end in \".tmp\"."); 
        }

    return 1 ; 

    }


// cats the remote file and returns a string with its contents
function read_remote_textfile($file=""){

    if($file == ""){ return 0; }
    
    // make sure there's something to read in the first place
    if(!$this->remote_file_exists($file)){ 
        error_log("ssh.php: read_remote_textfile() remote file \"$file\" does not exist.");
        return 0 ; 
        }

    $result = $this->_ssh_exec("cat \"" . escapeshellarg($file) . "\""); 

    $string = ""; 
    foreach($result as $line){ $string .= $line . "\n"; }

    return $string ; 

    }

}

?>
