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
$data = array('message' => '');

//In order to work with JText we have to load the language.
//Note: As we load language from an external file the site language cannot be properly
//identified and we end up with the en-GB tag by default.
$lang = JFactory::getLanguage();
//Check the lang tag parameter has been properly retrieved.
if(empty($langTag)) {
    //If not, we'll use english by default.
    $langTag = $lang->getTag();
}
//Load language.
$lang->load('com_ketshop', JPATH_ROOT.'/components/com_ketshop', $langTag);

if($task == 'add' || $task == 'remove') {
  //Don't take into account the possible changes (qty, price). Get the products directly
  //from the order table. 
  $products = OrderHelper::getProducts($orderId);
  //
  $ids = OrderHelper::separateIds($prodIds);

  if($task == 'add') {
    //Check for duplicates.
    foreach($products as $product) {
      if($product['prod_id'] == $ids['prod_id'] && $product['opt_id'] == $ids['opt_id']) {
	$data['message'] = JText::sprintf('COM_KETSHOP_DUPLICATE_PRODUCT', $product['name']);
	echo json_encode($data);
	return;
      }
    }

    $product = ShopHelper::getProduct($ids['prod_id'], $ids['opt_id']);

    $product['prod_id'] = $product['id'];
    $product['unit_price'] = $product['unit_sale_price'];
    $product['cart_rules_impact'] = $product['unit_sale_price'];
    $product['quantity'] = 1;
    //Add the new product to the order.
    $products[] = $product;
  }
}

//Set the order products.
foreach($products as $key => $product) {
  if($task == 'update') {
    //Set both the product and option ids for this product.
    $ids = OrderHelper::separateIds($product['ids']);
    $products[$key]['prod_id'] = $ids['prod_id'];
    $products[$key]['opt_id'] = $ids['opt_id'];
  }
  elseif($task == 'remove' && $product['prod_id'] == $ids['prod_id'] && $product['opt_id'] == $ids['opt_id']) {
    //Remove the product from the order.
    unset($products[$key]);
    continue;
  }

  //Update the product prices according the new quantities and price changes.
  $products[$key]['unit_price'] = filter_var($product['unit_price'], FILTER_VALIDATE_FLOAT);
  $products[$key]['quantity'] = filter_var($product['quantity'], FILTER_VALIDATE_INT);
  $products[$key]['unit_sale_price'] = $product['unit_price'] * $product['quantity'];
  $products[$key]['cart_rules_impact'] = $product['unit_price'] * $product['quantity'];
}

file_put_contents('debog_file_prod.txt', print_r($products, true));
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

