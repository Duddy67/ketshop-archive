
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
    $('#jform_modifier').change( function() { $.fn.changeModifierType($('#jform_modifier').val()); });
    $('#jform_target').change( function() { $.fn.changeTargetType($('#jform_target').val()); });
    $('#jform_recipient').change( function() { GETTER.recipient.removeItems(); });
    $('#jform_condition').change( function() { $.fn.changeConditionType($('#jform_condition').val()); });
    //
    $.fn.changePriceruleType($('#jform_type').val());
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
    let priceruleType = $('#jform_type').val();
    let conditionType = $('#jform_condition').val();

    // First ensures that the required items have been selected according 
    // to the price rule configuration.

    // In any case at least one recipient must be selected.
    // In case of catalog price rule type, at least one product must be selected.
    // In case of cart price rule type and no 'total' condition type, at least one product must be selected.
    if(task[0].value != 'pricerule.cancel' && (GETTER.recipient.idNbList.length == 0 ||
       (priceruleType == 'catalog' && GETTER.target.idNbList.length == 0) || 
       (priceruleType == 'cart' && conditionType != 'total_prod_qty' && conditionType != 'total_prod_amount' && GETTER.condition.idNbList.length == 0))) {
      //
      alert(Joomla.JText._('COM_KETSHOP_NO_ITEM_SELECTED'));

      let itemName = 'recipient';
      if(priceruleType == 'catalog' && GETTER.target.idNbList.length == 0) {
	itemName = 'target';
      }
      else if(priceruleType == 'cart' && GETTER.condition.idNbList.length == 0) {
	itemName = 'condition';
      }

      // Shows the dynamic item tab.
      $('.nav-tabs a[href="#pricerule-'+itemName+'"]').tab('show');
      e.preventDefault();
      e.stopPropagation();
      return false;
    }

    // Checks the amount or quantity value.
    for(let key in items) {
      //
      let fields = {'item-name':''};

      if(key == 'condition') {
	if(conditionType == 'product_cat_amount') {
	  fields['item-amount'] = 'unsigned_float';
	}
	else {
	  fields['item-qty'] = 'unsigned_int';
	}
      }

      if(task[0].value != 'pricerule.cancel' && !GETTER[key].validateFields(fields)) {
	// Shows the dynamic item tab.
	$('.nav-tabs a[href="#pricerule-'+key+'"]').tab('show');

	e.preventDefault();
	e.stopPropagation();
	return false;
      }
    }

    let validate = true;

    // Checks for the quantity and amount condition fields.
    if(priceruleType == 'cart') {
      // In case the field was previously not valid.
      $('#jform_condition_qty').removeClass('mandatory');

      if(conditionType == 'total_prod_qty' && !GETTER.condition.checkValueType($('#jform_condition_qty').val(), 'unsigned_int')) {
	validate = false;
	$('#jform_condition_qty').addClass('mandatory');
      }

      // In case the field was previously not valid.
      $('#jform_condition_amount').removeClass('mandatory');

      if(conditionType == 'total_prod_amount' && !GETTER.condition.checkValueType($('#jform_condition_amount').val(),'unsigned_float')) {
	validate = false;
	$('#jform_condition_amount').addClass('mandatory');
      }
    }

    if(task[0].value != 'pricerule.cancel' && !validate) {
      alert(Joomla.JText._('COM_KETSHOP_ALERT_VALUE_TYPE_NOT_VALID'));
      // Shows the dynamic item tab.
      $('.nav-tabs a[href="#pricerule-condition"]').tab('show');

      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }

  // Builds a link to a modal window according to the item type.
  $.fn.createLinkToModal = function(dynamicItemType, idNb) {
    let productType = view = '';

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

    return 'index.php?option=com_ketshop&view='+view+'&layout=modal&tmpl=component&id_nb='+idNb+'&dynamic_item_type='+dynamicItemType+productType;
  }

  // Shows / hides some fields and section according to the selected price rule type.
  $.fn.changePriceruleType = function(type) {
    if(type == 'catalog') {
      $('#target-container').css({'visibility':'visible','display':'block'});
      $('#target-pagination').css({'visibility':'visible','display':'block'});
      // Hides the condition section.
      GETTER.condition.removeItems();
      $('#condition').css({'visibility':'hidden','display':'none'});
      $('a[href="#pricerule-condition"]').parent().css({'visibility':'hidden','display':'none'});
    }
    // cart
    else {
      $('#target-container').css({'visibility':'hidden','display':'none'});
      $('#target-pagination').css({'visibility':'hidden','display':'none'});
      // Shows the condition section.
      $('#condition').css({'visibility':'visible','display':'block'});
      $('a[href="#pricerule-condition"]').parent().css({'visibility':'visible','display':'block'});
      // Sets the condition fields.
      $.fn.changeConditionType($('#jform_condition').val());
    }

    // Sets both the action and target fields.
    $.fn.changeOperationType($('#jform_operation').val());
    $.fn.switchTargetOptions(type);
    $.fn.changeTargetType($('#jform_target').val());
  }

  // Shows / hides some fields from the action section according to the selected operation type.
  $.fn.changeOperationType = function(type) {
    let priceruleType = $('#jform_type').val();
    let modifierType = $('#jform_modifier').val();

    if(priceruleType == 'catalog') {
      $('#jform_application').parent().parent().css({'visibility':'visible','display':'block'});
      $('#jform_modifier').parent().parent().css({'visibility':'visible','display':'block'});

      if((type == '-%' || type == '+%') || (modifierType == 'profit_margin_modifier' && (type == '-' || type == '+'))) {
	$('#jform_application').parent().parent().css({'visibility':'hidden','display':'none'});
      }

      // Sets the application field.
      $.fn.changeModifierType(modifierType);
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

  $.fn.changeModifierType = function(type) {
    let operationType = $('#jform_operation').val();
    let priceRuleType = $('#jform_type').val();

    if((operationType == '-' || operationType == '+') && type == 'profit_margin_modifier') {
      $('#jform_application').parent().parent().css({'visibility':'hidden','display':'none'});
    }
    else if((operationType == '-' || operationType == '+') && type == 'sale_price_modifier') {
      $('#jform_application').parent().parent().css({'visibility':'visible','display':'block'});
    }

    // Unlocks the radio buttons in case meanwhile the situation has changed.
    $('#jform_show_rule').removeClass('readonly disabled');
    $('#jform_show_rule').css('pointer-events','auto');

    if(type == 'profit_margin_modifier' && priceRuleType == 'catalog') {
      // Rule must not be showed when it applies on profit margin.
      $.fn.switchShowRuleBtn();
    }
  }

  $.fn.changeTargetType = function(type) {
    GETTER.target.removeItems();
    let priceRuleType = $('#jform_type').val();

    // Unlocks the radio buttons in case meanwhile the situation has changed.
    $('#jform_show_rule').removeClass('readonly disabled');
    $('#jform_show_rule').css('pointer-events','auto');

    if(type == 'cart_amount' && priceRuleType == 'cart') {
      // A cart rule with a 'cart amount' target cannot be hidden.
      $.fn.switchShowRuleBtn(true);
    }
  }

  $.fn.switchShowRuleBtn = function(yes) {
    if(yes) {
      $('#jform_show_rule1').attr('checked', false);
      $('#jform_show_rule0').attr('checked', 'checked');
      $('label[for="jform_show_rule1"]').removeClass('active btn-danger');
      $('label[for="jform_show_rule0"]').addClass('active btn-success');
    }
    else {
      $('#jform_show_rule0').attr('checked', false);
      $('#jform_show_rule1').attr('checked', 'checked');
      $('label[for="jform_show_rule0"]').removeClass('active btn-success');
      $('label[for="jform_show_rule1"]').addClass('active btn-danger');
    }

    // Locks the radio buttons.
    $('#jform_show_rule').addClass('readonly disabled');
    $('#jform_show_rule').css('pointer-events','none');
  }

  $.fn.changeConditionType = function(type) {
    // First removes all the items.
    GETTER.condition.removeItems();

    // Shows or hides fields according to the condition type.
    if(type == 'total_prod_qty' || type == 'total_prod_amount') {
      // These condition types don't use dynamic items.
      $('#condition-add-button-container').css({'visibility':'hidden','display':'none'});
      $('#condition-pagination').css({'visibility':'hidden','display':'none'});
      $('#jform_logical_opr').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_comparison_opr').parent().parent().css({'visibility':'visible','display':'block'});

      // Shows the value type matching the selected condition type. 
      if(type == 'total_prod_qty') {
	$('#jform_condition_qty').parent().parent().css({'visibility':'visible','display':'block'});
	$('#jform_condition_amount').parent().parent().css({'visibility':'hidden','display':'none'});
	$('#jform_condition_amount').val('');
      }
      else {
	$('#jform_condition_qty').parent().parent().css({'visibility':'hidden','display':'none'});
	$('#jform_condition_qty').val('');
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

  // Enables or disables some target options according to the selected price rule type.
  $.fn.switchTargetOptions = function(priceRuleType) {
    let catalog = ['product', 'product_cat', 'bundle'];
    let cart = ['shipping_cost', 'cart_amount'];
    // Loops through the options.
    $('#jform_target > option').each(function() {
      $(this).prop('disabled', true);
      if(priceRuleType == 'catalog') {
	if(GETTER.target.inArray($(this).val(), catalog)) {
	  $(this).prop('disabled', false);
	}
      }
      else if(GETTER.target.inArray($(this).val(), cart)) {
	$(this).prop('disabled', false);

	// Selects the shipping_cost option by default when the price rule is new.
	if($('#jform_id').val() == 0 && $(this).val() == 'shipping_cost') {
	  $(this).prop('selected', true);
	}
      }
    });
    // Updates the Chosen plugin.
    $('#jform_target').trigger('liszt:updated');
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
      data = {'item_id':'', 'item_name':'', 'var_id':0};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'target-label-'+idNb};
    $('#target-row-1-cell-1-'+idNb).append(GETTER.target.createElement('span', attribs));
    $('#target-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected target id (ie: product, product category or bundle id).
    attribs = {'type':'hidden', 'name':'target_item_id_'+idNb, 'id':'target-item-id-'+idNb, 'value':data.item_id};
    let elem = GETTER.target.createElement('input', attribs);
    $('#target-row-1-cell-1-'+idNb).append(elem);

    // Creates the hidden input element to store the product variant id used in case of
    // a product or bundle target
    attribs = {'type':'hidden', 'name':'target_var_id_'+idNb, 'id':'target-var-id-'+idNb, 'value':data.var_id};
    elem = GETTER.target.createElement('input', attribs);
    $('#target-row-1-cell-1-'+idNb).append(elem);

    // Builds the link to the modal window.
    let url = $('#root-location').val()+'administrator/'+$.fn.createLinkToModal('target', idNb);
    let button = GETTER.target.createButton('select', idNb, url);
    $('#target-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'target-itemname-label-'+idNb};
    $('#target-row-1-cell-2-'+idNb).append(GETTER.target.createElement('span', attribs));
    $('#target-itemname-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'class':'item-large-field', 'id':'target-item-name-'+idNb, 'value':data.item_name};
    elem = GETTER.target.createElement('input', attribs);
    $('#target-row-1-cell-2-'+idNb).append(elem);
  }

  populateConditionItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'item_id':'', 'var_id':0, 'item_name':'', 'operator':'', 'item_qty':'', 'item_amount':''};
    }

    let conditionType = $('#jform_condition').val();
    // These 2 following condition types don't use dynamic items. They are based on a
    // single operator and a single value.
    if(conditionType == 'total_prod_qty' || conditionType == 'total_prod_amount') {
      // Deletes the item previously created by the Omkod library.
      GETTER.condition.removeItems();
    }
    // Condition types which use dynamic items.
    else {
      // Element label.
      let attribs = {'class':'item-space', 'id':'condition-label-'+idNb};
      $('#condition-row-1-cell-1-'+idNb).append(GETTER.condition.createElement('span', attribs));
      $('#condition-label-'+idNb).html('&nbsp;');

      // Creates the hidden input element to store the selected condition id (ie: product, product category or bundle id).
      attribs = {'type':'hidden', 'name':'condition_item_id_'+idNb, 'id':'condition-item-id-'+idNb, 'value':data.item_id};
      let elem = GETTER.condition.createElement('input', attribs);
      $('#condition-row-1-cell-1-'+idNb).append(elem);

      // Creates the hidden input element to store the product variant id used in case 
      // condition is applied on a product or a bundle. 
      attribs = {'type':'hidden', 'name':'condition_var_id_'+idNb, 'id':'condition-var-id-'+idNb, 'value':data.var_id};
      elem = GETTER.condition.createElement('input', attribs);
      $('#condition-row-1-cell-1-'+idNb).append(elem);

      // Builds the link to the modal window.
      let url = $('#root-location').val()+'administrator/'+$.fn.createLinkToModal('condition', idNb);
      let button = GETTER.condition.createButton('select', idNb, url);
      $('#condition-row-1-cell-1-'+idNb).append(button);

      // Element label.
      attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'condition-itemname-label-'+idNb};
      $('#condition-row-1-cell-2-'+idNb).append(GETTER.condition.createElement('span', attribs));
      $('#condition-itemname-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

      attribs = {'type':'text', 'disabled':'disabled', 'class':'item-large-field', 'id':'condition-item-name-'+idNb, 'value':data.item_name};
      elem = GETTER.condition.createElement('input', attribs);
      $('#condition-row-1-cell-2-'+idNb).append(elem);

      // Element label.
      attribs = {'title':Joomla.JText._('COM_KETSHOP_COMPARISON_OPERATOR_TITLE'), 'class':'item-label', 'id':'condition-operator-label-'+idNb};
      $('#condition-row-1-cell-3-'+idNb).append(GETTER.condition.createElement('span', attribs));
      $('#condition-operator-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_COMPARISON_OPERATOR_LABEL'));

      // Select tag:
      attribs = {'name':'condition_comparison_opr_'+idNb, 'class':'item-small-field', 'id':'condition-comparison-opr-'+idNb};
      elem = GETTER.condition.createElement('select', attribs);
      let optionValues = {'e':'=', 'gt':'&gt;', 'lt':'&lt;', 'gtoet':'&gt;=', 'ltoet':'&lt;='};
      let options = '';

      for(let key in optionValues) {
	let value = key;
	let selected = '';

	if(data.operator == value) {
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
      attribs = {'type':'text', 'name':'condition_item_'+valueType+'_'+idNb, 'class':'item-small-field', 'id':'condition-item-'+valueType+'-'+idNb, 'value':itemValue};
      $('#condition-row-1-cell-4-'+idNb).append(GETTER.condition.createElement('input', attribs));
    }

  }

  selectItem = function(id, name, idNb, dynamicItemType, var_id) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].selectItem(id, name, idNb, 'item', true);

    // Sets the variant id in case of product or bundle selecting.
    if(var_id !== undefined) {
      document.getElementById(dynamicItemType+'-var-id-'+idNb).value = var_id;
    }
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

  beforeRemoveItem = function(idNb, dynamicItemType) {
    // Execute here possible tasks before the item deletion.
  }

  afterRemoveItem = function(idNb, dynamicItemType) {
    // Execute here possible tasks after the item deletion.
  }

})(jQuery);

