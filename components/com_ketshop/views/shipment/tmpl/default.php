<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

$session = JFactory::getSession();
$shippers = $session->get('shippers', array(), 'ketshop'); 
$settings = $session->get('settings', array(), 'ketshop'); 
$cartAmount = $session->get('cart_amount', 0, 'ketshop'); 

//Get the total delay of the products which are in the cart.
$productsTotalDelay = ShopHelper::getTotalDelay();

//Search for shipping price rules.
$showRule = true;
//Store the display in a variable.
$output = '';

foreach($cartAmount['rules_info'] as $ruleInfo) {
 //As soon as one of the rules must not be shown, we stop
 //searching.
 if(!$ruleInfo['show_rule']) {
   $showRule = false;
   break;
 }

 if($ruleInfo['target'] == 'shipping_cost') {
   $output .= '<div class="shipping-rules-info">';
   $output .= '<span class="rule-name">'.$ruleInfo['name'].'</span>';
   $output .= '<span class="label label-warning">';
   $output .= UtilityHelper::formatPriceRule($ruleInfo['operation'], $ruleInfo['value']);
   $output .= '</span></div>';
 }
}
?>

<div class="blog purchase">

  <h1 class="item-title"><?php echo JText::_('COM_KETSHOP_CHOOSE_SHIPPING_TITLE'); ?></h1>

  <?php foreach($shippers as $shipper) : ?>

    <div class="shipper">
    <?php //This is the name and description of the plugin/shipper set in the admin shipper view. ?>
    <h1><?php echo $shipper['name'];  ?></h1>
    <?php echo $shipper['description']; ?>
    <?php $pluginName = $shipper['plugin_element']; ?>

    <?php if($pluginName == 'shipping' && empty($shipper['shippings'])) : //Only for Ketshop shipping plugin. ?>
      <?php echo JText::_('COM_KETSHOP_NO_SHIPPING_FOUND'); ?>
    <?php endif; ?>

      <?php foreach($shipper['shippings'] as $key => $shipping) : //Display shippings. 
             //Check if the current shipping is a delivery point (only for Ketshop shipping plugin). 
             $deliveryPoint = false;
	     if($shipping['delivery_type'] == 'at_delivery_point') {
	       $deliveryPoint = true;
	     } 
      ?>

	  <div class="shipping">
	    <form action="index.php?option=com_ketshop&view=summary&task=summary.setShipper"
		  method="post" id="<?php echo $pluginName.'_'.$shipping['id']; ?>">

	    <?php if($deliveryPoint) : ?>
	      <h1><?php echo $shipping['name']; ?>&nbsp;<span class="icon-shop-location" style="font-size:18px;"></span></h1>
	    <?php else : ?>
	      <h1><?php echo $shipping['name']; ?></h1>
	      <?php echo $shipping['description']; ?>
	    <?php endif; ?>

	    <?php $totalDelay = $shipping['min_delivery_delay'] + $productsTotalDelay; //Compute total delay, (shipping delay + products delay). ?>
	    <?php if($totalDelay > 0) : ?>
	      <div class="shipping-row">
		<?php echo JText::_('COM_KETSHOP_SHIPPING_MINIMUM_DELIVERY_DELAY'); ?>
		<span class="shipping-delay"><?php echo $shipping['min_delivery_delay'] + $productsTotalDelay; ?>
					     <?php echo JText::_('COM_KETSHOP_SHIPPING_DAYS_DELAY'); ?></span>
	      </div>			   
	    <?php endif; ?>

	    <?php if($showRule && !empty($output)) : //Display shipping price rules. ?>
	      <?php echo $output; ?>
	      <div class="shipping-row">
	      <?php echo JText::_('COM_KETSHOP_SHIPPING_COST'); ?>
	      <span class="striked-price"><?php echo UtilityHelper::formatNumber($shipping['cost']); ?>
					 <?php echo $settings['currency']; ?></span>
	    <?php else : ?>
	      <div class="shipping-row">
	      <?php echo JText::_('COM_KETSHOP_SHIPPING_COST'); ?>
	    <?php endif; ?>


	    <span class="shipping-cost"><?php echo UtilityHelper::formatNumber($shipping['final_cost']); ?>
					<?php echo $settings['currency']; ?></span>
	    </div>			   

	    <?php if($deliveryPoint) : ?>
	      <table class="table table-striped">
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_STREET_SH_LABEL'); ?>
	      </td><td class="address-input">
	      <?php echo $shipping['street']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_POSTCODE_SH_LABEL'); ?>
	      </td><td>
	      <?php echo $shipping['postcode']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_CITY_SH_LABEL'); ?>
	      </td><td>
	      <?php echo $shipping['city']; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_REGION_SH_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($shipping['region']); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_COUNTRY_SH_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($shipping['country']); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_INFORMATION_SH_LABEL'); ?>
	      </td><td>
	      <?php echo $shipping['information']; ?>
	      </td></tr>
	      </table>
	    <?php endif; ?>

	    <span class="space-2"></span>
	    <input class="btn btn-info" type="submit" value="<?php echo JText::_('COM_KETSHOP_CHOOSE_SHIPPING'); ?>" />

	    <input type="hidden" name="shipping_id" value="<?php echo $shipping['id']; ?>" />
	    <input type="hidden" name="shipper_id" value="<?php echo $shipper['id']; ?>" />
	    </form>
	  </div>

      <?php endforeach; ?>
      </div>

  <?php endforeach; ?>

    <div id="modify-address">
      <a href="index.php?option=com_ketshop&view=address" class="btn"><?php echo JText::_('COM_KETSHOP_MODIFY_ADDRESS'); ?></a> 
    </div>
</div>

