/**
 * This script modifies the admin page for a minisite page to display the
 * path to the page's parent before the entry field for the final URL portion
 * unique to the child.
 *
 * (Requires jQuery, which shouldn't be an issue since admin pages include it.)
 */

jQuery(function warp_page($) {
	var parent_id_sel = $('#disco_form select#parent_idElement');
	var slug_input = $('#disco_form #urlfragmentItem input[name=url_fragment]');

	if (!parent_id_sel.length || !slug_input.length)
		return;

	slug_input.before('<span class="parent_path"></span>');
	slug_input.after('<span class="trailing_slash">/</span> ' +
	    '<span class="incomplete_warning"></span>');
	$('span.url_comment_replace').text("The page's Web address.");
	var parent_path = $('#urlfragmentItem span.parent_path');

	function update_parent_path() {
		var parent_id = parent_id_sel.val();
		var incomplete = $(".incomplete_warning");
		var trail = $('.trailing_slash');
		var path;

		if (!parent_id) {
			parent_path.text('');
			incomplete.text('(Choose a parent page to see this page\'s full ' +
			    'URL.)');
			trail.text('');
		} else {
			path = $('#path_to_' + parent_id + 'Element').val();
			parent_path.text(path);
			incomplete.text('');
			trail.text('/');
		}
	}

	var rules = $('#disco_form #urlfragmentItem .rules');
	var unhappy = false;

	function validate() {
	    var value = slug_input.val();
	    var invalid = (!(/^[0-9a-z_\-]*$/i).test(value)) || (/\.html?$/.test(value));
	    if (!unhappy && invalid) {
	        unhappy = true;
	        rules.addClass('inappropriate_url_warning');
	    } else if (unhappy && !invalid) {
	        rules.removeClass('inappropriate_url_warning');
	        unhappy = false;
	    }
	}

	slug_input.keyup(validate);
	parent_id_sel.change(update_parent_path);
	update_parent_path();
	validate();
});
