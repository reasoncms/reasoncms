<?
// Oracle_DB
// General utility classes for interacting with Oracle databases.  
// Requires that /usr/local/oracle/OraHome1/network/admin/tnsnames.ora contain connection
// information for the database name passed in $db

class oracle_db {

var $conn;
var $error;
var $last_stmt;

function oracle_db($db, $username, $password) {
	$this->dbconnect($db, $username, $password);
	register_shutdown_function(array(&$this, "dbdisconnect")); 
}

function is_connected() {
	if ($this->conn) { return true; }
	else { return false; }
}

function dbconnect($db, $username, $password) {
	return true;

}

function dbdisconnect() {
	OCILogoff($this->conn);
}

function dbquery($sql) {
	$this->last_stmt = OCIParse($this->conn, $sql);
	if (!$this->error = OCIerror($this->conn)) {
		OCIExecute($this->last_stmt);
		if ($this->error = OCIerror($this->last_stmt)) {
			OCIFreeStatement($this->last_stmt);
			return false;
		} else {
			return true;
		}
	} else {
		OCIFreeStatement($this->last_stmt);
		return false;
	}
}

function dbfetch() {
	$rows = OCIFetchStatement($this->last_stmt,$results);
	if (is_array($results)) {
		// Oracle returns results in a backwards sort of fashion.  This cleans them up.
		$return = array();
		foreach ($results as $col => $rows) {
			foreach($rows as $count => $val) {
				if (!isset($return[$count])) $return[$count] = array();
				$return[$count][$col] = $val;
			}
		}
		
	} else {
		$return = false;
	}
	OCIFreeStatement($this->last_stmt);
	return $return;
}
}

?>
