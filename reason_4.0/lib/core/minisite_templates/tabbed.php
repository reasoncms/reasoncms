<?php

/**
 * Tabbed Template
 * @package reason
 * @subpackage minisite_templates
 */

// include the MinisiteTemplate class
reason_include_once( 'minisite_templates/default.php' );
// this variable must be the same as the class name
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'tabbedTemplate';

reason_include_once( 'minisite_templates/nav_classes/basic_tabs.php' );

/**
 * Tabbed template
 *
 * Uses the Basic Tabs Navigation to provide the top level of site as tabs
 *
 * @author Matt Ryan
 */
class tabbedTemplate extends MinisiteTemplate
{
	var $nav_class = 'BasicTabsNavigation';
}