<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('scripts/upgrade/reason_db_helper.php');

$GLOBALS['_reason_upgraders']['4.8_to_4.9']['add_language_fields'] = 'ReasonUpgrader_49_AddLanguageFields';

class ReasonUpgrader_49_AddLanguageFields implements reasonUpgraderInterface 
{

    protected $user_id;
    public function user_id( $user_id = NULL)
    {
        if(!empty($user_id))
            return $this->user_id = $user_id;
        else
            return $this->user_id;
    }
    
    private $dbHelper;

    public function __construct() 
    {
        $this->dbHelper = new ReasonDbHelper();
        $this->dbHelper->setUsername(reason_check_authentication());
    }

    /**
     * Get the title of the upgrader
     * @return string
     */
    public function title()
    {
        return 'Add a language field to the entity table';
    }
    
    /**
     * Get a description of this script's function
     * @return string HTML description
     */
    public function description()
    {
        return '<p>This script adds a language field to the entity table.</p>';
    }
    
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
    public function test()
    {
    	if($this->language_field_exists())
    	{
        	return '<p>The "language" field already exists.</p>';
        }
        return '<p>The "language" field does not exist. It will be created when this script is run.</p>';
    }
    
    protected function language_field_exists()
    {
    	$fields = get_fields_by_content_table( 'entity', false );
    	return in_array('language', $fields);
    }

    /**
     * Run the upgrader
     * @return string HTML report
     */
    public function run()
    {
    	if($this->language_field_exists())
    	{
        	return '<p>The "language" field already exists. Nothing done.</p>';
        }
        echo '<p>Adding the "language" field...</p>';
        if($this->create_language_field())
        {
        	echo '<p>Added "language" field.</p>';
        }
        else
        {
        	echo '<p>Unable to add "language" field. Please try adding a new field named "language" with the definition VARCHAR(8) to the entity table.</p>';
        }
    }
    
    protected function create_language_field()
	{
		$q = "ALTER TABLE `entity` ADD `language` VARCHAR(8)";
		$result = db_query($q, 'problem creating language field');
		return true;
	}
}