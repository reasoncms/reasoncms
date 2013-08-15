/**
 * ReasonImage and ReasonLink plugins
 *
 * These plugins integrate tinyMCE into the Reason CMS.
 *
 * ReasonImage allows a user to insert an image that belongs to a Reason Site.
 *
 * ReasonLink allow a user to link to pages on Reason sites.
 *
 * @todo allow links to additional types.
 *
 * @author Andrew Bacon
 * @author Nathan White
 */

/**
 * ReasonPlugins is a container and dispatch for ReasonImage and ReasonLink.
 *
 * It has some basic configuration, and then rest is done in the constituent
 * functions.
 *
 * Executes the correct plugin for the given filebrowser field type.
 * TODO: We need to account for having multiple editors per page. I think that maybe
 *       we should cache a reference to the current editor's plugin and check if activeEditor
 *       is the same as the last time ReasonPlugin was called?
 * TODO: Change ReasonPlugin.getPanel to keep going up elements until we find a
 *       parent of type panel to make it a little more robust.
 * TODO: insertReasonUI should insert a tinymce control of type panel w/ settings.html, maybe?
 * TODO: to style each element, insertReasonUI should copy styles/classes from native tinymce
 *       elements.
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

/**
 * Gets a reference to the plugin's lightbox window.
 * @param {String} windowName the 'name' value of the window.
 **/
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

 /**
 * Performs some function after a short delay, but resets timer on each
 * successive call, rather than queuing calls. Used to buffer search-as-
 * you-type.
 *
 * From SO: http://stackoverflow.com/questions/1909441/jquery-keyup-delay
 **/

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
 * Enables/disables "Next Page"/"Previous Page" buttons.
 * Should be called after every new chunk is loaded, page is displayed,
 * or search result is calculated.
 **/
ReasonPlugin.prototype.updatePagination = function() {
  var num_of_pages = Math.ceil(this.displayedItems.length/this.pageSize);
  this.nextButton.disabled = (this.page + 1 > num_of_pages);
  this.prevButton.disabled = (this.page - 1 <= 0);
  if (num_of_pages > 1)
  {
  	this.UI.querySelector(".reasonImage .pagination").style.visibility = 'visible';
  	this.UI.querySelector(".reasonImage .pageCount").innerHTML = this.pageCounter();
  }
  else
  {
  	pagination = this.UI.querySelector(".reasonImage .pagination");
  	if (pagination) pagination.style.visibility = 'hidden';
  }
};

/**
 * Returns a string that represents the page number over total pages.
 **/
ReasonPlugin.prototype.pageCounter = function() {
  var numPages = Math.ceil(this.displayedItems.length/this.pageSize);
  return "Pg. " + this.page + "/" + numPages;
};

/**
 * A page is a slice of the displayedItems array. This function returns
 * a slice of the array given a page number.
 * @param {Number} page_num 1-indexed page number.
 **/
ReasonPlugin.prototype.makePageSlice = function(page_num) {
  var begin, end;

  begin = ((page_num - 1) * this.pageSize);
  end = begin + this.pageSize;
  return this.displayedItems.slice(begin, end);
};

/**
 * This function is called when all items have been loaded and no more
 * data is required. It in turn calls all functions which have been
 * queued using ReasonPlugin.whenLoaded();
 **/
ReasonPlugin.prototype.loaded = function() {
	var self = this;
	tinymce.each(this.whenLoadedFuncs, function(v) {
		v.call(self);
	});
};

/**
 * Calls func when all JSON has finished loading.
 * @param {Function} func A function to add to the callbacks array.
 **/
ReasonPlugin.prototype.whenLoaded = function(func) {
	this.whenLoadedFuncs.push(func);
};

/**
 * Dispatch function. Gets a reference to the panel, and does everything we
 * need to do in order to get the plugin up and running.
 *
 * @param {Object} controlSelectors Map of certain control types to names
 *        been assigned. Should contain names for tabPanel, src, size,
 *        alt (array of names) and align (array of names).
 * @param {Object} placeholderSelector name of the window that will contain
 *        the plugin controls.
 */
