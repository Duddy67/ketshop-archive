
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Get the product item id.
    var productId = $('#jform_id').val();

    if(productId != 0) {
      //Get the type of the product from the hidden input tag.
      var productType = $('#jform_type').val();
    } else {
      //Get the type of the product from the current url query.
      var productType = $.fn.getQueryParamByName('type');
    }

    //Create a container for each item type.
    $('#attribute').getContainer();

    if(productType == 'normal' && productId != 0) {
      $('#option').getContainer();
      //Remove all option items whenever group changes.
      $('#jform_attribute_group').change( function() { $('#option-container').removeItem(); });
    }

    if(productType == 'bundle') {
      $('#bundleproduct').getContainer();
    }

    $('#image').getContainer();

    //Set as function the global variables previously declared in edit.php file.
    checkAlias = $.fn.checkAlias;
    checkCategory = $.fn.checkCategory;
    checkAttrValType = $.fn.checkAttrValType;
    checkOptionValType = $.fn.checkOptionValType;

    //If the product item exists we need to get the data of the dynamical items.
    if(productId != 0) {
      //Set the url parameters for the Ajax call.
      var urlQuery = {'product_id':productId, 'product_type':productType};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  url: 'components/com_ketshop/js/ajax/product.php', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result type retrieved from the database.

	    $.each(results.image, function(i, result) { $.fn.createItem('image', result); });

	    $.each(results.attribute, function(i, result) { $.fn.createItem('attribute', result); });
	    $.each(results.option, function(i, result) { $.fn.createItem('option', result); });

	    if(productType == 'bundle') {
	      $.each(results.product, function(i, result) { $.fn.createItem('bundleproduct', result); });
	    }
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

  });


  $.fn.checkAlias = function(task) {
    var rtn;
    var id = $('#jform_id').val();
    var catid = $('#jform_catid').val();
    var alias = $('#jform_alias').val();
    var name = $('#jform_name').val();

    //Set the url parameters for the Ajax call.
    var urlQuery = {'task':task, 'id':id, 'catid':catid, 'alias':encodeURIComponent(alias), 'name':encodeURIComponent(name)};
    //Ajax call which check for unique alias.
    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/checkalias.php', 
	dataType: 'json',
	async: false, //We need a synchronous calling here.
	data: urlQuery,
	//Get result.
	success: function(result, textStatus, jqXHR) {
	  rtn = result;
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });

    return rtn;
  };


  $.fn.checkCategory = function() {
    var rtn;
    var id = $('#jform_id').val();
    var catid = $('#jform_catid').val();
    var ref_prod_id = $('#jform_ref_prod_id').val();
    var variants = $('#jform_variants').val();
    var published = $('#jform_published').val();

    //Only existing reference products or product variants are treated.
    //Don't treat products which are not published.
    if(!id || (!ref_prod_id && !variants) || published != 1) {
      return 1;
    }

    //Set the url parameters for the Ajax call.
    var urlQuery = {'id':id, 'catid':catid, 'ref_prod_id':ref_prod_id, 'variants':variants};
    //Ajax call which check for category.
    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/checkcategory.php', 
	dataType: 'json',
	async: false, //We need a synchronous calling here.
	data: urlQuery,
	//Get result.
	success: function(result, textStatus, jqXHR) {
	  rtn = result;
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
    var linkToModal = 'index.php?option=com_ketshop&view=attributes&layout=modal&tmpl=component&id_nb='+idNb;
    $('#attribute-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
    $('#attribute-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#attribute-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'attribute-name-'+idNb, 'value':data.name};
    $('#attribute-item-'+idNb).createHTMLTag('<input>', properties, 'attribute-name');
    //Create the removal button.
    $('#attribute-item-'+idNb).createButton('remove');
    $('#attribute-item-'+idNb).append('<span class="attribute-separator">&nbsp;</span>');

    //Create the "value" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ATTRIBUTE_VALUES_TITLE'), 'id':'attribute-field-value-1-'+idNb+'-lbl'};
    $('#attribute-item-'+idNb).createHTMLTag('<span>', properties, 'attribute-values-label');
    $('#attribute-item-'+idNb+' .attribute-values-label').text(Joomla.JText._('COM_KETSHOP_ATTRIBUTE_VALUES_LABEL'));

    //Some data has been provided.
    if(data.field_type_1 !== undefined) {
      $.fn.loadAttributeFields(idNb, data);
    } else { //Create  2 empty dummy tags as attribute values.
      properties = {'name':'attribute_field_value_1_'+idNb, 'id':'attribute-value-list-1-'+idNb};
      $('#attribute-item-'+idNb).createHTMLTag('<select>', properties, 'attribute-value-select');
      properties = {'type':'hidden', 'name':'attribute_field_value_2_'+idNb, 'id':'attribute-value-list-2-'+idNb, 'value':''};
      $('#attribute-item-'+idNb).createHTMLTag('<input>', properties);
    }

  };


  $.fn.loadAttributeFields = function(idNb, data) {
    //Before loading the attribute fields we need to turn their
    //values into an array thanks to the Javascript split function.  
    //Note: When the string is empty, split returns an array containing one empty
    //string, rather than an empty array.
    data.field_value_1 = data.field_value_1.split('|');
    data.field_text_1 = data.field_text_1.split('|');
    data.selected_value_1 = data.selected_value_1.split('|'); //Just in case the closed list is set as a multiselect drop down list.
    data.field_value_2 = data.field_value_2.split('|');
    data.field_text_2 = data.field_text_2.split('|');
    //Put single value into an array anyway as it will be easier to check.
    data.selected_value_2 = data.selected_value_2.split('|'); 

    //Array used as a selected switch via its id (0 -> no selected or 1 -> selected)
    var selected = new Array('', ' selected="selected"');

    if(data.field_type_1 == 'closed_list') {
      //If there is just one attribute value and the multiselect option is set we use a multi select drop down list.
      if(data.field_type_2 == 'none' && data.multiselect == 1) {
	properties = {'multiple':'multiple', 'name':'attribute_field_value_1_'+idNb+'[]', 'id':'attribute-value-list-1-'+idNb};
      }
      else { //Use a single select drop down list.
	properties = {'name':'attribute_field_value_1_'+idNb, 'id':'attribute-value-list-1-'+idNb};
      }

      $('#attribute-item-'+idNb).createHTMLTag('<select>', properties, 'attribute-value-select');

      //Fill the drop down list with the corresponding data.
      var options = '';
      for(var i = 0; i < data.field_value_1.length; i++) {
	//Check if this value is selected.
	var isSelected = $.fn.inArray(data.field_value_1[i], data.selected_value_1);
	//Note: Use option value as option text.
	options += '<option value="'+data.field_value_1[i]+'" '+selected[isSelected]+'>'+data.field_text_1[i]+'</option>';
      }
      //Add the options to the select tag.
      $('#attribute-value-list-1-'+idNb).html(options);
    }
    else { //open_field
      //Create a text input and fill it with the corresponding data if any.
      properties = {'type':'text', 'name':'attribute_field_value_1_'+idNb, 'id':'attribute-field-value-1-'+idNb, 'value':data.field_value_1};
      $('#attribute-item-'+idNb).createHTMLTag('<input>', properties, 'attribute-value-input');

      //Create a hidden field to store the value type which must be entered.
      properties = {'type':'hidden', 'name':'attribute_value_type_'+idNb, 'id':'attribute-value-type-'+idNb, 'value':data.value_type};
      $('#attribute-item-'+idNb).createHTMLTag('<input>', properties);
    }

    //Do the same for the second attribute field.
    if(data.field_type_2 != 'none' && data.field_type_2 != 'open_field') {
      //Always use a single select drop down list for the second attribute value.
      properties = {'name':'attribute_field_value_2_'+idNb, 'id':'attribute-value-list-2-'+idNb};
      $('#attribute-item-'+idNb).createHTMLTag('<select>', properties, 'attribute-value-select');
      var options = '';
      for(var i = 0; i < data.field_value_2.length; i++) {
	var isSelected = $.fn.inArray(data.field_value_2[i], data.selected_value_2);
	options += '<option value="'+data.field_value_2[i]+'" '+selected[isSelected]+'>'+data.field_text_2[i]+'</option>';
      }

      $('#attribute-value-list-2-'+idNb).html(options);
    }
    else if(data.field_type_2 == 'open_field') {
      properties = {'type':'text', 'name':'attribute_field_value_2_'+idNb, 'id':'attribute-field-value-2-'+idNb, 'value':data.field_value_2};
      $('#attribute-item-'+idNb).createHTMLTag('<input>', properties, 'attribute-value-input');
    }
    else { //none  Note: The second field is optional so we just create an hidden field.
      properties = {'type':'hidden', 'name':'attribute_field_value_2_'+idNb, 'id':'attribute-value-list-2-'+idNb, 'value':''};
      $('#attribute-item-'+idNb).createHTMLTag('<input>', properties);
    }

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
    //Remove the current attribute fields.
    $('#attribute-value-list-1-'+idNb).remove();
    $('#attribute-value-list-2-'+idNb).remove();

    //Get the fields and their values from database.
    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/attribute.php', 
	dataType: 'json',
	data: {'attribute_id':id},
	//Get results as a json array.
	success: function(result, textStatus, jqXHR) {
	  //Load the fields of the selected attribute.
	  $.fn.loadAttributeFields(idNb, result);
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
    var link = 'index.php?option=com_media&view=images&tmpl=component&asset=com_ketshop&author='+userId+'&fieldid='+idNb+'&folder=ketshop';
    $('#image-item-'+idNb).createButton('select', '#', link);

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
    //product type (it prevents to have a bundle product types or product variants in the modal list). 
    var linkToModal = 'index.php?option=com_ketshop&view=products&layout=modal&tmpl=component&id_nb='+idNb+'&type=bundleproduct&product_type=normal';
    $('#bundleproduct-item-'+idNb).createButton('select', '#', linkToModal);

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


  $.fn.checkAttrValType = function() {
    var ret = true;
    var regex = /(attribute_value_type_)([0-9]+)/;
    //Search for all value type tags.
    $('input[name^="attribute_value_type_"]').each(function(i, tag) { 
      var valueType = tag.value;
      //Get the id number from the tag name.
      var match = regex.exec(tag.name);
      var idNb = match[2];
      //Get the corresponding field value.
      var fieldValue = $('input[name="attribute_field_value_1_'+idNb+'"]').val();
      //Check value type.
      if(!$.fn.checkValueType(fieldValue, valueType)) {
        ret = false;
	//Show off the concerned field.
	alertRed('attribute-field-value-1-'+idNb, 'attributes');

	alert(Joomla.JText._('COM_KETSHOP_ERROR_INCORRECT_VALUE_TYPE')+' : '+fieldValue+'\r'+Joomla.JText._('COM_KETSHOP_EXPECTED_VALUE_TYPE')+' : '+valueType);

	return false; //Important: Just breaks the each() loop but doesn't return false to the calling function.
      }
    });

    return ret;
  };


  $.fn.checkOptionValType = function() {
    var fieldTypes = {'ordering':'unsigned_int','option_name':'string','stock':'unsigned_int',
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
	  alertRed(tag.id, 'product-options');

	  alert(Joomla.JText._('COM_KETSHOP_ERROR_INCORRECT_VALUE_TYPE')+' : '+tag.value+'\r'+Joomla.JText._('COM_KETSHOP_EXPECTED_VALUE_TYPE')+' : '+fieldTypes[key]);

	  return false; //Important: Just breaks the each() loop but doesn't return false to the calling function.
	}
      });

      if(!ret) {
        return false;
      }
    }

    //Some options have been set.
    //Check that the option name field of the main product has been set.
    if(!empty && $('#jform_option_name').val() == '') {
      alert(Joomla.JText._('COM_KETSHOP_OPTION_NAME_MAIN_PRODUCT_EMPTY'));
      return false;
    }

    return true;
  };


  $.fn.createOptionItem = function(idNb, data) {
    //Get the selected attribute group id.
    var attribGroupId = $('#jform_attribute_group').val();
    //Get all the attribute groups.
    var attribGroups = ketshop.getAttributeGroups();
    var attribGroup = new Array();

    //Check if all groups are empty.
    if(!attribGroups.length) {
      //Remove the container newly created.
      $('#option-container').removeItem();

      alert(Joomla.JText._('COM_KETSHOP_ALL_ATTRIBUTE_GROUPS_EMPTY'));
      return false;
    }

    //Search for the selected group.
    for(var i = 0; i < attribGroups.length; i++) {
      if(attribGroups[i].group_id == attribGroupId) {
	attribGroup.push(attribGroups[i]);
      }
    }

    //Check for empty group.
    if(!attribGroup.length) {
      //Remove the container newly created.
      $('#option-container').removeItem();

      if(attribGroupId != 0) {
	alert(Joomla.JText._('COM_KETSHOP_ATTRIBUTE_GROUP_EMPTY'));
      }
      else {
	alert(Joomla.JText._('COM_KETSHOP_NO_ATTRIBUTE_GROUP_SELECTED'));
      }

      return false;
    }

    //First create divs in which we'll put all the option fields.
    var properties = {'id':'option-left-div-'+idNb};
    $('#option-item-'+idNb).createHTMLTag('<div>', properties, 'span3 option-div');

    properties = {'id':'option-center-div-'+idNb};
    $('#option-item-'+idNb).createHTMLTag('<div>', properties, 'span3 option-div');

    properties = {'id':'option-right-div-'+idNb};
    $('#option-item-'+idNb).createHTMLTag('<div>', properties, 'span3 option-div');

    //Build a drop down list for each attribute.
    for(var i = 0; i < attribGroup.length; i++) {
      //var attribute = attribGroup[i];  
      var field_value_1 = attribGroup[i].field_value_1.split('|');
      var field_text_1 = attribGroup[i].field_text_1.split('|');
      //Note: Store the attribute id just before the id number.
      properties = {'name':'attribute_'+attribGroup[i].id+'_'+idNb, 'id':'attribute-'+attribGroup[i].id+'-'+idNb};
      $('#option-right-div-'+idNb).createHTMLTag('<select>', properties, 'option-field');

      //Fill the drop down list with the attribute values.
      var options = '';
      for(var j = 0; j < field_value_1.length; j++) {
	var selected = '';
	if(data.opt_id) { //Item exists.
	  //Check if this value is selected.
	  for(var k = 0; k < data.attributes.length; k++) {
	    //Attribute id must be checked too.
	    if(data.attributes[k].attrib_value == field_value_1[j] && data.attributes[k].attrib_id == attribGroup[i].id) {
	      selected = ' selected="selected"';
	      break;
	    }
	  }
	}

	//Note: Use option value as option text.
	options += '<option value="'+field_value_1[j]+'" '+selected+'>'+field_text_1[j]+'</option>';
      }
      //Add the options to the select tag.
      $('#attribute-'+attribGroup[i].id+'-'+idNb).html(options);

      //Create the attribute name tag.
      properties = {'title':attribGroup[i].name, 'id':'attribute-name-'+attribGroup[i].id+'-'+idNb};
      var newTag = $('<span>').attr(properties);
      newTag.addClass('option-label');
      $('#attribute-'+attribGroup[i].id+'-'+idNb).before(newTag);
      $('#attribute-name-'+attribGroup[i].id+'-'+idNb).text(attribGroup[i].name);
    }

    //Create the hidden input tag to store the option id.
    properties = {'type':'hidden', 'name':'option_id_'+idNb, 'id':'option-id-'+idNb, 'value':data.opt_id};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties);

    //Associative array where keys are field names and values are field ids.
    var fields = {'published':'published','ordering':'ordering','option_name':'option-name',
                  'stock':'stock','base_price':'base-price','sale_price':'sale-price',
		  'sales':'sales','code':'code','availability_delay':'availability-delay',
		  'weight':'weight','length':'length','width':'width','height':'height'};

    var i = 0; //Needed for position.

    //Build option fields.
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
	$('#option-'+position+'-div-'+idNb).createHTMLTag('<select>', properties, 'option-field');
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
	$('#option-'+position+'-div-'+idNb).createHTMLTag('<input>', properties, 'option-field');

	//Sales field is not allowed to be edited.	
	if(fieldName == 'sales') {
	  $('#'+fieldId+'-'+idNb).prop('readonly', true);
	  $('#'+fieldId+'-'+idNb).addClass('readonly');
	}
      }

      properties =
      {'title':Joomla.JText._('COM_KETSHOP_'+fieldName.toUpperCase()+'_TITLE'), 'id':fieldId+'-'+idNb+'-lbl'};
      newTag = $('<span>').attr(properties);
      newTag.addClass('option-label');
      $('#'+fieldId+'-'+idNb).before(newTag);
      $('#'+fieldId+'-'+idNb+'-lbl').text(Joomla.JText._('COM_KETSHOP_'+fieldName.toUpperCase()+'_LABEL'));

      i++;
    }

    //Create the item removal button.
    $('#option-item-'+idNb).createButton('remove');

  };

})(jQuery);

