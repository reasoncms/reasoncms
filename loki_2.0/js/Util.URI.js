/**
 * Does nothing.
 *
 * @class Container for functions relating to URIs.
 */
Util.URI = function()
{
};

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
	return uri.replace(new RegExp('^https?:', ''), '');
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
Util.URI.protocol_host_pattern =
	new RegExp('^(([^:/?#]+):)?(//([^/?#]*))?', 'i');