/**
 * A partial implementation of CSS3 selectors.
 */
function match_elements(root, expression) {
	function run_tests(output, element, tests) {
		var i, length;
		for (i = 0, length = tests.length; i < length; ++i) {
			if (!tests[i](element))
				return false;
		}
		output.push(element);
		return true;
	}
	
	function re_escape(str) {
		return str.replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
	}
	
	function get_match(matches, n) {
		try {
			return matches[n] || null;
		} catch (e) {
			return null;
		}
	}
	
	var combinators = {
		children: function(matches, element, tests) {
			var child, i;
			
			for (var i = 0; i < element.childNodes.length; i++) {
				child = element.childNodes[i];
				if (child.nodeType != 1)
					continue;
				run_tests(matches, child, tests);
			}
			
			return matches;
		},
		
		descendants: function(matches, element, tests) {
			var child, i;
			
			function collect(element) {
				var i, len, child;
				for (i = 0, len = element.childNodes.length; i < len; i++) {
					child = element.childNodes[i];
					run_tests(matches, child, tests);
					collect(child);
				}
			}
			
			collect(element);
			
			return matches;
		},
		
		adjoining: function(matches, element, tests) {
			var s;
			
			for (s = element.nextSibling; s; s = s.nextSibling) {
				if (s.nodeType == 1) {
					run_tests(matches, s, tests);
					break;
				}
			}
			
			return matches;
		},
		
		siblings: function(matches, element, tests) {
			var s;
			
			for (s = element.nextSibling; s; s = s.nextSibling) {
				if (s.nodeType == 1)
					run_tests(matches, s, tests)
			}
			
			return matches;
		}
	};
	
	var selectors = [
		{
			pattern: /^#(\w+)/,
			make_test: function(id) {
				return function(element) {
					return (element.id == id);
				};
			},
			universal_override: function(id) {
				var el = (root.ownerDocument || root).getElementById(id);
				return (el) ? [el] : [];
			}
		},
		
		{
			pattern: /^\.([\w\-]+)/,
			make_test: function(class_) {
				var p = new RegExp('(^|\\b)' + re_escape(class_) + '(\\b|$)');
				return function(element) {
					return p.test(element.className);
				};
			}
		},
		
		{
			pattern: /^\[([^\]]+)\]/,
			make_test: function(expr) {
				var pattern = /^(\w+)(([~\|]?=)(["']|)(.+)\4)?$/;
				var match = pattern.exec(expr);
				var attr, op, value;
				var i, found = [];

				if (!match)
					throw new Error('Invalid attribute expression: ' + expr);

				attr = get_match(match, 1);
				op = get_match(match, 3);
				value = get_match(match, 5);
				if (get_match(match, 4))
					value = value.substr(1, value.length - 2);

				if (attr == 'class')
					attr = 'className';
				else if (attr == 'for')
					attr = 'htmlFor';

				var operators = {
					'=': function(value, expected) {
						return value == expected;
					},

					'~=': function(value, expected) {
						var p = new RegExp('(^|\\b)' + re_escape(expected) + '(\\b|$)');
						return p.test(value);
					},

					'|=': function(value, expected) {
						return new RegExp('^' + re_escape(expected) + '(-|$)').test(value);
					}
				};

				return function(el) {
					var el_val = el.getAttribute(attr);

					if (!op)
						return !!el_val;

					return operators[op](el_val, value);
				};
			}
		}
	];
	
	var tag, match, m, elements = null, selected, tests, combinator, sel, i;
	var univ = false, sel_args;
	var combinator_pattern = /^(\s*[\+>~]\s*|[ ]+)/;
	var tag_pattern = /^\s*(\*|\w+)/;
	
	while (expression) {
		if (elements) {
			univ = false;
			match = combinator_pattern.exec(expression);
			combinator = get_match(match, 1);
			
			if (!combinator)
				throw new Error();
			
			if (/\+/.test(combinator)) {
				combinator = combinators.adjoining;
			} else if (/~/.test(combinator)) {
				combinator = combinators.siblings;
			} else if (/>/.test(combinator)) {
				combinator = combinators.children;
			} else {
				combinator = combinators.descendants;
			}
			
			expression = expression.substr(match[0].length);
		}
		
		match = tag_pattern.exec(expression);
		tag = get_match(match, 1);
		tests = [];
		
		if (!elements) {
			tag = (tag) ? tag.toUpperCase() : '*';
			univ = (tag == '*');
			elements = root.getElementsByTagName(tag);
		} else if (tag) {
			tag = tag.toUpperCase();
			tests.push(function(element) {
				return element.tagName == tag;
			});
		}
		
		if (match)
			expression = expression.substr(match[0].length);
		
		while (expression) {
			for (sel = null, i = 0; i < selectors.length; ++i) {
				if (match = selectors[i].pattern.exec(expression)) {
					sel = selectors[i];
					break;
				}
			}
			
			if (!sel)
				break;
			
			sel_args = [];
			for (i = 1; i < match.length; i++) {
				sel_args.push(match[i]);
			}
			
			if (!tests.length && univ && sel.universal_override) {
				elements = sel.universal_override.apply(sel, sel_args);
			} else {
				tests.push(sel.make_test.apply(sel, sel_args));
			}
			
			expression = expression.substr(match[0].length);
		}
		
		selected = [];
		for (i = 0; i < elements.length; ++i) {
			(combinator || run_tests)(selected, elements[i], tests);
		}
		elements = selected;
	}
	
	return elements || [];
}
