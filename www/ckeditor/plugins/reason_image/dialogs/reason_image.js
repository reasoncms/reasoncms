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
            images.push('<figure class="cke_chrome"><img src=\"' + dataObjects[keys[i]].thumbnail + '\"><figcaption class="ui-dialog">' + dataObjects[keys[i]].name + '</figcaption></figure>');
        }

        if (element)
            element.setHtml(images.join(""));		
    }

    /**
     * jsonURL handles url and query string building for json requests.
     * For example, jsonURL(15, 6, 'image') should return a URL for the sixteenth
     * to the twenty-second images of the list.
     *
     * @param {Number} offset     the index of the first item to fetch
     * @param {Number} chunk_size the number of items to fetch
     * @param {String}  type       the type of items to fetch, i.e. image or link
     */
    function jsonURL(offset, chunk_size, type) {
        var site_id = tinymce.activeEditor.settings.reason_site_id,
            reason_http_base_path = tinymce.activeEditor.settings.reason_http_base_path;

        return reason_http_base_path + 'displayers/generate_json.php?site_id=' + site_id + '&type=' + type + '&num=' + chunk_size + '&offset=' + offset + '&';
    };



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
                                html: '<div id="image-list"></div>'
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
                console.log(data);
                  $.each(data.items, function(key, value) {
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
                console.log(filteredImgKeys);
                writeImagesHtml();
            });
            
            $(document).on('click', 'figure', function() {
                if ($(this).hasClass('selectedImage')) {
                    $(this).removeClass('selectedImage');
                }
                else {
                    $(this).siblings('figure').removeClass('selectedImage');
                    $(this).addClass('selectedImage');
                }
            });
        },

        // Called every time the dialog is opened
        onShow: function() {
            if (imgKeys.length > 0) {
                $('figure').removeClass('selectedImage');
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
                reason_image.setAttribute('src', $('figure.selectedImage > img').attr('src'));
                reason_image.setAttribute('alt', $('figure.selectedImage > figcaption').html());
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

