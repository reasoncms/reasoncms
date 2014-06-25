<?php
    reason_include_once( 'minisite_templates/modules/default.php' );
    
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'slateFormModule';
    
    class slateFormModule extends DefaultMinisiteModule
    {
        var $slate_info;

        function has_content()
        {
            $site_id = $this->site_id;
            $es = new entity_selector( $site_id );
            $es->add_type( id_of( 'slate_form_type' ) );
            $es->add_right_relationship($this->cur_page->id(), relationship_id_of('slate_form_to_page'));
            $es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('slate_form_to_page'));
            $this->slate_info = $es->run_one();

            if ($this->slate_info != false)
            {
                return true;
            }
            return false;   
        }
        function run()
        {
            foreach ($this->slate_info as $info)
            {
                echo '<div id="form_' . $info->get_value('slate_form_id') . '">Loading...</div><script src="https://connect.luther.edu/register/?id='. $info->get_value('slate_form_id') . '&amp;output=embed&amp;div=form_'. $info->get_value('slate_form_id') . '">/**/</script>' . "\n";          
            }
        }
        
    }
?>