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
	function ReasonAdminToolsModule( &$page )
	{
		$this->admin_page =& $page;
	}
	
	function init()
	{
		$this->admin_page->title = 'Administrative Tools';
		$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'admin_tools.css');                	
	}
	
	function _get_tools()
	{
		return array(
			'General Information' => array(
				'?cur_module=VersionCheck' => array(
					'title' => 'Check for Updates',
					'description' => 'Check to see if your version of Reason is up-to-date',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/page_types/view_page_type_info.php' => array(
					'title' => 'Page Types',
											'description' => 'See all the page type definitions',
					'safety_level' => 'safe',
				),
				'?cur_module=ReviewChanges' => array(
											'title' => 'Review Activity in Reason',
											'description' => 'See what has been added, deleted, and updated recently in Reason',
					'safety_level' => 'safe',
									),
									REASON_HTTP_BASE_PATH.'scripts/search/find_across_sites.php' => array(
											'title' => 'Search Across All Reason Sites',
											'description' => 'Find a given string anywhere in Reason',
					'safety_level' => 'safe',
									),
									REASON_HTTP_BASE_PATH.'scripts/developer_tools/modules.php' => array(
											'title' => 'Sample Pages for Each Module',
											'description' => 'Find pages that use particular modules (also: module usage stats)',
					'safety_level' => 'safe',
									),
				REASON_HTTP_BASE_PATH.'scripts/developer_tools/get_page_types.php' => array(
											'title' => 'Sample Pages for Each Page Type',
											'description' => 'Find pages that use particular page types (also: page type usage stats)',
					'safety_level' => 'safe',
									),
				REASON_HTTP_BASE_PATH.'scripts/developer_tools/get_type_listers.php' => array(
											'title' => 'Sample Listers & Content Managers for Each Type',
											'description' => 'Easy access to the administrative interfaces for each Reason type',
					'safety_level' => 'safe',
									),
				'?cur_module=EntityInfo' => array(
											'title' => 'Entity Info',
											'description' => 'Get basic information about an entity based soley on its ID',
					'safety_level' => 'safe',
									),
				'?cur_module=ActiveUsers' => array(
											'title' => 'Active Users',
											'description' => 'See who\'s been active in Reason within a given timeframe',
					'safety_level' => 'safe',
									),
				'?cur_module=ListSites' => array(
					'title' => 'List Sites',
					'description' => 'See all Reason sites, with links',
					'safety_level' => 'safe',
				),
				'?cur_module=ListUnusedThemes' => array(
					'title' => 'List Unused Themes',
					'description' => 'Lists themes and theme elements that are not currently in use',
					'safety_level' => 'safe',
				),
				'?cur_module=OldThemes' => array(
					'title' => 'List Old Themes',
					'description' => 'Lists themes that were created before a given date',
					'safety_level' => 'safe',
				),
				'?site_id='.id_of('master_admin').'&cur_module=TopLevelNavAnalysis' => array(
					'title' => 'Site Structure Analysis',
					'description' => 'Describes the top level navigation used by each site.',
					'safety_level' => 'safe',
				),
				'?site_id='.id_of('master_admin').'&cur_module=Export' => array(
					'title' => 'Data Export',
					'description' => 'Exports Reason data for a particular site',
					'safety_level' => 'safe',
				),
				'?site_id='.id_of('master_admin').'&cur_module=FormRecipients' => array(
					'title' => 'Form Recipients Report',
					'description' => 'Lists all the people who receive email notification when Reason forms are submitted',
					'safety_level' => 'safe',
				),
				'?site_id='.id_of('master_admin').'&cur_module=NoRelReport' => array(
					'title' => 'No Relationships Report',
					'description' => 'Lists entities with or without specific relationships',
					'safety_level' => 'safe',
				),	
			),
			'Content Actions' => array(
				REASON_HTTP_BASE_PATH.'scripts/urls/update_urls.php' => array(
					'title' => 'Update URLs',
					'description' => 'Run the .htaccess rewrites for a particular site or for all sites. (also: get the command for creating site directories that need creation)',
					'safety_level' => 'safe',
				),
				'?cur_module=EventSplit' => array(  
					'title' => 'Split Repeating Event',
					'description' => 'Chop a repeating event into multiple separate events (note: to use this module, first edit an event entity, then change the cur_module part of the query to "cur_module=EventSplit")',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/developer_tools/publication_migrator.php' => array(
					'title' => 'Publication Migrator',
					'description' => 'Migrate a publication from old-style news (e.g. no publication entity, single news page per site) to the modern blog/publication format.',
					'safety_level' => 'safe',
				),
				'?cur_module=ShareSiteOwnership' => array(  
					'title' => 'Share Site Ownership',
					'description' => 'Assign some or all of a user\'s sites to another user as well.',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/move/move_entities_among_sites.php' => array(
					'title' => 'Move Entities',
					'description' => 'Move entities from one site to another. Additional steps required if moving pages or assets.',
					'safety_level' => 'careful',
				),
				REASON_HTTP_BASE_PATH.'scripts/search/find_and_replace.php' => array(
					'title' => 'Find and Replace',
					'description' => 'Find and replace across multiple entities on multiple sites',
					'safety_level' => 'careful',
				),
				'?cur_module=CopySitePages' => array(  
					'title' => 'Copy Site Pages',
					'description' => 'Copy the pages (and only the pages) from one site into a new site.',
					'safety_level' => 'careful',
				),
				REASON_HTTP_BASE_PATH.'scripts/urls/replicate_url_history.php' => array(
					'title' => 'Replicate URL History',
					'description' => 'Give one page the URL history of another page. '.
							 'It is a good idea to run this script if you have replaced a page '.
							 'with a different page, and you want to make sure that Reason\'s '.
							 'redirection from previous URLs of the old page point to the new page.',
					'safety_level' => 'careful',
				),
				'?cur_module=BatchDelete' => array(
					'title' => 'Batch Delete',
					'description' => 'Delete multiple items at once',
					'safety_level' => 'experimental',
				),
/*
				'?cur_module=clone' => array(
					'title' => 'Clone Entity',
					'description' => 'Duplicate an entity. Note: This tool is extremely rough, and may have '.
							 'unintended consequences. Test in a development environment first, '.
							 'and review afterwards. Also: use this modul br chaing cur_module part in URL to '.
							 '"cur_module=clone" when editing the entity.',
					'safety_level' => 'experimental',
				),
*/
			),
			'Data Structure' => array(
				'?cur_module=AllowableRelationshipManager' => array(
					'title' => 'Allowable Relationship Manager',
					'description' => 'Define and review allowable relationship types among Reason entities',
					'safety_level' => 'careful',
				),
			),
			'Database Cleanup' => array(
				REASON_HTTP_BASE_PATH.'scripts/db_maintenance/delete_headless_chickens.php' => array(
					'title' => 'Delete Headless Chickens',
					'description' => 'Silly name; important cleanup script. Headless chickens are '.
						'records in Reason tables that do not correspond to a record in the '.
						'master entity table. This script will delete all of the headless chickens.',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/db_maintenance/delete_widowed_relationships.php' => array(
					'title' => 'Delete Widowed Relationships',
					'description' => 'Removes any relationships that point to a no-longer-existent entity',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/db_maintenance/amputees.php' => array(
					'title' => 'Fix Amputees',
					'description' => 'Fully populates any Reason entities that are not set up with all '.
						'their proper tables.',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/db_maintenance/find_extra_root_pages.php' => array(
					'title' => 'Find Extra Root Pages',
					'description' => 'Finds sites that have more than one root page and urges you to fix '.
						'them.',
					'safety_level' => 'safe',
				),
				REASON_HTTP_BASE_PATH.'scripts/db_maintenance/delete_duplicate_relationships.php' => array(
					'title' => 'Delete Duplicate Relationships',
					'description' => 'Remove identical relationships from the database '.
						'(these, if they exist, are pure cruft)',
					'safety_level' => 'careful',
				),
				REASON_HTTP_BASE_PATH.'scripts/db_maintenance/remove_duplicate_entities.php' => array(
					'title' => 'Collapse Duplicate Entities',
					'description' => 'Identical entities may be cruft. This script finds them and deletes '.
							 'one of each pair of identical entities.',
					'safety_level' => 'experimental',
				),
				'?cur_module=OrphanManager' => array(
					'title' => 'Orphaned Entities Manager',
					'description' => 'Finds entities not owned by a site and lets you move or delete them.',
					'safety_level' => 'careful',
				),
			),
			'Upgrading' => array(
				REASON_HTTP_BASE_PATH.'upgrade.php' => array(
					'title' => 'Run Upgrade Scripts',
					'description' => 'After updating your Reason codebase, you should always make sure to run the upgrade scripts from your old version to your new version. In some cases, Reason will be broken until you run the upgrade scripts, so it is best to run them immediately after updating your code.',
					'safety_level' => 'careful',
				),
			),
			'Developer Tools' => array(
				'?cur_module=ErrorVisibility' => array(
					'title' => 'Toggle Error Visibility',
					'description' => 'Choose whether you want to get reports of PHP errors on pages you are viewing.',
					'safety_level' => 'safe',
				),
			),
		);
	}
	
	function run()
	{
		echo '<div id="adminToolsModule">'."\n";
		echo '<div class="key"><h3>Key</h3>'."\n";
		echo '<ul><li class="safe"><strong>"Safe"</strong>: Benign and well-tested. Little-to-no danger of causing any unexpected problems</li>'."\n";
		echo '<li class="careful"><strong>"Careful"</strong>: Well-tested tool, but powerful and/or possible to break ';
		echo 'things if used improperly.</li>'."\n";
		echo '<li class="experimental"><strong>"Experimental"</strong>: Not well-tested, with known issues.';
		echo ' Included here because it may still be useful if used with';
		echo ' extreme care.</li>'."\n";
		echo '</ul></div>'."\n";
		foreach($this->_get_tools() as $set_name => $set)
		{
			echo '<div class="set">'."\n";
			echo '<h3>'.htmlspecialchars($set_name).'</h3>'."\n";
			foreach($set as $url => $info)
			{
				echo '<div class="tool '.htmlspecialchars($info['safety_level']).'">'."\n";
				echo '<h4><a href="'.htmlspecialchars($url).'">'.htmlspecialchars($info['title']).'</a> <span class="safetyLevel">('.htmlspecialchars(ucfirst($info['safety_level'])).')</span></h4>'."\n";
				echo '<p>'.htmlspecialchars($info['description']).'</p>'."\n";	
				echo '</div>'."\n";
			}
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
	}
}
?>
