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
	$ret = '';
    	if($this->language_field_exists())
    	{
        	$ret .= '<p>The "language" field already exists.</p>';
        }
	else
	{
            $ret .= '<p>The "language" field does not exist. It will be created when this script is run.</p>';
	}
        if($this->media_caption_lang_field_exists())
        {
            $ret .= '<p>The media caption "lang" field is still present. Its contents will be transferred to the new "language" field and it will be removed.<p>';
        }
	else
	{
            $ret .= '<p>The media caption "lang" field has been removed. Nothing left to be done.</p>';	
	}
	return $ret;
    }
    
    protected function language_field_exists()
    {
    	$fields = get_fields_by_content_table( 'entity', false );
    	return in_array('language', $fields);
    }
    protected function media_caption_lang_field_exists()
    {
        $fields = get_fields_by_content_table( 'media_captions', false );
	return in_array('lang', $fields);
    }


    /**
     * Run the upgrader
     * @return string HTML report
     */
    public function run()
    {
	$ret = '';
    	if($this->language_field_exists())
    	{
        	$ret .= '<p>The "language" field already exists. Nothing done.</p>';
        }
        else
        {
            $ret .= '<p>Adding the "language" field...</p>';
            if($this->create_language_field())
            {
            	$ret .= '<p>Added "language" field.</p>';
            }
            else
            {
            	$ret .= '<p>Unable to add "language" field. Please try adding a new field named "language" with the definition VARCHAR(8) to the entity table.</p>';
            }
        }
        if(!$this->media_caption_lang_field_exists())
        {
            $ret .= '<p>The media caption lang field has been deleted. Nothing is needed to be done.</p>';
        }
        else
        {
            $ret .= '<p>Transferring caption lang field data to the new language field...</p>';
            $es = new entity_selector();
            $es->add_type(id_of('av_captions'));
            $captions = $es->run_one();
            $count = 0;
            foreach($captions as $cap)
            {
                if($lang = $cap->get_value('lang'))
                {
                    reason_update_entity($cap->id(), $cap->get_value('last_edited_by'), array('language'=>$lang,'last_modified'=>$cap->get_value('last_modified')), false);
                    $count++;
                }
            }
            $ret .= '<p>Transferred data for '.$count.' captions.</p>';
            if($lang_field = $this->get_lang_field_entity())
            {
       	        $ret .= '<p>Deleting lang field...</p>';
                if(db_query('ALTER TABLE `media_captions` DROP `lang`'))
                {
       	            $ret .= '<p>Successfully dropped the lang column. Deleting the field entity...</p>';
                   	if(reason_expunge_entity($lang_field->id(),$this->user_id))
                   	{
                        $ret .= '<p>Successfully deleted the lang field entity.</p>';
                   	}
                   	else
                   	{
                   	    $ret .= '<p>Problem deleting the lang field entity. Please try deleting it manually in the Master Admin.</p>';
                   	}
       	        }
                else
                {
                    $ret .= '<p>Problem deleting the lang column from the media_captions table. Please try doing this manually in the Master Admin.</p>';
                }
            }
            else
            {
                $ret .= '<p>unable to find the media caption "lang" field. Please try deleting it manually in the Master Admin.</p>';
            }
        }
        return $ret;
    }
    protected function get_lang_field_entity()
    {
        $es = new entity_selector();
       	$es->add_type(id_of('content_table'));
       	$es->add_relation('name = "media_captions"');
       	$tables = $es->run_one();
       	if(!empty($tables))
       	{
       	    $table = current($tables);
	    $es = new entity_selector();
	    $es->add_type(id_of('field'));
	    $es->add_relation('name = "lang"');
	    $es->add_left_relationship($table->id(), relationship_id_of('field_to_entity_table'));
            $fields = $es->run_one();
            if(!empty($fields))
            {
                return current($fields);
            }
       	}
    }
    protected function create_language_field()
	{
		$q = "ALTER TABLE `entity` ADD `language` VARCHAR(24)";
		$result = db_query($q, 'problem creating language field');
		return true;
	}
}
