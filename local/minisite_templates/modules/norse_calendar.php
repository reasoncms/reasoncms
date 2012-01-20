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
		
	 foreach ($norse_calendar_info as $info) {
		$default_view = $info->get_value('default_view');
		$names = str_replace(' ','',$info->get_value('name'));
		$name_array = explode(',',$names);
	
		
	
	$calendar_color = array('23711616','230D7813','237A367A','238C500B','23AB8B00','234E5D6C','23865A5A','232952A3','23B1365F','23BE6D00');

	$i = sizeof($name_array);
	$ncal = sizeof($calendar_color);
	$source_string = '';
	while ($i >= 1)
	{ 
		$source_string .= 'src='. $name_array[$i-1];
		if (!preg_match("/@/", $name_array[$i-1]))
		{
			$source_string .= '%40luther.edu';
		}
		$source_string .= '&amp;color=%' . $calendar_color[($i-1) % $ncal];
		$source_string .= '&amp;';
		
		$i--;
	}

	echo '<div class="norseCalendar">'."\n"; 
	echo '<meta name = "viewport" content = "width = device-width, height = device-height" />';
	echo '<iframe src="https://www.google.com/calendar/hosted/luther.edu/embed?showTitle=0&amp;mode='. $default_view .'&ampheight=400&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;' . $source_string . 'ctz=America%2FChicago;" style=" border-width:0 " width="100%" height="600" frameborder="0" scrolling="no"></iframe>';
	echo '</div>'."\n";
	
	
	
	}
	
	
	
	
	
	
	
	

   
    /*
	echo '<div class="norseCalendar">'."\n";
        foreach ($norse_calendar_info as $info) {
            echo '<meta name = "viewport" content = "width = device-width, height = device-height" />';
            echo '<iframe src="https://www.google.com/calendar/hosted/luther.edu/embed?showTitle=0&amp;mode='. $info->get_value('default_view') .'&ampheight=400&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src='. $info->get_value('name') .'%40luther.edu&amp;color=%232952A3&amp;ctz=America%2FChicago" style=" border-width:0 " width="100%" height="400" frameborder="0" scrolling="no"></iframe>';
            
        }
        echo '</div>'."\n";
*/

    }
}
?>