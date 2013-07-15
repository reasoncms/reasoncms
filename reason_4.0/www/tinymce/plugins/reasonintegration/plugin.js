/**
 * ReasonImage and ReasonLink plugins
 *
 * These plugins integrate tinyMCE into the Reason CMS.
 * ReasonImage allows a user to insert an image that belongs
 * to a Reason Site
 */

/*global tinymce:true */


/**
 * ReasonPlugins is a container and dispatch for ReasonImage and ReasonLink.
 *
 * It has some basic configuration, and then rest is done in the constituent
 * functions.
 *
 * Executes the correct plugin for the given filebrowser field type.
 * TODO: json_generator should take the unique name of the type, not the type ID.
 * TODO: We need to account for having multiple editors per page. I think that maybe
 *       we should cache a reference to the current editor's plugin and check if activeEditor
 *       is the same as the last time ReasonPlugin was called?
 * TODO: Change ReasonPlugin.getPanel to keep going up elements until we find a
 *       parent of type panel to make it a little more robust.
 * TODO: insertReasonUI should insert a tinymce control of type panel w/ settings.html, maybe?
 * TODO: the plugin should use a real CSS file (not js-css in insertReasonUI)
 * TODO: to style each element, insertReasonUI should copy styles/classes from native tinymce
 *       elements.
 * TODO: A selected image's highlight doesn't persist between renders of the page of results.
 * TODO: use reason_http_base_path to reduce size of JSON being requested.
 * TODO: Search for a selected image as chunks come in, rather than all at the end.
 *
 * @param {Object} controlSelectors The items to which the the picker will be bound
 * @param {String} targetPanelSelector The item to which the the picker will be bound
 * @param {String} type 'image' or 'link'; determines which plugin to use
 **/
ReasonPlugin = function () {
  this.whenLoadedFuncs = [];
};

/**
 * jsonURL handles url and query string building for json requests.
 * For example, jsonURL(15, 6, 'image') should return a URL for the sixteenth
 * to the twenty-second images of the list.
 *
 * @param {Number} offset     the index of the first item to fetch
 * @param {Number} chunk_size the number of items to fetch
 * @param {String}  type       the type of items to fetch, i.e. image or link
 */
ReasonPlugin.prototype.jsonURL = function (offset, chunk_size, type) {
  var site_id = tinymce.activeEditor.settings.reason_site_id,
    reason_http_base_path = tinymce.activeEditor.settings.reason_http_base_path;

  return reason_http_base_path + 'displayers/generate_json.php?site_id=' + site_id + '&type=' + type + '&num=' + chunk_size + '&offset=' + offset + '&';
};

/**
 * Returns the tinyMCE control object for a given tinymce control name.
 *
 * @param {String} selector the 'name' value of a tinymce control
 **/
ReasonPlugin.prototype.getControl = function (selector) {
  return this.window.find('#' + selector)[0];
};

ReasonPlugin.prototype.getWindow = function(windowName) {
  var windows;
  windows = tinymce.activeEditor.windowManager.windows;
  for (var i in windows) {
    if (windows[i].name() == windowName)
      return windows[i];
  }
  return false;
};

/**
 * Gets a reference to tinyMCE's representation of the panel that holds the filePicker.
 * This code is pretty fragile, but could be improved to be more robust.
 * The fundamental consideration re: fragility is: "What is my containing element?" or,
 * more specifically, "Where do I want to put the ReasonPlugin controls?"
 * @param {String} control the selector for the file browser control
 **/
ReasonPlugin.prototype.getPanel = function (control) {
  return control.parent().parent();
};

// From SO: http://stackoverflow.com/questions/1909441/jquery-keyup-delay
ReasonPlugin.prototype.delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

/**
 * Searches this.items for ReasonPluginDialogItems that contain a search
 * term in their keywords, title, or description.
 * @param {String} q The string to look for in items
 * @return {Array<ReasonPluginDialogItem>} an array of matching ReasonPluginDialogItems
 **/
