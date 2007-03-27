//      this javascript file must be parsed as php
<?
        include ( 'paths.php' );
?>

<!-- //

// FUNCTIONS

var assetLocation="<? echo LOKI_HTTP_PATH; ?>";

function getElement(oElement,oTag) {

	try {
	
		while (oElement!=null && oElement.tagName!=oTag){ oElement = oElement.parentElement; }
  		return oElement;
		
	} catch(e) {}
}

function elem(oRng,oTag) {

	try {

		if (oRng.canHaveChildren==true) { return getElement(oRng,oTag); } // for use with "event.srcElement"
		else { return oRng.parentElement != null ? getElement(oRng.parentElement(),oTag) : getElement(oRng.item(0),oTag); } // for use with "document.selection.createRange()"

	} catch(e) {}
}

function insideElement(oRng,oTag,referenceID) {

	try {

		var oParent = elem(oRng,oTag);
		return referenceID.contains(oParent);
		
	} catch(e) {}
}

function selectionType() {

	try {
	
		var sel = document.selection;
		if (sel.type=="Text" || sel.type=="None") { return true; } // Text
		else { return false; } // Control
	
	} catch(e) {}
}

function exec(command,referenceID) {

	
	try {  
	
		var rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			rng.execCommand(command);
			//rng.parentElement().id="uniqueID";
			referenceID.focus();
		
		}
	
	} catch(e) {}

}

function insertHEADLINE(referenceID) {
	
	try {

		var rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			rng.queryCommandValue("FormatBlock")=="Normal" ? rng.execCommand("FormatBlock", false, "Heading 3") : rng.execCommand("FormatBlock", false, "Normal");
			//rng.parentElement().id="uniqueID";
			referenceID.focus();
		
		}
		
	} catch(e) {}
}

function insertBR(referenceID) {

	try {

		var rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			rng.pasteHTML("<br>");
			rng.select();
			referenceID.focus();
	
		}
		
	} catch(e) {}
}

// Although there's an execCommand to insert an HR, it doesn't start a
// new paragraph, which is a problem -- but pasteHTML does
// Added 2003-12-03 NF
function insertHR(referenceID) {

	try {

		var rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			rng.pasteHTML("<hr>");
			rng.select();
			referenceID.focus();
	
		}
		
	} catch(e) {}
}

function insertLINK(referenceID) {

	try {

		var rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			cleanAnchors(referenceID);
			rng.execCommand("CreateLink", true);
			//rng.parentElement().target="_blank";
			rng.parentElement().setAttribute("loki:href",rng.parentElement().href);
			referenceID.focus();
	
		}

	} catch(e) {}
}

// Rewritten 2003-07-28 by BK
function insertANCHOR(referenceID,siteID) {

	try {

		var arr = null;
		var oWidth = 440;
		var oHeight = 360;
		var rng = document.selection.createRange();
		var idstr = "lokiLink556e6971756";
		
		var args = new Array();
		args["siteID"] = siteID;
		args["href"] = "";
		args["target"] = "";
		args["title"] = "";
		
		// NEW 08/21/2003 BK
		var namedAnchors = new Array();
		var j=0;
		for (i = 0; i < document.images.length; i++) {
			if (referenceID.contains(document.images(i))
				&& document.images(i).getAttribute("loki:is_really_an_anchor_whose_name") != null) {
				namedAnchors[j]=document.images(i).getAttribute("loki:is_really_an_anchor_whose_name");
				j++;
			}
		}
		args["namedAnchors"] = namedAnchors;
	
		// Are we already inside an anchor tag?
		var insideAnchor = insideElement(rng,"A",referenceID);
		if (insideAnchor==true) {
			var oldAnchor = elem(rng,"A");
			args["href"] = oldAnchor.getAttribute("href");
			args["target"] = oldAnchor.getAttribute("target");
			args["title"] = oldAnchor.getAttribute("title");
		}
	
		var phpArgs = "site_id=" + siteID;
		var modalURL = assetLocation + "dialogs/lokiLink.php?" + phpArgs;
		var modalTitle = "Insert Link";
		var loadingURL = assetLocation + "dialogs/lokiLoading.php?dialog=" + escape(modalURL) + "&window_title=" + escape(modalTitle);
	
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			arr = window.showModalDialog(loadingURL,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
	
			if ( arr != null ) {
			
				rng.execCommand("CreateLink",false,idstr);
				//var oAnchor = elem(rng,"A");
				//oAnchor.setAttribute("target","_blank");
				var oAnchors = document.all.tags("A");  
				if (oAnchors != null) {
					for (var i = oAnchors.length - 1; i >= 0; i--) {
						if (oAnchors[i].href == idstr) {
							oAnchors[i].setAttribute("href",arr["href"]);
							oAnchors[i].setAttribute("loki:href",arr["href"]);
							
							if (arr['target']==true) { oAnchors[i].setAttribute('target','_blank');
							} else { oAnchors[i].removeAttribute('target'); }
				
							if (arr['title']!='') { oAnchors[i].setAttribute('title',arr['title']);
							} else { oAnchors[i].removeAttribute('title'); }
							
							if (rng.htmlText=="") { // if nothing is selected
								if (arr["href"].substring(0,4)=="http") { oAnchors[i].innerHTML=arr["href"];
								} else { oAnchors[i].removeAttribute("href"); // getting rid of the href causes tidy to remove the forgotten anchor
								}
							}
							
						}
					}
					if (arr["removeLink"]==true) { rng.execCommand("Unlink"); }
				}
			}
		}
	} catch(e) {}
}

// Added 2004-02-06 NF
function preloadModalLink(site_id, iframe) {
	var phpArgs = "site_id="   + site_id;
	document.getElementById(iframe).src = assetLocation + "dialogs/lokiLink.php?" + phpArgs;
}

