<?php
/**
 * Abstract HTMLPurifier config
 *
 * Our abstract class takes care of this
 * 
 * - gets a default configuration from HTMLPurifier
 * - Sets our cache path if we have one defined
 * - Gives the config a DefinitionID with the name of the instantiated class
 *
 * It passes the configuration to alter_config, which must be implemented by any descendant HTMLPurifier configs.
 *
 * For most use cases, you should extend ReasonDefaultHTMLPurifierConfig, which makes the most common customizations trivial,
 * but you can also extend this and implement all of alter_config yourself.
 *
 * @author Nathan White
 * @package reason 
 * @subpackage config
 */
abstract class ReasonAbstractHTMLPurifierConfig
{
	final function __construct()
	{
 		$this->config = HTMLPurifier_Config::createDefault();
 		if (defined("HTMLPURIFIER_CACHE")) $this->config->set('Cache.SerializerPath', HTMLPURIFIER_CACHE);
 		$this->config->set('HTML.DefinitionID', get_class($this));
 		$this->alter_config($this->config);
 	}
 	
 	abstract function alter_config($config);
 	
 	final function get_config()
 	{
 		return $this->config;
 	}
}