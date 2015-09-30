<?php

/**
 * HTML editor type library.
 *
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";
require_once PLASMATURE_TYPES_INC."text.php";

/**
 * Edit HTML using a Loki 2 editor.
 * @package disco
 * @subpackage plasmature
 */
class loki2Type extends defaultType
{
	var $type = 'loki2';
	var $widgets = 'default';
	var $site_id = 0;
	var $paths = array();
	var $allowable_tags = array();
	
	/**
	 * Exists for backwards compatibility with Loki 1
	 *
	 * Proper method to use now is to just pass the source option as a a widget, or not
	 * @deprecated
	 */
	var $user_is_admin;
	var $crash_report_uri;
	/**
	 * Allow for a custom sized text entry box
	 */
	var $rows = 20;
	var $cols = 80;
	var $type_valid_args = array('widgets', 'site_id', 'paths', 'allowable_tags', 'user_is_admin', 'crash_report_uri', 'rows', 'cols');
	function do_includes()
	{
		if (file_exists( LOKI_2_INC.'loki.php' ))
		{
			include_once( LOKI_2_INC.'loki.php' );
		}
		else
		{
			trigger_error('Loki 2 file structure has changed slightly. Please update LOKI_2_INC in package_settings.php to reference the ' . LOKI_2_INC . '/helpers/php/ directory.');
			include_once( LOKI_2_INC.'/helpers/php/inc/options.php' );
		}
	}
	function grab()
	{
		$http_vars = $this->get_request();
		if ( isset( $http_vars[ $this->name ] ) )
		{
			$val = tidy( $http_vars[ $this->name ] );
			if( empty( $val ) )
			{
				$tidy_err = tidy_err( $http_vars[ $this->name ] );
				if( !empty($tidy_err) )
				{
					$tidy_err = nl2br( htmlentities( $tidy_err,ENT_QUOTES,'UTF-8' ) );
					$this->set_error( 'Your HTML appears to be ill-formatted.  Here is what Tidy has to say about it: <br />'.$tidy_err );
					$this->set( $http_vars[ $this->name ] );
				}
				else
					$this->set( $val );
			}
			else
			{
				// this looks like a hack. We could look into removing it.
				// $val = eregi_replace("</table>\n\n<br />\n<br />\n","</table>\n", $val);
				$this->set( $val );
			}
		}
		$length = strlen( $this->value );
		if( ($this->db_type == 'tinytext' AND $length > 255) OR ($this->db_type == 'text' AND $length > 65535) OR ($this->db_type == 'mediumtext' AND $length > 16777215) )
			$this->set_error( 'There is more text in '.$this->display_name.' than can be stored ' );
	}
	function display()
	{
		$loki = new Loki2( $this->name, $this->value, $this->_resolve_widgets($this->widgets) );
		if(!empty($this->paths['image_feed']))
		{
			$loki->set_feed('images',$this->paths['image_feed']);
		}
		if(!empty($this->paths['site_feed']))
		{
			$loki->set_feed('sites',$this->paths['site_feed']);
		}
		if(!empty($this->paths['finder_feed']))
		{
			$loki->set_feed('finder',$this->paths['finder_feed']);
		}
		if(!empty($this->paths['default_site_regexp']))
		{
			$loki->set_default_site_regexp($this->paths['default_site_regexp']);
		}
		if(!empty($this->paths['default_type_regexp']))
		{
			$loki->set_default_type_regexp($this->paths['default_type_regexp']);
		}
		if(!empty($this->paths['css']))
		{
			$loki->add_document_style_sheets($this->paths['css']);
		}
		if(!empty($this->allowable_tags))
		{
			$loki->set_allowable_tags($this->allowable_tags);
		}
		if(!empty($this->crash_report_uri))
		{
			$loki->set_crash_report_uri($this->crash_report_uri);
		}
		$loki->print_form_children($this->rows, $this->cols);
	}
	function _resolve_widgets($widgets)
	{
		$widgets = $this->_flatten_widgets($widgets);
		if($this->user_is_admin)
		{
			$widgets .= ' +source +debug';
		}
		elseif($this->user_is_admin === false)
		{
			$widgets .= ' -source -debug';
		}
		return $widgets;
	}
	function _flatten_widgets($widgets)
	{
		if(is_array($widgets))
			return implode(' ',$widgets);
		else
			return $widgets;
	}
}

