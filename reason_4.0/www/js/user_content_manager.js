function init() {
	try {
		if (document.getElementById) {
			/* If it's not reason managed, attach appropriate functions to the file select element */
			/* Set up the parts toggle */
			var aSel = document.getElementById('user_authoritative_sourceElement');
			var curSel = aSel.options[aSel.selectedIndex].index;
			if(curSel == 1)
			{
				document.getElementById('usersurnameRow').style.display="none";
				document.getElementById('usergivennameRow').style.display="none";
				document.getElementById('useremailRow').style.display="none";
				document.getElementById('userphoneRow').style.display="none";
				document.getElementById('passwordRow').style.display="none";
				document.getElementById('confirmpasswordRow').style.display="none";
			}
			aSel.onchange=function() {
				var nSel = this.options[this.selectedIndex].index;
				if(nSel == 0) {
					document.getElementById('usersurnameRow').style.display="";
					document.getElementById('usergivennameRow').style.display="";
					document.getElementById('useremailRow').style.display="";
					document.getElementById('userphoneRow').style.display="";
					document.getElementById('passwordRow').style.display="";
					document.getElementById('confirmpasswordRow').style.display="";
				}
				else
				{
					document.getElementById('usersurnameRow').style.display="none";
					document.getElementById('usergivennameRow').style.display="none";
					document.getElementById('useremailRow').style.display="none";
					document.getElementById('userphoneRow').style.display="none";
					document.getElementById('passwordRow').style.display="none";
					document.getElementById('confirmpasswordRow').style.display="none";
				}
			}
		}
	} catch(e) {}
}

if (window.addEventListener)
  window.addEventListener("load", init, true);
else if (window.attachEvent)
  window.attachEvent("onload", init);
