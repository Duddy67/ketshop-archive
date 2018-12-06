/*

   *** item container (div) ******
   *                             *
   *  ** item 1 (div) *********  *      
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *************************  *      
   *                             *
   *                             *
   *  ** item 2 (div) *********  *      
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *************************  *      
   *                             *
   *  etc...                     *
   *                             *
   *******************************
  
  Both item type and id number can be easily retrieved from the id value.

  Pattern of the id value of an item div:

  #type-itemname-extra-12
     |                 |
     |                 |
   item type         id number

*/


(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Set as function the global variable previously declared in check.js file.
    showTab = $.fn.showTab;
    reverseOrder = $.fn.reverseOrder;
  });


  //Create an item container (ie: a div).
  $.fn.getContainer = function() {
    //The value of the id is taken as the type of the items to create. 
    var type = $(this).attr('id');

    //Create the button allowing to add items dynamicaly.
    $('#'+type).createButton('add');  
    $('#'+type).append('<span id="'+type+'-button-separator">&nbsp;</span>');  
    //Create the container for the given item type.
    $('#'+type).append('<div id="'+type+'-container" class="ketshop-'+type+'-container">');  

    return this;
  };


  $.fn.createButton = function(action, href, modal) {
    //Get the id value of the item.
    var idValue = $(this).prop('id');
    //Extract the type and id number of the item.
    var itemType = idValue.replace(/^([a-zA-Z0-9_]+)-.+$/, '$1');
    //Note: If no id number is found we set it to zero.
    var idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1')) || 0;
    //Build the link id.
    var linkId = action+'-'+itemType+'-button-'+idNb;

    if(href === undefined) {
      href = 'javascript:void(0);';
    }

    //Create the button.
    $(this).append('<div class="btn-wrapper">');
    //Create the button link which trigger the action when it is clicked.
    //Note: Find the last element linked to the .btn-wrapper class in case one 
    //button or more already exist into the item. 
    $(this).find('.btn-wrapper:last').append('<a href="'+href+'" id="'+linkId+'" class="btn btn-small">');
    //Create the label button according to its action.
    var label = 'COM_KETSHOP_BUTTON_'+action.toUpperCase()+'_LABEL'

    if(action == 'remove_image') {
      //The remove image label is just a remove label. 
      label = 'COM_KETSHOP_BUTTON_REMOVE_LABEL'
    }

    //Insert the icon and bind a function to the button according to the required action.
    switch(action) {
      case 'add':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-save-new"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.createItem(itemType); });
	break;

      case 'remove':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-remove"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $('#'+itemType+'-container').removeItem(idNb); });
	break;

      case 'remove_image':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-remove"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.imageReorder(idNb); });
	break;

      case 'select':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-checkbox"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.openIntoIframe(modal); });
	break;
    }
   
    //Insert the label.
    $(this).find('.btn-wrapper:last a').append(Joomla.JText._(label));

    return this;
  };


  //Create any html tag.
  $.fn.createHTMLTag = function(tag, properties, className) {
    var newTag = $(tag).attr(properties);
    if(className !== undefined) {
      newTag.addClass(className);
    }
    //Add the tag.
    $(this).append(newTag);

    return this;
  };


  //Remove a given item.
  $.fn.removeItem = function(idNb) {
    //If no id number is passed as argument we just remove all of the container
    //children (ie: all of the items).
    if(idNb === undefined) {
      $(this).children().remove();
    } else {
      //Searching for the item to remove.
      for(var i = 0, lgh = $(this).children('div').length; i < lgh; i++) {
	//Extract (thanks to a regex) the id number of the item which is
	//contained at the end of its id value.
	/.+-(\d+)$/.test($(this).children().eq(i).attr('id'));
	//If the id number matches we remove the item from the container.
	if(RegExp.$1 == idNb) {
	  $(this).children().eq(i).remove();
	  break;
	}
      }
    }

    return this;
  };


  //A generic function which initialize then create a basic item of the given type.
  $.fn.createItem = function(itemType, data) {
    //If no data is passed we get an empty data set.
    if(data === undefined) {
      data = $.fn.getDataSet(itemType);
    }

    //First of all we need an id number for the item.
    var idNb = $('#'+itemType+'-container').getIdNumber();

    //Now we can create the basic structure of the item.
    var properties = {'id':itemType+'-item-'+idNb};
    $('#'+itemType+'-container').createHTMLTag('<div>', properties, itemType+'-item');

    //Build the name of the specific function from the type name (ie: create+Type+Item). 
    var functionName = 'create'+$.fn.upperCaseFirstLetter(itemType)+'Item';

    //Call the specific function.
    $.fn[functionName](idNb, data);

    return this;
  };


  //Function called from a child window, so we have to be specific
  //and use the window object and the jQuery alias.
  window.jQuery.selectItem = function(id, name, idNb, type) {
    //Check if the current id is different from the new one.
    if($('#'+type+'-id-'+idNb).val() != id) {
      //Set the values of the selected item.
      $('#'+type+'-id-'+idNb).val(id);
      $('#'+type+'-name-'+idNb).val(name);

      //stock value is passed as extra argument in order to keep selectItem
      //function generic (with the 4 basic arguments). 
      if(arguments[4]) {
	$('#'+type+'-stock-'+idNb).val(arguments[4]);
      }
    }
    
    SqueezeBox.close();

    return this;
  };


  $.fn.openIntoIframe = function(link)
  {
    SqueezeBox.open(link, {handler: 'iframe', size: {x: 800, y: 530}});
    return this;
  };


  //Note: All the utility functions below are not chainable. 


  //Return a valid item id number which can be used in a container.
  $.fn.getIdNumber = function() {
    var newId = 0;
    //Check if the container has any div children. 
    if($(this).children('div').length > 0) {

      //Searching for the highest id number of the container.
      for(var i = 0, lgh = $(this).children('div').length; i < lgh; i++) {
	var idValue = $(this).children('div').eq(i).attr('id');
	//Extract the id number of the item from the end of its id value and
	//convert it into an integer.
	idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1'));

	//If the item id number is greater than ours, we use it.
	if(idNb > newId) {
	  newId = idNb;
	}
      }
    }

    //Return a valid id number (ie: the highest id number of the container plus 1).
    return newId + 1;
  };


  //Return a data set corresponding to the given item type.
  //Data is initialised with empty or default values.
  $.fn.getDataSet = function(itemType) {
    var data = '';
    if(itemType == 'image') {
      data = {'alt':'', 'ordering':'', 'src':'', 'width':'', 'height':''};
    } else if(itemType == 'bundleproduct') {
      data = {'id':'', 'name':'', 'quantity':'1', 'stock':'?'};
    } else if(itemType == 'variant') {
      data = {'variant_id':'','variant_name':'','stock':'0','base_price':'0.00','sale_price':'0.00','sales':'0','code':'','availability_delay':'0','weight':'0.00','length':'0.00','width':'0.00','height':'0.00','published':'0','ordering':'0', 'attributes':[]};
    } else if(itemType == 'condition') {
      data = {'id':'', 'name':'', 'operator':'', 'item_amount':'', 'item_qty':''};
    } else if(itemType == 'postcode') {
      data = {'from':'', 'to':'', 'cost':''};
    } else if(itemType == 'city') {
      data = {'name':'', 'cost':''};
    } else if(itemType == 'deliverypoint' || itemType == 'attribute' || 
	      itemType == 'target' || itemType == 'recipient') {
      data = {'id':'', 'name':''};
    } else { //region country or continent.
      data = {'code':'', 'cost':''};
    }

    return data;
  };


  $.fn.inArray = function(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
      if(haystack[i] == needle) return 1;
    }
    return 0;
  };


  $.fn.upperCaseFirstLetter = function(str) {
    return str.slice(0,1).toUpperCase() + str.slice(1);
  };


  $.fn.checkValueType = function(value, valueType) {
    switch(valueType) {
      case 'string':
	var regex = /^.+$/;
	//Check for string which doesn't start with a space character.
	//var regex = /^[^\s].+$/;
	break;

      case 'int':
	var regex = /^-?[0-9]+$/;
	break;

      case 'unsigned_int':
	var regex = /^[0-9]+$/;
	break;

      case 'float':
	var regex = /^-?[0-9]+(\.[0-9]+)?$/;
	break;

      case 'unsigned_float':
	var regex = /^[0-9]+(\.[0-9]+)?$/;
	break;

      default: //Unknown type.
	return false;
    }

    return regex.test(value);
  };


  $.fn.showTab = function(tabId) {
    var $tab = $('[data-toggle="tab"][href="#'+tabId+'"]');
    //Show the tab.
    $tab.show();
    $tab.tab('show');
  };


  //Remove the selected item then reset the order of the other items left.
  $.fn.itemReorder = function(idNb, itemType) {
    //Remove the selected item.
    $('#'+itemType+'-container').removeItem(idNb);

    //List all of the div children (ie: items) of the item container 
    //in order to reset their ordering value.
    $('#'+itemType+'-container').children('div').each(function(i, div) {
	//Reset the ordering input tag value.
	$(div).children('.item-ordering').val(i+1);
    });

    $.fn.setOrderManagement(itemType); 
  };


  $.fn.setOrderManagement = function(itemType) {
    var idNbs = new Array();
    //Get the id numbers of the exiting items.
    $('#'+itemType+'-container').children('div').each(function(i, div) {
      var idNb = parseInt($(div).prop('id').replace(/.+-(\d+)$/, '$1')) || 0;
      //Store the id number.
      idNbs.push(idNb);
    });

    var arrLength = idNbs.length;
    //No need to go further if there is no item.
    if(arrLength == 0) {
      return;
    }

    //Create and set the management tags for each item.
    for(i = 0; i < arrLength; i++) {
      var idNb = idNbs[i];
      var ordering = i + 1;

      //First remove all the previous management tags.
      $('#'+itemType+'-up-ordering-'+idNb).remove();
      $('#'+itemType+'-down-ordering-'+idNb).remove();
      $('#'+itemType+'-prev-'+idNb).remove();
      $('#'+itemType+'-next-'+idNb).remove();
      $('#'+itemType+'-order-transparent-'+idNb).remove();

      if(ordering > 1) {
	//Create and set the link which allows to reverse the position with the upper item.
	var properties = {'href':'#', 'id':itemType+'-up-ordering-'+idNb, 'onclick':'reverseOrder(\'up\',\''+itemType+'\','+idNb+')'};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<a>', properties, 'up-ordering');
	var arrowUp = '<img src="../media/com_ketshop/images/arrow_up.png" title="'+Joomla.JText._('COM_ODYSSEY_REORDER_TITLE')+'" alt="arrow up" height="16" width="16" />';
	$('#'+itemType+'-item-'+idNb+' .up-ordering').prepend(arrowUp);
	//Move the element just after the ordering input tag.
	$('#'+itemType+'-up-ordering-'+idNb).insertAfter($('#'+itemType+'-ordering-'+idNb));

	//Create the hidden field which holds the id number of the previous item.
	properties = {'type':'hidden', 'name':itemType+'_prev_'+idNb, 'id':itemType+'-prev-'+idNb, 'value':idNbs[i - 1]};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<input>', properties);
      }

      if(ordering < arrLength) {
	//Create and set the link which allows to reverse the position with the lower item.
	properties = {'href':'#', 'id':itemType+'-down-ordering-'+idNb, 'onclick':'reverseOrder(\'down\',\''+itemType+'\','+idNb+')'};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<a>', properties, 'down-ordering');
	var arrowDown = '<img src="../media/com_ketshop/images/arrow_down.png" title="'+Joomla.JText._('COM_ODYSSEY_REORDER_TITLE')+'" alt="arrow down" height="16" width="16" />';
	$('#'+itemType+'-item-'+idNb+' .down-ordering').prepend(arrowDown);
	//Move the element just before the ordering input tag.
	$('#'+itemType+'-down-ordering-'+idNb).insertBefore($('#'+itemType+'-ordering-'+idNb));

	//Create the hidden field which holds the id number of the next item.
	properties = {'type':'hidden', 'name':itemType+'_next_'+idNb, 'id':itemType+'-next-'+idNb, 'value':idNbs[i + 1]};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<input>', properties);
      }

      //Add a transparent png to the first and last items of the list in order their row
      //has the same width as the other item rows.

      if(ordering == 1 && arrLength > 1) {
	var transparent = '<img src="../media/com_ketshop/images/transparent.png" id="'+itemType+'-order-transparent-'+idNb+'" class="order-transparent" alt="transparent" height="16" width="16" />';
	$(transparent).insertAfter($('#'+itemType+'-ordering-'+idNb));
      }

      if(ordering == arrLength) {
	var transparent = '<img src="../media/com_ketshop/images/transparent.png" id="'+itemType+'-order-transparent-'+idNb+'" class="order-transparent" alt="transparent" height="16" width="16" />';
	$(transparent).insertBefore($('#'+itemType+'-ordering-'+idNb));
      }
    }
  };


  $.fn.reverseOrder = function(direction, itemType, idNb) {
    //Get the id and name of the current item.
    var currentItemId = $('#'+itemType+'-id-'+idNb).val();
    var currentItemName = $('#'+itemType+'-name-'+idNb).val();

    //Get the id number of the previous or next item.
    if(direction == 'up') {
      var idNbToReverse = $('#'+itemType+'-prev-'+idNb).val();
    }
    else {
      var idNbToReverse = $('#'+itemType+'-next-'+idNb).val();
    }

    //Reverse the order of the 2 items.
    $('#'+itemType+'-id-'+idNb).val($('#'+itemType+'-id-'+idNbToReverse).val());
    $('#'+itemType+'-name-'+idNb).val($('#'+itemType+'-name-'+idNbToReverse).val());
    $('#'+itemType+'-id-'+idNbToReverse).val(currentItemId);
    $('#'+itemType+'-name-'+idNbToReverse).val(currentItemName);

    if(itemType == 'option') {
      //Get checkboxes.
      var checkbox = $('input[id^=published-'+idNb+']');
      var checkboxToReverse = $('input[id^=published-'+idNbToReverse+']');
      var tmp = false;
      //Get the state of the main checkbox.
      if(checkbox.prop('checked')) {
	tmp = true;
      }

      //Shift states of checkboxes.
      checkbox.prop('checked', checkboxToReverse.prop('checked'))
      checkboxToReverse.prop('checked', tmp);

      //Get the value of the current item.
      var currentItemValue = $('#'+itemType+'-value-'+idNb).val();
      //Reverse the order of the item values.
      $('#'+itemType+'-value-'+idNb).val($('#'+itemType+'-value-'+idNbToReverse).val());
      $('#'+itemType+'-value-'+idNbToReverse).val(currentItemValue);

      //Get the text of the current item.
      var currentItemText = $('#'+itemType+'-text-'+idNb).val();
      //Reverse the order of the item texts.
      $('#'+itemType+'-text-'+idNb).val($('#'+itemType+'-text-'+idNbToReverse).val());
      $('#'+itemType+'-text-'+idNbToReverse).val(currentItemText);
    }
  };

 })(jQuery);