/**
 * Edit HTML using the TinyMCE editor modern (lightgray) theme.
 *
 * These are the type valid args you should use:
 *
 * - rows
 * - cols
 * - external_css
 * - plugins
 * - init_options
 *
 * Init options supports everything TinyMCE supports - we setup defaults in $this->base_init_options.
 *
 * For backwards compatibility we support these legacy type_valid_args. We set TinyMCE 4 base_init_options based upon them.
 *
 * - buttons
 * - buttons1
 * - buttons2
 * - formatselect_options
 * - content_css
 *
 * @todo do we need to add db_type error checks like we have in Loki?
 * @todo do we need to tidy here if core sanitization is off?
 * @todo would be great to integrate with head items instead of adding JS and CSS inline.
 *
 * @package disco
 * @subpackage plasmature
 */
class tiny_mceType extends textareaType
{
	var $type = 'tiny_mce';
	var $type_valid_args = array('rows',
								 'cols',
								 'external_css',
								 'external_js',
								 'init_options',
								 'buttons', // legacy
								 'buttons2', // legacy
								 'buttons3', // legacy
								 'formatselect_options', // legacy
								 'content_css', // legacy
								);
	/**
	 * @param int number of rows to display
	 */
	var $rows = 20;
	
	/**
	 * @param int number of columns to display
	 */
	var $cols = 80;

	/**
	 * @param array of paths (relative to server root) of CSS files to load before TinyMCE inits.
	 */
	protected $external_css = array();

	/**
	 * @param array of paths (relative to server root) of JS files to load after TinyMCE loads but before init.
	 */
	protected $external_js = array();
	
	/**
	 * @param array containing TinyMCE init options in addition or to override base_init_options
	 */
	protected $init_options = array();
	
	/**
	 * @param array deprecated
	 */						
	protected $buttons = array();

	/**
	 * @param array deprecated
	 */
	protected $buttons2 = array();

	/**
	 * @param array deprecated
	 */
	protected $buttons3 = array();

	/**
	 * @param array deprecated
	 */
	protected $formatselect_options;
	
	/**
	 * @param array deprecated this should be provided within init_options
	 */
	protected $content_css;

	/**
	 * $param array basic set of options for tinyMCE - init_options can override or add to this.
	 */	
	private $base_init_options = array(
		'mode' => 'exact',
		'toolbar1' => 'formatselect,bold,italic,hr,blockquote,numlist,bullist,indent,outdent,image,link,unlink,anchor',
		'plugins' => 'anchor,link,paste,hr',
		'dialog_type' => 'modal',
		'theme' => 'modern',
		'convert_urls' => false,
		'menubar' => false,
		'block_formats' => "Paragraph=p;Header 1=h3;Header 2=h4",
	);
	
	/**
	 * We set tinyMCE content_css to the UNIVERSAL_CSS_PATH by default.
	 */
	function __construct()
	{
		$this->base_init_options['content_css'] = UNIVERSAL_CSS_PATH;
	}
	
	function display()
	{
		$this->transform_deprecated_options();
		$display = $this->get_tiny_mce_javascript();
		$display .= $this->get_tiny_mce_external_css();
		$display .= '<script language="javascript" type="text/javascript">'."\n";
		$display .= $this->get_tiny_mce_init_string();
		$display .= '</script>'."\n";
		echo $display;
		parent::display();
	}
	
