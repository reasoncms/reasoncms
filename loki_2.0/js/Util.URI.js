/**
 * Does nothing.
 *
 * @class Container for functions relating to URIs.
 */
Util.URI = function()
{
};

/**
 * Determines whether or not two URI's are equal.
 *
 * Special handling that this function performs:
 *	- Does not distinguish between http and https.
 * 	- Domain-relative links are assumed to be relative to the current domain.
 * @param {string or object}
 * @param {string or object}
 * @type boolean
 */
Util.URI.equal = function(a, b)
{
	function normalize(url)
	{
		if (typeof(url) != 'string') {
			if (url.scheme === undefined)
				throw TypeError();
			url = Util.Object.clone(url);
		} else {
			url = Util.URI.parse(url);
		}
		
		if (!url.scheme) {
			url.scheme = 'http';
		} else if (url.scheme = 'https') {
			if (url.port == 443)
				url.port = null;
			url.scheme = 'http';
		}
		
		if (!url.host)
			url.host = Util.URI.extract_domain(window.location);
			
		if (url.scheme == 'http' && url.port == 80)
			url.port = null;
			
		return url;
	}
	
	a = normalize(a);
	b = normalize(b);
	
	if (!Util.Object.equal(this.parse_query(a.query), this.parse_query(b.query)))
		return false;
	
	return (a.scheme == b.scheme && a.host == b.host && a.port == b.port &&
		a.user == b.user && a.password == b.password && a.path == b.path &&
		a.fragment == b.fragment);
}

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
	
	var port = get_match(authority_match, 7);
	
	return {
		scheme: get_match(match, 2),
		authority: get_match(match, 4),
		user: get_match(authority_match, 2),
		password: get_match(authority_match, 4),
		host: get_match(authority_match, 5),
		port: (port ? Number(port) : port),
		path: get_match(match, 5),
		query: get_match(match, 7),
		fragment: get_match(match, 9)
	};
}

/**
 * Parses a query fragment into its constituent variables.
 */
Util.URI.parse_query = function(fragment)
{
	var vars = {};
	
	if (!fragment)
		return vars;
	
	fragment.replace(/^\?/, '').split(/[;&]/).each(function (part) {
		var keyvalue = part.split('='); // we can't simply limit the number of
		                                // splits or we'll use any parts beyond
		                                // the first =
		var key = keyvalue.shift();
		var value = keyvalue.join('='); // undo any damage from the split
		
		vars[key] = value;
	});
	
	return vars;
}

/**
 * Builds a query fragment from an object.
 */
Util.URI.build_query = function(variables)
{
	var parts = [];
	
	Util.Object.enumerate(variables, function(name, value) {
		parts.push(name + '=' + value);
	});
	
	return parts.join('&');
}

/**
 * Builds a URI from a parsed URI object.
 */
Util.URI.build = function(parsed)
{
	var uri = parsed.scheme || '';
	if (parsed.authority) {
		uri += '://' + parsed.authority;
	} else if (parsed.host) {
		uri += '://';
		if (parsed.user) {
			uri += parsed.user;
			if (parsed.password)
				uri += ':' + parsed.password;
			uri += '@';
		}
		
		uri += parsed.host;
		if (parsed.port)
			uri += ':' + parsed.port;
	}
	
	if (parsed.path)
		uri += parsed.path;
	if (parsed.query)
		uri += '?' + parsed.query;
	if (parsed.fragment)
		uri += '#' + parsed.fragment;
	
	return uri;
}

/**
 * Safely appends query parameters to an existing URI.
 * Previous occurrences of a query parameter are replaced.
 */
Util.URI.append_to_query = function(uri, params)
{
	var parsed = Util.URI.parse(uri);
	var query_params = Util.URI.parse_query(parsed.query);
	
	Util.Object.enumerate(params, function(name, value) {
		query_params[name] = value;
	});
	
	parsed.query = Util.URI.build_query(query_params);
	return Util.URI.build(parsed);
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
	new RegExp('^(([^:@]+)(:([^@]+))?@)?([^:]+)(:(\d+))?$');
Util.URI.protocol_host_pattern =
	new RegExp('^(([^:/?#]+):)?(//([^/?#]*))?', 'i');