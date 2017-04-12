<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


/**
 * #__ketshop_price_rule 
 * type:	The type of the price rule (catalog or cart).
 * operation:	The kind of operation to apply, percentage (-% +%) or absolute value (- +).
 * value:	The value of the operation (double 15,4).
 * modifier:	The part of the product price to modified, (sale price or profit margin). Only for catalog rule.
 * behavior:	Defines if the rule is exclusive or cumulative (XOR AND).
 * condition:	Defines what is the condition: quantity (product, products group, bundle) or amount (cart,products group). Only for cart rule.
 * logical_opr:	Conditions can be combined with AND or OR logical operators. Only for cart rules.
 * target:	Defines the target of the rule. Product, products group or bundle for catalog rules. Cart amount or shipping cost for cart rules.
 * recipient:	Defines the recipient of the rule: user or users group for both cart and catalog rules.
 */

/**
 * #__ketshop_prule_condition  (Only used with cart rules). 
 * prule_id:	The id of the price rule.
 * item_id:	The id of the item to check for quantity or amount, (not used with cart amount).
 * operator:	The comparison operator of the condition (<, <= >, >=, ==).
 * item_amount:  The item amount which determines the condition.
 * item_qty:	The item quantity which determines the condition, (not used with cart amount).
 */

/**
 * #__ketshop_prule_target  (Only used with catalog rules). 
 * prule_id:	The id of the price rule.
 * item_id:	The id of the target item, (product, products group, bundle). 
 */

/**
 * #__ketshop_prule_recipient  (Used with both catalog and cart rules). 
 * prule_id:	The id of the price rule.
 * item_id:	The id of the recipient item, (user, users group).
 */

defined('_JEXEC') or die; //No direct access to this file.

require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/ketshop.php';
require_once JPATH_SITE.'/components/com_ketshop/helpers/shop.php';


class PriceruleHelper
{

  public static function getCartAmount()
  {
    $cartAmount = array();
    //
    $totalProdAmt = PriceruleHelper::getTotalProductAmount();
    //Set the initial amounts.
    $cartAmount['amount'] = $totalProdAmt->amt_excl_tax;
    $cartAmount['amt_incl_tax'] = $totalProdAmt->amt_incl_tax;

    //Set final amount variables to initial amounts.
    $cartAmount['final_amount'] = $totalProdAmt->amt_excl_tax;
    $cartAmount['fnl_amt_incl_tax'] = $totalProdAmt->amt_incl_tax;

    //Get and store all the cart price rules.
    $priceRules = PriceruleHelper::getCartPriceRules();
    $cartAmount['pricerules'] = $priceRules;

    //Collect only the price rules targeting the cart amount.
    foreach($priceRules as $key => $priceRule) {
      if($priceRule['target'] != 'cart_amount') {
	unset($priceRules[$key]);
      }
    }

    //No price rule has been found. We just return the initial cart amounts.
    if(empty($priceRules)) {
      return $cartAmount;
    }

    //Get the data from the user session.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    //Get some needed variables.
    $taxMethod = $settings['tax_method'];
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];

