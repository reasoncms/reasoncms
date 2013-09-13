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
        /*
 * ?>
 * <p style=" margin:0; padding:5px 0 0 0;font-size:11px;font-family:Helvetica;font-weight:bold;text-align:center;color:red;"><a style="color: red;"href="http://reasondev.luther.edu/mobile/visitor">Click for Visitor Experience</a></p>
 *
 * <?php
        */
        echo '<div class="center">';
        echo '<ol class="icon-menu">';
        echo '<li id="menu-admissions">';
        echo '<a accesskey="1" href="admissions/">Admissions</a>';
        echo '</li>';
        echo '<li id="menu-visitors">';
        echo '<a accesskey="2" href="visitor/">Visitors</a>';
        echo '</li>';
        echo '<li id="menu-cafcam">';
        echo '<a accesskey="3" href="cafcam/">Caf Cam</a>';
        echo '</li>';
        echo '<li id="menu-cafmenu">';
        echo '<a accesskey="3" href="menu/">Caf Menu</a>';
        echo '</li>';
        echo '<li id="menu-calendar">';
        echo '<a accesskey="4" href="calendar/">Calendar</a>';
        echo '</li>';
        echo '<li id="menu-map">';
        echo '<a accesskey="4" href="map/">Campus Map</a>';
        echo '</li>';
        echo '<li id="menu-tour">';
        echo '<a accesskey="5" href="tour/">Campus Tour</a>';
        echo '</li>';
        echo '<li id="menu-contact">';
        echo '<a accesskey="5" href="contacts/">Contacts</a>';
        echo '</li>';
        echo '<li id="menu-directions">';
        echo '<a accesskey="6" href="directions/">Directions</a>';
        echo '</li>';
        echo '<li id="menu-directory">';
        echo '<a accesskey="7" href="https://www.luther.edu/directory/m.directory/">Directory</a>';
        echo '</li>';
        //echo '<li id="menu-find">';
        //echo '<a accesskey="7" href="https://find.luther.edu/">Find</a>';
        //echo '</li>';
        echo '<li id="menu-labstat">';
        echo '<a accesskey="8" href="http://labstats.luther.edu/">Lab Availability</a>';
        echo '</li>';
        echo '<li id="menu-library_search">';
        echo '<a accesskey="9" href="http://books.luther.edu/">Library Search</a>';
        echo '</li>';
        echo '<li id="menu-news">';
        echo '<a accesskey="10" href="news/">News</a>';
        echo '</li>';
        echo '<li id="menu-mail">';
        echo '<a accesskey="11" href="http://mail.luther.edu/">Norse Mail</a>';
        echo '</li>';
        echo '<li id="menu-watchsports">';
        echo '<a accesskey="12" href="http://client.stretchinternet.com/client/luther.portal">Watch Sports</a>';
        echo '</li>';
        echo '<li id="menu-home">';
        echo '<a accesskey="14" href="http://www.luther.edu/">Luther Website</a>';
        echo '</li>';
        echo '</ol>';
        echo '</div>';
    }
}
?>
