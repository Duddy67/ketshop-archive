<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access.
defined('_JEXEC') or die;

//Store all needed data in an array.
$data = array();
//Set the layout type.
$data['layout'] = 'order_admin';

$data['can_edit'] = false;
if($this->item->cart_status == 'completed' && ($this->item->order_status != 'completed' && $this->delivery->status != 'completed')) {
  $data['can_edit'] = true;
}

$data['products'] = $this->products;

//Prepare some data for the layouts.
$data['cart_amount'] = array('amount' => $this->item->cart_amount,
			     'final_amount' => $this->item->final_cart_amount,
			     'amt_incl_tax' => $this->item->crt_amt_incl_tax,
			     'fnl_amt_incl_tax' => $this->item->fnl_crt_amt_incl_tax,
			     'pricerules' => $this->amountPriceRules
                            );

$data['shippable'] = $this->item->shippable;

if($this->item->cart_status == 'completed') {
  $data['shipping_data'] = array();
  foreach($this->delivery as $key => $value) {
    if($key != 'address') { //Don't want the shipping address.
      //Remove "shipping_" from the keys to fit the layout format.
      $key = preg_replace('#shipping_#', '', $key);
      $data['shipping_data'][$key] = $value;
    }
  }
}
//var_dump($data['shipping_data']);

//Get some global needed variables.
$data['tax_method'] = $taxMethod = $this->item->tax_method;
$data['shippable'] = $shippable = $this->item->shippable;
$data['currency'] = $currency = $this->item->currency;
$data['rounding'] = $rounding = $this->item->rounding_rule;
$data['digits'] = $digits = $this->item->digits_precision;

//Compute the required number of columns.
$data['col_span_nb'] = 4;
if($this->item->tax_method == 'excl_tax') {
  $data['col_span_nb'] = 5;
}

if($data['can_edit']) {
  $data['col_span_nb'] = $data['col_span_nb'] + 1;
}
?>

  <table class="table product-row end-table">
    <?php //Display layouts. ?>
    <?php echo JLayoutHelper::render('product_header', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('product_rows', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

    <?php if($this->item->cart_status == 'completed') : //Don't display amounts if cart is still pending. ?>
      <?php echo JLayoutHelper::render('cart_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
      <?php echo JLayoutHelper::render('shipping_cost', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
      <?php echo JLayoutHelper::render('total_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php endif; ?>
  </table>


