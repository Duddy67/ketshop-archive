// As a namespace we just create a new object that contains all the classes.
var Omkod = {};


Omkod.Ajax = class {
  constructor() {
    // Initializes the XMLHttpRequest object.
    this.xhr = new XMLHttpRequest();
    this.params = '';
  }

  /**
   * Prepares the Ajax request with the given parameters and data.
   *
   * @param   object   params    The parameters for the Ajax request.
   * @param   object   data      The data for the Ajax request.
   * @return  void
  */
  prepare(params, data) {
    this.params = this.setParameters(params);
    // Sets the url and variables according to the given data.
    let url = this.params.url;
    let vars = null;

    if(data !== undefined) {
      vars = this.getVarsFromData(data);
      // Adds the variables (query) to the given url.
      if(this.params.method == 'GET') {
	vars = '?'+vars;
	// Checks whether the interrogation mark (ie: a query) is contained in url.
	let regex = /\?/;
	if(regex.test(this.params.url)) {
	  // Adds the variables after the query already existing. 
	  vars = vars.replace('?', '&');
	}

	url = url+vars;
      }
    }

    if(this.params.indicateFormat) {
      url = url+'&format='+this.params.dataType;
    }

    // Initializes the newly-created request.
    this.xhr.open(this.params.method, url, this.params.async);

    // Forces the MIME Type according to the given dataType.
    if(this.params.dataType == 'json') {
      this.xhr.overrideMimeType('application/json');
    }
    else if(this.params.dataType == 'xml') {
      this.xhr.overrideMimeType('text/xml');
    }
    else {
      this.xhr.overrideMimeType('text/plain');
    }

    if(this.params.method == 'POST') {
      // Send the proper header information along with the request.
      this.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      this.xhr.send(vars);
    }
    else {
      // Always null with GET method.
      this.xhr.send(null);
    }
  }

  /**
   * Runs the EventHandler that is called whenever the readyState attribute changes. Calls the given callback 
   * function when the Ajax request has succeed. 
   *
   * @param   string   callback  The name of the callback function to call when the Ajax request has succeed.
   * @return  void/boolean       Returns false whether the Ajax request or the JSON parsing fail. Void otherwise. 
  */
  process(callback) {
    const xhrRef = this.xhr; // Storing reference.
    let params = this.params; 

    xhrRef.onreadystatechange = function() {
      // Checks for error.
      if(xhrRef.status !== 200) {
	alert(xhrRef.status + ': ' + xhrRef.statusText);
	return false;
      }
      else if(xhrRef.readyState === 4 && xhrRef.status === 200) {
	// By default returns response as plain text.
	let response = xhrRef.responseText;

	// Formats response according to the given dataType.
	if(params.dataType == 'json') {
	  try {
	    response = JSON.parse(xhrRef.responseText);
	  }
	  catch(e) {
	    alert('Parsing error: '+e);
	    return false;
	  }
	}
	else if(params.dataType == 'xml') {
	  response = xhrRef.responseXML;
	}

	// To get header information in debugging mode.
	//alert(xhrRef.getAllResponseHeaders());

	// Calls the given callback function.
	callback(response);
      }
    }
  }

  /**
   * Checks the given parameters for the current Ajax request and modified them if needed.
   *
   * @param   object   params    The parameters for the Ajax request.
   * @return  object             The parameters (possibly modified) for the Ajax request.
  */
  setParameters(params) {
    if(params.method === undefined || (params.method != 'GET' && params.method != 'POST')) {
      params.method = 'GET';
    }

    if(params.dataType === undefined || (params.dataType != 'json' && params.dataType != 'xml' && params.dataType != 'text')) {
      params.dataType = 'text';
    }

    if(params.async === undefined) {
      params.async = true;
    }

    if(params.url === undefined) {
      // Uses the current url.
      params.url = window.location.href;
    }

    if(params.indicateFormat === undefined) {
      params.indicateFormat = false;
    }

    return params;
  }

  /**
   * Turns the given data for the current Ajax request into an encoded variable string.
   *
   * @param   object   data      The data for the Ajax request.
   * @return  string             The data as an encoded variable string for the Ajax request.
  */
  getVarsFromData(data) {
    let vars = '';
    // Loops through the given data object.
    for(var key in data) {
      // Checks for arrays.
      if(Array.isArray(data[key])) {
	for(var i = 0; i < data[key].length; i++) {
	  // Encodes the array values.
	  vars += key+'='+encodeURIComponent(data[key][i])+'&';
	}
      }
      else {
	// Encodes the data values.
	vars += key+'='+encodeURIComponent(data[key])+'&';
      }
    }

    // Removes the & character from the end of the string.
    vars = vars.slice(0, -1);

    return vars;
  }
}

