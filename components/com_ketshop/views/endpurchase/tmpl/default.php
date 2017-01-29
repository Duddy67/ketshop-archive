<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

//Store all needed data in an array.
$data = array();
//Set the layout type.
$data['layout'] = 'endpurchase';

$data['products'] = $this->products;

//Prepare some data for the layouts.
$data['cart_amount'] = array('amount' => $this->item->cart_amount,
			     'final_amount' => $this->item->final_cart_amount,
			     'amt_incl_tax' => $this->item->crt_amt_incl_tax,
			     'fnl_amt_incl_tax' => $this->item->fnl_crt_amt_incl_tax,
			     'rules_info' => $this->amountPriceRules
                            );

$data['shipping_data'] = $this->shippingData;
$data['shippable'] = $this->item->shippable;

//Get some global needed variables.
$data['tax_method'] = $this->item->tax_method;
$data['currency'] = UtilityHelper::getCurrency();
$data['shippable'] = ShopHelper::isShippable();
$data['rounding'] = $this->item->rounding_rule;
$data['digits'] = $this->item->digits_precision;

$data['col_span_nb'] = 4;
if($this->item->tax_method == 'excl_tax') {
  $data['col_span_nb'] = 5;
}

$data['billing_address_id'] = $this->item->billing_address_id;
$addresses = array('shipping' => $this->shippingAddress, 'billing' => $this->billingAddress);
$data['addresses'] = $addresses;
?>

<div class="blog purchase">

  <h2 class="item-title"><?php echo JText::_('COM_KETSHOP_END_PURCHASE_TITLE'); ?></h1>

  <table class="table product-row end-table">

    <?php //Display layouts. ?>
    <?php echo JLayoutHelper::render('product_header', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('product_rows', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('cart_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('shipping_cost', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('total_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

  </table>

  <span class="btn" id="endpurchase-button">
    <a href="index.php?option=com_ketshop&task=finalize.endPurchase"><?php echo JText::_('COM_KETSHOP_END_PURCHASE'); ?></a>
  </span>

  <?php echo JLayoutHelper::render('addresses', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

</div>

