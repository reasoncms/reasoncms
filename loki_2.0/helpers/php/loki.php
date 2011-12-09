<?php

if (!defined('DIRECTORY_SEPARATOR'))
	define('DIRECTORY_SEPARATOR', '/');

if (!defined('LOKI_2_PHP_INC')) {
	define('LOKI_2_PHP_INC', dirname(__FILE__).DIRECTORY_SEPARATOR.
		'inc'.DIRECTORY_SEPARATOR);
}

if (!defined('LOKI_2_PATH')) {
	if (defined('LOKI_2_INC')) {
		// old constant name
		define('LOKI_2_PATH', LOKI_2_INC);
	} else {
		guess_loki2_path();
	}
}

/**
 * Second generation of the Loki XHTML editor
 *
 * Takes care of building the (x)html to instantiate Loki
 * 
 * <strong>Minimal Usage:</strong>
 * <code>
 * $widgets = array('em','strong','ulist'); // or $widgets = 'all'; or $widgets = 'default'; etc.
 * $loki = new Loki2( 'field_name', 'field_value' );
 * $loki->print_form_children();
 * </code>
 *
 * <strong>More sophisticated usage:</strong>
 * <code>
 * $widgets = array('em','strong','ulist');
 * $admin = true; // for code editing
 * $loki = new Loki2( 'field_name', 'field_value', $widgets, $admin );
 * $loki->print_form_children();
 * </code>
 *
 * <strong>Very sophisticated usage -- integrated into a content management system:</strong>
 * <code>
 * $widgets = array('em','strong','ulist');
 * $admin = true; // for code editing
 * $paths = array('image_feed'=>'http://foo.com','site_feed'=>'http://bar.net','finder'=>'http://baz.edu','default_site_regexp'=>'http:\/\/foofoo.org\/','
 * $loki = new Loki2( 'field_name', 'field_value', $widgets, $admin );
 *
 * // see Loki's integration documentation for an explanation of the values in this area
 * $loki->set_feed('images','http://foo.com/image_feed.xml');
 * $loki->set_feed('sites','http://bar.net/site_feed.xml');
 * $loki->set_feed('finder','http://baz.edu/feed_finder.xml');
 * $loki->set_default_site_regexp('http:\/\/foofoo.org\/');
 * $loki->set_default_type_regexp('http:\/\/barbar.org\/');
 *
 * $loki->print_form_children();
 * </code>
 */

class Loki2
{
	var $_asset_path;
	var $_current_options;

	var $_field_name;
	var $_field_value;
	var $_editor_id;
	var $_editor_obj;
	var $_feeds = array();
	var $_default_site_regexp = '';
	var $_default_type_regexp = '';
	var $_sanitize_unsecured = false;
	var $_allowable_tags = null;
	var $_allowable_inline_styles = null;
	var $_external_script_path = null;
	var $_html_generator = null;
	var $_document_style_sheets = array();

	/**
	 * Constructor
	 *
	 * @param	string	$field_name		  How Loki is identified within its containing form. This will become the name of the textarea that Loki creates
	 *									  and therefore of the request variable received by the form's action.
	 * @param	string	$field_value	  The HTML that Loki will initially be editing.
	 * @param	string	$current_options  Indicates which buttons etc Loki should present to the user.
	 */
	function Loki2($field_name, $field_value='', $current_options=null)
	{
		if (!defined('LOKI_2_HTTP_PATH')) {
			trigger_error('The constant LOKI_2_HTTP_PATH must be defined '.
				'in order to instantiate a copy of the Loki2 editor.',
				E_USER_ERROR);
		}
		
		$this->_asset_protocol = $this->_get_protocol().'://';
		$this->_asset_host = $_SERVER['HTTP_HOST'];
		$this->_asset_path = ('/' != substr(LOKI_2_HTTP_PATH, -1, 1))
			? LOKI_2_HTTP_PATH.'/'
			: LOKI_2_HTTP_PATH;
		$this->_asset_uri = $this->_asset_protocol . $this->_asset_host . $this->_asset_path;
		$this->_asset_file_path = LOKI_2_PATH;
		$this->_current_options = $current_options;

		$this->_field_name = $field_name;
		$this->_set_field_value($field_value);
		$this->_editor_id = uniqid('loki');
		$this->_editor_obj = $this->_editor_id."_obj";
	}

