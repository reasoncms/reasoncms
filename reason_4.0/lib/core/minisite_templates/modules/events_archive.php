<?php 
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsArchiveModule';


class EventsArchiveModule extends EventsModule
{
	var $show_calendar_grid = false;
	var $show_views = false;
	function init_list()
	{
		$this->today = date('Y-m-d');
		$init_array = array();
		$init_array['site'] = $this->parent->site_info;
		$init_array['start_date'] = '1970-01-01 00:00:00';  // Make the calendar show all events
		if(!empty($this->pass_vars['view']))
			$init_array['view'] = $this->pass_vars['view'];
		if(!empty($this->pass_vars['audience']))
		{
			$audiences = array($this->pass_vars['audience']);
			$init_array['audiences'] = $audiences;
		}
		if(!empty($this->pass_vars['category']))
		{
			$category = new entity($this->pass_vars['category']);
			$categories = array( $category->id()=>$category );
			$init_array['categories'] = $categories;
		}
		if(!empty($this->ideal_count))
			$init_array['ideal_count'] = $this->ideal_count;
		$this->calendar = new reasonCalendar($init_array);
		$this->calendar->run();
	}
}
?>
