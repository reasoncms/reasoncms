<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileIconHomeModule';

class MobileIconHomeModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
        echo '<div class="center">';
        echo '<ol class="icon-menu">';
        echo '<li id="menu-home">';
        echo '<a accesskey="2" href="http://www.luther.edu/">Full Site</a>';
        echo '</li>';
        echo '<li id="menu-directions">';
        echo '<a accesskey="3" href="http://reasondev.luther.edu/mobile/directions/">Directions</a>';
        echo '</li>';
        echo '<li id="menu-mail">';
        echo '<a accesskey="4" href="http://mail.luther.edu/">Norse Mail</a>';
        echo '</li>';
        echo '</ol>';
        echo '</div>';
    }
?>