	/**
	 * Sets the given feed.
	 *
	 * @param	string	$feed_name	The name of the feed.
	 * @param	string	$feed_url	The url of the feed.
	 */
	function set_feed($feed_name, $feed_url)
	{
		$this->_feeds[$feed_name] = $feed_url;
	}

	/**
	 * Sets the regular expression used by the link dialog to determine which site 
	 * to display as default when no link is selected.
	 *
	 * @param	string	$regexp The _Javascript_ regexp. You might want to use 
	 *							{@link js_regexp_quote()} to make things easier.
	 */
	function set_default_site_regexp($regexp)
	{
		$this->_default_site_regexp = $regexp;
	}

	/**
	 * Sets the regular expression used by the link dialog to determine which type 
	 * to display as default when no link is selected.
	 *
	 * @param	string	$regexp The _Javascript_ regexp. You might want to use 
	 *							{@link js_regexp_quote()} to make things easier.
	 */
	function set_default_type_regexp($regexp)
	{
		$this->_default_type_regexp = $regexp;
	}
	
	/**
	 * Sets whether or not Loki will sanitize embedded content the transmission
	 * of which is not SSL-secured by not displaying it in the editor.
	 * @param bool $value true to perform the sanitization, false if otherwise
	 * @return void
	 */
	function sanitize_unsecured($value)
	{
		$this->_sanitize_unsecured = (bool) $value;
	}
	
	/**
	 * Adds style sheets to the editing document.
	 * @param mixed $path either the path to a CSS file to include, or an array
	 * of them
	 * @return void
	 */
	function add_document_style_sheets($path)
	{
		$path_arrays = func_get_args();
		
		foreach ($path_arrays as $paths) {
			foreach (((array) $paths) as $path) {
				$this->_document_style_sheets[] = $path;
			}
		}
	}
	
	/**
	 * Sets the HTML generator to use with Loki.
	 * @param string $generator the HTML generator to use
	 * @return void
	 */
	function set_html_generator($generator)
	{
		$this->_html_generator = $generator;
	}

	/**
	 * Prints the html which needs to be placed within a form.
	 *
	 * @param $rows number of rows 
	 * @parm $cols number of columns
	 */
	function print_form_children($rows = 20, $cols = 80)
	{
		$id = $this->_editor_id;
		$onload = $this->_editor_id.'_do_onload';
		
		$this->include_js();
		?>

		<!--div><a href="javascript:void(0);" onclick="Util.Window.alert(document.body.innerHTML);">View virtual source</a></div-->
		<script type="text/javascript" language="javascript">
		//document.domain = 'carleton.edu'; /// XXX: for testing; maybe remove later if not necessary
		var <?php echo $id ?>;
		function <?php echo $onload ?>()
		{
			if (!UI || !Util) {
				throw new Error("The Loki code does not appear to be loaded " +
					"on the document; make sure that loki.js is being " +
					"included.");
			}
			
			if (<?php echo $id ?>) {
				return;
			}
			
			<?php echo $id ?> = new UI.Loki;
			<?php $settings = $this->_get_js_settings_object(); ?>
			
			var settings = <?php echo _js_serialize($settings) ?>;
			
			<?php echo $id ?>.init(document.getElementById('loki__<?php echo $this->_field_name; ?>__textarea'), settings);
		}
		
		if (Util && Util.Event && Util.Event.observe) {
			Util.Event.observe(document, 'DOMContentLoaded', <?php echo $onload ?>);
			Util.Event.observe(window, 'load', <?php echo $onload ?>);
		} else if (window.addEventListener) {
			document.addEventListener('DOMContentLoaded', <?php echo $onload ?>, false);
			window.addEventListener('load', <?php echo $onload ?>, false);
		} else if (window.attachEvent) {
			window.attachEvent('onload', <?php echo $onload ?>);
		} else {
			if (Util && Util.Unsupported_Error) {
				throw new Util.Unsupported_Error('modern event API\'s');
			} else {
				throw new Error("No known modern event API is available.");
			}
		}
		</script>
		<?php /* we htmlspecialchars because Mozilla converts all greater and less than signs in the textarea to entities, but doesn't convert amperstands to entities. When the value of the textarea is copied into the iframe, these entities are resolved, so as to create tags ... but then so are greater and less than signs that were originally entity'd. This is not desirable, and in particular allows people to add their own HTML tags, which is bad bad bad. */ ?>
		<textarea name="<?php echo $this->_field_name; ?>" rows="<?php echo htmlspecialchars($rows); ?>" cols="<?php echo htmlspecialchars($cols); ?>" 
			id="loki__<?php echo $this->_field_name; ?>__textarea"><?php echo htmlentities($this->_field_value, ENT_QUOTES, 'UTF-8'); ?></textarea>
		<?php

	}
	
