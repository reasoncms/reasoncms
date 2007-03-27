<?php

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CustomFormModule';
reason_include_once( 'minisite_templates/modules/default.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

class CustomFormModule extends DefaultMinisiteModule
{
    var $user;
    var $email;
    
    function init( $args )
    {
        parent::init( $args );
	$dir = new directory_service();
	if ($dir->search_by_attribute('ds_username', getenv('REMOTE_USER'), array('ds_email','ds_fullname')))
	{
		$this->user = $dir->get_first_value('ds_fullname');
		$this->email = $dir->get_first_value('ds_email');
	}
    }

    function run()
    {
        //for overloading
        //switch( $stage ), or something like that
    }
    
    function show_form()
    {
        //for overloading
    }
    
    function finalize()
    {
        echo '<h3>form completed!</h3>';
    }
}

?>
