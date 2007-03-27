<?php
include_once('reason_header.php');
class AmputeeFixer
{
	var $queries = array();
	var $stats = array();
	function fix_amputees($type_id = 0)
	{
		set_time_limit(240);
	
		$types = get_entities_by_type_name('type');
		
		foreach( $types as $type )
		{
			if($type_id == 0 || $type_id == $type['id'])
			{
				$tables = get_entity_tables_by_type( $type['id'] );
				foreach( $tables as $table )
				{
					$q = "SELECT e.*,type.name as type_name FROM entity AS e LEFT JOIN $table AS t ON e.id = t.id, entity AS type WHERE e.type = ".$type['id']." AND t.id IS NULL AND e.type = type.id";
					$r = db_query( $q, 'Unable to grab amputees.' );
					while( $row = mysql_fetch_array( $r, MYSQL_ASSOC )) 
					{
						$q = 'INSERT INTO '.$table.' (id) VALUES ('.$row['id'].')';
						$this->queries[] = $q;
						if(empty($this->stats[$type['name']]))
						{
							$this->stats[$type['name']] = array();
						}
						if(empty($this->stats[$type['name']][$table]))
						{
							$this->stats[$type['name']][$table] = 0;
						}
						$this->stats[$type['name']][$table]++;
						db_query( $q, 'Unable to add prosthetic record.' );
					}
					mysql_free_result( $r );
				}
			}
		}
	}
	function get_queries()
	{
		return $this->queries;
	}
	function get_stats()
	{
		return $this->stats;
	}
	function generate_report()
	{
		if(!empty($this->queries))
		{
			echo '<h2>Amputees Fixed</h2>';
			foreach($this->stats as $type=>$tables)
			{
				echo '<h3>'.$type.'</h3>';
				echo '<ul>';
				foreach($tables as $table=>$count)
				{
					echo '<li><strong>'.$table.' table:</strong> '.$count.' records created</li>';
				}
				echo '</ul>';
			}
			echo '<h2>Queries Performed</h2><ul><li>';
			echo implode('</li><li>',$this->queries);
			echo '</li></ul>';
		}
		else
		{
			echo '<h2>Congratulations</h2><p>No amputees found.</p>';
		}
	}
}
?>