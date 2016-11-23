<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for products.
$products = $displayData['products'];
//var_dump($cart);
?>


<?php foreach($products as $key => $product) : //List the products of the cart.

      //Make short alias names from some variables for more convenience.
      $unitSalePrice = $product['unit_sale_price']; 
      $unitPrice = $product['unit_price']; 
      $quantity = $product['quantity']; 
      $taxRate = $product['tax_rate']; 
      $rulesInfo = $product['rules_info']; 
      $optionName = '';

      if($product['attribute_group']) {
	$optionName = '<span class="small">'.$product['option_name'].'</span>';
      }
      //Compute the class name according to $key value (ie: odd or even number).
      $class = ($key % 2) ? 'odd' : 'even';
  ?>

      <?php /////////////////////// PRODUCT ROW //////////////////////////// ?>
      <tr class="<?php echo $class; ?>"><td>
	<a href="<?php echo $product['url']; ?>" class="font-bold" target="_blank"><?php echo $product['name']; ?></a>
	<?php echo $optionName; ?>

	    <?php /////////////////////// UNIT PRICE //////////////////////////// ?>
	    <?php if($quantity > 1) : //Check if unit price should be displayed. ?>
		<div  class="info-row small">
		  <?php echo JText::_('COM_KETSHOP_UNIT_PRICE_LABEL'); ?>

		  <?php if(!empty($rulesInfo) && $rulesInfo[0]['show_rule']) : //Check if striked unit sale price can be displayed. ?>
		    <span class="striked-price">
		      <?php echo UtilityHelper::formatNumber($unitSalePrice, $displayData['digits']); ?>
		      <?php echo $displayData['currency']; ?></span>
		    <span class="space">&nbsp;</span>
		  <?php endif; ?>

		    <span class="unit-price">
		      <?php echo UtilityHelper::formatNumber($unitPrice, $displayData['digits']); ?>
		      <?php echo $displayData['currency']; ?>
		    </span>

		    <?php if($displayData['tax_method'] == 'excl_tax') : ?>
		      <span class="tax-method"><?php echo JText::_('COM_KETSHOP_EXCLUDING_TAXES'); ?></span> 
		    <?php else : //incl_tax ?>
		      <span class="tax-method"><?php echo JText::_('COM_KETSHOP_INCLUDING_TAXES'); ?></span> 
		    <?php endif; ?>
		</div>
	    <?php endif; /////////////////////// END UNIT PRICE //////////////////////////  ?>

	    <?php /////////////////////// CATALOG RULES DISPLAY ////////////////////////// ?>
	      <?php if(!empty($rulesInfo) && $rulesInfo[0]['show_rule']) : //Check for any catalog rules to display. ?>

		<?php foreach($rulesInfo as $ruleInfo) : ?>
		  <div class="info-row">
		    <span class="rule-name"><?php echo $ruleInfo['name']; ?></span>
		    <span class="label label-warning">
		      <?php echo UtilityHelper::formatPriceRule($ruleInfo['operation'], $ruleInfo['value']); ?>
		    </span>
		  </div>
		<?php endforeach; ?>
	      <?php endif; ?>
	    <?php /////////////////////// END CATALOG RULES DISPLAY ////////////////////////// ?>

      </td><td class="center">
      <?php if($displayData['layout'] == 'cart' && !$displayData['locked']) : //Cart can be updated. ?>
	<input class="quantity" type="text" name="quantity_<?php echo $product['id']; ?>_<?php echo $product['opt_id']; ?>"
	       id="quantity_product_<?php echo $product['id']; ?>_<?php echo $product['opt_id']; ?>"
		value="<?php echo $quantity; ?>" />
      <?php else : ?>
	<span class="muted"><?php echo $quantity; ?></span>
      <?php endif; ?>

      <?php if($displayData['layout'] == 'cart') : //. ?>
	<input type="hidden" name="min_quantity_<?php echo $product['id']; ?>_<?php echo $product['opt_id']; ?>"
		value="<?php echo $product['min_quantity']; ?>" />
	<input type="hidden" name="max_quantity_<?php echo $product['id']; ?>_<?php echo $product['opt_id']; ?>"
		value="<?php echo $product['max_quantity']; ?>" />
	<input type="hidden" name="name_<?php echo $product['id']; ?>_<?php echo $product['opt_id']; ?>"
		value="<?php echo $product['name']; ?>" />
      <?php endif; ?>
      </td>

      <?php if($displayData['tax_method'] == 'excl_tax') : //Price should be displayed without taxes. ?>
	<td>
	<?php if(!empty($rulesInfo) && $rulesInfo[0]['show_rule']) : //Check if striked price can be displayed. ?>
	  <span class="striked-price">
	    <?php echo UtilityHelper::formatNumber($unitSalePrice * $quantity, $displayData['digits']); ?>
	    <?php echo $displayData['currency']; ?></span>
	<?php endif; ?>

	<span class="product-price">
	  <?php echo UtilityHelper::formatNumber($unitPrice * $quantity, $displayData['digits']); ?>
	  <?php echo $displayData['currency']; ?></span>
      <?php endif; ?>

      </td><td>

       <?php //Check if striked price can be displayed. For incl_tax method only. ?>
       <?php if($displayData['tax_method'] == 'incl_tax' && !empty($rulesInfo) && $rulesInfo[0]['show_rule']) : ?>
	  <span class="striked-price">
	  <?php echo UtilityHelper::formatNumber($unitSalePrice * $quantity, $displayData['digits']); ?>
	  <?php echo $displayData['currency']; ?></span>
       <?php endif; ?>

       <span class="product-price">
	<?php  if($displayData['tax_method'] == 'excl_tax') {
		 $sum = $unitPrice * $quantity;
		 $inclTaxResult =
		   UtilityHelper::roundNumber(UtilityHelper::getPriceWithTaxes($sum, $taxRate), $displayData['rounding'], $displayData['digits']);
		 echo UtilityHelper::formatNumber($inclTaxResult, $displayData['digits']);
	       }
	       else { //incl_tax 
		 echo UtilityHelper::formatNumber($unitPrice * $quantity, $displayData['digits']);
	       }

	       echo $displayData['currency'];
	?>
       </span>

      </td><td class="small">
	<?php echo $taxRate.' %'; ?>
      </td>
      <?php if($displayData['layout'] == 'cart') : //. ?>
	<td class="center">
	<?php if($displayData['layout'] == 'cart' && !$displayData['locked']) : //Cart can be updated. ?>
	  <a class="btn" href="<?php echo 'index.php?option=com_ketshop&task=cart.removeFromCart&prod_id='.$product['id']; ?>">
	   <?php echo JText::_('COM_KETSHOP_REMOVE'); ?></a> 
	<?php endif; ?>
	</td>
      <?php endif; ?>
      </tr>
      <?php /////////////////////// END PRODUCT ROW //////////////////////////// ?>


      <?php /////////////////////// CART RULES IMPACT ////////////////////////// ?>
      <?php //Check if product price has been impacted by cart rules, (only displayed when excluding taxes method is used). ?>
      <?php if($displayData['tax_method'] == 'excl_tax' && $unitPrice !== $product['cart_rules_impact']) : ?>
	<tr class="<?php echo $class; ?> cart-prl-mod"><td>
	  <span class="cart-rules-impact-label"><?php echo JText::_('COM_KETSHOP_CART_RULES_IMPACT_LABEL'); ?></span> 
	</td>
	<td class="center">-</td>
	<td>
	  <span class="cart-rules-impact">
	    <?php $sum = $product['cart_rules_impact'] * $quantity;
		//For tax free products we rounding after multiplied the product with its quantity.
		echo UtilityHelper::formatNumber(UtilityHelper::roundNumber($sum, $displayData['rounding'], $displayData['digits']), $displayData['digits']); ?>
	  </span>
	  <span class="cart-rules-impact-currency"><?php echo $displayData['currency']; ?></span>
	</td><td>
	<span class="cart-rules-impact">
	  <?php 
	      $sum = $product['cart_rules_impact'] * $quantity;
	      $inclTaxResult = UtilityHelper::roundNumber(UtilityHelper::getPriceWithTaxes($sum, $taxRate), $displayData['rounding'], $displayData['digits']);
	      echo UtilityHelper::formatNumber($inclTaxResult, $displayData['digits']);
	  ?>
	</span>
	  <span class="cart-rules-impact-currency"><?php echo $displayData['currency']; ?></span>
	</td>
	<td class="center">-</td>
	<?php if($displayData['layout'] == 'cart') : //Add the "Remove" button column. ?>
	  <td class="center">-</td>
	<?php endif; ?>
	</tr>
      <?php endif; ?>
      <?php /////////////////////// END CART RULE IMPACT ////////////////////////// ?>

<?php endforeach; ?>