// Rewritten 2003-04-09 by Nate
/* function insertANCHOR(referenceID,siteID) {

	var arr = null;
	var oWidth = 440;
	var oHeight = 360;
	var rng = document.selection.createRange();
	var args = new Array();
	args["siteID"] = siteID;
	args["href"] = rng.parentElement().getAttribute("href");
	args["target"] = rng.parentElement().getAttribute("target");
	args["title"] = rng.parentElement().getAttribute("title");
	
	var phpArgs = "site_id="   + siteID;
// 	phpArgs    += "&href="     + escape(rng.parentElement().getAttribute("href"));
// 	phpArgs    += "&target="   + escape(rng.parentElement().getAttribute("target"));
// 	phpArgs    += "&title="    + escape(rng.parentElement().getAttribute("title"));

	rng2 = rng.duplicate();
	rng2.moveToElementText(referenceID);
	
	if (rng2.inRange(rng)) {
	
		arr = window.showModalDialog(assetLocation + "dialogs/lokiLink.php?" + phpArgs,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
	
		if ( arr != null ) {

			// I. Find or create the anchor
			var oFindAnchor = getElement(rng.parentElement(),'A');
			if (oFindAnchor != null) {
				var oAnchor = oFindAnchor;
			}
			else {
				var oHtmlText = rng.htmlText;
				oHtmlText = oHtmlText.replace( /<a\s+.*?>/gi, "" );
				oHtmlText = oHtmlText.replace( /<\/a>/gi, "" );
				if (oHtmlText=='') { oHtmlText = arr['href']; }

				rng.pasteHTML('<a id="lokiCurrentAnchor">' + oHtmlText + '</a>');

				var oAnchor = document.getElementById('lokiCurrentAnchor');
			}

			// II. Set the anchor's attributes
			if (arr['href'] == '') {
				oAnchor.outerHTML = oAnchor.innerHTML;
			}
			else {
				oAnchor.setAttribute('href',arr['href']);
				oAnchor.setAttribute('loki:href',arr['href']);
				
				if (arr['target']==true) oAnchor.setAttribute('target','_blank');
				else oAnchor.removeAttribute('target');
				
				if (arr['title']!='') oAnchor.setAttribute('title',arr['title']);
				else oAnchor.removeAttribute('title');
				
				if (oAnchor.id='lokiCurrentAnchor') oAnchor.removeAttribute('id');

				//if (oAnchor.innerHTML == arr['href']) oAnchor.innerHTML = arr['filename'];
			}
		}
	}
} */

function insertIMAGE(referenceID) {

	try {

		referenceID.focus();
		//var rng = document.body.createControlRange();
		var rng = document.selection.createRange();
		rng.execCommand("InsertImage", true);
		referenceID.focus();

	} catch(e) {}
}

function noControlResize() { // Does not allow tables, images to be resized

	try {

		event.returnValue = false;
		
	} catch(e) {}
}

function indent(referenceID) {
	
	try {

		var rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			rng.execCommand("Indent");
	
			//var newCol=document.all.tags("BLOCKQUOTE");
			var newCol=referenceID.getElementsByTagName("BLOCKQUOTE");
	
			for (i=0;i<newCol.length;i++) {
		
				newCol.item(i).removeAttribute("style");
				newCol.item(i).removeAttribute("dir");
			}
			referenceID.focus();
		}
		
	} catch(e) {}
}

//-----------------------------------------------
// Open an edit-options window
//-----------------------------------------------

function openModal(URL,oWidth,oHeight) {

	try {

  		window.showModalDialog(assetLocation + URL,window,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off");

	} catch(e) {}
}

//-----------------------------------------------
// OK button is active only after text has been entered
//-----------------------------------------------

function OKactive(oFieldName,oButtonName) {

	try {

		if (document.getElementById(oFieldName).value != "") { document.getElementById(oButtonName).disabled = false; }
		else { document.getElementById(oButtonName).disabled = true; }

	} catch(e) {}
}

function isDigit() {
  
	try {

		return ((event.keyCode >= 48) && (event.keyCode <= 57));

	} catch(e) {}
}

function getRadioValue (radioButtonOrGroup) {
  
	try {

		var value = null;
  		if (radioButtonOrGroup.length) { // group 
    	
			for (var b = 0; b < radioButtonOrGroup.length; b++)
      		if (radioButtonOrGroup[b].checked)
        	value = radioButtonOrGroup[b].value;
  		}
  		else if (radioButtonOrGroup.checked)
    	value = radioButtonOrGroup.value;
  		return value;
	
	} catch(e) {}
}

function insertTABLE(referenceID) {

	try {

		var arr = null;
		var oWidth=500;
		var oHeight=500;
		var rng=document.selection.createRange();
		var args = new Array();
	
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			// does not allow nested tables
			var inTd = insideElement(rng,"TD",referenceID);
			if (!inTd) { arr = window.showModalDialog(assetLocation + "dialogs/lokiTable.php",args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); }
		
			if (arr!=null) {
	
				var oRow = arr["oRow"];
				var oCol = arr["oCol"];
				var oBorder = arr["oBorder"];
				var oSummary = arr["oSummary"];
				var oAltrows = arr["oAltrows"];
				var oTableColor = "";
				var oTdColor = "";
	
				if (arr["oColor"]!=null) {
				
					if (oAltrows) { var oTableColor = " altrows='true' row1='" + arr["oRow1"] + "' row2='" + arr["oRow2"] + "'"; /* Alternationg row colors */
					} else { var oTdColor = " class='" + arr["oColor"] + "'"; var oTableColor =  " class='" + arr["oColor"] + "'"; /* Background class color */ }

				}
		
				var oTable = "<table cellpadding='5' cellspacing='0' border='" + oBorder + "' summary='" + oSummary + "' cols='" + oCol + "'" + oTableColor + ">\n";
		
				for (i=1;i<=oRow;i++) {

					oTable += "<tr>\n";

					for (j=1;j<=oCol;j++) {
  
  						if (oAltrows) { oTable += "<td align='left' valign='top' class='" + (i%2==0 ? arr["oRow2"] : arr["oRow1"]) + "'></td>\n";
						} else { oTable += "<td align='left' valign='top'" + oTdColor + "></td>\n"; }
					}

					oTable += "</tr>\n";
  
				}

				oTable += "</table>\n"
		
				rng.pasteHTML(oTable);
				rng.select();
    			referenceID.focus();
		
				tableBorders(referenceID);
			}
  		}
		
	} catch(e) {}
}

