
(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};
  // The dynamic items to create. {item name:nb of cells}
  const items = {'condition':5, 'target':3, 'recipient':3};

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
    $('#pricerule-form').submit( function(e) { validateFields(e); });

    let priceRuleId = $('#jform_id').val();
    let recipientType = $('#jform_recipient').val();
    let conditionType = $('#jform_condition').val();
    let priceRuleType = $('#jform_type').val();
    let targetType = $('#jform_target').val();

    // Prepares then run the Ajax query.
    const ajax = new Omkod.Ajax();
    let params = {'method':'GET', 'dataType':'json', 'indicateFormat':true, 'async':true};
    // Gets the form security token.
    let token = jQuery('#token').attr('name');
    // N.B: Invokes first the ajax() function in the global controller to check the token.
    let data = {[token]:1, 'task':'ajax', 'pricerule_id':priceRuleId, 'pricerule_type':priceRuleType, 'target_type':targetType, 'condition_type':conditionType, 'recipient_type':recipientType};
    ajax.prepare(params, data);
    ajax.process(getAjaxResult);

    // New item.
    if(priceRuleId == 0) {
      // Binds the delivery type select tag to the corresponding function. 
      //$('#jform_delivery_type').change( function() { $.fn.switchDeliveryType($('#jform_delivery_type').val()); });
    }

    $('#jform_type').change( function() { $.fn.changePriceruleType($('#jform_type').val()); });
    $('#jform_operation').change( function() { $.fn.changeOperationType($('#jform_operation').val()); });
    $('#jform_target').change( function() { GETTER.target.removeItems(); });
    $('#jform_recipient').change( function() { GETTER.recipient.removeItems(); });
    $('#jform_condition').change( function() { $.fn.changeConditionType($('#jform_condition').val()); });
    //$.fn.switchDeliveryType(deliveryType);
    $.fn.changePriceruleType($('#jform_type').val());
    $.fn.changeConditionType($('#jform_condition').val());
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

  // Builds a link to a modal window according to the item type.
  $.fn.createLinkToModal = function(dynamicItemType, idNb) {
    var productType = view = '';

    //Check for the value of the item type.
    switch($('#jform_'+dynamicItemType).val()) {
      case 'product':
	view = 'products';
	productType = '&product_type=normal';
	break;
      case 'product_cat':
	view = 'categories';
	break;
      case 'product_cat_amount':
	view = 'categories';
	break;
      case 'customer':
	view = 'users';
	break;
      case 'customer_group':
	view = 'groups';
	break;
      case 'bundle':
	view = 'products';
	productType = '&product_type=bundle';
	break;
    }

    //let Type = type.slice(0,1).toUpperCase() + type.slice(1);

    //return 'index.php?option=com_ketshop&view='+view+'&layout=modal&tmpl=component&function=select'+Type+'Item&id_nb='+idNb+'&dynamic_item_type='+type+productType;
    return 'index.php?option=com_ketshop&view='+view+'&layout=modal&tmpl=component&id_nb='+idNb+'&dynamic_item_type='+dynamicItemType+productType;
  }

  $.fn.changePriceruleType = function(type) {
    if(type == 'catalog') {
      $('#target-container').css({'visibility':'visible','display':'block'});
      $('#target-pagination').css({'visibility':'visible','display':'block'});
    }
    // cart
    else {
      GETTER.target.removeItems();
      $('#target-container').css({'visibility':'hidden','display':'none'});
      $('#target-pagination').css({'visibility':'hidden','display':'none'});
    }
  }

  $.fn.changeOperationType = function(type) {
    let priceruleType = $('#jform_type').val();
    let modifierType = $('#jform_modifier').val();

    if(priceruleType == 'catalog') {
      $('#jform_modifier').parent().parent().css({'visibility':'visibility','display':'block'});
    }
    // cart
    else {
      $('#jform_modifier').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_application').parent().parent().css({'visibility':'hidden','display':'none'});

      if(type == '-' || type == '+') {
	$('#jform_application').parent().parent().css({'visibility':'visible','display':'block'});
      }
    }
  }

  $.fn.changeConditionType = function(type) {
    // First removes all the items.
    GETTER['condition'].removeItems();

    // Shows or hides fields according to the condition type.
    if(type == 'total_prod_qty' || type == 'total_prod_amount') {
      $('#condition-add-button-container').css({'visibility':'hidden','display':'none'});
      $('#condition-pagination').css({'visibility':'hidden','display':'none'});
      $('#jform_logical_opr').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_comparison_opr').parent().parent().css({'visibility':'visible','display':'block'});

      if(type == 'total_prod_qty') {
	$('#jform_condition_qty').parent().parent().css({'visibility':'visible','display':'block'});
	$('#jform_condition_amount').parent().parent().css({'visibility':'hidden','display':'none'});
      }
      else {
	$('#jform_condition_qty').parent().parent().css({'visibility':'hidden','display':'none'});
	$('#jform_condition_amount').parent().parent().css({'visibility':'visible','display':'block'});
      }
    }
    else {
      $('#condition-add-button-container').css({'visibility':'visible','display':'block'});
      $('#condition-pagination').css({'visibility':'visible','display':'block'});
      $('#jform_logical_opr').parent().parent().css({'visibility':'visible','display':'block'});
      $('#jform_comparison_opr').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_condition_qty').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_condition_amount').parent().parent().css({'visibility':'hidden','display':'none'});
    }
  }

  /** Callback functions **/

  populateRecipientItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'item_id':'', 'item_name':''};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'recipient-label-'+idNb};
    $('#recipient-row-1-cell-1-'+idNb).append(GETTER.recipient.createElement('span', attribs));
    $('#recipient-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected recipient id (ie: group or user id).
    attribs = {'type':'hidden', 'name':'recipient_item_id_'+idNb, 'id':'recipient-item-id-'+idNb, 'value':data.item_id};
    let elem = GETTER.recipient.createElement('input', attribs);
    $('#recipient-row-1-cell-1-'+idNb).append(elem);
    // Builds the link to the modal window.
    let url = $('#root-location').val()+'administrator/'+$.fn.createLinkToModal('recipient', idNb);
    let button = GETTER.recipient.createButton('select', idNb, url);
    $('#recipient-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'recipient-itemname-label-'+idNb};
    $('#recipient-row-1-cell-2-'+idNb).append(GETTER.recipient.createElement('span', attribs));
    $('#recipient-itemname-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'id':'recipient-item-name-'+idNb, 'value':data.item_name};
    elem = GETTER.recipient.createElement('input', attribs);
    $('#recipient-row-1-cell-2-'+idNb).append(elem);
  }

  populateTargetItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'item_id':'', 'item_name':''};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'target-label-'+idNb};
    $('#target-row-1-cell-1-'+idNb).append(GETTER.target.createElement('span', attribs));
    $('#target-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected target id (ie: product, product category or bundle id).
    attribs = {'type':'hidden', 'name':'target_item_id_'+idNb, 'id':'target-item-id-'+idNb, 'value':data.item_id};
    let elem = GETTER.target.createElement('input', attribs);
    $('#target-row-1-cell-1-'+idNb).append(elem);
    // Builds the link to the modal window.
    let url = $('#root-location').val()+'administrator/'+$.fn.createLinkToModal('target', idNb);
    let button = GETTER.target.createButton('select', idNb, url);
    $('#target-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'target-itemname-label-'+idNb};
    $('#target-row-1-cell-2-'+idNb).append(GETTER.target.createElement('span', attribs));
    $('#target-itemname-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'id':'target-item-name-'+idNb, 'value':data.item_name};
    elem = GETTER.target.createElement('input', attribs);
    $('#target-row-1-cell-2-'+idNb).append(elem);
  }

  populateConditionItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'item_id':'', 'item_name':'', 'comparison_opr':'', 'item_qty':'', 'item_amount':''};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'condition-label-'+idNb};
    $('#condition-row-1-cell-1-'+idNb).append(GETTER.condition.createElement('span', attribs));
    $('#condition-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected condition id (ie: product, product category or bundle id).
    attribs = {'type':'hidden', 'name':'condition_item_id_'+idNb, 'id':'condition-item-id-'+idNb, 'value':data.item_id};
    let elem = GETTER.condition.createElement('input', attribs);
    $('#condition-row-1-cell-1-'+idNb).append(elem);
    // Builds the link to the modal window.
    let url = $('#root-location').val()+'administrator/'+$.fn.createLinkToModal('condition', idNb);
    let button = GETTER.condition.createButton('select', idNb, url);
    $('#condition-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'condition-itemname-label-'+idNb};
    $('#condition-row-1-cell-2-'+idNb).append(GETTER.condition.createElement('span', attribs));
    $('#condition-itemname-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'id':'condition-item-name-'+idNb, 'value':data.item_name};
    elem = GETTER.condition.createElement('input', attribs);
    $('#condition-row-1-cell-2-'+idNb).append(elem);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_COMPARISON_OPERATOR_TITLE'), 'class':'item-label', 'id':'condition-operator-label-'+idNb};
    $('#condition-row-1-cell-3-'+idNb).append(GETTER.condition.createElement('span', attribs));
    $('#condition-operator-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_COMPARISON_OPERATOR_LABEL'));

    // Select tag:
    attribs = {'name':'condition_comparison_opr_'+idNb, 'id':'condition-comparison-opr-'+idNb};
    elem = GETTER.condition.createElement('select', attribs);
    let optionValues = {'e':'=', 'gt':'&gt;', 'lt':'&lt;', 'gtoet':'&gt;=', 'ltoet':'&lt;='};
    let options = '';

    for(let key in optionValues) {
      let value = key;
      let selected = '';

      if(data.comparison_opr == value) {
	selected = 'selected="selected"';
      }

      options += '<option value="'+value+'" '+selected+'>'+optionValues[key]+'</option>';
    }

    $('#condition-row-1-cell-3-'+idNb).append(elem);
    $('#condition-comparison-opr-'+idNb).html(options);
    // Update the chosen plugin.
    $('#condition-comparison-opr-'+idNb).chosen();

    let valueType = 'qty'; 
    let itemValue = data.item_qty;
    if($('#jform_condition').val() == 'product_cat_amount') {
      valueType = 'amount'; 
      itemValue = data.item_amount;
    }

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_'+valueType.toUpperCase()+'_TITLE'), 'class':'item-label', 'id':'condition-item'+valueType+'-label-'+idNb};
    $('#condition-row-1-cell-4-'+idNb).append(GETTER.condition.createElement('span', attribs));
    $('#condition-item'+valueType+'-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_'+valueType.toUpperCase()+'_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'condition_item_'+valueType+'_'+idNb, 'id':'condition-item-'+valueType+'-'+idNb, 'value':itemValue};
    $('#condition-row-1-cell-4-'+idNb).append(GETTER.condition.createElement('input', attribs));

  }

  selectItem = function(id, name, idNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].selectItem(id, name, idNb, 'item', true);
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

})(jQuery);

