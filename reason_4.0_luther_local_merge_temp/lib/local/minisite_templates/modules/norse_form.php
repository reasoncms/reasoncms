<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'NorseFormModule';

class NorseFormModule extends DefaultMinisiteModule {

    function init($args = array()) {
        
    }
    
    function on_every_time() {
        parent::on_every_time();
        
        echo '<script type="text/javascript" src="/reason/js/google_map.js"></script>';
        
    }

    function has_content() {
        return true;
    }

    function run() {
       
        
        echo '<script type=”text/JavaScript”';
        echo 'src=”http://maps.google.com/maps/apis/js?sensor=false”>';
        echo '</script>';

        echo '<style type"text/css">';
        echo '#map{';
        echo 'width:500px;';
        echo 'height:500px;';
        echo 'float:left;';
        echo 'position:absolute;';
        echo 'left:300px;';
        echo 'top:200px;';

        echo '}';
        echo ' </style>';
    }

}
?>
