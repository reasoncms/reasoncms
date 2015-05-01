<?php
/**
 * Upgrader that adds the Cloak theme
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
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.5_to_4.6']['add_cloak_theme'] = 'ReasonUpgrader_46_AddCloakTheme';

class ReasonUpgrader_46_AddCloakTheme implements reasonUpgraderInterface
{
    protected $user_id;

    public function user_id( $user_id = NULL)
    {
        if(!empty($user_id))
            return $this->user_id = $user_id;
        else
            return $this->user_id;
    }

    /**
     * Get the title of the upgrader
     * @return string
     */
    public function title()
    {
        return 'Add the new default Reason "Cloak" theme';
    }

    /**
     * Get a description of what this upgrade script will do
     * 
     * @return string HTML description
     */
    public function description()
    {
        return '<p>This upgrade adds the "Cloak" theme, a responsive theme developed to be the new default Reason theme.</p>';
    }
    
    protected function get_entity_info()
    {
        return array(
            'css' => array(
                array(
                    'name' => 'Cloak Normalize',
                    'url' => 'cloak/css/vendor/normalize.css',
                    'css_relative_to_reason_http_base' => 'true',
                    'unique_name' => 'cloak_normalize_css',
                ),
                array(
                    'name' => 'Font Awesome 4.2.0',
                    'url' => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css',
                    'css_relative_to_reason_http_base' => 'false',
                    'unique_name' => 'cloak_fontawesome_css',
                ),
                array(
                    'name' => 'Cloak Google Fonts',
                    'url' => 'https://fonts.googleapis.com/css?family=Open+Sans:400italic,400,300,700|Merriweather:400,300,400italic,700',
                    'css_relative_to_reason_http_base' => 'false',
                    'unique_name' => 'cloak_googlefonts_css',
                ),
                array(
                    'name' => 'Cloak',
                    'url' => 'cloak/scss/cloak.scss',
                    'css_relative_to_reason_http_base' => 'true',
                    'unique_name' => 'cloak_css',
                ),
                array(
                    'name' => 'Cloak Login',
                    'url' => 'cloak/scss/cloak_login.scss',
                    'css_relative_to_reason_http_base' => 'true',
                    'unique_name' => 'cloak_login_css',
                ),
                array(
                    'name' => 'Reason College',
                    'url' => 'cloak/scss/reason-college.scss',
                    'css_relative_to_reason_http_base' => 'true',
                    'unique_name' => 'reason_college_css',
                ),
            ),
            'minisite_template' => array(
                array(
                    'name' => 'cloak',
                    'unique_name' => 'cloak_template',
                ),
                array(
                    'name' => 'cloak_login',
                    'unique_name' => 'cloak_login_template',
                ),
                array(
                    'name' => 'reason_college',
                    'unique_name' => 'reason_college_template',
                ),
            ),
            'theme_type' => array(
                array(
                    'name' => 'Cloak',
                    'unique_name' => 'cloak_theme',
                ),
                array(
                    'name' => 'Cloak (Login)',
                    'unique_name' => 'cloak_login_theme',
                ),
                array(
                    'name' => 'Reason College',
                    'unique_name' => 'reason_college_theme',
                ),
            ),
        );
    }
    
    protected function get_theme_rels()
    {
        return array(
            'cloak_theme' => array(
                'theme_to_minisite_template' => array(
                    'cloak_template',
                ),
                'theme_to_external_css_url' => array(
                    'cloak_normalize_css',
                    'cloak_fontawesome_css',
                    'cloak_googlefonts_css',
                    'cloak_css',
                ),
            ),
            'cloak_login_theme' => array(
                'theme_to_minisite_template' => array(
                    'cloak_login_template',
                ),
                'theme_to_external_css_url' => array(
                    'cloak_normalize_css',
                    'cloak_fontawesome_css',
                    'cloak_googlefonts_css',
                    'cloak_login_css',
                ),
            ),
            'reason_college_theme' => array(
                'theme_to_minisite_template' => array(
                    'reason_college_template',
                ),
                'theme_to_external_css_url' => array(
                    'cloak_normalize_css',
                    'cloak_fontawesome_css',
                    'cloak_googlefonts_css',
                    'reason_college_css',
                ),
            ),
        );
    }

    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
    public function test()
    {
        return $this->process(true);
    }

    /**
     * Run the upgrader
     *
     * @return string HTML report
     */
    public function run()
    {
        return $this->process(false);
    }
    
    function process($test = true)
    {
        $ret = '';
        foreach($this->get_entity_info() as $type_uname => $entities)
        {
            foreach($entities as $entity)
            {
                if(!reason_unique_name_exists($entity['unique_name']))
                {
                    if($test)
                    {
                        $ret .= '<p>Would create '.$type_uname.' '.$entity['name'].'</p>';
                    }
                    elseif(reason_create_entity( id_of('master_admin'), id_of($type_uname), $this->user_id, $entity['name'], $entity))
                    {
                        $ret .= '<p>Created '.$type_uname.' '.$entity['name'].'</p>';
                    }
                    else
                    {
                        $ret .= '<p>ERROR: Unable to create '.$type_uname.' '.$entity['name'].'</p>';
                    }
                }
            }
        }
        foreach($this->get_theme_rels() as $theme_uname=>$rels)
        {
            if(reason_unique_name_exists($theme_uname))
            {
                $theme = new entity(id_of($theme_uname));
                foreach($rels as $rel_name => $rel_unames)
                {
                    $rel_id = relationship_id_of($rel_name);
                    if(empty($rel_id))
                    {
                        $ret .= '<p>ERROR: unable to create relationships with name '.$rel_name.' -- not found</p>';
                        continue;
                    }
                    $rel_entities = $theme->get_left_relationship($rel_name);
                    $rel_unames_in_db = array();
                    foreach($rel_entities as $rel_entity)
                    {
                        if($rel_entity->get_value('unique_name'))
                            $rel_unames_in_db[] = $rel_entity->get_value('unique_name');
                    }
                    $unrelated = array_diff($rel_unames, $rel_unames_in_db);
                    foreach($unrelated as $unrelated_uname)
                    {
                        if(!$test)
                        {
                            if(!reason_unique_name_exists($unrelated_uname))
                            {
                                $ret .= '<p>ERROR: unable to create relationships with unique name '.$unrelated_uname.' -- not found</p>';
                                continue;
                            }
                            else
                            {
                                $unrelated_id = id_of($unrelated_uname);;
                            }
                        }
                        if($test)
                        {
                            $ret .= '<p>Would create relationship from '.$theme_uname.' to '.$unrelated_uname.'</p>';
                        }
                        elseif(create_relationship( $theme->id(), $unrelated_id, $rel_id))
                        {
                            $ret .= '<p>Created relationship from '.$theme_uname.' to '.$unrelated_uname.'</p>';
                        }
                        else
                        {
                            $ret .= '<p>ERROR: Unable to create relationship from '.$theme_uname.' to '.$unrelated_uname.'</p>';
                        }
                    }
                }
            }
            elseif($test)
            {
                $ret .= '<p>Would create relationships from '.$theme_uname.' to css and templates.</p>';
            }
            else
            {
                trigger_error('Unable to create relationship with theme '.$theme_uname.' -- it doesn\'t appear to exist!');
                $ret .= '<p>ERROR: Unable to create relationship with theme '.$theme_uname.' -- it doesn\'t appear to exist!. Please try running this script again.</p>';
            }
        }
        if(empty($ret))
            $ret .= '<p>Upgrade complete; nothing to do</p>';
        return $ret;
    }
}
