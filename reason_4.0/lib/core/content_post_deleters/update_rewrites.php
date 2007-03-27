<?php
/*
 * This script contains a class that updates the rewrite rules for the site after the state of an entity
 * is changed from Live to Deleted
 */

$GLOBALS['_content_post_deleter_classes'][ basename( __FILE__) ] = 'updateRewrites';

reason_include_once('content_post_deleters/default.php');
reason_include_once('classes/url_manager.php');

/**
 * Update rewrites content post deleter class
 * Instantiates a url a manager and runs the rewrite rules for the site where an entity was was
 * changed from Live to Deleted
 * @author Nathan White
 * @date 2006-08-17
 */
class updateRewrites extends defaultPostDeleter
{
        /**
         * run the rewrite rules
         */
        function run()
        {
                $urlm = new url_manager($this->vars['site_id']);
                $urlm->update_rewrites();
        }
}

?>