	/**
	 * TinyMCE 3 handled some configuration differently and we used more type_valid_args. We handle this gracefully.
	 *
	 * - If formatselect_options were provided, transform them gracefully.
	 * - If buttons, buttons2, or buttons3 was used, populate the appropriate toolbar init option. 
	 * 
	 * This method alters base_init_options.
	 * 
	 * @return void
	 */
	function transform_deprecated_options()
	{
		if ($format_select = $this->get_class_var('formatselect_options'))
		{
			$format_to_block_map = array(
				'p' => 'Paragraph',
				'address' => 'Address',
				'pre' => 'Pre',
				'h1' => 'Header 1',
				'h2' => 'Header 2',
				'h3' => 'Header 3',
				'h4' => 'Header 4',
				'h5' => 'Header 5',
				'h6' => 'Header 6',
			);
			foreach ($format_select as $v)
			{
				if (isset($format_to_block_map[$v]))
				{
					$block_formats[] = $format_to_block_map[$v].'='.$v;
				}
				else $block_formats[] = $v.'='.$v;
			}
			$this->base_init_options['block_formats'] = implode(";",$block_formats);
		}
		
		if ($buttons = $this->get_class_var('buttons'))
		{
			$this->base_init_options['toolbar1'] = implode(" ",$buttons);
		}
		if ($buttons2 = $this->get_class_var('buttons2'))
		{
			$this->base_init_options['toolbar2'] = implode(" ",$buttons2);
		}
		if ($buttons3 = $this->get_class_var('buttons3'))
		{
			$this->base_init_options['toolbar3'] = implode(" ",$buttons3);
		}
	}
	
	/**
	 * Generate TinyMCE init string from the combination of base_init_options and init_options.
	 *
	 * The items in our init options arrays are generally treated as strings with three exceptions:
	 *
	 * - We assume values starting with [ or { are JSON and do not add quotes.
	 * - We do not add quotes to integers.
	 * - We do not add quotes to boolean values - tinyMCE init will treat true and "true" differently.
	 *
	 * @return string
	 */
	function get_tiny_mce_init_string()
	{	
		$options = $this->base_init_options;
		//$options['elements'] = $this->name; 
		$options['selector'] = 'textarea[name='.$this->name.']';
		
		// Merge in custom options
		foreach($this->init_options as $option => $val) $options[$option] = $val;
		
		// Format the options
		foreach ($options as $option => $val)
		{
			// support configuration params that expect a json object or pure integer
			if (is_int($val) || (!empty($val) && ((substr($val, 0, 1) == '[') || (substr($val, 0, 1) == '{'))))
			{
				$parts[] = sprintf('%s : %s', $option, $val);
			}
			else if (is_bool($val)) // handle booleans
			{
				$strval = ($val) ? 'true' : 'false';
				$parts[] = sprintf('%s : %s', $option, $strval);
			}
			else if (strpos($val, 'function(') === 0) // functions
			{
				$parts[] = sprintf('%s : %s', $option, $val);
			}
			else // default for strings
			{
				$parts[] = sprintf('%s : "%s"', $option, $val);
			}
		}
		return 'tinymce.init({'."\n" . implode(",\n", $parts) . "\n});\n";
	}

	/**
	 * We return the main javascript for TinyMCE and any external javascript - we use a static variable to keep track such that we include it only once.
	 *
	 * @return string
	 */
	function get_tiny_mce_javascript()
	{
		// we only want to load the main js file once.
		static $loaded_an_instance;
		if (!isset($loaded_an_instance))
		{
			$js = '<script language="javascript" type="text/javascript" src="'.TINYMCE_HTTP_PATH.'tinymce.min.js"></script>'."\n";
			$external_js = $this->get_class_var('external_js');
			if (!empty($external_js))
			{
				foreach ($external_js as $js_file)
				{
					$js .= '<script language="javascript" type="text/javascript" src="'.$js_file.'"></script>'."\n";
				}
			}
			$loaded_an_instance = true;
		}
		return (!empty($js)) ? $js : '';
	}
	
	/**
	 * If a css path was provided to tinyMCE, then load it. Note any file here is used to style aspects of tinyMCE
	 * other than the content area. If you want to style content within TinyMCE's content area, use the tinyMCE
	 * content_css config option.
	 *
	 * @return string
	 */
	function get_tiny_mce_external_css()
	{
		// we only want to load this extra css declaration once.
		static $loaded_css;
		if (!isset($loaded_css))
		{
			$external_css = $this->get_class_var('external_css');
			if (!empty($external_css))
			{
				$css = '';
				foreach ($external_css as $css_file)
				{
					$css .= '<link rel="stylesheet" type="text/css" href="' . $css_file . '" />'."\n";
				}
			}
			$loaded_css = true;
		}
		return (!empty($css)) ? $css : '';
	}
}

/**
 * Edit HTML using an unlabeled TinyMCE editor.
 * @package disco
 * @subpackage plasmature
 */
class tiny_mce_no_labelType extends tiny_mceType // {{{
{
	var $_labeled = false;
}
