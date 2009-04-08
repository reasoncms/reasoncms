<?php
/**
 * The publication migrator helps transition sites using old style news to use
 * the publications module.
 *
 * It basically works as follows:
 * 
 * - Identify sites using page types from the old publications framework
 * - Screen 1: Allow selection of a site to "migrate."
 * - Screen 2: Allow association of news items, issues, and sections to an existing or new publication.
 * - Screen 3: Map known page types from old page type to new page type and relate publication.
 *
 * Not all page types can be known, and some sites will require some manual work to migrate.
 * Even for sites that require manual work, it may be worthwhile to extend this tool to handle them...
 *
 * For extension, look to the PublicationMigratorHelper.
 *
 * @author Nathan White
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(DISCO_INC . 'disco.php');
reason_include_once('scripts/developer_tools/publication_migrator/publication_migrator_helper.php');

/**
 * Instantiate & run relevant classes
 */
$pmg = new PublicationMigratorHelper();
$pmg->init();
$pmg->run();
?>