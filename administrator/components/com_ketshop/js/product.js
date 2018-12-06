
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Get the product item id.
    var productId = $('#jform_id').val();

    if(productId != 0) {
      //Get the type of the product from the form value.
      var productType = $('#jform_type').val();
    } else {
      //Get the type of the product from the hidden input tag.
      var productType = $('#product-type').val();
    }

    //Create a container for each item type.
    $('#attribute').getContainer();

    if(productType == 'normal' && productId != 0) {
      $('#variant').getContainer();
    }

    if(productType == 'bundle') {
      $('#bundleproduct').getContainer();
    }

    $('#image').getContainer();

    //Set as function the global variables previously declared in edit.php file.
    checkAlias = $.fn.checkAlias;
    checkVariantValType = $.fn.checkVariantValType;

    //If the product item exists we need to get the data of the dynamical items.
    if(productId != 0) {
      var isAdmin = $('#is-admin').val();

      //Gets the token's name as value.
      var token = $('#token').attr('name');
      //Sets up the ajax query.
      var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'context':'product_elements', 'product_id':productId, 'product_type':productType, 'is_admin':isAdmin};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result type retrieved from the database.
	    $.each(results.data.image, function(i, result) { $.fn.createItem('image', result); });
	    $.each(results.data.attribute, function(i, result) { $.fn.createItem('attribute', result); });
	    $.each(results.data.variant, function(i, result) { $.fn.createItem('variant', result); });

	    if(productType == 'bundle') {
	      $.each(results.data.product, function(i, result) { $.fn.createItem('bundleproduct', result); });
	    }
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

  });


  $.fn.checkAlias = function() {
    var rtn;
    var productId = $('#jform_id').val();
    var catid = $('#jform_catid').val();
    var name = $('#jform_name').val();
    var alias = $('#jform_alias').val();
    //Gets the token's name as value.
    var token = $('#token').attr('name');
    //Sets up the ajax query.
    var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'context':'check_alias', 'product_id':productId, 'catid':catid, 'name':name, 'alias':alias};

    //Ajax call which check for unique alias.
    $.ajax({
	type: 'GET', 
	dataType: 'json',
	async: false, //We need a synchronous calling here.
	data: urlQuery,
	//Get result.
	success: function(results, textStatus, jqXHR) {
	  rtn = results.data;
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });

    return rtn;
  };


  $.fn.createAttributeItem = function(idNb, data) {
    //Create the hidden input tag to store the attribute id.
    var properties = {'type':'hidden', 'name':'attribute_id_'+idNb, 'id':'attribute-id-'+idNb, 'value':data.id};
    $('#attribute-item-'+idNb).createHTMLTag('<input>', properties);

    //Build the link to the modal window displaying the attribute list.
    var baseUrl = $('#base-url').val();
    var linkToModal = baseUrl+'administrator/index.php?option=com_ketshop&view=attributes&layout=modal&tmpl=component&id_nb='+idNb;
    $('#attribute-item-'+idNb).createButton('select', 'javascript:void(0);', linkToModal);

    //Create the "Value" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_VALUE_TITLE')};
    $('#attribute-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#attribute-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_VALUE_LABEL'));

    //Creates an empty select list.
    properties = {'name':'attribute_value_'+idNb, 'id':'attribute-value-'+idNb};
    $('#attribute-item-'+idNb).createHTMLTag('<select>', properties, 'attribute-value-select');

    //Some data has been provided.
    if(data.id != '') {
      $.fn.loadAttributeOptions(idNb, data);
    }

    //Create the removal button.
    $('#attribute-item-'+idNb).createButton('remove');
    $('#attribute-item-'+idNb).append('<span class="attribute-separator">&nbsp;</span>');
  };


  $.fn.loadAttributeOptions = function(idNb, data, attributeId) {
    //Variant attributes need an extra id to sort them out later.
    var extraId = ['', ''];
    //Checks if an attribute id is provided.
    if($.isNumeric(attributeId)) {
      extraId[0] = '-'+attributeId; //For the tag id (hyphen)
      extraId[1] = '_'+attributeId; //For the tag name (underscore)
    }

    var disabled = '';
    //If the multiselect option is set we use a multi select drop down list.
    if(data.multiselect == 1) {
      var properties = {'multiple':'multiple', 'name':'attribute_value_'+idNb+extraId[1]+'[]'};
      $('#attribute-value-'+idNb+extraId[0]).attr(properties);
      //Prevents the very first option to be checked.
      disabled = 'disabled="disabled"';
    }

    //Fill the drop down list with the corresponding data.
    var options = '';
    //Creates the very first option.
    options += '<option value="" '+disabled+'> - '+data.name+' - </option>';

    for(var i = 0; i < data.options.length; i++) {
      //Check if this option is selected.
      var isSelected = data.options[i].selected;
      //Creates the options.
      options += '<option value="'+data.options[i].option_value+'" '+isSelected+'>'+data.options[i].option_text+'</option>';
    }

    //Adds the options to the select list.
    $('#attribute-value-'+idNb+extraId[0]).append(options);

    //Change the item class to indicate that the item is unpublished archived or trashed.
    if(data.published != 1) {
      $('#attribute-item-'+idNb).prop('class', 'unpublished-item');
    }
  };


  //Function called from the attribute modal child window, so we have to be specific
  //and use the window object and the jQuery alias as well.
  window.jQuery.selectAttribute = function(id, name, idNb) {
    //Invoke our standard function to set the id an name of the
    //selected item. 
    window.jQuery.selectItem(id, name, idNb, 'attribute');
    //Empties all the possible options of the select list. 
    $('#attribute-value-'+idNb).empty();

    //Gets the token's name as value.
    var token = $('#token').attr('name');
    //Sets up the ajax query.
    var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'context':'attribute', 'attribute_id':id};

    //Get the fields and their values from database.
    $.ajax({
	type: 'GET', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
	  //Load the options of the selected attribute.
	  $.fn.loadAttributeOptions(idNb, results.data);
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });
  };


  $.fn.createImageItem = function(idNb, data) {
    //Get the id of the current user.
    var userId = ketshop.getUserId();
    //Build the link to the Joomla image server.
    var baseUrl = $('#base-url').val();
    var link = baseUrl+'administrator/index.php?option=com_media&view=images&tmpl=component&asset=com_ketshop&author='+userId+'&fieldid='+idNb+'&folder=ketshop';
    $('#image-item-'+idNb).createButton('select', 'javascript:void(0);', link);

    //Create the "alt" label.
    var properties = {'title':Joomla.JText._('COM_KETSHOP_IMAGE_ALT_TITLE')};
    $('#image-item-'+idNb).createHTMLTag('<span>', properties, 'image-alt-label');
    $('#image-item-'+idNb+' .image-alt-label').text(Joomla.JText._('COM_KETSHOP_IMAGE_ALT_LABEL'));
    //Create the "alt" input.
    properties = {'type':'text', 'name':'image_alt_'+idNb, 'value':data.alt};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties, 'image-alt');

    //Create the "order" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_IMAGE_ORDERING_TITLE')};
    $('#image-item-'+idNb).createHTMLTag('<span>', properties, 'image-ordering-label');
    $('#image-item-'+idNb+' .image-ordering-label').text(Joomla.JText._('COM_KETSHOP_IMAGE_ORDERING_LABEL'));

    //Get the number of image items within the container then use it as ordering
    //number for the current item.
    var ordering = $('#image-container').children('div').length;
    if(data.ordering !== '') {
      ordering = data.ordering;
    }
    //Create the "order" input.
    properties = {'type':'text', 'name':'image_ordering_'+idNb, 'readonly':'readonly', 'value':ordering};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties, 'image-ordering');
    //Create the removal button.
    $('#image-item-'+idNb).createButton('remove_image');

    //Create a div in which img tag is nested.
    properties = {'id':'img-div-'+idNb};
    $('#image-item-'+idNb).createHTMLTag('<div>', properties, 'div-product-image');
    //Create the img tag within the div.
    properties = {'src':data.src, 'width':data.width, 'height':data.height, 'id':'product-img-'+idNb};
    $('#img-div-'+idNb).createHTMLTag('<img>', properties, 'product-image');

    if(data.src !== '') {
      //Div is resized to fit the image dimensions and a 1px gray border is defined. 
      $('#img-div-'+idNb).css({'width':data.width+'px','height':data.height+'px','border':'1px solid #c0c0c0'});
    }

    //Create the hidden inputs needed to save image data.
    properties = {'type':'hidden', 'name':'image_src_'+idNb, 'id':'image-src-'+idNb, 'value':data.src};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties);
    properties = {'type':'hidden', 'name':'image_width_'+idNb, 'id':'image-width-'+idNb, 'value':data.width};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties);
    properties = {'type':'hidden', 'name':'image_height_'+idNb, 'id':'image-height-'+idNb, 'value':data.height};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties);
  };

  //Remove the selected image item then reset the order of the other items left.
  $.fn.imageReorder = function(idNb) {
    //Remove the selected image.
    $('#image-container').removeItem(idNb);

    //List all of the div children (ie: image items) of the image container 
    //in order to reset their ordering value.
    $('#image-container').children('div').each(function(i, div) {
	//Reset the ordering input tag value.
	$(div).children('.image-ordering').val(i+1);
	});
  };


  $.fn.createBundleproductItem = function(idNb, data) {
    //Create the hidden input tag to store the bundle product id.
    var properties = {'type':'hidden', 'name':'bundleproduct_id_'+idNb, 'id':'bundleproduct-id-'+idNb, 'value':data.id};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<input>', properties);

    //Build the link to the modal window displaying the products.
    //Note: product_type parameter ask product model to only display normal
    //product type (it prevents to have a bundle product types in the modal list). 
    var linkToModal = 'index.php?option=com_ketshop&view=products&layout=modal&tmpl=component&id_nb='+idNb+'&type=bundleproduct&product_type=normal';
    $('#bundleproduct-item-'+idNb).createButton('select', 'javascript:void(0);', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#bundleproduct-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'bundleproduct-name-'+idNb, 'value':data.name};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<input>', properties, 'bundleproduct-name');

    //Create the "quantity" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_QUANTITY_TITLE')};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<span>', properties, 'item-quantity-label');
    $('#bundleproduct-item-'+idNb+' .item-quantity-label').text(Joomla.JText._('COM_KETSHOP_ITEM_QUANTITY_LABEL'));
    //Create text field to store the bundle product quantity.
    properties = {'type':'text', 'name':'bundleproduct_quantity_'+idNb, 'id':'bundleproduct-quantity-'+idNb, 'value':data.quantity};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<input>', properties, 'bundleproduct-quantity');

    //Create the "stock" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_PRODUCT_STOCK_TITLE')};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<span>', properties, 'product-stock-label');
    $('#bundleproduct-item-'+idNb+' .product-stock-label').text(Joomla.JText._('COM_KETSHOP_PRODUCT_STOCK_LABEL'));
    //Create text field to store the bundle product stock value.
    properties = {'type':'text', 'disabled':'disabled', 'name':'bundleproduct_stock_'+idNb, 'id':'bundleproduct-stock-'+idNb, 'value':data.stock};
    $('#bundleproduct-item-'+idNb).createHTMLTag('<input>', properties, 'bundleproduct-stock');
    //Create the removal button.
    $('#bundleproduct-item-'+idNb).createButton('remove');
  };


  $.fn.checkVariantValType = function() {
    var fieldTypes = {'ordering':'unsigned_int','variant_name':'string','stock':'unsigned_int',
                      'base_price':'unsigned_float','sale_price':'unsigned_float','code':'string',
                      'availability_delay':'unsigned_int','weight':'unsigned_float','length':'unsigned_float',
                      'width':'unsigned_float','height':'unsigned_float'};
    var ret = empty = true;

    for(var key in fieldTypes) {
      $('input[name^="'+key+'_"]').each(function(i, tag) { 
	empty = false; //Set the empty flag to indicate that at least one option is set.
	if(!$.fn.checkValueType(tag.value, fieldTypes[key])) {
	  ret = false;
	  //Show off the concerned field.
	  alertRed(tag.id, 'product-variants');

	  alert(Joomla.JText._('COM_KETSHOP_ERROR_INCORRECT_VALUE_TYPE')+' : '+tag.value+'\r'+Joomla.JText._('COM_KETSHOP_EXPECTED_VALUE_TYPE')+' : '+fieldTypes[key]);

	  return false; //Important: Just breaks the each() loop but doesn't return false to the calling function.
	}
      });

      if(!ret) {
        return false;
      }
    }

    //Some variants have been set.
    //Check that the variant name field of the main product has been set.
    if(!empty && $('#jform_variant_name').val() == '') {
      alert(Joomla.JText._('COM_KETSHOP_OPTION_NAME_MAIN_PRODUCT_EMPTY'));
      return false;
    }

    return true;
  };


  $.fn.createVariantItem = function(idNb, data) {
    //First create divs in which we'll put all the variant fields.
    var properties = {'id':'variant-left-div-'+idNb};
    $('#variant-item-'+idNb).createHTMLTag('<div>', properties, 'span3 variant-div');

    properties = {'id':'variant-center-div-'+idNb};
    $('#variant-item-'+idNb).createHTMLTag('<div>', properties, 'span3 variant-div');

    properties = {'id':'variant-right-div-'+idNb};
    $('#variant-item-'+idNb).createHTMLTag('<div>', properties, 'span3 variant-div');

    //Gets the attributes linked to the product (in the attributes tab).
    //Note: Used whenever a brand new variant item is created.
    var attributes = ketshop.getProductAttributes();

    //If data is provided gets the attributes with the selected options.
    if(data.var_id != '' && data.attributes.length > 0) {
      attributes = data.attributes;
    }

    for(var i = 0; i < attributes.length; i++) {
      //Creates a div for the select element.
      properties = {'id':'variant-attribute-div-'+idNb+'-'+attributes[i].id};
      $('#variant-right-div-'+idNb).createHTMLTag('<div>', properties, 'variant-attribute-div');

      properties = {'title':attributes[i].name};
      $('#variant-attribute-div-'+idNb+'-'+attributes[i].id).createHTMLTag('<span>', properties, 'item-name-label');
      $('#variant-attribute-div-'+idNb+'-'+attributes[i].id+' .item-name-label').text(attributes[i].name);

      //Creates an empty select list.
      properties = {'name':'attribute_value_'+idNb+'_'+attributes[i].id, 'id':'attribute-value-'+idNb+'-'+attributes[i].id};
      $('#variant-attribute-div-'+idNb+'-'+attributes[i].id).createHTMLTag('<select>', properties, 'attribute-value-select');
      $.fn.loadAttributeOptions(idNb, attributes[i], attributes[i].id);
    }

    //Create the hidden input tag to store the variant id.
    properties = {'type':'hidden', 'name':'variant_id_'+idNb, 'id':'variant-id-'+idNb, 'value':data.var_id};
    $('#variant-item-'+idNb).createHTMLTag('<input>', properties);

    //Associative array where keys are field names and values are field ids.
    var fields = {'published':'published','ordering':'ordering','variant_name':'variant-name',
                  'stock':'stock','base_price':'base-price','sale_price':'sale-price',
		  'sales':'sales','code':'code','availability_delay':'availability-delay',
		  'weight':'weight','length':'length','width':'width','height':'height'};

    var i = 0; //Needed for position.

    //Build variant fields.
    for(var key in fields) {
      var fieldName = key;
      var fieldId = fields[key];
      var position = 'left';

      if(i > 5) {
	position = 'center';
      }

      //The published value is managed with a select tag.
      if(fieldName == 'published') {
	properties = {'name':fieldId+'_'+idNb, 'id':fieldId+'-'+idNb};
	$('#variant-'+position+'-div-'+idNb).createHTMLTag('<select>', properties, 'variant-field');
	options = '';
	for(var j = 0; j < 2; j++) {
	  selected = '';

	  if(data[fieldName] == j) {
	    selected = 'selected="selected"';
	  }

	  options += '<option value="'+j+'" '+selected+'>'+Joomla.JText._('COM_KETSHOP_YESNO_'+j)+'</option>';
	}

	//Add the options to the select tag.
	$('#'+fieldId+'-'+idNb).html(options);
      }
      else { //Build a text input.
	properties = {'type':'text', 'name':fieldName+'_'+idNb, 'id':fieldId+'-'+idNb, 'value':data[fieldName]};
	$('#variant-'+position+'-div-'+idNb).createHTMLTag('<input>', properties, 'variant-field');

	//Sales field is not allowed to be edited.	
	if(fieldName == 'sales') {
	  $('#'+fieldId+'-'+idNb).prop('readonly', true);
	  $('#'+fieldId+'-'+idNb).addClass('readonly');
	}
      }

      properties = {'title':Joomla.JText._('COM_KETSHOP_'+fieldName.toUpperCase()+'_TITLE'), 'id':fieldId+'-'+idNb+'-lbl'};
      newTag = $('<span>').attr(properties);
      newTag.addClass('variant-label');
      $('#'+fieldId+'-'+idNb).before(newTag);
      $('#'+fieldId+'-'+idNb+'-lbl').text(Joomla.JText._('COM_KETSHOP_'+fieldName.toUpperCase()+'_LABEL'));

      i++;
    }

    //Create the item removal button.
    $('#variant-item-'+idNb).createButton('remove');
  };

})(jQuery);

