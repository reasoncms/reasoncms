<?php
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CafMenuUploadModule';
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once('classes/plasmature/upload.php');
reason_include_once( 'classes/object_cache.php' );

// require_once CARL_UTIL_INC.'basic/misc.php';

include_once(DISCO_INC.'disco.php');

class CafMenuUploadModule extends DefaultMinisiteModule {
    var $form;
    var $elements;
        
    function init( $args = array() ) //{{{
    {
        parent::init( $args );

        $this->form = new disco();
        $this->form->elements = $this->elements;
        $this->form->actions = array('Upload');
        $this->form->error_header_text = 'Upload error';
        $this->form->add_callback(array(&$this, 'process'),'process');
        $this->form->add_callback(array(&$this, 'no_show_form'),'no_show_form');
        $this->form->add_callback(array(&$this, 'pre_show_form'),'pre_show_form');
        $this->form->add_callback(array(&$this, 'post_show_form'),'post_show_form');
        $this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
        $this->form->add_callback(array(&$this, 'on_every_time'),'on_every_time');
        $this->form->init();

    }//}}}

    function no_show_form(){
        echo 'Please login.';
    }

    function on_every_time(){
        $user = reason_check_access_to_site($this->site_id, $force_refresh = false);
        if ($user) {
            $this->form->show_form = true;
            echo "<p><a href='/login/?logout=1'>Logout</a></p>";
        } else {
            $this->form->show_form = false;
        }

        $this->form->add_element('upload_file', 'ReasonUpload', array(
                'display_name' => 'Upload Menu',
                'acceptable_types' => array('text/html'),
                'allow_upload_on_edit' => true,
                ));
    }
    
    function run()//{{{
    {
        $this->form->run();
    } //}}}

    function pre_show_form(){
        return '<div id="cafMenuUploadForm">';
    }

    function post_show_form(){
        return '</div>';
    }

    /** Return an error if not enough has been filled out in the form or passed in the URL.
     */
    function run_error_checks(&$form) {
        // check to see if an asset has been uploaded
            $file = $form->get_element( 'upload_file' );
            
            if( $file->state == 'ready' )
            {
                $form->set_error( 'upload_file', 'You must upload a file' );
            }
    }
    function process() // {{{
    {
            $document = $this->form->get_element( 'upload_file' );
            // see if document was uploaded successfully
            if(($document->state == 'received' OR $document->state == 'pending') AND file_exists( $document->tmp_full_path)){
                $path_parts = pathinfo($document->tmp_full_path);
                $suffix = (!empty($path_parts['extension'])) ? $path_parts['extension'] : '';

                // if there is no extension/suffix, try to guess based on the MIME type of the file
                if( empty( $suffix ) )
                {
                    $type_to_suffix = array(
                        'application/msword' => 'doc',
                        'application/vnd.ms-excel' => 'xls',
                        'application/vns.ms-powerpoint' => 'ppt',
                        'text/plain' => 'txt',
                        'text/html' => 'html',
                     );
                     
                     $type = $document->get_mime_type();
                     if ($type) {
                         $m = array();
                         if (preg_match('#^([\w-.]+/[\w-.]+)#', $type, $m)) {
                             // strip off any ;charset= crap
                             $type = $m[1];
                             if (!empty($type_to_suffix[$type]))
                                $suffix = $type_to_suffix[$type];
                         }
                     }
                }
                if(empty($suffix)) 
                {
                    $suffix = 'unk';
                    trigger_error('uploaded asset at '.$document->tmp_full_path.' had an indeterminate file extension ... assigned to .unk');
                }
                
                $asset_dest = WEB_PATH . 'caf/WeeklyMenu.htm';

                //move the file - if windows and the destination exists, unlink it first.
                if (server_is_windows() && file_exists($asset_dest))
                {
                    unlink($asset_dest);
                }
                echo $document->tmp_full_path;
                echo '<hr>';
                echo $asset_dest;
                
                rename ($document->tmp_full_path, $asset_dest );
            }

            // make sure to ignore the 'asset' field
            $this->form->_process_ignore[] = 'upload_file';

            // and, call the regular CM process method
            parent::process();
        } // }}}

        function where_to(){
            return 'http://caf.luther.edu/';
        }

}
?>