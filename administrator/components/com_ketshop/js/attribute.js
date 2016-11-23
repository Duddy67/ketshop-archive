
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Create a container.
    $('#group').getContainer();

    //Bind value field select tags to a switching function (show/hide).
    $('#jform_field_type_1').change( function() { $.fn.valueField(this); });
    $('#jform_field_type_2').change( function() { $.fn.valueField(this); });

    //Initialize fields according to their current values.
    $.fn.valueField(document.getElementById('jform_field_type_1'));
    $.fn.valueField(document.getElementById('jform_field_type_2'));

    //Get the attribute item id.
    var attributeId = $('#jform_id').val();

    //If the attribute item exists we need to get the data of the dynamical items.
    if(attributeId != 0) {
      var urlQuery = {'attribute_id':attributeId};
      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  url: 'components/com_ketshop/js/ajax/attributegroup.php', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results, function(i, result) { $.fn.createItem('group', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

  });


  //Same thing for continent items.
  $.fn.createGroupItem = function(idNb, data) {
    var usedAttrGrpIds = ketshop.getUsedAttributeGroups();
    var properties = {'name':'group_'+idNb, 'id':'group-id-'+idNb};
    $('#group-item-'+idNb).createHTMLTag('<select>', properties, 'group-select');

    var options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';
    for(var i = 1; i < 11; i++) {
      var disabled = '';
      //Check for used attribute groups.
      if($.fn.inArray(i, usedAttrGrpIds)) {
	//Attribute group already used with a product cannot be selected.
	disabled = 'disabled="disabled"';
      }

      options += '<option value="'+i+'" '+disabled+'>'+'Attribute group '+i+'</option>';
    }

    $('#group-id-'+idNb).html(options);

    var isUsed = false;

    if(data !== '') {
      //Set the selected option.
      $('#group-id-'+idNb+' option[value="'+data+'"]').attr('selected', true);

      //If the selected attribute group is part of those which are used with one or more
      //products, we disabled the drop down list (and the remove button as well). 
      if($.fn.inArray(data, usedAttrGrpIds)) {
	$('#group-id-'+idNb).attr('disabled', true);
	//Modify the name attribute of the select (to prevent any pb with the hidden input)
	$('#group-id-'+idNb).attr('name', 'group_id_'+idNb+'_disabled');
	//As the value of disabled tags is not conveyed by the form we must create a hidden
	//fields in order to send the initial value.
	$('#group-item-'+idNb).append('<input type="hidden" name="group_'+idNb+'" value="'+data+'" />');
	isUsed = true;
      }
    }

    //Use Chosen jQuery plugin.
    $('#group-id-'+idNb).trigger('liszt:updated');
    $('#group-id-'+idNb).chosen();

    if(isUsed) { //Create a fake and disabled removal button.
      $('#group-item-'+idNb).append('<div class="btn-wrapper btn" id="btn-disabled-'+idNb+'">');
      $('#group-item-'+idNb).find('.btn-wrapper').append('<span class="icon-remove"/>');
      $('#group-item-'+idNb).find('.btn-wrapper').append(Joomla.JText._('COM_KETSHOP_BUTTON_REMOVE_LABEL'));
      $('#btn-disabled-'+idNb).attr('disabled', true);
    }
    else { //Create the item removal button.
      $('#group-item-'+idNb).createButton('remove');
    }

  };


  $.fn.valueField = function(this_) {
    //Extract the id number which is set at the end of the id string.
    var idString = this_.id;
    //Note: If no id number is found we set it to zero.
    var idNb = parseInt(idString.replace(/.+([0-9]+)$/, '$1')) || 0;

    //Display or hide the corresponding value list field according to the selected value.
    if(this_.value != 'closed_list') {
      //Go back to the highest parent tag of the select (ie: select->div->div).
      $('#jform_field_value_'+idNb).parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_field_text_'+idNb).parent().parent().css({'visibility':'hidden','display':'none'});
    }
    else {
      $('#jform_field_value_'+idNb).parent().parent().css({'visibility':'visible','display':'block'});
      $('#jform_field_text_'+idNb).parent().parent().css({'visibility':'visible','display':'block'});
    }

    //Multiselect option is available only if field 1 is used alone and as a closed list.
    if($('#jform_field_type_2').val() == 'none' && $('#jform_field_type_1').val() == 'closed_list') {
      $('#jform_multiselect').parent().parent().css({'visibility':'visible','display':'block'});
    }
    else {
      $('#jform_multiselect').parent().parent().css({'visibility':'hidden','display':'none'});
    }

    if($('#jform_field_type_1').val() == 'open_field') {
      $('#jform_value_type').parent().parent().css({'visibility':'visible','display':'block'});
    }
    else {
      $('#jform_value_type').parent().parent().css({'visibility':'hidden','display':'none'});
    }
  };

})(jQuery);
