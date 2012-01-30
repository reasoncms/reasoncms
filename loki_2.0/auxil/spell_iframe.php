<?php

//--//include_once('reason_header.php');

$sc = new spell_check;
//$sc->init($_REQUEST['text']);
$sc->init($HTTP_RAW_POST_DATA);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:spell="http://www.carleton.edu/spell">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Spell Check</title>
<!-- These are copied from UI.Loki._append_document_stylesheets.
     XXX figure this out automatically -->
<style type="text/css"><?php include('../css/cssSelector.css'); ?></style>
<style type="text/css"><?php include('../css/cssSelector_gecko.css'); ?></style>
<style type="text/css"><?php include('../../../css/modules.css'); ?></style>
<style type="text/css"><?php include('../../../css/default_styles.css'); ?></style>
<!-- <style type="text/css"><?php // include('../../../css/minisites_styles.css'); ?></style> -->
<style type="text/css"><?php include('../css/Loki_Document.css'); ?></style>
<style type="text/css">
body
{
	color:black;
	background:white;
	padding:.5ex;
	margin:0;
}
spell\:word
{
	color:black;
	background:lightgrey;
}
spell\:word.current
{
	color:white;
	background:darkblue;
}
</style>
<script type="text/javascript" language="javascript">
<?php include('spell_anchor_position.js'); ?>
</script>
<script type="text/javascript" language="javascript">
var do_onload = window.spell_iframe__do_onload = function()
{
	var suggestion_list = <?php echo $sc->get_suggestion_list_js() ?>;
	parent.suggestion_list = suggestion_list;
	//parent.do_onframeload(document.getElementsByTagName('body').item(0).innerHTML);
	var words = document.getElementsByTagName('spell:word'); // W3C
	if ( words.length == 0 ) // IE
		words = document.getElementsByTagName('word');
	parent.do_onframeload(suggestion_list, words);


// 	var suggestion_list = <?php //echo $sc->get_suggestion_list_js() ?>;
// 	var words = document.getElementsByTagName('word');
// 	alert(words.length);
// 	parent.do_onframeload(suggestion_list, words);
}
function scroll_to_word(word)
{
	var word_coordinates = getAnchorPosition(word.getAttribute('id'));
	//window.scrollTo(word_coordinates.x, word_coordinates.y - 2); // "y - 2" so it looks a little better
	window.scrollTo(0, word_coordinates.y - 2); // "y - 2" so it looks a little better
}
</script>
</head>

<!-- the onload is for Gecko (do_onload is called for IE in UI.Spell_Dialog). 
     XXX Doing that in two places is a big hack - fix. -->
<body onload="if ( !document.all ) do_onload();">
<?php echo $sc->get_text(); ?>
</body>
</html>
<?php




/////////////////////////////////////////////////////////////////////////////////////
//
// Uses aspell to spellcheck a large chunk of text.
//
// Example usage:
//       $sc = new spell_check;
//       $sc->init($a_lot_of_text);
//       echo $sc->get_text();
//       echo '<script type="text/javascript" language="javascript">' . "\n";
//       echo 'var suggestion_list = ' . $sc->get_suggestion_list_js() . ';' . "\n";
//       echo '</script>' . "\n";
//
// (sort of loosely based on a script somewhere in the PHP manual ... scary)
// Nathanael Fillmore, 2004-04-28
//
/////////////////////////////////////////////////////////////////////////////////////
class spell_check
{
	var $_text_arr;
	var $_aspell_output_arr;
	var $_suggestions_arr;

