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

//If the product has multiple variants or no attributes and we're in the tag view, attributes are not displayed.
$displayAttribs = true;
if((empty($displayData->attributes) || $displayData->nb_variants > 1) && $displayData->attributes_location == 'summary') {
  $displayAttribs = false;
}
?>

<?php if($params->get('show_attributes') && ($params->get('attributes_location') == $displayData->attributes_location || $params->get('attributes_location') == 'both') && $displayAttribs) : ?>

  <table class="table table-condensed small">

  <?php foreach($displayData->attributes as $key => $attribute) : ?>
	  <tr><td>
	    <?php //Checks if we're dealing with the same attribute then the previous one (multiselect). If we
		  //do the attribute name is not displayed. 
		  if($key == 0 || $displayData->attributes[$key - 1]['id'] != $attribute['id']) {
		    echo $attribute['name'];
		  }      
	    ?>
	  </td><td>  
	    <?php
	          $multi = false;
	          foreach($attribute['options'] as $option) {
		    if(!empty($option['selected'])) {

		      if($multi) {
			echo '<br />';
		      }

		      echo $option['option_text'];
		      $multi = true;
		    }
		  }
	    ?>
	  </td></tr>
  <?php endforeach; ?>
  </table>
<?php endif; ?>