ReasonPlugin.prototype.findItemsWithText = function (q) {
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
 * Handles enabling/disabling of "Next Page"/"Previous Page" buttons.
 * Should be called after every new chunk is loaded, page is displayed,
 * or search result is calculated.
 **/
ReasonPlugin.prototype.updatePagination = function() {
  var num_of_pages = Math.ceil(this.displayedItems.length/this.pageSize);
  this.nextButton.disabled = (this.page + 1 > num_of_pages);
  this.prevButton.disabled = (this.page - 1 <= 0);
};

ReasonPlugin.prototype.makePageSlice = function(page_num) {
  var begin, end;

  begin = ((page_num - 1) * this.pageSize);
  end = begin + this.pageSize;
  return this.displayedItems.slice(begin, end);
}

ReasonPlugin.prototype.loaded = function() {
  var self = this;
  tinymce.each(this.whenLoadedFuncs, function(v) {v.call(self);});
}
ReasonPlugin.prototype.whenLoaded = function(func_to_add) {
  this.whenLoadedFuncs.push(func_to_add);
};

/**
 * Dispatch function. Gets a reference to the panel, and does everything we
 * need to do in order to get the plugin up and running.
 */
ReasonImage = function(controlSelectors, placeholderSelector) {
  this.chunkSize = 1000;
  this.pageSize = 6;
  this.page = 1;
  this.type = "image";
  this.items = [];

  this.getControlReferences(controlSelectors, placeholderSelector);
  this.insertReasonUI();
  this.bindReasonUI();
  this.renderReasonImages();
};
ReasonImage.prototype = new ReasonPlugin();


ReasonImage.prototype.getControlReferences = function(controlSelectors, placeholderSelector) {
  var self = this;

  this.window = this.getWindow(controlSelectors.tabPanel);
  this.targetPanel = this.getControl(placeholderSelector);
  this.srcControl = this.getControl(controlSelectors.src);
  this.altControls = tinymce.map(controlSelectors.alt, function(item) {
    return self.getControl(item);
  });
  this.alignControls = tinymce.map(controlSelectors.align, function(item) {
    return self.getControl(item);
  });
  this.sizeControl = this.getControl(controlSelectors.size);
}

/**
 * Prepends the reason controls to the tinyMCE panel specified by
 * this.targetPanel.
 **/
ReasonImage.prototype.insertReasonUI = function() {
  var holderDiv;
  this.UI = this.targetPanel.getEl();
  var css = '.selectedImage {background-image: linear-gradient(to bottom, rgb(222, 222, 222), rgb(184, 184, 184)); } button:disabled, button:disabled:hover, button:disabled:focus, button[disabled=true] { background-image: linear-gradient(to bottom, rgb(222, 222, 222), rgb(184, 184, 184)) !important; color: #aaaaaa; } .items_chunk { text-align: center; height: 300px; white-space: normal; text-align: center; overflow: auto;} .image_item {width: 190px; padding: 5px; display: inline-block; text-align: center; } .items_chunk .name, .items_chunk .description {display: block; white-space: normal; text-align: center;} .items_chunk .description {font-size: 0.9em;}' ,
    head = document.getElementsByTagName('head')[0],
    style = document.createElement('style');

  style.type = 'text/css';
  if (style.styleSheet){
    style.styleSheet.cssText = css;
  } else {
    style.appendChild(document.createTextNode(css));
  }

  head.appendChild(style);
  holderDiv = document.createElement("div");
  var search = '<div style="margin-left: 20px; margin-top: 20px; width: 660px; height: 30px;" class="mce-container-body mce-abs-layout"><div id="mce_51-absend" class="mce-abs-end"></div><label style="line-height: 18px; left: 0px; top: 6px; width: 122px; height: 18px;" id="mce_52" class="mce-widget mce-label mce-first mce-abs-layout-item">Search:</label><input style="left: 122px; top: 0px; width: 528px; height: 28px;" id="searchyThing" class="reasonImageSearch mce-textbox mce-last mce-abs-layout-item" value="" hidefocus="true" size="40"></div>';
  holderDiv.innerHTML = '<div class="reasonImage">' + search + '<button class="mce-btn prevImagePage" type="button">Previous</button><button class="mce-btn nextImagePage">Next</button><div class="items_chunk"> </div></div>';

  this.UI.insertBefore(holderDiv.firstChild, this.UI.firstChild);

};

/**
 * Binds various controls like cancel, next page, and search to their
 * corresponding functions.
 **/
ReasonImage.prototype.bindReasonUI = function() {
  var self = this;

  this.imagesListBox = this.UI.getElementsByClassName('items_chunk')[0];
  this.prevButton = this.UI.getElementsByClassName('prevImagePage')[0];
  this.nextButton = this.UI.getElementsByClassName('nextImagePage')[0];
  this.searchBox = this.UI.getElementsByClassName('reasonImageSearch')[0];

  // Maybe I should move these bindings elsewhere for better coherence?
  tinymce.DOM.bind(this.imagesListBox, 'click', function(e) {
    var target = e.target || window.event.srcElement;
    if (target.nodeName == 'A' && target.className == 'image_item')
      self.selectImage( target );
    else if (target.nodeName == 'IMG' || (target.nodeName == 'SPAN' && (target.className == 'name' || target.className == 'description')))
      self.selectImage( target.parentElement );
  });

  tinymce.DOM.bind(this.prevButton, 'click', function() {
    self.page -= 1;
    self.displayImages(self.makePageSlice(self.page));
  });

  tinymce.DOM.bind(this.nextButton, 'click', function() {
    self.page += 1;
    self.displayImages(self.makePageSlice(self.page));
  });

  this.sizeControl.on('select', function () {
    self.setImageSize(self.sizeControl.value());
  });

  this.altControls[0].on('change', function() {
    self.setAlt(self.altControls[0].value());
  });
  this.altControls[1].on('change', function() {
    self.setAlt(self.altControls[1].value());
  });

  this.alignControls[0].on('select', function(e) {
    self.setAlign(e.control.value());
  });
  this.alignControls[1].on('select', function(e) {
    self.setAlign(e.control.value());
  });

  tinymce.DOM.bind(this.searchBox, 'keyup', function(e) {
    var target = e.target || window.event.srcElement;
    self.delay(function() {
      if (target.value) {
        self.page = 1;
        self.result = self.findItemsWithText(target.value);
        self.displayedItems = self.result;
        self.displayImages();
      } else {
        self.page = 1;
        self.displayedItems = self.items;
        self.displayImages();
      }
    }, 200);
  });
};

ReasonImage.prototype.switchToTab = function(tabName) {
  if (tabName === "reason") {
    this.window.find("tabpanel")[0].activateTab(0);
  } else {
    this.window.find("tabpanel")[0].activateTab(1);
  }
};

ReasonImage.prototype.setAlt = function(alt) {
  tinymce.each(this.altControls, function(v) {v.value(alt);});
};
ReasonImage.prototype.setAlign = function(align) {
  tinymce.each(this.alignControls, function(v) {v.value(align);});
};
ReasonImage.prototype.setSrc = function(src) {
  this.srcControl.value(src);
};
ReasonImage.prototype.deduceSize = function(url) {
    if (url.search("_tn.") != -1)
      return "thumbnail";
    else
      return "full";
};

ReasonImage.prototype.findPageWith = function(image_url) {
  for (var i = 0; i < this.items.length; i++) {
    if (this.items[i].URLs.thumbnail == image_url || this.items[i].URLs.full == image_url) {
      return Math.ceil((i+1) / this.pageSize);
    }
  }
  return false;
};

ReasonImage.prototype.displayPageWith = function (image_url) {
  var thePage = this.findPageWith(image_url);
  if (!thePage)
    return false;
  else {
    this.displayImages(this.makePageSlice(this.findPageWith(image_url)));
    return true;
  }
};

ReasonImage.prototype.findImageItemOnPage = function (image_item) {
  var images = this.targetPanel.getEl().getElementsByTagName("IMG");
  for (var i in images) {
    if (images[i].src == image_item || images[i].src.replace("_tn", "") == image_item) {
      return images[i].parentNode;
    }
  }
};

/**
 * Links reason controls (selecting an image, writing alt text) to hidden
 * tinyMCE elements.
 * @param {HTMLDivElement|String} image_item the div that contains the image
 */
ReasonImage.prototype.selectImage = function (image_item) {
  if (typeof image_item == "string") {
    if (this.displayPageWith(image_item)) {
      image_item = this.findImageItemOnPage(image_item);
      this.switchToTab("reason");
    } else
      return false;
  };

  this.highlightImage(image_item);

  var src = image_item.getElementsByTagName('IMG')[0].src;
  if (!!this.imageSize && this.imageSize == 'full')
    src = src.replace("_tn", "");

  this.setSrc(src);
  this.setAlt(image_item.getElementsByClassName('description')[0].innerHTML);
  return true;
};

ReasonImage.prototype.highlightImage = function(image_item) {
  tinymce.each(this.window.getEl().getElementsByClassName("selectedImage"), function(v) {v.className = v.className.replace("selectedImage",""); });
  image_item.className += " selectedImage";
};

/**
 * setImageSize is used to do some string voodoo on the src attribute. Call it whenever
 * src or the image size is changed.
 *
 * @param {String} size
 */

ReasonImage.prototype.setImageSize = function (size) {
  this.imageSize = size;

  if (this.sizeControl.value() != size)
    this.sizeControl.value(size);

  var curVal = this.srcControl.value(),
    reason_http_base_path = tinymce.activeEditor.settings.reason_http_base_path;
  if (!curVal || curVal.search(reason_http_base_path) == -1)
    return;
  if (size == "full") {
    if (curVal.search("_tn.") != -1) {
      this.srcControl.value(curVal.replace("_tn", ""));
    }
  } else if (curVal.search("_tn.") == -1) {
    var add_from = curVal.lastIndexOf('.'),
      string;
    string = curVal.substr(0, add_from) + "_tn" + curVal.substr(add_from);
    this.srcControl.value(string);
  }
};

ReasonImage.prototype.renderReasonImages = function () {
  this.fetchImages(1, function() {
    this.displayedItems = this.items;
    this.displayImages();
  });
};

/**
 * Renders an array of ReasonImageDialogItems to
 * this.imagesListBox.innerHTML. If there is no array provided,
 * renders the first page of result from the current context (images or
 * search results).
 * @param {Array<ReasonImageDialogItem>} images_array
 **/
ReasonImage.prototype.displayImages = function (images_array) {
  var imagesHTML = "";

  images_array = (!images_array && this.displayedItems) ? this.makePageSlice(1) : images_array;

  for (var i in images_array) {
    i = images_array[i];
    imagesHTML += i.displayItem();
  }

  this.imagesListBox.innerHTML = imagesHTML;
  this.updatePagination();
};

/**
 * Given a response, constructs ReasonImageDialogItems and pushes
 * each one onto the this.items array.
 * @param {String} response the JSON string that contains the items
 **/
ReasonImage.prototype.parseImages = function(response) {
  var parsed_response = JSON.parse(response), response_items = parsed_response.items, item;

  this.totalItems = parsed_response.count;

  for (var i in response_items) {
    item = new ReasonImageDialogItem();
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
 * via ajax as a series of chunks of size this.chunkSize, and executes
 * a callback after the first chunk finishes downloading.
 * @param {Number}   chunk    the number of the chunk to get. Used for calculating
 *                          offset.
 * @param {Function} callback a function to be executed when the chunk has finished
 *                          being downloaded and parsed.
 **/
ReasonImage.prototype.fetchImages = function (chunk, callback) {
  if (this.closed)
    return;

  if (!this.jsonURL)
    throw "You need to set a URL for the dialog to fetch JSON from.";

  var offset = ((chunk - 1) * this.chunkSize), url;

  if (typeof this.jsonURL === 'function')
  {
    url = this.jsonURL(offset, this.chunkSize, this.type);
  } else
    url = this.jsonURL;

  tinymce.util.XHR.send({
    "url": url,
    "success": function(response) {
      this.parseImages(response, chunk);
      callback.call(this);
      if (chunk+1 <= this.totalItems/this.chunkSize)
        this.fetchImages(chunk+1, function() {});
      else
        this.loaded();
    },
    "success_scope": this
  });
};

var ReasonPluginDialogItem = function() {};

var ReasonImageDialogItem = function () {};
ReasonImageDialogItem.prototype = new ReasonPluginDialogItem();
ReasonImageDialogItem.prototype.escapeHtml = function (unsafe) {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
};

ReasonImageDialogItem.prototype.URLs = {
  thumbnail: '',
  full: ''
};
ReasonImageDialogItem.prototype.hasText = function(q) {
  if ((this.name && this.name.search(q) !== -1) || (this.description && this.description.search(q) !== -1))
    return this;
};

ReasonImageDialogItem.prototype.description = '';


ReasonImageDialogItem.prototype.renderItem = function () {
  var size, description;
  size = 'thumbnail';
  description = this.escapeHtml(this.description);
  return '<img ' +
    'src="' + this.URLs[size] +
    '" alt="' + description + '"></img>';
};

ReasonImageDialogItem.prototype.displayItem = function () {
  return '<a id="reasonimage_' + this.id + '" class="image_item"><span class="name">' + this.escapeHtml(this.name) + '</span>' + this.renderItem() + '<span class="description">' + this.escapeHtml(this.description) + '</span></a>';
};


ReasonLink = function() {};
ReasonLink.prototype = new ReasonPlugin();



/**
 * This is the actual tinyMCE plugin.
 */



tinymce.PluginManager.add('reasonintegration', function(editor, url) {

  function showDialog() {
    var win, data, dom = editor.dom, imgElm = editor.selection.getNode(), reasonImagePlugin;
    var width, height;

    if (imgElm.nodeName == "IMG" && !imgElm.getAttribute('data-mce-object')) {
      data = {
        src: dom.getAttrib(imgElm, 'src'),
        alt: dom.getAttrib(imgElm, 'alt')
      };
    } else {
      imgElm = null;
    }

    tinymce.activeEditor = editor;

    win = editor.windowManager.open({
      title: 'Add an image',
      name: 'reasonImageWindow',
      body: [
        // Add from Reason
        {
          title: "existing image",
          name: "reasonImagePanel",
          type: "form",
          minWidth: "700",
          minHeight: "525",
          items: [
            {name: 'alt_2', type: 'textbox', size: 40, label: 'Description'},
            {name: 'size', type: 'listbox', label: "Size", values: [
              {text: 'Thumbnail', value: 'thumb'},
              {text: 'Full', value: 'full'}
            ]},
            {name: 'align_2', type: 'listbox', label: "Align", values: [
              {text: 'None', value: 'none'},
              {text: 'Left', value: 'left'},
              {text: 'Right', value: 'right'}
            ]}
          ],
          onchange: function(e) {console.log(!!e.target? e.target.value: e);}
        },

        // Add from the Web
        {
          title: "from a web address",
          type: "form",
          items: [{
            name: 'src',
            type: 'textbox',
            filetype: 'image',
            size: 40,
            autofocus: true,
            label: 'URL'
          }, {
            name: 'alt',
            type: 'textbox',
            size: 40,
            label: 'Description'
          }, {
            name: 'align', type: 'listbox', label: "Align", values: [
              {text: 'None', value: 'none'},
              {text: 'Left', value: 'left'},
              {text: 'Right', value: 'right'}
            ]
          }]
        }

      ],
      bodyType: 'tabpanel',
      onPostRender: function(e) {
        var target_panel = 'reasonImagePanel',
            controls_to_bind = {
              tabPanel: "reasonImageWindow",
              src: 'src',
              alt: ['alt', 'alt_2'],
              align: ['align', 'align_2'],
              size: 'size'
            };
        reasonImagePlugin = new ReasonImage(controls_to_bind, target_panel, 'image', e);
        if (imgElm) {
          reasonImagePlugin.switchToTab("URL");
          reasonImagePlugin.setAlign(imgElm.align);
          reasonImagePlugin.setAlt(imgElm.alt);
          reasonImagePlugin.setSrc(imgElm.src);
          reasonImagePlugin.whenLoaded(function() {
            if (this.selectImage(imgElm.src)) {
              this.setImageSize(this.deduceSize(imgElm.src));
              this.setAlt(imgElm.alt);
            }
          });
        }
      },
      onSubmit: function() {
        var data = win.toJSON();
        if (!data.src)
          return;

        data.align == false && delete data.align;

        if (imgElm) {
          dom.setAttribs(imgElm, data);
        } else {
          editor.insertContent(dom.createHTML('img', data));
        }

        reasonImagePlugin.closed = true;
      },
      onClose: function() {
        reasonImagePlugin.closed = true;
      }
    });
  }

  editor.addButton('reasonimage', {
    icon: 'image',
    tooltip: 'Insert/edit image',
    onclick: showDialog,
    stateSelector: 'img:not([data-mce-object])'
  });

  editor.addMenuItem('reasonimage', {
    icon: 'image',
    text: 'Insert image',
    onclick: showDialog,
    context: 'insert',
    prependToContext: true
  });
});
