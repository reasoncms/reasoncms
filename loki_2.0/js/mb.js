/**
 * For debugging.
 */
var messagebox_i = 0;
var messagebox_elem;
var messagebox = mb = function(message, obj)
{
	if (loki_debug)
	{
		function init()
		{
			messagebox_elem = document.createElement('DIV');
			document.body.appendChild(messagebox_elem);
			messagebox_elem.setAttribute('style', 'position:fixed; bottom:0px; background:white; height:200px; overflow:scroll');
			document.body.style.marginBottom = document.body.style.marginBottom + 220;
			messagebox_elem.appendChild( document.createTextNode(' ') ); // so insertBefore firstChild always works
		};

		if ( messagebox_i == 0 )
			init();

		if ( typeof(message) != 'string' )
		{
			obj = message;
			message = '';
		}

		var mb_line = document.createElement('DIV');
		mb_line.innerHTML = (messagebox_i++) + ': ' + message + ' ';
		if ( obj != null )
		{
			mb_line.innerHTML = mb_line.innerHTML + ': ' + obj.toString();
			mb_line.onclick = function() { Util.Object.print_r(obj); };
		}
		mb_line.innerHTML = mb_line.innerHTML + '<br />';
		messagebox_elem.insertBefore(mb_line, messagebox_elem.firstChild);

		//document.getElementById('messagebox').innerHTML = 
		//(messagebox_i++) + ": " + message + ' ' + obj_part + "<br />" + document.getElementById('messagebox').innerHTML;
	}
};
