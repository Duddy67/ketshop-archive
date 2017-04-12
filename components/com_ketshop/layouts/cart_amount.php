<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for cart amount.
$cartAmount = $displayData['cart_amount'];
//var_dump($cart);
?>

<tr class="amount-row-bgr font-bold"><td colspan="<?php echo $displayData['col_span_nb']; ?>" class="text-right">
 <?php echo JText::_('COM_KETSHOP_CART_AMOUNT_LABEL'); ?>
</td></tr>

<?php if(!empty($cartAmount['pricerules'])) : //Check for displaying cart rules if any. ?>
    <tr class="cart-rules-background"><td colspan="<?php echo $displayData['col_span_nb']; ?>" class="text-right">

    <?php foreach($cartAmount['pricerules'] as $ruleInfo) : ?>
      <?php if($ruleInfo['target'] == 'cart_amount') : //. ?>
	<div class="info-row">
	  <span class="rule-name"><?php echo $ruleInfo['name']; ?></span>
	  <span class="label label-warning">
	    <?php echo UtilityHelper::formatPriceRule($ruleInfo['operation'], $ruleInfo['value']); ?>
	  </span>
	  <?php //if(!empty($ruleInfo['description'])) : //For now we don't display the rule description (too confusing). ?>
	    <!-- <div class="rule-description"><?php //echo $ruleInfo['description']; ?></div>-->
	  <?php //endif; ?>
	</div>
      <?php endif; ?>
    <?php endforeach; ?>

    </td></tr>
  <?php endif; ?>

<tr><td colspan="<?php echo $displayData['col_span_nb']; ?>" class="text-right">
<?php //Since cart rules with cart amount as target are always shown, we display the striked original amount. ?>
<?php if($cartAmount['amount'] != $cartAmount['final_amount']) : //Check if amount has been modified by rules. ?>

  <?php if($displayData['tax_method'] == 'excl_tax') : ?>
     <span class="striked-amount"><?php echo UtilityHelper::formatNumber($cartAmount['amount'], $displayData['digits']); ?>
				  <?php echo $displayData['currency']; ?></span>
  <?php else : //incl_tax ?>
     <span class="striked-amount">
       <?php echo UtilityHelper::formatNumber($cartAmount['amt_incl_tax'], $displayData['digits']); ?>
       <?php echo $displayData['currency']; ?></span>
  <?php endif; ?>

<?php endif; ?>

<?php if($displayData['tax_method'] == 'excl_tax') : ?>
   <span class="cart-amount"><?php echo UtilityHelper::formatNumber($cartAmount['final_amount'], $displayData['digits']); ?>
			<?php echo $displayData['currency']; ?></span>
   <span class="tax-method"><?php echo JText::_('COM_KETSHOP_EXCLUDING_TAXES'); ?></span> 
   <span class="width-space"></span>
   <span class="cart-amount"><?php echo UtilityHelper::formatNumber($cartAmount['fnl_amt_incl_tax'], $displayData['digits']); ?>
			<?php echo $displayData['currency']; ?></span>
   <span class="tax-method"><?php echo JText::_('COM_KETSHOP_INCLUDING_TAXES'); ?></span> 
<?php else : //incl_tax ?>
   <span class="cart-amount">
     <?php echo UtilityHelper::formatNumber($cartAmount['fnl_amt_incl_tax'], $displayData['digits']); ?>
     <?php echo $displayData['currency']; ?></span>
   <span class="tax-method"><?php echo JText::_('COM_KETSHOP_INCLUDING_TAXES'); ?></span> 
<?php endif; ?>
</td></tr>

