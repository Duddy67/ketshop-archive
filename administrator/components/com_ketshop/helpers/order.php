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
   * Deletes the session of the edited order.
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
   * Returns the shop settings from the order data.
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
   * Separates 2 numbers concatenated with an underscore (eg: 78_5)
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
   * Returns the products of the given order.
   *
   * @param integer  The id of the edited order.
   *
   * @return array   The products of the order.
   */
  public static function getProducts($orderId)
  {
    //Get the products from the order.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('p.id, p.catid, p.name, p.alias, p.code, p.stock_subtract, p.published, p.min_quantity, p.max_quantity,'.
		   'p.attribute_group, p.min_stock_threshold, p.allow_order, p.stock, op.unit_price, op.unit_sale_price,'.
		   'op.tax_rate, op.opt_id, op.quantity, op.prod_id, op.option_name, op.cart_rules_impact,'.
		   'po.published AS opt_published, po.stock AS opt_stock')
	  ->from('#__ketshop_order_prod AS op')
	  ->join('LEFT', '#__ketshop_product AS p ON p.id=op.prod_id')
	  ->join('LEFT', '#__ketshop_product_option AS po ON po.prod_id=op.prod_id AND po.opt_id=op.opt_id')
	  ->where('op.order_id='.(int)$orderId)
	  ->where('(op.history=1 OR op.history=2)')
	  ->order('p.name');
    $db->setQuery($query);
    $products = $db->loadAssocList();

    //Check for product options.
    foreach($products as $key => $product) {
      if($product['opt_id']) {
	//Replace the values of the main product with those of the option.
	$products[$key]['published'] = $product['opt_published']; 
	$products[$key]['stock'] = $product['opt_stock']; 
      }

      //Remove unnecessary variables.
      unset($products[$key]['opt_published']);
      unset($products[$key]['opt_stock']);
    }

    return $products;
  }


  /**
   * Sets the product price rules (and their history attribute) linked to the added or removed product.
   *
   * history codes:
   * 0: The price rule is part of the initial order but is not currently applied (ie: the linked product has been deleted).
   * 1: The price rule is part of the initial order and is currently applied.
   * 2: The price rule is not part of the initial order and is currently applied. It will
   *    be removed from the table in case of deletion of the linked product.
   *
   * @param integer  The id of the edited order.
   * @param array  The product for which price rules have to be set. 
   * @param string  The name of the task currently applied on the order.
   *
   * @return mixed  The price rules for the product (array), void otherwise.
   */
  public static function setProductPriceRules($orderId, $product, $task)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if($task == 'remove') {
      //Remove all the price rules (if any) 
      //in case the product is not part of the initial order (history=2).
      $query->delete('#__ketshop_order_prule')
	    ->where('order_id='.(int)$orderId)
	    ->where('prod_id='.(int)$product['prod_id'])
	    ->where('history=2');
      $db->setQuery($query);
      $db->query();

      //Set the history attribute to zero for price rules in case the product 
      //is part of the initial order.
      $query->clear();
      $query->update('#__ketshop_order_prule')
	    ->set('history=0')
	    ->where('order_id='.(int)$orderId)
	    ->where('prod_id='.(int)$product['prod_id']);
      $db->setQuery($query);
      $db->query();
    }
    else { //Add product
      $priceRules = array();
      //Check first if the product is part of the initial order (ie: it as been removed
      //then added again).
      $query->select('history')
	    ->from('#__ketshop_order_prod')
	    ->where('order_id='.(int)$orderId)
	    ->where('prod_id='.(int)$product['prod_id'])
	    ->where('opt_id='.(int)$product['opt_id']);
      $db->setQuery($query);
      $history = $db->loadResult();

      if($history !== null) {
	//Update the possible initial price rules for this product.
	$query->clear();
	$query->update('#__ketshop_order_prule')
	      ->set('history=1')
	      ->where('order_id='.(int)$orderId)
	      ->where('prod_id='.(int)$product['prod_id']);
	$db->setQuery($query);
	$db->query();
      }
      else { // The added product is not being part of the initial order.
	$priceRules = $product['pricerules'];
	//Insert the possible price rules for this product. 
	//Note: The history attribute is set to 2 which means this price rule will be
	//      deleted in case this product is removed from the order.
	$values = array();
	foreach($priceRules as $priceRule) {
	  $values[] = (int)$orderId.','.(int)$priceRule['id'].','.(int)$product['id'].','.$db->Quote($priceRule['name']).
		      ','.$db->Quote($priceRule['type']).','.$db->Quote($priceRule['target']).','.$db->Quote($priceRule['operation']).
		      ','.$db->Quote($priceRule['behavior']).','.$db->Quote($priceRule['modifier']).','.$db->Quote($priceRule['application']).
		      ','.$priceRule['value'].','.$priceRule['ordering'].','.$priceRule['show_rule'].',2';
	}

	if(!empty($values)) {
	  $columns = array('order_id','prule_id','prod_id','name','type','target','operation',
			   'behavior','modifier','application','value','ordering','show_rule','history');

	  $query->clear();
	  $query->insert('#__ketshop_order_prule')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->query();
	}
      }

      return $priceRules;
    }
  }


  /**
   * Returns the cart price rules linked to the given order.
   *
   * @param integer  The id of the edited order.
   *
   * @return array  The cart price rules linked to the given order.
   */
  public static function getCartPriceRules($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('prule_id AS id, name, type, target, operation, `condition`,'.
	           'logical_opr, behavior, value, show_rule, history')
	  ->from('#__ketshop_order_prule')
	  ->where('type='.$db->Quote('cart'))
	  ->where('order_id='.(int)$orderId)
	  ->order('ordering');
    $db->setQuery($query);
    $priceRules = $db->loadAssocList();

    if(empty($priceRules)) {
      return $priceRules;
    }

    //Collect the price rule ids and set the conditions attribute.
    $ids = array();
    foreach($priceRules as $key => $priceRule) {
      $priceRules[$key]['conditions'] = array();
      $ids[] = $priceRule['id'];
    }

    //Get the conditions linked to the price rules.
    $query->clear();
    $query->select('*')
	  ->from('#__ketshop_prule_condition')
	  ->where('prule_id IN('.implode(',', $ids).')');
    $db->setQuery($query);
    $conditions = $db->loadAssocList();

    //Link the condition array to the corresponding price rule.
    foreach($priceRules as $key => $priceRule) {
      foreach($conditions as $condition) {
        if($condition['prule_id'] == $priceRule['id']) {
	  $priceRules[$key]['conditions'][] = $condition;
	}
      }
    }

    return $priceRules;
  }


  /**
   * Sets the shipping cost according to the given price rules.
   *
   * @param integer  The id of the edited order.
   * @param array  The price rules to applied on the shipping cost
   *
   * @return void
   */
  public static function setShippingCost($orderId, $priceRules)
  {
    //Get the current shipping costs.
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
    $db->setQuery($query);
    $db->query();
  }


  /**
   * Updates the products and their history attribute in database.
   *
   * history codes:
   * 0: The product is part of the initial order but not part of the current order (due to an order modification).
   * 1: The product is part of the initial order as well as the current order.
   * 2: The product is not part of the initial order but is part of the current order. It will
   *    be removed from the table in case of deletion through the order form.
   *
   * @param integer  The id of the edited order.
   * @param array  The products coming from the order form.
   *
   * @return void
   */
  public static function updateProducts($orderId, $products)
  {
    //First get the initial products.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__ketshop_order_prod')
	  ->where('order_id='.(int)$orderId)
	  ->where('(history=0 OR history=1)');
    $db->setQuery($query);
    $initialProducts = $db->loadAssocList();

    //Delete all the order products including those which are not part 
    //of the initial order (ie: history=2).
    $query->clear();
    $query->delete('#__ketshop_order_prod')
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

    $values = array();
    //Check the products of the current order.
    foreach($products as $product) {
      $history = 2;
      foreach($initialProducts as $key => $initialProduct) {
	//Check wether the product is part of the initial order.
	if($initialProduct['prod_id'] == $product['prod_id'] && $initialProduct['opt_id'] == $product['opt_id']) {
	  $history = 1;
	  //Remove the product from the array 
	  unset($initialProducts[$key]);
	  break;
	}
      }

      //Update the product.
      $values[] = (int)$orderId.','.(int)$product['prod_id'].','.(int)$product['opt_id'].','.$db->Quote($product['name']).
	          ','.$db->Quote($product['option_name']).','.$db->Quote($product['code']).','.$product['unit_sale_price'].
		  ','.$product['unit_price'].','.$product['cart_rules_impact'].','.(int)$product['quantity'].
		  ','.$product['tax_rate'].','.(int)$history;
    }

    //Set the remaining of the initial products to 0 as they are not part of the current order.
    foreach($initialProducts as $initialProduct) {
      $values[] = (int)$orderId.','.(int)$initialProduct['prod_id'].','.(int)$initialProduct['opt_id'].','.$db->Quote($initialProduct['name']).
	          ','.$db->Quote($initialProduct['option_name']).','.$db->Quote($initialProduct['code']).','.$initialProduct['unit_sale_price'].
		  ','.$initialProduct['unit_price'].','.$initialProduct['cart_rules_impact'].','.(int)$initialProduct['quantity'].
		  ','.$initialProduct['tax_rate'].',0';
    }

    $columns = array('order_id', 'prod_id', 'opt_id', 'name', 'option_name',
		     'code', 'unit_sale_price', 'unit_price', 'cart_rules_impact',
		     'quantity', 'tax_rate', 'history');

    $query->clear();
    $query->insert('#__ketshop_order_prod')
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);
    $db->query();
  }


  /**
   * Updates the cart price rule data in database.
   *
   * @param integer  The id of the edited order.
   * @param array  The cart price rules of the given order.
   *
   * @return void
   */
  public static function updateCartPriceRules($orderId, $priceRules)
  {
    if(empty($priceRules)) {
      return;
    }

    //Build the WHEN case.
    $when = '';
    foreach($priceRules as $priceRule) {
      $when .= 'WHEN order_id='.$orderId.' AND prule_id='.$priceRule['id'].' THEN '.$priceRule['history'].' ';
    }

    $case = ' history = CASE '.$when.' ELSE history END';

    //Update data.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->update('#__ketshop_order_prule')
	  ->set($case)
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

  }


  /**
   * Updates the order amounts in database.
   *
   * @param integer  The id of the edited order.
   * @param array  The amounts of the given order.
   *
   * @return void
   */
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
    $db->setQuery($query);
    $db->query();
  }
}

