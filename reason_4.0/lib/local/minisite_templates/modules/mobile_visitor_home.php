<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileVisitorHomeModule';

class MobileVisitorHomeModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
/*
 * ?>
 *
 * <p style=" margin:0; padding:5px 0 0 0;font-size:11px;font-family:Helvetica;font-weight:bold;text-align:center;color:red;">Welcome to Luther College!</p>
 *
 * <?php
 *
 */
        echo '<div class="center">';
        echo '<ol class="icon-menu">';
        echo '<li id="menu-map">';
        echo '<a accesskey="1" href="/mobile/map/">Campus Map</a>';
        echo '</li>';
        echo '<li id="menu-tour">';
        echo '<a accesskey="2" href="/mobile/tour/">Campus Tour</a>';
        echo '</li>';
        echo '<li id="menu-contact">';
        echo '<a accesskey="3" href="/mobile/contacts/">Contacts</a>';
        echo '</li>';
        echo '<li id="menu-directions">';
        echo '<a accesskey="4" href="/mobile/directions/">Directions</a>';
        echo '</li>';
        //echo '<li id="menu-fullmobile">';
        //echo '<a accesskey="5" href="/mobile/">Full Mobile</a>';
        //echo '</li>';
        echo '</ol>';
        echo '</div>';
    }
}
?>
