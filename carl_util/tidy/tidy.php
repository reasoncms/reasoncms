<?php
/**
 * Tidy HTML
 *
 * tidy function converts HTML to XHTML in a sort of round-a-bout way
 * uses the w3c's tidy program and some command line and string manipulation trickery
 * to produce valid XHTML from HTML
 *
 * @author dave hendler
 * @author nate white
 * @package carl_util
 * @subpackage tidy
 */
 
/**
 * Turn a string or array into valid, standards-compliant (x)HTML
 *
 * Uses configuraton options in tidy.conf - which should minimally have show-body-only set to yes
 *
 * @param mixed $text The data to be tidied up
 * @return mixed $result Tidied data
 */
function tidy( $text )
{
	static $tidy_funcs;
	static $tidy_conf;
	if (!isset($tidy_conf)) $tidy_conf = SETTINGS_INC . 'tidy.conf';

	if(is_array($text))
	{
		$result = array();
		foreach(array_keys($text) as $key)
		{
			$result[$key] = tidy($text[$key]);
		}
		return $result;
	}
	
	// determine what tidy libraries are available
	if (empty($tidy_funcs)) $tidy_funcs = get_extension_funcs('tidy');
	$tidy_1_lib_available = (!empty($tidy_funcs)) && (array_search('tidy_setopt', $tidy_funcs) !== false);
	$tidy_2_lib_available = (!empty($tidy_funcs)) && (array_search('tidy_setopt', $tidy_funcs) === false);
	$tidy_command_line_available = (TIDY_EXE) ? file_exists(TIDY_EXE) : false;
	
	$text = protect_string_from_tidy( $text );
	
	$text = '<html><body>'.$text.'</body></html>';
	
	if ($tidy_2_lib_available) // Run tidy for PHP 5
	{
		$tidy = new tidy();
		$tidy->parseString($text, $tidy_conf, 'utf8');
		$tidy->cleanRepair();
		$result = $tidy;
	}
	elseif ($tidy_1_lib_available) // Run tidy for PHP 4
	{
		tidy_load_config($tidy_conf);
		tidy_set_encoding('utf8');
		tidy_parse_string($text);
		tidy_clean_repair();
		$result = tidy_get_output();
	}		
	elseif ($tidy_command_line_available) // attempt to run COMMAND LINE tidy
	{
		$arg = escapeshellarg( $text ); // escape the bad stuff in the text
		$cmd = 'echo '.$arg.' | '.TIDY_EXE.' -q -config '.$tidy_conf.' 2> /dev/null'; // the actual command - pipes the input to tidy which diverts its output to the random file
		$result = shell_exec($cmd); // execute the command		
	}
	else
	{
		trigger_error('tidy does not appear to be available within php or at the command line - no tidying is taking place.');
		$result = $text;
	}
	return trim($result);
}

/**
 * See where this is used and provide better error handling for tidylib in php 4 and 5
 */
function tidy_err( $text )
{
	static $tidy_conf;
	if (!isset($tidy_conf)) $tidy_conf = SETTINGS_INC . 'tidy.conf';
	$arg = escapeshellarg( $text );
	$err = shell_exec( 'echo '.$arg.' | '.TIDY_EXE.' -q -config '.$tidy_conf.' 2>&1' );
	$err = explode( "\n", $err );
	$errors = array();
	foreach( $err AS $line )
	{
		// look for both type and value inequality
		if( strstr( $line, 'Error:' ) !== false )
		$errors[] = $line;
	}
	return implode("\n",$errors);
}

