<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for params.
$params = $displayData->params;
$filterStates = $displayData->filterStates;
?>

<?php if($params->get('filter_field') != 'hide' || $params->get('show_pagination_limit') || $params->get('filter_ordering')) : ?>
  <div class="ketshop-toolbar clearfix">
  <?php
	  //Gets the filter fields.
	  //$fieldset = $displayData->filterForm->getFieldset('filter');
	  $fieldset = $displayData->filterForm->getFieldset('filter');

	  //Loops through the fields.
	  foreach($fieldset as $field) {
	    $filterName = $field->getAttribute('name');

	    if($filterName == 'filter_search' && $params->get('filter_field') != 'hide') { ?>
	      <div class="btn-group input-append span6">
	    <?php
		  $hint = JText::_('COM_KETSHOP_'.$params->get('filter_field').'_FILTER_LABEL');
		  $displayData->filterForm->setFieldAttribute($filterName, 'hint', $hint); 
		  //Displays only the input tag (without the div around).
		  echo $displayData->filterForm->getInput($filterName, null, $filterStates[$filterName]);
		  //Adds the search and clear buttons.  ?>
	      <button type="submit" onclick="ketshop.submitForm();" class="btn hasTooltip"
		      title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		  <i class="icon-search"></i></button>

	      <button type="button" onclick="ketshop.clearSearch()" class="btn hasTooltip js-stools-btn-clear"
		      title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		  <?php echo JText::_('JSEARCH_FILTER_CLEAR');?></button>
	      </div>
    <?php	}
	    elseif(($filterName == 'filter_ordering' && $params->get('filter_ordering')) ||
		   ($filterName == 'limit' && $params->get('show_pagination_limit'))) {
	      //Sets the field value to the currently selected value.
	      $field->setValue($filterStates[$filterName]);
	      echo $field->renderField(array('hiddenLabel' => true, 'class' => 'span3 ketshop-filters'));
	    }
	  }
   ?>
   </div>
<?php endif; ?>

<?php if($displayData->filterAttributes !== null) : ?>
  <div class="ketshop-toolbar clearfix attribute-filters">
   <h3><?php echo JText::_('COM_KETSHOP_ATTRIBUTE_FILTERS');?></h3>
  <?php
	foreach($displayData->filterAttributes as $attribute) {
	  echo '<select name="filter_attrib_'.$attribute['id'].'" id="filter_attrib_'.$attribute['id'].'" onchange="this.form.submit();">'.
	       '<option value="">'.$attribute['name'].'</option>';

	  foreach($attribute['options'] as $option) {
	    $selected = '';
	    if($filterStates['filter_attrib_'.$attribute['id']] == $option['option_value']) {
	      $selected = 'selected="selected"';
	    }

	    echo '<option value="'.$option['option_value'].'" '.$selected.'>'.$option['option_text'].'</option>';
	  }

	  echo '</select>';
	}
   ?>

      <button type="button" onclick="ketshop.clearFilters()" class="btn hasTooltip js-stools-btn-clear"
	      title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
	  <?php echo JText::_('JSEARCH_FILTER_CLEAR');?></button>
  </div>
<?php endif; ?>

