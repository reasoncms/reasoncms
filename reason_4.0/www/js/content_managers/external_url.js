function getOptions(item){
	var optionNodes = item.children;
	var options = [];
	for(var x =0; x<optionNodes.length; x+=1){
		var key = optionNodes[x].nodeName;
		if(key.indexOf(":")!= -1){
			key = key.split(":")[0];
		}
		key = key.toLowerCase();
		options.push(key);
	}
	return options;

}

function displayFeed(){
	for(x=0;x<rssItems.length;x+=1){
		var itemDiv = document.createElement("DIV");
		itemDiv.className = "rssPreviewItem";
		if(x==0){
			itemDiv.className += " top";
		}
		var item = rssItems[x];
		var keys = Object.keys(item);
		for(y=0;y<keys.length;y+=1){
			key = keys[y];
			keyDiv = document.createElement("DIV");
			keyDiv.className = "rssKeySec";
			if(key=="title"){
				var keyValue = document.createElement("H3");
			}
			else{
				var keyValue = document.createElement("P");
			}
			keyValue.className += " rssKeyValue";
			var keyName = document.createElement("P");
			keyName.className = "rssKeyName";
			if(key=="author"){
				keyValue.className += "rssPreviewAuthor";
			}
			console.log(typeof item[key]);
			if(typeof item[key] == 'object'){
				keyValueText = document.createTextNode("Object/Null");
			}
			else{
				keyValueText = document.createTextNode(item[key]);
			}
			keyNameText = document.createTextNode(key);

			keyValue.appendChild(keyValueText);
			keyName.appendChild(keyNameText);

			keyDiv.appendChild(keyName);
			keyDiv.appendChild(keyValue);

			itemDiv.appendChild(keyDiv);
		}
		$('#previewElement').append(itemDiv);
	}
}

$(document).ready(function() {
	var url = $("#urlElement")[0].value;
	if(url != undefined && url != ""){
		$('<tr valign="top" id="previewRow"><td align="right" class="words"><span class="labelText">Preview:</span></td><td align="left" class="element"><div id="previewElement"></div></td></tr>').insertAfter($('#urlRow'));
		displayFeed();
	}
	else{
		console.log(url);
		$("#fieldtitleRow").hide();
		$("#fieldwordsRow").hide();
	}

});
