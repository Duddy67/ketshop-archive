
(function($) {

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
    $('#region-code-'+idNb).trigger('liszt:updated');
    $('#region-code-'+idNb).chosen();

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
    $('#country-code-'+idNb).trigger('liszt:updated');
    $('#country-code-'+idNb).chosen();

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
    $('#continent-id-'+idNb).toggle('chosen:updated');
    $('#continent-id-'+idNb).chosen();

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

})(jQuery);

