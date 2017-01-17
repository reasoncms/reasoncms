/**
 * Reason image plugin dialog window definition
 *
 * See http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */
ReasonCKPlugin = function() {
};

/**
 * jsonURL handles url and query string building for json requests.
 * For example, jsonURL(15, 6, 'image') should return a URL for the sixteenth
 * to the twenty-second images of the list.
 *
 * @param {Number} offset     the index of the first item to fetch
 * @param {Number} chunk_size the number of items to fetch
 * @param {String}  type the feed type of items to fetch, i.e. image or link
 */
ReasonCKPlugin.prototype.jsonURL = function(offset, chunk_size, type) {
    var site_id = this.editor.config.customValues.reason_site_id,
        reason_http_base_path = this.editor.config.customValues.reason_http_base_path;

    return reason_http_base_path + 'displayers/generate_json.php?site_id=' + site_id + '&type=' + type + '&num=' + chunk_size + '&offset=' + offset + '&';
};

/**
 * Searches this.items for ReasonCKPluginDialogItems that contain a search
 * term in their keywords, title, or description.
 * @param {String} q The string to look for in items
 * @return {Array<ReasonCKPluginDialogItem>} an array of matching ReasonCKPluginDialogItems
 **/
ReasonCKPlugin.prototype.findItemsWithText = function (q) {
    var result = [],
        list = this.items,
        regex = new RegExp(q, "i");
    for (var i in list) {
        if (list.hasOwnProperty(i)) {
            if (list[i].hasText(regex)) {
                result.push(list[i]);
            }
        }
    }
    return result;
};

/**
 * Enables/disables "Next Page"/"Previous Page" buttons.
 * Should be called after every new chunk is loaded, page is displayed,
 * or search result is calculated.
 **/
ReasonCKPlugin.prototype.updatePagination = function() {
    var numberOfPages = this.numberOfPages();
    this.nextButton.css('visibility', (this.page + 1 > numberOfPages) ? 'hidden' : 'visible');
    this.prevButton.css('visibility', (this.page - 1 <= 0) ? 'hidden' : 'visible');
    if (numberOfPages > 1)
    {
        this.document.find(".cke-pagination .pageCount").getItem(0).setStyle('visibility', 'visible');
        this.document.find(".cke-pagination .pageCount").getItem(0).setHtml(this.pageCounter());
    }
    else
    {
        pagination = this.document.find('.cke-pagination  .pageCount').getItem(0);
        if (pagination) pagination.setStyle('visibility', 'hidden');
    }
};

ReasonCKPlugin.prototype.numberOfPages = function() {
    var numPages = Math.ceil(this.displayedItems.length / this.pageSize);
    return numPages;
}
/**
 * Returns a string that represents the page number over total pages.
 **/
ReasonCKPlugin.prototype.pageCounter = function() {
    return "Pg. " + this.page + "/" + this.numberOfPages();
};

/**
 * A page is a slice of the displayedItems array. This function returns
 * a slice of the array given a page number.
 * @param {Number} page_num 1-indexed page number.
 **/
ReasonCKPlugin.prototype.makePageSlice = function(page_num) {
    var begin, end;
    begin = ((page_num - 1) * this.pageSize);
    end = begin + this.pageSize;
    return this.displayedItems.slice(begin, end);
};


ReasonCKImage = function(editor) {
    this.editor = editor;
    this.chunkSize = 3;
    this.pageSize = 6;
    this.page = 1;
    this.feedType = "image";
    this.items = [];
    this.document = CKEDITOR.dialog.getCurrent().getElement().getDocument();

    this.setupReasonUI();
    this.renderReasonImages();
};
ReasonCKImage.prototype = new ReasonCKPlugin();

ReasonCKImage.prototype.setupReasonUI = function() {
    var context = this;

    this.prevButton = $('#cke_previous_page');
    this.nextButton = $('#cke_next_page');
    this.prevButton.on('click', function() {
        context.page -= 1;
        context.displayImages(context.makePageSlice(context.page));
    });

    this.nextButton.on('click', function() {
        context.page += 1;
        context.displayImages(context.makePageSlice(context.page));
    });


};

ReasonCKImage.prototype.renderReasonImages = function () {
    this.fetchImages(1)
};

ReasonCKImage.prototype.parseImages = function(data) {
    var parsed_response = data,
        response_items = parsed_response.items,
        item;

    this.totalItems = parsed_response.count;

    for (var i in response_items) {
        item = new ReasonCKImageDialogItem();
        item.name = response_items[i].name;
        item.id = response_items[i].id;
        item.description = response_items[i].description;
        item.pubDate = response_items[i].pubDate;
        item.lastMod = response_items[i].lastMod;
        item.URLs = {'thumbnail': response_items[i].thumbnail, 'full': response_items[i].link};
        this.items.push(item);
    }
};

/**
 * Fetches all of the images that belong to or are borrowed from a site,
 * via ajax as a series of chunks of size CHUNK_SIZE, and executes
 * a callback after the first chunk finishes downloading.
 * @param {Number}   chunk    the number of the chunk to get. Used for calculating
 *                          offset.
 **/
ReasonCKImage.prototype.fetchImages = function(chunk) {
    var context = this;
    var offset = ((chunk - 1) * this.chunkSize), url;
    url = this.jsonURL(offset, this.chunkSize, this.feedType);

    function processData(data) {
        console.log(data);
        context.parseImages(data);
        context.displayedItems = context.items;
        context.updatePagination();
        if (chunk + 1 <= context.totalItems / context.chunkSize) {
            context.fetchImages(chunk + 1);
        } else {
            // DONE!
            // context.loaded();
            this.displayedItems = this.items;
            if (context.items.length != 0)
                context.displayImages();
            else {
                // context.noImages();
                // context.switchToTab('URL');
            }
        }

    }

    $.getJSON(url, processData);
};