ReasonImage = function(controlSelectors, placeholderSelector) {
  this.whenLoadedFuncs = [];
  this.chunkSize = 5000;
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
ReasonImage.prototype.constructor=ReasonImage;

/**
 * Converts names of controls to references to their tinymce data structures.
 * Prevents us from typing this.getControl(string) a lot.
 * @see ReasonImage() for information on parameters.
 **/
ReasonImage.prototype.getControlReferences = function(controlSelectors, placeholderSelector) {
  var self = this;

  this.window = this.getWindow(controlSelectors.tabPanel);
  this.targetPanel = this.getControl(placeholderSelector);
  this.srcControl = this.getControl(controlSelectors.src);
  this.sizeControl = this.getControl(controlSelectors.size);
  this.altControls = tinymce.map(controlSelectors.alt, function(item) {
    return self.getControl(item);
  });
  this.alignControls = tinymce.map(controlSelectors.align, function(item) {
    return self.getControl(item);
  });
};

/**
 * Prepends the reason controls to the tinyMCE panel specified by
 * this.targetPanel.
 **/
ReasonImage.prototype.insertReasonUI = function() {
  var holderDiv;
  this.UI = this.targetPanel.getEl();
  holderDiv = document.createElement("div");
  var search = '<div style="margin-left: 20px; margin-top: 20px; width: 660px; height: 30px;" class="mce-container-body mce-abs-layout"><div id="mce_51-absend" class="mce-abs-end"></div><label style="line-height: 18px; left: 0px; top: 6px; width: 101px; height: 18px;" id="mce_52" class="mce-widget mce-label mce-first mce-abs-layout-item">Search</label><input style="left: 101px; top: 0px; width: 549px; height: 28px;" id="searchyThing" class="reasonImageSearch mce-textbox mce-last mce-abs-layout-item" value="" hidefocus="true" size="40"></div>';
  holderDiv.innerHTML = '<div class="reasonImage">' + search + '<div class="pagination"><span class="pageCount">Pg. 1/10</span><div class="mce-btn mce-widget"><button class="prevImagePage" type="button">Previous</button></div><div class="mce-btn mce-widget"><button class="nextImagePage">Next</button></div></div><div class="items_chunk"></div></div>';
  this.UI.insertBefore(holderDiv.firstChild, this.UI.firstChild);
};

/**
 * Binds various controls like cancel, next page, and search to their
 * corresponding functions.
 **/
ReasonImage.prototype.bindReasonUI = function()	{
	var self = this;
	this.imagesListBox = this.UI.querySelectorAll('.items_chunk')[0];
	this.prevButton = this.UI.querySelectorAll('.prevImagePage')[0];
	this.nextButton = this.UI.querySelectorAll('.nextImagePage')[0];
	this.searchBox = this.UI.querySelectorAll('.reasonImageSearch')[0];
	
	// Maybe I should move these bindings elsewhere for better coherence?
	tinymce.DOM.bind(this.imagesListBox, 'click', function(e) {
		var target = e.target || window.event.srcElement;
		if (target.nodeName == 'A' && target.className == 'image_item') {
			self.selectImage( target );
		}
		else if (target.nodeName == 'IMG' || (target.nodeName == 'SPAN' && (target.className == 'name' || target.className == 'description'))) {
			self.selectImage( target.parentElement );
		}
	});
	
	tinymce.DOM.bind(this.prevButton, 'click', function() {
		self.page -= 1;
		self.displayImages(self.makePageSlice(self.page));
	});
	
	tinymce.DOM.bind(this.nextButton, 'click', function() {
		self.page += 1;
		self.displayImages(self.makePageSlice(self.page));
	});
	
	tinymce.DOM.bind(this.searchBox, 'keyup', function(e) {
		var target = e.target || window.event.srcElement;
		self.delay(function() {
			self.displayedItems = self.findItemsWithText(target.value);
			if (self.displayedItems.length > 0) {
				self.page = 1;
    			self.displayImages();
    		}
    		else {
    			self.noResults();
    		}
    		self.updatePagination();
    	}, 200);
    });
	
	this.sizeControl.on('select', function (e) {
		cur_src = self.srcControl.value();
		for (var i in self.items) {
			for (url in self.items[i].URLs) {
				if (cur_src == self.items[i].URLs[url])
				{
					self.setSrc(self.items[i].URLs[e.control.value()]);
					break;
				}
			}
		}
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
	
	/**
	 * Redraw current page in case new URL matches a reason item on the page.
	 */
	this.srcControl.on('change', function(e) {
		self.displayImages(self.makePageSlice(self.page));
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

/**
 * Set the src
 */
ReasonImage.prototype.setSrc = function(src) {
	this.srcControl.value(src);
};

/**
 * Looks through all loaded items to find the image with the specified url.
 * Used to select an already-inserted image.
 * @param {String} imageUrl the url of the image that you'd like to find. Can
 *        be of either thumbnail or full-size image. 
 **/
ReasonImage.prototype.findPageWith = function(imageUrl) {
  for (var i = 0; i < this.items.length; i++) {
    if (this.items[i].URLs.thumbnail == imageUrl || this.items[i].URLs.full == imageUrl) {
      return Math.ceil((i+1) / this.pageSize);
    }
  }
  return false;
};

/**
 * Renders the page of images that includes the image of given url.
 * @param {String} imageUrl the URL of the image to find.
 **/
ReasonImage.prototype.displayPageWith = function (imageUrl) {
  var thePage = this.findPageWith(imageUrl);
  if (!thePage)
    return false;
  else {
    this.displayImages(this.makePageSlice(this.findPageWith(imageUrl)));
    return true;
  }
};

/**
 * Finds the DOM node which contains an image of the given URL, so that we can select it.
 *
 * @todo this should be based on the ids of those dom nodes and the corresponding URLs in this.items.
 * @param {String} imageUrl the url of the image to select.
 **/
ReasonImage.prototype.findImageItemOnPage = function (imageURL)
{
	var images = this.targetPanel.getEl().querySelectorAll(".image_item");
	for (var i = 0; i < images.length; i++)
	{
		URLs = this.getImageURLs(this.getImageID(images[i]));
		for (var u in URLs)
		{
			if (imageURL == URLs[u])
			{
				return {
					size: u,
					image: images[i]
				};
			}
		}
	}
};

/**
 * Links reason controls (selecting an image, writing alt text) to hidden
 * tinyMCE elements.
 * @param {HTMLDivElement|String} image_item the div that contains the image
 * @todo this should possibly be updated to use ReasonImageDialogItems instead of the DOM?
 */
ReasonImage.prototype.selectImage = function (image_item) {
	if (typeof image_item == "string")
	{
		if (this.displayPageWith(image_item))
    	{
    		image = this.findImageItemOnPage(image_item);
    		if (image)
    		{
    			image_item = image.image;
    			if (this.sizeControl.value() != image.size)
    			{
    				this.sizeControl.value(image.size);
    			}
    			this.switchToTab("reason");
    		}
    		else return false;
    	}
    	else
    	{
    		return false;
    	}
    }
    if (!this.sizeControl.value()) this.sizeControl.value('thumbnail');
    this.highlightImage(image_item);
    image_elm = image_item.getElementsByTagName('IMG')[0];
    URLs = this.getImageURLs(this.getImageID(image_item));
    this.setSrc(URLs[this.sizeControl.value()]);
    this.setAlt(tinymce.DOM.getAttrib(image_elm, 'alt'));
    return true;
};

/**
 * Adds class "selectedImage" to the given DOM node, removes it from all other
 * nodes. Makes it look highlighted.
 * @param {HTMLElement} image_item DOM node to add class to
 **/
ReasonImage.prototype.highlightImage = function(image_item) {
  tinymce.each(this.window.getEl().querySelectorAll(".selectedImage"), function(v) {v.className = v.className.replace("selectedImage",""); });
  image_item.className += " selectedImage";
};

/**
 * Get the image id from an node that has an id this format reasonimage_IMAGEID.
 */
ReasonImage.prototype.getImageID = function(image_node)
{
	return image_node.id.replace("reasonimage_", "");
}

/**
 * Given an image_id from an image_node in the panel, return the URLs from the items array.
 */
ReasonImage.prototype.getImageURLs = function(image_id)
{
	for (var i = 0; i < this.items.length; i++)
	{
		if (image_id == this.items[i].id)
		{
			return this.items[i]['URLs'];
		}
	}
	return;
}

ReasonImage.prototype.renderReasonImages = function () {
  throbber = new tinymce.ui.Throbber(this.imagesListBox);
  throbber.show();
  this.fetchImages(1, function() {
    this.displayedItems = this.items;
    if (this.items.length != 0)
      this.displayImages();
    else {
      this.noImages();
      this.switchToTab('URL');
    }
  });
};

ReasonImage.prototype.noImages = function() {
	this.UI.innerHTML = '<span class="noResult">No images are attached to this site.</span>';
};

ReasonImage.prototype.noResults = function() {
	this.imagesListBox.innerHTML = '<span class="noResult">No images were found with those search terms.</span>';
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
	cur_src = this.srcControl.value();
	for (var i in images_array)
	{
		selected = false;
		if (!!cur_src)
		{
			for (url in images_array[i].URLs) {
				if (cur_src == images_array[i].URLs[url])
				{
					selected = true;
					break;
				}
			}
		}
		imagesHTML += images_array[i].displayItem(selected);
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
      this.updatePagination();
      if (chunk+1 <= this.totalItems/this.chunkSize)
      {
      	this.fetchImages(chunk+1, function() {});
      } else
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

/**
 * render image in the dialog box with selectedImage class if it is the current image.
 */
ReasonImageDialogItem.prototype.displayItem = function ( selected ) {
	selectedImageClass = (selected == true) ? " selectedImage" : "";
	imageHTML = '';
	imageHTML += '<a id="reasonimage_' + this.id + '" class="image_item' + selectedImageClass + '">';
	imageHTML += '<span class="name">' + this.escapeHtml(this.name) + '</span>';
	imageHTML += '<img src="' + this.URLs['thumbnail'] + '" alt="' + this.escapeHtml(this.description) + '"/>';
	imageHTML += '<span class="description">' + this.escapeHtml(this.description) + '</span></a>';
	return imageHTML;
};


// TODO change all reasonPlugins to use TinyMCEObj.on instead of DOM events.




ReasonLink = function(controlSelectors, placeholderSelector) {
	this.whenLoadedFuncs = [];
	this._throbber;
	this._selected = {};
	this._disabled = {};
	this._siteId = tinymce.activeEditor.settings.reason_site_id;
	this._reason_http_base_path = tinymce.activeEditor.settings.reason_http_base_path;
	this.getControlReferences(controlSelectors, placeholderSelector);
	this.initControlVals();
	this.insertReasonUI();
};

ReasonLink.prototype = new ReasonPlugin();
ReasonLink.prototype.constructor = ReasonLink;

ReasonLink.prototype.getControlReferences = function(controlSelectors, placeholderSelector) {
  var self = this;

  if (!this.window) {
    this.window = this.getWindow(controlSelectors.tabPanel);
    this.targetPanel = this.getControl(placeholderSelector);
    this.hrefControls = [this.getControl(controlSelectors.href)];
    this.emailControl = this.getControl(controlSelectors.email);
    this.descriptionControls = tinymce.map(controlSelectors.description, function(item) {
      return self.getControl(item);
    });
  }

  this.formControls = {
    Anchors: this.getControl('anchors'),
    Pages: this.getControl('pages'),
    //Types: this.getControl('types'),
    Sites: this.getControl('sites'),
    Description: this.getControl('title_3')
  };

  this.descriptionControls[2] = this.getControl('title_3');
};
/**
 * Turns an object of params to a query string. Very facile implementation.
 * @param {Object} params mapping of query variable names and values.
 **/
ReasonLink.prototype.jsonURL = function (params) {
  urlString = this._reason_http_base_path + 'displayers/generate_json.php?';

  for (var i in params) {
    if (params.hasOwnProperty(i))
      urlString += i + "=" + params[i] + "&";
  }
  return urlString;
};

ReasonLink.prototype.getFormControl = function() { return this._formControl; };
ReasonLink.prototype.setFormControl = function(formctl) { this._formControl = formctl; };
ReasonLink.prototype.setSites = function(sites) { this._sites = sites; };
ReasonLink.prototype.getSites = function() { return this._sites; };
ReasonLink.prototype.setTypes = function(types) { this._types = types; };
ReasonLink.prototype.getTypes = function() { return this._types; };
ReasonLink.prototype.setPages = function(pages) { this._pages = pages; };
ReasonLink.prototype.getPages = function() { return this._pages; };
ReasonLink.prototype.setDesc = function(desc) {
  this._desc = desc;
  tinymce.each(this.descriptionControls, function(v) {v.value(desc);});
};
ReasonLink.prototype.setHref = function(url) { this.hrefControls[0].value(url); };
ReasonLink.prototype.getDesc = function() { return this._desc; };
ReasonLink.prototype.setAnchors = function(anchors) { this._anchors = anchors; };
ReasonLink.prototype.getAnchors = function() { return this._anchors; };
ReasonLink.prototype.getSelected = function() { return this._selected; };
ReasonLink.prototype.setSelected = function(type, id) {
  if (typeof type == "object")
    this._selected = type;
  else
    this._selected[type] = id;
};
ReasonLink.prototype.getDisabled = function() { return this._disabled; };
ReasonLink.prototype.setDisabled = function(type, value) {
  if (typeof type == "object")
    this._disabled = type;
  else
    this._disabled[type] = value;
};

ReasonLink.prototype.fetchSites = function(callback) {
  this.fetchItems({type: "siteList", site_id: this._siteId}, callback);
};
ReasonLink.prototype.fetchTypes = function(callback) {
  this.fetchItems({type: "typeList", site_id: this._siteId}, callback);
};
ReasonLink.prototype.fetchPages = function(siteId, callback) {
  this.fetchItems({type: "pageList", site_id: siteId}, callback);
};
ReasonLink.prototype.fetchAnchors = function(siteId, pageId, callback) {
  this.fetchItems({type: "anchorList", site_id: siteId, content_type: 'application/xml', url: pageId}, callback);
};

/**
 * Fetches all anchors without hrefs in a given document (the better to link
 * to, my dear!). Uses a div (detached from DOM). If things got really slow
 * we could recycle this div, but that's premature at this point.
 * @param {String} response html of document from XHR.
 **/
ReasonLink.prototype.scrapeForAnchors = function(response) {
  var doc, divContent, possibleAnchors, results, docFrag;

  results = [{name: "(No anchor)", url: "#"}];
  docFrag = document.createDocumentFragment();
  div = docFrag.appendChild(document.createElement("div"));
  div.innerHTML = response;

  possibleAnchors = div.getElementsByTagName('A');
  for (var i = 0; i < possibleAnchors.length; i++) {
    var curA = possibleAnchors[i];
    if ( !curA.href && (curA.id || curA.name) )
      results.push({name: curA.id || curA.name, url: '#' + (curA.id || curA.name)});
  }

  return results;
};

/**
 * Adds items of `type` to the corresponding storage in the plugin object.
 *
 * @param {String} response the result of an XHR.
 **/
ReasonLink.prototype.parseItems = function (type, response) {
  var result;
  switch (type) {
    case "anchorList":
      result = this.scrapeForAnchors(response);
      this.setAnchors(result);
      break;
    case "pageList":
      result = JSON.parse(response);
      this.setPages([{treeName: "(Select a page)", url: "0"}].concat(this.flattenTree(result, 0)));
      break;
    case "siteList":
      result = JSON.parse(response);
      this.setSites([{treeName: "(Select a site)", url: "0"}].concat(result.sites));
      break;
  }
};

/**
 * In the case of an error we run parseItems with an empty json result.
 */
ReasonLink.prototype.fetchItems = function(options, callback) {
  var url = options.url || this.jsonURL(options);

  tinymce.util.XHR.send({
    "url": url,
    "content_type": options.content_type || '',
    "success": function(response, xhr) {
    	this.parseItems(options.type, response);
    	callback.call(this);
    	this.loaded(options.type);
    },
    "error" : function(type, req, o ) {
    	this.parseItems(options.type, {});
    	callback.call(this);
    	this.loaded(options.type);
    },
    "success_scope": this,
    "error_scope": this
  });
};

ReasonLink.prototype.flattenTree = function(tree, depth) {
  var returnArray = [];

  tree.depth = depth;
  tree.treeName = this.treeName(tree);

  if (!tree.pages) {
    return [tree];
  } else {
    for (var i = 0; i < tree.pages.length; i++) {
      returnArray = returnArray.concat(this.flattenTree(tree.pages[i], depth+1));
    }
    delete tree.pages;
    returnArray.unshift(tree);
    return returnArray;
  }
};

ReasonLink.prototype.treeName = function(page) {
  var prefix = "";
    for (var i=0; i <= page.depth; i++) {
        prefix += "—";
    }
    return (prefix + " " + page.name);
};

ReasonLink.prototype.updateValues = function(items, selectedItem) {
  var values = [];
  for (var i=0; i < items.length; i++) {
    var value = items[i].url || items[i].id;
    
    // lets decode entities into raw UTF-8
    var text = items[i].treeName || items[i].name;
    text = tinymce.html.Entities.decode(text);
    
    values.push({text: text, value: value, selected:(value == selectedItem)});
  }
  return values;
};

ReasonLink.prototype.bindReasonUI = function () {
  var self = this;

  this.hrefControls[0].on('change', function() {
    self.setSelected({});
    self.updateForm();
  });

  this.emailControl.on('change', function() {
    self.setHref("mailto:" + self.emailControl.value());
    self.setSelected({});
    self.initControlVals();
    self.updateForm();
  });

  this.descriptionControls[0].on('change', function() {
    self.setDesc(self.descriptionControls[0].value());
  });
  this.descriptionControls[1].on('change', function() {
    self.setDesc(self.descriptionControls[1].value());
  });
  this.descriptionControls[2].on('change', function() {
    self.setDesc(self.descriptionControls[2].value());
  });

  this.formControls.Anchors.on('select', function(e) {
    self.setSelected('anchor', e.control.value());
    self.setHref(self.makeURL());
  });
  this.formControls.Pages.on('select', function(e) {
    if (e.control.value() == "0")
      return;
    self.setSelected('page', e.control.value());
    self.setHref(self.makeURL());
    self.setDesc(e.control.text().replace(/\u2014* /, ""));
    self.startThrobber();
    self.fetchAnchors(self._siteId, e.control.value(), function() {
      self.stopThrobber();
      self.setDisabled('anchors', false);
      self.updateForm();
    });
  });
  this.formControls.Sites.on('select', function(e) {
    if (e.control.value() == "0")
      return;
    self.emailControl.value('');
    self.setHref('');
    self.setSelected('site', e.control.value());
	self.startThrobber();
    self.fetchPages(e.control.value(), function() {
      self.stopThrobber();
      self.setDisabled('pages', false);
      self.updateForm();
    });
  });
};

ReasonLink.prototype.initControlVals = function() {
  this.setDisabled('pages', true);
  this.setDisabled('anchors', true);
  this.setPages([{name: "(Select a page)", url: "0"}]);
  this.setAnchors([{name: "(No anchor)", url: "0"}]);  
};

ReasonLink.prototype.makeURL = function () {
  var page = this.getSelected().page, anchor = this.getSelected().anchor || "";
  return "//" + window.location.hostname + page + anchor;
};

ReasonLink.prototype.constructSites = function() {
  var sites = this.getSites(),
      selected = this.getSelected().site;
  return {
    name: "sites",
    label: "Site",
    type: "listbox",
    flex: 1,
    values: this.updateValues(sites, selected),
    disabled: !!this.getDisabled().sites
  };
};

ReasonLink.prototype.constructTypes = function() {
  var types = this.getTypes(),
      selected = this.getSelected().type;

  return {
    name: "types",
    label: "Type",
    type: "listbox",
    flex: 1,
    values: this.updateValues(types, selected),
    disabled: !!this.getDisabled().types
  };
};

ReasonLink.prototype.constructPages = function() {
  var pages = this.getPages();
  var selected = this.getSelected().page;

  return {
    name: "pages",
    label: "Page",
    type: "listbox",
    flex: 1,
    values: this.updateValues(pages, selected),
    disabled: !!this.getDisabled().pages
  };
};

ReasonLink.prototype.constructAnchors = function() {
  var anchors = this.getAnchors();
  var selected = this.getSelected().anchor;

  return {
    name: "anchors",
    label: "Anchor",
    type: "listbox",
    flex: 1,
    values: this.updateValues(anchors),
    disabled: !!this.getDisabled().anchors
  };
};

ReasonLink.prototype.constructDescription = function() {
  var currentDesc = this.getDesc();

  return {
    name: 'title_3',
    type: 'textbox',
    size: 40,
    flex: 1,
    label: 'Description',
    value: currentDesc
  };
};

ReasonLink.prototype.constructFormObj = function() {
  var formObj = {
    type: "form",
    name: "reasonLinkForm",
    items: [
      this.constructSites(),
      //this.constructTypes(),
      this.constructPages(),
      this.constructAnchors(),
      this.constructDescription()
    ]
  };
  window.formObj = formObj;
  return formObj;
};

ReasonLink.prototype.startThrobber = function() {
	throbber = this.getThrobber();
	throbber.show();
}

ReasonLink.prototype.stopThrobber = function() {
	throbber = this.getThrobber();
	throbber.hide();
}

ReasonLink.prototype.getThrobber = function() {
	if (!this._throbber)
	{
		this._throbber = new tinymce.ui.Throbber(this.targetPanel.getEl());
	}
  	return this._throbber;
}

ReasonLink.prototype.insertReasonUI = function() {
  var self = this;
  this.startThrobber();
  this.fetchSites(function() {
  	self.stopThrobber();
    self.addFormObj(self.constructFormObj());
    self.bindReasonUI();
  });
};

ReasonLink.prototype.addFormObj = function (obj) {
  var newForm = this.targetPanel.append(obj).items()[0];
  this.targetPanel.renderTo().reflow().postRender();
  this.setFormControl(newForm);
  this.getControlReferences();
  this.saveLayoutRect();
  return newForm;
};

ReasonLink.prototype.saveLayoutRect = function() {
  this.layoutRects = tinymce.map(this.formControls, function(v) {
    return [v.layoutRect(), v.parent().layoutRect()];
  });
};


ReasonLink.prototype.updateForm = function() {
  var i = 0;
  tinymce.each(this.formControls, function(v, k) {
    var methodName = "construct" + k;
    v.before(tinymce.ui.Factory.create(this[methodName]())).remove();
  }, this);
  this.getControlReferences()
  tinymce.each(this.formControls, function(v) {
    v.parent().layoutRect(this.layoutRects[i][1]);
    v.layoutRect(this.layoutRects[i][0]);
    v.parent().reflow();
    v.reflow();
    i++;
  }, this);

  this.bindReasonUI();
};



/**
 * This is the actual tinyMCE plugin.
 */



tinymce.PluginManager.add('reasonintegration', function(editor, url) {

  function showImageDialog() {
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
              {text: 'Thumbnail', value: 'thumbnail'},
              {text: 'Full', value: 'full'}
            ]},
            {name: 'align_2', type: 'listbox', label: "Align", values: [
              {text: 'None', value: 'none'},
              {text: 'Left', value: 'left'},
              {text: 'Right', value: 'right'}
            ]}
          ]
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
        	reasonImagePlugin.setAlt(tinymce.DOM.getAttrib(imgElm, 'alt'));
        	reasonImagePlugin.setSrc(tinymce.DOM.getAttrib(imgElm, 'src'));
        	reasonImagePlugin.whenLoaded(function()
        	{
        		this.selectImage(tinymce.DOM.getAttrib(imgElm, 'src'));
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

/***************************************************
 * REASONLINK
 ***************************************************/
  function showLinkDialog() {
		var data = {}, selection = editor.selection, dom = editor.dom, selectedElm, anchorElm, initialText;
		var win, linkListCtrl, relListCtrl, targetListCtrl;

		selectedElm = selection.getNode();
		anchorElm = dom.getParent(selectedElm, 'a[href]');
		if (anchorElm) {
			selection.select(anchorElm);
		}

		data.text = initialText = selection.getContent({format: 'text'});
		data.href = anchorElm ? dom.getAttrib(anchorElm, 'href') : '';
		data.target = anchorElm ? dom.getAttrib(anchorElm, 'target') : '';
		data.rel = anchorElm ? dom.getAttrib(anchorElm, 'rel') : '';
		
		if ((/^mailto:/).test(data.href))
		{
			data.title = anchorElm ? dom.getAttrib(anchorElm, 'title') : '';
			data.email = data.href.slice(7);
		}
		data.title_2 = anchorElm ? dom.getAttrib(anchorElm, 'title') : '';
		
		if (selectedElm.nodeName == "IMG") {
			data.text = initialText = " ";
		}

    if (!data.text) {
      tinymce.activeEditor.windowManager.alert({title: "Insert a link", text: "You must first select some text in order to insert a link."});
      return;
    }


    win = editor.windowManager.open({
      title: 'Create a link',
      data: data,
      name: 'reasonLinkWindow',
      body: [
        // Add from Reason
        {
          title: "an existing item",
          name: "reasonLinkPanel",
          type: "form",
          minWidth: "700",
          minHeight: "375",
          items: []
        },

        // Add from the Web
        {
          title: "a web address",
          type: "form",
          items: [{
            name: 'href',
            type: 'textbox',
            filetype: 'image',
            size: 40,
            autofocus: true,
            label: 'Destination web address'
          }, {
            name: 'title_2',
            type: 'textbox',
            size: 40,
            label: 'Description'
          }]
        },
        {
          title: "an email address",
          type: "form",
          items: [{
            name: 'email',
            type: 'textbox',
            filetype: 'image',
            size: 40,
            autofocus: true,
            label: 'Email address'
          }, {
            name: 'title',
            type: 'textbox',
            size: 40,
            label: 'Description'
          }]
        }
      ],
      bodyType: 'tabpanel',
      onPostRender: function(e) {
        var target_panel = 'reasonLinkPanel',
            controls_to_bind = {
              tabPanel: "reasonLinkWindow",
              href: ["href"],
              description: ["title", "title_2"],
              email: "email"
            };
        reasonLinkPlugin = new ReasonLink(controls_to_bind, target_panel, 'link', e);
      },
      onSubmit: function(e) {
       var data = win.toJSON(), href = data.href, title = data.title;
				function insertLink() {
						editor.execCommand('mceInsertLink', false, {
							href: href,
							//target: data.target,
              title: title,
							rel: data.rel ? data.rel : null
						});
				}

				if (!href) {
					editor.execCommand('unlink');
					return;
				}

				insertLink();
      }
    });
  }


/***************************************************
 * Buttons
 ***************************************************/
	editor.addMenuItem('reasonlink', {
		icon: 'link',
		text: 'Insert link',
		onclick: showLinkDialog,
		context: 'insert',
		prependToContext: true
	});

	editor.addButton('reasonlink', {
		icon: 'link',
		tooltip: 'Insert link',
		onclick: showLinkDialog,
		stateSelector: 'a[href]'
	});


  editor.addButton('reasonimage', {
    icon: 'image',
    tooltip: 'Insert/edit image',
    onclick: showImageDialog,
    stateSelector: 'img:not([data-mce-object])'
  });

  editor.addMenuItem('reasonimage', {
    icon: 'image',
    text: 'Insert image',
    onclick: showImageDialog,
    context: 'insert',
    prependToContext: true
  });
});
