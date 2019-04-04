
(function($) {
  // A global variable to store then access the dynamical item objects. 
  const GETTER = {};

  // Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    // The input element containing the root location.
    let rootLocation = $('#root-location').val();
    // Sets the dynamic item properties.
    let props = {'component':'ketshop', 'item':'attribute', 'ordering':false, 'rootLocation':rootLocation, 'rowsCells':[3], 'Chosen':true, 'nbItemsPerPage':5};

    const attribute = new Omkod.DynamicItem(props);
    // Stores the newly created object.
    GETTER.attribute = attribute;
    // Sets the validating function. 
    $('#filter-form').submit( function(e) { validateFields(e); });

    let filterId = $('#jform_id').val();

    // Prepares then run the Ajax query.
    const ajax = new Omkod.Ajax();
    let params = {'method':'GET', 'dataType':'json', 'indicateFormat':true, 'async':true};
    // Gets the form security token.
    let token = jQuery('#token').attr('name');
    // N.B: Invokes first the ajax() function in the global controller to check the token.
    let data = {[token]:1, 'task':'ajax', 'filter_id':filterId};
    ajax.prepare(params, data);
    ajax.process(getAjaxResult);
  });

  getAjaxResult = function(result) {
    if(result.success === true) {
      $.each(result.data, function(i, item) { GETTER.attribute.createItem(item); });
    }
    else {
      alert('Error: '+result.message);
    }
  }

  validateFields = function(e) {
    let task = document.getElementsByName('task');
    let fields = {'attribute-name':''}; 

    if(task[0].value != 'filter.cancel' && !GETTER.attribute.validateFields(fields)) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }

  /** Callback functions **/

  populateAttributeItem = function(idNb, data) {
    // Defines the default field values.
    if(data === undefined) {
      data = {'attribute_id':'', 'attribute_name':''};
    }

    // Element label.
    let attribs = {'class':'item-space', 'id':'attribute-label-'+idNb};
    $('#attribute-row-1-cell-1-'+idNb).append(GETTER.attribute.createElement('span', attribs));
    $('#attribute-label-'+idNb).html('&nbsp;');

    // Creates the hidden input element to store the selected attribute id.
    attribs = {'type':'hidden', 'name':'attribute_attribute_id_'+idNb, 'id':'attribute-attribute-id-'+idNb, 'value':data.attribute_id};
    let elem = GETTER.attribute.createElement('input', attribs);
    $('#attribute-row-1-cell-1-'+idNb).append(elem);
    let url = $('#root-location').val()+'administrator/index.php?option=com_ketshop&view=attributes&layout=modal&tmpl=component&function=selectAttributeItem&dynamic_item_type=attribute&id_nb='+idNb;
    let button = GETTER.attribute.createButton('select', idNb, url);
    $('#attribute-row-1-cell-1-'+idNb).append(button);

    // Element label.
    attribs = {'title':Joomla.JText._('COM_KETSHOP_ITEM_NAME_TITLE'), 'class':'item-label', 'id':'attribute-attributename-label-'+idNb};
    $('#attribute-row-1-cell-2-'+idNb).append(GETTER.attribute.createElement('span', attribs));
    $('#attribute-attributename-label-'+idNb).text(Joomla.JText._('COM_KETSHOP_ITEM_NAME_LABEL'));

    attribs = {'type':'text', 'disabled':'disabled', 'id':'attribute-attribute-name-'+idNb, 'value':data.attribute_name};
    elem = GETTER.attribute.createElement('input', attribs);
    $('#attribute-row-1-cell-2-'+idNb).append(elem);
  }

  selectAttributeItem = function(id, name, idNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].selectItem(id, name, idNb, 'attribute', true);
  }

  browsingPages = function(pageNb, dynamicItemType) {
    // Calls the parent function from the corresponding instance.
    GETTER[dynamicItemType].updatePagination(pageNb);
  }

})(jQuery);