function updateTABLE(referenceID) {

	try {
	
		var rng = document.selection.createRange();
		var oTable = elem(rng,"TABLE");
		var nRows = oTable.rows.length; //num of rows
		var nCols = getColumns(oTable);
		var args = new Array();
		var arr = null;
		var oWidth=400;
		var oHeight=400;
		
		args["cols"] = nCols;
		args["rows"] = nRows;
		args["border"] = oTable.getAttribute("border");
		args["summary"] = oTable.getAttribute("summary");
		args["className"] = oTable.getAttribute("className");
		args["altrows"] = oTable.getAttribute("altrows");
		args["row1"] = oTable.getAttribute("row1");
		args["row2"] = oTable.getAttribute("row2");
		
		arr = window.showModalDialog(assetLocation + "dialogs/lokiTable.php",args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off");

		if (arr!=null) {
		
			oTable.setAttribute("summary",arr["oSummary"]);
			
			if (arr["oColor"]!=null) { 
			
				oTable.removeAttribute("altrows");
				oTable.removeAttribute("row1");
				oTable.removeAttribute("row2");
				oTable.setAttribute("className",arr["oColor"]);
				for (i=0; i < oTable.cells.length; i++) {
				
					oTable.cells[i].setAttribute("className",arr["oColor"]);
    			}
				
			} else { // no background
			
				oTable.removeAttribute("altrows");
				oTable.removeAttribute("row1");
				oTable.removeAttribute("row2");
				for (i=0; i < oTable.cells.length; i++) {
				
					oTable.cells[i].removeAttribute("className");
    			}
			
			}
			
			if (arr["oAltrows"]!=null) { 
				
				oTable.removeAttribute("className");
				oTable.setAttribute("altrows",arr["oAltrows"]);
				oTable.setAttribute("row1",arr["oRow1"]);
				oTable.setAttribute("row2",arr["oRow2"]);
				
				for (i=0; i < oTable.cells.length; i++) {
				
        			oTable.cells[i].setAttribute("className",arr["oRow1"]);
    			}
				
				redrawTable(oTable);
				
			}
			if (arr["oBorder"]==1) {
			
				oTable.setAttribute("border",1);
				oTable.runtimeStyle.borderWidth = 1;
				oTable.runtimeStyle.borderColor = "";
				oTable.runtimeStyle.borderStyle = "";
				oTable.runtimeStyle.borderCollapse = "";
			
    			for (j=0; j < oTable.cells.length; j++) {
				
        			oTable.cells[j].runtimeStyle.borderWidth = 1;
					oTable.cells[j].runtimeStyle.borderColor = "";
					oTable.cells[j].runtimeStyle.borderStyle = "";
					oTable.cells[j].runtimeStyle.borderCollapse = "";
    			}
			
			} else { oTable.setAttribute("border",0); tableBorders(referenceID); }
			
		}
		
	} catch(e) {}
}

function tableBorders(referenceID) {

	try {

		var oTables = referenceID.getElementsByTagName("TABLE");
	
		for (i=0;i<oTables.length;i++){
	
			if(oTables[i].border == 0){
		
				oTables[i].setAttribute("cellPadding",5);
				oTables[i].setAttribute("cellSpacing",0);
				oTables[i].setAttribute("border",0);
				oTables[i].runtimeStyle.borderWidth = 1;
				oTables[i].runtimeStyle.borderColor = "#BCBCBC";
				oTables[i].runtimeStyle.borderStyle = "dotted";
				oTables[i].runtimeStyle.borderCollapse = "collapse";
			
    			for (j=0; j < oTables[i].cells.length; j++) {
        			oTables[i].cells[j].runtimeStyle.borderWidth = 1;
					oTables[i].cells[j].runtimeStyle.borderColor = "#BCBCBC";
					oTables[i].cells[j].runtimeStyle.borderStyle = "dotted";
					oTables[i].cells[j].runtimeStyle.borderCollapse = "collapse";
    			}
			}
		}
		
	} catch(e) {}
}

//////////////////

function do_onpaste(editor_id){
    
	try {

		event.returnValue=false;

    	objSelection = document.selection.createRange();

    	document.getElementById('clipboard_div').innerHTML = '';
    	document.getElementById('clipboard_div').focus();
    	document.execCommand("Paste");

    	var clipboard_raw = document.getElementById('clipboard_div').innerHTML;
    	clipboard_clean = strip_attributes( clipboard_raw );
    	// for testing
    	//clipboard_clean = clipboard_clean + '<hr />' + clipboard_raw;

    	objSelection.pasteHTML(clipboard_clean);
    	document.getElementById(editor_id).focus();

	} catch(e) {}
}

function strip_attributes(text_raw) {

    try {

		// 1. Remove the pseudo-xml namspaces
    	text_clean = text_raw.replace( /<\?(\w*).*?>/gi, "" );

    	// 2. Remove all <o:xxx> tags
    	text_clean = text_clean.replace( /<(\/?)(o\:\w*).*?>/gi, "" );

    	// 3. Remove all <stl:xxx> tags
    	text_clean = text_clean.replace( /<(\/?)(stl\:\w*).*?>/gi, "" );

    	// 3. Remove all <span> tags
    	text_clean = text_clean.replace( /<(\/?)(span).*?>/gi, "" );

    	// 3. Remove all <font> tags
    	text_clean = text_clean.replace( /<(\/?)(font).*?>/gi, "" );

    	// 4. Remove all attributes except href
    	text_clean = text_clean.replace( /<(\/?)(\w*)((?:\s*?href=\"\S*?\")?).*?>/gi, "<$1$2$3>" );
	
	 	// 5. Remove all <p>&nbsp;</p>
    	text_clean = text_clean.replace( /<p>\&nbsp;<\/p>/gi, "");

		// 6. Remove all <u> tags
    	text_clean = text_clean.replace( /<(\/?)(u)>/gi, "" );
	
		// 8. Remove all &nbsp; tags
    	text_clean = text_clean.replace( /\&nbsp;/gi, "" );
	
		// 9. Replace all <h4> tags with <h6> tags
    	text_clean = text_clean.replace( /<(\/?)(h[7654321]).*?>/gi, "<$1h3>" );
	
    	return (text_clean);
		
	} catch(e) {}
}

function do_onmoveineditor(){
    
	try {

		TRange = document.selection.createRange();

	} catch(e) {}
}

//// CONTEXT MENU STUFF
var oPopup = window.createPopup();
var oPopupBody = oPopup.document.body;

function showContextMenu(referenceID,siteID) {

 	try {
	
		var elementName = event.srcElement.tagName;

		oPopupBody.style.backgroundColor = "threedface";
 		oPopupBody.style.border = "outset 2px";
 		oPopupBody.style.fontFamily = "Tahoma";
 		oPopupBody.style.fontSize = "11px";
	
		// reset the popup if redisplaying
		oPopupBody.innerHTML = "";
	
		// DEFAULT MENU
		
			// Cut
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Cut";
			el.onclick = function() { closePopup(); exec("Cut",referenceID); }
			oPopupBody.appendChild(el);
		
			// Copy
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Copy";
			el.onclick = function() { closePopup(); exec("Copy",referenceID); }
			oPopupBody.appendChild(el);
		
			// Paste
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Paste";
			el.onclick = function() { closePopup(); referenceID.document.execCommand("Paste"); }
			oPopupBody.appendChild(el);
	
		// TABLE MENU
		if (event.srcElement.tagName=="TABLE") {
		
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
	
			// Table Properties
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Table Properties";
			el.onclick = function() { updateTABLE(referenceID); }
			oPopupBody.appendChild(el);
	
		}
	
		// TD MENU
		if (insideElement(event.srcElement,"TD",referenceID)) {
	
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
	
			// Cell Properties
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Cell Properties";
			el.onclick = function() { closePopup(); tableCellProperties(); }
			oPopupBody.appendChild(el);
		
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
		
			// Insert Column
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Insert Column";
			el.onclick = function() { closePopup(); tableInsertCol("right",referenceID); }
			oPopupBody.appendChild(el);	
		
			// Delete Column
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Delete Column";
			el.onclick = function() { closePopup(); tableDeleteCol(referenceID); }
			oPopupBody.appendChild(el);	
		
			// Merge Columns
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Merge Columns";
			el.onclick = function() { closePopup(); tableColSpan(referenceID); }
			oPopupBody.appendChild(el);
		
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
		
			// Insert Row
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Insert Row";
			el.onclick = function() { closePopup(); tableInsertRow("below",referenceID); }
			oPopupBody.appendChild(el);
		
			// Delete Row
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Delete Row";
			el.onclick = function() { closePopup(); tableDeleteRow(referenceID); }
			oPopupBody.appendChild(el);
	
		}
		
		// Added 2003-08-01 BK
		// COLORED BACKGROUND BLOCK ELEMENT MENU
		// 9966 is site id of English Dept.
		// NOT READY YET
		/* if (((siteID==150000000)||(siteID==2822))&&((insideElement(event.srcElement,"P",referenceID)) || (insideElement(event.srcElement,"H3",referenceID)) || (insideElement(event.srcElement,"H2",referenceID)) || (insideElement(event.srcElement,"H1",referenceID))) && (!(insideElement(event.srcElement,"TD",referenceID)) && !(insideElement(event.srcElement,"TABLE",referenceID)))) {
			
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);

			// Highlight Paragraph
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Color Background";
			el.onclick = function() { closePopup(); colorBackground(elementName); }
			oPopupBody.appendChild(el);
		
		} */
		
		// P MENU
		if ((insideElement(event.srcElement,"P",referenceID)) && !(insideElement(event.srcElement,"TD",referenceID)) && !(insideElement(event.srcElement,"TABLE",referenceID))) {
		
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
		
			// Highlight Paragraph
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Highlight Paragraph";
			el.onclick = function() { closePopup(); specialP(); }
			oPopupBody.appendChild(el);
			
			// Paragraph get turned into Pullquote
			// Added 8/13/2003 BK
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Pullquote";
			el.onclick = function() { closePopup(); pullQuote(); }
			oPopupBody.appendChild(el);
		
		}
		
		// Added 2003-04-22 by Nate
		// OL MENU
		if (insideElement(event.srcElement,"OL",referenceID)) {
	
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
	
			// Upper Roman
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Upper Roman";
			el.onclick = function() { closePopup(); olUpdateClass("upperRoman"); }
			oPopupBody.appendChild(el);

			// Lower Roman
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Lower Roman";
			el.onclick = function() { closePopup(); olUpdateClass("lowerRoman"); }
			oPopupBody.appendChild(el);

			// Upper Alpha
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Upper Alpha";
			el.onclick = function() { closePopup(); olUpdateClass("upperAlpha"); }
			oPopupBody.appendChild(el);

			// Lower Alpha
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Lower Alpha";
			el.onclick = function() { closePopup(); olUpdateClass("lowerAlpha"); }
			oPopupBody.appendChild(el);

			// Decimal
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Decimal";
			el.onclick = function() { closePopup(); olUpdateClass("decimal"); }
			oPopupBody.appendChild(el);
		}
		
		// Added 2003-05-13 by BK
		// BLOCK ELEMENT (FOR DIR AND LANG) MENU
		if ((insideElement(event.srcElement,"P",referenceID)) || (insideElement(event.srcElement,"H3",referenceID)) || (insideElement(event.srcElement,"H2",referenceID)) || (insideElement(event.srcElement,"H1",referenceID)) || (insideElement(event.srcElement,"OL",referenceID)) || (insideElement(event.srcElement,"UL",referenceID))) {
			
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
			
			// TESTING LANG AND DIR
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Direction Options";
			el.onclick = function() { closePopup(); directionOptions(referenceID); }
			oPopupBody.appendChild(el);
			
		}
	
		// END DEFAULT MENU
	
			// MENU SEPARATOR
			var el = oPopup.document.createElement("<div>")
			el.innerHTML = "<hr>";
			el.setAttribute('unselectable','on');
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px; padding: 2px; overflow: hidden;";
			oPopupBody.appendChild(el);
	
			// Select All
			var el = oPopup.document.createElement("<div>");
			el.style.cssText = "cursor: default; width: 100%; height: 17px; align: center; margin: 0px 1px; padding: 2px 20px;";
			el.attachEvent('onmouseover',contextHilite);
			el.attachEvent('onmouseout',contextDelite);
			el.setAttribute('unselectable','on');
			el.innerHTML = "Select All";
			el.onclick = function() { closePopup(); referenceID.document.execCommand("SelectAll"); }
			oPopupBody.appendChild(el);
	
		oHeight = (oPopupBody.children.length * 17)+5;
 		oPopup.show(event.clientX+2, event.clientY+2, 150, oHeight, document.body);
 		document.body.onmouseup = closePopup;

	} catch(e) {}
}

function closePopup() {

	try {

		oPopup.hide();
	
	} catch(e) {}
}

function specialP() {

	try {

		var rng = document.selection.createRange();
		var oP = elem(rng,"P");

		if (oP.getAttribute("className")=="callOut") { oP.removeAttribute("className"); }
		else { oP.setAttribute("className","callOut"); }
	
	} catch(e) {}
}

function contextHilite(event) {

    try {

		event.srcElement.runtimeStyle.backgroundColor = "Highlight";

    	if (event.srcElement.state){ event.srcElement.runtimeStyle.color = "GrayText"; }
		else { event.srcElement.runtimeStyle.color = "HighlightText"; }

	} catch(e) {}
}

function contextDelite(event) {

    try {

		event.srcElement.runtimeStyle.backgroundColor = "";
		event.srcElement.runtimeStyle.color = "";

	} catch(e) {}
}
	
///// TABLE STUFF

function getColumns(oTable) {
	
	try {

		var nColSpan = 0;
		var nCols = oTable.rows[0].cells.length;
		for (i=0;i<nCols;i++) {
	
			nColSpan += oTable.rows[0].cells[i].colSpan;
	
		}
		return nColSpan;

	} catch(e) {}
}

function tableInsertCol(type,referenceID) {
	
	try {

		var rng = document.selection.createRange();
	
		var inTd = insideElement(rng,"TD",referenceID);
		if (inTd) {
	
			var oTable = elem(rng,"TABLE");
			var oTr = elem(rng,"TR");
			var oTd = elem(rng,"TD");
			var iCol;
			iCol = oTd.cellIndex; //insert left
			if(type=="right") { iCol=iCol+1; } //insert right
			var numofrows = oTable.rows.length; //num of rows
		
			for (var i=0;i<numofrows;i++) {
		
				try {
				
					if (oTable.rows[i].cells[iCol-1]) { var elCell = oTable.rows[i].insertCell(iCol); }
					else { var elCell = oTable.rows[i].insertCell(-1); }
				
					//var elCell = oTable.rows[i].insertCell(iCol);
					var previousClassName = elCell.previousSibling.getAttribute("className");
				
					elCell.innerHTML = "";
					if (previousClassName != null) { elCell.setAttribute("className",previousClassName); }
					elCell.setAttribute("vAlign","top");
					elCell.setAttribute("align","left");
			
				} catch(e) {}		
			}
			if (oTable.cols!="") { oTable.setAttribute("cols",oTable.cols+1); }
			tableBorders(referenceID);
		}
		
	} catch(e) {}	
}

function tableDeleteCol(referenceID) {

	try {

		var rng = document.selection.createRange();
	
		var inTd = insideElement(rng,"TD",referenceID);
		if (inTd) {
	
			var oTable = elem(rng,"TABLE");
			var oTd = elem(rng,"TD");
			var iCol = oTd.cellIndex;
			var numofrows = oTable.rows.length;
			for (var i=0;i<numofrows;i++) {
		
				// this needed to manage colspans across multiple columns
				var thisTable = oTable.rows[i];
				var thisTd = thisTable.cells[iCol];
				if (thisTd.colSpan!=1) {
		
					oColspan = thisTd.getAttribute("colSpan");
					var newTd = thisTable.insertCell(iCol+1);
					newTd.colSpan = oColspan-1;
					newTd.innerHTML = thisTd.innerHTML;
				}
		
				try { oTable.rows[i].deleteCell(iCol); }
				catch(e) {}		
			}
			if (oTable.cols!="") { oTable.setAttribute("cols",oTable.cols-1); }
		}
		
	} catch(e) {}
}

function tableInsertRow(type,referenceID) {

	try {

		var rng = document.selection.createRange();
	
		var inTd = insideElement(rng,"TD",referenceID);
		if (inTd) {
	
			var oTable = elem(rng,"TABLE");
			var oTr = elem(rng,"TR");
			var oTd = elem(rng,"TD");
		
			//var previousClassName = oTd.getAttribute("className");
			var previousClassName = oTable.getAttribute("className");
			if (oTable.altrows) { previousClassName = oTable.getAttribute("row1"); }
		
			var numofrows = oTable.rows.length; //num of rows
			var i,iRow;
			for (i=0;i<numofrows;i++) { if(oTr==oTable.rows[i]) iRow=i; }
		
			var numofcols = getColumns(oTable);
		
			var elRow;
			if(type=="above") elRow = oTable.insertRow(iRow); //insert above
			if(type=="below") elRow = oTable.insertRow(iRow+1); //insert below
	
			for (i=0;i<numofcols;i++) {
				try {
				
					var elCell = elRow.insertCell();
					elCell.innerHTML = "";
					if (previousClassName != "") { elCell.setAttribute("className",previousClassName); }
					elCell.setAttribute("vAlign","top");
					elCell.setAttribute("align","left");
				}
				catch(e) {}
			}
			tableBorders(referenceID);
			if (oTable.getAttribute("altrows")) { redrawTable(oTable); }
		}

	} catch(e) {}	
}

function tableDeleteRow(referenceID) {

	try {

		var rng = document.selection.createRange();
	
		var inTd = insideElement(rng,"TD",referenceID);
		if (inTd) {
	
			var oTable = elem(rng,"TABLE");
			var oTr = elem(rng,"TR");
	
			oTable.deleteRow(oTr.rowIndex);
			if (oTable.getAttribute("altrows")) { redrawTable(oTable); }
		}
		
	} catch(e) {}
}

function tableColSpan(referenceID) {

	try {

		var rng = document.selection.createRange();
		var inTd = insideElement(rng,"TD",referenceID);
		if (inTd) {
	
			var oTable = elem(rng,"TABLE");
			var oTr = elem(rng,"TR");
			var oTd = elem(rng,"TD");
			var nextTd = oTd.nextSibling;
	
			if (nextTd != null) {
	
				oColspan1 = oTd.getAttribute("colSpan");
				oColspan2 = nextTd.getAttribute("colSpan");
		
				oTd.colSpan = oColspan1 + oColspan2;
				oTd.innerHTML += nextTd.innerHTML;
				oTable.rows[oTr.rowIndex].deleteCell(nextTd.cellIndex);
	
			}
		}

	} catch(e) {}
}

function redrawTable(oTable) {

	try {

		oRow1 = oTable.getAttribute("row1");
		oRow2 = oTable.getAttribute("row2");
		
		for (i=0; i < oTable.rows.length; i++) {
        	for (j=0; j < oTable.rows(i).cells.length; j++) {
		
				if ((oTable.rows(i).cells(j).getAttribute("className")!=oRow1) && (oTable.rows(i).cells(j).getAttribute("className")!=oRow2)) { /* do nothing */}
				else { (i%2==0 ? oTable.rows(i).cells(j).setAttribute("className",oRow2) : oTable.rows(i).cells(j).setAttribute("className",oRow1)); }
        	}
    	}

	} catch(e) {}
}

function tableCellProperties() {
	
	try {

		var rng = document.selection.createRange();
		var oTr = elem(rng,"TR");
		var oTd = elem(rng,"TD");
		var oRow = oTr.rowIndex;
		var oCell = oTd.cellIndex;
	
		var arr = null;
		var args = new Array();
		var oWidth=420;
		var oHeight=250;
	
		args["align"] = oTd.getAttribute("align");
		args["vAlign"] = oTd.getAttribute("vAlign");
		args["className"] = oTd.getAttribute("className");
		args["nowrap"] = oTd.getAttribute("nowrap");
	
		args["row"] = oRow;
		args["cell"] = oCell;
	
		arr = window.showModalDialog(assetLocation + "dialogs/lokiCellProperties.php",args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off");
	
		if (arr!=null) {
	
			oTd.setAttribute("align",arr["oAlign"]);
			oTd.setAttribute("vAlign",arr["oValign"]);
		
			if (arr["oNowrap"]==true) { oTd.setAttribute("noWrap","noWrap"); }
			else { oTd.removeAttribute("noWrap");  }
		
			switch(arr["oColor"]) {
		
				case "none" : oTd.removeAttribute("className"); break;
				case "current" : break;
				default: oTd.setAttribute("className",arr["oColor"]);
		
			}
		}

	} catch(e) {}
}

function cleanApostrophes(dirtyString) {

	try {

		cleanString = dirtyString.replace( /\'/gi, "&#39;" );
		cleanString = cleanString.replace( /\"/gi, '&#34;' );
		return cleanString;

	} catch(e) {}
}

function cleanAnchors(referenceID) {
	
	for(i=0; i<document.links.length; i++) {
		
		try {
		
			if (referenceID.contains(document.links(i))) {
		
				if (document.links(i).getAttribute("loki:href") != null) {
					document.links(i).setAttribute("href",document.links(i).getAttribute("loki:href"));
					//document.links(i).removeAttribute("loki:href");
				}
		
			}
			
		} catch(e) {}
	
	}

}

function cloneHrefs(original) {
	try {
	var changed = original.replace( / href="(.*?)"/gi, ' href="$1" loki:href="$1"' );
	changed = changed.replace( / onclick="(.*?)"/gi, ' loki:onclick="$1"' );
	return changed;
	} catch(e) {}
}

function uncloneHrefs(original) {
	try {
	var changed = original.replace( /( loki:href=".*?")/gi, '' );
	changed = changed.replace( / loki:onclick="(.*?)"/gi, ' onclick="$1"' );
	return changed;
	} catch(e) {}
}

function removeCloneHrefs(referenceID) {
	
	for(i=0; i<document.links.length; i++) {
		
		try {
				document.links(i).removeAttribute("loki:href");
			
		} catch(e) {}
	
	}

}

// ADDED 03072003 BK
// See insertImageLink() below for more info
function insertImageLister(referenceID, siteID) {

	//try {
	
		var arr = null;
		var oWidth=500;
		var oHeight=400;
		var rng = document.selection.createRange();
		var idstr = "lokiImage556e6971756";
		
		var args = new Array();
		args["siteID"] = siteID;
		args["href"] = "";
		args["target"] = "";
		args["title"] = "";
		args["rng"] = rng;
		args["doc"] = document;
	
		// Are we already inside an anchor tag?
		var insideAnchor = insideElement(rng,"A",referenceID);
		if (insideAnchor==true) {
			var oldAnchor = elem(rng,"A");
			args["href"] = oldAnchor.getAttribute("href");
			args["target"] = oldAnchor.getAttribute("target");
			args["title"] = oldAnchor.getAttribute("title");
		}
	
		var phpArgs = "site_id=" + siteID;
		var modalURL = assetLocation + "dialogs/lokiImage.php?" + phpArgs;
		var modalTitle = "Insert Image or Link to Image";
		var loadingURL = assetLocation + "dialogs/lokiLoading.php?dialog=" + escape(modalURL) + "&window_title=" + escape(modalTitle);

		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
			window.showModelessDialog(loadingURL,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off");
		}

	//} catch(e) {}
}
// This function is called from inside dialogs/lokiImage.php. The
// reason that insertImage works differently from other dialogs which
// involve making links is that lokiIimage handles both inserting
// images, via drag-and-drop, and inserting links. And to
// drag-and-drop, the dialog has to be modeless. And as a result of
// its being modless, the script doesn't wait for the window to
// return. So we have to call another function from within the window
// which takes care of the second half of the process (i.e., the
// inserting). Hence, this function.
function insertImageLink(arr)
{
	//try {

		if ( arr != null ) {
					
			var rng = arr['rng'];
			var doc = arr['doc']; 
			var idstr = "lokiImage556e6971756";

			if (rng.htmlText!="") { // must be a selection

				rng.execCommand("CreateLink",false,idstr);
				//var oAnchor = elem(rng,"A");
				//oAnchor.setAttribute("target","_blank");
				var oAnchors = doc.all.tags("A");  
				if (oAnchors != null) {
					for (var i = oAnchors.length - 1; i >= 0; i--) {
						if (oAnchors[i].href == idstr) {
							oAnchors[i].setAttribute("href",arr["href"]);
							oAnchors[i].setAttribute("loki:href",arr["href"]);
							oAnchors[i].setAttribute("loki:onclick",arr["onclick"]);
							oAnchors[i].setAttribute('target','_blank');
						}
					}
				}
			}
			if (arr["removeLink"]==true) { rng.execCommand("Unlink"); }
		}

	//} catch(e) {}
}

// Added 2004-02-06 NF
function preloadModalImage(siteID, iframe) {
	document.getElementById(iframe).src = "/admin/scripts/image_list.php?site_id="+siteID;
}

// Rewritten 2003-07-28 by BK
function insertLinkToAsset(referenceID,siteID) {

	try {

		var arr = null;
		var oWidth = 450;
		var oHeight = 230;
		var rng = document.selection.createRange();
		var idstr = "lokiAsset556e6971756";
		
		var args = new Array();
		args["siteID"] = siteID;
		args["href"] = "";
		args["target"] = "";
		args["title"] = "";
	
		// Are we already inside an anchor tag?
		var insideAnchor = insideElement(rng,"A",referenceID);
		if (insideAnchor==true) {
			var oldAnchor = elem(rng,"A");
			args["href"] = oldAnchor.getAttribute("href");
			args["target"] = oldAnchor.getAttribute("target");
			args["title"] = oldAnchor.getAttribute("title");
		}
	
		var phpArgs = "site_id=" + siteID;
		var modalURL = assetLocation + "dialogs/lokiLinkToAsset.php?" + phpArgs;
		var modalTitle = "Insert Link to Asset";
		var loadingURL = assetLocation + "dialogs/lokiLoading.php?dialog=" + escape(modalURL) + "&window_title=" + escape(modalTitle);

		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);
	
		if (rng2.inRange(rng)) {
	
			arr = window.showModalDialog(loadingURL,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
	
			if ( arr != null ) {
				
				if (rng.htmlText!="") { // must be a selection
			
					rng.execCommand("CreateLink",false,idstr);
					//var oAnchor = elem(rng,"A");
					//oAnchor.setAttribute("target","_blank");
					var oAnchors = document.all.tags("A");  
					if (oAnchors != null) {
						for (var i = oAnchors.length - 1; i >= 0; i--) {
							if (oAnchors[i].href == idstr) {
								oAnchors[i].setAttribute("href",arr["href"]);
								oAnchors[i].setAttribute("loki:href",arr["href"]);
								oAnchors[i].setAttribute('target','_blank');
							}
						}
					}
				}
				if (arr["removeLink"]==true) { rng.execCommand("Unlink"); }
			}
		}
	} catch(e) {}
}

// Added 2004-02-06 NF
function preloadModalLinkToAsset(site_id, iframe) {
	var phpArgs = "site_id="   + site_id;
	document.getElementById(iframe).src = assetLocation + "dialogs/lokiLinkToAsset.php?" + phpArgs;
}

// Added 2003-04-01 by Nate
/* function insertLinkToAsset(referenceID,siteID) {

	var arr = null;
	var oWidth = 450;
	var oHeight = 200;
	var rng = document.selection.createRange();
	var args = new Array();
	args["siteID"] = siteID;
	args["href"] = rng.parentElement().getAttribute("href");
	
	var phpArgs = "site_id="   + siteID;

	rng2 = rng.duplicate();
	rng2.moveToElementText(referenceID);
	
	if (rng2.inRange(rng)) {
	
		arr = window.showModalDialog(assetLocation + "dialogs/lokiLinkToAsset.php?" + phpArgs,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
		
		if ( arr != null ) {

			// I. Find or create the anchor
			var oFindAnchor = getElement(rng.parentElement(),'A');
			if (oFindAnchor != null) {
				var oAnchor = oFindAnchor;
			}
			else {
				var oHtmlText = rng.htmlText;
				oHtmlText = oHtmlText.replace( /<a\s+.*?>/gi, "" );
				oHtmlText = oHtmlText.replace( /<\/a>/gi, "" );
				if (oHtmlText=='') { oHtmlText = arr['href']; }

				rng.pasteHTML('<a id="lokiCurrentAnchor">' + oHtmlText + '</a>');

				var oAnchor = document.getElementById('lokiCurrentAnchor');
			}

			// II. Set the anchor's attributes
			if (arr['href'] == '') {
				oAnchor.outerHTML = oAnchor.innerHTML;
			}
			else {
				oAnchor.setAttribute('href',arr['href']);
				oAnchor.setAttribute('loki:href',arr['href']);
			
				if (arr['target']==true) oAnchor.setAttribute('target','_blank');
				else oAnchor.removeAttribute('target');

				if (oAnchor.id='lokiCurrentAnchor') oAnchor.removeAttribute('id');

				if (oAnchor.innerHTML == arr['href']) oAnchor.innerHTML = arr['name'];
			}
		}

	}
} */
// Added 2003-04-22 by Nate
function olUpdateClass(className) {

	try {

		var rng = document.selection.createRange();
		var oOl = elem(rng,"OL");
		oOl.setAttribute("className",className);

	} catch(e) {}
}
/// TESTING
function insertDir() {
	try {  
	
		var rng = document.selection.createRange();
		if (rng.queryCommandValue("BlockDirRTL")) { rng.execCommand("BlockDirLTR"); rng.execCommand("JustifyLeft"); }
		else { rng.execCommand("BlockDirRTL"); rng.execCommand("JustifyRight"); }
	
	} catch(e) {}
}
// Added 2003-05-23 by Nate
function directionOptions(referenceID) {
  	try {  

		var arr = null;
		var oWidth = 250;
		var oHeight = 130;
		var rng = document.selection.createRange();
		var args = new Array();
		if (rng.queryCommandValue("BlockDirRTL")) args["direction"] = "rtl";
		else                                      args["direction"] = "ltr";
		
		var phpArgs = "";
		
 		rng2 = rng.duplicate();
 		rng2.moveToElementText(referenceID);

		//alert(isDirRtl(rng.parentElement()));


		if (rng2.inRange(rng)) {
			
			arr = window.showModalDialog(assetLocation + "dialogs/lokiDirection.php?" + phpArgs,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
			
			if ( arr != null ) {
				if (arr['direction'] == 'ltr') {
					rng.execCommand("BlockDirLTR"); rng.execCommand("JustifyLeft");
				}
				else {
					rng.execCommand("BlockDirRTL"); rng.execCommand("JustifyRight");
				}
			}
		}

  	} catch(e) {}
}

// Added 8/1/2003 BK
function colorBackground(elementName) {

	try {

		var rng = document.selection.createRange();
		var oElem = elem(rng,elementName);

		if (oElem.getAttribute("className")=="colorBlockFormat") { oElem.removeAttribute("className"); }
		else { oElem.setAttribute("className","colorBlockFormat"); }
	
	} catch(e) {}
}

// Added 8/13/2003 BK
function pullQuote() {

	try {

		var rng = document.selection.createRange();
		var oP = elem(rng,"P");

		if (oP.getAttribute("className")=="pullQuoteRight") { oP.removeAttribute("className"); }
		else { oP.setAttribute("className","pullQuoteRight"); }
	
	} catch(e) {}
}

//Added 2003-09-18 NF
function insertNamedAnchor(referenceID) {

	var arr = null;
	var oWidth = 440;
	var oHeight = 230;

	var args = new Array();
	args['name'] = '';
	var phpArgs = '';

	// A named anchor isn't already selected, so add a new one
	if ( document.selection.type != "Control") {

		var rng, rng2;
		rng = document.selection.createRange();
		rng2 = rng.duplicate();
		rng2.moveToElementText(referenceID);

		if ( rng2.inRange(rng) ) {
			arr = window.showModalDialog(assetLocation + "dialogs/lokiNamedAnchor.php?" + phpArgs,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
	
			if ( arr != null && arr['name'] != '' ) {
				rng.collapse();
				rng.pasteHTML('<img src="'+assetLocation+'images/nav/anchor.gif" loki:is_really_an_anchor_whose_name="'+arr['name']+'" />');
			}
		}

	}

	// Is a named anchor selected? If so, just update it
	else if ( document.selection.type == "Control") {

		var rng = document.selection.createRange();
		var oSelectedAnchor;

		for (var i = 0; i < rng.length; i++) {
			if ( rng.item(i).tagName == "IMG"
				 && rng.item(i).getAttribute("loki:is_really_an_anchor_whose_name") != null ) {

				oSelectedAnchor = rng.item(i);
				args['name'] = oSelectedAnchor.getAttribute("loki:is_really_an_anchor_whose_name");

			}
		}

		arr = window.showModalDialog(assetLocation + "dialogs/lokiNamedAnchor.php?" + phpArgs,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off");

		if ( arr != null ) {

			if ( arr['name'] != '' ) {
				oSelectedAnchor.setAttribute("loki:is_really_an_anchor_whose_name", arr['name']);
			}
			else if ( arr['name'] == '' || arr['removeLink'] == true ) {
				oSelectedAnchor.outerHTML = '';
			}
			
		}
	}
}

// Added 2004-02-06 NF
function preloadModalNamedAnchor(iframe) {
	var phpArgs = '';
	document.getElementById(iframe).src = assetLocation + "dialogs/lokiNamedAnchor.php?" + phpArgs;
}

// Added 2004-05-12 NF
function checkSpelling(referenceID) {
// 	try {

		var arr = null;
		var oWidth = 600;
		var oHeight = 300;
		var rng = document.selection.createRange();
		var phpArgs = "";
		var args = new Array();
		args['text'] = referenceID.innerHTML;
			
		arr = window.showModalDialog(assetLocation + "dialogs/lokiSpell.php?" + phpArgs,args,"dialogWidth:" + oWidth + "px;dialogHeight:" + oHeight + "px;center:on;resizable:on;edge:raised;help:off;scroll:off;status:off"); 
			
		if ( arr != null ) {
			referenceID.innerHTML = arr['text'];
		}

// 	} catch(e) {}
}

//-->
