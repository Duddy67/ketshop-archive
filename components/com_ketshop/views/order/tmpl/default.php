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
$data['layout'] = 'order';

//Prepare some data for the layouts.
$data['cart_amount'] = array('amount' => $this->item->cart_amount,
			     'final_amount' => $this->item->final_cart_amount,
			     'amt_incl_tax' => $this->item->crt_amt_incl_tax,
			     'fnl_amt_incl_tax' => $this->item->fnl_crt_amt_incl_tax,
			     'pricerules' => $this->amountPriceRules
                            );

$data['shipping_data'] = $this->shippingData;
$data['shippable'] = $this->item->shippable;

$data['products'] = $this->products;
//Get some global needed variables.
$data['tax_method'] = $this->item->tax_method;
$data['shippable'] = $this->item->shippable;
$data['currency'] = $this->item->currency;
$data['rounding_rule'] = $this->item->rounding_rule;
$data['digits_precision'] = $this->item->digits_precision;

$data['col_span_nb'] = 4;
if($this->item->tax_method == 'excl_tax') {
  $data['col_span_nb'] = 5;
}

//Build a status array.
$status = array();
$status['completed'] = 'COM_KETSHOP_OPTION_COMPLETED_STATUS';
$status['pending'] = 'COM_KETSHOP_OPTION_PENDING_STATUS';
$status['other'] = 'COM_KETSHOP_OPTION_OTHER_STATUS';
$status['cancelled'] = 'COM_KETSHOP_OPTION_CANCELLED_STATUS';
$status['error'] = 'COM_KETSHOP_OPTION_ERROR_STATUS';
$status['no_shipping'] = 'COM_KETSHOP_OPTION_NO_SHIPPING_STATUS';
$status['unfinished'] = 'COM_KETSHOP_OPTION_UNFINISHED_STATUS';
$status['cartbackup'] = 'COM_KETSHOP_OPTION_CART_BACKUP_STATUS';

$Itemid = JRequest::getVar('Itemid', 0, 'GET', 'int');

$data['billing_address_id'] = $this->item->billing_address_id;
$addresses = array('shipping' => $this->shippingAddress, 'billing' => $this->billingAddress);
$data['addresses'] = $addresses;
?>

<div class="purchase">

<h2 class="item-title"><?php echo JText::sprintf('COM_KETSHOP_ORDER_TITLE', $this->item->name); ?></h1>

  <table class="table">
    <thead>
    <th><?php echo JText::_('COM_KETSHOP_HEADING_ORDER_STATUS'); ?></th>
    <th><?php echo JText::_('COM_KETSHOP_HEADING_PAYMENT_STATUS'); ?></th>
    <th><?php echo JText::_('COM_KETSHOP_HEADING_SHIPPING_STATUS'); ?></th>
    <th><?php echo JText::_('JDATE'); ?></th>
    </thead>
    <tr class="center"><td>
      <?php echo JText::_($status[$this->item->order_status]); ?>
    </td><td>
      <?php echo JText::_($status[$this->item->payment_status]); ?>
    </td><td>
      <?php echo JText::_($status[$this->item->shipping_status]); ?>
    </td><td>
      <?php echo JHTML::_('date',$this->item->created, JText::_('COM_KETSHOP_DATE_FORMAT')); ?>
    </td></tr>
  </table>


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

  <?php echo JLayoutHelper::render('addresses', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>


  <form action="<?php echo JRoute::_('index.php?option=com_ketshop&task=order.saveCustomerNote&order_id='.(int)$this->item->id).
                                     '&Itemid='.$Itemid; ?>" method="post" name="adminForm"
				       enctype="multipart/form-data" id="ketshop-form" class="form-validate">

    <div id="order-notes">
      <fieldset class="adminform">
	  <ul class="adminformlist">
	    <li><?php echo $this->form->getLabel('vendor_note'); ?>
	    <?php //Form tags are just used for css convenience, so we have to fill them with data. ?>
	    <?php $this->form->setValue('vendor_note', null, $this->item->vendor_note); ?>
	    <?php echo $this->form->getInput('vendor_note'); ?></li>
	    <li><?php echo $this->form->getLabel('customer_note'); ?>
	    <?php $this->form->setValue('customer_note', null, $this->item->customer_note); ?>
	    <?php echo $this->form->getInput('customer_note'); ?></li>
	    </ul>
	<input type="submit" value="Envoyer" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a <?php echo 'href="index.php?option=com_ketshop&view=orders&Itemid='.$Itemid.'"'; ?>>Annuler</a>
      </fieldset>
    </div>

    <?php echo JHtml::_('form.token'); ?>
 </form>

 </div>


