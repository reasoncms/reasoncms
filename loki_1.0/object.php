<?php
include_once('paths.php');
include_once(LOKI_INC.'lokiOptions.php3'); // so we can get L_DEFAULT etc.
include_once(CARL_UTIL_INC . 'tidy/tidy.php');

class Loki
{
	var $_editor_version;
	var $_asset_location;
	var $_current_options;
	var $_p_rules;

	var $_clean_brs_callback_tmp_count = 0;

	var $_field_name;
	var $_field_value;
	var $_site_id;
	var $_editor_id;
	var $_editor_obj;
	var $_user_is_admin;

	/////////////////////////////////////////////////////////
	//
	//  Loki::Loki
	//
	/////////////////////////////////////////////////////////

	function Loki( $field_name, $field_value = '', $current_options = 'default', $site_id = -1, $user_is_admin = false )
	{
		$this->_editor_version = $this->_get_editor_version();
		$this->_asset_location = LOKI_HTTP_PATH;
		//$this->_asset_location = '/fillmorn/hel-loki/';
		$this->_current_options = $current_options;
		$this->_p_rules = $this->_get_p_rules();

		$this->_field_name = $field_name;
		$this->_set_field_value($field_value);
		$this->_site_id = $site_id;
		$this->_editor_id = uniqid('loki');
		$this->_editor_obj = $this->_editor_id."_obj"; // if the formula for generating $editor_obj changes here, js/hel.js also needs to be changed
		$this->_user_is_admin = $user_is_admin;
	}

	/////////////////////////////////////////////////////////
	//
	//  Loki::_get_p_rules()
	//
	//    Simply returns an array of rules for formatting paragraphs
	//    in Hel. Only to be used by Loki::Loki(). Other functions
	//    should instead use $this->_p_rules.
	//
	/////////////////////////////////////////////////////////

	function _get_p_rules()
	{
		$p_rules = array('can_contain_p' => 'object|ins|del|dd|blockquote|dd|form|fieldset|button|body|map|noscript',
						 'cannot_contain_p' => 'p|h1|h2|h3|h4|h5|h6|pre|address',
						 'can_contain_only_more_than_one_p' => 'div|th|td',
						 'can_contain_double_brs_but_not_p' => 'li');

		$i = 0;
		$count = count($p_rules);
		$all_block_level_elements = '';
		foreach ( $p_rules as $rule )
		{
			if ( $i < $count - 1 )
				$all_block_level_elements .= $rule . '|';
			else
				$all_block_level_elements .= $rule;

			$i++;
		}
		$p_rules['all_block_level_elements'] = $all_block_level_elements;

		return $p_rules;
	}

	/////////////////////////////////////////////////////////
	//
	//  Loki::print_form_children
	//
	/////////////////////////////////////////////////////////

