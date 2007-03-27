// adapted from http://aleembawany.com/weblog/webdev/000051_ajax_instant_tutorial.html
var updatediv = '';

function loadurl(dest, div) { 
	try {
		// Moz supports XMLHttpRequest. IE uses ActiveX.  
		// browser detction is bad. object detection works for any browser  

		xmlhttp = window.XMLHttpRequest?new XMLHttpRequest():
		new ActiveXObject("Microsoft.XMLHTTP");
	} 

	catch (e) { // browser doesn't support ajax. handle however you want
	}

	// the xmlhttp object triggers an event everytime the status changes
	// triggered() function handles the events

	if (div != '') {
		updatediv = div;
	}
	xmlhttp.onreadystatechange = triggered;

	// open takes in the HTTP method and url.
	xmlhttp.open("GET", dest); 

	// send the request. if this is a POST request we would have
	// sent post variables: send("name=aleem&gender=male)
	// Moz is fine with just send(); but
	// IE expects a value here, hence we do send(null);
	xmlhttp.send(null);
}

function triggered() {
	// if the readyState code is 4 (Completed)  
	// and http status is 200 (OK) we go ahead and get the responseText  
	// other readyState codes:  
	// 0=Uninitialised 1=Loading 2=Loaded 3=Interactive 

	if (updatediv != '')
	{
		if ((xmlhttp.readyState == 4) && (xmlhttp.status == 200)) { 
			// xmlhttp.responseText object contains the response.
			document.getElementById(updatediv).innerHTML = xmlhttp.responseText;
	}
	}
}
