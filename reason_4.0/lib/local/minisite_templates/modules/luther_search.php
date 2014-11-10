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
        function init($args = array())
        {
            $head_items = $this->get_head_items();
            $head_items->remove_head_item('script', array('src'=>REASON_HTTP_BASE_PATH.'js/jquery.maskedinput-1.3.1.min.js'));
            $head_items->remove_head_item('script', array('src'=>REASON_HTTP_BASE_PATH.'js/jquery.tools.min.js'));
            $head_items->remove_head_item('script', array('src'=>REASON_HTTP_BASE_PATH.'js/timer/timer.js'));
            $head_items->remove_head_item('script', array('src'=>REASON_HTTP_BASE_PATH.'jquery.watermark-3.1.3/jquery.watermark.min.js'));
            $head_items->remove_head_item('script', array('src'=>'/javascripts/jquery.init.js'));
            $head_items->remove_head_item('script', array('src'=>'/javascripts/jquery.tmpl.js'));
            $head_items->remove_head_item('script', array('src'=>'/javascripts/jquery.metadata.js'));
            $head_items->remove_head_item('script', array('src'=>'/javascripts/modernizr-1.1.min.js'));
            $head_items->remove_head_item('script', array('src'=>'/javascripts/highslide/highslide-full.js'));
            $head_items->remove_head_item('script', array('src'=>'/javascripts/highslide/highslide-overrides.js'));
            $head_items->remove_head_item('script', array('src'=>'//ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js'));
        }

        function run(){
            echo '<gcse:searchresults-only></gcse:searchresults-only>';   
        }
    }
?>
