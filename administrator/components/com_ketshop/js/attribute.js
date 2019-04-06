(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};

  // Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    // The input element containing the root location.
    let rootLocation = $('#root-location').val();
    // Sets the dynamic item properties.
    let props = {'component':'ketshop', 'item':'option', 'ordering':true, 'rootLocation':rootLocation, 'rowsCells':[5], 'Chosen':true, 'nbItemsPerPage':5};

    // Stores the newly created object.
    GETTER.option = new Omkod.DynamicItem(props);
    // Sets the validating function.
    $('#attribute-form').submit( function(e) { validateFields(e); });

    let attributeId = $('#jform_id').val();

    // Prepares then run the Ajax query.
    const ajax = new Omkod.Ajax();
    let params = {'method':'GET', 'dataType':'json', 'indicateFormat':true, 'async':true};
    let token = jQuery('#token').attr('name');
    let data = {[token]:1, 'task':'ajax', 'attribute_id':attributeId};
    ajax.prepare(params, data);
    ajax.process(getAjaxResult);
  });

  getAjaxResult = function(result) {
    if(result.success === true) {
      $.each(result.data, function(i, item) { GETTER.option.createItem(item); });
    }
    else {
      alert('Error: '+result.message);
    }
  }

  validateFields = function(e) {
    let task = document.getElementsByName('task');
    let fields = {'value':'', 'text':''}; 

    if(task[0].value != 'attribute.cancel' && !GETTER.option.validateFields(fields)) {
      // Shows the dynamic item tab.
      $('.nav-tabs a[href="#options"]').tab('show');

      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }

  /** Callback functions **/

  populateOptionItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'option_value':'', 'option_text':''};
    }

    // Value label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_VALUE_TITLE'), 'class':'item-label', 'id':'option-value-label-'+idNb};
    $('#option-row-1-cell-1-'+idNb).append(GETTER.option.createElement('span', attribs));
    $('#option-value-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_VALUE_LABEL'));

    // Value input tag:
    attribs = {'type':'text', 'name':'option_value_'+idNb, 'id':'option-value-'+idNb, 'value':data.option_value};
    $('#option-row-1-cell-1-'+idNb).append(GETTER.option.createElement('input', attribs));

    // Text label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_TEXT_TITLE'), 'class':'item-label', 'id':'option-text-label-'+idNb};
    $('#option-row-1-cell-2-'+idNb).append(GETTER.option.createElement('span', attribs));
    $('#option-text-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_TEXT_LABEL'));

    // Text input tag:
    attribs = {'type':'text', 'name':'option_text_'+idNb, 'id':'option-text-'+idNb, 'value':data.option_text};
    $('#option-row-1-cell-2-'+idNb).append(GETTER.option.createElement('input', attribs));

    // Published label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_PUBLISHED_TITLE'), 'class':'item-label', 'id':'option-published-label-'+idNb};
    $('#option-row-1-cell-3-'+idNb).append(GETTER.option.createElement('span', attribs));
    $('#option-published-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_PUBLISHED_LABEL'));

    // Published tag:
    attribs = {'type':'checkbox', 'name':'option_published_'+idNb, 'id':'option-published-'+idNb, 'value':'published'};

    if(data.published == 1) {
      attribs.checked = 'checked';
    }

    $('#option-row-1-cell-3-'+idNb).append(GETTER.option.createElement('input', attribs));
  }

  reverseOrder = function(direction, idNb, dynamicItemType) {
    // Optional code...
    GETTER[dynamicItemType].reverseOrder(direction, idNb);
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Optional code...
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

})(jQuery);

