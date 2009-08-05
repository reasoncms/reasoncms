/*
 * jQuery Upload Support Tools
 * Copyright © 2009 Carleton College.
 */

// Convert a human-readable size into a number of bytes.
var _size_pattern = /(\d+(?:\.\d+)?)\s*(b(?:ytes)?|[kmgp]b?)$/i;
function parse_file_size(size) {
    var raw = Number(size);
    if (!isNaN(raw)) {
        // no suffix; "size" is a number of bytes, which is what we want
        return raw;
    }
    
    size = size.toLowerCase();
    var match = _size_pattern.exec(size);
    if (!match)
        return null;
    
    var suffix = match[2];
    size = parseFloat(match[1]);
    
    switch (suffix.charAt(0)) {
        case 'p':
            size *= 1024;
        case 'g':
            size *= 1024;
        case 'm':
            size *= 1024;
        case 'k':
            size *= 1024;
    }
    
    return size;
}
window.parse_file_size = parse_file_size;

var _size_suffixes = ['bytes', 'KB', 'MB', 'GB', 'PB'];
function format_size(size) {
    var i = 0;
    while (size > 1024 && i < _size_suffixes.length) {
        size /= 1024;
        i++;
    }
    
    var rounded = /^\d+(\.\d{1,2})?/.exec(size);
    return rounded[0] + " " + _size_suffixes[i];
}
window.format_size = format_size;

String.prototype.interpolate = function str_interpolate(vars) {
    var pos = 0;
    var parts = [];
    var length = this.length;
    var a, b, namespec, name, names;
    var target;
    
    while (pos < length) {
        a = this.indexOf('{', pos);
        if (a < 0)
            break;
        parts.push(this.substring(pos, a));
        
        b = this.indexOf('}', a);
        if (b < 0)
            break;
        
        namespec = this.substring(a + 1, b);
        names = namespec.split('.');
        target = vars;
        while (names.length > 0) {
            name = names.shift();
            if (name in target) {
                target = target[name];
            } else {
                target = null;
                break;
            }
        }
        if (typeof(target) !== 'undefined') {
            parts.push(target);
        } else {
            parts.push("{" + namespec + "}");
        }
        
        pos = b + 1;
    }
    
    parts.push(this.substring(pos));
    return parts.join('');
};

function get_flash_version() {
    function get_from_plugins() {
        if (!navigator.plugins)
            return null;
        
        var plugin = navigator.plugins["Shockwave Flash 2.0"] ||
            navigator.plugins["Shockwave Flash"];
        if (!plugin)
            return null;
        
        var match = /(\d+)\.(\d+)\s*[\.rd](\d+)/.exec(plugin.description);
        return (match) ?
            [parseInt(match[1]), parseInt(match[2]), parseInt(match[3])] :
            null;
    }
    
    function get_from_control() {
        var control;
        var version;
        var match;
        
        try {
            control = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
            version = control.GetVariable("$version");
            match = /(\d+),(\d+),(\d+)/.exec(version);
            return (match) ?
                [parseInt(match[1]), parseInt(match[2]), parseInt(match[3])] :
                null;
        } catch (e) {
            return null;
        }
    }
    
    return get_from_plugins() || get_from_control();
}
