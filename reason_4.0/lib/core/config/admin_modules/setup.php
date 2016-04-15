<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/*
 * Rules for inclusion of admin modules
 *
 * This file sets up the array $GLOBALS['_reason_admin_modules']. This file defines the "core" admin
 * modules. If you have local admin modules, you should define $GLOBALS['_reason_admin_modules_local'] 
 * in a file called setup_local.php and place it here:
 *
 * reason_package/reason_4.0/lib/local/config/admin_modules/setup_local.php
 * 
 * Modules defined in $GLOBALS['_reason_admin_modules_local'] will be merged into the available set
 * of admin modules defined in $GLOBALS['_reason_admin_modules']. 
 * 
 * The $GLOBALS['_reason_admin_modules'] array identifues the filename and class name for each admin module.
 * Each key of this array corresponds to a string identified in the request as "cur_module." 
 *
 * Reason compares the requested module to this keys in this array. If it finds a matching key
 * (case-sensitive) it includes the file identified by the key "file" in the value array of the cur_module 
 * key in the classes/admin/modules directory.
 *
 * Reason then instantiates the class identified by the key "class" in the value array.
 *
 * A brief schematic of the $GLOBALS['_reason_admin_modules'] array:
 *
 * <pre>
 *	$GLOBALS['_reason_admin_modules'] = array(
 *		'Default'=>array('file'=>'default.php','class'=>'DefaultModule'),
 *		'Another'=>array('file'=>'another.php','class'=>'AnotherModule'),
 * );
 * </pre>
 *
 * To create a new admin module, add a file to reason_package/reason_4.0/lib/local/classes/admin/modules/.
 *
 * In this file, define a class that extends the DefaultModule. Overload the various methods as needed.
 *
 * Add a line to the $GLOBALS['_reason_admin_modules_local'] which identifies the filename and class name 
 * of your new module. Now the new module should be available simply by altering the cur_module request element to 
 * match the key you used in this array.
 */
 $GLOBALS['_reason_admin_modules'] = array(
		'Default'=>array('file'=>'default.php','class'=>'DefaultModule'),
		'DoBorrow'=>array('file'=>'doBorrow.php','class'=>'DoBorrowModule'),
		'DoAssociate'=>array('file'=>'doAssociate.php','class'=>'DoAssociateModule'),
		'DoDisassociate'=>array('file'=>'doDisassociate.php','class'=>'DoDisassociateModule'),
		'Archive'=>array('file'=>'archive.php','class'=>'ArchiveModule'),
		'Sorting'=>array('file'=>'sorter.php','class'=>'SortingModule'),
		'Site'=>array('file'=>'site.php','class'=>'SiteModule'),
		'Lister'=>array('file'=>'lister.php','class'=>'ListerModule'),
		'Delete'=>array('file'=>'delete.php','class'=>'DeleteModule'),
		'Undelete'=>array('file'=>'undelete.php','class'=>'UndeleteModule'),
		'Expunge'=>array('file'=>'expunge.php','class'=>'ExpungeModule'),
		'Editor'=>array('file'=>'editor.php','class'=>'EditorModule'),
		'Associator'=>array('file'=>'associator.php','class'=>'AssociatorModule'),
		'ReverseAssociator'=>array('file'=>'reverse_associator.php','class'=>'ReverseAssociatorModule'),
		'user_info'=>array('file'=>'user_info.php','class'=>'UserInfoModule'),
		'kill_session'=>array('file'=>'kill_session.php','class'=>'KillSessionModule'),
		'show_session'=>array('file'=>'show_session.php','class'=>'ShowSessionModule'),
		'about_reason'=>array('file'=>'reason_info.php','class'=>'ReasonInfoModule'),
		'Test'=>array('file'=>'test.php','class'=>'TestModule'),
		'Sharing'=>array('file'=>'sharing.php','class'=>'SharingModule'),
		'Preview'=>array('file'=>'preview.php','class'=>'PreviewModule'),
		'Finish'=>array('file'=>'finish.php','class'=>'FinishModule'),
		'Cancel'=>array('file'=>'cancel.php','class'=>'CancelModule'),
		'NoDelete'=>array('file'=>'no_delete.php','class'=>'NoDeleteModule'),
		'ChooseTheme'=>array('file'=>'choose_theme.php','class'=>'ChooseThemeModule'),
		'ViewUsers'=>array('file'=>'view_users.php','class'=>'ViewUsersModule'),
		'Duplicate'=>array('file'=>'duplicate.php','class'=>'DuplicateModule'),
		'clone'=>array('file'=>'cloner.php','class'=>'ClonerModule'),
		'ThorData'=>array('file'=>'thor_data.php','class'=>'ThorDataModule'),
		'GroupTester'=>array('file'=>'group_tester.php','class'=>'GroupTesterModule'),
		'ListSites'=>array('file'=>'list_sites.php','class'=>'ListSitesModule'),
		'ListUnusedThemes'=>array('file'=>'list_unused_themes.php','class'=>'ListUnusedThemesModule'),
		'ImageImport'=>array('file'=>'image_import.php','class'=>'ImageImportModule'),
		'AllowableRelationshipManager'=>array('file'=>'allowable_relationship_manager.php','class'=>'AllowableRelationshipManagerModule'),
		'SortPosts'=>array('file'=>'sort_posts.php','class'=>'SortPostsModule'),
		'EntityInfo'=>array('file'=>'entity_info.php','class'=>'EntityInfoModule'),
		'BatchDelete'=>array('file'=>'batch_delete.php','class'=>'BatchDeleteModule'),
		'Export'=>array('file'=>'export.php','class'=>'ReasonExportModule'),
		'VersionCheck'=>array('file'=>'version_check.php','class'=>'ReasonVersionCheckModule'),
		'EventSplit'=>array('file'=>'event_split.php','class'=>'ReasonEventSplitModule'),
		'ActiveUsers'=>array('file'=>'active_users.php','class'=>'ReasonActiveUsersModule'),
		'ReviewChanges'=>array('file'=>'review_changes.php','class'=>'ReasonReviewChangesModule'),
		'SitePages'=>array('file'=>'site_pages.php','class'=>'ReasonSitePagesModule'),
		'ImageSizer'=>array('file'=>'image_sizer.php','class'=>'ImageSizerModule'),
		'OrphanManager'=>array('file'=>'orphan_manager.php','class'=>'OrphanManagerModule'),		
		'ManageLocks'=>array('file'=>'manage_locks.php','class'=>'ManageLocksModule'),
		'AdminTools'=>array('file'=>'admin_tools.php','class'=>'ReasonAdminToolsModule'),
		'ErrorVisibility'=>array('file'=>'error_visibility.php','class'=>'ErrorVisibilityModule'),	
		'KalturaMediaImagePicker'=>array('file'=>'media_work_image_picker_kaltura.php','class'=>'kalturaMediaWorkImagePickerModule'),
		'ZencoderMediaImagePicker'=>array('file'=>'media_work_image_picker_zencoder.php', 'class'=>'zencoderMediaWorkImagePickerModule'),
		'ZencoderMediaWorkUpdate'=>array('file'=>'pull_files_from_zencoder.php','class'=>'zencoderMediaWorkUpdateModule'),
		'MediaDownloadLinks'=>array('file'=>'media_work_download_links.php','class'=>'mediaWorkDownloadLinksModule'),
		'KalturaImport'=>array('file'=>'kaltura_import.php','class'=>'KalturaImportModule'),
		'MediaImport'=>array('file' => 'media_import.php', 'class'=>'MediaImportModule'),
		'CustomizeTheme'=>array('file'=>'customize_theme.php','class'=>'CustomizeThemeModule'),
		'SiteAccessDenied'=>array('file'=>'site_access_denied.php', 'class'=>'SiteAccessDeniedModule'),
		'Newsletter'=>array('file'=>'newsletter/newsletter.php', 'class'=>'NewsletterModule'),
		'DeleteRegistrationSlotData'=>array('file'=>'delete_slot_data.php', 'class'=>'DeleteRegistrationSlotDataModule'),
		'ClearCache'=>array('file'=>'clear_cache.php', 'class'=>'ReasonClearCacheModule'),
		'FormRecipients'=>array('file'=>'form_recipients.php', 'class'=>'FormRecipientsModule'),
		'Analytics'=>array('file'=>'analytics.php', 'class'=>'AnalyticsModule'),
		'AnalyticsAbout'=>array('file'=>'analytics.php', 'class'=>'AnalyticsAboutModule'),
		'ShareSiteOwnership'=>array('file'=>'share_site_ownership.php', 'class'=>'ShareSiteOwnershipModule'),
		'BorrowThis'=>array('file'=>'borrow_this.php', 'class'=>'BorrowThisModule'),
		'UserPosing'=>array('file'=>'user_posing.php', 'class'=>'UserPosingModule'),
	 	'CopySitePages'=>array('file'=>'copy_site_pages.php','class'=>'ReasonCopySitePagesModule'),
	 	'OldThemes'=>array('file'=>'old_themes.php','class'=>'OldThemesModule'),
	 	'SiteToPDF'=>array('file'=>'site_to_pdf.php','class'=>'ReasonSiteToPDFModule'),
	);

if (reason_file_exists('config/admin_modules/setup_local.php'))
{
	reason_include_once('config/admin_modules/setup_local.php');
	if(!empty($GLOBALS['_reason_admin_modules_local']))
	{
		$GLOBALS['_reason_admin_modules'] = array_merge($GLOBALS['_reason_admin_modules'],$GLOBALS['_reason_admin_modules_local']);
	}
}
?>
