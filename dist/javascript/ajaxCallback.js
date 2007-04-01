// A plugin that wraps all ajax calls introducing a fixed callback function on ajax complete
if(!jQuery.load_handlers) {
	jQuery.load_handlers = new Array();
	//
	// Add a function to the list of those to be executed on ajax load complete
	//
	function onAjaxLoad(f) {
		jQuery.load_handlers.push(f);
	};
	
	//
	// Call the functions that have been added to onAjaxLoad
	//
	function triggerAjaxLoad(root) {
		for ( var i = 0; i < jQuery.load_handlers.length; i++ )
			jQuery.load_handlers[i].apply( root );
	};
	
	jQuery.fn._load = jQuery.fn.load;
	
	jQuery.fn.load = function( url, params, callback, ifModified ) {
	
		callback = callback || function(){};
	
		// If the second parameter was provided
		if ( params ) {
			// If it's a function
			if ( params.constructor == Function ) {
				// We assume that it's the callback
				callback = params;
				params = null;
			} 
		}
		var callback2 = function(res,status) {triggerAjaxLoad(this);callback(res,status);};
		
		return this._load( url, params, callback2, ifModified );
	};

	jQuery._ajax = jQuery.ajax;
	
	jQuery.ajax = function(type) {
	  
	  //If called by _load exit now because the callback has already been set
    if (jQuery.ajax.caller==jQuery.fn._load) return jQuery._ajax( type);
		
    var orig_complete = type.complete || function() {}; 
    type.complete = function(res,status) {
      //Do not fire OnAjaxLoad if the dataType is not html
      var dataType = type.dataType;
			var ct = res.getResponseHeader("content-type");
			var xml = !dataType && ct && ct.indexOf("xml") >= 0;
			if(dataType == "" && !xml || dataType == "html") triggerAjaxLoad(document);
			orig_complete(res,status);
		};
	
	  return jQuery._ajax(type); 
	
	};

}
