function init() {
	try {
		if (document.getElementById) {
			/* If it's not reason managed, attach appropriate functions to the file select element */
			if(document.getElementById('reason_managed_mediaElement').value != "1")
			{
				var fSel = document.getElementById('import_fileElement');
				document.getElementById('defaultmediadeliverymethodRow').style.display="none";
				fSel.onchange=function() {
					var nSel = this.options[this.selectedIndex].index;
					if(nSel == 0) {
						document.getElementById('urlRow').style.display="";
						document.getElementById('defaultmediadeliverymethodRow').style.display="none";
					}
					else
					{
						document.getElementById('urlRow').style.display="none";
						document.getElementById('defaultmediadeliverymethodRow').style.display="";
					}
				}
			}
			/* Set up the parts toggle */
			var pSel = document.getElementById('is_partElement');
			var curSel = pSel.options[pSel.selectedIndex].index;
			if(curSel == 1)
			{
				document.getElementById('avpartnumberRow').style.display="none";
				document.getElementById('avparttotalRow').style.display="none";
				document.getElementById('descriptionRow').style.display="none";
			}
			pSel.onchange=function() {
				var nSel = this.options[this.selectedIndex].index;
				if(nSel == 0) {
					document.getElementById('avpartnumberRow').style.display="";
					document.getElementById('avparttotalRow').style.display="";
					document.getElementById('descriptionRow').style.display="";
				}
				else
				{
					document.getElementById('avpartnumberRow').style.display="none";
					document.getElementById('avparttotalRow').style.display="none";
					document.getElementById('descriptionRow').style.display="none";
				}
			}
		}
	} catch(e) {}
}

if (window.addEventListener)
  window.addEventListener("load", init, true);
else if (window.attachEvent)
  window.attachEvent("onload", init);
