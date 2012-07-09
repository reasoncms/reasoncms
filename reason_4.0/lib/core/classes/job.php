<?php
/**
 * A framework for assembling and processing stacks of jobs.
 *
 * This framework should be used to break complicated upgrade, migration, or other scripts and operations 
 * into sensible, reusable pieces.
 *
 * A job can be used individually:
 *
 * $job = new SomeReasonJob();
 * $job->config('some_config') = 'foo';
 * $job->run();
 * echo $job->get_report();
 *
 * More usefully, jobs might be used in a stack, where one job can use the result of other jobs (or job stacks).
 *
 * A stack by default pushes failed jobs to the end and retries them. This reduces the importance of order and
 * might let you stack interdependent jobs in ways that make the writing of certain scripts easier.
 *
 * $job_stack = new ReasonJobStack();
 * $job = new SomeReasonJob();
 * $job->config('some_config') = 'foo';
 * $job_stack->add_job($job);
 * $job2 = new SomeOtherReasonJob();
 * $job2->config('other_job_id') = $job->id();
 * $job_stack->add_job($job2);
 * $job_stack->run();
 * var_dump ($job_stack->get_report());
 *
 * A ReasonJobStack is just another ReasonJob, so you can nest stacks. You can configure a stack by setting
 * retry to true if you want that stack to push failed jobs to the end
 *
 * Any job can call the method block_jobs() to stop any instance of ReasonJob from running jobs.
 * 
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');

/**
 * A Reason Job encapsulates some task.
 *
 * Every job must define a method run_job, which performs the job and returns a boolean value indicating if the job is complete.
 *
 * A job may set a result value and should set a report.
 *
 * - set_result($result) optional mechanism to set a result - useful when setting up job sets that depend on the result of other jobs.
 * - set_report($string) html which reports on the outcome of the job.
 *
 * A job which depends on some other job can ask for the result of another job using this method:
 *
 * - get_result($job_key) to retrieve the result of a job.
 *
 * Outside the class (or possibly inside a job) you may want to use these methods:
 *
 * - get_report()
 *
 * @todo implement job_unique_names? *
 * @todo allow config value to be the result of a ReasonJob
 *
 * @author Nathan White
 */
abstract class ReasonJob implements BasicReasonJob
{
	/**
	 * We store an array of all results statically so any job has the results of all jobs available.
	 */
	static $results;
	
	/**
	 * This is a big stick - use it sparingly - if set to true any subsequent jobs do not run_job and return false. 
	 */
	static $blocked;
	
	/**
	 * @var array key / value pairs of configuration options for the job - get or set via config($key, $value = NULL) method
	 */
	protected $config;
	
	/**
	 * @var array names the keys that must be set in config in order for the job to run
	 */
	protected $required;
	
	/**
	 * @var string plain text or html describing what happened
	 */
	private $report;
	
	/**
	 * @var string auto-generated guid for the job
	 */
	private $id;

	
	/**
	 * Extend if you wish to add custom checks for a job.
	 *
	 * @return boolean
	 */
	public function configured()
	{
		return true;
	}

	/**
	 * Run
	 *
	 * @return boolean
	 */
	public final function run()
	{
		return (!$this->_blocked() && $this->_configured() && $this->configured()) ? $this->run_job() : false;
	}
	
	/**
	 * Get the id for the job.
	 */
	public final function id()
	{
		if (!isset($this->id))
		{
			$this->id = $this->construct_job_id();
		}
		return $this->id;
	}
	
	public final function get_report()
	{
		return $this->report;
	}
	
	public final function get_result($id = NULL)
	{
		if (is_null($id)) $id = $this->id();
		return (isset(self::$results[$id])) ? self::$results[$id] : NULL;
	}
	
	/**
	 * Set or get configuration paramaters for the controller.
	 *
	 * @param string configuration key
	 * @param mixed configuration value - stored for the provided key.
	 * @return mixed value for provided key or NULL if it is not set.
	 */
	public final function config($key, $value = NULL)
	{
		if (isset($value)) $this->config[$key] = $value;
		if (isset($this->config[$key])) return $this->config[$key];
		return NULL;
	}

