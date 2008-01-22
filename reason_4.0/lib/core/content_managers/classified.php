<?php
reason_include_once('content_managers/default.php3');
$GLOBALS['_content_manager_class_names'][basename(__FILE__)] = 'ClassifiedManager';
class ClassifiedManager extends ContentManager {
	function alter_data() {
		if ($this->entity->get_value('state')=='Pending')
			$this->set_value('datetime', get_mysql_datetime());
	}
}
?>
