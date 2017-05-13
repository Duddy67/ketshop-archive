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

$optionIds = array();
//Check for options.
if(!empty($displayData->options)) {
  //Get the option attribute ids.
  $optAttribs = $displayData->options[0]['attributes'];
  foreach($optAttribs as $optAttrib) {
    $optionIds[] = $optAttrib['attrib_id'];
  }
}

//If all attributes are options or if there is one or more options and we are 
//in category view, attributes are not displayed.
$allOptions = false;
if((count($optionIds) == count($displayData->attributes)) || ($displayData->attribute_group && $displayData->attributes_location == 'summary')) {
  $allOptions = true;
}
?>

<?php if($params->get('show_attributes') && ($params->get('attributes_location') == $displayData->attributes_location || $params->get('attributes_location') == 'both') && !empty($displayData->attributes) && !$allOptions) : ?>
  <table class="table table-condensed small">
  <caption class="text-left font-bold"><?php echo JText::_('COM_KETSHOP_PRODUCT_ATTRIBUTES'); ?></caption>
  <?php foreach($displayData->attributes as $key => $attribute) : ?>
    <?php if(empty($optionIds) || !in_array($attribute->attrib_id, $optionIds)) : //Don't display option attributes. ?>
      <tr><td>
	<?php //Check if we're dealing with the same attribute then the previous one. If we
	      //do the attribute name is not displayed. 
	      if($key == 0 || $displayData->attributes[$key - 1]->attrib_id != $attribute->attrib_id) {
		echo $attribute->name;
	      }      
	?>
      </td><td>  
	<?php
	      if(!empty($attribute->field_text_1)) { //closed list
		echo preg_replace('#\|#', ', ', $attribute->field_text_1); //In case we're dealing with a multi select drop down list.
	      }
	      else { //open field
		echo $attribute->field_value_1; //Display the single value.
	      }

	      echo '&nbsp;';

	      if(!empty($attribute->field_value_2)) {
		if(!empty($attribute->field_text_2)) {
		  echo $attribute->field_text_2;
		}
		else {
		  echo $attribute->field_value_2;
		}
	      }
	?>
      </td></tr>
    <?php endif; ?>
  <?php endforeach; ?>
  </table>
<?php endif; ?>


