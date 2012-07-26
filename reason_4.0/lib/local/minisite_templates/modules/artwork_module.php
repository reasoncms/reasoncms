<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'artworkModule';

class artworkModule extends DefaultMinisiteModule {

    // a super simple module doesn't need to init anything

    function init() {
        
    }

    function has_content() {
        //return true;
        $site_id = $this->site_id;
        $es = new entity_selector($site_id);
        $es->add_type(id_of('artwork_type'));
        // $es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
        //$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_twitter_feed'));
        $posts = $es->run_one();
        print_r($posts);
        return false;
    }

    // this is the section that is run when the page renders your module

    function run() {

        foreach ($posts AS $post) {

            echo $post->get_value('artist_name');

            echo $post->get_value('medium');

            echo $post->get_value('date_format');
        }

        echo 'Module says "Hello World"';
    }

}

?>
