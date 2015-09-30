<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'JobsModule';

/**
 * A minisite module that lists job entities on the current site
 *
 * @todo redo, based on generic3/4
 */
class JobsModule extends DefaultMinisiteModule
{
	var $job_type;
	var $job_type_id;
	var $site_specific = true;
	var $div_id = 'jobs';
	var $position_start_text = 'Date Open';
	var $cleanup_rules = array(
		'job_id' => array('function' => 'turn_into_int')
	);
	
	function init( $args = array() ) // {{{
	{
		parent::init( $args );
		$this->job_type = new entity( id_of( 'job' ) );
		$this->job_type_id = $this->job_type->id();
		$this->init_list();
		if(!empty($this->request['job_id']) && empty($this->jobs[ $this->request['job_id'] ]))
			http_response_code(404);
	} // }}}
	function run()
	{
		echo '<div id="'.$this->div_id.'">'."\n";
		if( !empty( $this->request['job_id'] ) )
			$this->show_job();
		$this->list_jobs();
		$this->show_feed_link();
		echo '</div>'."\n";
	}
	function list_jobs() // {{{
	{
		echo '<ul>'."\n";
		foreach( $this->jobs as $job )
		{
			if(empty($this->request['job_id']) || $job->id() != $this->request['job_id'])
			{
				echo '<li><h4><a href="?job_id='.$job->get_value( 'id' );
				if(!empty($this->parent->textonly))
					echo '&amp;textonly='.$this->parent->textonly;
				echo '">'.$job->get_value( 'name' ).'</a></h4></li>';
			}
		}
		echo '</ul>'."\n";
	} // }}}
	function init_list() // {{{
	{
		if($this->site_specific)
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = "Getting the jobs on this site";
		}
		else
		{
			$es = new entity_selector();
			$es->description = "Getting all jobs -- not just on this site";
		}
		$es->add_type( id_of( 'job' ) );
		$es->add_relation( 'show_hide.show_hide = "show"' );
		$es->add_relation( 'job.posting_start <= "'.date( 'Y-m-d' ).'"' );
		$es->add_relation( 'adddate( job.posting_start, interval duration.duration day ) >= "'.date( 'Y-m-d' ).'"' );
		$this->jobs = $es->run_one();
		if(!empty($this->request['job_id']) && !empty($this->jobs[$this->request['job_id']]) )
		{
			$this->_add_crumb( $this->jobs[$this->request['job_id']]->get_value('name') );
		}
	} // }}}
	function show_job()
	{
		if(!empty($this->jobs[ $this->request['job_id'] ]))
		{
			$this->job = $this->jobs[ $this->request['job_id'] ];
			echo '<h3>'.$this->job->get_value( 'name' );
			if($this->job->get_value( 'title_extension' ))
				echo ' ('.$this->job->get_value( 'title_extension' ).')';
			echo '</h3>'."\n";
			echo '<ul>'."\n";
			if($this->job->get_value( 'office' ))
				echo '<li><strong>Office:</strong> '.$this->job->get_value( 'office' ).'</li>'."\n";
			if($this->job->get_value( 'position_start' ))
				$this->show_position_start();
			echo '<li><strong>Description:</strong> '.str_replace(array('<h3>','</h3>'), array('<h4>','</h4>'), $this->job->get_value( 'content' ) ).'</li>'."\n";
			echo '</ul>'."\n";
			echo '<h3 class="otherOpps">Other Opportunities</h3>'."\n";
		}
		else
			$this->show_job_error();
	}
	function show_position_start()
	{
		if($this->job->get_value( 'position_start' ) && $this->job->get_value( 'position_start' ) != '0000-00-00' )
		{
			echo '<li><strong>'.$this->position_start_text.':</strong> ';
			if($this->job->get_value( 'position_start' ) >= date('Y-m-d'))
				echo prettify_mysql_datetime($this->job->get_value( 'position_start' ), 'F j, Y');
			else
				echo 'Immediately';
			echo '</li>'."\n";
		}
	}
	function show_job_error() // {{{
	{
		echo '<p>We\'re sorry, the job requested is not currently available. This may be due to incorrectly typing in the URL; if you believe this is a bug, please report it to the contact person listed at the bottom of the page.</p>'."\n";
	} // }}}
	function show_feed_link()
	{
		if($this->job_type->get_value('feed_url_string'))
		{
			reason_include_once('function_libraries/feed_utils.php');
			echo make_feed_link( $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$this->job_type->get_value('feed_url_string'), 'RSS feed for this site\'s jobs');
		}
		
	}
}
?>
