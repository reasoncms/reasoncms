<?php
reason_include_once( 'minisite_templates/modules/default.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFooterModule';

class LutherFooterModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }
    function has_content() {
        return true;
    }
    function run() {
        echo '<div id="foot">';
        echo '<center><p><br /></p><p></p><p></p>'."\n";
        echo '<div><p></p>&#169 '.date("Y").' • Luther College • 700 College Dr • Decorah, IA 52101 USA'."\n".'</div>';
        echo '<div> Phone: 563-387-2000 or 800-4 LUTHER (800-458-8437) <p></p></div>';
        //echo '<div><i>Email burkaa01@luther.edu with questions, comments, concerns</i></div></center>'."\n";
        google_analytics();
    }
}
?>