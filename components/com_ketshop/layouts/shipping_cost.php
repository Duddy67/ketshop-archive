<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 * @contact lucas.sanner@nomendum.com
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for cart amount.
$cartAmount = $displayData['cart_amount'];
//var_dump($cart);
?>

<?php if($displayData['shippable']) : //Display shipping. ?>
  <tr class="shipping-row-bgr font-bold"><td colspan="<?php echo $displayData['col_span_nb']; ?>">
   <?php echo JText::_('COM_KETSHOP_SHIPPING_COST_LABEL'); ?>
  </td></tr>

  <?php //Display the shipping price rules.

     $showRule = true;
     //Store display in a variable.
     $output = '';
     //Searching for shipping price rules.

     foreach($cartAmount['pricerules'] as $priceRule) {
       //As soon as one of the rules must not be shown, we stop
       //searching.
       if(!$priceRule['show_rule']) {
	 $showRule = false;
	 break;
       }

       if($priceRule['target'] == 'shipping_cost') {
	 $output .= '<div class="info-row">';
	 $output .= '<span class="rule-name">'.$priceRule['name'].'</span>';
	 $output .= '<span class="label label-warning">';
	 $output .= UtilityHelper::formatPriceRule($priceRule['operation'], $priceRule['value']);
	 $output .= '</span>';

	 //Note: For now we don't display rule description (too confusing). 
	 /*if(!empty($priceRule['description'])) {
	   $output .= '<div class="rule-description">'.$priceRule['description'].'</div>';
	 }*/

	 $output .= '</div>';
       }
     }
     
     if(!empty($output)) {
       $output = '<tr class="cart-rules-background"><td colspan="'.$displayData['col_span_nb'].'">'.$output.'</td></tr>';
     }

     //If all of the rules are allowed to be shown we can display them.
     if($showRule){
       echo $output;
     }
  ?>

  <?php if($displayData['layout'] != 'cart') : //Display shipping cost.
          $shippingData = $displayData['shipping_data'];
      ?>
    <tr class="amount-background"><td colspan="<?php echo $displayData['col_span_nb']; ?>">
      <span class="shipping-name"><?php echo $shippingData['name']; ?></span>
      <?php //Display the striked original cost. ?>
      <?php if($showRule && $shippingData['cost'] != $shippingData['final_cost']) : //. ?>
       <span class="striked-amount">
        <?php echo UtilityHelper::formatNumber($shippingData['cost'], $displayData['digits_precision']); ?>
        <?php echo $displayData['currency']; ?>
       </span>
      <?php endif; ?>
       <span class="shipping-cost">
        <?php echo UtilityHelper::formatNumber($shippingData['final_cost'], $displayData['digits_precision']); ?>
        <?php echo $displayData['currency']; ?>
       </span>
       <span class="incl-taxes"><?php echo JText::_('COM_KETSHOP_INCLUDING_TAXES'); ?></span> 
    </td></tr>
  <?php endif; ?>

<?php endif; ?>

