
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Create a container for each price rule item type.
    $('#condition').getContainer();
    $('#target').getContainer();
    $('#recipient').getContainer();

    //Set as function the global variable previously declared in edit.php file.
    checkPriceRule = $.fn.checkPriceRule;

    //Get the price rule id.
    var priceRuleId = $('#jform_id').val();
    //If the price rule item exists we need to get the data of the dynamical items.
    if(priceRuleId != 0) {
      //Restore the form to its original state filled with the initial data 
      //in case the user changes some value then press the F5 key.
      //It also allows to get the price rule type directly from the form tag.
      $('#pricerule-form')[0].reset();
      //Gets the price rule type previously set and the target value as well. 
      var priceRuleType = $('#jform_type').val();
      var targetType = $('#jform_target').val();
      $.fn.initForm(priceRuleType);
      //Target select tag options are destroyed then rebuild during the
      //initialisation. So we must set target select tag again to the correct value. 
      $('#jform_target').val(targetType);

      var recipientType = $('#jform_recipient').val();
      var conditionType = $('#jform_condition').val();

      //Gets the token's name as value.
      var token = $('#token').attr('name');
      //Sets up the ajax query.
      var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'pricerule_id':priceRuleId, 'pricerule_type':priceRuleType, 'target_type':targetType, 'condition_type':conditionType, 'recipient_type':recipientType};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {

	    //Create an item type for each result retrieved from the database.
	    if(priceRuleType == 'cart') {
	      $.each(results.data.condition, function(i, result) { $.fn.createItem('condition', result); });
	    } else { //catalog
	      $.each(results.data.target, function(i, result) { $.fn.createItem('target', result); });
	    }
	    //Recipient items are common to both of the price rule types.
	    $.each(results.data.recipient, function(i, result) { $.fn.createItem('recipient', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    } else { //Price rule item is new.
      //Get the current price rule type.
      var priceRuleType = $('#jform_type').val();
      $.fn.initForm(priceRuleType);
    }

    //Bind some select tags to functions which set some of the price rule tags
    //whenever a different option is selected.
    $('#jform_type').change( function() { $.fn.changeType($('#jform_type').val()); });
    $('#jform_condition').change( function() { $.fn.changeCondition(); });
    $('#jform_target').change( function() { $.fn.changeTarget(); });
    $('#jform_recipient').change( function() { $.fn.changeRecipient(); });
    $('#jform_operation').change( function() { $.fn.setTags(); });
    $('#jform_modifier').change( function() { $.fn.setTags(); });

    //
    $.fn.setTags();
  });


  $.fn.createRecipientItem = function(idNb, data) {
    //Create the hidden input tag for the recipient id.
    var properties = {'type':'hidden', 'name':'recipient_id_'+idNb, 'id':'recipient-id-'+idNb, 'value':data.id};
    $('#recipient-item-'+idNb).createHTMLTag('<input>', properties);

    //Set the view to use according to the recipient type (customer or customer_group).
    var view = 'users';
    //Check for the recipient type.
    if($('#jform_recipient').val() == 'customer_group') {
      view = 'groups';
    }
    //Create the select button.
    var linkToModal = $.fn.createLinkToModal('recipient', idNb);
    $('#recipient-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
    $('#recipient-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#recipient-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'recipient-name-'+idNb, 'value':data.name};
    $('#recipient-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');
    //Create the removal button.
    $('#recipient-item-'+idNb).createButton('remove');
  };


  $.fn.createTargetItem = function(idNb, data) {
    //Create the hidden input tag for the target id.
    var properties = {'type':'hidden', 'name':'target_id_'+idNb, 'id':'target-id-'+idNb, 'value':data.id};
    $('#target-item-'+idNb).createHTMLTag('<input>', properties);

    //Create the select button.
    var linkToModal = $.fn.createLinkToModal('target', idNb);
    $('#target-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
    $('#target-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#target-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'target-name-'+idNb, 'value':data.name};
    $('#target-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');
    //Create the removal button.
    $('#target-item-'+idNb).createButton('remove');
  };


  $.fn.createConditionItem = function(idNb, data) {
    //Get the value of the item type.
    var valueType = $('#jform_condition').val();

    //Create the hidden input tag for the condition id.
    var properties = {'type':'hidden', 'name':'condition_id_'+idNb, 'id':'condition-id-'+idNb, 'value':data.id};
    $('#condition-item-'+idNb).createHTMLTag('<input>', properties);

    //Note: The total_prod_ type conditions don't need select and remove buttons as they're unique
    if(valueType != 'total_prod_qty' && valueType != 'total_prod_amount') {
      //Create the select button.
      var linkToModal = $.fn.createLinkToModal('condition', idNb);
      $('#condition-item-'+idNb).createButton('select', '#', linkToModal);

      //Create the "name" label.
      properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE')};
      $('#condition-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
      $('#condition-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

      // Create a dummy text field to store the name.
      properties = {'type':'text', 'disabled':'disabled', 'id':'condition-name-'+idNb, 'value':data.name};
      $('#condition-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');
      //Create the removal button.
      $('#condition-item-'+idNb).createButton('remove');
    } else { //total_prod_amount or total_prod_qty
      //Check if an empty item has already been created during the price rule
      //initialisation.
      if(idNb > 1) {
	//As cart_amount condition item is unique, we just set the item 
	//previously created then we leave the function to prevent another
	//operator and amount field to be created. 

	//Set the selected option.
	$('#operator-1 option[value="'+data.operator+'"]').attr('selected', true);
	//Set the amount or quantity value accordingly.
	if(valueType == 'total_prod_amount') {
	  //Format the amount.
	  data.item_amount = parseFloat(data.item_amount).toFixed(2);
	  $('#condition-item-amount-1').val(data.item_amount);
	}
	else {
	  $('#condition-item-quantity-1').val(data.item_qty);
	}

	//Remove the second condition item div which has just been created
	//by the createItem function.
	$('#condition-item-'+idNb).remove();

	//We don't go further or a second condition item will be created.
	return;
      }
    }

    //Create the "operator" label (Note: Operator tag is common to each condition types).
    properties = {'title':Joomla.JText._('COM_KETSHOP_COMPARISON_OPERATOR_TITLE')};
    $('#condition-item-'+idNb).createHTMLTag('<span>', properties, 'operator-select-label');
    $('#condition-item-'+idNb+' .operator-select-label').text(Joomla.JText._('COM_KETSHOP_COMPARISON_OPERATOR_LABEL'));
    //Create the operator drop down list.
    properties = {'name':'operator_'+idNb, 'id':'operator-'+idNb};
    $('#condition-item-'+idNb).createHTMLTag('<select>', properties, 'operator-select');
    //Set values and texts option.
    //Important: We don't use real comparison signs in option values as < sign
    //causes problem because of the < and > html tags.
    //e: Equal, gt: Greater Than, lt: Lower Than, gtoet: Greater Than Or Equal To,
    //ltoet: Lower Than Or Equal To.
    var options = '<option value="e">=</option><option value="gt">&gt;</option><option value="lt">&lt;</option>';
    options += '<option value="gtoet">&gt;=</option><option value="ltoet">&lt;=</option>';
    $('#operator-'+idNb).html(options);

    if(data.operator !== '') {
      //Set the selected option.
      $('#operator-'+idNb+' option[value="'+data.operator+'"]').attr('selected', true);
    }

    //Create a quantity or amount label and input tags according to the type of
    //the value.
    if(valueType == 'total_prod_amount' || valueType == 'product_cat_amount') {
      //Create an amount label.
      properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_AMOUNT_TITLE')};
      $('#condition-item-'+idNb).createHTMLTag('<span>', properties, 'item-amount-label');
      $('#condition-item-'+idNb+' .item-amount-label').text(Joomla.JText._('COM_KETSHOP_ITEM_AMOUNT_LABEL'));

      //Format item amount if any.
      if(data.item_amount !== '') {
	data.item_amount = parseFloat(data.item_amount).toFixed(2);
      }
      //Create an text input to store the amount to compare.
      properties = {'type':'text', 'name':'condition_item_amount_'+idNb, 'id':'condition-item-amount-'+idNb, 'value':data.item_amount};
      $('#condition-item-'+idNb).createHTMLTag('<input>', properties, 'item-amount');
    } else { //The rest of the condition types are compared against quantity.
      //Create a quantity label.
      properties = {'title':Joomla.JText._('COM_KETSHOP_ITEM_QUANTITY_TITLE')};
      $('#condition-item-'+idNb).createHTMLTag('<span>', properties, 'item-quantity-label');
      $('#condition-item-'+idNb+' .item-quantity-label').text(Joomla.JText._('COM_KETSHOP_ITEM_QUANTITY_LABEL'));

      //Create a text input to store the quantity to compare.
      properties = {'type':'text', 'name':'condition_item_qty_'+idNb,'id':'condition-item-quantity-'+idNb, 'value':data.item_qty};
      $('#condition-item-'+idNb).createHTMLTag('<input>', properties, 'item-quantity');
    }
  };


  //Build a link to a modal window according to the item type.
  $.fn.createLinkToModal = function(type, idNb) {
    var productType = view = '';

    //Check for the value of the item type.
    switch($('#jform_'+type).val()) {
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

    return 'index.php?option=com_ketshop&view='+view+'&layout=modal&tmpl=component&id_nb='+idNb+'&type='+type+productType;
  };


  //Set and initialize some of the price rule tags according to the price rule type.
  $.fn.initForm = function(priceRuleType) {
    //Display/hide panels according to the price rule type.
    if(priceRuleType == 'catalog') {
      //Hide the condition item div and tab. 
      $('#condition').css({'visibility':'hidden','display':'none'});
      $('a[href="#pricerule-condition"]').parent().css({'visibility':'hidden','display':'none'});
      //Display the modifier tag.
      $('#jform_modifier-lbl').css({'visibility':'visible','display':'block'});
      //$('#jform_modifier').css({'visibility':'visible','display':'block'}); //Managed by Chosen plugin.
      $('#jform_modifier_chzn').css({'visibility':'visible','display':'block'}); //Chosen plugin.
    }
    else { //cart 
      //Display the condition item div and tab. 
      $('#condition').css({'visibility':'visible','display':'block'});
      $('a[href="#pricerule-condition"]').parent().css({'visibility':'visible','display':'block'});
      //Hide the modifier tag.
      $('#jform_modifier-lbl').css({'visibility':'hidden','display':'none'});
      //$('#jform_modifier').css({'visibility':'hidden','display':'none'}); //Managed by Chosen plugin.
      $('#jform_modifier_chzn').css({'visibility':'hidden','display':'none'}); //Chosen plugin.
      //Initialize condition.
      $.fn.changeCondition();
    }

    $.fn.switchTargetOptions();
    $('#jform_target').trigger('liszt:updated'); //Update Chosen plugin.
  };


  //Modify the state of the show_rule and application tags.
  $.fn.setTags = function() {
    $.fn.setShowRule();
    $.fn.switchApplication();
  };


  $.fn.changeType = function(priceRuleType) {
    $.fn.initForm(priceRuleType);
    $.fn.setTags();
  };


  $.fn.changeCondition = function() {
    //First remove of all the items from the container.
    $('#condition-container').removeItem();

    var condition = $('#jform_condition').val();
    if(condition == 'total_prod_qty' || condition == 'total_prod_amount') {
      //Hide the logical operator as the condition is unique.
      $('#jform_logical_opr').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#add-condition-button-0').parent().css({'visibility':'hidden','display':'none'});
      //Create an empty and unique condition item.
      var data = $.fn.getDataSet('condition');
      //Set its id to zero as the item is unique.
      data.id = 0;
      $.fn.createItem('condition', data);
    }
    else {
      //Show the logical operator.
      $('#jform_logical_opr').parent().parent().css({'visibility':'visible','display':'block'});
      $('#add-condition-button-0').parent().css({'visibility':'visible','display':'block'});
    }
  };


  //Switch the target options according to the current price rule type.
  $.fn.switchTargetOptions = function() {
//alert('switchTargetOptions');
    //First remove all of the target items from the container
    $('#target-container').removeItem();
    //Get the selected option.
    var selected = $('#jform_target').val();
    //then empty the previous target options
    $('#jform_target').empty();

    //Create the require target options.
    if($('#jform_type').val() == 'catalog') {
      var options = '<option value="product">'+Joomla.JText._('COM_KETSHOP_OPTION_PRODUCT')+'</option>';
      options += '<option value="product_cat">'+Joomla.JText._('COM_KETSHOP_OPTION_PRODUCT_CAT')+'</option>';
      options += '<option value="bundle">'+Joomla.JText._('COM_KETSHOP_OPTION_BUNDLE')+'</option>';
      //Display the "add" button.
      $('#add-target-button-0').parent().css({'visibility':'visible','display':'block'});
    }
    else { //cart
      var options = '<option value="cart_amount">'+Joomla.JText._('COM_KETSHOP_OPTION_CART_AMOUNT')+'</option>';
      options += '<option value="shipping_cost">'+Joomla.JText._('COM_KETSHOP_OPTION_SHIPPING_COST')+'</option>';
      //No need dynamical items here, so we hide the "add" button.
      $('#add-target-button-0').parent().css({'visibility':'hidden','display':'none'});
    }
    //Add options to the select tag.
    $('#jform_target').html(options);
    //Set the tag to the proper value.
    $('#jform_target').val(selected);
  };


  $.fn.changeTarget = function() {
    //Check for the price rule type.
    if($('#jform_type').val() == 'catalog') {
      //Remove target items previously set whenever a different option is selected. 
      $('#target-container').removeItem();
    } else { //cart
      $.fn.setShowRule();
      $.fn.switchApplication();
    }
  };


  $.fn.changeRecipient = function() {
    //Remove previous recipient items whenever a new option is selected.
    $('#recipient-container').removeItem();
  };


  //Show/Hide the show_rule radio button tag.
  $.fn.setShowRule = function() {
      //alert($('#jform_target').val());
    var priceRuleType = $('#jform_type').val();
    var modifier = $('#jform_modifier').val();
    var target = $('#jform_target').val();
    //Check for states that imply the show_rule radio buttons are disabled.
    if((modifier == 'profit_margin_modifier' && priceRuleType == 'catalog') ||
	(target == 'cart_amount' && priceRuleType == 'cart')) {

      if(modifier == 'profit_margin_modifier' && priceRuleType == 'catalog') {
	//Rule should not be showed when it applies on profit margin so we set
	//radio buttons to "no".
	$('#jform_show_rule1').prop('checked', true);
      }

      if(target == 'cart_amount' && priceRuleType == 'cart') {
	//A cart rule with a 'cart amount' target cannot be hidden so we set
	//radio buttons to "yes".
	$('#jform_show_rule0').prop('checked', true);
      }
      //Disable show_rule radio buttons
      $('#jform_show_rule').prop('disabled', true);
    } else {
      //Enable show_rule radio buttons.
      $('#jform_show_rule').prop('disabled', false);
    }
  };


  //Hide or show the application select tag (ie: after/before taxes) according to the 
  //operation, modifier and/or target tags value.
  $.fn.switchApplication = function() {
    //Get the current operation tag value.
    var operation = $('#jform_operation').val();
    var display = true;

    //Check for the current price rule type.
    if($('#jform_type').val() == 'catalog') {
      var modifier = $('#jform_modifier').val();
      //After/before taxes notion is not used with percentages or profit margin modifier.
      if((operation == '+%' || operation == '-%') || modifier == 'profit_margin_modifier') {
	display = false;
      }
    } else { //cart
      //After/before taxes notion is not used with percentages or shipping cost target.
      var target = $('#jform_target').val();
      if((operation == '+%' || operation == '-%') || target == 'shipping_cost') {
	display = false;
      }
    }

    //Show/hide the application tag.
    if(display) {
      $('#jform_application-lbl').css({'visibility':'visible','display':'block'});
      //$('#jform_application').css({'visibility':'visible','display':'block'}); //Managed by Chosen plugin.
      $('#jform_application_chzn').css({'visibility':'visible','display':'block'}); //Chosen plugin.
    } else {
      $('#jform_application-lbl').css({'visibility':'hidden','display':'none'});
      //$('#jform_application').css({'visibility':'hidden','display':'none'}); //Managed by Chosen plugin.
      $('#jform_application_chzn').css({'visibility':'hidden','display':'none'}); //Chosen plugin.
    }
  };

  //Check the price rule's dynamical items.
  $.fn.checkPriceRule = function() {
    //Get the Bootstrap recipient tag. 
    var $recipientTab = $('[data-toggle="tab"][href="#pricerule-recipient"]');

    //Check for each recipient value.
    if($('#recipient-container > div').length) {
      $('[id^="recipient-id-"]').each(function() { 
	                                if($(this).val() == '') {
					  alert(Joomla.JText._('COM_KETSHOP_ERROR_EMPTY_VALUE'));
					  $recipientTab.show();
					  $recipientTab.tab('show');
					  return false;
					}
				    });
    }
    else { //Recipient container is empty.
      alert(Joomla.JText._('COM_KETSHOP_ERROR_RECIPIENT_MISSING'));
      $recipientTab.show();
      $recipientTab.tab('show');
      return false;
    }

    //Check for either target or condition item according to the price rule type.
    var item = 'target';
    if($('#jform_type').val() == 'cart') {
      item = 'condition';
    }

    //Get the Bootstrap item tag. 
    var $itemTab = $('[data-toggle="tab"][href="#pricerule-'+item+'"]');

    //Check for each target or condition item value.
    if($('#'+item+'-container > div').length) {
      $('[id^="'+item+'-id-"]').each(function() { 
	                                if($(this).val() == '') {
					  alert(Joomla.JText._('COM_KETSHOP_ERROR_EMPTY_VALUE'));
					  $itemTab.show();
					  $itemTab.tab('show');
					  return false;
					}
				    });

      //In case of a condition we also have to check the value of the condition itself.
      if(item == 'condition') {
	//Set the type according to the condition type.
	var type = 'qty';
	if($('#jform_condition').val() == 'product_cat_amount' || $('#jform_condition').val() == 'total_prod_amount') {
	  type = 'amount';
	}

	//Check for each condition value.
	$('[name^="condition_item_'+type+'_"]').each(function() { 
	                                  //Note: $.isNumeric function check for both
					  //numeric and float values.
					  if(!$.isNumeric($(this).val())) {
					    alert(Joomla.JText._('COM_KETSHOP_ERROR_INCORRECT_OR_EMPTY_VALUE'));
					    $itemTab.show();
					    $itemTab.tab('show');
					    return false;
					  }
				      });
      }
    }
    else { //Target or condition container is empty.
      alert(Joomla.JText._('COM_KETSHOP_ERROR_'+item.toUpperCase()+'_MISSING'));
      $itemTab.show();
      $itemTab.tab('show');
      return false;
    }

    return true;
  };

})(jQuery);

