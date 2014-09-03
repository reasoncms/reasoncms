<?php
/**
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/course_functions.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

ini_set('display_errors', 'stdout');
ini_set('error_reporting', E_ALL);

$import = new CourseImportEngine();
$import->run();

class CourseImportEngine
{
	protected $errors = array();
	
	public function run()
	{	
		connectDB(REASON_DB);
		
		$this->disable_output_buffering();
		echo "<pre>Running\n";
		//$this->delete_all_course_entities();
		
		//foreach ($this->get_section_org_ids() as $org_id)
		//{
			if ($raw_data = $this->get_course_template_data())
			{
				if ($mapped_data = $this->map_course_template_data($raw_data))
				{
					$this->build_course_template_entities($mapped_data);
				}
				else
				{
					$this->errors[] = 'Course template data failed to map for '.$org_id.'.';	
				}
			}
			else
			{
				$this->errors[] = 'No course template data received for '.$org_id.'.';
			}
		//}
		
		foreach ($this->get_section_org_ids() as $org_id)
		{

			if ($raw_data = $this->get_course_section_data($org_id))
			{
				if ($mapped_data = $this->map_course_section_data($raw_data))
				{
					$this->build_course_section_entities($mapped_data);
				}
				else
				{
					$this->errors[] = 'Course section data failed to map for '.$org_id.'.';
				}
			}
			else
			{
				$this->errors[] = 'No course section data received for '.$org_id.'.';	
			}
		}
		
		echo join("\n", $this->errors);
		echo "Import Complete.\n";
	}

	protected function get_section_parent($parent_template_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('course_template_type'));
		$es->add_relation('sourced_id = "'.$parent_template_id.'"');
		if ($result = $es->run_one())
		{
			return reset($result);	
		}
		return false;
	}
	
	protected function link_section_to_parent($section)
	{
		//echo round(memory_get_usage()/1024,2)."K at point A\n"; 

		if ($template = $this->get_section_parent($section->get_value('parent_template_id')))
		{
		//	echo round(memory_get_usage()/1024,2)."K at point B\n"; 

			if (!$parents = $section->get_right_relationship('course_template_to_course_section'))
			{
				return create_relationship( $template->id(), $section->id(), relationship_id_of('course_template_to_course_section'),false,false);
			}
			else if (is_array($parents))
			{
				$current_template = reset($parents);
		//		echo round(memory_get_usage()/1024,2)."K at point C\n"; 
				// verify that we have the correct parent, and fix if not.	
				if ($current_template->get_value('sourced_id') == $template->get_value('sourced_id'))
				{
					return true;	
				}
				else
				{
					//$this->errors[] = 'Incorrect template attached to '.$section->get_value('name');	
					echo 'Incorrect template attached to '.$section->get_value('name');	
				}
			}
			else
			{
				//$this->errors[] = 'Non-array '.$parents.' returned from get_right_relationship';
				echo 'Non-array '.$parents.' returned from get_right_relationship';
			}
		}
		else
		{
			//$this->errors[] = 'No template found for '.$section->get_value('name');
			echo 'No template found for '.$section->get_value('name');
			return false;
		}
	}
	
	protected function build_course_template_entities($data)
	{
		echo "Building entities\n";
		foreach ($data as $key => $row)
		{
			$name = sprintf('%s %s %s', $row['org_id'], $row['course_number'], $row['title']);
			//echo 'Adding '.$name ."\n"; continue;
			$es = new entity_selector();
			$es->add_type(id_of('course_template_type'));
			$es->add_relation('sourced_id = "'.$row['sourced_id'].'"');
			if ($result = $es->run_one())
			{
				$course = reset($result);
				// Find all the values that correspond to the data we're importing
				$values = array_intersect_key($course->get_values(), $row);
				if ($values != $row)
				{
					echo 'Updating '.$name ."\n";
					reason_update_entity( $course->id(), get_user_id('causal_agent'), $row, false);
				}
			}
			else
			{
				echo 'Adding '.$name ."\n";
				reason_create_entity( id_of('academic_catalog_site'), id_of('course_template_type'), get_user_id('causal_agent'), $name, $row);
			}
		}
	}

	protected function build_course_section_entities($data)
	{
		echo "Building section entities\n";
		foreach ($data as $key => $row)
		{
			//echo round(memory_get_usage()/1024,2)."K at point E\n"; 
			$es = new entity_selector();
			$es->add_type(id_of('course_section_type'));
			$name = sprintf('%s %s %s', $row['course_number'], $row['academic_session'], $row['title'] );
			$es->relations = array();
			$es->add_relation('sourced_id = "'.$row['sourced_id'].'"');
			if ($result = $es->run_one())
			{
				$section = reset($result);
				// Find all the values that correspond to the data we're importing
				$values = array_intersect_key($section->get_values(), $row);
				if ($values != $row)
				{
					echo 'Updating: '.$name ."\n";
					reason_update_entity( $section->id(), get_user_id('causal_agent'), $row, false);
				}
				else
				{
					echo 'Unchanged: '.$name ."\n";	
				}
			}
			else
			{
				if ($this->get_section_parent($row['parent_template_id']))
				{
					echo 'Adding: '.$name ."\n";
					$id = reason_create_entity( id_of('academic_catalog_site'), id_of('course_section_type'), get_user_id('causal_agent'), $name, $row);
					$section = new entity($id);
				}
				else
				{
					echo 'No course template found; skipping '.$name ."\n";
					continue;
				}
			}
			
			if (!empty($section))
				$this->link_section_to_parent($section);
			//echo round(memory_get_usage()/1024,2)."K at point D\n"; 
		}
	}
	
	protected function map_course_template_data($data)
	{
		echo "map_course_template_data\n";
		$map = array(
			'course_number' => 'SEC_COURSE_NO',
			'org_id' => 'SEC_SUBJECT',
			'title' => 'title',
			'short_description' => null,
			'long_description' => 'description',
			'credits' => 'SEC_MAX_CRED',
			'list_of_prerequisites' => 'prereq',
			'status' => 'Active',
			'data_source' => 'Colleague',
			'sourced_id' => 'COURSES_ID',
			);
		
		foreach($data as $row)
		{
			unset($mapped_row);
			foreach ($map as $key => $mapkey)
			{
				if ($mapkey)
				{
					if (method_exists($this, 'template_map_'.$mapkey))
					{
						$method = 'template_map_'.$mapkey;
						$mapped_row[$key] = $this->$method($row);
					}
					else if (array_key_exists($mapkey, $row))
					{
						$mapped_row[$key] = $row[$mapkey];	
					}
					else
					{
						$mapped_row[$key] = $mapkey;	
					}
				}
			}
			if (isset($mapped_row))
				$mapped_rows[] = $mapped_row;
			else
				$this->errors[] = 'Failed to map course template row';
		}
		if (isset($mapped_rows))
			return $mapped_rows;
		else
		{
			$this->errors[] = 'Failed to map any course template rows';
			return false;
		}
	}

	protected function map_course_section_data($data)
	{
		echo "map_course_template_data\n";
		$map = array(
			'course_number' => 'SEC_NAME',
			'org_id' => 'SEC_SUBJECT',
			'title' => 'title',
			'short_description' => null,
			'long_description' => 'description',
			'credits' => 'credits',
			'academic_session' => 'SEC_TERM',
			'timeframe_begin' => 'SEC_START_DATE',
			'timeframe_end' => 'SEC_END_DATE',
			'location' => 'location',
			'meeting' => 'meeting',
			'notes' => null,
			'status' => 'Active',
			'data_source' => 'Colleague',
			'sourced_id' => 'COURSE_SECTIONS_ID',
			'parent_template_id' => 'SEC_COURSE',
			);
		
		foreach($data as $row)
		{
			unset($mapped_row);
			foreach ($map as $key => $mapkey)
			{
				if ($mapkey)
				{
					if (method_exists($this, 'section_map_'.$mapkey))
					{
						$method = 'section_map_'.$mapkey;
						$mapped_row[$key] = $this->$method($row);
					}
					else if (array_key_exists($mapkey, $row))
					{
						$mapped_row[$key] = $row[$mapkey];	
					}
					else
					{
						$mapped_row[$key] = $mapkey;	
					}
				}
			}
			if (isset($mapped_row))
				$mapped_rows[] = $mapped_row;
			else
			{
				//$this->errors[] = 'Failed to map course section row';
				echo 'Failed to map course section row';
			}
		}
		if (isset($mapped_rows))
			return $mapped_rows;
		else
		{
			$this->errors[] = 'Failed to map any course section rows';
			return false;
		}
	}
	
	protected function get_course_template_data($org_id = null)
	{
		echo "get_course_template_data $org_id\n";
		$restore_conn = get_current_db_connection_name();
		connectDB('reg_catalog_new');	
		mysql_set_charset('utf8');
		$query = 'SELECT * FROM IDM_CRS WHERE CRS_END_DATE IS NULL OR CRS_END_DATE > NOW() ORDER BY CRS_NAME';
		if ($result = mysql_query($query))
		{
			while ($row = mysql_fetch_assoc($result))
			{
				if (strpos($row['CRS_NAME'], 'OCP') === 0) continue;
				if (strpos($row['CRS_NAME'], 'NORW') === 0) continue;
				if (substr($row['CRS_NAME'], -3) == 'SAT') continue;
				if (substr($row['CRS_NAME'], -2) == 'WL') continue;
				if (substr($row['CRS_NAME'], -2) == 'IB') continue;
				if (substr($row['CRS_NAME'], -2) == 'CC') continue;
				if (substr($row['CRS_NAME'], -2) == 'MP') continue;
				if (substr($row['CRS_NAME'], -2) == 'AP') continue;
				if (substr($row['CRS_NAME'], -1) == 'S') continue;
				if (substr($row['CRS_NAME'], -1) == 'L') continue;

				$found = false;
				$coursetableyear = 2014;
				while ($coursetableyear > 2009)
				{
					$coursetable = 'course'.$coursetableyear;
					$query = 'SELECT IDM_COURSE.*, description, title, prereq FROM IDM_COURSE 
						JOIN '.$coursetable.' 
						ON '.$coursetable.'.course_id = IDM_COURSE.SEC_COURSE 
						AND ('.$coursetable.'.match_title IS NULL 
						OR '.$coursetable.'.match_title = IDM_COURSE.SEC_SHORT_TITLE)
						WHERE SEC_COURSE = "'.$row['COURSES_ID'].'"';
					if ($result2 = mysql_query($query))
					{
						if (mysql_num_rows($result2) == 1)
						{
							$row = array_merge($row, mysql_fetch_assoc($result2));
							$found = true;
						}
						else if (mysql_num_rows($result2) > 1)
						{
							$row = array_merge($row, mysql_fetch_assoc($result2));
							$found = true;
						}
						else
						{
							//echo "No data for $row[COURSES_ID] in $coursetable\n";
							$coursetableyear--;
							continue;
						}
						$data[] = $row;
						break;
					}
					else
					{
						$this->errors[] = mysql_error();
					}
				}
				if (!$found) echo "No data for $row[CRS_NAME] in catalog\n";
			}
		} 
		else
		{
			$this->errors[] = mysql_error();
		}
		connectDB($restore_conn);
		
		if (isset($data)) return $data;
	}

	protected function get_course_section_data($org_id = null)
	{
		echo "get_course_section_data $org_id\n";
		$data = array();
		$coursetable = 'course2014';
		$restore_conn = get_current_db_connection_name();
		connectDB('reg_catalog_new');	
		mysql_set_charset('utf8');
		$found = false;
		$coursetableyear = 2014;
		$org_id_limit = ($org_id) ? ' AND SEC_SUBJECT="'.$org_id.'" ' : '';
		while ($coursetableyear > 2009)
		{
			$coursetable = 'course'.$coursetableyear;
			$query = 'SELECT s.*, description, title FROM IDM_CRS c, IDM_COURSE s
				JOIN '.$coursetable.' 
				ON '.$coursetable.'.course_id = s.SEC_COURSE 
				AND ('.$coursetable.'.match_title IS NULL 
				OR '.$coursetable.'.match_title = s.SEC_SHORT_TITLE)
				WHERE s.SEC_COURSE = c.COURSES_ID AND
				(CRS_END_DATE IS NULL OR CRS_END_DATE > NOW()) 
				AND SEC_START_DATE > "2009-09-01 00:00:00" '. $org_id_limit .'
				ORDER BY SEC_NAME';

			if ($result = mysql_query($query))
			{
				while ($row = mysql_fetch_assoc($result))
				{
					if (isset($row['SEC_SUBJECT']) && empty($data[$row['COURSE_SECTIONS_ID']]))
					{
						if ($row['SEC_SUBJECT'] == 'OCP' || $row['SEC_SUBJECT'] == 'NORW') continue;
						if (strpos($row['SEC_NO'], 'WL') !== false) continue;
						//if (strpos($row['SEC_TERM'], 'SU') !== false) continue;
						
						$data[$row['COURSE_SECTIONS_ID']] = $row;
					}
				}
			}
			else
			{
				$this->errors[] = mysql_error();
			}
			$coursetableyear--;
		}
		connectDB($restore_conn);
		return $data;
	}

	protected function section_map_location($row)
	{
		$location = array();
		if ($times = explode('|', $row['XSEC_CC_MEETING_TIMES_SV']))
		{
			foreach ($times as $time)
			{
				if (preg_match('/(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/', $time, $matches))
				{
					$location[] = $matches[1] . ' '. $matches[2];
				}
			}
		}
		return join('|', $location);
	}

	protected function section_map_meeting($row)
	{
		$meeting = array();
		if ($times = explode('|', $row['XSEC_CC_MEETING_TIMES_SV']))
		{
			foreach ($times as $time)
			{
				if (preg_match('/(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)/', $time, $matches))
				{
					$meeting[] = $matches[3] . ' '. $matches[4] . ' '. $matches[5];
				}
			}
		}
		return join('|', $meeting);
	}

	/**
	  * If both min and max credits are set, return "MIN-MAX" -- otherwise, return
	  * whichever one is set, or nothing.
	  */
	protected function section_map_credits($row)
	{
		$credits = array();
		if ($row['SEC_MIN_CRED']) $credits[] = $row['SEC_MIN_CRED'];
		if ($row['SEC_MAX_CRED']) $credits[] = $row['SEC_MAX_CRED'];
		return join('-', $credits);
	}
	
	protected function delete_all_course_entities()
	{
		$user = get_user_id('causal_agent');
		$es = new entity_selector();
		$es->add_type(id_of('course_template_type'));
		if ($result = $es->get_ids())
		{
			foreach ($result as $id)
			{
				reason_expunge_entity($id, $user);
			}
		}
	}
	
	protected function delete_all_section_entities()
	{
		$user = get_user_id('causal_agent');
		$es = new entity_selector();
		$es->add_type(id_of('course_section_type'));
		if ($result = $es->get_ids())
		{
			foreach ($result as $id)
			{
				reason_expunge_entity($id, $user);
			}
		}
	}
	
	protected function get_template_org_ids()
	{
		return $this->get_section_org_ids();
	}

	protected function get_section_org_ids()
	{
		$org_ids = array();
		$q = 'SELECT DISTINCT SEC_SUBJECT FROM IDM_COURSE ORDER BY SEC_SUBJECT';
		connectDB('reg_catalog_new');	
		if ($result = mysql_query($q))
		{
			while($row = mysql_fetch_assoc($result))
				$org_ids[] = $row['SEC_SUBJECT'];
		}
		connectDB(REASON_DB);
		return $org_ids;
	}
	
	function disable_output_buffering()
	{
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);
		@ini_set('implicit_flush', 1);
		for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
		ob_implicit_flush(1);
	}
	
}
