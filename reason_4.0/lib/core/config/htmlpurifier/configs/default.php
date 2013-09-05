<?php
/**
 * @package reason
 * @subpackage config
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'config/htmlpurifier/configs/abstract.php' );

/**
 * Register config with Reason CMS
 */
$GLOBALS['_reason_htmlpurifier_config_class'][ basename(__FILE__, '.php') ] = 'ReasonDefaultHTMLPurifierConfig';

/**
 * Default HTMLPurifier configuration for Reason CMS.
 *
 * The default config makes the following changes from the default.
 *
 * - Enables id attribute.
 * - Allows _blank as a target.
 * - Allows iframes that match certain regexps.
 * 
 * @author Nathan White
 */
class ReasonDefaultHTMLPurifierConfig extends ReasonAbstractHTMLPurifierConfig
{
	/**
	 * @param int $revision should be incremented anytime a configuration changes.
	 */
	protected $revision = 1;

	/**
	 * @param boolean $allow_blank_target should blank targets be allowed?
	 */
	protected $allow_blank_target = true;

	/**
	 * @param boolean $enable_id should id be an allowed attribute?
	 */
	protected $enable_id = true;
	
	/**
	 * @param boolean $allow_html5_tags should i modify the defaults to allow most html5 tags?
	 */
	protected $allow_html5_tags = true;
		
	/**
	 * @param int $default_config_revision is the version of this default config file - should be incremented if changes to HMTL Purifier definitions happen here.
	 */
	private $default_config_revision = 1;
	
	/**
	 * @param object config HTMLPurifier config object
	 */
	final function alter_config($config)
	{
 		$config->set('HTML.DefinitionRev', $this->get_revision());
		if ($this->enable_id) $config->set('Attr.EnableID', true);
		if ($this->allow_blank_target) $config->set('Attr.AllowedFrameTargets', array('_blank'));
		if ($this->allow_html5_tags) $this->add_html5_tags($config);
 	}
 	
 	/**
 	 * This adds various HTML5 tags to what we allow through HTML Purifier, which by default just allows XHTML tags.
 	 *
 	 * https://gist.github.com/lluchs/3303693
 	 */
 	protected function add_html5_tags($config)
 	{
 		if ($def = $config->maybeGetRawHTMLDefinition())
 		{
			// http://developers.whatwg.org/sections.html
			$def->addElement('section', 'Block', 'Flow', 'Common');
			$def->addElement('nav', 'Block', 'Flow', 'Common');
			$def->addElement('article', 'Block', 'Flow', 'Common');
			$def->addElement('aside', 'Block', 'Flow', 'Common');
			$def->addElement('header', 'Block', 'Flow', 'Common');
			$def->addElement('footer', 'Block', 'Flow', 'Common');
 
			// Content model actually excludes several tags, not modelled here
			$def->addElement('address', 'Block', 'Flow', 'Common');
 
			$def->addElement('hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common');
 
			// http://developers.whatwg.org/grouping-content.html
			$def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
			$def->addElement('figcaption', 'Inline', 'Flow', 'Common');
 
			// http://developers.whatwg.org/text-level-semantics.html
			$def->addElement('s', 'Inline', 'Inline', 'Common');
			$def->addElement('var', 'Inline', 'Inline', 'Common');
			$def->addElement('sub', 'Inline', 'Inline', 'Common');
			$def->addElement('sup', 'Inline', 'Inline', 'Common');
			$def->addElement('mark', 'Inline', 'Inline', 'Common');
			$def->addElement('wbr', 'Inline', 'Empty', 'Core');
 
			// http://developers.whatwg.org/edits.html
			$def->addElement('ins', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA'));
			$def->addElement('del', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA'));
		}
	}
	
	/**
	 * You may customize the configuration in this method.
	 *
	 * @param object config HTMLPurifier config object
	 */
 	protected function custom_config($config)
 	{
 	
 	}
 	
 	final function get_revision()
 	{
 		return $this->revision + $this->default_config_revision;
 	}
}
