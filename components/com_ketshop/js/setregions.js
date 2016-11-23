
(function($) {

  //Run a function when the page is fully loaded including graphics.
  //Note: This script is used in address forms on both front and back end. 
  $(window).load(function() {
    var formType = $('#form-type').val();

    $('#'+formType+'_country_code_sh').change( function() { $.fn.setRegions(this.value, 'sh'); });
    $('#'+formType+'_country_code_bi').change( function() { $.fn.setRegions(this.value, 'bi'); });

    $.fn.initRegions();
  });


  $.fn.setRegions = function(country_code, type) {
    var regions = ketshop.getRegions();
    var length = regions.length;
    var options = '<option value="">'+Joomla.JText._('COM_KETSHOP_OPTION_SELECT')+'</option>';

    var regex = new RegExp('^'+country_code+'-');
    //Create an option tag for each region.
    for(var i = 0; i < length; i++) {
      //Test the regex to get only regions from the selected country.
      if(regex.test(regions[i].code)) {
	options += '<option value="'+regions[i].code+'">'+regions[i].text+'</option>';
      }
    }

    var formType = $('#form-type').val();
    //Empty the previous options.
    $('#'+formType+'_region_code_'+type).empty();
    //Add the new region options to the select tag.
    $('#'+formType+'_region_code_'+type).append(options);

    //Use Chosen jQuery plugin.
    $('#'+formType+'_region_code_'+type).trigger('liszt:updated');
  };


  $.fn.initRegions = function() {
    var formType = $('#form-type').val();
    //Get the value of the previously selected regions if any.
    var regionCodeSh = $('#hidden-region-code-sh').val();
    var regionCodeBi = $('#hidden-region-code-bi').val();

    //Empty the options previously set by the regionlist field function.
    $('#'+formType+'_region_code_sh').empty();
    $('#'+formType+'_region_code_sh').trigger('liszt:updated');
    $('#'+formType+'_region_code_bi').empty();
    $('#'+formType+'_region_code_bi').trigger('liszt:updated');

    if(regionCodeSh != '') {
      //Build the region option list according to the previously selected country.
      $.fn.setRegions($('#'+formType+'_country_code_sh').val(), 'sh');
      //Set the region value previously selected. 
      $('#'+formType+'_region_code_sh').val(regionCodeSh);
      $('#'+formType+'_region_code_sh').trigger('liszt:updated');
    }

    if(regionCodeBi != '') {
      //Build the region option list according to the previously selected country.
      $.fn.setRegions($('#'+formType+'_country_code_bi').val(), 'bi');
      //Set the region value previously selected. 
      $('#'+formType+'_region_code_bi').val(regionCodeBi);
      $('#'+formType+'_region_code_bi').trigger('liszt:updated');
    }
  };

})(jQuery);

