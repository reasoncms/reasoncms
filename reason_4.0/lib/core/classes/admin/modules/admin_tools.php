<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * List various administrative tools
	 */
	class ReasonAdminToolsModule extends DefaultModule
	{
		function AdminToolsModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
		} // }}}
		function _get_tools()
		{
			return array(
				'General Information' => array(
					'?cur_module=VersionCheck' => array(
						'title' => 'Check for Updates',
						'description' => 'Check to see if your version of Reason is up-to-date',
					),	
					'scripts/page_types/view_page_type_info.php' => array(
						'title' => 'Page Types',
                                                'description' => 'See all the page type definitions',
					),
					'?cur_module=ReviewChanges' => array(
                                                'title' => 'Review Activity in Reason',
                                                'description' => 'See what has been added, deleted, and updated recently in Reason',
                                        ),
                                        'scripts/search/find_across_sites.php' => array(
                                                'title' => 'Search Across All Reason Sites',
                                                'description' => 'Find a given string anywhere in Reason',
                                        ),
                                        'scripts/developer_tools/modules.php' => array(
                                                'title' => 'Sample Pages for Each Module',
                                                'description' => 'Find pages that use particular modules (also: module usage stats)',
                                        ),
					'scripts/developer_tools/get_page_types.php' => array(
                                                'title' => 'Sample Pages for Each Page Type',
                                                'description' => 'Find pages that use particular page types (also: page type usage stats)',
                                        ),
					'scripts/developer_tools/get_type_listers.php' => array(
                                                'title' => 'Sample Listers & Content Managers for Each Type',
                                                'description' => 'Easy access to the administrative interfaces for each Reason type',
                                        ),
					'?cur_module=EntityInfo' => array(
                                                'title' => 'Entity Info',
                                                'description' => 'Get basic information about an entity based soley on its ID',
                                        ),
					'?cur_module=ActiveUsers' => array(
                                                'title' => 'Active Users',
                                                'description' => 'See who\'s been active in Reason within a given timeframe',
                                        ),
					'?cur_module=ListSites' => array(
						'title' => 'List Sites',
						'description' => 'See all Reason sites, with links',
					),
									
				),
				'Batch Actions' => array(
					'scripts/move/move_entities_among_sites.php' => array(
						'title' => 'Move Entities',
						'description' => 'Move entities from one site to another',
					),
					'scripts/urls/update_urls.php' => array(
						'title' => 'Update URLs',
						'description' => 'Run the .htaccess rewrites for a particular site or for all sites. (also: get the command for creating site directories that need creation)',
					),
					'scripts/search/find_and_replace.php' => array(
						'title' => 'Find and Replace',
						'description' => 'Find and replace across multiple entities on multiple sites',
					),
					'?cur_module=EventSplit' => array(
						'title' => 'Split Repeating Event',
						'description' => 'Chop a repeating event into multiple separate events (note: to use this module, 
first edit an event entity, then change the cur_module part of the query to "cur_module=EventSplit")',
					),

				),
			);
		}
		function run() // {{{
		{
			echo '<div id="adminToolsModule">'."\n";
			foreach($this->_get_tools() as $set_name => $set)
			{
				echo '<h3>'.htmlspecialchars($set_name).'</h3>'."\n";
				foreach($set as $url => $info)
				{
					echo '<h4><a href="'.htmlspecialchars($url).'">'.htmlspecialchars($info['title']).'</a></h4>'."\n";
					echo '<p>'.htmlspecialchars($info['description']).'</p>'."\n";	
				}
			}
			echo '</div>'."\n";
		}
	}
?>