	function print_form_children()
	{
		$lo = new Loki_Options($this->_current_options);

		/////////////////////////
		// Hel
		/////////////////////////
		if ( $this->_editor_version == 'hel' )
		{
			?>
			<script language='JavaScript' type='text/javascript' src='<?php echo $this->_asset_location ?>js/hel.js'></script>
			<link rel='stylesheet' type='text/css' href='<?php echo $this->_asset_location ?>css/editorStyles.css'>

			<div id='<?php echo $this->_editor_id; ?>_supported_container' 
				 style="display: none; /* hide from browsers w/ js turned off */">

			<table class='editorBox' UNSELECTABLE='on' cellpadding='5' cellspacing='0' border='0'>
			<tr align='left' valign='top'>
			<td nowrap='nowrap'>
				<table border='0' cellpadding='0' cellspacing='2' UNSELECTABLE='on'>
				<tr align='left' valign='top'>
				<?php if ($lo->is_sel('strong')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/bold.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='BOLD' title='BOLD' name='Bold' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("Bold");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('em')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/italic.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='ITALIC' title='ITALIC' name='Italic' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("Italic");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($this->_site_id==2822 || $this->_site_id==13295) /* Only Music & Web Develompment */ { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/underline.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='UNDERLINE' title='UNDERLINE' name='Underline' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("Underline");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('headline')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/header.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='HEADLINE' title='HEADLINE' name='HEADLINE' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.headline();' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('pre')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/pre.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='PREFORMATTED' title='PREFORMATTED' name='PREFORMATTED' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.preformat();' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('linebreak')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/break.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='CARRIAGE RETURN' title='CARRIAGE RETURN' name='Carriage_Return' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.insert_br();' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('hrule')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/hr.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='HORIZONTAL RULE' title='HORIZONTAL RULE' name='Horizontal_Rule' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.insert_hr();' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('alignleft')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/leftalign.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='LEFT ALIGN' title='LEFT ALIGN' name='Left_Align' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("JustifyLeft");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('aligncenter')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/center.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='CENTER ALIGN' title='CENTER ALIGN' name='Center' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("JustifyCenter");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('alignright')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/rightalign.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='RIGHT ALIGN' title='RIGHT ALIGN' name='Right_Align' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("JustifyRight");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('olist')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/ol.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='ORDERED LIST'  title='ORDERED LIST' name='OL' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("InsertOrderedList");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('ulist')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/ul.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='UNORDERED LIST' title='UNORDERED LIST' name='UL' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("InsertUnorderedList");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('indenttext')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/indent.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INDENT TEXT' title='INDENT TEXT' name='Indent' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("Indent");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('indenttext')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/outdent.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='REMOVE INDENT' title='REMOVE INDENT' name='Outdent' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.exec("Outdent");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<!-- <?php if ($lo->is_sel('findtext')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/findText.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='FIND & REPLACE TEXT' title='FIND & REPLACE TEXT' name='FINDREPLACE' width='21' height='20' onclick='openModal("modalFindReplace.php3?editorID=<?php echo $this->_editor_id; ?>", 590, 225);' border='0' unselectable='on'></td><?php echo "\n"; } ?> -->
				<!-- <?php if ($lo->is_sel('link')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/link.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK' title='INSERT LINK' name='Link' width='21' height='20' onclick='insertANCHOR(<?php print ($this->_editor_id . "," . $this->_site_id); ?>);' border='0' unselectable='on'></td><?php echo "\n"; } ?> -->
				<!-- <?php if ($lo->is_sel('table')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/insertTable.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT TABLE' title='INSERT TABLE' name='INSERTTABLE' width='21' height='20' onclick='insertTABLE(<?php echo $this->_editor_id; ?>);' border='0' unselectable='on'></td><?php echo "\n"; } ?> -->
				<?php if ($lo->is_sel('image') && $this->_site_id > -1) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/image.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT IMAGE' title='INSERT IMAGE' name='INSERTIMAGE' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.open_modal_link("image");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('link')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/link.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK' title='INSERT LINK' name='Link' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.open_modal_link("");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('link')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/anchorInNav.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT NAMED ANCHOR' title='INSERT NAMED ANCHOR' name='INSERT NAMED ANCHOR' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.open_modal_named_anchor();' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('link')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/email.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK TO EMAIL ADDRESS' title='INSERT LINK TO EMAIL ADDRESS' name='Link' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.open_modal_link("email");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('assets') && $this->_site_id > -1) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/insertAssets.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK TO ASSET' title='INSERT LINK TO ASSET' name='linktoasset' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.open_modal_link("asset");' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($lo->is_sel('spell')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/spellCheck.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='CHECK SPELLING' title='CHECK SPELLING' name='spellcheck' width='21' height='20' onclick='<?php echo $this->_editor_obj; ?>.open_modal_spell();' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($this->_user_is_admin) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/source.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='TOGGLE SOURCE' title='TOGGLE SOURCE' name='TOGGLESOURCE' width='21' height='20' onclick='var location = window.location + ""; window.location = location + ( location.indexOf("?") > -1 ? "&loki_source=true" : "?loki_source=true" );' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<td width='100%' align='right'><div onclick='<?php echo $this->_editor_obj; ?>.alert(document.getElementById("<?php echo $this->_editor_id; ?>").contentWindow.document.body.innerHTML);'>.</div></td>
				</tr>
				</table>
			</td>
			</tr>
			<tr align='left' valign='top'>
			<td>
				<iframe id="<?php echo $this->_editor_id; ?>" class='editorData' src="<?php echo $this->_asset_location; ?>hel_blank.php"></iframe>
				<!-- we htmlspecialchars because Mozilla converts all greater and less than signs in the textarea to entities, but doesn't convert amperstands to entities. When the value of the textarea is copied into the iframe, these entities are resolved, so as to create tags ... but then so are greater and less than signs that were originally entity'd. This is not desirable, and in particular allows people to add their own HTML tags, which is bad bad bad. -->
				<textarea id="<?php echo $this->_editor_id; ?>_source" style="display:none;"><?php echo $this->_field_value; ?></textarea>
			</td>
			</tr>
			<iframe id="<?php echo $this->_editor_id; ?>_clipboard" style="display:inline; position:absolute; width:1px; left: -5000px;"></iframe>
			<iframe id="<?php echo $this->_editor_id; ?>_preload_modal_image_iframe" style="display:inline; position:absolute; width:1px; left: -5000px;"></iframe>
			<iframe id="<?php echo $this->_editor_id; ?>_preload_modal_link_to_asset_iframe" style="display:inline; position:absolute; width:1px; left: -5000px;"></iframe>
			<iframe id="<?php echo $this->_editor_id; ?>_preload_modal_link_to_email_iframe" style="display:inline; position:absolute; width:1px; left: -5000px;"></iframe>
			<iframe id="<?php echo $this->_editor_id; ?>_preload_modal_link_iframe" style="display:inline; position:absolute; width:1px; left: -5000px;"></iframe>
			<iframe id="<?php echo $this->_editor_id; ?>_preload_modal_named_anchor_iframe" style="display:inline; position:absolute; width:1px; left: -5000px;"></iframe>
			</table>

			<textarea style="display: none;" name='<?php echo $this->_field_name; ?>' id='<?php echo $this->_field_name; ?>_supported'><?php echo $this->_field_value; ?></textarea>

			<script type="text/javascript">
			function <?php echo $this->_editor_id; ?>__add_event_listeners()
			{
				<?php echo $this->_editor_obj; ?>._editor_window.addEventListener("keyup", function(event) {
					<?php echo $this->_editor_obj; ?>.do_on_keyup(event);
				}, true);

				<?php echo $this->_editor_obj; ?>._editor_window.addEventListener("keydown", function(event) {
					<?php echo $this->_editor_obj; ?>.do_on_keydown(event);
				}, true);

				/* <?php echo $this->_editor_obj; ?>._editor_window.addEventListener("blur", function(event) {
					document.getElementById('<?php echo $this->_field_name; ?>_supported').value = <?php echo $this->_editor_obj; ?>.export_html();
				}, true); */

				/* document.getElementById('<?php echo $this->_editor_id; ?>_source').addEventListener("blur", function(event) {
					document.getElementById('<?php echo $this->_field_name; ?>_supported').value = <?php echo $this->_editor_obj; ?>.export_html();
				}, true); */
				
				find_form(document.getElementById('<?php echo $this->_field_name; ?>_supported')).addEventListener("submit", function(event) {
					document.getElementById('<?php echo $this->_field_name; ?>_supported').value = <?php echo $this->_editor_obj; ?>.export_html();
				}, true);
				
			}

			window.addEventListener("load", function(event) {
				<?php echo $this->_editor_obj; ?> = new hel(
					'<?php echo $this->_editor_id; ?>',
					'<?php echo $this->_site_id; ?>',
					'<?php echo $_SERVER['SERVER_NAME']; ?>',
					<?php echo $this->_convert_array_to_js_object($this->_p_rules); ?>
				);
				<?php echo $this->_editor_id; ?>__add_event_listeners();
				//<?php echo $this->_editor_obj; ?>.preload_modal_image('<?php echo $this->_editor_id; ?>_preload_modal_image_iframe');
				//<?php echo $this->_editor_obj; ?>.preload_modal_link('asset', '<?php echo $this->_editor_id; ?>_preload_modal_link_to_asset_iframe');
				//<?php echo $this->_editor_obj; ?>.preload_modal_link('email', '<?php echo $this->_editor_id; ?>_preload_modal_link_to_email_iframe');
				//<?php echo $this->_editor_obj; ?>.preload_modal_link('', '<?php echo $this->_editor_id; ?>_preload_modal_link_iframe');
				//<?php echo $this->_editor_obj; ?>.preload_modal_named_anchor('<?php echo $this->_editor_id; ?>_preload_modal_named_anchor_iframe');
			}, true);
			</script>
			</div> <!-- ending div id="<?php $this->_editor_id; ?>_supported_container" -->

			<!-- in case designMode is not supported by this build -->
			<div id='<?php echo $this->_editor_id; ?>_unsupported_container'>
				<textarea name='<?php echo $this->_field_name; ?>' cols="60" rows="10"><?php echo $this->_field_value; ?></textarea>
			</div>

			<?php
		}

		/////////////////////////
		// Loki
		/////////////////////////
		elseif ( $this->_editor_version == 'loki' )
		{
			//if ( $GLOBALS['LOKI_HAS_BEEN_CALLED'] != true ) {
		
				print ("<script language='JavaScript' src='" . $this->_asset_location . "js/loki.js'></script>\n");
				print ("<link rel='stylesheet' type='text/css' href='" . $this->_asset_location . "css/editorStyles.css'>\n");
				print ("<style type='text/css'>\n#".$this->_editor_id. " li { margin-top: 15px; }\n</style>\n");
				print ("<div id='clipboard_div' contenteditable='true' style='display:inline; position:absolute; width:1px; left: -5000px;'></div>");
			
				$GLOBALS['LOKI_HAS_BEEN_CALLED'] = true;
			//}
		
			printf ("<input type='hidden' name='%s' id='%s' value='%s'>\n",
					$this->_field_name, $this->_field_name, $this->_field_value);

			?>
			<table class='editorBox' UNSELECTABLE='on' cellpadding='5' cellspacing='0' border='0'>
			<tr align='left' valign='top'>
			<td nowrap="nowrap">
				<table border='0' cellpadding='0' cellspacing='2' UNSELECTABLE='on'>
				<tr align='left' valign='top'>
				<?php if ($lo->is_sel('strong')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/bold.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='BOLD' name='Bold' width='21' height='20' onclick='exec("Bold",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php /* KEEP THIS HERE - BK */ if ($this->_site_id==13295) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/underline.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='UNDERLINE' name='Underline' width='21' height='20' onclick='exec("Underline",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('em')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/italic.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='ITALIC' name='Italic' width='21' height='20' onclick='exec("Italic",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('headline')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/header.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='HEADLINE' name='HEADLINE' width='21' height='20' onclick='insertHEADLINE(<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('linebreak')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/break.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='CARRIAGE RETURN' name='Carriage_Return' width='21' height='20' onclick='insertBR(<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('hrule')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/hr.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='HORIZONTAL RULE' name='Horizontal_Rule' width='21' height='20' onclick='insertHR(<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('alignleft')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/leftalign.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='LEFT ALIGN' name='Left_Align' width='21' height='20' onclick='exec("JustifyLeft",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('aligncenter')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/center.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='CENTER ALIGN' name='Center' width='21' height='20' onclick='exec("JustifyCenter",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('alignright')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/rightalign.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='RIGHT ALIGN' name='Right_Align' width='21' height='20' onclick='exec("JustifyRight",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('olist')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/ol.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='ORDERED LIST'  name='OL' width='21' height='20' onclick='exec("InsertOrderedList",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('ulist')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/ul.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='UNORDERED LIST' name='UL' width='21' height='20' onclick='exec("InsertUnorderedList",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('indenttext')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/indent.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INDENT TEXT' name='Indent' width='21' height='20' onclick='indent(<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('indenttext')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/outdent.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='REMOVE INDENT' name='Outdent' width='21' height='20' onclick='exec("Outdent",<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('findtext')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/findText.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='FIND & REPLACE TEXT' name='FINDREPLACE' width='21' height='20' onclick='openModal("dialogs/lokiFindReplace.php?editorID=<?php print ($this->_editor_id); ?>", 600, 300);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('link') && $this->_site_id!=-1) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/link.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK' name='Link' width='21' height='20' onclick='insertANCHOR(<?php print ($this->_editor_id . "," . $this->_site_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); }
				elseif ($lo->is_sel('link')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/link.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK' name='Link' width='21' height='20' onclick='insertLINK(<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } else { print (""); }?>
				<?php if ($lo->is_sel('link')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/anchorInNav.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT NAMED ANCHOR' name='INSERT NAMED ANCHOR' width='21' height='20' onclick='insertNamedAnchor(document.getElementById("<?php echo $this->_editor_id; ?>"));' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('table')) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/insertTable.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT TABLE' name='INSERTTABLE' width='21' height='20' onclick='insertTABLE(<?php print ($this->_editor_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('image') && $this->_site_id!=-1) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/image.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT IMAGE' name='INSERT IMAGE' width='21' height='20' onclick='insertImageLister(<?php print ($this->_editor_id) ?>,<?php print ($this->_site_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('assets') && $this->_site_id!=-1) { ?><td><img src='<?php print ($this->_asset_location); ?>images/nav/insertAssets.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='INSERT LINK TO ASSET' name='INSERTLINKTOASSET' width='21' height='20' onclick='insertLinkToAsset(<?php print ($this->_editor_id) ?>,<?php print ($this->_site_id); ?>);' border='0' unselectable='on'></td><?php print ("\n"); } ?>
				<?php if ($lo->is_sel('spell')) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/spellCheck.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='CHECK SPELLING' title='CHECK SPELLING' name='spellcheck' width='21' height='20' onclick='checkSpelling(<?php print ($this->_editor_id) ?>);' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				<?php if ($this->_user_is_admin) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/source.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='TOGGLE SOURCE' title='TOGGLE SOURCE' name='TOGGLESOURCE' width='21' height='20' onclick='var location = window.location + ""; window.location = location + ( location.indexOf("?") > -1 ? "&loki_source=true" : "?loki_source=true" );' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				</tr>
				</table><!-- <?php print ("Site Id: " . $this->_site_id); ?> --></td>
			</tr>
			<tr align='left' valign='top'>
			<td>
				<div xmlns:loki="http://www.carleton.edu/loki/"
					id='<?php print ($this->_editor_id); ?>'
					CONTENTEDITABLE='true'
					class='editorData'
					onblur='cleanAnchors(<?php print ($this->_editor_id); ?>); document.all.<?php print ($this->_field_name); ?>.value=<?php print ($this->_editor_id); ?>.innerHTML; document.all.<?php print ($this->_field_name); ?>.value=uncloneHrefs(document.all.<?php print ($this->_field_name); ?>.value);'
					oncontextmenu='showContextMenu(<?php print ($this->_editor_id); ?>,<?php print ($this->_site_id); ?>);return false;'
					onresizestart='noControlResize();'
					onpaste="do_onpaste('<?php print ($this->_editor_id); ?>');tableBorders(<?php print ($this->_editor_id); ?>)"
					onclick='do_onmoveineditor();'
					onkeypress='do_onmoveineditor();'
				><p></p></div>
			</td>
			</tr>
			</table>

			<div onclick='alert(document.getElementById("<?php print ($this->_editor_id); ?>").innerHTML);'>&nbsp;</div>

			<script language='JScript'>
			if ( document.all.<?php print ($this->_editor_id); ?>.readyState=="complete" &&
				 document.all.<?php print ($this->_field_name); ?>.readyState=="complete") {

				document.all.<?php print ($this->_editor_id); ?>.innerHTML=cloneHrefs(document.all.<?php print ($this->_field_name); ?>.value);
				cleanAnchors(<?php print ($this->_editor_id); ?>);
				document.all.<?php print ($this->_editor_id); ?>.onreadystatechange = null;
				document.all.<?php print ($this->_field_name); ?>.onreadystatechange = null;
				tableBorders(<?php print ($this->_editor_id); ?>);
			};
			</script>

			<?php

		}

		/////////////////////////
		// Source
		/////////////////////////
		elseif ( $this->_editor_version == 'source' ) 
		{
			?>
			<link rel='stylesheet' type='text/css' href='<?php echo $this->_asset_location ?>css/editorStyles.css'>
			<table class='editorBox' UNSELECTABLE='on' cellpadding='5' cellspacing='0' border='0'>
			<tr align='left' valign='top'>
			<td nowrap="nowrap">
				<table border='0' cellpadding='0' cellspacing='2' UNSELECTABLE='on'>
				<tr align='left' valign='top'>
					<?php if ($this->_user_is_admin) { ?><td><img src='<?php echo $this->_asset_location; ?>images/nav/source.gif' class="tb" onmouseout="this.className='tb';" onmouseover="this.className='tbOver';" onmousedown="this.className='tbPressed';" alt='TOGGLE SOURCE' title='TOGGLE SOURCE' name='TOGGLESOURCE' width='21' height='20' onclick='var location = window.location + ""; window.location = location + ( location.indexOf("?") > -1 ? "&loki_source=false" : "?loki_source=false" );' border='0' unselectable='on'></td><?php echo "\n"; } ?>
				</tr>
				</table>
			</td>
			</tr>
			<tr align='left' valign='top'>
			<td>
				<textarea cols="60" rows="10" name="<?php echo $this->_field_name; ?>"><?php echo $this->_field_value ?></textarea>
				<input type="hidden" name="loki_source" value="true" />
			</td>
			</tr>
			</table>
			<?php
		}

		/////////////////////////
		// Textarea
		/////////////////////////
		else {
			printf( '<textarea cols="60" rows="10" name="%s">%s</textarea>',
					 $this->_field_name, $this->_field_value );
		}

	}

	/////////////////////////////////////////////////////////
	//
	//  Loki::_convert_array_to_js_object
	//
	/////////////////////////////////////////////////////////

	function _convert_array_to_js_object($array)
	{
		$js = '{ ';
		$i = 0;
		$array_count = count($array);
		foreach ( $array as $key => $value )
		{
			if ( $i < $array_count - 1 ) // is this the last item?
				$js .= $key . ': "' . $value . '", ';
			else
				$js .= $key . ': "' . $value . '" ';

			$i++;
		}
		$js .= '}';
		return $js;
	}

	/////////////////////////////////////////////////////////
	//
	//  Loki::get_field_value
	//
	/////////////////////////////////////////////////////////

	function get_field_value() { return $this->_field_value; }


	/////////////////////////////////////////////////////////
	//
	//  Loki::_set_field_value
	//
	/////////////////////////////////////////////////////////

	function _set_field_value($field_value) {

		$this->_field_value = $field_value;

		if ( $this->_editor_version == 'hel' )
		{
			$this->_field_value = eregi_replace('src="//webdev','src="https://webdev',$this->_field_value); // UPDATED 08/18/2003 BK // hel seem to have a problem with images if the url is just //web... we end up stripping http(s): out anyway later on
			$this->_field_value = eregi_replace('src="//webapps','src="https://webapps',$this->_field_value);// UPDATED 08/18/2003 BK // look above
			$this->_field_value = eregi_replace("src='//webdev","src='https://webdev",$this->_field_value);// UPDATED 08/18/2003 BK // look above
			$this->_field_value = eregi_replace("src='//webapps","src='https://webapps",$this->_field_value);// UPDATED 08/18/2003 BK // look above

			$this->_field_value = preg_replace("-<strong(\W[^>]*)>-isU", "<b$1>", $this->_field_value);
			$this->_field_value = preg_replace("-</strong>-isU", "</b>", $this->_field_value);
 			$this->_field_value = preg_replace("-<em(\W[^>]*)>-isU", "<i$1>", $this->_field_value);
			$this->_field_value = preg_replace("-</em>-isU", "</i>", $this->_field_value);

			// Change named anchors to image placeholders -- 2003-09-25 NF, rewritten 2003-12-04 NF
			$this->_field_value = preg_replace('-<a\s[^>]*name=(\'|")(.*)\1[^>]*>(.*)</a>-isU',
											   '<img src="http://' .
											   $_SERVER['SERVER_NAME'].$this->_asset_location .
											   'images/nav/anchor.gif" loki:is_really_an_anchor_whose_name="$2" />$3',
											   $this->_field_value);

			// Change onclicks to loki:onclicks
			$this->_field_value = preg_replace('-(<a[^>]*\s)onclick=-isU',
											   '$1 loki:onclick=',
											   $this->_field_value);

			// Change <br>'s and <hr>'s to dummy placeholders -- 2003-12-04 NF
// 			$this->_field_value = preg_replace('-(<br(>|\s[^>]*>))-isU',
// 											   '<img loki:is_really_a_br="true" src="http://' . $_SERVER['SERVER_NAME'] . $this->_asset_location.'images/nav/stp.gif" style="display:block; height:0px; width:0px; padding:0px; margin:0px; border:0px;" />',
// 											   $this->_field_value);
// do we want this:?
			$this->_field_value = preg_replace('-(<hr(>|\s[^>]*>))-isU',
											   '<img loki:is_really_an_hr="true" src="http://' . $_SERVER['SERVER_NAME'] . $this->_asset_location.'images/nav/stp.gif" style="display:block; height:0px; width:100%; padding:0ex; margin:1ex 0ex; border:0px; border: thin inset grey;" />',
											   $this->_field_value);

			// Change </p>'s to <br></p>'s, just so that new and old paragraphs behave the same way
			$this->_field_value = preg_replace("-</p>-isU", "<br>\n</p>", $this->_field_value);
		}

		elseif ( $this->_editor_version == 'loki' )
		{
			// Change alignment from style to html attributes
			$this->_field_value = preg_replace( "/style=\"([^\"]*?)(text-align:\s*left[;]?)/i", "ALIGN=\"LEFT\" STYLE=\"$1", $this->_field_value );
			$this->_field_value = preg_replace( "/style=\"([^\"]*?)(text-align:\s*right[;]?)/i", "ALIGN=\"RIGHT\" STYLE=\"$1", $this->_field_value );
			$this->_field_value = preg_replace( "/style=\"([^\"]*?)(text-align:\s*center[;]?)/i", "ALIGN=\"CENTER\" STYLE=\"$1", $this->_field_value );

			// Change named anchors to image placeholders -- 2003-09-25 NF, rewritten 2003-12-04 NF, rewritten 2004-02-04 BK
			// BK COMMENTS - http:// was causing "nonsecure items" security problem
			$this->_field_value = preg_replace('-<a\s[^>]*name=(\'|")(.*)\1[^>]*>(.*)</a>-isU',
											   '<img src="https://' .
											   $_SERVER['SERVER_NAME'].$this->_asset_location .
											   'images/nav/anchor.gif" loki:is_really_an_anchor_whose_name="$2" />$3',
											   $this->_field_value);

			if ( trim($this->_field_value) == '' )
				$this->_field_value = '<p></p>';

			// field_value must be one continous string and must have its ' and " changed to &#??
			$this->_field_value=str_replace("\r","",$this->_field_value);
			$this->_field_value=str_replace("\n","",$this->_field_value);
			$this->_field_value=str_replace("'","&#39;",$this->_field_value);
			$this->_field_value=str_replace('"',"&#34;",$this->_field_value);
		}

		else {} //TEMP

		$this->_field_value = str_replace( '<', '&lt;', $this->_field_value );
		$this->_field_value = str_replace( '>', '&gt;', $this->_field_value );
		
	}
		

	/////////////////////////////////////////////////////////
	//
	//  Loki::_get_editor_version
	//
	/////////////////////////////////////////////////////////

	function _get_editor_version()
	{
		$loki_source = !empty($_GET['loki_source']) ? $_GET['loki_source'] : (!empty($_POST['loki_source']) ? $_POST['loki_source'] : (!empty($_COOKIE['loki_source']) ? $_COOKIE['loki_source'] : '') );
		if ( !empty($loki_source) )
		{
			setcookie('loki_source', $loki_source);
			if ( $loki_source == 'true' )
			{
				return 'source';
			}
		}

		$ua = $_SERVER['HTTP_USER_AGENT'];

		if ( ereg('MSIE ([5-9].[0-9]{1,2})', $ua) AND !ereg('Mac', $ua) )
			return 'loki';

		// Any build after this date will presumably have a good
		// enough DOM + ECMAScript to allow us to show the editor only
		// if it's supported
		$build_date = Array();
		preg_match('-Gecko/(\d{8})-', $ua, $build_date);
		if ( array_key_exists(1, $build_date) && $build_date[1] > 20030126 )
			return 'hel';

		return 'none';

	}
}


class Loki_Process extends Loki
{

	function get_form_onsubmit() {}
	function print_form_children() {}


	/////////////////////////////////////////////////////////
	//
	//  Loki_Process::Loki
	//
	/////////////////////////////////////////////////////////

	function Loki_Process( $field_value )
	{
		$this->_p_rules = $this->_get_p_rules();
		$this->_editor_version = $this->_get_editor_version();
		$this->_set_field_value($field_value);
	}

	/////////////////////////////////////////////////////////
	//
	//  Loki_Process::_set_field_value
	//
	/////////////////////////////////////////////////////////

	function _set_field_value($field_value)
	{
		$this->_field_value = $field_value;

		$good_tags = '<a><abbrev><acronym><address><area><au><author><b><big><blockquote><bq><br><caption><center><cite><code><col><colgroup><credit><dfn><dir><div><dl><dt><dd><em><fn><form><h1><h2><h3><h4><h5><h6><hr><i><img><input><lang><lh><li><link><listing><map><math><menu><multicol><nobr><note><ol><option><p><param><person><plaintext><pre><samp><select><small><strike><strong><sub><sup><table><tbody><td><textarea><tfoot><th><thead><tr><tt><u><ul><var><wbr>';
		$good_tags_regexp = 'a|abbrev|acronym|address|area|au|author|b|big|blockquote|bq|br|caption|center|cite|code|col|colgroup|credit|dfn|dir|div|dl|dt|dd|em|fn|form|h1|h2|h3|h4|h5|h6|hr|i|img|input|lang|lh|li|link|listing|map|math|menu|multicol|nobr|note|ol|option|p|param|person|plaintext|pre|samp|select|small|strike|strong|sub|sup|table|tbody|td|textarea|tfoot|th|thead|tr|tt|u|ul|var|wbr';
		$this->_field_value = strip_tags( $field_value, $good_tags );
		
		// Change all &nbsp;'s to plain ol' spaces -- 2003-12-04 NF
		$this->_field_value = preg_replace('-&nbsp;-i', ' ', $this->_field_value);

		// I hate Tidy
		// Tidy sucks
		// Fuckin' Tidy
		// So, Tidy has a bug in it.  Apparently, an empty style attribute in paragraph that also has an align
		// attribute causes tidy to segfault.  So, we remove empty style attributes to get around
		// the bug.
		$this->_field_value = eregi_replace( 'style="[ ]*"','', $this->_field_value );

		// strip http: or https: from image tags to avoid bad protocol stuff - DH 
		// may need to hack on it some more...
		$this->_field_value = str_replace( 'src="http:', 'src="', $this->_field_value );
		$this->_field_value = str_replace( 'src="https:', 'src="', $this->_field_value );

		if ( $this->_editor_version == 'hel' )
		{
			////prp($this->_field_value, 'field_value before clean_brs');

			// Zap all <br>'s (legitimate <br>'s will actually be block-styled <span>'s at this point) -- 2003-12-04 NF
			// This is primarily directed against examples of Mozilla bug #94502, such as <p>asdf<br></p>
			//   N.B.: I'm not sure if it's better to zap *all* <br>'s, or only the
			//   <br>'s before </'s (as above and as we were originally doing).
			//   The only situation I can think of where there would be a <br> apart
			//   from a </ is if someone just presses enter in the middle of an existing
			//   paragraph, and presses nothing else. But in that situation, it might be
			//   better to just have the <br> go away--because people might not notice the
			//   misplaced <br>, and this could lead to random <br>s in the middle of paragraphs.
// 			$this->_field_value = preg_replace('-(<br(>|\s[^>]*>))-isU', '', $this->_field_value);

			// Add a <p> the beginning if needed
			if ( substr(trim($this->_field_value),0,2) != '<p' ) $this->_field_value = '<p>' . $this->_field_value;

			// Change image placeholders to named anchors -- 2003-09-18 NF
			$this->_field_value = preg_replace('-<img\s[^>]*loki:is_really_an_anchor_whose_name=(\'|")(.*)\1[^>]*>-isU', "<a name=$1$2$1></a>", $this->_field_value);

			// Change loki:onclicks to onclicks
			$this->_field_value = preg_replace('-(<a[^>]*\s)loki:onclick=-isU', '$1 onclick=', $this->_field_value);

			// Get rid of all style attributes (do we want this? ...)
			$this->_field_value = preg_replace('-(<(?:' . $good_tags_regexp . ')\s[^>]*\s)style=(\'|")[^>]*\2-isuU', '$1', $this->_field_value);


// 			// Get rid of other Microsoft crud
// 			$this->_field_value = preg_replace('-.*-isuU', '', $this->_field_value);
// 			$this->_field_value = preg_replace('-(<(?:' . $good_tags_regexp . ')\s[^>]*\s)style=(\'|")[^>]*\2-isuU', '$1', $this->_field_value);
// 			$this->_field_value = preg_replace('-(<(?:' . $good_tags_regexp . ')\s[^>]*\s)class="MsoNormal"-isuU', '$1', $this->_field_value);

			// Change br and hr placeholders to brs and hrs -- 2003-12-03 NF
// 			$this->_field_value = preg_replace("-<img\s[^>]*loki:is_really_a_br[^>]*>-isU", "<br />", $this->_field_value);
//get rid of this:?
			$this->_field_value = preg_replace("-<img\s[^>]*loki:is_really_an_hr[^>]*>-isU", "<hr />", $this->_field_value);

			// Tidy will klobber the hr, if it's inside a p tag -- 2003-12-03 NF
// this doesn't seem like such a good way to do it:
			$this->_field_value = preg_replace('-(<hr(>|\s[^>]*>))-isU', '</p>$1<p>', $this->_field_value);

			// Run Tidy
			$this->_field_value = tidy($this->_field_value);

// not sure if this is necessary anymore:
			// Zap all <br>'s before any <hr> (Tidy puts <br>'s in sometimes,
			// I think because of the </p> that we put in above)
			$this->_field_value = preg_replace('-(?:<br(?:>|\s[^>]*>)\s*)*(<hr(?:>|\s[^>]*>))-siuU', '$1', $this->_field_value);

// 			if ( $this->_site_id != id_of('mathcs_site') ) //TEMP

			$this->_field_value = $this->_protect_pre_brs($this->_field_value);
			$this->_field_value = $this->_clean_brs($this->_field_value);
			$this->_field_value = $this->_unprotect_pre_brs($this->_field_value);

			// Run Tidy again
			$this->_field_value = tidy($this->_field_value);
		}
		
		elseif ( $this->_editor_version == 'loki' )
		{
			// Change image placeholders to named anchors -- 2003-09-18 NF
			$this->_field_value = preg_replace('-<img\s[^>]*loki:is_really_an_anchor_whose_name=(\'|")(.*)\1[^>]*>-isU', "<a name=$1$2$1></a>", $this->_field_value);

			$this->_field_value = tidy($this->_field_value);
			//$this->_field_value = str_replace( chr(160), ' ', $this->_field_value );
		}

		else {} //TEMP

		$this->_field_value = preg_replace( '/<\/li>\s*<li style="list-style: none">/i', '', $this->_field_value );
	}

	// The purpose of _clean_brs, _clean_brs_callback, and
	// _replace_brs is to deal both with examples of Mozilla bug
	// #94502, such as <p>asdf<br></p>; and with examples of Mozilla
	// bug #xxxxx, i.e. the fact that when one presses enter, Mozilla
	// puts in <br> (we have it set up to put in <br><br>, though)
	// rather than </p><p>
	//
	// 2004-06-18 NF
	function _clean_brs($html)
	{
		$faux_matches = array( 0 => $html, 1 => 'p', 2 =>'', 3 => $html );
		$html = $this->_clean_brs_callback($faux_matches, true);

		// turn <loki:dont_touch_br>s back into <br>s
		$dtb = '<loki\:dont_touch_br(>|/>|\s[^>]*>)';
		$dtb_regex = '-(?:' . $dtb . '){2}-siuU';
		$html = preg_replace($dtb_regex, '<br $1', $html);

		return $html;
	}
	function _clean_brs_callback($matches, $first_time = false)
	{
// 		$can_contain_p = 'object|ins|del|dd|blockquote|li|dd|form|fieldset|th|td|button|body|map|noscript';
// 		$cannot_contain_p = 'div|address|p|h1|h2|h3|h4|h5|h6|pre';
// 		$both = $can_contain_p . '|' . $cannot_contain_p;

		$html = $matches['3'];
		$repl_tag = $matches['1'];
		$extra_attrs = !empty($matches['2']) ? $matches['2'] : '';

// 		if ( $_REQUEST['id'] == 15856 )
// 		{
// 			prp($html, 'html');
// 			if ( $this->_clean_brs_callback_tmp_count > 10 ) die();
// 			//die($_REQUEST['id']);
// 		}

		// This is a temporary measure to prevent infinite recursion
		$this->_clean_brs_callback_tmp_count++;
		if ( $this->_clean_brs_callback_tmp_count < 100 )
		{
			$opening_tag = '<(' . $this->_p_rules['all_block_level_elements'] . ')(?:\>|\s([^>]*))>';
			$closing_tag = '</\1>';
			$inner_html = ' ( (?: (?R) | .* )* ) '; // the recursive part, (?R), is needed to deal with nested tags with the same tagname, e.g. "<div><div>asdf</div></div>"
			$regex = '-(?: ' . $opening_tag . $inner_html . $closing_tag . ' )-siuUx';

			if ( preg_match($regex, $html) > 0 )
				$html = preg_replace_callback($regex, array($this, '_clean_brs_callback'), $html);

			$html = $this->_replace_brs($html, $repl_tag, $extra_attrs);
			if ( !$first_time )
				$html = '<' . $repl_tag . ' ' . $extra_attrs . '>' . $html . '</' . $repl_tag . '>';
		}

		return $html;
	}
	function _replace_brs($html, $repl_tag, $extra_attrs)
	{
		// obviously div can spec-wise contain a p, but it seems like
		// usually we would want just another div rather than a p;
		// this needs to be thought about more
// 		$can_contain_p = 'object|ins|del|dd|blockquote|li|dd|form|fieldset|th|td|button|body|map|noscript';
// 		$cannot_contain_p = 'div|address|p|h1|h2|h3|h4|h5|h6|pre';
// 		$both = $can_contain_p . '|' . $cannot_contain_p;

		// 1. Replace <br /><br />
		$br = '<br(>|/>|\s[^>]*>)\s*';
		$br_regex = '-(?:' . $br . '){2}-siuU';
		if ( strpos($this->_p_rules['cannot_contain_p'], $repl_tag) !== false )
		{
			$html = preg_replace($br_regex, '</' . $repl_tag . '><' . $repl_tag . ' ' . $extra_attrs . '>', $html);
		}
		elseif ( strpos($this->_p_rules['can_contain_p'], $repl_tag) !== false )
		{
			$html = '<p>' . $html . '</p>';
			$html = preg_replace($br_regex, '</p><p>', $html); //don't want the extra_attrs here, because they'll already be on the parent element
		}
		elseif ( strpos($this->_p_rules['can_contain_only_more_than_one_p'], $repl_tag) !== false )
		{
			if ( preg_match($br_regex, $html) > 0 )
			{
				$html = '<p>' . $html . '</p>';
				$html = preg_replace($br_regex, '</p><p>', $html); //don't want the extra_attrs here, because they'll already be on the parent element
			}
		}
		elseif ( strpos($this->_p_rules['can_contain_double_brs_but_not_p'], $repl_tag) !== false )
		{
			$html = preg_replace($br_regex, '<loki:dont_touch_br$1<loki:dont_touch_br$2', $html); //we don't want these to get replaced when we're going through one of the parent elements
		}


		// 2. Replace <br /></xxx>
		$closing_tag = '</(?:' . $this->_p_rules['all_block_level_elements'] . ')>';
		$regex = '-' . $br . '\s*(' . $closing_tag . ')-siuU';
		$html = preg_replace($regex, '$2', $html);

		return $html;
	}

	function _protect_pre_brs($html)
	{
		return preg_replace_callback('-<pre>.*</pre>-siuU',
									 array($this, '_protect_pre_brs_callback'),
									 $html);
	}

	function _protect_pre_brs_callback($matches)
	{
		return preg_replace('-<br />-siuU', '<loki:is_really_a_br_inside_pre />', $matches[0]);
	}

	function _unprotect_pre_brs($html)
	{
		$html = preg_replace('-<loki:is_really_a_br_inside_pre />-siuU', '<br />', $html);
		$html = preg_replace('-<br />\s*</pre>-siuU', '</pre>', $html);

		return $html;
	}
}

?>
