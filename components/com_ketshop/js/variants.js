
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('[id^="product-variants-"]').change( function() { $.fn.setVariantId(this); });

  });

  $.fn.setVariantId = function(this_) {
    //Get the id number from the tag id value.
    //Note: If no id number is found we set it to zero.
    var idNb = parseInt(this_.id.replace(/.+-(\d+)$/, '$1')) || 0;
    //Get the href value of the "add to cart" link.
    var href = $('#product-'+idNb).attr('href');
    //Set the variant id to the selected option.
    var newHref = href.replace(/\d+$/, this_.value);
    //Set the href attribute to the new value.
    $('#product-'+idNb).attr('href', newHref);
    $('#cart-product-'+idNb).attr('href', newHref);
  };

})(jQuery);
