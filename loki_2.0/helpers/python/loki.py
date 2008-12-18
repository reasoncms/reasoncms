"""
Helper code for Loki, a JavaScript WYSIWYG HTML editor.

This file will eventually be fleshed out to be on par with the PHP helper code,
but not yet.

Copyright (c) 2008 Carleton College.
"""

def get_source_filenames(folder_path, priority_util_files=[]):
	"""
	Returns a 2-tuple, the first element of which is the list of Loki source
	files sorted into the order that they should be included, and the second
	is the modification time of the most-recently modified source file.
	"""
	
	import os
	
	if len(priority_util_files) is 0:
		priority_util_files.extend(['Util.js', 'Util.Scheduler.js',
			'Util.Function.js', 'Util.Array.js', 'Util.Node.js', 'Util.Browser.js',
			'Util.Element.js', 'Util.Event.js', 'Util.Object.js',
			'Util.OOP.js'])
			
	latest_time = [0]
	
	def compare_filenames(a, b):
		a_ui = a.startswith('UI.')
		b_ui = b.startswith('UI.')
		a_util = a.startswith('Util.')
		b_util = b.startswith('Util.')
		
		if not (a_util or a_ui):
			if b_util or b_ui:
				return -1
			else:
				return cmp(a, b)
		elif not (b_util or b_ui):
			return 1
		elif a_util:
			if b_ui:
				return -1
			
			for p_file in priority_util_files:
				if a == p_file:
					return -1
				if b == p_file:
					return 1
			
			return cmp(a, b)
		elif b_util:
			if a_ui:
				return 1
			else:
				return cmp(a, b)
		elif a == 'UI.js':
			return -1
		elif b == 'UI.js':
			return 1
		elif a == 'UI.Loki.js':
			return 1
		elif b == 'UI.Loki.js':
			return -1
		else:
			return cmp(a, b)
			
	script_files = []
	
	def add_file(filename):
		mtime = os.path.getmtime(os.path.join(folder_path, filename))
		if mtime > latest_time[0]:
			latest_time[0] = mtime
		
		(lo, hi) = (0, len(script_files))
		
		while lo < hi:
			mid = int((lo + hi) / 2)
			if compare_filenames(filename, script_files[mid]) < 0:
				hi = mid
			else:
				lo = mid + 1
		
		script_files.insert(lo, filename)
			
	def accept(path, filename):
		return (os.path.isfile(path) and filename.endswith('.js')
			and not filename.startswith('.'))
	
	for filename in os.listdir(folder_path):
		if not accept(os.path.join(folder_path, filename), filename):
			continue
		
		add_file(filename)
	
	return (script_files, latest_time[0])
	