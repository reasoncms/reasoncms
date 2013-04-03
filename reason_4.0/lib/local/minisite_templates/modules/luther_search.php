<?php
/**
 * Default Reason search module
 * @package reason
 * @subpackage minisite_modules
 */
    /**
     * Include the parent class and register the module with Reason
     */
    reason_include_once( 'minisite_templates/modules/default.php' );

    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherSearchModule';

    class LutherSearchModule extends DefaultMinisiteModule
    {
        function run(){
            echo '<gcse:searchresults-only></gcse:searchresults-only>';   
        }
    }
?>