function protect_string_from_tidy( $str )
{
	$utf_entity_trans = array(
		'&nbsp;' => chr('194').chr('160'),
		'&#160;' => chr('194').chr('160'),
		'&iexcl;' => chr('194').chr('161'),
		'&#161;' => chr('194').chr('161'),
		'&cent;' => chr('194').chr('162'),
		'&#162;' => chr('194').chr('162'),
		'&pound;' => chr('194').chr('163'),
		'&#163;' => chr('194').chr('163'),
		'&curren;' => chr('194').chr('164'),
		'&#164;' => chr('194').chr('164'),
		'&yen;' => chr('194').chr('165'),
		'&#165;' => chr('194').chr('165'),
		'&brvbar;' => chr('194').chr('166'),
		'&#166;' => chr('194').chr('166'),
		'&sect;' => chr('194').chr('167'),
		'&#167;' => chr('194').chr('167'),
		'&uml;' => chr('194').chr('168'),
		'&#168;' => chr('194').chr('168'),
		'&copy;' => chr('194').chr('169'),
		'&#169;' => chr('194').chr('169'),
		'&ordf;' => chr('194').chr('170'),
		'&#170;' => chr('194').chr('170'),
		'&laquo;' => chr('194').chr('171'),
		'&#171;' => chr('194').chr('171'),
		'&not;' => chr('194').chr('172'),
		'&#172;' => chr('194').chr('172'),
		'&shy;' => chr('194').chr('173'),
		'&#173;' => chr('194').chr('173'),
		'&reg;' => chr('194').chr('174'),
		'&#174;' => chr('194').chr('174'),
		'&macr;' => chr('194').chr('175'),
		'&#175;' => chr('194').chr('175'),
		'&deg;' => chr('194').chr('176'),
		'&#176;' => chr('194').chr('176'),
		'&plusmn;' => chr('194').chr('177'),
		'&#177;' => chr('194').chr('177'),
		'&sup2;' => chr('194').chr('178'),
		'&#178;' => chr('194').chr('178'),
		'&sup3;' => chr('194').chr('179'),
		'&#179;' => chr('194').chr('179'),
		'&acute;' => chr('194').chr('180'),
		'&#180;' => chr('194').chr('180'),
		'&micro;' => chr('194').chr('181'),
		'&#181;' => chr('194').chr('181'),
		'&para;' => chr('194').chr('182'),
		'&#182;' => chr('194').chr('182'),
		'&middot;' => chr('194').chr('183'),
		'&#183;' => chr('194').chr('183'),
		'&cedil;' => chr('194').chr('184'),
		'&#184;' => chr('194').chr('184'),
		'&sup1;' => chr('194').chr('185'),
		'&#185;' => chr('194').chr('185'),
		'&ordm;' => chr('194').chr('186'),
		'&#186;' => chr('194').chr('186'),
		'&raquo;' => chr('194').chr('187'),
		'&#187;' => chr('194').chr('187'),
		'&frac14;' => chr('194').chr('188'),
		'&#188;' => chr('194').chr('188'),
		'&frac12;' => chr('194').chr('189'),
		'&#189;' => chr('194').chr('189'),
		'&frac34;' => chr('194').chr('190'),
		'&#190;' => chr('194').chr('190'),
		'&iquest;' => chr('194').chr('191'),
		'&#191;' => chr('194').chr('191'),
		'&Agrave;' => chr('195').chr('128'),
		'&#192;' => chr('195').chr('128'),
		'&Aacute;' => chr('195').chr('129'),
		'&#193;' => chr('195').chr('129'),
		'&Acirc;' => chr('195').chr('130'),
		'&#194;' => chr('195').chr('130'),
		'&Atilde;' => chr('195').chr('131'),
		'&#195;' => chr('195').chr('131'),
		'&Auml;' => chr('195').chr('132'),
		'&#196;' => chr('195').chr('132'),
		'&Aring;' => chr('195').chr('133'),
		'&#197;' => chr('195').chr('133'),
		'&AElig;' => chr('195').chr('134'),
		'&#198;' => chr('195').chr('134'),
		'&Ccedil;' => chr('195').chr('135'),
		'&#199;' => chr('195').chr('135'),
		'&Egrave;' => chr('195').chr('136'),
		'&#200;' => chr('195').chr('136'),
		'&Eacute;' => chr('195').chr('137'),
		'&#201;' => chr('195').chr('137'),
		'&Ecirc;' => chr('195').chr('138'),
		'&#202;' => chr('195').chr('138'),
		'&Euml;' => chr('195').chr('139'),
		'&#203;' => chr('195').chr('139'),
		'&Igrave;' => chr('195').chr('140'),
		'&#204;' => chr('195').chr('140'),
		'&Iacute;' => chr('195').chr('141'),
		'&#205;' => chr('195').chr('141'),
		'&Icirc;' => chr('195').chr('142'),
		'&#206;' => chr('195').chr('142'),
		'&Iuml;' => chr('195').chr('143'),
		'&#207;' => chr('195').chr('143'),
		'&ETH;' => chr('195').chr('144'),
		'&#208;' => chr('195').chr('144'),
		'&Ntilde;' => chr('195').chr('145'),
		'&#209;' => chr('195').chr('145'),
		'&Ograve;' => chr('195').chr('146'),
		'&#210;' => chr('195').chr('146'),
		'&Oacute;' => chr('195').chr('147'),
		'&#211;' => chr('195').chr('147'),
		'&Ocirc;' => chr('195').chr('148'),
		'&#212;' => chr('195').chr('148'),
		'&Otilde;' => chr('195').chr('149'),
		'&#213;' => chr('195').chr('149'),
		'&Ouml;' => chr('195').chr('150'),
		'&#214;' => chr('195').chr('150'),
		'&times;' => chr('195').chr('151'),
		'&#215;' => chr('195').chr('151'),
		'&Oslash;' => chr('195').chr('152'),
		'&#216;' => chr('195').chr('152'),
		'&Ugrave;' => chr('195').chr('153'),
		'&#217;' => chr('195').chr('153'),
		'&Uacute;' => chr('195').chr('154'),
		'&#218;' => chr('195').chr('154'),
		'&Ucirc;' => chr('195').chr('155'),
		'&#219;' => chr('195').chr('155'),
		'&Uuml;' => chr('195').chr('156'),
		'&#220;' => chr('195').chr('156'),
		'&Yacute;' => chr('195').chr('157'),
		'&#221;' => chr('195').chr('157'),
		'&THORN;' => chr('195').chr('158'),
		'&#222;' => chr('195').chr('158'),
		'&szlig;' => chr('195').chr('159'),
		'&#223;' => chr('195').chr('159'),
		'&agrave;' => chr('195').chr('160'),
		'&#224;' => chr('195').chr('160'),
		'&aacute;' => chr('195').chr('161'),
		'&#225;' => chr('195').chr('161'),
		'&acirc;' => chr('195').chr('162'),
		'&#226;' => chr('195').chr('162'),
		'&atilde;' => chr('195').chr('163'),
		'&#227;' => chr('195').chr('163'),
		'&auml;' => chr('195').chr('164'),
		'&#228;' => chr('195').chr('164'),
		'&aring;' => chr('195').chr('165'),
		'&#229;' => chr('195').chr('165'),
		'&aelig;' => chr('195').chr('166'),
		'&#230;' => chr('195').chr('166'),
		'&ccedil;' => chr('195').chr('167'),
		'&#231;' => chr('195').chr('167'),
		'&egrave;' => chr('195').chr('168'),
		'&#232;' => chr('195').chr('168'),
		'&eacute;' => chr('195').chr('169'),
		'&#233;' => chr('195').chr('169'),
		'&ecirc;' => chr('195').chr('170'),
		'&#234;' => chr('195').chr('170'),
		'&euml;' => chr('195').chr('171'),
		'&#235;' => chr('195').chr('171'),
		'&igrave;' => chr('195').chr('172'),
		'&#236;' => chr('195').chr('172'),
		'&iacute;' => chr('195').chr('173'),
		'&#237;' => chr('195').chr('173'),
		'&icirc;' => chr('195').chr('174'),
		'&#238;' => chr('195').chr('174'),
		'&iuml;' => chr('195').chr('175'),
		'&#239;' => chr('195').chr('175'),
		'&eth;' => chr('195').chr('176'),
		'&#240;' => chr('195').chr('176'),
		'&ntilde;' => chr('195').chr('177'),
		'&#241;' => chr('195').chr('177'),
		'&ograve;' => chr('195').chr('178'),
		'&#242;' => chr('195').chr('178'),
		'&oacute;' => chr('195').chr('179'),
		'&#243;' => chr('195').chr('179'),
		'&ocirc;' => chr('195').chr('180'),
		'&#244;' => chr('195').chr('180'),
		'&otilde;' => chr('195').chr('181'),
		'&#245;' => chr('195').chr('181'),
		'&ouml;' => chr('195').chr('182'),
		'&#246;' => chr('195').chr('182'),
		'&divide;' => chr('195').chr('183'),
		'&#247;' => chr('195').chr('183'),
		'&oslash;' => chr('195').chr('184'),
		'&#248;' => chr('195').chr('184'),
		'&ugrave;' => chr('195').chr('185'),
		'&#249;' => chr('195').chr('185'),
		'&uacute;' => chr('195').chr('186'),
		'&#250;' => chr('195').chr('186'),
		'&ucirc;' => chr('195').chr('187'),
		'&#251;' => chr('195').chr('187'),
		'&uuml;' => chr('195').chr('188'),
		'&#252;' => chr('195').chr('188'),
		'&yacute;' => chr('195').chr('189'),
		'&#253;' => chr('195').chr('189'),
		'&thorn;' => chr('195').chr('190'),
		'&#254;' => chr('195').chr('190'),
		'&yuml;' => chr('195').chr('191'),
		'&#255;' => chr('195').chr('191'),
	);
	$str = str_replace( array_keys( $utf_entity_trans ), $utf_entity_trans, $str );
	return $str;
}
?>
