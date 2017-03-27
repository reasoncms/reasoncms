`_.mixin({
  pathAssign: function (obj, path, val) {
    var fields = path.split('.');
    var result = obj;
    for (var i = 0, n = fields.length; i < n && result !== undefined; i++) {
      var field = fields[i];
      if (i === n - 1) {
        result[field] = val;
      } else {
        if (typeof result[field] === 'undefined' || !_.isObject(result[field])) {
          result[field] = {};
        }
        result = result[field];
      }
    }
  },
  pathGet: function(obj, path) {
    var fields = path.split('.');
    var curObj = obj;
    for (var i = 0, n = fields.length; i < n && curObj !== undefined; i++) {
      var field = fields[i];
      if (i === n - 1) {
        return curObj[field];
      } else {
        if (typeof curObj[field] === 'undefined' || !_.isObject(curObj[field])) {
          return undefined;
        }
        curObj = curObj[field];
      }
    }
    return undefined;
  },
  capitalize: function(string) {
    return string.charAt(0).toUpperCase() + string.substring(1).toLowerCase();
  },
  truncate: function(str, length, truncateStr){
      if (str == null) return '';
      str = String(str); truncateStr = truncateStr || '...';
      length = ~~length;
      return str.length > length ? str.slice(0, length) + truncateStr : str;
  }
});`