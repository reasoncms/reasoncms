<?php
/*
 * This script contains a class that updates the rewrite rules for the site after the state of the site
 * is changed from Live to Deleted
 */

$GLOBALS['_content_post_deleter_classes'][ basename( __FILE__) ] = 'updateRewritesOnSiteDeletion';

reason_include_once('content_post_deleters/default.php');
reason_include_once('classes/url_manager.php');

/**
 * Update rewrites content post deleter class
 * Instantiates a url a manager and runs the rewrite rules for the site when the site was
 * changed from Live to Deleted
 * @author Matt Ryan
 * @date 2006-10-02
 */
class updateRewritesOnSiteDeletion extends defaultPostDeleter
{
        /**
         * run the rewrite rules
         */
        function run()
        {
                $urlm = new url_manager($this->deleted_entity->id());
                $urlm->update_rewrites();
        }
}

?>

