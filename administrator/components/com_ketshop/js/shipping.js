
(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};
  // The dynamic items to create. {item name:nb of cells}
  const items = {'postcode':4, 'city':3, 'region':3, 'country':3, 'continent':3};

  // Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    // The input element containing the root location.
    let rootLocation = $('#root-location').val();

    // Loops through the item array to instanciate all of the dynamic item objects.
    for(let key in items) {
      // Sets the dynamic item properties.
      let props = {'component':'ketshop', 'item':key, 'ordering':false, 'rootLocation':rootLocation, 'rowsCells':[items[key]], 'Chosen':true, 'nbItemsPerPage':5};
      // Stores the newly created object.
      GETTER[key]= new Omkod.DynamicItem(props);
    }

    let shippingId = $('#jform_id').val();
    let deliveryType = $('#jform_delivery_type').val();

    // Prepares then run the Ajax query.
    const ajax = new Omkod.Ajax();
    let params = {'method':'GET', 'dataType':'json', 'indicateFormat':true, 'async':true};
    // Gets the form security token.
    let token = jQuery('#token').attr('name');
    // N.B: Invokes first the ajax() function in the global controller to check the token.
    let data = {[token]:1, 'task':'ajax', 'shipping_id':shippingId, 'item_type':deliveryType};
    ajax.prepare(params, data);
    ajax.process(getAjaxResult);

    // Binds the delivery type select tag to the corresponding function. 
    $('#jform_delivery_type').change( function() { $.fn.switchDeliveryType($('#jform_delivery_type').val()); });
    $.fn.switchDeliveryType(deliveryType);
  });

  getAjaxResult = function(result) {
    if(result.success === true) {
      // Sets the items according to the delivery type.
      if($('#jform_delivery_type').val() == 'at_delivery_point') {
	$.fn.setAddress(result.data);
      }
      // at_destination
      else {
	// Loops through the item array to create all of the dynamic items.
	for(let key in items) {
	  $.each(result.data[key], function(i, item) { GETTER[key].createItem(item); });
	}
      }
    }
    else {
      alert('Error: '+result.message);
    }
  }

  validateFields = function(e) {
    let task = document.getElementsByName('task');
    let fields = {'attribute-name':''}; 

    if(task[0].value != 'filter.cancel' && !GETTER.attribute.validateFields(fields)) {
      // Shows the dynamic item tab.
      $('.nav-tabs a[href="#attributes"]').tab('show');

      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }

  $.fn.setAddress = function(data) {
    $('#jform_street').val(data.street);
    $('#jform_city').val(data.city);
    $('#jform_postcode').val(data.postcode);
    $('#jform_region_code').val(data.region_code);
    $('#jform_region_code').trigger('liszt:updated');
    $('#jform_country_code').val(data.country_code);
    $('#jform_country_code').trigger('liszt:updated');
    $('#jform_phone').val(data.phone);
  }

  $.fn.switchDeliveryType = function(deliveryType) {
    if(deliveryType == 'at_delivery_point') {
      $('#address').css({'visibility':'visible','display':'block'});
      $('#postcode').css({'visibility':'hidden','display':'none'});
      $('a[href="#postcode-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#city').css({'visibility':'hidden','display':'none'});
      $('a[href="#city-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#region').css({'visibility':'hidden','display':'none'});
      $('a[href="#region-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#country').css({'visibility':'hidden','display':'none'});
      $('a[href="#country-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#continent').css({'visibility':'hidden','display':'none'});
      $('a[href="#continent-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#jform_global_cost').parent().parent().css({'visibility':'hidden','display':'none'}); 
      $('#jform_delivpnt_cost').parent().parent().css({'visibility':'visible','display':'block'}); 
    // at_destination
    } else { 
      $('#address').css({'visibility':'hidden','display':'none'});
      $('#postcode').css({'visibility':'visible','display':'block'});
      $('a[href="#postcode-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#city').css({'visibility':'visible','display':'block'});
      $('a[href="#city-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#region').css({'visibility':'visible','display':'block'});
      $('a[href="#region-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#country').css({'visibility':'visible','display':'block'});
      $('a[href="#country-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#continent').css({'visibility':'visible','display':'block'});
      $('a[href="#continent-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#jform_global_cost').parent().parent().css({'visibility':'visible','display':'block'}); 
      $('#jform_delivpnt_cost').parent().parent().css({'visibility':'hidden','display':'none'}); 
    }
  }

  /** Callback functions **/

  populatePostcodeItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'from':'', 'to':'', 'cost':''};
    }

    // Element label.
    let attribs = {'title':Joomla.JText._('COM_KETSHOP_FROM_POSTCODE_TITLE'), 'class':'item-label', 'id':'postcode-from-label-'+idNb};
    $('#postcode-row-1-cell-1-'+idNb).append(GETTER.postcode.createElement('span', attribs));
    $('#postcode-from-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_FROM_POSTCODE_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'postcode_from_'+idNb, 'id':'postcode-from-'+idNb, 'value':data.from};
    $('#postcode-row-1-cell-1-'+idNb).append(GETTER.postcode.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_TO_POSTCODE_TITLE'), 'class':'item-label', 'id':'postcode-to-label-'+idNb};
    $('#postcode-row-1-cell-2-'+idNb).append(GETTER.postcode.createElement('span', attribs));
    $('#postcode-to-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_TO_POSTCODE_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'postcode_to_'+idNb, 'id':'postcode-to-'+idNb, 'value':data.to};
    $('#postcode-row-1-cell-2-'+idNb).append(GETTER.postcode.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_COST_TITLE'), 'class':'item-label', 'id':'postcode-cost-label-'+idNb};
    $('#postcode-row-1-cell-3-'+idNb).append(GETTER.postcode.createElement('span', attribs));
    $('#postcode-cost-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_COST_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'postcode_cost_'+idNb, 'id':'postcode-cost-'+idNb, 'value':data.cost};
    $('#postcode-row-1-cell-3-'+idNb).append(GETTER.postcode.createElement('input', attribs));
  }

  populateCityItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'name':'', 'cost':''};
    }

    // Element label.
    let attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'city-name-label-'+idNb};
    $('#city-row-1-cell-1-'+idNb).append(GETTER.city.createElement('span', attribs));
    $('#city-name-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'city_name_'+idNb, 'id':'city-name-'+idNb, 'value':data.name};
    $('#city-row-1-cell-1-'+idNb).append(GETTER.city.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_COST_TITLE'), 'class':'item-label', 'id':'city-cost-label-'+idNb};
    $('#city-row-1-cell-2-'+idNb).append(GETTER.city.createElement('span', attribs));
    $('#city-cost-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_COST_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'city_cost_'+idNb, 'id':'city-cost-'+idNb, 'value':data.cost};
    $('#city-row-1-cell-2-'+idNb).append(GETTER.city.createElement('input', attribs));
  }

  populateRegionItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'code':'', 'cost':''};
    }

    // Element label.
    let attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'region-name-label-'+idNb};
    $('#region-row-1-cell-1-'+idNb).append(GETTER.region.createElement('span', attribs));
    $('#region-name-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Gets the region codes and names.
    let regions = ketshop.getRegions();

    // Select tag:
    attribs = {'name':'region_code_'+idNb, 'id':'region-code-'+idNb};
    elem = GETTER.region.createElement('select', attribs);

    // Builds the select options.
    let options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';

    for(let i = 0; i < regions.length; i++) {
      let selected = '';

      if(data.code == regions[i].code) {
	selected = 'selected="selected"';
      }

      options += '<option value="'+regions[i].code+'" '+selected+'>'+regions[i].text+'</option>';
    }

    $('#region-row-1-cell-1-'+idNb).append(elem);
    $('#region-code-'+idNb).html(options);
    // Update the chosen plugin.
    $('#region-code-'+idNb).chosen();

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_COST_TITLE'), 'class':'item-label', 'id':'region-cost-label-'+idNb};
    $('#region-row-1-cell-2-'+idNb).append(GETTER.region.createElement('span', attribs));
    $('#region-cost-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_COST_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'region_cost_'+idNb, 'id':'region-cost-'+idNb, 'value':data.cost};
    $('#region-row-1-cell-2-'+idNb).append(GETTER.region.createElement('input', attribs));
  }

  populateCountryItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'code':'', 'cost':''};
    }

    // Element label.
    let attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'country-name-label-'+idNb};
    $('#country-row-1-cell-1-'+idNb).append(GETTER.country.createElement('span', attribs));
    $('#country-name-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Gets the country codes and names.
    let countries = ketshop.getCountries();

    // Select tag:
    attribs = {'name':'country_code_'+idNb, 'id':'country-code-'+idNb};
    elem = GETTER.country.createElement('select', attribs);

    // Builds the select options.
    let options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';

    for(let i = 0; i < countries.length; i++) {
      let selected = '';

      if(data.code == countries[i].code) {
	selected = 'selected="selected"';
      }

      options += '<option value="'+countries[i].code+'" '+selected+'>'+countries[i].text+'</option>';
    }

    $('#country-row-1-cell-1-'+idNb).append(elem);
    $('#country-code-'+idNb).html(options);
    // Update the chosen plugin.
    $('#country-code-'+idNb).chosen();

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_COST_TITLE'), 'class':'item-label', 'id':'country-cost-label-'+idNb};
    $('#country-row-1-cell-2-'+idNb).append(GETTER.country.createElement('span', attribs));
    $('#country-cost-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_COST_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'country_cost_'+idNb, 'id':'country-cost-'+idNb, 'value':data.cost};
    $('#country-row-1-cell-2-'+idNb).append(GETTER.country.createElement('input', attribs));
  }

  populateContinentItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'code':'', 'cost':''};
    }

    // Element label.
    let attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'continent-name-label-'+idNb};
    $('#continent-row-1-cell-1-'+idNb).append(GETTER.country.createElement('span', attribs));
    $('#continent-name-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Gets the continent codes and names.
    let continents = ketshop.getContinents();

    // Select tag:
    attribs = {'name':'continent_code_'+idNb, 'id':'continent-code-'+idNb};
    let elem = GETTER.continent.createElement('select', attribs);

    // Builds the select options.
    let options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';

    for(let i = 0; i < continents.length; i++) {
      let selected = '';

      if(data.code == continents[i].code) {
	selected = 'selected="selected"';
      }

      options += '<option value="'+continents[i].code+'" '+selected+'>'+continents[i].text+'</option>';
    }

    $('#continent-row-1-cell-1-'+idNb).append(elem);
    $('#continent-code-'+idNb).html(options);
    // Update the chosen plugin.
    $('#continent-code-'+idNb).chosen();

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_COST_TITLE'), 'class':'item-label', 'id':'continent-cost-label-'+idNb};
    $('#continent-row-1-cell-2-'+idNb).append(GETTER.continent.createElement('span', attribs));
    $('#continent-cost-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_COST_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'continent_cost_'+idNb, 'id':'continent-cost-'+idNb, 'value':data.cost};
    $('#continent-row-1-cell-2-'+idNb).append(GETTER.continent.createElement('input', attribs));
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

})(jQuery);
/*(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Create a container for each item type.
    $('#postcode').getContainer();
    $('#city').getContainer();
    $('#region').getContainer();
    $('#country').getContainer();
    $('#continent').getContainer();

    //Get the current delivery type.
    var deliveryType = $('#jform_delivery_type').val();
    //Get the shipping item id.
    var shippingId = $('#jform_id').val();
    //If the shipping item exists we need to get the data of the dynamical items.
    if(shippingId != 0) {
      //Restore the form to its original state filled with the initial data 
      //in case the user changes some value then press the F5 key.
      //It also allows to get the delivery type directly from the form tag.
      $('#shipping-form')[0].reset();
      deliveryType = $('#jform_delivery_type').val();

      //Gets the token's name as value.
      var token = $('#token').attr('name');
      //Sets up the ajax query.
      var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'shipping_id':shippingId, 'item_type':'at_destination'};

      if(deliveryType == 'at_delivery_point') {
	urlQuery.item_type = 'deliverypoint';
      } 

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    if(deliveryType == 'at_delivery_point') {
	      $.fn.setAddress(results.data);
	    } else {
	      $.each(results.data.postcode, function(i, result) { $.fn.createItem('postcode', result); });
	      $.each(results.data.city, function(i, result) { $.fn.createItem('city', result); });
	      $.each(results.data.region, function(i, result) { $.fn.createItem('region', result); });
	      $.each(results.data.country, function(i, result) { $.fn.createItem('country', result); });
	      $.each(results.data.continent, function(i, result) { $.fn.createItem('continent', result); });
	    }
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

    //Bind the delivery type select tag to the switchDelivery function. 
    $('#jform_delivery_type').change( function() { $.fn.switchDelivery($('#jform_delivery_type').val()); });
    $.fn.switchDelivery(deliveryType);

  });


  $.fn.createCommonElements = function(type, idNb, data) {
    var properties = '';
    if(type != 'postcode' && type != 'deliverypoint') {
      //Create the "name" label.
      properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
      $('#'+type+'-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
      $('#'+type+'-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));
      //Move the label span to the top of the item div. 
      $('#'+type+'-item-'+idNb+' .item-name-label').prependTo('#'+type+'-item-'+idNb);
    }

    //Create the cost label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_COST_TITLE')};
    $('#'+type+'-item-'+idNb).createHTMLTag('<span>', properties, 'item-cost-label');
    $('#'+type+'-item-'+idNb+' .item-cost-label').text(Joomla.JText._('COM_KETSHOP_ITEM_COST_LABEL'));
    //Create the cost input.
    //First we need to format cost data if any.
    if(data.cost !== '') {
      data.cost = parseFloat(data.cost).toFixed(2);
    }
    properties = {'type':'text', 'name':type+'_cost_'+idNb, 'value':data.cost};
    $('#'+type+'-item-'+idNb).createHTMLTag('<input>', properties, 'cost-item');
    //Create the item removal button.
    $('#'+type+'-item-'+idNb).createButton('remove');
  };

  //Functions which create a specific item type.

  $.fn.createPostcodeItem = function(idNb, data) {
    //Create the "from" label.
    var properties = {'title':Joomla.JText._('COM_KETSHOP_FROM_POSTCODE_TITLE')};
    $('#postcode-item-'+idNb).createHTMLTag('<span>', properties, 'from-postcode-label');
    $('#postcode-item-'+idNb+' .from-postcode-label').text(Joomla.JText._('COM_KETSHOP_FROM_POSTCODE_LABEL'));
    //Create the "from" input.
    properties = {'type':'text', 'name':'postcode_from_'+idNb, 'value':data.from};
    $('#postcode-item-'+idNb).createHTMLTag('<input>', properties, 'from-postcode');
    //Create the "to" label.
    var properties = {'title':Joomla.JText._('COM_KETSHOP_TO_POSTCODE_TITLE')};
    $('#postcode-item-'+idNb).createHTMLTag('<span>', properties, 'to-postcode-label');
    $('#postcode-item-'+idNb+' .to-postcode-label').text(Joomla.JText._('COM_KETSHOP_TO_POSTCODE_LABEL'));
    //Create the "to" input.
    properties = {'type':'text', 'name':'postcode_to_'+idNb, 'value':data.to};
    $('#postcode-item-'+idNb).createHTMLTag('<input>', properties, 'to-postcode');

    $.fn.createCommonElements('postcode', idNb, data);
  };


  $.fn.createCityItem = function(idNb, data) {
    //Create the "name" input.
    var properties = {'type':'text', 'name':'city_name_'+idNb, 'value':data.name};
    $('#city-item-'+idNb).createHTMLTag('<input>', properties, 'city-name-item');

    $.fn.createCommonElements('city', idNb, data);
  };


  $.fn.createRegionItem = function(idNb, data) {
    //Create the "name" input.
    var properties = {'name':'region_code_'+idNb, 'id':'region-code-'+idNb};
    $('#region-item-'+idNb).createHTMLTag('<select>', properties, 'region-select');

    //Get the region codes and names.
    var regions = ketshop.getRegions();
    var length = regions.length;
    var options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';
    //Create an option tag for each region.
    for(var i = 0; i < length; i++) {
      options += '<option value="'+regions[i].code+'">'+regions[i].text+'</option>';
    }

    //Add the region options to the select tag.
    $('#region-code-'+idNb).html(options);

    if(data.code !== '') {
      //Set the selected option.
      $('#region-code-'+idNb+' option[value="'+data.code+'"]').attr('selected', true);
    }

    //Use Chosen jQuery plugin.
    $.fn.updateChosen('region-code-'+idNb);

    $.fn.createCommonElements('region', idNb, data);
  };


  $.fn.createCountryItem = function(idNb, data) {
    //Create a country select tag.
    var properties = {'name':'country_code_'+idNb, 'id':'country-code-'+idNb};
    $('#country-item-'+idNb).createHTMLTag('<select>', properties, 'country-select');

    //Get the country codes and names.
    var countries = ketshop.getCountries();
    var length = countries.length;
    var options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';
    //Create an option tag for each country.
    for(var i = 0; i < length; i++) {
      options += '<option value="'+countries[i].code+'">'+countries[i].text+'</option>';
    }

    //Add the country options to the select tag.
    $('#country-code-'+idNb).html(options);

    if(data.code !== '') {
      //Set the selected option.
      $('#country-code-'+idNb+' option[value="'+data.code+'"]').attr('selected', true);
    }

    //Use Chosen jQuery plugin.
    $.fn.updateChosen('country-code-'+idNb);

    $.fn.createCommonElements('country', idNb, data);
  };


  //Same thing for continent items.
  $.fn.createContinentItem = function(idNb, data) {
    var properties = {'name':'continent_code_'+idNb, 'id':'continent-id-'+idNb};
    $('#continent-item-'+idNb).createHTMLTag('<select>', properties, 'continent-select');

    var continents = ketshop.getContinents();
    var length = continents.length;
    var options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';
    for(var i = 0; i < length; i++) {
      options += '<option value="'+continents[i].code+'">'+continents[i].text+'</option>';
    }

    $('#continent-id-'+idNb).html(options);

    if(data.code !== '') {
      //Set the selected option.
      $('#continent-id-'+idNb+' option[value="'+data.code+'"]').attr('selected', true);
    }

    //Use Chosen jQuery plugin.
    $.fn.updateChosen('continent-id-'+idNb);

    $.fn.createCommonElements('continent', idNb, data);
  };


  $.fn.setAddress = function(data) {
    $('#jform_street').val(data.street);
    $('#jform_city').val(data.city);
    $('#jform_postcode').val(data.postcode);
    $('#jform_region_code').val(data.region_code);
    $('#jform_region_code').trigger('liszt:updated');
    $('#jform_country_code').val(data.country_code);
    $('#jform_country_code').trigger('liszt:updated');
    $('#jform_phone').val(data.phone);
  };


  //Switch the item types according to the delivery type.
  $.fn.switchDelivery = function(deliveryType) {
    if(deliveryType == 'at_delivery_point') {
      $('#address').css({'visibility':'visible','display':'block'});
      $('a[href="#address-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#postcode').css({'visibility':'hidden','display':'none'});
      $('a[href="#postcode-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#city').css({'visibility':'hidden','display':'none'});
      $('a[href="#city-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#region').css({'visibility':'hidden','display':'none'});
      $('a[href="#region-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#country').css({'visibility':'hidden','display':'none'});
      $('a[href="#country-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#continent').css({'visibility':'hidden','display':'none'});
      $('a[href="#continent-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#globalcost').css({'visibility':'hidden','display':'none'});
      $('a[href="#globalcost-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#jform_delivpnt_cost').parent().parent().css({'visibility':'visible','display':'block'}); 
    } else { //at_destination
      $('#address').css({'visibility':'hidden','display':'none'});
      $('a[href="#address-title"]').parent().css({'visibility':'hidden','display':'none'});
      $('#postcode').css({'visibility':'visible','display':'block'});
      $('a[href="#postcode-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#city').css({'visibility':'visible','display':'block'});
      $('a[href="#city-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#region').css({'visibility':'visible','display':'block'});
      $('a[href="#region-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#country').css({'visibility':'visible','display':'block'});
      $('a[href="#country-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#continent').css({'visibility':'visible','display':'block'});
      $('a[href="#continent-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#globalcost').css({'visibility':'visible','display':'block'});
      $('a[href="#globalcost-title"]').parent().css({'visibility':'visible','display':'block'});
      $('#jform_delivpnt_cost').parent().parent().css({'visibility':'hidden','display':'none'}); 
    }
  };

  $.fn.updateChosen = function(id) {
    $('#'+id).trigger('liszt:updated');
    $('#'+id).chosen();
    //Some css attributes have to be modified or the list won't show in the drop down list.
    //FIXIT: Scrolling doesn't work. 
    $('.chzn-container .chzn-drop').css('overflow-y', 'auto');
    $('.chzn-container .chzn-drop').css('overflow-x', 'hidden');
    $('.chzn-container .chzn-results').css('overflow-x', 'visible');
    $('.chzn-container .chzn-results').css('overflow-y', 'visible');
    $('.chzn-search').css('background-color', 'white');
  };

})(jQuery);*/

