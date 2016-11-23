<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.

//Use utility functions from the KetShop component.
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
require_once JPATH_SITE.'/components/com_ketshop/helpers/shop.php';

//Get some useful variables.
$quantity = ShopHelper::getTotalQuantity();
$taxMethod = JComponentHelper::getParams('com_ketshop')->get('tax_method');

//Grab the user session.
$session = JFactory::getSession();

//Purchase is done, all previous purchase session data must be deleted.
if($session->get('end_purchase', 0, 'ketshop'))
  KetshopHelper::clearPurchaseData();

//Get the cart amount.
$cartAmount = $session->get('cart_amount', array(), 'ketshop'); 

if(empty($cartAmount)) {
  $cartAmount['final_amount'] = 0;
  $cartAmount['fnl_amt_incl_tax'] = 0;
}

require(JModuleHelper::getLayoutPath('mod_ketshopcart'));

