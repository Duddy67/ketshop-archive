
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Important: Don't use the click method or it won't work after the ajax refresh. Use
    //the on method instead.
    $('#order-edit').on('click', '.remove-product', function() { $.fn.removeProduct(this); });
    $('#update-order').click( function() { $.fn.updateOrder(); });
  });


  $.fn.addProduct = function(id) {
    var urlQuery = $.fn.getUrlQuery();
    urlQuery.context = 'add';

    //Note: Product id can be a single integer or a coupled figures in case of product variant (eg: 41_2)  
    //      For more convenience we add a zero product variant value to single product ids.
    var regex = /^[0-9]+$/;
    if(regex.test(id)) {
      id = id+'_0';
    }

    urlQuery.product_ids = id;
    $.fn.runAjax(urlQuery);
  };


  //Note: Don't call this function "remove" as it seems to interfere with the JQuery methods.
  $.fn.removeProduct = function(element) {
    if(confirm(Joomla.JText._('COM_KETSHOP_REMOVE_PRODUCT'))) {
      var urlQuery = $.fn.getUrlQuery();
      urlQuery.context = 'remove';
      urlQuery.product_ids = element.id;
      $.fn.runAjax(urlQuery);
    }
  };


  $.fn.updateOrder = function() {
    var urlQuery = $.fn.getUrlQuery();
    urlQuery.context = 'update';
    $.fn.runAjax(urlQuery);
  };


  //Function called from a child window, so we have to be specific
  //and use the window object and the jQuery alias.
  window.jQuery.selectProduct = function(id, name) {
    SqueezeBox.close();
    $.fn.addProduct(id);
  };


  $.fn.getUrlQuery = function() {
    var orderId = $('#jform_id').val();
    var userId = $('#user_id').val();
    //Gets the token's name as value.
    var token = $('#token').attr('name');
    //Sets up the ajax query.
    var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'order_id':orderId, 'user_id':userId, 'products':[]};

    $('[id^="unit_price_"]').each(function() {
      //
      var ids = this.id.substring(11);
      //Collect all the needed data from the order form.
      var unitPrice = $('#'+this.id).val();
      var quantity = $('#quantity_product_'+ids).val();
      var taxRate = $('#tax_rate_'+ids).val();
      var catid = $('#catid_'+ids).val();
      var name = $('#name_'+ids).val();
      var variantName = $('#variant_name_'+ids).val();
      var code = $('#code_'+ids).val();
      var unitSalePrice = $('#unit_sale_price_'+ids).val();
      var minQty = parseInt($('input[name=min_quantity_'+ids+']').val());
      var maxQty = parseInt($('input[name=max_quantity_'+ids+']').val());
      var initialQty = $('#initial_quantity_'+ids).val();
      var stockSubtract = $('#stock_subtract_'+ids).val();
      var stock = $('#stock_'+ids).val();
      var hasVariants = $('#has_variants__'+ids).val();
      var alias = $('#alias_'+ids).val();
      var type = $('#type_'+ids).val();
      //Insert dynamicaly an array of data for each product of the order.
      urlQuery.products.push({'ids':ids, 'unit_price':unitPrice,
			      'quantity':quantity, 'tax_rate':taxRate,
			      'catid':catid, 'name':name,
			      'variant_name':variantName,
			      'code':code, 'unit_sale_price':unitSalePrice,
			      'min_quantity':minQty,'max_quantity':maxQty, 
			      'initial_quantity':initialQty,
			      'stock_subtract':stockSubtract, 'stock':stock,
			      'has_variants':hasVariants, 'alias':alias, 'type':type});
     });

    return urlQuery;
  };


  $.fn.runAjax = function(urlQuery) {

    $.ajax({
	type: 'GET', 
	dataType: 'json',
	data: urlQuery,
	beforeSend: function(jqXHR, settings) {
	  //Display the waiting screen all over the page.
	  $('#ajax-waiting-screen').css({'visibility':'visible','display':'block'});
	},
	complete: function(jqXHR, textStatus) {
	  $('#ajax-waiting-screen').css({'visibility':'hidden','display':'none'});
	},
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
	  //Display warning messages sent through JResponseJson.
	  if(results.message) {
	    alert(results.message);
	  }

	  if(results.messages) {
	    Joomla.renderMessages(results.messages);
	  }

	  if(!results.success) {
	    return;
	  }

	  //Refresh the order table with new data.
	  $('#order-edit').empty();
	  $('#order-edit').html(results.data.render);
	  //location.reload();
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	  $('#ajax-waiting-screen').css({'visibility':'hidden','display':'none'});
	}
    });
  };

})(jQuery);

