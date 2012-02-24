<?php
/**
 * @package reason
 * @subpackage content_post_deleters
 */

/**
 * Register the post deleter with Reason & include dependencies
 */
$GLOBALS['_content_post_deleter_classes'][ basename( __FILE__) ] = 'updateRewrites';

reason_include_once('content_post_deleters/default.php');
reason_include_once('classes/url_manager.php');

/**
 * Update rewrites content post deleter class
 *
 * Instantiates a url a manager and runs the rewrite rules for the site where an entity was was
 * changed from Live to Deleted
 *
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
                
                // if a page was just deleted, lets also clear the nav cache for the site
                if ($this->vars['type_id'] == id_of('minisite_page'))
                {
                	reason_include_once('classes/object_cache.php');
					$cache = new ReasonObjectCache($this->vars['site_id'] . '_navigation_cache');
					$cache->clear();
                }
        }
}

?>