	function init($text)
	{
		$this->_text_arr = explode("\n", $text);

		$this->_run_aspell();
		$this->_flag_misspelled_words();
	}
	function _run_aspell()
	{
		$temp_filename = tempnam("/tmp", "spelltext");
//		$aspell_command = 'cat ' . $temp_filename . ' | /usr/bin/aspell -a -H'; // this one is for use with aspell-0.33.7.1
		//$aspell_command = 'cat ' . $temp_filename . ' | /home/fillmorn/aspell/bin/aspell -a -H --encoding=utf-8'; // this one is for use with aspell-0.50.5, which is needed to handle utf-8 properly
		//$aspell_command = 'cat ' . $temp_filename . ' | /usr/bin/aspell -a -H --encoding=utf-8'; // On RHEL AS4 we have aspell 0.50, should work
		// "-H" results in img's alt tags being checked. 
		// "--mode sgml --rem-sgml-check alt" results in everything within sgml tags 
		// being skipped, which is what we want.
		$aspell_command = 'cat ' . $temp_filename . ' | /usr/bin/aspell -a --mode sgml --rem-sgml-check alt --encoding=utf-8'; // On RHEL AS4 we have aspell 0.50, should work

		$fd = fopen($temp_filename, 'w');
		if ( !$fd )
			die( 'Couldn\'t open temp file for aspell.' );

		fwrite($fd, "!\n");
		foreach ( $this->_text_arr as $key => $value )
		{
			// adding the carat to each line prevents the use of aspell commands within the text...
			fwrite($fd, "^$value\n");
		}
		fclose($fd);
		
		// next create tempdict and temprepl (skipping for now...)
		
		$return = shell_exec($aspell_command);
		unlink($temp_filename);

		$this->_aspell_output_arr = explode("\n", $return);

		//prp($this->_aspell_output_arr, 'aspell_output_arr');
		//prp($this->_text_arr, 'text_arr');
	}
	function _flag_misspelled_words()
	{
		$flags_offset = 0;
		$line_index = 0;
		$word_index = 0;
		$prev_indicator = '';
		foreach($this->_aspell_output_arr as $aspell_output_line)
		{
			$exploded = explode(' ', $aspell_output_line);
			$indicator = array_shift($exploded);

			// There is a misspelled word, and there are suggestions
			if ( $indicator == '&' )
			{
				$word = array_shift($exploded);
				$suggestions_count = array_shift($exploded);
				$word_offset = substr(array_shift($exploded), 0, -1) - 1; // substr to get rid of the trailing colon
				$suggestions = $exploded;

				$flags_offset += $this->_flag_misspelled_word($word, $word_offset, $word_index, $flags_offset, $line_index);
				$this->_add_suggestions($suggestions, $word_index, $line_index);

				$word_index++;
			}
			// There is a misspelled word, but no suggestions
			elseif ( $indicator == '#' )
			{
				$word = array_shift($exploded);
				$word_offset = array_shift($exploded) - 1;
				$suggestions = array();
	
				$flags_offset += $this->_flag_misspelled_word($word, $word_offset, $word_index, $flags_offset, $line_index);
				$this->_add_suggestions($suggestions, $word_index, $line_index);
				
				$word_index++;
			}
			// There were no misspelled words at all on the previous
			// line of text (or this is the first line). (I have to do
			// it like this because contrary to what the documentation
			// says, aspell doesn't output a line with an asterisk on
			// it if the line of text had no misspellings; rather,
			// aspell outputs an empty line.)
			elseif ( $indicator == '' && ($prev_indicator == '' || $prev_indicator == '@(#)') )
			{
				$line_index++;
			}
			// There are no more misspelled words on this line of the text.
			// This matches an empty $indicator (--> no more misspelled words on this line)
			// or an asterisk in $indicator (--> no misspelled words at all on this line)
			else
			{
				$flags_offset = 0;
				if ( $word_index > 0 )
				{
					$line_index++;
					$word_index = 0;
				}
			}
			$prev_indicator = $indicator;
		}
	}
	function _flag_misspelled_word($word, $word_offset, $word_index, $flags_offset, $line_index)
	{
		$offset = $word_offset + $flags_offset;
		
		//prp( $line_index, 'line_index');
		$before_word = substr($this->_text_arr[$line_index], 0, $offset);
		$after_word = substr($this->_text_arr[$line_index], $offset + strlen($word));
		
		$opener_flag = '<spell:word id="line_' . $line_index . '_word_' . $word_index . '">';
		$closer_flag = '</spell:word>';
		
		$this->_text_arr[$line_index] = $before_word . $opener_flag . $word . $closer_flag . $after_word;

		//echo 'new flags_offset: ' . ( strlen($opener_flag) + strlen($closer_flag) );
		return strlen($opener_flag) + strlen($closer_flag);
	}
	function _add_suggestions($suggestions_arr, $word_index, $line_index)
	{
		$id = 'line_' . $line_index . '_word_' . $word_index;

		foreach ($suggestions_arr as $key => $value)
			$suggestions_arr[$key] = str_replace(',', '', trim($value));

		$this->_suggestions_arr[$id] = $suggestions_arr;
	}
	// Returns the text, with the misspelled words flagged.
	function get_text()
	{
		return implode('', $this->_text_arr);
	}
	// Returns the suggestions as a javascript object. The object
	// contains a number of variables (named using (misspelled)
	// word_ids) which point to arrays of misspelled words.
	function get_suggestion_list_js()
	{
		$list = '{ ' . "\n";
		
		if ( !empty($this->_suggestions_arr) )
		{
		$i = 0;
		$suggestions_arr_count = count($this->_suggestions_arr);
		foreach ( $this->_suggestions_arr as $word_id => $sug_list )
		{
			$list .= '    "' . $word_id . '" :' . "\n";
			$list .= '    [ ' . "\n";

			$j = 0;
			$sug_list_count = count($sug_list);
			foreach ( $sug_list as $sug )
			{
				if ( $j < $sug_list_count - 1 ) // is this the last item?
					$list .= '        "' . $sug . '", ' . "\n";
				else
					$list .= '        "' . $sug . '" ' . "\n";

				$j++;
			}

			if ( $i < $suggestions_arr_count - 1 ) // is this the last item?
				$list .= '    ],' . "\n";
			else
				$list .= '    ]' . "\n";

			$i++;
		}
		}
		
		$list .= '}' . "\n";

		return $list;
	}
}
?>
