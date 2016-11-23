<?php
/**
 * @package JooShop
 * @copyright Copyright (c)2012 - 2015 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for options.
$options = $displayData->options;
//
$priceText = JText::_('COM_KETSHOP_HEADING_PRICE_INCL_TAX');
if($displayData->shop_settings->tax_method == 'excl_tax') {
  $priceText = JText::_('COM_KETSHOP_HEADING_PRICE_EXCL_TAX');
}
?>

<?php if(!empty($options)) : ?>
  <h2>Product options</h2>
  <select id="product-options-<?php echo $options[0]['prod_id']; ?>"
	  name="product_options_<?php echo $options[0]['prod_id']; ?>">

  <?php foreach($options as $option) :
          $disabled = '';
	  //Check that the product option can be ordered. 
	  if(($displayData->stock_subtract && $displayData->shippable) && $option['stock_state'] == 'minimum'
	     && (!$displayData->allow_order || $option['stock'] == 0)) {
	    $disabled = 'disabled="disabled"';
	  }
  ?>
    <option value="<?php echo $option['opt_id']; ?>" <?php echo $disabled; ?>><?php 
          $text = '';
	  foreach($option['attributes'] as $attribute) {
	    $text .= $attribute['attrib_text'].' / ';
	  }
	  //Remove separation characters from the end of the string.
	  $text = substr($text, 0, -3);
          echo $text;
    ?></option>
  <?php endforeach; ?>

  </select>

  <?php
        $html = '';
	foreach($options as $option) { 
	  //Display options which price is different from the price of the main product.
	  if($option['sale_price'] > 0 && $option['base_price'] > 0) {
	    $text = '';
	    foreach($option['attributes'] as $attribute) {
	      $text .= $attribute['attrib_text'].' / ';
	    }
	    //Remove separation characters from the end of the string.
	    $text = substr($text, 0, -3);

	    $html .= '<tr><td>'.$text.'</td><td>'.$option['code'].
	             '</td><td>';

	    //Check for price rule.
	    if($option['sale_price'] != $option['final_price'] && $option['rules_info'][0]['show_rule']) {
	      $html .= '<span class="striked-price small">'.UtilityHelper::formatNumber($option['sale_price']).'</span> ';
	    }

	    $html .= '<span class="price small">'.UtilityHelper::formatNumber($option['final_price']).'</span> '.
		     '<span class="currency small">'.$displayData->shop_settings->currency.'</span>';

	    if(isset($option['final_price_with_taxes'])) {
	      $html .= '<span class="price-incl-tax small">'.$option['final_price_with_taxes'].'</span> '.
		       '<span class="price-incl-tax small">'.$displayData->shop_settings->currency.'</span>';
	    }

	    $html .= '</td></tr>'; 	  
	  }
        } ?>

  <?php if(!empty($html)) : ?>
    <table class="table small">
    <tr><th><?php echo JText::_('COM_KETSHOP_OPTION'); ?></th>
    <th><?php echo JText::_('COM_KETSHOP_PRODUCT_REFERENCE'); ?></th>
    <th><?php echo $priceText; ?></th></tr>
    <?php echo $html; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>


