<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

$form = $displayData->getForm();

//Get the product type and item id as well from the query string. 
$id = JFactory::getApplication()->input->get->get('id', 0, 'int');
$type = $form->getValue('type');

if($type == 'bundle') {
  $fields = $displayData->get('fields') ?: array(
	  'dummy_stock',
	  'dummy_stock_subtract',
	  'dummy_availability_delay', 
	  'dummy_shippable',
	  'min_stock_threshold',
	  'max_stock_threshold',
	  'allow_order',
	  'min_quantity',
	  'max_quantity'
  );

  if(!$id) { //Item is not created yet.
    $form->setValue('dummy_stock', null, '?');
    $form->setValue('dummy_availability_delay', null, '?');
  }
  else { 
    //The following attributes cannot be set here as their values are computed elsewhere, so we use some dummy fields.
    $form->setValue('dummy_stock', null, $form->getValue('stock'));
    $form->setValue('dummy_stock_subtract',null, $form->getValue('stock_subtract'));
    $form->setValue('dummy_availability_delay',null, $form->getValue('availability_delay'));
    $form->setValue('dummy_shippable', null, $form->getValue('shippable'));
  }

  //Make the "real" fields hidden to get values stored in database, (dummy disabled fields 
  //are not taking into account when saving).
  $hiddenFields = $displayData->get('hidden_fields') ?: array('stock', 'stock_subtract', 'availability_delay', 'shippable');
}
else { //normal
  $fields = $displayData->get('fields') ?: array(
	  'stock',
	  'stock_subtract',
	  'availability_delay', 
	  'shippable',
	  'min_stock_threshold',
	  'max_stock_threshold',
	  'allow_order',
	  'min_quantity',
	  'max_quantity'
  );

  $hiddenFields = $displayData->get('hidden_fields') ?: array();
}

$separator = array('availability_delay','shippable','allow_order');

foreach ($fields as $field) {
  $field = is_array($field) ? $field : array($field);
  foreach ($field as $f) {
    if($form->getField($f)) {
      if(in_array($f, $hiddenFields)) {
	$form->setFieldAttribute($f, 'type', 'hidden');
      }

      echo $form->renderField($f);
      break;
    }
  }

  if(in_array($f, $separator)) {  ?>
    <span class="spacer"><span class="before"></span><span class=""><hr class="" />
    </span><span class="after"></span></span>
   <?php
  }
}
