
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('.remove-product').click( function() { $.fn.removeProduct(this); });
    $('.refresh-qty').click( function() { $.fn.changeQuantity(this); });
    $('#update-order').click( function() { $.fn.updateOrder(); });
  });


  $.fn.addProduct = function(id) {
    var urlQuery = $.fn.getUrlQuery();
    urlQuery.task = 'add';

    //Note: Product id can be a single integer or a coupled figures in case of product option (eg: 41_2)  
    //      For more convenience we add a zero product option to single product ids.
    var regex = /^[0-9]+$/;
    if(regex.test(id)) {
      id = id+'_0';
    }

    urlQuery.product_ids = id;
    $.fn.runAjax(urlQuery);
    //alert(urlQuery.products[1].unit_price);
  };


  //Note: Don't call this function "remove" as it seems to interfere with the JQuery methods.
  $.fn.removeProduct = function(element) {
    var urlQuery = $.fn.getUrlQuery();
    urlQuery.task = 'remove';
    urlQuery.product_ids = element.id;
    //alert(element.id);
    $.fn.runAjax(urlQuery);
  };


  $.fn.updateOrder = function() {
    var urlQuery = $.fn.getUrlQuery();
    urlQuery.task = 'update';
    $.fn.runAjax(urlQuery);
    //alert(urlQuery.products[1].unit_price);
    //location.reload();
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

    var urlQuery = {'order_id':orderId, 'user_id':userId, 'products':[]};
    //
    $('[id^="unit_price_"]').each(function() {
      var ids = this.id.substring(11);
      var unitPrice = $('#'+this.id).val();
      var quantity = $('#quantity_product_'+ids).val();
      var taxRate = $('#tax_rate_'+ids).val();
      var catid = $('#catid_'+ids).val();
      var name = $('#name_'+ids).val();
      var optionName = $('#option_name_'+ids).val();
      var code = $('#code_'+ids).val();
      var unitSalePrice = $('#unit_sale_price_'+ids).val();
      //Insert dynamicaly an array of data for each product of the order.
      urlQuery.products.push({'ids':ids, 'unit_price':unitPrice,
			      'quantity':quantity, 'tax_rate':taxRate,
			      'catid':catid, 'name':name,
			      'option_name':optionName,
			      'code':code, 'unit_sale_price':unitSalePrice});
     });

    return urlQuery;
  };


  $.fn.runAjax = function(urlQuery) {

    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/order.php', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
	  //Display message if any.
	  if(results.message) {
	    alert(results.message);
	  }

	  location.reload();
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });
  };


  $.fn.changeQuantity = function(element) {
    var orderId = $('#jform_id').val();
    var userId = $('#user_id').val();
    var newQty = parseInt($('#quantity_product_'+element.id).val());
    var minQty = parseInt($('input[name=min_quantity_'+element.id+']').val());
    var maxQty = parseInt($('input[name=max_quantity_'+element.id+']').val());

    //Check new quantity number.
    var regex = /^[1-9][0-9]*/;
    if(!regex.test(newQty)) {
      alert('not valid');
      return;
    }

    if(newQty < minQty) {
      alert('qty too low');
      return;
    }

    if(newQty > maxQty) {
      alert('qty too hight'+maxQty+':'+newQty);
      return;
    }

    var ids = element.id;
    var matches = ids.match('([0-9]+)_([0-9]+)');
    //alert(matches[1]+' '+matches[2]);
    var urlQuery = {'order_id':orderId, 'product_id':matches[1], 'option_id':matches[2], 'new_qty':newQty, 'task':'change_quantity'};

    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/order.php', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
	  //alert(newQty+':'+minQty+':'+maxQty);
	  if(results.no_qty_change) {
	    alert('no qty change');
	  }

	  if(results.insufficient_stock) {
	    alert('insufficient stock');
	  }
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });
  };
})(jQuery);

