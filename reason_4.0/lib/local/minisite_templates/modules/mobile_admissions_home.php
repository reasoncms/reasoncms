<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileAdmissionsHomeModule';

class MobileAdmissionsHomeModule extends DefaultMinisiteModule {
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
        //echo '<li id="menu-contact">';
        //echo '<a accesskey="3" href="/mobile/contacts/">Contacts</a>';
        //echo '</li>';
        echo '<li id="menu-directions">';
        echo '<a accesskey="3" href="/mobile/directions/">Directions</a>';
        echo '</li>';
        echo '<li id="menu-directory">';
        echo '<a accesskey="4" href="https://www.luther.edu/directory/m.directory/">Directory</a>';
        echo '</li>';
        echo '<li id="menu-news">';
        echo '<a accesskey="5" href="/mobile/news/">News</a>';
        echo '</li>';
        //echo '<li id="menu-fullmobile">';
        //echo '<a accesskey="5" href="/mobile/">Full Mobile</a>';
        //echo '</li>';
        echo '</ol>';
        echo '<div style="text-align: center;"><p><strong>Admissions Office</strong><br />
Luther College<br />
700 College Drive<br />
Decorah, Iowa 52101<br />
800-4 LUTHER<br />
(800-458-8437)<br />
admissions@luther.edu</p></div>';
        echo '</div>';
    }
}
?>