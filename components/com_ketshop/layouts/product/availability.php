<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for params.
$item = $displayData['item'];
$params = $displayData['params'];
$view = $displayData['view'];

//In case product has options as we're in tag view, we don't display the cart icon 
//as the customer has to choose between options on the product page. 
//The cart icon is no needed in the vendor product view neither.
$displayCartIcon = true;
if($view == 'vendorproduct' || ($view == 'tag' && $item->attribute_group)) {
  $displayCartIcon = false;
}
?>

<?php if($params->get('show_stock_state') && !$item->attribute_group) : //Note: Don't display stock state when product has options. ?>
  <span class="label label-default">
    <?php echo JText::_('COM_KETSHOP_STOCK_STATE_'.strtoupper($item->stock_state)); ?>
  </span>
  <img src="<?php echo JURI::base().'components/com_ketshop/images/stock_state_'.$item->stock_state.'.gif'; ?>"
       class="stock-icon" width="13" height="20"
       alt="<?php echo $this->escape(JText::_('COM_KETSHOP_STOCK_STATE_'.strtoupper($item->stock_state))); ?>" />

  <span class="space-2"></span>
<?php endif; ?>

<?php if($displayCartIcon) : ?>
  <?php if(ShopHelper::canOrderProduct($item)) : ?>
    <a id="product-<?php echo $item->id; ?>"
       href="<?php echo JURI::base().'index.php?option=com_ketshop&task=cart.addToCart&prod_id='.$item->id.'&slug='.$item->slug.'&catid='.$item->catid.'&opt_id=0'; ?>">
      <span class="label btn-success">
      <?php echo JText::_('COM_KETSHOP_ADD_TO_CART'); ?>
      </span>
    </a>
    <a id="cart-product-<?php echo $item->id; ?>"
       href="<?php echo JURI::base().'index.php?option=com_ketshop&task=cart.addToCart&prod_id='.$item->id.'&slug='.$item->slug.'&catid='.$item->catid.'&opt_id=0'; ?>">
      <img src="<?php echo JURI::base().'components/com_ketshop/images/cart_add.png'; ?>"
	   class="cart-icon" width="24" height="24"
	   alt="<?php echo $this->escape(JText::_('COM_KETSHOP_ADD_TO_CART')); ?>" /></a>
  <?php else : ?>
    <span class="label btn-danger">
    <?php echo JText::_('COM_KETSHOP_UNAVAILABLE_PRODUCT'); ?>
    </span>
      <img src="<?php echo JURI::base().'components/com_ketshop/images/unavailable.png'; ?>"
	   class="cart-icon" width="24" height="24"
	   alt="<?php echo $this->escape(JText::_('COM_KETSHOP_UNAVAILABLE_PRODUCT')); ?>" />
  <?php endif; ?>
<?php endif; ?>


