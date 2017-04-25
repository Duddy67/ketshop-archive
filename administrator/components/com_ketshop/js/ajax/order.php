<?php
//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_ketshop/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
//
require_once (JPATH_BASE.'/components/com_ketshop/helpers/shop.php');
require_once (JPATH_BASE.'/components/com_ketshop/helpers/pricerule.php');
require_once (JPATH_BASE.'/administrator/components/com_ketshop/helpers/order.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

function separateIds($ids)
{
  if(!preg_match('#^([1-9][0-9]*)_(0|[1-9][0-9]*)$#', $ids, $matches)) {
    return null;
  }

  $separatedIds = array('prod_id' => $matches[1], 'opt_id' => $matches[2]);

  return $separatedIds;
}


//Get required variables.
$task = JFactory::getApplication()->input->get->get('task', '', 'string');
$orderId = JFactory::getApplication()->input->get->get('order_id', 0, 'uint');
//$optId = JFactory::getApplication()->input->get->get('option_id', 0, 'uint');
$products = JFactory::getApplication()->input->get->get('products', array(), 'array');
$prodIds = JFactory::getApplication()->input->get->get('product_ids', '', 'string');
$newQty = JFactory::getApplication()->input->get->get('new_qty', 0, 'uint');
$userId = JFactory::getApplication()->input->get->get('user_id', 0, 'uint');
$data = array();

foreach($products as $key => $product) {
  $ids = separateIds($product['ids']);
  $products[$key]['prod_id'] = $ids['prod_id'];
  $products[$key]['opt_id'] = $ids['opt_id'];
  $products[$key]['unit_price'] = filter_var($product['unit_price'], FILTER_VALIDATE_FLOAT);
  $products[$key]['quantity'] = filter_var($product['quantity'], FILTER_VALIDATE_INT);
  $products[$key]['unit_sale_price'] = $product['unit_price'] * $product['quantity'];
  $products[$key]['cart_rules_impact'] = $product['unit_price'] * $product['quantity'];
}

OrderHelper::setOrderSession($orderId, $products);
$sessionGroup = 'ketshop_order_'.$orderId;

//Get the cart price rules already linked to the order.
$orderCartPrules = OrderHelper::getCartPriceRules($orderId);
$orderCartPrules = PriceruleHelper::checkCartPriceRuleConditions($orderCartPrules, $sessionGroup);

//Get the possible extra cart price rules matching the updating of the products.
$cartPrules = PriceruleHelper::getCartPriceRules($userId);
$cartPrules = PriceruleHelper::checkCartPriceRuleConditions($cartPrules, $sessionGroup);

$cartPriceRules = array_merge($orderCartPrules, $cartPrules);

$pruleIds = $shippingPrules = array();
foreach($cartPriceRules as $key => $cartPriceRule) {
  //Put aside the shipping price rules.
  if($cartPriceRule['target'] == 'shipping_cost') {
    $shippingPrules[] = $cartPriceRule;
    unset($cartPriceRules[$key]);
  }
  //Remove duplicates rules (ie: already into the order)
  elseif(in_array($cartPriceRule['id'], $pruleIds)) {
    unset($cartPriceRules[$key]);
  }
  else {
    $pruleIds[] = $cartPriceRule['id'];
  }
}

$totalProdAmt = PriceruleHelper::getTotalProductAmount(false, $sessionGroup);

if(!empty($cartPriceRules)) {
  $result = PriceruleHelper::applyCartPriceRules($cartPriceRules, $totalProdAmt, $sessionGroup);
  $products = $result['products'];
}
else {
  $result = array('final_amount' => $totalProdAmt->amt_excl_tax, 'fnl_amt_incl_tax' => $totalProdAmt->amt_incl_tax);
}

OrderHelper::deleteOrderSession($orderId);
file_put_contents('debog_file.txt', print_r($products, true));
file_put_contents('debog_file_2.txt', print_r($priceRules, true));

/*$cases = array('unit_price' => ' unit_price = CASE ', 'unit_sale_price' => ' unit_sale_price = CASE ', 'quantity' => ' quantity = CASE ');
foreach($products as $product) {
  $ids = separateIds($product['ids']);
  $values = array();
  $values['unit_price'] = filter_var($product['unit_price'], FILTER_VALIDATE_FLOAT);
  $values['quantity'] = filter_var($product['quantity'], FILTER_VALIDATE_INT);
  $values['unit_sale_price'] = $values['unit_price'] * $values['quantity'];

  foreach($cases as $key => $case) {
    $cases[$key] .= 'WHEN order_id='.$orderId.' AND prod_id='.$ids['prod_id'].' AND opt_id='.$ids['opt_id'].' THEN '.$values[$key].' ';
  }
}

$query = '';
foreach($cases as $case) {
  $query .= $case.'END,';
}
$query = substr($query, 0, -1);

UtilityHelper::setOrderSession($orderId, $products);
$sessionGroup = 'ketshop_order_'.$orderId;
$session = PriceruleHelper::getSession($sessionGroup);
UtilityHelper::deleteOrderSession($orderId);
file_put_contents('debog_file.txt', print_r($session, true));
*/

/*
$db = JFactory::getDbo();
$query = $db->getQuery(true);

if($task == 'change_quantity') {
  //Set some return variables.
  $data['insufficient_stock'] = 0;
  $data['no_qty_change'] = 0;

  //Check the current quantity.
  $query->select('quantity')
	->from('#__ketshop_order_prod')
	->where('order_id='.(int)$orderId)
	->where('prod_id='.(int)$prodId)
	->where('opt_id='.(int)$optId);
  $db->setQuery($query);
  $currentQty = $db->loadResult();

  //No need to go further.
  if($newQty == $currentQty) {
    $data['no_qty_change'] = 1;
    echo json_encode($data);
    return;
  }

  //Need to check the stock.
  if($newQty > $currentQty) {
    $addedQty = $newQty - $currentQty;
    $table = '#__ketshop_product';
    $query->clear()
          ->select('stock');

    //Get the product stock value according to the product. 
    if($optId) { //product option
      $query->from('#__ketshop_product_option')
	    ->where('prod_id='.(int)$prodId)
	    ->where('opt_id='.(int)$optId);
    }
    else { //regular product
      $query->from('#__ketshop_product')
	    ->where('id='.(int)$prodId);
    }

    $db->setQuery($query);
    $stock = $db->loadResult();

    //Cannot change quantity.
    if($addedQty > $stock) {
      $data['insufficient_stock'] = 1;
      echo json_encode($data);
      return;
    }
  }
}
*/

echo json_encode($data);

