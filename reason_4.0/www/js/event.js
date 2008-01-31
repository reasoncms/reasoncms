// "oSelect" is the id of the controller select menu
var oSelect="recurrenceElement";
// "menuState" array is for the form elemens that should NOT be shown 

var menuState = new Array();
menuState[0]= new Array("frequencyRow","sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow", "enddateRow", "monthlyrepeatRow");
menuState[1]= new Array("sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow", "monthlyrepeatRow");
menuState[2]= new Array("monthlyrepeatRow");
menuState[3]= new Array("sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow");
menuState[4]= new Array("sundayRow", "mondayRow", "tuesdayRow", "wednesdayRow", "thursdayRow", "fridayRow", "saturdayRow", "monthlyrepeatRow");

var freqState = new Array();
freqState[0] = '';
freqState[1] = 'day(s)';
freqState[2] = 'week(s)';
freqState[3] = 'month(s)';
freqState[4] = 'year(s)';

function init() {
	if (document.getElementById)
	{
		var oSel = document.getElementById(oSelect);
		var oSelIndex = oSel.options[oSel.selectedIndex].index;
		
		// the default when page is loaded
		for (i in menuState[oSelIndex])
		{
			if (document.getElementById(menuState[oSelIndex][i]))
			{
				document.getElementById(menuState[oSelIndex][i]).style.display="none";
			}
		}
		
		document.getElementById("frequencyComment").firstChild.nodeValue=freqState[oSelIndex];
		// when the select is changed turn them all ON, then turn the correct ones OFF
		oSel.onchange=function()
		{
			for (i in menuState) {
				for (j in menuState[i]) {
					if (document.getElementById(menuState[i][j]))
					{
						document.getElementById(menuState[i][j]).style.display="";
					}
				}
			}
			var nSel = this.options[this.selectedIndex].index;
			for (i in menuState[nSel]) {
				if (document.getElementById(menuState[nSel][i]))
				{
					document.getElementById(menuState[nSel][i]).style.display="none";
				}
			}
			document.getElementById("frequencyComment").firstChild.nodeValue=freqState[nSel];
		}
	} 
	update_radio_buttons();
}

if (window.addEventListener)
  window.addEventListener("load", init, true);
else if (window.attachEvent)
  window.attachEvent("onload", init);
