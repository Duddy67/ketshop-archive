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
if(empty($displayData->attributes)) {
  $displayAttribs = false;
}
?>

<?php if($params->get('show_attributes') && ($params->get('attributes_location') == $displayData->attributes_location || $params->get('attributes_location') == 'both') && $displayAttribs) : ?>

  <table class="table table-condensed small">

  <?php foreach($displayData->attributes as $key => $attribute) : ?>
	  <tr><td>
	    <?php echo $attribute['name']; ?>
	  </td><td>  
	    <?php
                  if($attribute['multiselect'] == 1) {
		    foreach($attribute['options'] as $option) {
		      echo $option['option_text'];
		      echo '<br />';
		    }
		  }
		  else {
		    echo $attribute['option_text'];
		  }
	    ?>
	  </td></tr>
  <?php endforeach; ?>
  </table>
<?php endif; ?>