	/**
	 * Include all JavaScript files.
	 *
	 * NOTE: This function will only run once per page generation so as not to bloat the page.
	 * it is important to make sure method contains only the hooks to the general Loki 
	 * libraries, and nothing else, because a single block of javascript is used for all Loki 
	 * instances on a page.
	 *
	 * NOTE: static vars in a method are shared by all objects in PHP
	 * we are depending on this fact to make sure that only the first Loki object spits 
	 * out the Loki js.
	 *
	 * @param	mode	Defaults to 'static', which uses the prebuilt script
	 *					file that ships with Loki. Other modes are only useful
	 *					for Loki testing and only work when paired with a
	 *					source distribution or Subversion checkout of Loki.
	 * @param	path	For the 'external' mode, specifies the HTTP path to the
	 *					Loki script aggregator. If this is not specified,
	 *					the path will be guessed based on the default Loki
	 *					directory layout.
	 */
	function include_js($mode=null, $path=null)
	{
		static $loki_js_has_been_included = false;

		if(!$loki_js_has_been_included)
		{
			// Set up hidden iframe for clipboard operations
			$priv_jar = 'jar:'.$this->_asset_protocol.$this->_asset_host.
				$this->_asset_path.'auxil/privileged.jar!/gecko_clipboard.html';

			?>
			<script type="text/javascript">
				var _gecko_clipboard_helper_src = '<?php echo $priv_jar ?>';
				UI__Clipboard_Helper_Editable_Iframe__src = '<?php echo $this->_asset_protocol . $this->_asset_host . $this->_asset_path; ?>auxil/loki_blank.html';
			</script>
			<?php

			$mode = ($mode) ? strtolower($mode) : 'static';

			if ($mode == 'static') {
				if (!$path) {
					$path = $this->_asset_path.'loki.js';
				}

				echo '<script type="text/javascript" charset="utf-8" language="javascript" src="'.$path.'">',
					"</script>\n";
			} else if ($mode == 'debug') {
				$files = $this->_get_js_files();
				$base = $this->_asset_path.'js';
				if (!$files)
					return false;
				
				foreach ($files as $filename) {
					echo '<script type="text/javascript" '.
						'src="'.$base.$filename.'" charset="utf-8"></script>';
				}
			} else if ($mode == 'external') {
				if (!$path) {
					$path = $this->_asset_path.
						'helpers/php/loki_editor_scripts.php';
				}
				
				echo '<script type="text/javascript" src="'.$path.'" charset="utf-8">',
					"</script>\n";
			} else if ($mode == 'inline') {
				$files = $this->_get_js_files();
				$base = $this->_asset_file_path.'js';
				if (!$files)
					return false;
				
				echo '<script type="text/javascript" charset="utf-8">', "\n";
				foreach ($files as $filename) {
					echo "\n// file $file \n\n";
					readfile($base.$filename);
				}
			} else {
				user_error('Unknown Loki JS inclusion mode "'.$mode.'". '.
					'Cannot load Loki\'s JavaScript.', E_USER_WARNING);
				return false;
			}
			
			$loki_js_has_been_included = true;
		}
		
		return true;
	}

	/**
	 * Quotes a javascript regular expression.
	 *
	 * @param	string	$s	The unquoted regexp.
	 * @return	string		The quoted regexp.
	 */
	function js_regexp_quote($s)
	{
		$specials_pat = '/(\/|\.|\*|\+|\?|\||\(|\)|\[|\]|\{|\}|\\\\)/';
		return preg_replace($specials_pat, '\\\\\1', $s);
	}

	/**
	 * Gets the field's value.
	 * @return	string	The value of the Loki-ized field (before being edited).
	 */
	function get_field_value()
	{ 
		return $this->_field_value;
	}

