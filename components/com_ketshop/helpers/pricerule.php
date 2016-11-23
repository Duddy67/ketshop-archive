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

    //Get all the rules concerning the current user (or the group he's in).
    //The list of result is ordered to determine the level of the rules.
    //Only cart rules are selected.
    $query->select('pr.id,pr.type,pr.operation,pr.value,pr.behavior,pr.ordering,pr.show_rule,pr.children_cat,'. 
		   'pr.condition, pr.logical_opr, pr.target, pr.recipient,'.$translatedFields.'pr.application,'.
		    //For rules based on cart amount, we can retrieve both price and operator 
		    //directly from this query since cart amount is unique.
		   'prc.operator, prc.item_amount, prc.item_qty')
	  ->from('#__ketshop_price_rule AS pr')
	  ->join('LEFT', '#__ketshop_prule_recipient AS prr ON (pr.recipient="customer" '.
			 'AND prr.item_id='.$user->id.') OR (pr.recipient="customer_group" '.
			 'AND prr.item_id IN ('.$INcustGroups.')) ')
	  ->join('LEFT', '#__ketshop_prule_condition AS prc ON pr.id = prc.prule_id');

    //Check for translation.
    if(!empty($leftJoinTranslation)) {
      $query->join('LEFT', $leftJoinTranslation);
    }

    $query->where('pr.id = prr.prule_id AND pr.published = 1 AND pr.type = "cart"')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('ordering');
    $db->setQuery($query);
    $rules = $db->loadObjectList();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //Get the data from the user session.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    //Get some needed variables.
    $taxMethod = $settings['tax_method'];
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];

    $amount = $amtInclTax = 0;
    foreach($cart as $key => $product) {
      //cart_rules_impact is a specific variable used to compute the impact of the
      //cart rules on each product within the cart then to calculate the final amounts.
      //Note: We have to keep in mind that unit price is expressed excluding taxes
      //with excl_tax method and including taxes with incl_tax method.
      //So we must compute cart rules accordingly.

      //Set the cart_rules_impact attribute of each product to its unit price.
      $cart[$key]['cart_rules_impact'] = $product['unit_price'];

      //Compute the initial amounts (with and without taxes).
      if($taxMethod == 'excl_tax') {
	//Compute the excluding tax amount.
	$amount += $product['unit_price'] * $product['quantity'];
	//Compute the including tax amount.
        //Note: Taxes are applied AFTER the multiplication of product with quantity .
	$sum = $product['unit_price'] * $product['quantity'];
	$inclTaxResult = UtilityHelper::roundNumber(UtilityHelper::getPriceWithTaxes($sum, $product['tax_rate']), $rounding, $digits);
	$amtInclTax += $inclTaxResult;
      }
      else {
	//Note: Tax free amount would probably not used with incl_tax method but
	//we compute it anyway to stick to the logic of the price rule algorithm.
	$amtInclTax += $product['unit_price'] * $product['quantity'];
	$exclTaxProd = UtilityHelper::getPriceWithoutTaxes($product['unit_price'], $product['tax_rate']);
	$exclTaxResult = $exclTaxProd * $product['quantity'];
	$amount += UtilityHelper::roundNumber($exclTaxResult, $rounding, $digits);
      }
    }

    //Initialize the result array.
    $cartAmount = array();
    //Set the initial amounts.
    $cartAmount['amount'] = $amount;
    $cartAmount['amt_incl_tax'] = $amtInclTax;
    //Initialize variables we're gonna use in the foreach loop.
    $finalAmount = $fnlAmtInclTax = 0;
    $rulesInfo = array();

    //Set final amount variables to initial amounts.
    $cartAmount['final_amount'] = $amount;
    $cartAmount['fnl_amt_incl_tax'] = $amtInclTax;
    $cartAmount['rules_info'] = $rulesInfo;

    //No rule has been found. We just return the initial cart amounts.
    if(empty($rules)) {
      //Set the initial cart_rules_impact variable of the products.
      $session->set('cart', $cart, 'ketshop');
      return $cartAmount;
    }

    $ruleIds = array();
    //First we check if there any exclusive rule. 
    //Note: Take advantage of the loop to set the rule id array.
    foreach($rules as $rule) {
      //Take the highest exclusive rule into the stack (ie: ordering).
      if($rule->behavior == 'XOR') {
	//Empty the rules array then fill it only with the exclusive rule.
	$rules = array($rule);
	$ruleIds = array($rule->id);
	break;
      }

      $ruleIds[] = $rule->id;
    }

    //Get all conditions linked to the rules (to avoid to run a query through the foreach loop above).
    $query->clear();
    $query->select('prule_id, item_id, operator, item_amount, item_qty')
	  ->from('#__ketshop_prule_condition')
	  ->where('prule_id IN('.implode(',', $ruleIds).')');
    $db->setQuery($query);
    $allConditions = $db->loadObjectList(); 

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //List the rules and apply them according to their target type.
    foreach($rules as $rule) {
      //Initialize (or reset) the condition flag to false.
      $conditionValue = false;

      //Get the conditions linked to this rule.
      $conditions = array();
      foreach($allConditions as $condition) {
	if($condition->prule_id == $rule->id) {
	  $conditions[] = $condition;
	}
      }

      //Get the type (percent or absolute) and the operator (+ or -) of the operation.
      $operation = PriceruleHelper::getOperationAttributes($rule->operation);

      //We must now determinate which item type we must get, (product or
      //product category), and which type of data we have to
      //compare, (quantity or amount).
      //Note: Only product group item can be compared against amount or quantity.
      //All the other items are compared against quantity only. 
      //Note: Bundles are treated the same way as products.
      if($rule->condition == 'product_cat_amount') {
	$items = PriceruleHelper::getProductGroupData('amount', $conditions, $rule->children_cat, $taxMethod);
      }
      elseif($rule->condition == 'product_cat') {
	$items = PriceruleHelper::getProductGroupData('quantity', $conditions, $rule->children_cat);
      }
      else { //Product or bundle.
	$items = PriceruleHelper::getProductQty();
      }

      //Check if conditions are verified.
      foreach($conditions as $condition) {
	if(isset($items[$condition->item_id])) {
	  //Then check against amount or quantity.
	  if($rule->condition == 'product_cat_amount') {
	    $ifResult = PriceruleHelper::ifResult($items[$condition->item_id], $condition->operator, $condition->item_amount);
	  }
	  else {
	    $ifResult = PriceruleHelper::ifResult($items[$condition->item_id], $condition->operator, $condition->item_qty);
	  }

	  if($ifResult) { 
	    $conditionValue = true;
	    //If logical operator is OR we can leave the condition checking 
	    //as soon as one condition is verified.
	    if($rule->logical_opr == 'OR') {
	      break; //Leave the foreach loop.
	    }
	  }
	  else {
	    $conditionValue = false;
	  }
	}

	//If logical operator is AND we don't need to check any longer as soon
	//as one condition is not verified.
	if(!$conditionValue && $rule->logical_opr == 'AND') {
	  break;
	}
      }

      //If condition is true we apply rule on the target, (ie: cart amount or shipping cost).
      if($conditionValue) {
	if($rule->target == 'cart_amount') {
	  //Reset cart amounts to prevent to add up product prices twice or more in case of
	  //multiple cart rules.
	  $finalAmount = $fnlAmtInclTax = 0;

	  foreach($cart as $key => $product) {
	    if($operation->type == 'percent') {
	      $result = $cart[$key]['cart_rules_impact'] * ($rule->value / 100);
	      //Apply the cart rule.
	      $cart[$key]['cart_rules_impact'] = PriceruleHelper::applyRule($operation->operator, $result, $cart[$key]['cart_rules_impact']);
//file_put_contents('/var/www/web/debug_files/pricerule1.txt', print_r($cart[$key]['cart_rules_impact'].' : ', true),FILE_APPEND);

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
	      if($rule->application == 'after_taxes') {
		//We need product unit price with taxes.
		if($taxMethod == 'excl_tax') {
		  $cart[$key]['cart_rules_impact'] = UtilityHelper::getPriceWithTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']);
		}

                //Calculate the percentage represented by the product against the cart amount.
		$prodPercentage = ($cart[$key]['cart_rules_impact'] / $amtInclTax) * 100;
	      }
	      else { // before_taxes  
		//We need product unit price without taxes.
		if($taxMethod == 'incl_tax') {
		  $cart[$key]['cart_rules_impact'] = UtilityHelper::getPriceWithoutTaxes($cart[$key]['cart_rules_impact'], $product['tax_rate']);
		}

                //Calculate the percentage represented by the product against the cart amount.
		$prodPercentage = ($cart[$key]['cart_rules_impact'] / $amount) * 100;
	      }
	      //Note: No changing are needed if (before_tax && excl_tax) and
	      //if (after_tax && incl_tax).

	      //Apply product percentage to the rule value.
	      $result = $rule->value * ($prodPercentage / 100);

	      //Apply the cart rule to the product.
	      $cart[$key]['cart_rules_impact'] = PriceruleHelper::applyRule($operation->operator, $result, $cart[$key]['cart_rules_impact']);

	      //Now the rule is applied to the product we must perform the
	      //opposite operations.

	      if($rule->application == 'after_taxes') {
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

	  $finalAmount = UtilityHelper::roundNumber($finalAmount, $rounding, $digits);
	  $fnlAmtInclTax = UtilityHelper::roundNumber($fnlAmtInclTax, $rounding, $digits);

	  //Check that the modified cart amount is still above zero.
	  if($finalAmount < 0) {
	    //Write the error down in the log file.
	    ShopHelper::logEvent($codeLocation, 'pricerule_error', 0, 103, 'getCartAmount: final amount is under zero');
	  }
	}

	//Note: Since the shipping cost is not known yet we cannot apply any
	//rule on it. So we just store rule data into the rules info array.
	//Shipping plugins will retrieve it and will invoke the
	//applyShippingPriceRules function to get the final shipping cost.

	//Note: show_rule attribute is only used with shipping rules.
	$info = array('id' => $rule->id, 'operation' => $rule->operation,
		      'target' => $rule->target, 'value' => $rule->value,
		      'type' => $rule->type, 'name' => $rule->name,
		      'description' => $rule->description, 'show_rule' => $rule->show_rule);
	$rulesInfo[] = $info;
      }
    }

    //Set the updated cart_rules_impact variable of the products.
    $session->set('cart', $cart, 'ketshop');

    //Some shipping cost conditions may have been verified. Information
    //about them will be displayed in the cart view.
    $cartAmount['rules_info'] = $rulesInfo;

    //Some cart amount conditions have been verified.
    if($finalAmount > 0) {
      $cartAmount['final_amount'] = $finalAmount;
      $cartAmount['fnl_amt_incl_tax'] = $fnlAmtInclTax;
    }

    return $cartAmount;
  }


  //Compute a product price according to the set price rules.
  //Note: $product and $settings parameters are passed either as an associative array or as an object.
  public static function getCatalogPrice($product, $settings)
  {
    //Convert object into associative array by just casttype it.
    if(is_object($product)) {
      $product = (array)$product;
    }

    if(is_object($settings)) {
      $settings = (array)$settings;
    }

    //When coming from getProduct function, salePrice variable is called unit_sale_price.
    if(isset($product['unit_sale_price'])) {
      $salePrice = $product['unit_sale_price'];
    }
    else {
      $salePrice = $product['sale_price'];
    }

    //Initialize some needed variables.
    $basePrice = $product['base_price'];
    $productType = $product['type'];
    $taxRate = $product['tax_rate'];
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];
    $taxMethod = $settings['tax_method'];


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

    //Get all the rules concerning the product (or the group/category it's in) and the
    //current user (or the group he's in).
    //The list of result is ordered to determine their level.
    //Only catalog rules are selected.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('pr.id, pr.type, pr.operation, pr.value, pr.behavior, pr.ordering, pr.show_rule,'. 
		   'pr.target, pr.recipient,'.$translatedFields.'pr.modifier, pr.application')
	  ->from('#__ketshop_price_rule AS pr')
	  ->join('LEFT', '#__ketshop_prule_recipient AS prr ON (pr.recipient="customer" '.
			 'AND prr.item_id='.$user->id.') OR (pr.recipient="customer_group" '.
			 'AND prr.item_id IN ('.$INcustGroups.')) ')
	  ->join('LEFT', '#__ketshop_prule_target AS prt ON (pr.target="product" '.
			 'AND prt.item_id='.(int)$product['id'].') OR (pr.target="bundle" AND prt.item_id='.(int)$product['id'].') '.
			 'OR (pr.target="product_cat" AND prt.item_id='.(int)$product['catid'].')');

    //Check for translation.
    if(!empty($leftJoinTranslation)) {
      $query->join('LEFT', $leftJoinTranslation);
    }

    $query->where('pr.id = prt.prule_id AND pr.id = prr.prule_id AND pr.published = 1 AND pr.type = "catalog"')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('ordering');
    $db->setQuery($query);
    $rules = $db->loadObjectList();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //First we check if there any exclusive rule. 
    foreach($rules as $rule) {
      //Take the highest exclusive rule into the stack (ie: ordering).
      if($rule->behavior == 'XOR') {
	//Empty the rules array then fill it only with the exclusive rule.
	$rules = array($rule);
	break;
      }
    }

    //Create the catalog price object.
    $catalogPrice = new JObject;
    $catalogPrice->rules_info = array();
    //Set the variables we need for price calculation.
    $finalPrice = $salePrice;
    $rulesInfo = array();
    $showRule = 0;
    $i = 0;

    //List the rules and apply them according to their modifier type.
    foreach($rules as $rule) {
      //The highest rule on the stack defines the show_rule flag for all the 
      //following rules. 
      if($i == 0 && $rule->show_rule) {
	$showRule = 1;
      }

      //Get the type (percent or absolute) and the operator (+ or -) of the
      //operation.
      $operation = PriceruleHelper::getOperationAttributes($rule->operation);

      if($rule->modifier == 'profit_margin_modifier') {
	//Compute the profit margin of this product.
	$profitMargin = $salePrice - $basePrice;

	//Note: With profit margin, rules are always applied before taxes.

	if($operation->type == 'percent') {
	  $result = $profitMargin * ($rule->value / 100);
	}
	else { //absolute
	  $result = $rule->value;
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
	  $result = $finalPrice * ($rule->value / 100);
	  //Apply rule to final price then round the result.
	  $finalPrice = PriceruleHelper::applyRule($operation->operator, $result, $finalPrice);
	}
	else { //With absolute values, before and after taxes applications must be computed differently.
	  $result = $rule->value;

	  //Check when the rule must be applied (ie: after or before taxes).
	  //Note: We have to keep in mind that unit price is expressed excluding taxes
	  //with excl_tax method and including taxes with incl_tax method.
	  //So we must compute rules accordingly.

	  if($rule->application == 'after_taxes' && $taxMethod == 'excl_tax') {
	    //Rule must be applied to product unit price with taxes.
	    $finalPrice = UtilityHelper::getPriceWithTaxes($finalPrice, $taxRate);
	    $finalPrice = PriceruleHelper::applyRule($operation->operator, $result, $finalPrice);
	    //Do the opposite operation (ie: retrieve product price without taxes). 
	    $finalPrice = UtilityHelper::getPriceWithoutTaxes($finalPrice, $taxRate);
	  }
	  elseif($rule->application == 'before_taxes' && $taxMethod == 'incl_tax') {
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

      $info = array('id' => $rule->id, 'operation' => $rule->operation,
		    'value' => $rule->value, 'type' => $rule->type,
		    'target' => $rule->target, 'name' => $rule->name,
		    'description' => $rule->description, 'show_rule' => $showRule);
      $rulesInfo[] = $info;

      $i++;
    }

    //Set the final price.
    $catalogPrice->final_price = $finalPrice;
    $catalogPrice->rules_info = $rulesInfo;

    return $catalogPrice;
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
  //Returned value is an associative array indexed with the product id.
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


  //Return id and data of each product category to which products are mapped. 
  //Data is either quantity, (the sum of product quantities found in a category),
  //or amount (the sum of product prices found in a category).
  //Returned value is an associative array indexed with the category id.
  //Note: taxMethod argument is only used with rules based on product group amount.
  protected static function getProductGroupData($dataType, $conditions, $childrenCat, $taxMethod = '')
  {
    //Used as first argument of the logEvent function.
    $codeLocation = 'helpers/pricerule.php';

    //Grab the user session and get the cart array.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    //Arrays to store data needed to check categories.
    $productData = $productGroupData = $catids = $childCatIds = array(); 

    foreach($conditions as $condition) {
      //Store the category id set in the condition as the array index.
      $productGroupData[$condition->item_id] = 0;
      //Might be needed if childrenCat is true.
      $catids[] = $condition->item_id;
    }

    if($childrenCat) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      //Get the children categories of the categories set in the condition.
      $query->select('DISTINCT parent.id, child.id')
	    ->from('#__categories AS parent, #__categories AS child')
	    ->where('parent.lft < child.lft AND parent.rgt > child.rgt')
	    ->where('parent.id IN('.implode(',', $catids).')');
      $db->setQuery($query);
      $children = $db->loadRowList();

      //Rearrange data for more convenience.
      foreach($catids as $catid) {
        //Store the parent category id in the array index. 
	$childCatIds[$catid] = array();
	//Store children categories.
	foreach($children as $child) {
	  if($child[0] == $catid) {
	    $childCatIds[$catid][] = $child[1];
	  }
	}
      }
    }

    foreach($cart as $product) {
      //According to the tax method we use product price with or without taxes.
      //In case we're dealing with a rule based on product group amount.
      if($taxMethod == 'excl_tax') {
	$unitPrice = $product['unit_price'];
      }
      else {
	$unitPrice = UtilityHelper::getPriceWithTaxes($product['unit_price'], $product['tax_rate']);
      }

      //Store the needed attributes for each product.
      $productData[] = array('id' => $product['id'],
			     'unit_price' => $unitPrice,
			     'quantity' => $product['quantity'],
			     'catids' => $product['catids'],
			     //Because of multicategories we have to check whenever a
			     //product is found to prevent duplicates. 
			     'found' => false);
    }

    foreach($productGroupData as $catid => $data) {
      foreach($productData as $key => $product) {
	if(!$product['found'] && in_array($catid, $product['catids'])) {
	  //Set or update the amount or quantity value of each category the product is linked to.
	  if($dataType == 'amount') {
	    $productGroupData[$catid] += $product['unit_price'] * $product['quantity'];
	  }
	  else { // quantity
	    $productGroupData[$catid] += $product['quantity'];
	  }

	  //Exclude the product from the futur research.
	  $productData[$key]['found'] = true;
	}

	//Search children categories.
	if(!$product['found'] && $childrenCat) {
	  foreach($childCatIds[$catid] as $childCatId) {
	    if(in_array($childCatId, $product['catids'])) {
	      if($dataType == 'amount') {
		$productGroupData[$catid] += $product['unit_price'] * $product['quantity'];
	      }
	      else { // quantity
		$productGroupData[$catid] += $product['quantity'];
	      }

	      //Exclude the product from the futur research.
	      $productData[$key]['found'] = true;
	      break;
	    }
	  }
	}
      }
    }

    return $productGroupData;
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


  //Return the result of an IF statement.
  protected static function ifResult($leftValue, $operator, $rightValue)
  {
//file_put_contents('debog_pr_helper.txt', print_r($leftValue.':'.$operator.':'.$rightValue, true));
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


