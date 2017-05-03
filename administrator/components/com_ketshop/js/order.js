
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
    if(confirm(Joomla.JText._('COM_KETSHOP_REMOVE_PRODUCT'))) {
      var urlQuery = $.fn.getUrlQuery();
      urlQuery.task = 'remove';
      urlQuery.product_ids = element.id;
      $.fn.runAjax(urlQuery);
    }
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
      var minQty = parseInt($('input[name=min_quantity_'+ids+']').val());
      var maxQty = parseInt($('input[name=max_quantity_'+ids+']').val());
      var initialQty = $('#initial_quantity_'+ids).val();
      //Insert dynamicaly an array of data for each product of the order.
      urlQuery.products.push({'ids':ids, 'unit_price':unitPrice,
			      'quantity':quantity, 'tax_rate':taxRate,
			      'catid':catid, 'name':name,
			      'option_name':optionName,
			      'code':code, 'unit_sale_price':unitSalePrice,
			      'min_quantity':minQty,'max_quantity':maxQty, 
			      'initial_quantity':initialQty});
     });

    return urlQuery;
  };


  $.fn.runAjax = function(urlQuery) {

    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/order.php', 
	dataType: 'json',
	data: urlQuery,
	beforeSend: function(jqXHR, settings) {
	  //Display the waiting screen all over the page.
	  $('#ajax-waiting-screen').css({'visibility':'visible','display':'block'});
	},
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
	  $('#ajax-waiting-screen').css({'visibility':'hidden','display':'none'});
	}
    });
  };

})(jQuery);

