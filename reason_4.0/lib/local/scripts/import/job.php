<?php
reason_include_once('function_libraries/root_finder.php');

/**
 * We need to define these fields somewhere ... probably in the particular job.
 * 
 * - a job is an import operation that returns true if it completes, and false if it should be pushed to the end of the queue.
 * - a job has a property self_destruct - if set to true this job will return true immediately next time it is run.
 * -
 * - method - run()
 * - a job returns true if it ran - usually signifies a database change.
 * - a job returns false if it should be pushed to the end of the queue - should NEVER have changed the database.
 * - a job can run set_result($result) to store a result value that other jobs can grab.
 */
abstract class AbstractImportJob implements ImportJob
{
	var $_has_run = false;
	var $push_to_end = true;
	var $job_key;
	var $results;
	var $report;
	var $alert;
	var $_destroyed;
	
	final function run()
	{
		if ($this->_destroyed) return true;
		return $this->run_job();
	}
	
	final function self_destruct()
	{
		$this->_destroyed = true;
	}
	
	final function set_result($result)
	{
		$this->results[$this->job_key] = $result;
	}
	
	final function get_result($job_key = NULL)
	{
		return (!empty($job_key) && isset($this->results[$job_key])) ? $this->results[$job_key] : NULL;
	}
	
	/**
	 * The report is an 
	 */
	final function get_report()
	{
		if (!empty($this->report)) return $this->report;
	}
	
	/**
	 * Alerts are user notices of critical importance.
	 *
	 * For instance, in the wordpress import, we use alerts to build the actual text for rewrite rules.
	 *
	 * @return array key / value pairs of any alert 
	 */
	final function get_alerts()
	{
		if (!empty($this->alert)) return $this->alert;
	}
}

/**
 * Anything using this should implement run_job and should return true as fast as possible
 */
interface ImportJob
{
	function run();
	
	/**
	 * Is this actually useful at all or should run_job just do the self_destruct (return true) if it wants when instantiated twice?
	 */ 
	function self_destruct();
	
	// do whatever we do - if we ever hit an unknown hash return false 
	function run_job();
}

/**
 * Run all our import jobs
 *
 * The stacked job set has a few characteristics
 * - absolutely zero direct dependencies between jobs (though we might ask for a job result by id, and a job might depend upon results for other jobs existing).
 * - if a job neeeds the result from a job id but there is no result yet, we move the job to the end of the queue.
 * - if a job fails with no successful jobs between the last attempt we quit the loop.
 */ 
class ImportJobStack
{
	var $job_queue = array();
	var $job_results = array(); // each job can populate a result that other jobs have access to
	var $complete_jobs = array();
	var $report;
	var $alerts;
	
	function run()
	{
		$report = array();
		$alerts = array();
		$fail_index = 0;
		$job_queue_count = count($this->job_queue);
		while (!empty($this->job_queue) && ($fail_index < $job_queue_count))
		{
			$job = reset($this->job_queue);
			$job_key = key($this->job_queue);
			$job_results[$job_key] = NULL;
			unset($this->job_queue[$job_key]);
			$job->results =& $this->job_results;
			$job->job_key = $job_key;
			$result = $job->run();
			if (!$result)
			{
				$this->job_queue[$job_key] = $job; // this should push it to the end and preserve the key I think ...
				//$this->report[] = $job_key . ' is the job key - result was false pushed to end.';
				$fail_index++;
			}
			else
			{
				$complete_jobs[$job_key] = $job;
				if ($alerts = $job->get_alerts())
				{
					foreach ($alerts as $k=>$v)
					{
						$this->alerts[$k] = $v;
					}
				}
			}
			if ($report = $job->get_report()) $this->report[get_class($job)][] = $report;
			$job_queue_count = count($this->job_queue);
		}
	}
	
	function get_report()
	{
		return $this->report;
	}
	
	function get_alerts()
	{
		return $this->alerts;
	}
	
	/**
	 * @param object ImportJob
	 */
	function add_job($job, $id = NULL)
	{
		if (!empty($id))
		{
			if (isset($this->job_queue[$id]))
			{
				trigger_error('You cannot add job id ' . $id . ' it is already in the queue', FATAL);
			}
			$this->job_queue[$id] = $job;
		}
		else
		{
			$this->job_queue[] = $job;
		}
	}
}