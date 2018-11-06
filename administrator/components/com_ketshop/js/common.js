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
      data = {'variant_id':'','variant_name':'','stock':'0','base_price':'0.00','sale_price':'0.00','sales':'0','code':'','availability_delay':'0','weight':'0.00','length':'0.00','width':'0.00','height':'0.00','published':'0','ordering':'0'};
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

 })(jQuery);

