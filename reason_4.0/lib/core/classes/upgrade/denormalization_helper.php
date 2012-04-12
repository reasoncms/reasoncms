<?php
include_once('reason_header.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/amputee_fixer.php');

class denormalizationUpgradeHelper
{
	protected $destination_table;
	protected $source_tables = array();
	protected $type_unique_name;
	protected $user_id;
	
	public function destination_table($table = null)
	{
		if(!empty($table))
			$this->destination_table = $table;
		return $this->destination_table;
	}
	
	public function source_tables($tables = null)
	{
		if(!empty($tables))
			$this->source_tables = $tables;
		return $this->source_tables;
	}
	
	public function type_unique_name($uname = null)
	{
		if(!empty($uname))
			$this->type_unique_name = $uname;
		return $this->type_unique_name;
	}
	
	public function type_id()
	{
		$uname = $this->type_unique_name();
		if(empty($uname))
		{
			trigger_error('type_id() not available until type_unique_name() has been set');
			return;
		}
		return id_of($uname);
	}
	
	public function user_id($user_id = null)
	{
		if(!empty($user_id))
			$this->user_id = $user_id;
		return $this->user_id;
	}
	
	public function fully_set_up()
	{
		if($this->destination_table() && $this->source_tables() && $this->type_unique_name() && $this->type_id() && $this->user_id())
			return true;
		trigger_error('The denormaliation Helper is not fully set up. It needs destination_table, source_tables, valid type_unique_name, and user id.');
		return false;
	}
	
	public function test()
	{
		if(!$this->fully_set_up())
		{
			return '<p>There is a programming error that prevents this denormalization attempt from working. Please check with a friendly geek and try again once fixes have been made.</p>';
		}
		
		$ret = '';
		
		$tables = get_entity_tables_by_type( $this->type_id(), false );
		
		if(!in_array($this->destination_table(),$tables))
		{
			$ret .= '<p>Will add the '.htmlspecialchars($this->destination_table()).' table.</p>';
		}
		
		$to_move = array_intersect($this->source_tables(), $tables);
		
		if(!empty($to_move))
		{
			$ret .= '<p>Will move the '.htmlspecialchars($this->type_unique_name()).' contents of these tables into the '.htmlspecialchars($this->destination_table()).' table:</p>';
			$ret .= '<ul>';
			foreach($to_move as $table)
			{
				$ret .= '<li>'.htmlspecialchars($table).'</li>';
			}
			$ret .= '</ul>';
			$ret .= '<h4>Notes</h4><ol><li>It is especially important to make a backup of your Reason instance before running this script. An unexpected server failure during this upgrade step will result in a corrupted Reason database.</li>
			<li>We recommend that you turn off your administrative interface (by changing DISABLE_REASON_ADMINISTRATIVE_INTERFACE to true in settings/reason_settings.php) before running this script.</li>
			<li>Any database queries for '.htmlspecialchars($this->type_unique_name()).' in local Reason modifications that reference the tables being moved will need to be updated to either not reference a table (preferred) or to reference the '.htmlspecialchars($this->destination_table()).' table instead.</li></ol>';
		}
		else
		{
			$ret .= '<p>The table move has been run.</p>';
		}
		return $ret;
	}
	
	public function run()
	{
		if(!$this->fully_set_up())
		{
			return '<p>There is a programming error that prevents this denormalization attempt from working. Please check with a friendly geek and try again once fixes have been made.</p>';
		}
		$ret = '';
		
		$tables = get_entity_tables_by_type( $this->type_id(), false );
		
		if(!in_array($this->destination_table(),$tables))
		{
			$ret .= $this->_add_table($this->destination_table());
		}
		
		$to_move = array_intersect($this->source_tables(), $tables);
		
		if(!empty($to_move))
		{
			foreach($to_move as $table)
			{
				$ret .= $this->_move_table_fields($table);
			}
		}
		
		if(empty($ret))
		{
			$ret .= '<p>This upgrade has already been run. There is nothing to do.</p>';
		}
		return $ret;
		
	}
	
	protected function _add_table($table_name)
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.addslashes($table_name).'"');
		$es->set_num(1);
		$results = $es->run_one();
		
		if(empty($results))
		{
			create_reason_table($table_name, $this->type_unique_name(), $this->user_id());
			$ret = '<p>Created the '.htmlspecialchars($table_name).' table.</p>';
		}
		else
		{
			$table_entity = current($results);
			create_relationship( $this->type_id(), $table_entity->id(), relationship_id_of('type_to_table'));
			
			$ret = '<p>Added the '.htmlspecialchars($table_name).' table to the '.htmlspecialchars($this->type_unique_name()).' type.</p>'."\n";
		}
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees($this->type_id());
		
		return $ret;
	}
	
	protected function _move_table_fields($from)
	{
		$ret = '';
		set_time_limit(3600);
		
		$success = reason_move_table_fields( $this->type_id(), $from, $this->destination_table(), $this->user_id() );
		if($success)
			$ret .= '<p>Successfully moved '.htmlspecialchars($this->type_unique_name()).' content in the '.htmlspecialchars($from).' table to the '.htmlspecialchars($this->destination_table()).' table.</p>';
		else
		{
			$ret = '<p>Unable to move the content from the '.htmlspecialchars($from).' table to the '.htmlspecialchars($this->destination_table()).' table. Please look in your PHP error logs for information about the cause of this error. This error may cause problems with Reason modules or queries; we recommend restoring your latest backup, identifying and fixing the cause on a testing server, and re-running this upgrade.</p>';
			$err = error_get_last();
			if(!empty($err))
				$ret .= '<p>Last error: "'.htmlspecialchars($err['message']).'"</p>';
		}
		return $ret;
	}
}
?>