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
        $es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_norse_calendar'));
        $norse_calendar_info = $es->run_one();
		
       	echo '<div class="norseCalendar">'."\n";
        foreach ($norse_calendar_info as $info) {
            echo '<meta name = "viewport" content = "width = device-width, height = device-height" />';
            echo '<iframe src="https://www.google.com/calendar/hosted/luther.edu/embed?showTitle=0&amp;height=400&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src='. $info->get_value('name') .'%40luther.edu&amp;color=%23A32929&amp;ctz=America%2FChicago" style=" border-width:0 " width="100%" height="400" frameborder="0" scrolling="no"></iframe>';
            
        }
        echo '</div>'."\n";
    }
}
?>