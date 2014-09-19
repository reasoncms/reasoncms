<?php

/**
 * Thor type library.
 * @package thor
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * Thor
 * @package disco
 * @subpackage plasmature
 */
class thorType extends defaultType
{
	var $tmp_id; // private, and don't set it using args, either
	var $original_db_conn_name; // private
	var $type = 'thor';
	var $thor_db_conn_name = NULL;
	var $asset_directory = THOR_HTTP_PATH;
	var $type_valid_args = array( 'thor_db_conn_name' );
	function display()
	{
		if(!$this->can_run())
		{
			return;
		}
		?>
		<script type="text/javascript">
		<!--
		var MM_contentVersion = 6;
		var plugin = (navigator.mimeTypes && navigator.mimeTypes["application/x-shockwave-flash"]) ? navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin : 0;
		if ( plugin ) {
			var words = navigator.plugins["Shockwave Flash"].description.split(" ");
   			for (var i = 0; i < words.length; ++i) {
				if (isNaN(parseInt(words[i])))
				continue;
				var MM_PluginVersion = words[i];
    		}
			var MM_FlashCanPlay = MM_PluginVersion >= MM_contentVersion;
		} else if (navigator.userAgent && navigator.userAgent.indexOf("MSIE")>=0 && (navigator.appVersion.indexOf("Win") != -1)) {
			document.write('<SCR' + 'IPT LANGUAGE=VBScript\> \n'); //FS hide this from IE4.5 Mac by splitting the tag
			document.write('on error resume next \n');
			document.write('MM_FlashCanPlay = ( IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash." & MM_contentVersion)))\n');
			document.write('</SCR' + 'IPT\> \n');
		}
		if ( MM_FlashCanPlay ) {
			document.write('<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" WIDTH="600" HEIGHT="400" id="thor" ALIGN="">');
			document.write('<PARAM NAME="movie" VALUE="<?php print ($this->asset_directory); ?>thor.swf?time=<?php print (time()); ?>">');
			document.write('<PARAM NAME="quality" VALUE="high">');
			document.write('<PARAM NAME="bgcolor" VALUE="#FFFFFF">');
			document.write('<PARAM NAME="FlashVars" VALUE="tmp_id=<?php print ($this->tmp_id); ?>&asset_directory=<?php print ($this->asset_directory); ?>">');
			document.write('<EMBED FlashVars="tmp_id=<?php print ($this->tmp_id); ?>&asset_directory=<?php print ($this->asset_directory); ?>" src="<?php print ($this->asset_directory); ?>thor.swf?time=<?php print (time()); ?>" quality="high" bgcolor="#FFFFFF"  WIDTH="600" HEIGHT="400" NAME="thor" ALIGN="" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"></EMBED>');
			document.write('</OBJECT>');
		} else{
			document.write('<span style="color:#ffffff;background-color:#ff0000;">You do not have the most current version of the Flash plug-in. Please install the latest <a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash">Flash Player</a> to manage this form.</span>');
		}
		//-->
		</script>
		<?php
		// Use a hidden field to pass along the tmp_id to $this->get()
		echo '<input type="hidden" id="' . $this->name . 'Element" name="' . $this->name . '" value="' . $this->tmp_id.'" />' . "\n";
	}
	function set( $value )
	{
		if(!$this->can_run())
		{
			return;
		}
		if ( empty($value) )
			$this->value = '<' . '?xml version="1.0" ?' . '><form submit="Submit" reset="Clear" />';
		else
			$this->value = $value;
		// If there's no xml declaration, assume that value contains the tmp_id
		if ( strpos($this->value, '<' . '?xml') === false )
		{
			$this->tmp_id = $this->value;
			connectDB($this->thor_db_conn_name);
			$dbs = new DBSelector();
			$dbs->add_table('thor');
			$dbs->add_field('thor', 'content');
			$dbs->add_relation('thor.id = ' . addslashes($this->tmp_id));
			$results = $dbs->run();
			if ( count($results) > 0 )
				$this->value = $results[0]['content'];
			else
				$this->value = '';
			connectDB($this->original_db_conn_name );
		}
		// Otherwise, assume that value contains the xml
		else
		{
			connectDB($this->thor_db_conn_name);
			include_once( CARL_UTIL_INC .'db/sqler.php');
			$sqler = new SQLER;
			$sqler->insert('thor', Array('content' => $this->value));
			$this->tmp_id = mysql_insert_id();
			connectDB($this->original_db_conn_name );
		}
	}

	// After init is run, one can safely assume that $this->tmp_id
	// is contains the tmp_id, and $this->value contains the XML
	function init( $args = array() )
	{
		parent::init($args);

		// $attachPoints = array('init','on_first_time','on_every_time','pre_show_form','post_show_form','no_show_form','pre_error_check_actions','run_error_checks','post_error_check_actions','post_error_checks','process','where_to');
		// foreach ($attachPoints as $ap) { $this->parentContentManager->add_callback(array($this, 'discoEventTester_' . $ap), $ap); }
		// $this->parentContentManager->add_callback(array($this, 'discoEventTester'), "on_first_time");
		// $this->parentContentManager->add_callback(array($this, 'discoEventTester2'), "on_every_time");

		// echo "<HR>INITING FLASH.PHP<HR>";
		// $this->ensure_temp_db_table_exists();


		if(!$this->can_run())
		{
			trigger_error('thor needs a db connection name (thor_db_conn_name)');
			return;
		}
		include_once(CARL_UTIL_INC.'db/db.php');
		$this->original_db_conn_name = get_current_db_connection_name();
		$this->set($this->value);
		return true;

		/*
		// if $this->value doesn't contain an xml declaration,assume it's a tmp_id
		if ( strpos($this->value, '<' . '?xml') === false )
		{
			prp('in init, matches number');
			prp($this->value, 'this->value, in init (early)');
			$this->tmp_id = $this->value;
			// Use file('getXML.php'), instead of a direct call to
			// the database, because connecting to the test
			// database here seems to screw up the connection to
			// cms. Possibly only one mysql link can be open at
			// once.
			$this->value = implode("\n", file('http://'.HTTP_HOST_NAME.THOR_HTTP_PATH.'getXML.php?tmp_id=' . $this->tmp_id));
			prp($this->value, 'this->value, in init (late)');
		}
		// otherwise, assume $this->value contains the XML
		else
		{
			connectDB($this->thor_db_conn_name);
			include_once(CARL_UTIL_INC . 'db/sqler.php');
			$sqler = new SQLER;
			$sqler->insert('thor', Array('content' => $this->value));
			$this->tmp_id = mysql_insert_id();
			connectDB($this->original_db_conn_name);
		}
		 */
	}

	// $attachPoints = array('init','on_first_time','on_every_time','pre_show_form','post_show_form','no_show_form','pre_error_check_actions','run_error_checks','post_error_check_actions','post_error_checks','process','where_to');
	function discoEventTester($disco) { echo "<P>onFirstTime...<P>"; }
	function discoEventTester2($disco) { echo "<P>onEveryTime...<P>"; }

	function can_run()
	{
		if(empty($this->thor_db_conn_name))
		{
			return false;
		}
		return true;
	}
	function get()
	{
		if(!$this->can_run())
		{
			return;
		}
		return $this->value;
	}
}
