
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Create a container.
    $('#attribute').getContainer();

    //Get the filter item id.
    var filterId = $('#jform_id').val();

    //If the filter item exists we need to get the data of the dynamical items.
    if(filterId != 0) {
      //Gets the token's name as value.
      var token = $('#token').attr('name');
      //Sets up the ajax query.
      var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'filter_id':filterId};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results.data, function(i, result) { $.fn.createItem('attribute', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }
  });


  $.fn.createAttributeItem = function(idNb, data) {
    //Create the hidden input tag to store the attribute id.
    var properties = {'type':'hidden', 'name':'attribute_id_'+idNb, 'id':'attribute-id-'+idNb, 'value':data.id};
    $('#attribute-item-'+idNb).createHTMLTag('<input>', properties);

    //Build the link to the modal window displaying the attribute list.
    var baseUrl = $('#base-url').val();
    var linkToModal = baseUrl+'administrator/index.php?option=com_ketshop&view=attributes&layout=modal&tmpl=component&id_nb='+idNb;
    $('#attribute-item-'+idNb).createButton('select', 'javascript:void(0);', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
    $('#attribute-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#attribute-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'attribute-name-'+idNb, 'value':data.name};
    $('#attribute-item-'+idNb).createHTMLTag('<input>', properties, 'attribute-name');
    //Create the removal button.
    $('#attribute-item-'+idNb).createButton('remove');
  },


  window.jQuery.selectAttribute = function(id, name, idNb) {
    //Invoke our standard function to set the id an name of the
    //selected item. 
    window.jQuery.selectItem(id, name, idNb, 'attribute');
  };
})(jQuery);
