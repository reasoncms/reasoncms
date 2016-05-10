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
		var element = document.getById('image-list');
		var images = [];
		
		keys = filteredImgKeys.length == 0 ? imgKeys : filteredImgKeys
		for (var i = 0; i < keys.length; i++) {
			images.push('<figure class="cke_chrome" style="float: left; width: 125px; height: 154px; overflow: hidden; padding: 2px; margin: 3px;"><img src=\"' + dataObjects[keys[i]].link.replace(/\.(jpe?g|png|gif)$/, '_tn.$1') + '\"><figcaption class="ui-dialog" style="width:125px; white-space: normal;">' + dataObjects[keys[i]].name + '</figcaption></figure>');
		}

        if (element)
            element.setHtml(images.join(""));		
	}
	
	
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Insert image',
		minWidth: 425,
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
						// Window where images and captions are displayed
					    type: 'vbox',
					    id: 'vbox_img',
					    children: [
					               {
					            	   type: 'html',
					            	   id: 'html_img',
					            	   html: '<div id="image-list" style="overflow-y: scroll; height:254px;"></div>'
					               }
					               ],
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
		
		// Called when the dialog is first created
		onLoad: function() {
			dialog = this;
			
			// getJSON is called asynchronously, and onShow: gets called before getJSON returns
			$.getJSON("//" + window.location.host + "/reason/displayers/generate_json.php?site_id=240622&type=image", function( data ) {
				  // "count" and "items" are json "data" object keys
				  $.each(data.items, function(key, value) {
					  //console.log(key, value);
					  //console.log(value.link);
					  //console.log(value.name);
					  dataObjects.push(value);
					  imgKeys.push(key);
				  });
				  writeImagesHtml();
			});
		
			// TODO: fix the #cke_39_textInput hack using registerEvents() instead
			$("#cke_39_textInput").keyup(function() {
				//console.log(dataObjects);
				var filter = dialog.getValueOf('tab-existing', 'filter').toLowerCase();
				filteredImgKeys = [];
				for (var i = 0; i < dataObjects.length; i++) {
					if (dataObjects[i].name.toLowerCase().indexOf(filter) >= 0) {
						filteredImgKeys.push(i);
					}
				}
				//console.log(filteredImgKeys);
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
		},
		
		// Called every time the dialog is opened
		onShow: function() {
			
			if (imgKeys.length > 0) {
				$('figure').removeClass('cke_menubutton_on');
				filteredImgKeys = []
				writeImagesHtml();
			}
		},

		// Method is invoked once a user clicks the OK button, confirming the dialog.
		onOk: function() {

			var dialog = this;
			
			// Create a new img url link
			var reason_image = editor.document.createElement('img');
			
			if (dialog.definition.dialog._.currentTabId == 'tab-existing') {
				reason_image.setAttribute('src', $('figure.cke_menubutton_on > img').attr('src'));
				reason_image.setAttribute('alt', $('figure.cke_menubutton_on > figcaption').html());
				reason_image.setAttribute('style', 'float: ' + dialog.getValueOf('tab-existing', 'alignment'));
			}
			else if (dialog.getValueOf('tab-web', 'location') != '') {
				reason_image.setAttribute('src', dialog.getValueOf('tab-web', 'location'));
				reason_image.setAttribute('alt', dialog.getValueOf('tab-web', 'description'));
				reason_image.setAttribute('style', 'float: ' + dialog.getValueOf('tab-web', 'alignment'));				
			}

			// Insert the element into the editor at the caret position.
			editor.insertElement(reason_image);
		}
	};
});

