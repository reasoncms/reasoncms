<?php

include_once('paths.php');
include_once(SETTINGS_INC.'reason_settings.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once(CARL_UTIL_INC.'db/connectDB.php');

header('Content-Type:text/plain;');

function sprint_r_with_type($input)
{
	return '('.gettype($input).') '.sprint_r($input);
}

class uncastable
{
}
class castable
{
	function __toString()
	{
		return 'castable';
	}
}

$uncastable = new uncastable();
$castable = new castable();

connectDB(REASON_DB);

$dbs = new DBSelector();

$condition_tests = array(
	array(
		'name' => 'Simple equality',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','=','value'),
		'output' => "table.field = 'value'",
	),
	array(
		'name' => 'NULL-safe equality',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','<=>',NULL),
		'output' => "table.field <=> NULL",
	),
	array(
		'name' => 'NULL-safe equality with string',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','<=>','value1'),
		'output' => "table.field <=> 'value1'",
	),
	array(
		'name' => 'Escape',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','=','\\\'"'),
		'output' => "table.field = '\\\\\'\\\"'",
	),
	array(
		'name' => 'Array equality',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','=',array('value1',NULL,1)),
		'output' => "(table.field = 'value1' OR table.field IS NULL OR table.field = 1)",
	),
	array(
		'name' => 'Multiple tables equality',
		'function' => array($dbs, 'get_condition'),
		'params' => array(array('table.field1','table.field2'),'=',5),
		'output' => "(table.field1 = 5 OR table.field2 = 5)",
	),
	array(
		'name' => 'Simple inequality',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','!=','value'),
		'output' => "table.field <> 'value'",
	),
	array(
		'name' => 'Inequality array',
		'function' => array($dbs, 'get_condition'),
		'params' => array(array('table.field1','table.field2'),'<>',array('value1','value2')),
		'output' => "(table.field1 <> 'value1' AND table.field2 <> 'value1' AND table.field1 <> 'value2' AND table.field2 <> 'value2')",
	),
	array(
		'name' => 'Simple greater than, integer',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','>',0),
		'output' => "table.field > 0",
	),
	array(
		'name' => 'IN with mixed values',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','IN',array(1,'string')),
		'output' => "table.field IN (1,'string')",
	),
	array(
		'name' => 'IN with nonarray',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','IN',1),
		'output' => "table.field IN (1)",
	),
	array(
		'name' => 'LIKE with AND',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','LIKE',array('value1','value2'),'AND'),
		'output' => "(table.field LIKE 'value1' AND table.field LIKE 'value2')",
	),
	array(
		'name' => 'NOT LIKE with array',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','NOT LIKE',array('%value1%','%value2%')),
		'output' => "(table.field NOT LIKE '%value1%' AND table.field NOT LIKE '%value2%')",
	),
	array(
		'name' => 'NOT LIKE with array, OR',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','NOT LIKE',array('value1','value2'), 'OR'),
		'output' => "(table.field NOT LIKE 'value1' OR table.field NOT LIKE 'value2')",
	),
	array(
		'name' => 'Empty field',
		'function' => array($dbs, 'get_condition'),
		'params' => array('','=','value'),
		'output' => NULL,
	),
	array(
		'name' => 'Invalid operator',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','INVALID_OPERATOR','value'),
		'output' => NULL,
	),
	array(
		'name' => 'Invalid boolean',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','=','value','INVALID_BOOLEAN'),
		'output' => NULL,
	),
	array(
		'name' => 'NOT IN with OR',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','NOT IN',array('value1','value2'),'OR'),
		'output' => NULL,
	),
	array(
		'name' => 'IN with NULL',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','IN',array('value1',NULL)),
		'output' => NULL,
	),
	array(
		'name' => 'IN with empty array',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','IN',array()),
		'output' => NULL,
	),
	array(
		'name' => 'IN with AND',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','IN',array('value1'),'AND'),
		'output' => NULL,
	),
	array(
		'name' => 'AND tables',
		'function' => array($dbs, 'get_condition'),
		'params' => array(array('table1','table2'),'=','value', 'AND'),
		'output' => "(table1 = 'value' AND table2 = 'value')",
	),
	array(
		'name' => 'Uncastable object',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','=',$uncastable),
		'output' => NULL,
	),
	array(
		'name' => 'Castable object',
		'function' => array($dbs, 'get_condition'),
		'params' => array('table.field','=',$castable),
		'output' => "table.field = 'castable'",
	),
	array(
		'name' => 'Simple condition set',
		'function' => array($dbs, 'get_condition_set'),
		'params' => array(array('table1','=','value1'), array('table2', '!=', 'value2'), array('table3', '<', 7)),
		'output' => "(table1 = 'value1' OR table2 <> 'value2' OR table3 < 7)",
	),
	array(
		'name' => 'Complex condition set',
		'function' => array($dbs, 'get_condition_set'),
		'params' => array(
			array( array('table1','table2'), '=', array('value1','value2') ),
			array( 'table2', '!=', array('value2',NULL) ),
			array( 'table3', '<', 7),
			array( array('table4','table5'), '>', 20, 'AND',)),
		'output' => "((table1 = 'value1' OR table2 = 'value1' OR table1 = 'value2' OR table2 = 'value2') OR (table2 <> 'value2' AND table2 IS NOT NULL) OR table3 < 7 OR (table4 > 20 AND table5 > 20))",
	),
	array(
		'name' => 'Condition set error',
		'function' => array($dbs, 'get_condition_set'),
		'params' => array(array('table1','BAD','value1'), array('table2', '!=', 'value2')),
		'output' => NULL,
	),
);

$passes = 0;
$fails = 0;
foreach($condition_tests as $test)
{
	$pass = false;
	echo $test['name']."\n";
	$output = call_user_func_array($test['function'],$test['params']);
	if($output === $test['output'])
	{
		$pass = true;
		$passes++;
		echo 'PASS';
	}
	else
	{
		$fails++;
		echo 'FAIL';
	}
	echo "\n".'Output: '.sprint_r_with_type($output)."\n";
	if(!$pass)
	{
		echo 'Expected: '.sprint_r_with_type($test['output'])."\n";
	}
	echo "\n";
}
echo 'Passes: '.$passes."\n";
echo 'Fails: '.$fails."\n";