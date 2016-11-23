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
$data['layout'] = 'summary';

//Get all the session variables needed for building the order.
$session = JFactory::getSession();
$data['products'] = $session->get('cart', array(), 'ketshop'); 
$data['cart_amount'] = $session->get('cart_amount', 0, 'ketshop'); 
$shippers = $session->get('shippers', array(), 'ketshop'); 
$settings = $session->get('settings', array(), 'ketshop'); 
$data['billing_address_id'] = $session->get('billing_address_id', 0, 'ketshop'); 

$data['tax_method'] = $settings['tax_method'];
$data['shippable'] = ShopHelper::isShippable();
$data['currency'] = $settings['currency'];
$data['rounding'] = $settings['rounding_rule'];
$data['digits'] = $settings['digits_precision'];

$shippingData = array();
//Search for the selected shipper. 
foreach($shippers as $shipper) {
  if((bool)$shipper['selected']) {
    foreach($shipper['shippings'] as $shipping) {
      //Store the shipping data.
      if((bool)$shipping['selected']) {
	$shippingData['name'] = $shipping['name'];
	$shippingData['cost'] = $shipping['cost'];
	$shippingData['final_cost'] = $shipping['final_cost'];
	//$shippingData['rules_info'] = $shipping['rules_info']; //TODO: undefined warning. Check wether an old bug.
	//Get the address of the delivery point chosen by the user.  
	if($shipping['delivery_type'] == 'at_delivery_point') {
	  $shippingData['delivery_type'] = 'at_delivery_point';
	  $this->addresses['shipping']['street'] = $shipping['street'];
	  $this->addresses['shipping']['city'] = $shipping['city'];
	  $this->addresses['shipping']['postcode'] = $shipping['postcode'];
	  $this->addresses['shipping']['region'] = $shipping['region'];
	  $this->addresses['shipping']['country'] = $shipping['country'];
	  $this->addresses['shipping']['phone'] = $shipping['phone'];
	  $this->addresses['shipping']['information'] = $shipping['information'];
	  $this->addresses['shipping']['name'] = $shipping['name'];
	}
	else {
	  $shippingData['delivery_type'] = 'at_destination';
	}
      }
    }
  }
}

//Store shipping data in the session cause it will be needed later in
//sendConfirmationMail function. 
$session->set('shipping_data', $shippingData, 'ketshop'); 

$data['shipping_data'] = $shippingData;

$data['col_span_nb'] = 4;
if($data['tax_method'] == 'excl_tax') {
  $data['col_span_nb'] = 5;
}

$data['addresses'] = $this->addresses;
?>

<div class="blog purchase">

<h2 class="item-title"><?php echo JText::_('COM_KETSHOP_SUMMARY_TITLE'); ?></h1>

<p class="main-information">
    <?php echo JText::_('COM_KETSHOP_SUMMARY_INFORMATION'); ?>
</p>

<?php if(!empty($data['products'])) : ?>

  <form action="<?php echo JRoute::_('index.php?option=com_ketshop&task=store.storeData');?>"
	method="post" id="ketshop-summary-order">

    <table class="table product-row end-table">

      <?php //Display layouts. ?>
      <?php echo JLayoutHelper::render('product_header', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
      <?php echo JLayoutHelper::render('product_rows', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
      <?php echo JLayoutHelper::render('cart_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
      <?php echo JLayoutHelper::render('shipping_cost', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
      <?php echo JLayoutHelper::render('total_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

    </table>

    <div id="action-buttons">
      <span class="btn">
	<a href="index.php?option=com_ketshop&view=cart" onclick="hideButton('btn')">
	   <?php echo JText::_('COM_KETSHOP_MODIFY_CART'); ?></a> 
      </span>
      <span class="width-space"></span>

      <?php if($data['shippable']) : // ?>
	<span class="btn">
	  <a href="index.php?option=com_ketshop&view=shipment" onclick="hideButton('btn')">
	     <?php echo JText::_('COM_KETSHOP_MODIFY_SHIPMENT'); ?></a> 
	</span>
	<span class="width-space"></span>
      <?php endif; ?>

      <span class="btn btn-danger">
	<a href="index.php?option=com_ketshop&task=cart.emptyCart" onclick="return getMessage('empty_cart','btn');" class="btn-link">
	   <?php echo JText::_('COM_KETSHOP_EMPTY_CART'); ?></a>
      </span>
      <span class="width-space"></span>
	<input type="submit" class="btn btn-success" id="submit-button" onclick="hideButton('btn')"
	       value="<?php echo JText::_('COM_KETSHOP_PAY'); ?>" />
    </div>

  <?php echo JHtml::_('form.token'); ?>
  </form>
<?php else : ?>
  <div class="alert alert-no-items">
    <?php echo JText::_('COM_KETSHOP_CART_EMPTY'); ?>
  </div>
<?php endif; ?>

<?php echo JLayoutHelper::render('addresses', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
</div>

