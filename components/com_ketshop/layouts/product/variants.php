<?php
/**
 * @package JooShop
 * @copyright Copyright (c)2012 - 2015 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for variants.
$variants = $displayData->variants;
//
$priceText = JText::_('COM_KETSHOP_HEADING_PRICE_INCL_TAX');
if($displayData->shop_settings['tax_method'] == 'excl_tax') {
  $priceText = JText::_('COM_KETSHOP_HEADING_PRICE_EXCL_TAX');
}
?>

<?php if(!empty($variants)) : ?>
  <div>
  <h2>Product variants</h2>
  <select id="product-variants-<?php echo $variants[0]['prod_id']; ?>"
	  name="product_variants_<?php echo $variants[0]['prod_id']; ?>">

  <?php foreach($variants as $variant) :
          $disabled = '';
	  //Check that the product variant can be ordered. 
	  if(($displayData->stock_subtract && $displayData->shippable) && $variant['stock_state'] == 'minimum'
	     && (!$displayData->allow_order || $variant['stock'] == 0)) {
	    $disabled = 'disabled="disabled"';
	  }
  ?>
    <option value="<?php echo $variant['var_id']; ?>" <?php echo $disabled; ?>><?php 
          $text = '';
	  foreach($variant['attributes'] as $attribute) {
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
	foreach($variants as $variant) { 
	  //Display variants which price is different from the price of the main product.
	  if($variant['sale_price'] > 0 && $variant['base_price'] > 0) {
	    $text = '';
	    foreach($variant['attributes'] as $attribute) {
	      $text .= $attribute['attrib_text'].' / ';
	    }
	    //Remove separation characters from the end of the string.
	    $text = substr($text, 0, -3);

	    $html .= '<tr><td>'.$text.'</td><td>'.$variant['code'].
	             '</td><td>';

	    //Check for price rule.
	    if($variant['sale_price'] != $variant['final_price'] && $variant['pricerules'][0]['show_rule']) {
	      $html .= '<span class="striked-price small">'.UtilityHelper::formatNumber($variant['sale_price']).'</span> ';
	    }

	    $html .= '<span class="price small">'.UtilityHelper::formatNumber($variant['final_price']).'</span> '.
		     '<span class="currency small">'.$displayData->shop_settings['currency'].'</span>';

	    if(isset($variant['final_price_with_taxes'])) {
	      $html .= '<span class="price-incl-tax small">'.$variant['final_price_with_taxes'].'</span> '.
		       '<span class="price-incl-tax small">'.$displayData->shop_settings['currency'].'</span>';
	    }

	    $html .= '</td></tr>'; 	  
	  }
        } ?>

  <?php if(!empty($html)) : ?>
    <table class="table small">
    <tr><th><?php echo JText::_('COM_KETSHOP_VARIANT'); ?></th>
    <th><?php echo JText::_('COM_KETSHOP_PRODUCT_REFERENCE'); ?></th>
    <th><?php echo $priceText; ?></th></tr>
    <?php echo $html; ?>
    </table>
  <?php endif; ?>
  </div>
<?php endif; ?>


