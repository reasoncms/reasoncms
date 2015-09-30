<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['remove_backwards_rels'] = 'ReasonUpgrader_41_RemoveBackwardsRels';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * This class searches and destroys backwards relationships in Reason.
 */
class ReasonUpgrader_41_RemoveBackwardsRels implements reasonUpgraderInterface
{
	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
        /**
         * Get the title of the upgrader
         * @return string
         */
	public function title()
	{
		return 'Remove backwards relationships';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade removes backwards relationships (if any exist) that could be created in some instances by an odd bug in the finish module.</p>";
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		$backwards_rels = $this->find_backwards_rels();
		$count = (!empty($backwards_rels)) ? count($backwards_rels) : 0;
		if(empty($count))
		{
			return '<p>No backwards relationships exist in this reason instance.</p>';
		}
		else
		{
			return '<p>Would delete ' . $count . ' backwards relationships.</p>';
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$backwards_rels = $this->find_backwards_rels();
		$count = (!empty($backwards_rels)) ? count($backwards_rels) : 0;
		if(empty($count))
		{
			return '<p>No backwards relationships exist in this reason instance.</p>';
		}
		else
		{
			$backwards_rel_keys = array_keys($backwards_rels);
			foreach ($backwards_rel_keys as $id)
			{
				delete_relationship($id);
			}
			return '<p>Deleted ' . $count . ' backwards relationships.</p>';
		}
	}
	
	/**
	 * We find all backwards rels in Reason.
	 */
	protected function find_backwards_rels()
	{
		$dbs = new DBSelector();
		$dbs->add_table('r', 'relationship');
		$dbs->add_table('ar', 'allowable_relationship');
		$dbs->add_table('e1', 'entity');
		$dbs->add_table('e2', 'entity');
		$dbs->add_field('r', '*');
		$dbs->add_relation('r.type = ar.id');
		$dbs->add_relation('e1.id = r.entity_a');
		$dbs->add_relation('e2.id = r.entity_b');
		$dbs->add_relation('e1.type = ar.relationship_b');
		$dbs->add_relation('e2.type = ar.relationship_a');
		$dbs->add_relation('e1.type != e2.type'); // we cannot know if type to type rels are backwards!
		$result = $dbs->run();
		if ($result)
		{
			foreach($result as $row)
			{
				$backwards_rels[$row['id']] = $row;
			}
		}
		return (!empty($backwards_rels)) ? $backwards_rels : false;
	}
}
?>