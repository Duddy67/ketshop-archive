
(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};
  // The dynamic items to create. {item name:nb of cells}
  const items = {'postcode':4, 'city':3, 'region':3, 'country':3, 'continent':3};
  // The address fields.
  const address = ['street', 'city', 'postcode', 'region_code', 'country_code', 'phone'];

  // Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    // The input element containing the root location.
    let rootLocation = $('#root-location').val();

    // Loops through the item array to instanciate all of the dynamic item objects.
    for(let key in items) {
      // Sets the dynamic item properties.
      let props = {'component':'ketshop', 'item':key, 'ordering':false, 'rootLocation':rootLocation, 'rowsCells':[items[key]], 'Chosen':true, 'nbItemsPerPage':5};
      // Stores the newly created object.
      GETTER[key] = new Omkod.DynamicItem(props);
    }

    // Sets the validating function.
    $('#shipping-form').submit( function(e) { validateFields(e); });

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

    // New item.
    if(shippingId == 0) {
      // Binds the delivery type select tag to the corresponding function. 
      $('#jform_delivery_type').change( function() { $.fn.switchDeliveryType($('#jform_delivery_type').val()); });
    }

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

    for(let key in items) {
      // Cost is a field common to all the dynamic items.
      let fields = {'cost':'unsigned_float'}; 

      // Adds the required field according to the dynamic item.
      if(key == 'postcode') {
	fields.from = 'unsigned_int';
	fields.to = 'unsigned_int';
      }
      else if(key == 'city') {
	fields.name = '';
      }
      else {
	fields.code = '';
      }

      if(task[0].value != 'shipping.cancel' && !GETTER[key].validateFields(fields)) {
	// Shows the dynamic item tab.
	$('.nav-tabs a[href="#'+key+'-tab"]').tab('show');

	e.preventDefault();
	e.stopPropagation();
	return false;
      }
    }
  }

  $.fn.setAddress = function(data) {
    // Sets the address fields with the given data.
    for(let i = 0; i < address.length; i++) {
      $('#jform_'+address[i]).val(data[address[i]]);
    }

    $('#jform_region_code').trigger('liszt:updated');
    $('#jform_country_code').trigger('liszt:updated');
  }

  $.fn.switchDeliveryType = function(deliveryType) {
    if(deliveryType == 'at_delivery_point') {
      // Hides the dynamic items.
      for(let key in items) {
	$('#'+key).css({'visibility':'hidden','display':'none'});
	$('a[href="#'+key+'-tab"]').parent().css({'visibility':'hidden','display':'none'});
      }

      // Makes some fields required or not required according to the delivery type.
      $('#jform_delivpnt_cost').prop('required', true);
      $('#jform_delivpnt_cost-lbl').addClass('required');
      $('#jform_global_cost').prop('required', false);
      $('#jform_global_cost-lbl').removeClass('required');

      for(let i = 0; i < address.length; i++) {
	$('#jform_'+address[i]).prop('required', true);
	$('#jform_'+address[i]+'-lbl').addClass('required');
      }

      // Shows or hides some fields according to the delivery type.
      $('#address').css({'visibility':'visible','display':'block'});
      $('#jform_global_cost').parent().parent().css({'visibility':'hidden','display':'none'}); 
      $('#jform_delivpnt_cost').parent().parent().css({'visibility':'visible','display':'block'}); 
    }
    // at_destination
    else { 
      // Shows the dynamic items.
      for(let key in items) {
	$('#'+key).css({'visibility':'visible','display':'block'});
	$('a[href="#'+key+'-tab"]').parent().css({'visibility':'visible','display':'block'});
      }

      // Makes some fields required or not required according to the delivery type.
      $('#jform_global_cost').prop('required', true);
      $('#jform_global_cost-lbl').addClass('required');
      $('#jform_delivpnt_cost').prop('required', false);
      $('#jform_delivpnt_cost-lbl').removeClass('required');

      for(let i = 0; i < address.length; i++) {
	$('#jform_'+address[i]).prop('required', false);
	$('#jform_'+address[i]+'-lbl').removeClass('required');
      }

      // Shows or hides some fields according to the delivery type.
      $('#jform_global_cost').parent().parent().css({'visibility':'visible','display':'block'}); 
      $('#address').css({'visibility':'hidden','display':'none'});
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

