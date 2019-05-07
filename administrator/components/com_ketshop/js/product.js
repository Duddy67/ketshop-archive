(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};
  // The dynamic items to create. {item name:nb of cells}
  const items = {'attribute':4, 'image':[4,1]};

  // Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    // The input element containing the root location.
    let rootLocation = $('#root-location').val();
    let productId = $('#jform_id').val();
    let isAdmin = $('#is-admin').val();

    if(productId != 0) {
      items.variant = [8,6,6];
    }

    // Loops through the item array to instanciate all of the dynamic item objects.
    for(let key in items) {
      // Sets the dynamic item properties.
      let props = {'component':'ketshop', 'item':key, 'rootLocation':rootLocation, 'Chosen':true, 'nbItemsPerPage':5};

      props.rowsCells = items[key];
      props.ordering = true;

      if(key == 'attribute') {
	// Some properties are different for attributes.
	props.rowsCells = [items[key]];
	props.ordering = false;
      }

      // Stores the newly created object.
      GETTER[key] = new Omkod.DynamicItem(props);
    }

    // Sets the validating function.
    $('#product-form').submit( function(e) { validateFields(e); });

    // Prepares then run the Ajax query.
    const ajax = new Omkod.Ajax();
    let params = {'method':'GET', 'dataType':'json', 'indicateFormat':true, 'async':true};
    // Gets the form security token.
    let token = jQuery('#token').attr('name');
    // N.B: Invokes first the ajax() function in the global controller to check the token.
    let data = {[token]:1, 'task':'ajax', 'product_id':productId, 'is_admin':isAdmin};
    ajax.prepare(params, data);
    ajax.process(getAjaxResult);
  });

  getAjaxResult = function(result) {
    if(result.success === true) {
      // Loops through the item array to create all of the dynamic items.
      for(let key in items) {
	$.each(result.data[key], function(i, item) { GETTER[key].createItem(item); });
      }
    }
    else {
      alert('Error: '+result.message);
    }
  }

  validateFields = function(e) {
    let task = document.getElementsByName('task');
  }

  $.fn.setAttributeOptions = function(idNb, attribId, data) {
    // Gets the options corresponding to the given attribute id.
    let attributeOptions = ketshop.attributeOptions[attribId].options;
    let multiselect = ketshop.attributeOptions[attribId].multiselect;
    let options = '';

    // Deletes all the possible previous options.
    $('#attribute-value-'+idNb).empty();
    // By default use the multi select mode.
    $('#attribute-value-'+idNb).attr('multiple', 'true');

    // Single select mode.
    if(multiselect == 0) {
      $('#attribute-value-'+idNb).removeAttr('multiple');
      // Creates the very first list option.
      options += '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';
    }

    // Destroys the div structure previously created by the Chosen plugin. 
    $('#attribute-value-'+idNb).chosen('destroy');

    // Handles the selected options in multiselect mode.
    if(data !== undefined && multiselect == 1) {
      // Converts the string format array to a valid JS array.
      var selectedOptions = JSON.parse(data.selected_option);
    }

    // Loops through the options.
    for(let i = 0; i < attributeOptions.length; i++) {
      let selected = '';
      if(data !== undefined) {
	// Sets the selected option(s) according to the select mode (single or multi).
	if((multiselect == 0 && data.selected_option == attributeOptions[i].option_value) ||
	   (multiselect == 1 && GETTER.attribute.inArray(attributeOptions[i].option_value, selectedOptions))) {
	  selected = 'selected="selected"';
	}
      }

      options += '<option value="'+attributeOptions[i].option_value+'" '+selected+'>'+attributeOptions[i].option_text+'</option>';
    }
    
    // Adds the options to the select list.
    $('#attribute-value-'+idNb).append(options);
    // Recreates a new Chosen div structure.
    $('#attribute-value-'+idNb).chosen();
  }

  /** Callback functions **/

  populateAttributeItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'attribute_id':'', 'attribute_name':'', 'selected_option':''};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'attribute-label-'+idNb};
    $('#attribute-row-1-cell-1-'+idNb).append(GETTER.attribute.createElement('span', attribs));
    $('#attribute-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected attribute id.
    attribs = {'type':'hidden', 'name':'attribute_attribute_id_'+idNb, 'id':'attribute-attribute-id-'+idNb, 'value':data.attribute_id};
    let elem = GETTER.attribute.createElement('input', attribs);
    $('#attribute-row-1-cell-1-'+idNb).append(elem);
    let url = $('#root-location').val()+'administrator/index.php?option=com_ketshop&view=attributes&layout=modal&tmpl=component&function=selectAttributeItem&dynamic_item_type=attribute&id_nb='+idNb;
    let button = GETTER.attribute.createButton('select', idNb, url);
    $('#attribute-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'attribute-attributename-label-'+idNb};
    $('#attribute-row-1-cell-2-'+idNb).append(GETTER.attribute.createElement('span', attribs));
    $('#attribute-attributename-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'id':'attribute-attribute-name-'+idNb, 'value':data.attribute_name};
    elem = GETTER.attribute.createElement('input', attribs);
    $('#attribute-row-1-cell-2-'+idNb).append(elem);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_VALUE_TITLE'), 'class':'item-label', 'id':'attribute-valuename-label-'+idNb};
    $('#attribute-row-1-cell-3-'+idNb).append(GETTER.attribute.createElement('span', attribs));
    $('#attribute-valuename-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_VALUE_LABEL'));

    // Select tag:
    attribs = {'name':'attribute_value_'+idNb, 'id':'attribute-value-'+idNb};
    elem = GETTER.attribute.createElement('select', attribs);
    $('#attribute-row-1-cell-3-'+idNb).append(elem);

    if(data.attribute_id != '') {
      $.fn.setAttributeOptions(idNb, data.attribute_id, data);
    }
  }

  populateImageItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'src':'', 'width':'', 'height':'', 'alt':''};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'image-label-'+idNb};
    $('#image-row-1-cell-1-'+idNb).append(GETTER.image.createElement('span', attribs));
    $('#image-label-'+idNb).html('&nbsp;');

    // Creates the hidden input elements to store the image attributes.
    attribs = {'type':'hidden', 'name':'image_src_'+idNb, 'id':'image-src-'+idNb, 'value':data.src};
    let elem = GETTER.image.createElement('input', attribs);
    $('#image-row-1-cell-1-'+idNb).append(elem);
    attribs = {'type':'hidden', 'name':'image_width_'+idNb, 'id':'image-width-'+idNb, 'value':data.width};
    elem = GETTER.image.createElement('input', attribs);
    $('#image-row-1-cell-1-'+idNb).append(elem);
    attribs = {'type':'hidden', 'name':'image_height_'+idNb, 'id':'image-height-'+idNb, 'value':data.height};
    elem = GETTER.image.createElement('input', attribs);
    $('#image-row-1-cell-1-'+idNb).append(elem);

    // Gets the id of the current user.
    let userId = ketshop.getUserId();

    let url = $('#root-location').val()+'administrator/index.php?option=com_media&view=images&tmpl=component&asset=com_ketshop&author='+userId+'&fieldid='+idNb+'&folder=ketshop';
    let button = GETTER.attribute.createButton('select', idNb, url);
    $('#image-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_IMAGE_ALT_TITLE'), 'class':'item-label', 'id':'image-alt-label-'+idNb};
    $('#image-row-1-cell-2-'+idNb).append(GETTER.image.createElement('span', attribs));
    $('#image-alt-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_IMAGE_ALT_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'image_alt_'+idNb, 'id':'image-alt-'+idNb, 'value':data.alt};
    $('#image-row-1-cell-2-'+idNb).append(GETTER.image.createElement('input', attribs));

    // Div tag:
    attribs = {'id':'img-div-'+idNb, 'class':'div-product-image'};
    $('#image-row-2-cell-1-'+idNb).append(GETTER.image.createElement('div', attribs));

    // Image tag:
    attribs = {'src':data.src, 'width':data.width, 'height':data.height, 'id':'product-img-'+idNb};
    // Embeds the img tag into the div.
    $('#img-div-'+idNb).append(GETTER.image.createElement('img', attribs));
  }

  populateVariantItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'var_id':0, 'variant_name':'', 'base_price':'', 'sale_price':'', 'stock':'', 'sales':'', 'published':0, 'weight':'', 'length':'', 'width':'', 'height':'', 'code':'', 'availability_delay':''};
    }
    else {
      data = {'var_id':data.var_id, 'variant_name':data.variant_name, 'base_price':data.base_price, 'sale_price':data.sale_price, 'stock':data.stock, 'sales':data.sales, 'published':data.published, 'weight':data.weight, 'length':data.length, 'width':data.width, 'height':data.height, 'code':data.code, 'availability_delay':data.availability_delay};
    }

    let rowNb = 1;
    let cellNb = 1;
    let attribs = null;

    for(let key in data) {
      if(key == 'var_id') {
	let value = data.var_id;
	if(value == 0) {
	  value = idNb;
	}

	// Input tag:
	attribs = {'type':'hidden', 'name':'variant_'+key+'_'+idNb, 'id':'variant-'+key+'-'+idNb, 'value':value};
	$('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('input', attribs));
	continue;
      }

      // Element label.
      attribs = {'title':Joomla.JText._('COM_KETSHOP_'+key.toUpperCase()+'_TITLE'), 'class':'item-label', 'id':'variant-'+key+'-label-'+idNb};
      $('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('span', attribs));
      $('#variant-'+key+'-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_'+key.toUpperCase()+'_LABEL'));

      // Input tag:
      attribs = {'type':'text', 'name':'variant_'+key+'_'+idNb, 'id':'variant-'+key+'-'+idNb, 'value':data[key]};

      if(key != 'variant_name' && key != 'published') {
	attribs.class = 'item-small-field';
      }

      if(key == 'published') {
	attribs.type = 'checkbox';

	if(data.published == 1) {
	  attribs.checked = 'checked';
	}
      }

      $('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('input', attribs));

      cellNb++;

      if(cellNb > 6) {
	cellNb = 1;
	rowNb++;
      }
    }

    // Element label.
    /*attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'variant-name-label-'+idNb};
    $('#variant-row-1-cell-1-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-name-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Name input tag:
    attribs = {'type':'text', 'name':'variant_name_'+idNb, 'id':'variant-name-'+idNb, 'value':data.name};
    $('#variant-row-1-cell-1-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_BASE_PRICE_TITLE'), 'class':'item-label', 'id':'variant-baseprice-label-'+idNb};
    $('#variant-row-1-cell-2-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-baseprice-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_BASE_PRICE_LABEL'));

    // Base price input tag:
    attribs = {'type':'text', 'name':'variant_base_price_'+idNb, 'class':'item-small-field', 'id':'variant-base-price-'+idNb, 'value':data.base_price};
    $('#variant-row-1-cell-2-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_SALE_PRICE_TITLE'), 'class':'item-label', 'id':'variant-saleprice-label-'+idNb};
    $('#variant-row-1-cell-3-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-saleprice-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_SALE_PRICE_LABEL'));

    // Sale price input tag:
    attribs = {'type':'text', 'name':'variant_sale_price_'+idNb, 'class':'item-small-field', 'id':'variant-sale-price-'+idNb, 'value':data.sale_price};
    $('#variant-row-1-cell-3-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_STOCK_TITLE'), 'class':'item-label', 'id':'variant-stock-label-'+idNb};
    $('#variant-row-1-cell-4-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-stock-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_STOCK_LABEL'));

    // Stock input tag:
    attribs = {'type':'text', 'name':'variant_stock_'+idNb, 'class':'item-small-field', 'id':'variant-stock-'+idNb, 'value':data.stock};
    $('#variant-row-1-cell-4-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_SALES_TITLE'), 'class':'item-label', 'id':'variant-sales-label-'+idNb};
    $('#variant-row-1-cell-5-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-sales-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_SALES_LABEL'));

    // Sales input tag:
    attribs = {'type':'text', 'name':'variant_sales_'+idNb, 'class':'item-small-field', 'id':'variant-sales-'+idNb, 'value':data.sales};
    $('#variant-row-1-cell-5-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_PUBLISHED_TITLE'), 'class':'item-label', 'id':'variant-published-label-'+idNb};
    $('#variant-row-1-cell-6-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-published-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_PUBLISHED_LABEL'));

    // Published checkbox tag:
    attribs = {'type':'checkbox', 'name':'variant_published_'+idNb, 'id':'variant-published-'+idNb, 'value':'published'};

    if(data.published == 1) {
      attribs.checked = 'checked';
    }

    $('#variant-row-1-cell-6-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_WEIGHT_TITLE'), 'class':'item-label', 'id':'variant-weight-label-'+idNb};
    $('#variant-row-2-cell-1-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-weight-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_WEIGHT_LABEL'));

    // Weight input tag:
    attribs = {'type':'text', 'name':'variant_weight_'+idNb, 'class':'item-small-field', 'id':'variant-weight-'+idNb, 'value':data.weight};
    $('#variant-row-2-cell-1-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_LENGTH_TITLE'), 'class':'item-label', 'id':'variant-length-label-'+idNb};
    $('#variant-row-2-cell-2-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-length-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_LENGTH_LABEL'));

    // Length input tag:
    attribs = {'type':'text', 'name':'variant_length_'+idNb, 'class':'item-small-field', 'id':'variant-length-'+idNb, 'value':data.length};
    $('#variant-row-2-cell-2-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_WIDTH_TITLE'), 'class':'item-label', 'id':'variant-width-label-'+idNb};
    $('#variant-row-2-cell-3-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-width-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_WIDTH_LABEL'));

    // Width input tag:
    attribs = {'type':'text', 'name':'variant_width_'+idNb, 'class':'item-small-field', 'id':'variant-width-'+idNb, 'value':data.width};
    $('#variant-row-2-cell-3-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_HEIGHT_TITLE'), 'class':'item-label', 'id':'variant-height-label-'+idNb};
    $('#variant-row-2-cell-4-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-height-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_HEIGHT_LABEL'));

    // Height input tag:
    attribs = {'type':'text', 'name':'variant_height_'+idNb, 'class':'item-small-field', 'id':'variant-height-'+idNb, 'value':data.height};
    $('#variant-row-2-cell-4-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_AVAILABILITY_DELAY_TITLE'), 'class':'item-label', 'id':'variant-availabilitydelay-label-'+idNb};
    $('#variant-row-2-cell-5-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-availabilitydelay-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_AVAILABILITY_DELAY_LABEL'));

    // Availability delay input tag:
    attribs = {'type':'text', 'name':'variant_availability_delay_'+idNb, 'class':'item-small-field', 'id':'variant-availability-delay-'+idNb, 'value':data.availability_delay};
    $('#variant-row-2-cell-5-'+idNb).append(GETTER.variant.createElement('input', attribs));

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_CODE_TITLE'), 'class':'item-label', 'id':'variant-code-label-'+idNb};
    $('#variant-row-2-cell-6-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-code-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_CODE_LABEL'));

    // Code input tag:
    attribs = {'type':'text', 'name':'variant_code_'+idNb, 'class':'item-small-field', 'id':'variant-code-'+idNb, 'value':data.code};
    $('#variant-row-2-cell-6-'+idNb).append(GETTER.variant.createElement('input', attribs));*/
  }

  selectAttributeItem = function(id, name, idNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].selectItem(id, name, idNb, 'attribute', true);
    // Populates the attribute values with the proper options.
    $.fn.setAttributeOptions(idNb, id);
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

  reverseOrder = function(direction, idNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].reverseOrder(direction, idNb);
  }

})(jQuery);

