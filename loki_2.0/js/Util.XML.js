Util.XML = function()
{
};

/**
 * Loads an XML document asynchronously.
 *
 * N.B.: This must be asynchronous, in order to deal with an IE bug
 * involving HTTPS over SSL:
 * <http://support.microsoft.com/kb/272359/en>.
 *
 * @param	uri				The URI of the XML document.
 * @param	post_data		string containing post data
 * @return					the XML document's document object
 */
Util.XML.load = function(uri, post_data)
{
	var xml_doc;

	if ( !post_data )
	{
		if ( document.implementation && document.implementation.createDocument)
		{
			xml_doc = document.implementation.createDocument('', 'doc', null);
		}
		else
		{
			try
			{
				xml_doc = new ActiveXObject('Microsoft.XMLDOM');
			}
			catch(e)
			{
				throw "Util.XML.load: Your browser supports neither the W3C method nor the MS method of creating an XML document.";
			}
			
		}
		xml_doc.async = false; // setting this to true causes Microsoft.XMLDOM to fail 
		xml_doc.load(uri);
	}
	else
	{
		if ( document.implementation && document.implementation.createDocument)
		{
			xml_doc = new XMLHttpRequest();
		}
		else
		{
			try
			{
				xml_doc = new ActiveXObject('Microsoft.XMLHTTP');
			}
			catch(e)
			{
				throw "Util.XML.load: Your browser supports neither the W3C method nor the MS method of creating an XML document.";
			}
		}
		xml_doc.open('POST', 'http://www.example.com/test.php', false);
		xml_doc.send(post_data);
	}

	return xml_doc;
};

/**
 * Adds an onload listener to a node. The normal add_event_listener
 * cannot be used because IE doesn't have a load event for xml
 * documents, but instead has an onreadystatechange event.
 *
 * @param	node		the node to which to add the event listener
 * @param	listener	a function which will be called when the event is fired, and which receives as a paramater an
 *                      Event object (or, in IE, a Util.Event.DOM_Event object)
 */
/*
Util.XML.add_load_listener = function(node, listener)
{
	try
	{
		Util.Event.add_event_listener(node, 'load', function() { listener(); });
	}
	catch(e)
	{
		try
		{
			node.onreadystatechange = function() { if ( node.readyState == 4 ) listener(); };
//			Util.Event.add_event_listener(node, 'readystatechange', function() { if ( node.readyState == 4 ) listener(); });
		}
		catch(f)
		{
			throw(new Error('Util.XML.add_load_listener(): Your browser supports neither the W3C method nor the MS method of adding a load listener. ' +
							'When the W3C way was tried, an error with the following message was thrown: <<' + e.message + '>>. ' +
							'When the IE way was tried, an error with the following message was thrown: <<' + f.message + '>>.'));
		}
	}
}
*/



/**
 * Loads an XML document asynchronously.
 *
 * N.B.: This must be asynchronous, in order to deal with an IE bug
 * involving HTTPS over SSL:
 * <http://support.microsoft.com/kb/272359/en>.
 *
 * @param	uri				The URI of the XML document.
 * @param	load_handler	The function to call on load.
 * @return					the XML document's document object
 */

/*
Util.XML.load = function(uri, load_handler)
{
	var xml_doc;

	if ( document.implementation && document.implementation.createDocument)
	{
		xml_doc = document.implementation.createDocument('', 'doc', null);
		xml_doc.onload = function() { load_handler(); };
	}
	else
	{
		try
		{
			xml_doc = new ActiveXObject('Microsoft.XMLDOM');
			xml_doc.onreadystatechange = function() { if ( xml_doc.readyState == 4 ) load_handler(); };
		}
		catch(e)
		{
			throw "Util.XML.load(): Your browser supports neither the W3C method nor the MS method of creating an XML document.";
		}
		
	}
	//xml_doc.async = false; // setting this to true causes Microsoft.XMLDOM to fail 
	xml_doc.load(uri);

	return xml_doc;
};
*/


/*
Example usage:

function do_onload()
{
	var xml_doc = Ext_XML.load('/fillmorn/rdf/rdf_entity.php?id=4369');

	(new Ext_Object(xml_doc)).print_r(2);
	return true;

	// this works in mozilla but not ie:
	var entities = xml_doc.getElementsByTagNameNS('http://www.carleton.edu/reason/', 'entity');
//	var entities = xml_doc.getElementsByTagName('reason:entity');
	alert(entities.length);
	for ( var i = 0; i < entities.length; i++ )
	{
		var entity = entities.item(i);
// 		Ext_Node.get_related_nodes(entity, Ext_Node.ANCESTOR, function(node) { node.nodeType == Node.ELEMENT_NODE && 1==1 }, -1);

		var matching_descendant_nodes = Ext_Node.get_matching_descendant_nodes(entity, function(node) { return node.nodeType == Node.ELEMENT_NODE }, -1);
		alert('just before print_r');
		(new Ext_Object(matching_descendant_nodes)).print_r(3);
		alert('just after print_r');
	}


	alert(xml_doc.getElementsByTagName('reason:entity'));
}
*/
