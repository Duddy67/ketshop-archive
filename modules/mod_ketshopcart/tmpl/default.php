<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.

//Include css file.
$css = JFactory::getDocument();
$css->addStyleSheet(JURI::base().'modules/mod_ketshopcart/ketshopcart.css');

$link = $endLink = '';

if($quantity) {
  //If SEF is enabled we must set the Itemid variable to zero in order to
  //avoid SEF binds any previous menu item id to the cart view.  
  $Itemid = '';
  if(JFactory::getConfig()->get('sef')) {
    $Itemid = '&Itemid=0';
  }

  $link = '<a href="'.JRoute::_('index.php?option=com_ketshop&view=cart'.$Itemid).'" title="'.JText::_('MOD_KETSHOP_SEE_DETAIL_ORDER').'">';
  $endLink = '</a>';
}
?>

<div id="ketshop-cart">
  <?php echo $link; ?><img src="<?php echo JURI::base().'modules/mod_ketshopcart/cart.png'; ?>"
       id="cart-icon" width="24" height="24" alt="<?php echo htmlspecialchars(JText::_('MOD_KETSHOP_YOUR_CART')); ?>" />
  <span id="cart-information">
  <?php if($taxMethod == 'excl_tax') : //Display cart amount according to the tax method. ?>
    <?php echo JText::sprintf('MOD_KETSHOP_PRODUCTS_INTO_CART', $quantity, $cartAmount['final_amount']); ?>
    <?php echo JText::_('MOD_KETSHOP_EXCLUDING_TAXES'); ?>
  <?php else : ?>
    <?php echo JText::sprintf('MOD_KETSHOP_PRODUCTS_INTO_CART', $quantity, $cartAmount['fnl_amt_incl_tax']); ?>
    <?php echo JText::_('MOD_KETSHOP_INCLUDING_TAXES'); ?>
  <?php endif; ?>
  </span><?php echo $endLink; ?>
</div>


