<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
require_once (JPATH_ROOT.'/components/com_ketshop/helpers/pricerule.php');



class OrderHelper
{
  /**
   * Creates a cart and settings session variables from the order. 
   * These variables are aimed to be used with the price rule functions. 
   *
   * @param integer  The id of the edited order.
   * @param array  The products of the order.
   *
   * @return string  The group name of the created session. 
   */
  public static function setOrderSession($orderId, $products)
  {
    //Just in case a previous session for this order is hanging around.
    self::deleteOrderSession($orderId);
    $settings = self::getOrderSettings($orderId);

    //Grab the user session.
    $session = JFactory::getSession();
    $session->set('cart', $products, 'ketshop_order_'.$orderId);
    $session->set('settings', $settings, 'ketshop_order_'.$orderId);

    return 'ketshop_order_'.$orderId;
  }


  /**
   * Delete the session of the edited order.
   *
   * @param integer  The id of the edited order.
   *
   * @return void
   */
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


  /**
   * Return the shop settings from the order data.
   *
   * @param integer  The id of the edited order.
   *
   * @return array   The shop settings.
   */
  public static function getOrderSettings($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('tax_method, currency_code, rounding_rule, digits_precision')
	  ->from('#__ketshop_order')
	  ->where('id='.(int)$orderId);
    $db->setQuery($query);

    return $db->loadAssoc();
  }


  /**
   * Separate 2 numbers concatenated with an underscore (eg: 78_5)
   *
   * @param string  The product and option ids concatenated with an underscore
   *
   * @return array  The separated product and option ids.
   */
  public static function separateIds($ids)
  {
    if(!preg_match('#^([1-9][0-9]*)_(0|[1-9][0-9]*)$#', $ids, $matches)) {
      return null;
    }

    $separatedIds = array('prod_id' => $matches[1], 'opt_id' => $matches[2]);

    return $separatedIds;
  }


  /**
   * Return the products of the given order.
   *
   * @param integer  The id of the edited order.
   *
   * @return array   The products of the order.
   */
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


  /**
   * Set the price rules for the added or removed product.
   *
   * @param integer  The id of the edited order.
   * @param array  The product for which price rules have to be set. 
   * @param string  The name of the task currently applied on the order.
   *
   * @return mixed  The set price rules for the product (array), void otherwise.
   */
  public static function setProductPriceRules($orderId, $product, $task)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if($task == 'remove') {
      $query->delete('#__ketshop_order_prule')
	    ->where('order_id='.(int)$orderId)
	    ->where('prod_id='.(int)$product['prod_id'])
	    ->where('state=3');
      $db->setQuery($query);
      $db->query();

      //
      $query->clear();
      $query->update('#__ketshop_order_prule')
	    ->set('state=2')
	    ->where('order_id='.(int)$orderId)
	    ->where('prod_id='.(int)$product['prod_id']);
      $db->setQuery($query);
      $db->query();
    }
    else { //add
      $query->select('prule_id AS id, name, type, target, operation, application,'.
		     'modifier, behavior, value, show_rule, state')
	    ->from('#__ketshop_order_prule')
	    ->where('order_id='.(int)$orderId)
	    ->where('prod_id='.(int)$product['prod_id'])
	    ->order('ordering');
      $db->setQuery($query);
      $priceRules = $db->loadAssocList();

      if(!empty($priceRules)) {
	$query->clear();
	$query->update('#__ketshop_order_prule')
	      ->set('state=1')
	      ->where('order_id='.(int)$orderId)
	      ->where('prod_id='.(int)$product['prod_id']);
    //file_put_contents('debog_file_shipping.txt', print_r($query->__toString(), true));
	$db->setQuery($query);
	$db->query();
      }
      else {
	//Insert the possible price rules for this product. 
	//Note: The state attribute is set to 3 which means this price rule will be deleted in
	//      case this product is removed from the order.
	$values = array();
	foreach($product['pricerules'] as $priceRule) {
	  $values[] = (int)$orderId.','.(int)$priceRule['id'].','.(int)$product['id'].','.$db->Quote($priceRule['name']).
		      ','.$db->Quote($priceRule['type']).','.$db->Quote($priceRule['target']).','.$db->Quote($priceRule['operation']).
		      ','.$db->Quote($priceRule['behavior']).','.$db->Quote($priceRule['modifier']).','.$db->Quote($priceRule['application']).
		      ','.$priceRule['value'].','.$priceRule['ordering'].','.$priceRule['show_rule'].',3';
	}

	if(!empty($values)) {
	  $columns = array('order_id','prule_id','prod_id','name','type','target','operation',
			   'behavior','modifier','application','value','ordering','show_rule','state');

	  $query->clear();
	  $query->insert('#__ketshop_order_prule')
		->columns($columns)
		->values($values);
      //file_put_contents('debog_file_prod.txt', print_r($query->__toString(), true));
	  $db->setQuery($query);
	  $db->query();
	}
      }

      return $priceRules;
    }
  }


  public static function getCartPriceRules($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('prule_id AS id, name, type, target, operation, `condition`,'.
	           'logical_opr, behavior, value, show_rule, state')
	  ->from('#__ketshop_order_prule')
	  ->where('type='.$db->Quote('cart'))
	  ->where('order_id='.(int)$orderId)
	  ->order('ordering');
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


  public static function setShippingPriceRules($orderId, $priceRules)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('shipping_cost, final_shipping_cost')
	  ->from('#__ketshop_delivery')
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $shippingCosts = $db->loadAssoc();

    //Nothing new happened.
    if(empty($priceRules) && $shippingCosts['shipping_cost'] == $shippingCosts['final_shipping_cost']) {
      return;
    }
    //Price rules have been cancelled due to the order modification. Reset the shipping costs. 
    elseif(empty($priceRules) && $shippingCosts['shipping_cost'] > $shippingCosts['final_shipping_cost']) {
      $shippingCosts['final_shipping_cost'] = $shippingCosts['shipping_cost'];
    }
    //Compute the shipping cost according to the price rules.
    else {
      $shippingCosts['final_shipping_cost'] = PriceruleHelper::applyShippingPriceRules($shippingCosts['shipping_cost'],
										       $priceRules, 'ketshop_order_'.$orderId);
    }

    //Set the shipping costs.
    $fields = array('shipping_cost='.$shippingCosts['shipping_cost'],
		    'final_shipping_cost='.$shippingCosts['final_shipping_cost']); 

    $query->clear();
    $query->update('#__ketshop_delivery')
	  ->set($fields)
	  ->where('order_id='.(int)$orderId);
//file_put_contents('debog_file_shipping.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $db->query();
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
//file_put_contents('debog_file_prod.txt', print_r($query->__toString(), true));
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
//file_put_contents('debog_file_prules.txt', print_r($query->__toString(), true));
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
//file_put_contents('debog_file_order.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $db->query();

  }
}

