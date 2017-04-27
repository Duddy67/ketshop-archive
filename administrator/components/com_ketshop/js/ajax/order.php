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
  $ids = OrderHelper::separateIds($product['ids']);
  $products[$key]['prod_id'] = $ids['prod_id'];
  $products[$key]['opt_id'] = $ids['opt_id'];
  $products[$key]['unit_price'] = filter_var($product['unit_price'], FILTER_VALIDATE_FLOAT);
  $products[$key]['quantity'] = filter_var($product['quantity'], FILTER_VALIDATE_INT);
  $products[$key]['unit_sale_price'] = $product['unit_price'] * $product['quantity'];
  $products[$key]['cart_rules_impact'] = $product['unit_price'] * $product['quantity'];
}

OrderHelper::setOrderSession($orderId, $products);
$sessionGroup = 'ketshop_order_'.$orderId;

//Get and check the cart price rules linked to the order.
$orderCartPrules = OrderHelper::getCartPriceRules($orderId);
$cartPriceRules = PriceruleHelper::checkCartPriceRuleConditions($orderCartPrules, $sessionGroup);

foreach($orderCartPrules as $key => $orderCartPrule) {
  $orderCartPrules[$key]['state'] = 0;
  foreach($cartPriceRules as $cartPriceRule) {
    if($cartPriceRule['id'] == $orderCartPrule['id']) {
      $orderCartPrules[$key]['state'] = 1;
      break;
    }
  }
}

$shippingPrules = array();
foreach($cartPriceRules as $key => $cartPriceRule) {
  //Put aside the shipping price rules.
  if($cartPriceRule['target'] == 'shipping_cost') {
    $shippingPrules[] = $cartPriceRule;
    unset($cartPriceRules[$key]);
  }
}

$totalProdAmt = PriceruleHelper::getTotalProductAmount(false, $sessionGroup);
$amounts = array('cart_amount' => $totalProdAmt->amt_excl_tax,
		 'crt_amt_incl_tax' => $totalProdAmt->amt_incl_tax,
		 'final_amount' => $totalProdAmt->amt_excl_tax,
		 'fnl_amt_incl_tax' => $totalProdAmt->amt_incl_tax);

if(!empty($cartPriceRules)) {
  $result = PriceruleHelper::applyCartPriceRules($cartPriceRules, $totalProdAmt, $sessionGroup);
  $products = $result['products'];
  $amounts['final_amount'] = $result['final_amount'];
  $amounts['fnl_amt_incl_tax'] = $result['fnl_amt_incl_tax'];
}

OrderHelper::updateProducts($orderId, $products);
OrderHelper::updatePriceRules($orderId, $orderCartPrules);
OrderHelper::updateOrder($orderId, $amounts);

OrderHelper::deleteOrderSession($orderId);


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

