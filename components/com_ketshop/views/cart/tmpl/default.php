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
$data['layout'] = 'cart';

//Grab the user session.
$session = JFactory::getSession();
//Get the safety variables.
$data['locked'] = $session->get('locked', 0, 'ketshop'); 
$endPurchase = $session->get('end_purchase', 0, 'ketshop'); 

//Purchase is done, all purchase session data must be deleted.
if($endPurchase) {
  ShopHelper::clearPurchaseData();
}

//Get where the user comes from.
$data['location'] = $session->get('location', '', 'ketshop'); 

//Check if user comes from the summary view.
$data['summary'] = false;
if(preg_match('#view=summary#', $data['location'])) {
  $data['summary'] = true;
}


//Get products in the cart and cart amount.
$data['products'] = $session->get('cart', array(), 'ketshop'); 
//echo '<pre>';
//var_dump($data['products']);
//echo '</pre>';


if(!empty($data['products'])) {
  $data['cart_amount'] = $session->get('cart_amount', array(), 'ketshop'); 
  $settings = $session->get('settings', array(), 'ketshop'); 
  //Get unavailable products if any, (only for pending cart).
  $unavailable = $session->get('unavailable', array(), 'ketshop'); 

  $data['shippable'] = ShopHelper::isShippable();
  $data['tax_method'] = $settings['tax_method'];
  $data['currency'] = $settings['currency'];
  $data['rounding_rule'] = $settings['rounding_rule'];
  $data['digits_precision'] = $settings['digits_precision'];

  $data['col_span_nb'] = 5;
  if($data['tax_method'] == 'excl_tax') {
    $data['col_span_nb'] = 6;
  }
}
?>

<div class="blog purchase">
<!--<h1 class="icon-shop-cart" style="margin-left:10px;font-size:24px;"></h1>-->

<?php if(!empty($data['products'])) : //Make sure there is something within the cart. ?>

  <form action="index.php?option=com_ketshop&task=cart.updateQuantity" method="post" id="ketshop_cart">

    <table class="table product-row end-table">

    <?php //Display table header layout. ?>
    <?php echo JLayoutHelper::render('product_header', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

    <?php if(!empty($unavailable)) : //. ?>

      <?php for($i = 0; $i < count($unavailable); $i++) : ?>
	  <tr class="unavailable"><td>
	    <span><?php echo $unavailable[$i]['name']; ?>
	  </td><td>
	    <span>-</span>
	  </td><td>
	    <span>-</span>
	  </td><td>
	    <?php if($data['tax_method'] == 'excl_tax') : ?>
	      <span>-</span>
	      </td><td>
	    <?php endif; ?>
	    <span>-</span>
	  </td><td>
	    <span class="unavailable-product">
	     <?php echo JText::_('COM_KETSHOP_UNAVAILABLE_PRODUCT'); ?>
	    </span>
	  </td></tr>
      <?php endfor; ?>
    <?php endif; ?>

    <?php //Display layouts. ?>
    <?php echo JLayoutHelper::render('product_rows', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('cart_amount', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>
    <?php echo JLayoutHelper::render('shipping_cost', $data, JPATH_SITE.'/components/com_ketshop/layouts/'); ?>

      <?php //Display shipping message according to the situation. ?>
      <tr><td colspan="<?php echo $data['col_span_nb']; ?>">
	 <?php if(!$data['summary']) : //User is shopping, shipping cost is not defined yet. ?>
	   <span class="shipping-cost-state"><?php echo JText::_('COM_KETSHOP_SHIPPING_COST_UNDEFINED'); ?></span> 
	 <?php else : //User comes from summary view (to applying changes). Shipping cost must be recalculated. ?>
	   <span class="shipping-cost-state"><?php echo JText::_('COM_KETSHOP_SHIPPING_COST_RECALCULATED'); ?></span> 
	 <?php endif; ?>
      </td></tr>
    </table>

    <?php if(!$data['locked']) : //Cart can be updated. ?>
      
	<input type="submit" class="btn btn-info"  onclick="hideButton('btn')" 
	       value="<?php echo JText::_('COM_KETSHOP_UPDATE_CART'); ?>" />
    <?php endif; ?>
  </form> <?php // Close the cart form. ?>

  <div id="btn-message">
    <?php if(!$data['locked']) : //Cart can be updated. ?>
      <span class="btn btn-danger">
	<a href="index.php?option=com_ketshop&task=cart.emptyCart" onclick="return getMessage('empty_cart','btn');" class="btn-link">
	  <?php echo JText::_('COM_KETSHOP_EMPTY_CART'); ?></a>
      </span>
      <span class="width-space"></span>
      <?php if(!$data['summary']) : //As long as user doesn't come from summary view, he can carry on shopping. ?>
	<span class="btn">
	  <a href="index.php?<?php echo $data['location']; ?>" onclick="hideButton('btn')">
	    <?php echo JText::_('COM_KETSHOP_CARRY_ON_SHOPPING'); ?></a> 
	</span>
	<span class="width-space"></span>

	<?php $orderingLink = 'index.php?option=com_ketshop&task=ordering.checkUser'; ?>

      <?php else : //User comes from summary view. ?>

        <?php if($data['shippable']) { //
		$orderingLink = 'index.php?option=com_ketshop&task=shipment.setShipment';
	      } 
              else { //Redirect to summary view.
		$orderingLink = 'index.php?option=com_ketshop&view=summary';
	      }
	?>
      <?php endif; ?>

      <span class="btn btn-success">
	<a href="<?php echo JRoute::_($orderingLink); ?>" onclick="hideButton('btn')" class="btn-link">
	 <?php echo JText::_('COM_KETSHOP_ORDERING'); ?></a> 
      </span>
      <span class="width-space"></span>

      <?php //Use a form here to control multiple submit syndrome. ?>
      <form action="index.php?option=com_ketshop&task=store.saveCart" method="post" id="ketshop_save">
	      <input type="submit" class="btn btn-warning" onclick="hideButton('btn')"
		     value="<?php echo JText::_('COM_KETSHOP_SAVE_CART'); ?>" />
      </form>

    <?php else : //Order is stored. The only things left to do are paying, cancelling or saving the cart. ?>

      <span class="btn">
	<a href="index.php?option=com_ketshop&task=payment.setPayment" onclick="hideButton('btn')">
	  <?php echo JText::_('COM_KETSHOP_PAY'); ?></a> 
      </span>
      <span class="btn">
	<a href="index.php?option=com_ketshop&task=cart.cancelCart" onclick="return getMessage('cancel_cart','btn');">
	  <?php echo JText::_('COM_KETSHOP_CANCEL_CART'); ?></a> 
      </span>
      <?php //Use a form here to control multiple submit syndrome. ?>
      <form action="index.php?option=com_ketshop&task=store.saveLockedCart" method="post" id="ketshop_save">
	      <input type="submit" class="btn btn-warning" onclick="hideButton('btn')"
		     value="<?php echo JText::_('COM_KETSHOP_SAVE_CART'); ?>" />
      </form>
    <?php endif; ?>
  </div>

<?php else : //Cart is empty. ?>
  <div class="alert alert-no-items">
    <?php echo JText::_('COM_KETSHOP_CART_EMPTY'); ?>
  </div>
<?php endif; ?>
</div>

