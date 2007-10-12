/**
 * Does nothing.
 *
 * @class Container for functions relating to URIs.
 */
Util.URI = function()
{
};

/**
 * Parses a URI into its constituent parts.
 */
Util.URI.parse = function(uri)
{
	var match = Util.URI.uri_pattern.exec(uri);
	
	if (!match) {
		throw new Error('Invalid URI: "' + uri + '".');
	}
	
	var authority_match = (typeof(match[4]) == 'string' && match[4].length)
		? Util.URI.authority_pattern.exec(match[4])
		: [];
	
	// this wouldn't need to be so convulted if JScript weren't so crappy!
	function get_match(source, index)
	{
		try {
			if (typeof(source[index]) == 'string' && source[index].length) {
				return source[index];
			}
		} catch (e) {
			// ignore and return null below
		}
		
		return null;
	}
	
	return {
		scheme: get_match(match, 2),
		authority: get_match(match, 4),
		user: get_match(authority_match, 2),
		password: get_match(authority_match, 4),
		host: get_match(authority_match, 5),
		path: get_match(match, 5),
		query: get_match(match, 7),
		fragment: get_match(match, 9)
	};
}

/**
 * Converts the given uri to either https or http, depending on value of
 * use_https.
 * 
 * @param	uri			the uri
 * @param 	use_https	(boolean) whether to use https or http
 */
Util.URI.make_https_or_http = function(uri, use_https)
{
	if ( this._use_https )
		uri = uri.replace(new RegExp('^http:', ''), 'https:');
	else
		uri = uri.replace(new RegExp('^https:', ''), 'http:');
	mb('Util.URI.make_https_or_http: made uri:', uri);
	return uri
};

/**
 * Strips leading "https:" or "http:" from a uri, to avoid warnings about
 * mixing https and http. E.g.: https://apps.carleton.edu/asdf ->
 * //apps.carleton.edu/asdf.
 * 
 * @param	uri			the uri
 */
Util.URI.strip_https_and_http = function(uri)
{
	return (typeof(uri) == 'string')
		? uri.replace(new RegExp('^https?:', ''), '')
		: null;
};

/**
 * Extracts the domain name from the URI.
 * @param	uri	the URI
 * @return	the domain name or null if an invalid URI was provided
 */
Util.URI.extract_domain = function(uri)
{
	var match = Util.URI.uri_pattern.exec(uri);
	return (!match || !match[4]) ? null : match[4].toLowerCase();
};

/**
 * Makes the given URI relative to its domain
 * (i.e. strips the protocol and domain).
 */
Util.URI.make_domain_relative = function(uri)
{
	return uri.replace(Util.URI.protocol_host_pattern, '');
}

Util.URI.uri_pattern =
	new RegExp('^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?',
	'i');
Util.URI.authority_pattern =
	new RegExp('^(([^:@]+)(:([^@]+))?@)?(.+)$');
Util.URI.protocol_host_pattern =
	new RegExp('^(([^:/?#]+):)?(//([^/?#]*))?', 'i');