/**
 * Renders an array of ReasonCKImageDialogItems to
 * this.imagesListBox.innerHTML. If there is no array provided,
 * renders the first page of result from the current context (images or
 * search results).
 * @param {Array<ReasonCKImageDialogItem>} images_array
 **/
ReasonCKImage.prototype.displayImages = function (images_array) {
    var imagesHtml = "",
        imagesContainer = this.document.find('#image-list').getItem(0),
        cur_src;

    images_array = (!images_array && this.displayedItems) ? this.makePageSlice(1) : images_array;
    // cur_src = this.srcControl.value();
    cur_src = false;
    for (var i in images_array)
    {
        selected = false;
        if (!!cur_src)
        {
            // for (url in images_array[i].URLs) {
            //     if (cur_src == images_array[i].URLs[url])
            //     {
            //         selected = true;
            //         break;
            //     }
            // }
        }
        imagesHtml += images_array[i].displayItem(selected);
    }
    imagesContainer.setHtml(imagesHtml);
    this.updatePagination();
};


var ReasonCKPluginDialogItem = function() {};

var ReasonCKImageDialogItem = function () {};
ReasonCKImageDialogItem.prototype = new ReasonCKPluginDialogItem();
ReasonCKImageDialogItem.prototype.escapeHtml = function (unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
};

ReasonCKImageDialogItem.prototype.URLs = {
    thumbnail: '',
    full: ''
};
ReasonCKImageDialogItem.prototype.hasText = function(q) {
    if ((this.name && this.name.search(q) !== -1) || (this.description && this.description.search(q) !== -1))
        return this;
};

ReasonCKImageDialogItem.prototype.description = '';

/**
 * render image in the dialog box with selectedImage class if it is the current image.
 */
ReasonCKImageDialogItem.prototype.displayItem = function ( selected ) {
    var selectedImageClass = (selected == true) ? " selectedImage" : "";
    var imageHTML = '';
    imageHTML += '<figure data-image-id="' + this.id + '" class="cke_chrome' + selectedImageClass + '">';
    imageHTML += '<img src="' + this.URLs['thumbnail'] + '" alt="' + this.escapeHtml(this.description) + '"/>';
    imageHTML += '<figcaption class="ui-dialog description">' + this.escapeHtml(this.name) + '</figcaption>';
    imageHTML += '</figure>';
    return imageHTML;
};

// this is the CKEditor dialog code.
CKEDITOR.dialog.add( 'reasonImageDialog', function( editor ) {
    var selectedKey = -1;
    var page = 1;
    var dialog;


    const MIN_WIDTH = 425;
    const MIN_HEIGHT = 400;
    var dialogDefinition = {
        // Basic properties of the dialog window: title, minimum size.
        title: 'Insert/Edit image',
        minWidth: MIN_WIDTH,
        minHeight: MIN_HEIGHT,

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
                                html: '<div class="cke-pagination">' +
                                '  <div class="pagination-wrapper">' +
                                '    <a class="cke_dialog_ui_button" id="cke_previous_page">' +
                                '       <span class="cke_dialog_ui_button">&lt;&lt; Prev</span>' +
                                '    </a>' +
                                '    <span class="pageCount">Pg. 1/10</span>' +
                                '    <a class="cke_dialog_ui_button" id="cke_next_page">' +
                                '       <span class="cke_dialog_ui_button">Next &gt;&gt;</span>' +
                                '    </a>' +
                                '  </div>' +
                                '</div>' +
                                '<div id="image-list"></div>'
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
            this.imageHandler = imageHandler = new ReasonCKImage(editor);
            // getJSON is called asynchronously, and onShow: gets called before getJSON returns

            // TODO: fix the #cke_39_textInput hack using registerEvents() instead
            // TODO: figure out that this does -- Tom
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
                imageHandler.displayImages();
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
            // debugger;
            if (this.imageHandler.items.length > 0) {
                $('figure').removeClass('selectedImage');
                this.imageHandler.displayImages();
            }
        },

        // Method is invoked once a user clicks the OK button, confirming the dialog.
        onOk: function() {
            // Create a new img url link
            var reason_image = editor.document.createElement('img');

            if (this._.currentTabId == 'tab-existing') {
                var selectedImageElement = $('figure.selectedImage');
                if (selectedImageElement.length > 0) {
                    var selectedImageId = selectedImageElement.data().imageId;
                    var selectedItem;
                    for (var i = 0; i < this.imageHandler.displayedItems.length; i++) {
                        var id = this.imageHandler.displayedItems[i].id;
                        if (id && parseInt(id) === selectedImageId) {
                            selectedItem = this.imageHandler.displayedItems[i];
                            break;
                        }
                    }
                    if (selectedItem) {
                        imageType = this.getValueOf('tab-existing', 'size');
                        altText = selectedItem.description;
                        reason_image.setAttribute('src', selectedItem.URLs[imageType]);
                        reason_image.setAttribute('alt', altText);
                        reason_image.setAttribute('style', 'float: ' + this.getValueOf('tab-existing', 'alignment'));
                    }
                }
            } else if (this.getValueOf('tab-web', 'location') !== '') {
                reason_image.setAttribute('src', this.getValueOf('tab-web', 'location'));
                reason_image.setAttribute('alt', this.getValueOf('tab-web', 'description'));
                reason_image.setAttribute('style', 'float: ' + this.getValueOf('tab-web', 'alignment'));
            }
            // Insert the element into the editor at the caret position.
            editor.insertElement(reason_image);
        }
    };
    return dialogDefinition;
});

