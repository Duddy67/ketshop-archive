
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Create a container.
    $('#option').getContainer();

    //Get the attribute item id.
    var attributeId = $('#jform_id').val();

    //If the attribute item exists we need to get the data of the dynamical items.
    if(attributeId != 0) {
      //Gets the token's name as value.
      var token = $('#token').attr('name');
      //Sets up the ajax query.
      var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'attribute_id':attributeId};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results.data, function(i, result) { $.fn.createItem('option', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

  });


  $.fn.createOptionItem = function(idNb, data) {
    //Create the "value" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_VALUE_TITLE')};
    $('#option-item-'+idNb).createHTMLTag('<span>', properties, 'item-value-label');
    $('#option-item-'+idNb+' .item-value-label').text(Joomla.JText._('COM_KETSHOP_VALUE_LABEL'));

    //Create the option value input.
    var properties = {'type':'text', 'name':'option_value_'+idNb, 'id':'option-value-'+idNb, 'value':data.option_value};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'item-value');

    //Create the "text" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_TEXT_TITLE')};
    $('#option-item-'+idNb).createHTMLTag('<span>', properties, 'item-text-label');
    $('#option-item-'+idNb+' .item-text-label').text(Joomla.JText._('COM_KETSHOP_TEXT_LABEL'));

    //Create the option text input.
    var properties = {'type':'text', 'name':'option_text_'+idNb, 'id':'option-text-'+idNb, 'value':data.option_text};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'item-text');

    //Create the "published" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_PUBLISHED_TITLE')};
    $('#option-item-'+idNb).createHTMLTag('<span>', properties, 'published-label');
    $('#option-item-'+idNb+' .published-label').text(Joomla.JText._('COM_KETSHOP_PUBLISHED_LABEL'));

    //Create the "published" checkbox.
    //Note: The value is not important here as it doesn't have impact on the fact that 
    //      the checkbox is checked or not. 
    var properties = {'type':'checkbox', 'name':'option_published_'+idNb, 'id':'option-published-'+idNb, 'value':idNb};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'option-name-item');

    //Set the checkbox state.
    if(data.published == 1) { //checked
      $('#option-published-'+idNb).prop('checked', true);
    }

    //Get the number of items within the container then use it as ordering
    //number for the current item.
    var ordering = $('#option-container').children('div').length;
    if(data.option_ordering !== undefined) {
      ordering = data.option_ordering;
    }

    //Create the "order" input.
    properties = {'type':'text', 'name':'option_ordering_'+idNb, 'id':'option-ordering-'+idNb, 'readonly':'readonly', 'value':ordering};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'item-ordering');
    $.fn.setOrderManagement('option');

    //Create the removal button.
    $('#option-item-'+idNb).createButton('remove');
  };

})(jQuery);
