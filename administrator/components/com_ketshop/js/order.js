
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('.remove-product').click( function() { $.fn.remove(this); });
    $('.refresh-qty').click( function() { $.fn.quantity(this); });
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


  $.fn.quantity = function(element) {
    var userId = $('#user_id').val();
    var newQty = $('input[id^="quantity_product_'+element.id+'_"]').val();
    var ids = element.id;
    var matches = ids.match('([0-9]+)_([0-9]+)');
    alert(matches[1]+' '+matches[2]);
  };
})(jQuery);