	/**
	 * Sets the field's value.
	 * @param  string  $field_value	 The value of the Loki-ized field (before being edited).
	 */
	function _set_field_value($field_value) 
	{
		$this->_field_value = $field_value;
	}
	
	function _get_js_files($source=null)
	{
		if (!$source)
			$source = $this->_asset_file_path.'js';
		
		$finder = new Loki2ScriptFinder($source);
		return $finder->files;
	}
	
	/**
	 * Sets the list of HTML tags allowed to exist in Loki output.
	 * @param array $tags
	 * @return void
	 */
	function set_allowable_tags($tags)
	{
		$this->_allowable_tags = $tags;
	}
	
	/**
	 * Sets the list of inline styles allowed to exist in Loki output.
	 * @param array $styles the CSS names of the allowed styles
	 * @return void
	 */
	function set_allowable_inline_styles($styles)
	{
		$this->_allowable_inline_styles = $styles;
	}
	
	/**
	 * @access private
	 * @return string
	 */
	function _get_protocol()
	{
		if (!empty($_SERVER['SCRIPT_URI'])) {
			$proto_pos = strpos($_SERVER['SCRIPT_URI'], ':');
			if (false !== $proto_pos)
				return substr($_SERVER['SCRIPT_URI'], 0, $proto_pos);
		}
		
		if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
			return 'https';
		
		// Make a (very) good guess.
		return 'http';
	}
	
	function _get_js_settings_object()
	{
		$options = $this->_current_options;
		
		$s = new stdClass; // create an anonymous object
		
		if ($this->_asset_path)
			$s->base_uri = $this->_asset_path;
		if ($options)
			$s->options = $options;
		
		foreach (array('images', 'sites', 'finder') as $f) {
			if (!empty($this->_feeds[$f]))
			$s->{$f.'_feed'} = $this->_feeds[$f];
		}
		if ($this->_default_site_regexp)
			$s->default_site_regexp = $this->_default_site_regexp;
		if ($this->_default_type_regexp)
			$s->default_type_regexp = $this->_default_type_regexp;
		$s->use_xhtml = true;
		if ($this->_sanitize_unsecured)
			$s->sanitize_unsecured = $this->_sanitize_unsecured;
		if ($this->_document_style_sheets)
			$s->document_style_sheets = $this->_document_style_sheets;
		if ($this->_allowable_tags)
			$s->allowable_tags = $this->_allowable_tags;
		if ($this->_allowable_inline_styles)
			$s->allowable_inline_styles = $this->_allowable_inline_styles;
		if ($this->_html_generator)
			$s->html_generator = $this->_html_generator;
		
		return $s;
	}
}

/** @ignore */
function guess_loki2_path()
{
	if (defined('LOKI_2_PATH'))
		return;
	
	$php_helper = dirname(__FILE__);
	if (basename($php_helper) == 'php') {
		$helpers = dirname($php_helper);
		if (basename($helpers) == 'helpers') {
			define('LOKI_2_PATH', dirname($helpers).DIRECTORY_SEPARATOR);
			return;
		}
	}
	
	user_error('Cannot automatically determine the path to Loki 2; please '.
		'define the LOKI_2_PATH constant.', E_USER_ERROR);
}

/** @ignore */
function _js_serialize($item)
{
	if (is_scalar($item)) {
		if (is_string($item)) {
			return '"'.addslashes($item).'"';
		} else if (is_numeric($item)) {
			return $item;
		} else if (is_bool($item)) {
			return ($item) ? 'true' : 'false';
		} else {
			trigger_error('Unknown scalar type "'.gettype($item).'".',
				E_USER_WARNING);
			return 'undefined';
		}
	} else {
		if (is_null($item)) {
			return 'null';
		} else if (is_array($item)) {
			return '['.implode(', ',
				array_map('_js_serialize', $item)).']';
		} else if (is_object($item)) {
			$repr = '{';
			$first = true;
			foreach ((array) $item as $k => $v) {
				if ($first)
					$first = false;
				else
					$repr .= ', ';
				
				$repr .= "'".addslashes($k)."': "._js_serialize($v);
			}
			$repr .= '}';
			return $repr;
		} else {
			trigger_error('Unknown non-scalar type "'.gettype($item).'".',
				E_USER_WARNING);
			return 'undefined';
		}
	}
}

?>