<?php
reason_include_once( 'minisite_templates/modules/default.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'norseCalendarModule';

class norseCalendarModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }
    function has_content() {
        return true;
    }
    function run() {
        $site_id = $this->site_id;

        $es = new entity_selector( $site_id );
        $es->add_type( id_of( 'norse_calendar_type' ) );
        $norse_calendar_info = $es->run_one();

        foreach ($norse_calendar_info as $info) {
            echo '<iframe src="https://www.google.com/calendar/hosted/luther.edu/embed?height=600&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src='. $info->get_value('name') .'%40luther.edu&amp;color=%23A32929&amp;ctz=America%2FChicago" style=" border-width:0 " width="800" height="600" frameborder="0" scrolling="no"></iframe>';
            
        }
    }
}
?>