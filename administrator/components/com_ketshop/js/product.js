(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};
  // The dynamic items to create. {item name:nb of cells}
  const items = {'attribute':3, 'image':[4,1], 'variant':[8,6,6,1]};

  // Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    // The input element containing the root location.
    let rootLocation = $('#root-location').val();
    let productId = $('#jform_id').val();
    let productType = $('#product-type').val();
    let isAdmin = $('#is-admin').val();

    if(productType == 'bundle') {
      items.bundle = 4;
    }

    // Loops through the item array to instanciate all of the dynamic item objects.
    for(let key in items) {
      // Sets the dynamic item properties.
      let props = {'component':'ketshop', 'item':key, 'rootLocation':rootLocation, 'Chosen':true, 'nbItemsPerPage':5};

      props.rowsCells = items[key];
      props.ordering = true;

      if(key == 'attribute' || key == 'bundle') {
	// Some properties are different for both attribute and bundle items.
	props.rowsCells = [items[key]];
	props.ordering = false;
      }

      // Stores the newly created object.
      GETTER[key] = new Omkod.DynamicItem(props);
    }

    if(productId == 0 || productType == 'bundle') {
      // Uses only the basic variant.
      $('#variant-add-button-container').remove();

      if(productType == 'bundle') {
      }
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

      if(GETTER.variant.idNbList.length == 0) {
	GETTER.variant.createItem();
      }
    }
    else {
      alert('Error: '+result.message);
    }
  }

  validateFields = function(e) {
    let task = document.getElementsByName('task');
    let productType = $('#product-type').val();

    for(let key in items) {
      let fields = null; 
      if(key == 'attribute') {
	fields = {'attribute-name':''};
      }
      else if(key == 'variant') {
	fields = {'base_price':'unsigned_float', 'sale_price':'unsigned_float', 'min_stock_threshold':'unsigned_int', 'max_stock_threshold':'unsigned_int', 'min_quantity':'unsigned_int', 'max_quantity':'unsigned_int'};

	if($('#product-type').val() == 'normal') {
	  fields.stock = 'unsigned_int';
	  fields.availability_delay = 'unsigned_int';
	}

	if($('#jform_shippable').val() == 1) {
	  // Weight and dimensions are mandatory for shippable products.
	  fields.weight = 'unsigned_float';
	  fields.length = 'unsigned_float';
	  fields.width = 'unsigned_float';
	  fields.height = 'unsigned_float';
	}

	if(GETTER.variant.idNbList.length > 1) {
	  // Name field is mandatory if the product has more than one variant.
	  fields.name = '';
	}

	// Gets all the attributes from all of the variant items.
	let attributes = $('*[id^="variant-attribute-value-"]');

	// Loops through the variant list.
	for(let i = 0; i < GETTER.variant.idNbList.length; i++) {
	  let regex = new RegExp('-'+GETTER.variant.idNbList[i]+'$');

	  attributes.each(function() {
	    // Checks that this attribute is linked to this variant item.
	    if(regex.test($(this).attr('id'))) {
	      // Removes the "variant-" characters from the beginning of the string.
	      let selectId = $(this).attr('id').substr(8);
	      // Removes the hyphen and the id number from the end of the string.
	      selectId = selectId.replace(regex, '');
	      // Adds the field for check.
	      fields[selectId] = '';
	    }
	  });
	}
      }
      // image
      else {
	fields = {'src':''};
      }

      if(task[0].value != 'product.cancel' && !GETTER[key].validateFields(fields)) {
	// Shows the dynamic item tab.
	$('.nav-tabs a[href="#product-'+key+'"]').tab('show');

	e.preventDefault();
	e.stopPropagation();
	return false;
      }
    }
  }


  setStockValue = function(idNb) {
    if($('input[name=variant_stock_subtract_'+idNb+']:checked', '#product-form').val() == 1) {
      $('#variant-stock-'+idNb).val(0);
      $('#variant-stock-'+idNb).removeAttr('disabled');
      $('#variant-stock-'+idNb).removeAttr('readonly');
      $('#variant-stock-'+idNb).removeClass('readonly');
    }
    else {
      $('#variant-stock-'+idNb).val('∞');
      $('#variant-stock-'+idNb).attr('disabled', 'disabled');
      $('#variant-stock-'+idNb).attr('readonly', 'readonly');
      $('#variant-stock-'+idNb).addClass('readonly');
    }
  }

  $.fn.setDefaultVariant = function() {
      // Hides the "Remove" button from the very first variant item. 
      $('#variant-row-1-cell-8-'+GETTER.variant.idNbList[0]).css({'visibility':'hidden'});
      // Checks then disables the publishing option.
      $('#variant-published-'+GETTER.variant.idNbList[0]).attr('checked', true);
      $('#variant-published-'+GETTER.variant.idNbList[0]).attr('disabled', 'disabled');

      if(GETTER.variant.idNbList.length > 1) {
	// In case of reversing order between the first two items, the publishing option
	// of the second item must me enabled again. 
	$('#variant-row-1-cell-8-'+GETTER.variant.idNbList[1]).css({'visibility':'visible'});
	$('#variant-published-'+GETTER.variant.idNbList[1]).removeAttr('disabled');
	//
	$('#variant-name-'+GETTER.variant.idNbList[0]).removeAttr('disabled');
      }
      else {
	$('#variant-name-'+GETTER.variant.idNbList[0]).attr('disabled', 'disabled');
      }
  }

  $.fn.loadAttributeOptions = function(idNb, attribId, data) {
    // Gets the options corresponding to the given attribute id.
    let attributeOptions = ketshop.attributeOptions[attribId].options;
    let multiselect = ketshop.attributeOptions[attribId].multiselect;
    let options = '';

    // Deletes all the possible previous options.
    $('#variant-attribute-value-'+attribId+'-'+idNb).empty();

    // Single select mode.
    if(multiselect == 0) {
      $('#variant-attribute-value-'+attribId+'-'+idNb).removeAttr('multiple');
      // Creates the very first list option.
      options += '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';
    }
    // Multi select mode.
    else {
      $('#variant-attribute-value-'+attribId+'-'+idNb).attr('multiple', 'true');
      // Adds brackets to the element name.
      let inputName = $('#variant-attribute-value-'+attribId+'-'+idNb).attr('name');
      $('#variant-attribute-value-'+attribId+'-'+idNb).attr('name', inputName+'[]');
    }

    // Destroys the div structure previously created by the Chosen plugin. 
    $('#variant-attribute-value-'+attribId+'-'+idNb).chosen('destroy');

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
    $('#variant-attribute-value-'+attribId+'-'+idNb).append(options);
    // Recreates a new Chosen div structure.
    $('#variant-attribute-value-'+attribId+'-'+idNb).chosen();
  }

  $.fn.addVariantAttribute = function(idNb, attribId, attribName) {
    let rowNb = items.variant.length;
    let attribs = {'class':'variant-attribute-container', 'id':'variant-attribute-container-'+attribId+'-'+idNb};
    $('#variant-row-'+rowNb+'-cell-1-'+idNb).append(GETTER.variant.createElement('div', attribs));

    attribs = {'title':attribName, 'class':'item-label', 'id':'variant-attribute-'+attribId+'-label-'+idNb};
    $('#variant-attribute-container-'+attribId+'-'+idNb).append(GETTER.variant.createElement('span', attribs));
    $('#variant-attribute-'+attribId+'-label-'+idNb).text(attribName);

    // Creates the select tag:
    attribs = {'name':'variant_attribute_value_'+attribId+'_'+idNb, 'id':'variant-attribute-value-'+attribId+'-'+idNb};
    elem = GETTER.attribute.createElement('select', attribs);
    $('#variant-attribute-container-'+attribId+'-'+idNb).append(elem);
  }


  /** Callback functions **/

  populateAttributeItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'attribute_id':'', 'attribute_name':''};
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
      data = {'id_nb':idNb, 'name':'', 'base_price':'', 'sale_price':'', 'stock':'', 'sales':'', 'published':0, 'stock_subtract':1, 'allow_order':1, 'min_stock_threshold':5, 'max_stock_threshold':20, 'min_quantity':1, 'max_quantity':20, 'weight':'', 'length':'', 'width':'', 'height':'', 'code':'', 'availability_delay':'', 'attributes':[]};
    }

    let rowNb = 1;
    let cellNb = 1;
    let attribs = null;
    let productType = $('#product-type').val();

    // Builts the variant fields.

    for(let key in data) {
      if(key == 'id_nb') {
	// Each item has a specific id number stored in a hidden input.
	attribs = {'type':'hidden', 'name':'variant_'+key+'_'+idNb, 'id':'variant-'+key+'-'+idNb, 'value':data.id_nb};
	$('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('input', attribs));
	// Skips to the next data variable.
	continue;
      }

      // Skips the unneeded values coming from the MySQL query.
      if(key == 'var_id' || key == 'prod_id') {
	continue;
      }

      if(key == 'attributes') {
	// Will be treated later. Skips to the next data variable.
	continue;
      }

      // Creates the element label.
      attribs = {'title':Joomla.JText._('COM_KETSHOP_'+key.toUpperCase()+'_TITLE'), 'class':'item-label', 'id':'variant-'+key+'-label-'+idNb};
      $('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('span', attribs));
      $('#variant-'+key+'-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_'+key.toUpperCase()+'_LABEL'));

      // Creates the input tag:
      attribs = {'type':'text', 'name':'variant_'+key+'_'+idNb, 'id':'variant-'+key+'-'+idNb, 'value':data[key]};

      // Adjusts some attributes according to the field.

      if(key != 'name' && key != 'published') {
	attribs.class = 'item-small-field';
      }

      if(key == 'published') {
	attribs.type = 'checkbox';

	if(data.published == 1) {
	  attribs.checked = 'checked';
	}
      }

      if(key == 'sales' || (productType == 'bundle' && (key == 'stock' || key == 'availability_delay'))) {
	attribs.class += ' readonly';
	attribs.readonly = 'readonly';
      }

      // Yes/no radio buttons.
      if(key == 'stock_subtract' || key == 'allow_order') {
	let radioLabel = {'class':'radio-label', 'id':'variant-'+key+'-yes-label-'+idNb};
	$('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('span', radioLabel));
	$('#variant-'+key+'-yes-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_YESNO_1'));

	attribs.type = 'radio';
	attribs.class = 'radio-item';
	attribs.value = 1;
	attribs.id = 'variant-'+key+'-1-'+idNb;

	if(key == 'stock_subtract') {
	  attribs.onchange = 'setStockValue('+idNb+');';

	  if(productType == 'bundle') {
	    attribs.disabled = 'disabled';
	  }
	}
	else {
	  delete attribs.onchange;
	}

	if(data[key] == 1) {
	  attribs.checked = 'checked';
	}

	$('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('input', attribs));

	radioLabel = {'class':'radio-label', 'id':'variant-'+key+'-no-label-'+idNb};
	$('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('span', radioLabel));
	$('#variant-'+key+'-no-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_YESNO_0'));

	attribs.value = 0;
	attribs.id = 'variant-'+key+'-0-'+idNb;
	delete attribs.checked;

	if(data[key] == 0) {
	  attribs.checked = 'checked';
	}
      }

      // Inserts the element into the item structure.
      $('#variant-row-'+rowNb+'-cell-'+cellNb+'-'+idNb).append(GETTER.variant.createElement('input', attribs));

      // Sets the stock value to 'infinite'.
      if(key == 'stock_subtract' && data[key] == 0) {
	setStockValue(idNb);
      }

      cellNb++;

      // Resets the structure variables.
      if(cellNb > 6) {
	cellNb = 1;
	rowNb++;
      }
    }

    // Gets the id and name of the attributes of the product coming from the database.
    let productAttributes = ketshop.productAttributes;

    // In case of a new dynamic item the product attributes are taken from the current
    // attribute dynamic items. 
    if(data.attributes.length == 0) {
      // Initializes the attribute array.
      productAttributes = [];
      // Loops through the current product attribute list.
      for(let i = 0; i < GETTER.attribute.idNbList.length; i++) {
	// Gets the name and id of each attribute then store them.
	let attribId = $('#attribute-attribute-id-'+GETTER.attribute.idNbList[i]).val();
	let attribName = $('#attribute-attribute-name-'+GETTER.attribute.idNbList[i]).val();
	productAttributes.push({'attribute_id':attribId, 'attribute_name':attribName});
      }
    }

    //
    for(let i = 0; i < productAttributes.length; i++) {
      //
      let attribId = productAttributes[i].attribute_id;
      let attribName = productAttributes[i].attribute_name;

      $.fn.addVariantAttribute(idNb, attribId, attribName);

      // Loads the corresponding options.

      // Existing dynamic item, loads the options and set the selected option(s).
      for(let j = 0; j < data.attributes.length; j++) {
	// Searches the given data for the matching attribute value.
	if(data.attributes[j].attrib_id == attribId) {
	  $.fn.loadAttributeOptions(idNb, attribId, data.attributes[j]);
	}
      }

      // New dynamic item, just loads the options.
      if(data.attributes.length == 0) {
	$.fn.loadAttributeOptions(idNb, attribId, undefined);
      }

      cellNb++;
    }

    $.fn.setDefaultVariant(); 
  }

  populateBundleItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'product_id':'', 'var_id':'', 'product_name':'', 'quantity':1};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'bundle-label-'+idNb};
    $('#bundle-row-1-cell-1-'+idNb).append(GETTER.bundle.createElement('span', attribs));
    $('#bundle-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected product id and its variant id.
    attribs = {'type':'hidden', 'name':'bundle_product_id_'+idNb, 'id':'bundle-product-id-'+idNb, 'value':data.product_id};
    let elem = GETTER.bundle.createElement('input', attribs);
    $('#bundle-row-1-cell-1-'+idNb).append(elem);

    attribs = {'type':'hidden', 'name':'bundle_var_id_'+idNb, 'id':'bundle-var-id-'+idNb, 'value':data.var_id};
    elem = GETTER.bundle.createElement('input', attribs);
    $('#bundle-row-1-cell-1-'+idNb).append(elem);

    let url = $('#root-location').val()+'administrator/index.php?option=com_ketshop&view=products&layout=modal&tmpl=component&function=selectBundleItem&dynamic_item_type=bundle&product_type=normal&id_nb='+idNb;
    let button = GETTER.attribute.createButton('select', idNb, url);
    $('#bundle-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'bundle-name-label-'+idNb};
    $('#bundle-row-1-cell-2-'+idNb).append(GETTER.bundle.createElement('span', attribs));
    $('#bundle-name-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'id':'bundle-product-name-'+idNb, 'class':'item-large-field', 'value':data.product_name};
    elem = GETTER.bundle.createElement('input', attribs);
    $('#bundle-row-1-cell-2-'+idNb).append(elem);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_QUANTITY_TITLE'), 'class':'item-label', 'id':'bundle-quantity-label-'+idNb};
    $('#bundle-row-1-cell-3-'+idNb).append(GETTER.bundle.createElement('span', attribs));
    $('#bundle-quantity-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_QUANTITY_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'bundle_quantity_'+idNb, 'id':'bundle-quantity-'+idNb, 'class':'item-tiny-field', 'value':data.quantity};
    $('#bundle-row-1-cell-3-'+idNb).append(GETTER.bundle.createElement('input', attribs));
  }

  selectAttributeItem = function(id, name, idNb, dynamicItemType) {
    // First loops through the current variant list.
    for(let i = 0; i < GETTER.attribute.idNbList.length; i++) {
      // Checks for possible duplicate product attribute.
      if($('#attribute-attribute-id-'+GETTER.attribute.idNbList[i]).val() == id) {
	alert(Joomla.JText._('COM_KETSHOP_WARNING_DUPLICATE_PRODUCT_ATTRIBUTE'));
	return;
      }
    }

    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].selectItem(id, name, idNb, dynamicItemType, true);
    // Populates the attribute values with the proper options.
    let attribId = id;
    let attribName = name;

    // Loops through the current variant list.
    for(let i = 0; i < GETTER.variant.idNbList.length; i++) {
      let idNb = GETTER.variant.idNbList[i];
      $.fn.addVariantAttribute(idNb, attribId, attribName);
      //
      $.fn.loadAttributeOptions(idNb, attribId);
    }
  }

  selectBundleItem = function(id, name, idNb, dynamicItemType, var_id) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].selectItem(id, name, idNb, 'product', true);

    // Sets the variant id of the selected bundle product.
    document.getElementById('bundle-var-id-'+idNb).value = var_id;
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

  reverseOrder = function(direction, idNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].reverseOrder(direction, idNb);

    $.fn.setDefaultVariant(); 
  }

  beforeRemoveItem = function(idNb, dynamicItemType) {
    // Execute here possible tasks before the item deletion.
    if(dynamicItemType == 'attribute') {
      // Gets the id of the attribute which is about to be deleted.
      let attribId = $('#attribute-attribute-id-'+idNb).val();

      if(attribId != '') {
	// Loops through the current variant list.
	for(let i = 0; i < GETTER.variant.idNbList.length; i++) {
	  // Removes the attribute from each variant item. 
	  $('#variant-attribute-container-'+attribId+'-'+GETTER.variant.idNbList[i]).remove();
	}
      }
    }
  }

  afterRemoveItem = function(idNb, dynamicItemType) {
    // Execute here possible tasks after the item deletion.
    if(dynamicItemType == 'variant') {
      $.fn.setDefaultVariant(); 

      if(GETTER.variant.idNbList.length == 1) {
	$('#variant-name-'+GETTER.variant.idNbList[0]).val('');
      }
    }
  }

})(jQuery);

