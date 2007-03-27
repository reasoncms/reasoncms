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
