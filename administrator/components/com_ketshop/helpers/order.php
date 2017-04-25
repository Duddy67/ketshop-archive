<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class OrderHelper
{
  /* Creates a cart and settings session variables from the order. These variables are
   * aimed to be used with the price rule functions. 
  */
  public static function setOrderSession($orderId, $products)
  {
    self::deleteOrderSession($orderId);

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('tax_method, currency_code, rounding_rule, digits_precision')
	  ->from('#__ketshop_order')
	  ->where('id='.(int)$orderId);
    $db->setQuery($query);
    $settings = $db->loadAssoc();

    //Grab the user session.
    $session = JFactory::getSession();
    $session->set('cart', $products, 'ketshop_order_'.$orderId);
    $session->set('settings', $settings, 'ketshop_order_'.$orderId);

    return;
  }


  public static function deleteOrderSession($orderId)
  {
    $session = JFactory::getSession();
    //Check if variable exists. If it does we delete it.
    if($session->has('cart', 'ketshop_order_'.$orderId)) {
      $session->clear('cart', 'ketshop_order_'.$orderId);
    }

    if($session->has('settings', 'ketshop_order_'.$orderId)) {
      $session->clear('settings', 'ketshop_order_'.$orderId);
    }

    return;
  }


  public static function getCartPriceRules($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('prule_id AS id, name, type, target, operation, value, show_rule')
	  ->from('#__ketshop_order_prule')
	  ->where('type='.$db->Quote('cart'))
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $priceRules = $db->loadAssocList();

    if(empty($priceRules)) {
      return $priceRules;
    }

    $ids = array();
    foreach($priceRules as $key => $priceRule) {
      $priceRules[$key]['pricerules'] = array();
      $ids[] = $priceRule['id'];
    }

    $query->clear();
    $query->select('*')
	  ->from('#__ketshop_prule_condition')
	  ->where('prule_id IN('.implode(',', $ids).')');
    $db->setQuery($query);
    $conditions = $db->loadAssocList();

    foreach($priceRules as $key => $priceRule) {
      foreach($conditions as $condition) {
        if($condition['prule_id'] == $priceRule['id']) {
	  $priceRules[$key]['pricerules'] = $condition;
	}
      }
    }

    return $priceRules;
  }
}

