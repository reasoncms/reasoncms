<?php
/**
 * Upgrader that adds storage-related fields to media works.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['add_feature_to_category_allowable_relationship'] = 'ReasonUpgrader_47_AddFeatureToCategoryAllowableRelationship';

/**
 * @todo also add event_to_media_work relationship
 */
class ReasonUpgrader_47_AddFeatureToCategoryAllowableRelationship implements reasonUpgraderInterface
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
        return 'Add feature to category relationship';
    }
    /**
     * Get a description of what this upgrade script will do
     * @return string HTML description
     */
    public function description()
    {
        return '<p>This upgrade adds the feature_to_category allowable relationship.</p>';
    }

    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
    public function test()
    {
        if ( reason_relationship_name_exists( 'feature_to_category', false ) )
        {
            return '<p>This update has already run.</p>';
        }
        else
        {
            return '<p>The feature_to_category allowable relationship doesn\'t exist.</p><p>This update will add the feature_to_category allowable relationship.</p>';
        }
    }

    /**
     * Run the upgrader
     * @return string HTML report
     */
    public function run()
    {
        $run_message = '';
        if(!reason_relationship_name_exists('feature_to_category'))
        {
            $feature_to_category_definition = array (
                'description'=>'Features has Categories',
                'directionality'=>'bidirectional',
                'connections'=>'many_to_many',
                'required'=>'no',
                'is_sortable'=>'yes',
                'display_name'=>'Categorize',
                'display_name_reverse_direction'=>'Features with this category',
            );
            if(create_allowable_relationship(id_of('feature_type'),id_of('category_type'),'feature_to_category', $feature_to_category_definition))
                $run_message .= '<p>Added the feature_to_category allowable relationship.</p>';
            else
                $run_message .= '<p>Failed to create the feature_to_category allowable relationship. Try again. If you are not successful, you may wish to try to add this relationship type manually: In Master Admin, go to Allowable Relationship Manager, add a row, then create a relationship between Feature and Category named feature_to_category. The other values are:</p>'.spray($feature_to_category_definition);
        }
        if(!empty($run_message))
        {
            return $run_message;
        }
        else
        {
            return 'This update has already run.';
        }
    }
}
