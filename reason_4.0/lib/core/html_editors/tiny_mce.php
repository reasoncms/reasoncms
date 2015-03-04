<?php
/**
 * @package reason
 * @subpackage html_editors
 * @author Nathan White
 * @author Matt Ryan
 */
reason_include('html_editors/base.php');

// Identify the class that should be used
$GLOBALS[ '_reason_editor_integration_classes' ][ basename( __FILE__) ] = 'reasonTinyMCEIntegration';

/**
 * An editor integration class for TinyMCE
 *
 * We should implement in config named tinyMCE defaults, including the basic ones that we have used in the past:
 *
 * - notables
 * - default
 * - all
 * - all_minus_pre
 * - notables_plus_pre
 *
 * For now we basically just hard code the options.
 *
 * @todo implement config options for TinyMCE
 */
class reasonTinyMCEIntegration extends reasonEditorIntegrationBase
{
	/**
	 * Get the name of the plasmature element that should be used for this editor
	 * @return string name of the plasmature element
	 */
	function get_plasmature_type()
	{
		return 'tiny_mce';
	}
	
	/**
	 * Get the appropriate parameters to pass to the plasmature element
	 * @param integer $site_id The Reason id of the site in which this editor is being invoked
	 * @param integer $user_id The Reason id of the current user (0 if user is anonymous or not in the Reason user store)
	 * @return array plasmature parameters 
	 */
	function get_plasmature_element_parameters($site_id, $user_id = 0)
	{
		$param['rows'] = 20;
		$param['external_css'][] = REASON_HTTP_BASE_PATH . 'tinymce/css/external.css';
		$param['init_options']['content_css'] = $this->get_content_css_path();
		$site = new entity($site_id);
		$loki_default = $site->get_value( 'loki_default' );
		
		$config = (!empty($loki_default) && in_array($loki_default, array_keys($this->get_configuration_options()))) ? $loki_default : 'notables';	
		$imagetoolbar = ($this->reason_plugins_available($user_id)) ? 'reasonimage' : 'image';
		
		/* these will need to change when the link plugin is available */
		$linkplugin = ($this->reason_plugins_available($user_id)) ? 'link' : 'link';
		$linktoolbar = ($this->reason_plugins_available($user_id)) ? 'reasonlink' : 'link';
		$blockformats = ($config == 'notables_plus_pre' || $config == 'all')
		              ? 'Paragraph=p;Header 1=h3;Header 2=h4;Pre=pre'
		              : 'Paragraph=p;Header 1=h3;Header 2=h4';
		$tabletoolbar = ($config == 'all' || $config == 'all_minus_pre') ? '|,table,' : '';
		$cutcopypaste = ($config == 'default' || $config == 'all' || $config == 'all_minus_pre') ? '|,cut,copy,paste,' : '';
		
		/* load the basic reason image and link plug in options */
		if ($this->reason_plugins_available($user_id))
		{
			$param['init_options']['reason_site_id'] = $site_id;
			$param['init_options']['reason_http_base_path'] = REASON_HTTP_BASE_PATH;	
		}
		$param['init_options']['toolbar1'] = 'formatselect,|,bold,italic,|,hr,'.$cutcopypaste.'|,blockquote,|,numlist,bullist,|,indent,outdent,'.$tabletoolbar.'|,'.$imagetoolbar.',|,'.$linktoolbar.',unlink,|,anchor';
		$param['init_options']['plugins'] = 'anchor,link,paste,advlist,lists';
		if ($this->reason_plugins_available($user_id))
		{
			$param['init_options']['external_plugins'] = '{ "reasonintegration": "' . REASON_HTTP_BASE_PATH . 'tinymce/plugins/reasonintegration/plugin.js" }';
		}
		else
		{
			 $param['init_options']['plugins'] .= ',image';
		}
		$param['init_options']['block_formats'] = $blockformats;
		if (!empty($tabletoolbar))
		{
			$param['init_options']['plugins'] = 'contextmenu,table,' . $param['init_options']['plugins'];
			$param['init_options']['contextmenu'] = 'inserttable | cell row column deletetable';
		}
		if (reason_user_has_privs($user_id, 'edit_html'))
		{
			$param['init_options']['toolbar1'] .= ',|,code';
			$param['init_options']['plugins'] .= ',code';
		}
		return $param;
	}
	
	/**
	 * Returns the path in REASON_TINYMCE_CONTENT_CSS_PATH or a default path. 
	 */
	function get_content_css_path()
	{
		if (defined("REASON_TINYMCE_CONTENT_CSS_PATH"))
		{
			return REASON_TINYMCE_CONTENT_CSS_PATH;
		}
		else return REASON_HTTP_BASE_PATH . 'tinymce/css/content.css';
	}
	
	/**
	 * If the user_id is a valid reason user entity, we load our plugins.
	 *
	 * @return boolean
	 */
	function reason_plugins_available($user_id)
	{
		if (!isset($this->_reason_plugins_available))
		{
			if( !empty($user_id) )
			{
				$user = new entity($user_id);
				$this->_reason_plugins_available = reason_is_entity($user, 'user');	
			}
			else $this->_reason_plugins_available = false;
		}
		return $this->_reason_plugins_available;
	}
	
	/**
	 * Get the available configuration options for the editor
	 *
	 * These options are presented to administrators when setting up a Reason site
	 * Each option must be represented as a string <= 256 bytes, since it is stored in a tinytext field in the database
	 *
	 * @return array keys are values to be stored in the db and can then be used by @get_plasmature_element_parameters() when setting up the plasmature element, values are labels that are presented to the administrator
	 */
	function get_configuration_options()
	{
		return array(
			'notables' => 'Standard (All minus Tables &amp; Pre)',
			'default' => 'TinyMCE Default Set',
			'all' => 'Most (no underline)',
			'all_minus_pre' => 'Most minus Pre',
			'notables_plus_pre' => 'Most minus Tables',
		);
	}
}
?>
