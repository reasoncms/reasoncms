/** 
 * site.js - Javascript for site content manager
 *
 * @author Nathan White
 * @requires jQuery
 */

$(document).ready(function()
{	
	var footer_row = $("tr#customfooterRow");
	var select_elem = $("select#use_custom_footerElement");
	
	if ( $(select_elem).val() != 'yes' ) $(footer_row).hide(); // hide initially if necessary
	
	$(select_elem).change(function() // conditionally hide/show
	{
		if ($(this).val() == 'yes') $(footer_row).show(); 
		else $(footer_row).hide();
	});

	editor_opts_element = $("#loki_defaultElement");
	preview = $("#tinypreviewRow").find('textarea');


	// tinymce.init({selector:'#tiny_previewElement'});
	changeTinyPreview(editor_opts_element);
	editor_opts_element.change(function() { changeTinyPreview(editor_opts_element); });
});

function changeTinyPreview(editor_options_element) {

	opts = editor_options_element.val();
	tinymce.remove();
	tiny_options = {
		'selector' : '#tiny_previewElement',
		'mode' : 'exact',
		'dialog_type' : "modal",
		'theme' : "modern",
		'convert_urls' : false,
		'menubar' : false,
		'content_css' : "/reason/tinymce/css/content.css",
		'external_css' : "/reason/tinymce/css/external.css",
		'elements' : "content",
		'reason_http_base_path' : "/reason/",
		'external_plugins' : { "reasonintegration": "/reason/tinymce/plugins/reasonintegration/plugin.js" },
		'contextmenu' : "inserttable | cell row column deletetable",
		'toolbar1' : "formatselect,|,bold,italic,|,hr,|,cut,copy,paste,|,blockquote,|,numlist,bullist,|,table,|,reasonimage,|,reasonlink,unlink,|,anchor,|,searchreplace,|,code",
		'plugins' : "contextmenu,table,anchor,link,paste,advlist,code,searchreplace,lists",
		'block_formats' : "Paragraph=p;Header 1=h3;Header 2=h4;Pre=pre",
		'formats' : { "underline": {} }
	};

	switch (opts) {
		case 'notables':
			tiny_options.toolbar1 = "formatselect,|,bold,italic,|,hr,|,cut,copy,paste,|,blockquote,|,numlist,bullist,|,reasonimage,|,reasonlink,unlink,|,anchor,|,searchreplace,|,code";
			tiny_options.block_formats = "Paragraph=p;Header 1=h3;Header 2=h4;";
		break;
		case 'default':
			// just run the init with tiny_options below.
		break;
		case 'all':
			// just run the init with tiny_options below.
		break;
		case 'all_minus_pre':
			tiny_options.block_formats = "Paragraph=p;Header 1=h3;Header 2=h4;";
		break;
		case 'notables_plus_pre':
			tiny_options.toolbar1 = "formatselect,|,bold,italic,|,hr,|,cut,copy,paste,|,blockquote,|,numlist,bullist,|,reasonimage,|,reasonlink,unlink,|,anchor,|,searchreplace,|,code";
		break;
	}
	tinymce.init( tiny_options );
}