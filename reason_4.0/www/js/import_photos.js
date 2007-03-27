// Cross-browser function for attaching events to objects
function addEvent( obj, type, fn ) {
	if ( obj.attachEvent ) {
		obj['e'+type+fn] = fn;
		obj[type+fn] = function(){obj['e'+type+fn]( window.event );}
		obj.attachEvent( 'on'+type, obj[type+fn] );
	} else {
		obj.addEventListener( type, fn, false );
	}
}

// Hide all but the first upload row
function hideUploadFields() {
	if (document.getElementById) {
		index = 1;
		// loop through sequentially while we can find a row with the current number
		while ( row = document.getElementById('upload'+index+'Row')) {
			if (index > 1) {
				// hide everything past the first row
				row.style.display='none';	
			}
			// find the file input object for this row and add an event to it to show the next row
			// when its value changes
			var upload = document.getElementById('upload_'+index+'Element');
			addEvent(upload, 'change', showNext, index);
			index++;
		}
	}
}

// Show the next input row after the one that triggered the event
function showNext() {
	if (document.getElementById) {
		// Use regex to find the number of this input
		if ( nameparts = this.id.match(/upload_(\d+)Element/) ) {
			var index = parseInt(nameparts[1]);
			index++;
			// If there's a row at thisrow+1, show it
			if ( row = document.getElementById('upload'+index+'Row')) {
				row.style.display='';
			}
		}
	}
}

addEvent(window, 'load', hideUploadFields);
