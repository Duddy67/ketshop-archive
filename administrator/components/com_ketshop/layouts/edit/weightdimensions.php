<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

$form = $displayData->getForm();

$fields = $displayData->get('fields') ?: array(
	'weight_unit',
	'weight',
	'dimensions_unit', 
	'dimensions',
	'length',
	'width',
	'height'
);

$hiddenFields = $displayData->get('hidden_fields') ?: array();
$separator = array('weight');

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
