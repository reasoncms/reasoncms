<?php
/**
 * Find & Replace Text
 * @package loki_1
 * @subpackage loki
 */

/**
 * Start the page
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title>Find & Replace Text</title>
<link rel="stylesheet" type="text/css" href="../css/modalStyles.css">
<script type="text/javascript" src="../js/loki.js"></script>
<script type="text/javascript">

var callerWindowObj = dialogArguments;

// Following works, just not being supported right now
/* function selectionValue(oFieldName) {

	var callerWindowObj = dialogArguments;
	var selectionWord = callerWindowObj.<?php print ($editorID); ?>.document.selection.createRange();
	document.getElementById(oFieldName).value = selectionWord.text;
	
} */

var currentTRange = getTextRangeById('<?php echo htmlspecialchars($_REQUEST['editorID'], ENT_QUOTES); ?>');
currentTRange.collapse(true); //move to the beginning of the TR
var containerTRange = getTextRangeById('<?php echo htmlspecialchars($_REQUEST['editorID'], ENT_QUOTES); ?>');
var foundTRange = false;

function find( editor_container_id, str  )
{
    // Culled from <http://www.dynamicdrive.com/dynamicindex11/findpage.htm>, 2002-10-28
    // Modified extensively by Nathanael Fillmore

    var wrapSearch;

    // Try to find it from current position to the end of the trange
    if (currentTRange != null)
    {
	currentTRange.collapse(false);
	strFound = currentTRange.findText(str, 10000000, getFindTextFlags());
	if (strFound && containerTRange.inRange(currentTRange))
	{
	    currentTRange.select();
	}
	else
	{
	    currentTRange = null;
	    strFound = false;
	    if ( !confirm('No further matches found. Continue from beginning?') )
		wrapSearch = false;
	    else 
		wrapSearch = true;
	}
    }

    // Otherwise, try to start over and find it
    if ( (currentTRange == null || strFound == 0) && wrapSearch != false ) 
    {
	currentTRange = getTextRangeById( editor_container_id );
	strFound = currentTRange.findText(str, 10000000, getFindTextFlags());
	if (strFound && containerTRange.inRange(currentTRange)) 
	{
	    currentTRange.select();
	}
	else
	{
	    currentTRange = null;
	    strFound = false;
	}
    }

    if (strFound) foundTRange = currentTRange;
    else foundTRange = false;
}

function replace( editor_container_id, str_find, str_replace )
{
    // replace if something's been found
    if ( foundTRange )
    {
	foundTRange.text = str_replace;
	foundTRange = false;
    }

    // find next
    find( editor_container_id, str_find );
}

function replaceAll( editor_container_id, str_find, str_replace )
{   
    // replace if something's been found
    if ( foundTRange ) foundTRange.text = str_replace;

    // find next until there's no more
    do {
	find( editor_container_id, str_find );
	if ( foundTRange ) foundTRange.text = str_replace;
    } while ( foundTRange != false )
}

function getTextRangeById( theId )
{
    theTRange = callerWindowObj.document.body.createTextRange();
    theTRange.moveToElementText( callerWindowObj.document.getElementById(theId) );
    return (theTRange);
}

function getFindTextFlags()
{
    var theFlags = 0;

    if (document.getElementById('matchCase').checked == true)
	theFlags += 4;
    if (document.getElementById('wholeWordsOnly').checked == true)
	theFlags += 2;

    return (theFlags);
}
</script>
</head>

<body 
    onload="
	/* selectionValue('oFind'); */ 
	OKactive('oFind','replaceButton');
	OKactive('oFind','replaceAllButton');
	OKactive('oFind','findNextButton');
    "
>

<form id="oForm">

<table border="0" cellpadding="10" cellspacing="0" width="100%">
<tr>
<td>
    <!-- The fieldSet element groups form controls into a bordered field.  -->
    <!-- A legend element is used to specify the title of the field. -->
    <fieldset>
	<legend>Find and Replace Text:</legend>
	<table border="0" cellpadding="7" cellspacing="0">
	<tr>
	<td class="label">Search for:</td>
	<td>
	    <input 
		type="text" 
		class="inputText"
		id="oFind" 
		onkeyup="
		    OKactive('oFind','replaceButton');
		    OKactive('oFind','replaceAllButton');
		    OKactive('oFind','findNextButton');
		"
	    />
	</td>
	</tr>

	<tr align="left" valign="middle">
	<td class="label">Replace with:</td>
	<td>
	    <input 
		type="text" 
		id="oReplace"
		class="inputText"
	    />
	</td>
	</tr>

	<tr>
	<td>&nbsp;</td>
	<td>
		<input
		type="button" 
		class="inputButton" 
		id="findNextButton" 
		value="Find Next" 
		disabled="disabled" 
		onclick="find('<?php echo $editorID ?>', document.getElementById('oFind').value);"
	    />
		
	    <input
		type="button" 
		class="inputButton" 
		id="replaceButton" 
		value="Replace" 
		disabled="disabled" 
		onclick="replace('<?php echo $editorID ?>', document.getElementById('oFind').value, document.getElementById('oReplace').value);"
	    />

	    <input
		type="button" 
		class="inputButton" 
		id="replaceAllButton" 
		value="Replace All" 
		disabled="disabled" 
		onclick="replaceAll('<?php echo $editorID ?>', document.getElementById('oFind').value, document.getElementById('oReplace').value);"
	    />

	    <input
		type="button" 
		class="inputButton" 
		id="cancelButton" 
		value="Cancel" 
		onclick="window.close();"
	    />
	</td>
	</tr>

	<tr>
	<td>&nbsp;</td>
	<td>
	    <div>
	    <input
		type="checkbox" 
		class=""
		id="matchCase"
	    /><label for="matchCase" class="label">Match case</label>
	    </div>
	    <div>
	    <input
		type="checkbox" 
		class=""
		id="wholeWordsOnly"
	    /><label for="wholeWordsOnly" class="label">Find whole words only</label>
	    </div>
	</td>
	</tr>
	</table>
    </fieldset>
</td>
</tr>
</table>

</form>

</body>
</html>