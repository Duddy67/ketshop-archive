<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create shortcuts.
$cartAmount = $displayData['cart_amount'];
$shippingData = $displayData['shipping_data'];
//var_dump($cart);

?>

  <tr class="total-row-bgr font-bold"><td colspan="<?php echo $displayData['col_span_nb']; ?>">
     <?php echo JText::_('COM_KETSHOP_TOTAL_LABEL'); ?>
  </td></tr>
  <tr><td colspan="<?php echo $displayData['col_span_nb']; ?>">
  <?php //Whatever the tax method, total amount is always computed with all taxes. ?>
  <?php $totalAmount = $cartAmount['fnl_amt_incl_tax'] + $shippingData['final_cost']; ?>
   <span class="total-amount">
     <?php echo UtilityHelper::formatNumber($totalAmount, $displayData['digits']); ?>
     <?php echo $displayData['currency']; ?>
   </span>
   <span class="incl-taxes"><?php echo JText::_('COM_KETSHOP_INCLUDING_TAXES'); ?></span> 
  </td></tr>

