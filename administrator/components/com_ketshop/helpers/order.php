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


  public static function separateIds($ids)
  {
    if(!preg_match('#^([1-9][0-9]*)_(0|[1-9][0-9]*)$#', $ids, $matches)) {
      return null;
    }

    $separatedIds = array('prod_id' => $matches[1], 'opt_id' => $matches[2]);

    return $separatedIds;
  }


  public static function getProducts($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__ketshop_order_prod')
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public static function getCartPriceRules($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('prule_id AS id, name, type, target, operation, `condition`,'.
	           'logical_opr, behavior, value, show_rule, state')
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
      $priceRules[$key]['conditions'] = array();
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
	  $priceRules[$key]['conditions'][] = $condition;
	}
      }
    }

    return $priceRules;
  }


  public static function updateProducts($orderId, $products)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->delete('#__ketshop_order_prod')
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

    $values = array();
    foreach($products as $product) {
      $values[] = (int)$orderId.','.(int)$product['prod_id'].','.(int)$product['opt_id'].','.$db->Quote($product['name']).
	          ','.$db->Quote($product['option_name']).','.$db->Quote($product['code']).','.$product['unit_sale_price'].
		  ','.$product['unit_price'].','.$product['cart_rules_impact'].','.(int)$product['quantity'].','.$product['tax_rate'];
    }

    $columns = array('order_id', 'prod_id', 'opt_id', 'name', 'option_name',
		     'code', 'unit_sale_price', 'unit_price', 'cart_rules_impact',
		     'quantity', 'tax_rate');

    $query->clear();
    $query->insert('#__ketshop_order_prod')
	  ->columns($columns)
	  ->values($values);
file_put_contents('debog_file_prod.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $db->query();

  }


  public static function updatePriceRules($orderId, $priceRules)
  {
    if(empty($priceRules)) {
      return;
    }

    $when = '';
    foreach($priceRules as $priceRule) {
      $when .= 'WHEN order_id='.$orderId.' AND prule_id='.$priceRule['id'].' THEN '.$priceRule['state'].' ';
    }

    $case = ' state = CASE '.$when.' ELSE state END';

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->update('#__ketshop_order_prule')
	  ->set($case)
	  ->where('order_id='.(int)$orderId);
file_put_contents('debog_file_prules.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $db->query();

  }


  public static function updateOrder($orderId, $amounts)
  {
    //Set the new order amounts.
    $fields = array('cart_amount='.$amounts['cart_amount'],
		    'crt_amt_incl_tax='.$amounts['crt_amt_incl_tax'],
		    'final_cart_amount='.$amounts['final_amount'],
		    'fnl_crt_amt_incl_tax='.$amounts['fnl_amt_incl_tax']); 

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->update('#__ketshop_order')
	  ->set($fields)
	  ->where('id='.(int)$orderId);
file_put_contents('debog_file_order.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $db->query();

  }
}

