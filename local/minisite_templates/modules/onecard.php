<?php
// OneCard
// Utility class for interacting with the OneCard server.  Provides functions for querying and setting various values.

require_once ('oracle_db.php');

class onecard {

var $db;
var $carleton_prefix = '6394';  // not relevant for other campuses
var $termname = 'Carleton Meal Terms';  // The name of your meal terms, for getting term dates (not currently used)
var $transaction_user = '****';  // the CSGOLD user whose name transactions should be made under
var $transaction_location = '###';  // The ID of the CSGOLD location where transactions should be registered
var $import_file = 'web_import.mp'; // Name of import files created on the Transaction Processing Server

function onecard() {
	// Constructor; establish connection to database
	$db = '(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = YOUR.ORACLE.HOST) (PORT = 1521)) (CONNECT_DATA = (SID = ORCL)))';
	$this->db = new oracle_db($db, 'username', 'password');
}

function colleague_to_PIK($collid) {
	// Translate a Carleton Colleague ID into a CS Gold PIK -- not relevant for other campuses
	return sprintf('%d%09d', $this->carleton_prefix, $collid);
}

function PIK_to_patron_id($pik) {
	// Lookup the patron id associated with a given PIK
	// The patron id is the unchanging primary key for a patron which is used by the other methods in this class
	$query = "SELECT PATRONID FROM KEYMAPPINGINFO WHERE KEYVALUE = '$pik'";
	if ($this->db->dbquery($query)) {
		$result = $this->db->dbfetch();
		return $result[0]['PATRONID'];
	} else {
		return false;
	}	
}

function get_patron_info($id) {
	// Retrieve basic personal information about a patron
	$query = sprintf("SELECT * FROM PATRON_FLAT_VIEW  
		WHERE PATRONID = %d", $id);
	if ($this->db->dbquery($query)) {
		return $this->db->dbfetch();
	} else {
		return false;
	}
}

function set_patron_info($id, $data) {
	// Alter basic personal information about a patron
	if ($id && is_array($data))
	{
		foreach ($data as $key => $val)
		{
			$qvals[] = $key . "='" . addslashes($val) . "'";
		}
		$query = sprintf("UPDATE EXTENDEDPATRONINFOTAB SET %s 
			WHERE PATRONID = %d", join(',', $qvals), $id);
		if ($this->db->dbquery($query)) {
			return true;
		} else {
			return false;
		}
	}
}

function set_patron_name($id, $data) {
	// Alter name information about a patron
	if ($id && is_array($data))
	{
		foreach ($data as $key => $val)
		{
			$qvals[] = $key . "='" . addslashes($val) . "'";
		}
		$query = sprintf("UPDATE PATRONINFO SET %s 
			WHERE PATRONID = %d", join(',', $qvals), $id);
		if ($this->db->dbquery($query)) {
			return true;
		} else {
			return false;
		}
	}
}

function toggle_patron_active($id) {
	// Change an active patron to inactive or vice versa
	// Don't use -- may not be correct
	$query = sprintf('UPDATE PERS SET ACTIVE = DECODE(ACTIVE, 1, 0, 0, 1) 
		WHERE ID_PERS = %d', $id);
	return $this->db->dbquery($query);
}

function toggle_card_is_lost($id) {
	// Toggle the lost card flag on a patron's card
	if ($this->get_card_is_lost($id)) {
		$query = sprintf ('update keymappinginfo set cardislost = 0 
		where patronid = %d', $id);	
	} else {
		$query = sprintf ('update keymappinginfo set cardislost = 1 
		where mediatype <> -1 and patronid = %d', $id);	
	}
	return $this->db->dbquery($query);
}

function get_card_is_lost($id) {
	// Return the status of the lost card flag (true/false)
	$query = sprintf('SELECT cardislost FROM keymappinginfo
		WHERE patronid = %d AND cardislost = 1', $id);
	if ($this->db->dbquery($query)) {
		$result = $this->db->dbfetch();
		return (count($result) > 0);
	} else {
		return false;
	}
}

function set_patron_classification($id, $class) {
	// Set the classification field for a patron
	$query = sprintf('UPDATE PERS SET CLASSIFICATION = \'%s\'
		WHERE ID_PERS = %d', addslashes($class), $id);
	return $this->db->dbquery($query);
}

function add_to_patron_group($id, $group_id) {
	// Add a patron to a patron group
	if ($id && $group_id)
	{
		$query = sprintf("INSERT INTO PATRONGROUPS VALUES ( %d, %d, '', '', '', SYSDATE)",
			$group_id, $id);
		return $this->db->dbquery($query);
	}
	return false;
}

function delete_from_patron_group($id, $group_id) {
	// Remove a patron from a patron group
	if ($id && $group_id)
	{
		$query = sprintf('DELETE FROM PATRONGROUPS WHERE GROUPNUMBER = %d AND PATRONID = %d',
			$group_id, $id);
		return $this->db->dbquery($query);
	}
	return false;
}

function is_member_of_group($id, $group_id) {
	// Determine if the given user is a member of the given patron group	
	$query = sprintf("SELECT * FROM PATRONGROUPS 
		WHERE GROUPNUMBER = %d AND PATRONID = %d", $group_id, $id);
	if ($this->db->dbquery($query)) {
		$results = $this->db->dbfetch();
		return count($results);
	} else {
		return false;
	}	
}

function get_svc_balance($id) {
	// Return an array of current SVC balances for a patron
	$query = sprintf("SELECT * FROM PATRONSVCBALANCES_V b 
		WHERE PATRONID = %d", $id);
	if ($this->db->dbquery($query)) {
		return $this->db->dbfetch();
	} else {
		return false;
	}
}

function get_patron_mealplans($id) {
	// Return an array of data about each of a patron's mealplans
	$query = sprintf("SELECT DISTINCT p.*, i.*, u.DESCRIPTION AS MRD_DESC FROM PATRONMEALPLANS p, MEALPLANINFO i
		LEFT JOIN UILISTVALUES u ON u.FIELD = 'MEALS_REMAIN_DEF' AND i.MEALSREMAININGDEF = u.NUMCOPYOFVALUE
		WHERE p.PATRONID = %d
		AND p.MEALPLANDESIGNATION = i.MEALPLANDESIGNATION", $id);
	if ($this->db->dbquery($query)) {
		return $this->db->dbfetch();
	} else {
		return false;
	}
}

function get_patron_ledger($id, $start, $end) {
	// Return an array listing the SVC transactions posted to a patron's account between the given dates (in unixtime).
	$query = sprintf("SELECT DISTINCT l.*, d.LONGDES, TO_CHAR(l.TRANDATE, 'HH:MI AM') as TRANTIME,
		TO_CHAR(l.TRANDATE, 'Dy, Mon DD YYYY') as TRANDAY
		FROM GENERALLEDG_V l, LOCATIONDESCRIPTION d, UILISTVALUES u
		WHERE l.PATRONID = %d AND l.TRANSTYPE <> 9292 AND l.REQVALUE > 0 AND d.LOCATION = l.LOCATION
		AND TRANDATE >= TO_DATE('%s', 'DD-MON-YYYY HH24:MI:SS') 
		AND TRANDATE <= TO_DATE('%s', 'DD-MON-YYYY HH24:MI:SS') 
		AND l.TRANSTYPE = u.NUMCOPYOFVALUE AND u.FIELD ='TransType' 
		ORDER BY l.TRANSID, l.TRANDATE ASC", 
		$id, date('d-M-Y 00:00:00', $start), date('d-M-Y 23:59:59', $end));
	if ($this->db->dbquery($query)) {
		// Some transactions have purchase and tax split up, so we have to combine them.
		$result = $this->db->dbfetch();
		foreach ($result as $count => $rec) {
			if (isset($result[$count-1]) && $result[$count-1]['TRANSID'] == $rec['TRANSID']) {
				$result[$count]['APPRVALUEOFTRAN'] += $result[$count-1]['APPRVALUEOFTRAN'];
				unset($result[$count-1]);
			}
		}
		return $result;
	} else {
		return false;
	}
}

function get_meal_ledger($id, $start, $end) {
	// Return an array listing the mealplan transactions posted to a patron's account between the given dates (in unixtime).
	$query = sprintf("SELECT d.LONGDES, TO_CHAR(l.TRANDATE, 'HH:MI AM') as TRANTIME, 
		TO_CHAR(l.TRANDATE, 'Dy, Mon DD YYYY') as TRANDAY, l.*, p.PERIODNAME
		FROM MEALPLANTRANLEDG l, LOCATIONDESCRIPTION d, PERIODNAMES p
		WHERE  l.PATRONID = %d AND TRANSTYPE NOT IN (29898) AND d.LOCATION = l.LOCATION
		AND l.PERIODNUMBER = p.PERIODNUMBER
		AND TRANDATE >= TO_DATE('%s', 'DD-MON-YYYY HH24:MI:SS') AND 
		TRANDATE <= TO_DATE('%s', 'DD-MON-YYYY HH24:MI:SS') ORDER BY l.TRANDATE ASC", 
		$id, date('d-M-Y 00:00:00', $start), date('d-M-Y 23:59:59', $end));
	if ($this->db->dbquery($query)) {
		return $this->db->dbfetch();
	} else {
		return false;
	}
}

function get_patron_image($id) {
	// Return the patron's image record
	$query = sprintf("SELECT DBD_IMAGE FROM PATRONIMAGES WHERE PATRONID = %d", $id);
	if ($this->db->dbquery($query)) {
		$image = $this->db->dbfetch();
		return $image[0]['DBD_IMAGE'];
	} else {
		return false;
	}		
}

function get_terms() {
	// Return a list of meal plan terms with start and end dates
	$query = sprintf("SELECT * FROM TERMCALENDAR
		WHERE TERMNAME='%s'", $this->termname);
	if ($this->db->dbquery($query)) {
		$result = $this->db->dbfetch();
		$terms = array();
		for ($i = 1; $i < 4; $i++) $terms[$i] = array('startdate' => $result[0]['TERMSTARTDATE'.$i], 'enddate' => $result[0]['TERMENDDATE'.$i]);
		return $terms;
	} else {
		return false;
	}
}

function add_value($pik, $amount, $account, $comment) {
	// Add value to an SVC account; NOTE: uses PIK, not patron id
	
	$lines = $this->build_import_header_array();
	$lines[] = sprintf('C|%s|%d|++%d|%s', $pik, $account, $amount*100,addslashes($comment));
	$query = sprintf("INSERT INTO EXTRACTHOLDING VALUES ('D:\Import','%s','Online credit','%s',NULL,NULL,1,1)",$this->import_file,join("\r\n",$lines));
	return($this->db->dbquery($query));
}

function subtract_value($pik, $amount, $account, $comment, $budget = '') {
	// Subtract value from an SVC account; NOTE: uses PIK, not patron id
	
	$lines = $this->build_import_header_array();
	if ($budget) $lines[] = '/GL_DIVISIONDESIGNATOR="'.$budget.'"';
	$lines[] = sprintf('C|%s|%d|--%d|%s', $pik, $account, $amount*100,addslashes($comment));
	$query = sprintf("INSERT INTO EXTRACTHOLDING VALUES ('D:\Import','%s','Online debit','%s',NULL,NULL,1,1)",$this->import_file,join("\r\n",$lines));
	return($this->db->dbquery($query));
}

function build_import_header_array($fields=array())
{
	// Returns an array containing the standard header lines required for an import file on the transaction
	// processing server.  Assumes the basic fields needed for a value change, but you can pass your own in.
	// For more information, see CS Gold Administration Guide Book 5: Database Management
	
	$lines[] = '/DELIMITER="|"';
	$lines[] = '/USERNAME="'.$this->transaction_user.'"';
	$lines[] = '/LOCATION='.$this->transaction_location;
	$lines[] = '/INST=-1';
	if (count($fields))
	{
		$lines[] = '/FIELDS='.join('|', $fields);
	} else {
		$lines[] = '/FIELDS=update_mode|primarykeyvalue|svc1plannum|svc1amount|comment';
	}
	return $lines;		
}

}

?>