    foreach($priceRules as $priceRule) {
      //Get the type (percent or absolute) and the operator (+ or -) of the operation.
      $operation = PriceruleHelper::getOperationAttributes($priceRule['operation']);

      //Reset cart amounts to prevent to add up product prices twice or more in case of
      //multiple cart rules.
      $finalAmount = $fnlAmtInclTax = 0;

      foreach($cart as $key => $product) {
	if($operation->type == 'percent') {
	  $pruleAmount = $cart[$key]['cart_rules_impact'] * ($priceRule['value'] / 100);
	  //Apply the cart price rule.
	  $cart[$key]['cart_rules_impact'] = PriceruleHelper::applyRule($operation->operator, $pruleAmount, $cart[$key]['cart_rules_impact']);

	  //Compute final amounts according to the tax method.
	  if($taxMethod == 'excl_tax') {
	    $finalAmount += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	    $fnlAmtInclTax += UtilityHelper::getPriceWithTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']) * $product['quantity'];
	  }
	  else {
	    $finalAmount += UtilityHelper::getPriceWithoutTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']) * $product['quantity'];
	    $fnlAmtInclTax += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	  }
	}
	else { //absolute
	  if($priceRule['application'] == 'after_taxes') {

	    //We need product unit price with taxes.
	    if($taxMethod == 'excl_tax') {
	      $cart[$key]['cart_rules_impact'] = UtilityHelper::getPriceWithTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']);
	    }

	    //Calculate the percentage represented by the product against the cart amount.
	    $prodPercentage = ($cart[$key]['cart_rules_impact'] / $cartAmount['amt_incl_tax']) * 100;
	  }
	  else { // before_taxes  

	    //We need product unit price without taxes.
	    if($taxMethod == 'incl_tax') {
	      $cart[$key]['cart_rules_impact'] = UtilityHelper::getPriceWithoutTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']);
	    }

	    //Calculate the percentage represented by the product against the cart amount.
	    $prodPercentage = ($cart[$key]['cart_rules_impact'] / $cartAmount['amount']) * 100;
	  }
	  //Note: No changing are needed if (before_tax && excl_tax) and
	  //if (after_tax && incl_tax).

	  //Apply product percentage to the rule value.
	  $result = $priceRule['value'] * ($prodPercentage / 100);

	  //Apply the cart rule to the product.
	  $cart[$key]['cart_rules_impact'] = PriceruleHelper::applyRule($operation->operator, $result, $cart[$key]['cart_rules_impact']);

	  //Now the rule is applied to the product we must perform the
	  //opposite operations.

	  if($priceRule['application'] == 'after_taxes') {
	    if($taxMethod == 'excl_tax') {
	      $fnlAmtInclTax += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	      //Get the tax free product unit price back.
	      $cart[$key]['cart_rules_impact'] = UtilityHelper::getPriceWithoutTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']);
	      //Set the tax free amount.
	      $finalAmount += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	    }
	    else {
	      $fnlAmtInclTax += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	      $finalAmount += UtilityHelper::getPriceWithoutTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']) * $product['quantity'];
	    }
	  }
	  else { // before_taxes
	    if($taxMethod == 'excl_tax') {
	      //Set the tax free amount
	      $finalAmount += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	      //and the final amount with taxes.
	      $fnlAmtInclTax += UtilityHelper::getPriceWithTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']) * $product['quantity'];
	    }
	    else { //incl_tax
	      $finalAmount += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	      //Get the product unit price back with taxes.
	      $cart[$key]['cart_rules_impact'] = UtilityHelper::getPriceWithTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']);
	      $fnlAmtInclTax += $cart[$key]['cart_rules_impact'] * $product['quantity'];
	    }
	  }
	}
      }
    }

    $finalAmount = UtilityHelper::roundNumber($finalAmount, $rounding, $digits);
    $fnlAmtInclTax = UtilityHelper::roundNumber($fnlAmtInclTax, $rounding, $digits);

    //Check that the modified cart amount is still above zero.
    if($finalAmount <= 0) {
      //Write the error down in the log file.
      ShopHelper::logEvent($codeLocation, 'pricerule_error', 0, 103, 'getCartAmount: final amount is under zero');
    }

    //Set the updated cart_rules_impact variable of the products.
    $session->set('cart', $cart, 'ketshop');

    $cartAmount['final_amount'] = $finalAmount;
    $cartAmount['fnl_amt_incl_tax'] = $fnlAmtInclTax;