	/**
	 * Set a report for this job.
	 *
	 * @param report string
	 * @return void
	 */
	protected final function set_report($report)
	{
		$this->report = $report;
	}

	/**
	 * Set a result for this job.
	 *
	 * @param result mixed
	 * @return void
	 */
	protected final function set_result($result)
	{
		// self or static?
		$id = $this->id();
		$results = self::$results;
		$results[$id] = $result;
	}

	/**
	 * If called, this job and any other will be blocked from running.
	 *
	 * @return void
	 */
	protected final function block_jobs()
	{
		self::$blocked = true;
	}
	
	/**
	 * Makes sure required items are defined.
	 *
	 * @return boolean
	 */
	private final function _configured()
	{
		if (is_array($this->required))
		{
			foreach ($this->required as $value)
			{
				if ($this->config($value) == NULL)
				{
					$this->set_report('Job not properly configured - ' . $value . ' is required.');
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * Has some job blocked jobs?
	 *
	 * @return boolean
	 */
	private final function _blocked()
	{
		if (self::$blocked == true)
		{
			$this->set_report('Job is blocked');
			return true;
		}
		else return false;
	}
	
	/**
	 * Return a unique id for this job.
	 *
	 * @return string unique id
	 */
	private final function construct_job_id()
	{
		return uniqid('', true);
	}
}

/**
 * A ReasonJob that assembles and run a set of ReasonJobs.
 *
 * ReasonJobStack processes an arbitrary stack of ReasonJobs.
 *
 * - builds a combined HTML report
 * - will optionally recurse to complete jobs
 * - a job can be set to blocking - if it cannot complete it stops processing of the stack
 *
 * Supports a config param "retry" - if this is true:
 *
 * - incomplete jobs get pushed to the end of the stack
 * - if a job returns false twice with no successful jobs inbetween we stop processing
 *
 * @author Nathan White
 */ 
class ReasonJobStack extends ReasonJob
{
	/**
	 * @var array stack of ReasonJob objects.
	 */
	private $job_queue = array();
	 
	/**
	 * @var boolean should we retry incomplete jobs?
	 */
	protected $config = array('retry' => true);

	/**
	 * Run a stack of jobs - return true if all jobs completed.
	 */
	function run_job()
	{
		$report = array();
		$counts = array( 'fail' => 0,
						 'success' => 0,
						 'fail_index' => 0 );
						
		while (!empty($this->job_queue) && ($counts['fail_index'] < count($this->job_queue)))
		{
			$job = reset($this->job_queue);
			$job_id = $job->id();
			unset($this->job_queue[$job->id()]);
			$complete = $job->run();
			if (isset($complete) && $complete == false)
			{
				$counts['fail_index']++;
				$counts['fail']++;
				if ($this->config('retry') == true) $this->add_job($job);
			}
			else
			{
				$counts['fail_index'] = 0;
				$counts['success']++;	
			}
			if ($job_report = $job->get_report())
			{
				$attempt_num[$job_id] = (isset($attempt_num[$job_id])) ? ($attempt_num[$job_id] + 1) : 1;
				$job_class = get_class($job);
				$attempt_str = ($attempt_num[$job_id] > 1) ? '<em> (attempt ' . $attempt_num[$job_id] . ')</em>' : '';
				$report[] = '<span><strong>' . $job_class . $attempt_str . ':</strong></span> ' . $job_report;
			}
		}
		
		if (!empty($report))
		{
			// lets spit out an html list with each list item being a report.
			//$report[] = 'Summary:
			$this->set_report('<ul><li>' . implode("</li><li>", $report) . '</li></ul>');
		}
		return ($counts['fail_index'] == 0);
	}
	
	/**
	 * Add a ReasonJob to a queue.
	 *
	 * @param object ReasonJob
	 * @return id of the job added
	 */
	function add_job($job)
	{
		$id = $job->id();
		if (isset($this->job_queue[$id]))
		{
			trigger_error('You cannot add job id ' . $id . ' it is already in the queue', FATAL);
			return false;
		}
		$this->job_queue[$id] = $job;
		return $id;
	}
}

/**
 * Anything using this should implement run_job
 */
interface BasicReasonJob
{
	function run_job();
}