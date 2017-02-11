
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('.remove-product').click( function() { $.fn.remove(this); });
    $('.refresh-qty').click( function() { $.fn.changeQuantity(this); });
  });


  $.fn.remove = function(element) {
    var userId = $('#user_id').val();
    alert(element.id+':'+userId);
    var urlQuery = {'product_id':element.id, 'user_id':userId, 'task':'remove'};

    $.ajax({
	type: 'GET', 
	url: 'components/com_ketshop/js/ajax/order.php', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
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