    return $cartAmount;
  }


  public static function getCatalogPrice($product, $settings)
  {
    //Create the catalog price object.
    $catalogPrice = new JObject;
    $catalogPrice->pricerules = array();

    if(empty($product['pricerules'])) {
      $catalogPrice->final_price = $product['sale_price'];
      return $catalogPrice;
    }

    //Initialize some needed variables.
    $priceRules = $product['pricerules'];
    $basePrice = $product['base_price'];
    $salePrice = $product['sale_price'];
    $taxRate = $product['tax_rate'];
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];
    $taxMethod = $settings['tax_method'];

    //Set the variables we need for price calculation.
    $finalPrice = $salePrice;
    $showRule = 0;
    $i = 0;

    //List the price rules and apply them according to their modifier type.
    foreach($priceRules as $key => $priceRule) {
      //The highest rule on the stack defines the show_rule flag for all the 
      //following price rules. 
      if($i == 0 && $priceRule['show_rule']) {
	$showRule = 1;
      }

      //Get the type (percent or absolute) and the operator (+ or -) of the
      //operation.
      $operation = PriceruleHelper::getOperationAttributes($priceRule['operation']);

      if($priceRule['modifier'] == 'profit_margin_modifier') {
	//Compute the profit margin of this product.
	$profitMargin = $salePrice - $basePrice;

	//Note: With profit margin, rules are always applied before taxes.

	if($operation->type == 'percent') {
	  $result = $profitMargin * ($priceRule['value'] / 100);
	}
	else { //absolute
	  $result = $priceRule['value'];
	}

	//Apply rule to profit margin then round the result.
	$finalProfitMargin = PriceruleHelper::applyRule($operation->operator, $result, $profitMargin);

        //Check the modified profit margin is still above zero.
	if($finalProfitMargin <= 0) {
	  ShopHelper::logEvent($codeLocation, 'pricerule_error', 0, 101, 'getCatalogPrice: final profit margin is zero or under zero');
	  //Reset to the original value.
	  $catalogPrice->final_price = $salePrice;

	  return $catalogPrice;
	}

	//Compute the product price with the modified profit margin.
	$finalPrice = $basePrice + $finalProfitMargin;
      }
      else { //sale_price_modifier
	//With percentages, before and after taxes applications are not
	//taking in account cause they give the same final price value in the end.
	if($operation->type == 'percent') {
	  $result = $finalPrice * ($priceRule['value'] / 100);
	  //Apply rule to final price then round the result.
	  $finalPrice = PriceruleHelper::applyRule($operation->operator, $result, $finalPrice);
	}
	else { //With absolute values, before and after taxes applications must be computed differently.
	  $result = $priceRule['value'];

	  //Check when the rule must be applied (ie: after or before taxes).
	  //Note: We have to keep in mind that unit price is expressed excluding taxes
	  //with excl_tax method and including taxes with incl_tax method.
	  //So we must compute rules accordingly.

	  if($priceRule['application'] == 'after_taxes' && $taxMethod == 'excl_tax') {
	    //Rule must be applied to product unit price with taxes.
	    $finalPrice = UtilityHelper::getPriceWithTaxes($finalPrice, $taxRate);
	    $finalPrice = PriceruleHelper::applyRule($operation->operator, $result, $finalPrice);
	    //Do the opposite operation (ie: retrieve product price without taxes). 
	    $finalPrice = UtilityHelper::getPriceWithoutTaxes($finalPrice, $taxRate);
	  }
	  elseif($priceRule['application'] == 'before_taxes' && $taxMethod == 'incl_tax') {
	    //Rule must be applied to product unit price without taxes.
	    $finalPrice = UtilityHelper::getPriceWithoutTaxes($finalPrice, $taxRate);
	    $finalPrice = PriceruleHelper::applyRule($operation->operator, $result, $finalPrice);
	    //Do the opposite operation (ie: retrieve product price with taxes). 
	    $finalPrice = UtilityHelper::getPriceWithTaxes($finalPrice, $taxRate);
	  }
	  else { //For the other cases we just apply the rule as it is.
	    $finalPrice = PriceruleHelper::applyRule($operation->operator, $result, $finalPrice);
	  }
	}

	//Round the result.
	$finalPrice = UtilityHelper::roundNumber($finalPrice, $rounding, $digits);

	//Check the modified final price is still above zero.
	if($finalPrice <= 0) {
	  ShopHelper::logEvent($codeLocation, 'pricerule_error', 0, 102, 'getCatalogPrice: final price is zero or under zero');
	  //Reset to the original value.
	  $catalogPrice->final_price = $salePrice;

	  return $catalogPrice;
	}
      }

      $priceRules[$key]['show_rule'] = $showRule;

      $i++;
    }

    $catalogPrice->final_price = $finalPrice;
    $catalogPrice->pricerules = $priceRules;

    return $catalogPrice;
  }


  public static function getCatalogPriceRules($product)
  {
    //Used as first argument of the logEvent function.
    $codeLocation = 'helpers/pricerule.php';

    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    //Get the user data.
    $user = JFactory::getUser();
    //Get user group ids to which the user belongs to. 
    $groups = JAccess::getGroupsByUser($user->id);
    $INcustGroups = implode(',', $groups);

    //The translated fields of a price rule.
    $translatedFields = 'pr.name,pr.description,';
    $leftJoinTranslation = '';
    //Check if a translation is needed.
    if(ShopHelper::switchLanguage()) {
      //Get the SQL query parts needed for the translation of the price rules.
      $pruleTranslation = ShopHelper::getTranslation('price_rule', 'id', 'pr', 'pr');
      //Translation fields are now defined by the SQL conditions.
      $translatedFields = $pruleTranslation->translated_fields.',';
      //Build the left join SQL clause.
      //$leftJoinTranslation = 'LEFT OUTER JOIN '.$pruleTranslation->left_join;
      $leftJoinTranslation = $pruleTranslation->left_join;
    }

    //Check for possible coupon price rule.
    $couponQuery = PriceruleHelper::setCouponQuery();

    //Get all the rules concerning the product (or the group/category it's in) and the
    //current user (or the group he's in).
    //The list of result is ordered to determine their level.
    //Only catalog rules are selected.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('pr.id, pr.type, pr.operation, pr.value, pr.behavior, pr.ordering, pr.show_rule,'. 
		   'pr.target, pr.recipient, pr.ordering,'.$translatedFields.'pr.modifier, pr.application')
	  ->from('#__ketshop_price_rule AS pr')
	  ->join('LEFT', '#__ketshop_prule_recipient AS prr ON (pr.recipient="customer" '.
			 'AND prr.item_id='.$user->id.') OR (pr.recipient="customer_group" '.
			 'AND prr.item_id IN ('.$INcustGroups.')) ')
	  ->join('LEFT', '#__ketshop_prule_target AS prt ON (pr.target="product" '.
			 'AND prt.item_id='.(int)$product['id'].') OR (pr.target="bundle" AND prt.item_id='.(int)$product['id'].') '.
			 'OR (pr.target="product_cat" AND prt.item_id='.(int)$product['catid'].')')
	  ->join('LEFT', '#__ketshop_coupon AS cp ON cp.prule_id=pr.id');

    //Check for translation.
    if(!empty($leftJoinTranslation)) {
      $query->join('LEFT', $leftJoinTranslation);
    }

    $query->where('pr.id = prt.prule_id AND pr.id = prr.prule_id AND pr.published = 1 AND pr.type = "catalog"')
	  ->where($couponQuery)
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('ordering');
    $db->setQuery($query);
    $catalogPriceRules = $db->loadAssocList();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //Check for an possible exclusive rule. 
    $delete = false;
    foreach($catalogPriceRules as $key => $catalogPriceRule) {
      //An exclusive rule has been found. 
      if($delete) {
	//Delete the rest of the price rule array.
	unset($catalogPriceRules[$key]);
	continue;
      }

      //In case of exclusive rule, the rest of the price rule array has to be deleted.
      if($catalogPriceRule['behavior'] == 'XOR') {
	$delete = true;
      }
    }

    return $catalogPriceRules;
  }


  public static function getCartPriceRules()
  {
    //Used as first argument of the logEvent function.
    $codeLocation = 'helpers/pricerule.php';

    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    //Get the user data.
    $user = JFactory::getUser();
    //Get user group ids to which the user belongs to. 
    $groups = JAccess::getGroupsByUser($user->id);
    $INcustGroups = implode(',', $groups);

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //The translated fields of a price rule.
    $translatedFields = 'pr.name,pr.description,';
    $leftJoinTranslation = '';
    //Check if a translation is needed.
    if(ShopHelper::switchLanguage()) {
      //Get the SQL query parts needed for the translation of the price rules.
      $pruleTranslation = ShopHelper::getTranslation('price_rule', 'id', 'pr', 'pr');
      //Translation fields are now defined by the SQL conditions.
      $translatedFields = $pruleTranslation->translated_fields.',';
      //Build the left join SQL clause.
      //$leftJoinTranslation = 'LEFT OUTER JOIN '.$pruleTranslation->left_join;
      $leftJoinTranslation = $pruleTranslation->left_join;
    }

    //Check for possible coupon price rule.
    $couponQuery = PriceruleHelper::setCouponQuery();

    //Get all the cart price rules concerning the current user (or the group he's in).
    //The list of result is ordered to determine the level of the rules.
    $query->select('pr.id,pr.type,pr.operation,pr.value,pr.behavior,pr.ordering,pr.show_rule,pr.children_cat,'. 
		   'pr.condition, pr.logical_opr, pr.target, pr.recipient,'.$translatedFields.'pr.application')
	  ->from('#__ketshop_price_rule AS pr')
	  ->join('LEFT', '#__ketshop_prule_recipient AS prr ON (pr.recipient="customer" '.
			 'AND prr.item_id='.$user->id.') OR (pr.recipient="customer_group" '.
			 'AND prr.item_id IN ('.$INcustGroups.')) ')
	  ->join('LEFT', '#__ketshop_coupon AS cp ON cp.prule_id=pr.id');

    //Check for translation.
    if(!empty($leftJoinTranslation)) {
      $query->join('LEFT', $leftJoinTranslation);
    }

    $query->where('pr.id = prr.prule_id AND pr.published = 1 AND pr.type = "cart"')
	  ->where($couponQuery)
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('ordering');
    $db->setQuery($query);
    $cartPriceRules = $db->loadAssocList();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    if(empty($cartPriceRules)) {
      return $cartPriceRules;
    }

    //Collect the price rule ids.
    $pruleIds = array();
    foreach($cartPriceRules as $cartPriceRule) {
      $pruleIds[] = $cartPriceRule['id'];
    }

    //Get all conditions linked to the price rules.
    $query->clear();
    $query->select('*')
	  ->from('#__ketshop_prule_condition')
	  ->where('prule_id IN('.implode(',', $pruleIds).')');
    $db->setQuery($query);
    $conditions = $db->loadAssocList(); 

    //Add the corresponding conditions (array) to each price rules.
    foreach($cartPriceRules as $key => $cartPriceRule) {
      $cartPriceRules[$key]['conditions'] = array();

      foreach($conditions as $condition) {
        if($condition['prule_id'] == $cartPriceRule['id']) {
	  $cartPriceRules[$key]['conditions'][] = $condition;
	}
      }
    }

    return PriceruleHelper::checkCartPriceRuleConditions($cartPriceRules);
  }


  public static function checkCartPriceRuleConditions($cartPriceRules)
  {
    $delete = false;
    foreach($cartPriceRules as $key => $cartPriceRule) {
      if($delete) {
	unset($cartPriceRules[$key]);
	continue;
      }

      $conditions = $cartPriceRule['conditions'];
      $attribute = 'item_qty';

      if($cartPriceRule['condition'] == 'product_cat_amount') {
	$itemAttr = PriceruleHelper::getProdAttrByCategory(false);
	$attribute = 'item_amount';
      }
      elseif($cartPriceRule['condition'] == 'product_cat') {
	$itemAttr = PriceruleHelper::getProdAttrByCategory();
      }
      elseif($cartPriceRule['condition'] == 'total_prod_qty') {
	//
	$itemAttr = array(ShopHelper::getTotalQuantity(false));
      }
      elseif($cartPriceRule['condition'] == 'total_prod_amount') {
	$itemAttr = array(PriceruleHelper::getTotalProductAmount(true));
	$attribute = 'item_amount';
      }
      else { // product or bundle quantity
	$itemAttr = PriceruleHelper::getProductQty();
      }

      //Check conditions and handle the price rule accordingly.
      foreach($conditions as $condition) {
	//As soon as a condition is true whereas the logical operator is set to OR or is
	//empty (ie: total_prod_qty, total_prod_amount), the price rule is valid. 
        if(PriceruleHelper::isTrue($itemAttr[$condition['item_id']], $condition['operator'], $condition[$attribute]) &&
	    ($cartPriceRule['logical_opr'] == 'OR' || $cartPriceRule['logical_opr'] == '')) {
	  break;
	}

	//As soon as a condition is false whereas the logical operator is set to AND or is
	//empty (ie: unique condition),the price rule can be removed from the array. 
        if(!PriceruleHelper::isTrue($itemAttr[$condition['item_id']], $condition['operator'], $condition[$attribute]) &&
	    ($cartPriceRule['logical_opr'] == 'AND' || $cartPriceRule['logical_opr'] == '')) {
	  $delete = true;
	  break;
	}
      }

      if($delete) {
	unset($cartPriceRules[$key]);
	$delete = false;
	continue;
      }

      if($cartPriceRule['behavior'] == 'XOR') {
	$delete = true;
      }
    }

    return $cartPriceRules;
  }


  protected static function getTotalProductAmount($currentTaxMethod = false)
  {
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    $amtExclTax = $amtInclTax = 0;

    foreach($cart as $product) {
      //Note: We have to keep in mind that unit price is expressed excluding taxes
      //with excl_tax method and including taxes with incl_tax method.
      //So we must compute cart rules accordingly.
      //Compute the initial amounts (with and without taxes).
      if($settings['tax_method'] == 'excl_tax') {
	//Compute the excluding tax amount.
	$amtExclTax += $product['unit_price'] * $product['quantity'];
	//Compute the including tax amount.
        //Note: Taxes are applied AFTER the multiplication of product with quantity .
	$sum = $product['unit_price'] * $product['quantity'];
	$inclTaxResult = UtilityHelper::roundNumber(UtilityHelper::getPriceWithTaxes($sum, $product['tax_rate']),
										     $settings['rounding'], $settings['digits']);
	$amtInclTax += $inclTaxResult;
      }
      else {
	//Note: Tax free amount would probably not used with incl_tax method but
	//we compute it anyway to stick to the logic of the price rule algorithm.
	$amtInclTax += $product['unit_price'] * $product['quantity'];
	$exclTaxProd = UtilityHelper::getPriceWithoutTaxes($product['unit_price'], $product['tax_rate']);
	$exclTaxResult = $exclTaxProd * $product['quantity'];
	$amtExclTax += UtilityHelper::roundNumber($exclTaxResult, $settings['rounding'], $settings['digits']);
      }
    }

    if($currentTaxMethod) {
      if($settings['tax_method'] == 'excl_tax') {
	return $amtExclTax;
      }
      else {
	return $amtInclTax;
      }
    }

    //Create and set the cart amounts object.
    $totalProdAmount = new JObject;
    $totalProdAmount->amt_incl_tax = $amtInclTax;
    $totalProdAmount->amt_excl_tax = $amtExclTax;

    return $totalProdAmount;
  }


  protected static function getProdAttrByCategory($quantity = true)
  {
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    $prodAttrByCat = array();

    foreach($cart as $product) {
      //Set the product unit price according to the tax method beforehand in case amount attribute.
      $unitPrice = $product['unit_price'];
      if(!$quantity && $settings['tax_method'] == 'incl_tax') {
	$unitPrice = UtilityHelper::getPriceWithTaxes($product['unit_price'], $product['tax_rate']);
      }

      if(array_key_exists($product['catid'], $prodAttrByCat)) {
	if($quantity) {
	  $prodAttrByCat[$product['catid']] += $product['quantity'];
	}
	else {
	  $prodAttrByCat[$product['catid']] += ($unitPrice * $product['quantity']);
	}
      }
      else {
	if($quantity) {
	  $prodAttrByCat[$product['catid']] = $product['quantity'];
	}
	else {
	  $prodAttrByCat[$product['catid']] = $unitPrice * $product['quantity'];
	}
      }
    }

    return $prodAttrByCat;
  }


  private static function setCouponQuery()
  {
    //Get the coupon session array.
    $session = JFactory::getSession();
    $coupons = $session->get('coupons', array(), 'ketshop'); 
    //By default the coupon price rules are ruled out.
    $couponQuery = '(pr.behavior!="CPN_AND" AND pr.behavior!="CPN_XOR")';

    //Check the coupon session array.
    if(!empty($coupons)) {
      $couponQuery = '';
      //Concatenate the coupon codes whith OR operators. 
      foreach($coupons as $code) {
	$couponQuery .= 'cp.code="'.$code.'" OR '; 
      }

      //Remove the OR condition (include spaces) from the end of the string.
      $couponQuery = substr($couponQuery, 0, -4);
      //Search for both coupon and regular price rules.
      $couponQuery = '(('.$couponQuery.') OR (pr.behavior!="CPN_AND" AND pr.behavior!="CPN_XOR"))';
    }

    return $couponQuery;
  }


  public static function checkCoupon($code)
  {
    //Check for a valid code.
    if(!preg_match('#^[a-zA-Z0-9-_]{5,}$#', $code)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_ERROR_COUPON_CODE_NOT_VALID'), 'warning');
      return false;
    }

    $user = JFactory::getUser();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the needed coupon data to validate (or not) the code.
    $query->select('c.id, c.name, c.prule_id, c.max_nb_uses, c.max_nb_coupons, cc.nb_uses')
	  ->from('#__ketshop_coupon AS c')
	  ->join('LEFT', '#__ketshop_coupon_customer AS cc ON cc.customer_id='.(int)$user->get('id').' AND cc.code='.$db->quote($code))
	  ->where('c.code='.$db->quote($code).' AND c.published=1');
    // Setup the query
    $db->setQuery($query);
    $coupon = $db->loadAssoc();

    if(is_null($coupon)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_WARNING_NO_MATCHING_CODE'), 'warning');
      return false;
    }

    //The stock of coupons is empty.
    if($coupon['max_nb_coupons'] == 0) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_NOTICE_NO_MORE_COUPON_AVAILABLE'), 'notice');
      return false;
    }

    //The number of uses per customer must be checked.
    if($coupon['max_nb_uses'] > 0) {
      //The number of uses has been reached (or exceeded) by the customer.
      if($coupon['nb_uses'] >= $coupon['max_nb_uses']) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_WARNING_COUPON_CANNOT_BE_USED'), 'warning');
	return false;
      }
    }

    //Grab the user session.
    $session = JFactory::getSession();
    //Create the coupon session array if it doesn't exist.
    if(!$session->has('coupons', 'ketshop')) {
      $session->set('coupons', array(), 'ketshop');
    }

    //Get the coupon session array.
    $coupons = $session->get('coupons', array(), 'ketshop');
    //If the price rule id is already in the array we leave the function to prevent to
    //decrease the stock of coupon (or increase the number of uses) once again.
    if(in_array($code, $coupons)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_WARNING_COUPON_ALREADY_USED'), 'warning');
      return false;
    }

    //Store the coupon code.
    $coupons[] = $code;
    $session->set('coupons', $coupons, 'ketshop');

    if($user->get('guest') != 1 && $coupon['max_nb_uses'] > 0 && ($coupon['nb_uses'] < $coupon['max_nb_uses'] || empty($coupon['nb_uses']))) {
      if(empty($coupon['nb_uses'])) {
	$columns = array('customer_id', 'code', 'nb_uses');
	$values = (int)$user->get('id').','.$db->quote($code).',1'; 
	//Insert a new row for this customer/code.
	$query->clear();
	$query->insert('#__ketshop_coupon_customer')
	      ->columns($columns)
	      ->values($values);
	$db->setQuery($query);
	$db->execute();
      }
      else { //Increase the number of uses of the coupon for this customer.
	$query->clear();
	$query->update('#__ketshop_coupon_customer')
	      ->set('nb_uses = nb_uses + 1')
	      ->where('customer_id='.(int)$user->get('id').' AND code='.$db->quote($code));
	$db->setQuery($query);
	$db->execute();
      }
    }

    //The stock of coupons is not unlimited (-1) so we have to decrease its value.
    if($coupon['max_nb_coupons'] > 0) {
      $query->clear();
      $query->update('#__ketshop_coupon')
	    ->set('max_nb_coupons = max_nb_coupons - 1')
	    ->where('id='.(int)$coupon['id']);
      $db->setQuery($query);
      $db->execute();
    }

    ShopHelper::updateProductPrices(true);
    ShopHelper::updateCartAmount();

    return;
  }


  public static function applyShippingPriceRules($shippingCost, $shippingPriceRules)
  {
    if($shippingCost <= 0) { //We don't allow division by zero.
      return 0;
    }

    //Initialize the final shipping cost.
    $finalShippingCost = $shippingCost;
    foreach($shippingPriceRules as $shippingPriceRule) {
      //Get the percent/absolute and +/- attributes
      $operation = PriceruleHelper::getOperationAttributes($shippingPriceRule['operation']);

      if($operation->type == 'percent') {
	$result = $shippingCost * ($shippingPriceRule['value'] / 100);
      }
      else { //absolute
	$result = $shippingPriceRule['value'];
      }

      //Apply rule to shipping cost.
      $finalShippingCost = PriceruleHelper::applyRule($operation->operator, $result, $finalShippingCost);

      if($finalShippingCost < 0) {
	$finalShippingCost = 0;
      }

      //Get rounding data from session user.
      $session = JFactory::getSession();
      $settings = $session->get('settings', array(), 'ketshop'); 
      $rounding = $settings['rounding_rule'];
      $digits = $settings['digits_precision'];

      //Round the final shipping cost.
      $finalShippingCost = UtilityHelper::roundNumber($finalShippingCost, $rounding, $digits);
    }

    return $finalShippingCost;
  }


  //Return id and quantity of each product within the cart.
  //Returned value as an associative array indexed with the product id.
  protected static function getProductQty()
  {
    //Grab the user session and get the cart array.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    $productQty = array();

    foreach($cart as $product) {
      $prodId = $product['id'];
      $productQty[$prodId] = $product['quantity'];
    }

    return $productQty;
  }


  protected static function getOperationAttributes($operation)
  {
    $op = new JObject;

    //Set the type attribute.
    if(preg_match('#%#', $operation)) {
      $op->type = 'percent';
    }
    else {
      $op->type = 'absolute';
    }

    //Extract the operator from the operation sign.
    if(preg_match('#([+|-])%#', $operation, $matches)) {
      $op->operator = $matches[1];
    }
    else {
      $op->operator = $operation;
    }

    return $op;
  }


  protected static function applyRule($operator, $result, $value)
  {
    if($operator == '+') {
      return $value + $result;
    }
    else {
      return $value - $result;
    }
  }


  //Return whether a given comparison between 2 values is true or not. 
  protected static function isTrue($leftValue, $operator, $rightValue)
  {
    switch($operator) {
      case 'lt': //Lower Than
	return ($leftValue < $rightValue) ? true : false;

      case 'gt': //Greater Than
	return ($leftValue > $rightValue) ? true : false;

      case 'ltoet': //Lower Than Or Equal To
	return ($leftValue <= $rightValue) ? true : false;

      case 'gtoet': //Greater Than Or Equal To
	return ($leftValue >= $rightValue) ? true : false;

      case 'e': //Equal
	return ($leftValue == $rightValue) ? true : false;
    }
  }
}


