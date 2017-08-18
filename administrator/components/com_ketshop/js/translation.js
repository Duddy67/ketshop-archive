
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Get the current item type.
    var itemType = $('#jform_item_type').val();
    //Get the translation id.
    var translationId = $('#jform_id').val();
    //If the translation item exists we get its item type and reset the form.
    if(translationId != 0) {
      //Restore the form to its original state filled with the initial data 
      //in case the user changes some value then press the F5 key.
      //It also allows to get the item type directly from the form tag.
      $('#translation-form')[0].reset();
      itemType = $('#jform_item_type').val();
    }

    //Bind the select item type to the setTranslation function.
    $('#jform_item_type').change( function() { $.fn.setTranslation(); });

    $.fn.setTranslation(itemType);
  });


  $.fn.setTranslation = function(itemType) {
    //The value of the select tag has changed.
    if(itemType === undefined) {
      //Get the current item type.
      itemType = $('#jform_item_type').val();
      //Empty the previous item values.
      $('#jform_item_id_id').val('');
      $('#jform_item_id_name').val('');
    } 

    //Show or hide some fields according to the chosen item type.
    if(itemType == 'shipping' || itemType == 'shipper' || itemType == 'price_rule') {
      $.fn.switchAlias(false);
      $.fn.switchProductDescription(false);
      $.fn.switchDescription(true);
      $.fn.switchInformation(false);
      $.fn.switchProductMetadata(false);
    } else if(itemType == 'attribute' || itemType == 'tax') {
      $.fn.switchAlias(false);
      $.fn.switchProductDescription(false);
      $.fn.switchDescription(false);
      $.fn.switchInformation(false);
      $.fn.switchProductMetadata(false);
    } else if(itemType == 'payment_mode') {
      $.fn.switchAlias(false);
      $.fn.switchProductDescription(false);
      $.fn.switchDescription(true);
      $.fn.switchInformation(true);
      $.fn.switchProductMetadata(false);
    } else { //product 
      $.fn.switchAlias(true);
      $.fn.switchProductDescription(true);
      $.fn.switchProductMetadata(true);
      $.fn.switchDescription(false);
      $.fn.switchInformation(false);
    }

    //Set the view.
    //Remove underscore if any (price_rule, payment_mode etc..). 
    var view = itemType.replace("_","");
    //View names we need are in the plural (products, attributes etc...).
    view = view + 's';
    //Need an extra e with the "tax" word.
    if(itemType == 'tax')
      view = 'taxes';

    //Create the new link to the proper modal window.
    var link = 'index.php?option=com_ketshop&view='+view+'&layout=modal&tmpl=component&type=translation';
    //Update the select button link.
    $('#item_link').prop('href', link);
  };


  $.fn.switchAlias = function(show) {
    if(show) {
      $('#jform_alias-lbl').css({'visibility':'visible','display':'block'});
      $('#jform_alias').css({'visibility':'visible','display':'block'});
    } else {
      $('#jform_alias-lbl').css({'visibility':'hidden','display':'none'});
      $('#jform_alias').css({'visibility':'hidden','display':'none'});
    }
  };


  $.fn.switchDescription = function(show) {
    if(show) {
      $('#description-editor').css({'visibility':'visible','display':'inline'});
      $('#jform_description-lbl').css({'visibility':'visible','display':'block'});
      $('#jform_description').parent().css({'visibility':'visible','display':'block'});
    } else {
      $('#description-editor').css({'visibility':'hidden','display':'none'});
      $('#jform_description-lbl').css({'visibility':'hidden','display':'none'});
      $('#jform_description').parent().css({'visibility':'hidden','display':'none'});
    }
  };


  $.fn.switchProductDescription = function(show) {
    if(show) {
      $('#product-description-editor').css({'visibility':'visible','display':'inline'});
      $('#jform_product_description-lbl').css({'visibility':'visible','display':'block'});
      $('#jform_product_description').parent().css({'visibility':'visible','display':'block'});
    } else {
      $('#product-description-editor').css({'visibility':'hidden','display':'none'});
      $('#jform_product_description-lbl').css({'visibility':'hidden','display':'none'});
      $('#jform_product_description').parent().css({'visibility':'hidden','display':'none'});
    }
  };


  $.fn.switchInformation = function(show) {
    if(show) {
      $('#information-editor').css({'visibility':'visible','display':'inline'});
      $('#jform_information-lbl').css({'visibility':'visible','display':'block'});
      $('#jform_information').parent().css({'visibility':'visible','display':'block'});
    } else {
      $('#information-editor').css({'visibility':'hidden','display':'none'});
      $('#jform_information-lbl').css({'visibility':'hidden','display':'none'});
      $('#jform_information').parent().css({'visibility':'hidden','display':'none'});
    }
  };


  $.fn.switchProductMetadata = function(show) {
    if(show) {
      $('#metadata').css({'visibility':'visible','display':'block'});
      $('a[href="#metadata-title"]').parent().css({'visibility':'visible','display':'block'});
    } else {
      $('#metadata').css({'visibility':'hidden','display':'none'});
      $('a[href="#metadata-title"]').parent().css({'visibility':'hidden','display':'none'});
    }
  };

})(jQuery);
