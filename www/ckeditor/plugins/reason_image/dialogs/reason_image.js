/**
 * Reason image plugin dialog window definition
 * 
 * See http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

CKEDITOR.dialog.add( 'reasonImageDialog', function( editor ) {
	var dataObjects = [];
	var imgKeys = []
	var filteredImgKeys = [];
	var selectedKey = -1;
	var dialog;
	
	function writeImagesHtml() {
		var keys;
		var document = dialog.getElement().getDocument();
		var element = document.getById('testdiv');
		var images = [];
		
		keys = filteredImgKeys.length == 0 ? imgKeys : filteredImgKeys
		for (var i = 0; i < keys.length; i++) {
			images.push('<figure class="cke_chrome" style="float: left; width: 125px; height: 200px; padding: 2px; margin: 3px;"><img src=\"' + dataObjects[keys[i]].link.replace(/\.(jpe?g|png|gif)$/, '_tn.$1') + '\"><figcaption class="ui-dialog" style="width:100px; word-wrap:normal;">' + dataObjects[keys[i]].name + '</figcaption></figure>');
		}

        if (element)
            element.setHtml(images.join(""));		
	}
	
	
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Insert image',
		minWidth: 400,
		minHeight: 400,

		// Dialog window content definition.
		contents: [
		    // Definition of the Existing image dialog tab (page).
			{				
				id: 'tab-existing',
				label: 'Exisiting image',
				elements: [
					{
						type: 'text',
						id: 'filter',
						label: 'Filter results',
					},
					{
					    type: 'radio',
					    id: 'size',
					    label: 'Size',
					    items: [['Thumbnail', 'thumbnail'], ['Full', 'full']],
					    default: 'thumbnail',
					    style: 'display: inline-block',
					},
					{
					    type: 'radio',
					    id: 'alignment',
					    label: 'Alignment',
					    items: [['None', 'none'], ['Left', 'left'], ['Right', 'right']],
					    default: 'none',
					    style: 'display: inline-block',
					},
					{
						// Horizontal window where images and captions are displayed
					    type: 'hbox',
					    widths: [ '100%' ],
					    children: [
					               {
					            	   type: 'html',
					            	   id: 'html_img',
					            	   html: '<div id="testdiv" style="overflow-y: scroll; height:200px;"></div>'
					               }
					               ]
					},
				]
			},

			// Definition of the Image at web address dialog tab (page).
			{
				id: 'tab-web',
				label: 'Image at web address',
				elements: [
					{
						type: 'text',
						id: 'location',
						label: 'Location: ',
					},
					{
						type: 'text',
						id: 'description',
						label: 'Description: ',
					},
					{
					    type: 'radio',
					    id: 'alignment',
					    label: 'Alignment',
					    items: [['None', 'none'], ['Left', 'left'], ['Right', 'right']],
					    default: 'none',
					    style: 'display: inline-block',
					},
				]
			}
		],
		
		onLoad: function() {
			dialog = this;
	        //var exists = urlExists(url);
			var jsonImg = $.getJSON('http://192.168.56.101/reason/displayers/generate_json.php?site_id=240622&type=image');

			$.getJSON("http://192.168.56.101/reason/displayers/generate_json.php?site_id=240622&type=image", function( data ) {
				  // "count" and "items" are json "data" object keys
				  $.each(data.items, function(key, value) {
					  console.log(key, value);
					  console.log(value.link);
					  console.log(value.name);
					  dataObjects.push(value);
					  imgKeys.push(key);
				  });
				  writeImagesHtml();
			});

			console.log(dialog.setValueOf('tab-web', 'id', "Yo web"));
		
// TODO: fix the #cke_39_textInput hack using registerEvents()
			$("#cke_39_textInput").keyup(function() {
				console.log(dataObjects);
				var filter = dialog.getValueOf('tab-existing', 'filter').toLowerCase();
				filteredImgKeys = [];
				for (var i = 0; i < dataObjects.length; i++) {
					if (dataObjects[i].name.toLowerCase().indexOf(filter) >= 0) {
						filteredImgKeys.push(i);
					}
				}
				console.log(filteredImgKeys);
				writeImagesHtml();
			});
			
			$(document).on('click', 'figure', function() {
				if ($(this).hasClass('cke_menubutton_on')) {
					$(this).removeClass('cke_menubutton_on');
				}
				else {
					$(this).siblings('figure').removeClass('cke_menubutton_on');
					$(this).addClass('cke_menubutton_on');
				}
			});
			
			$(document).ready(function(){
				$('.cke_reset_all *').css('white-space: wrap;');
			});
			
		},

		// This method is invoked once a user clicks the OK button, confirming the dialog.
		onOk: function() {

			// The context of this function is the dialog object itself.
			// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
			var dialog = this;

			// Create a new img url link
			var reason_image = editor.document.createElement('img');
			
			reason_image.setAttribute('src', $('figure.cke_menubutton_on > img').attr('src'));
			reason_image.setAttribute('alt', $('figure.cke_menubutton_on > figcaption').html());
			reason_image.setAttribute('style', 'float: ' + dialog.getValueOf('tab-existing', 'alignment'));

			// Set element attribute and text by getting the defined field values.
			//reason_image.setAttribute( 'title', dialog.getValueOf( 'tab-existing', 'title' ) );
			//reason_image.setText( dialog.getValueOf( 'tab-existing', 'abbr' ) );

			// Now get yet another field value from the Advanced Settings tab.
			//var id = dialog.getValueOf( 'tab-web', 'id' );
			//if ( id )
			//	reason_image.setAttribute( 'id', id );

			// Insert the element into the editor at the caret position.
			editor.insertElement(reason_image);
		}
	};
});

