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
$lang->load('com_ketshop', JPATH_ROOT.'/administrator/components/com_ketshop', $langTag);

if($task == 'add' || $task == 'remove') {
  //Don't take into account the possible changes (qty, unit price) set in the form. 
  //Get the products directly from the order table. 
  $products = OrderHelper::getProducts($orderId);
  //Get the product and option id. 
  $ids = OrderHelper::separateIds($prodIds);

  //The order must contained at least one product.
  if($task == 'remove' && count($products) == 1) {
    $data['message'] = JText::_('COM_KETSHOP_CANNOT_REMOVE_PRODUCT');
    echo json_encode($data);
    return;
  }

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

    //Check for stock.
    if($product['stock'] == 0) {
      $data['message'] = JText::sprintf('COM_KETSHOP_INSUFFICIENT_STOCK', $product['name']);
      echo json_encode($data);
      return;
    }

    $product['prod_id'] = $product['id'];
    $prodPrules = OrderHelper::setProductPriceRules($orderId, $product, $task);

    if(!empty($prodPrules)) {
      //Replace the new price rules with the ones previously set in the order.
      $product['pricerules'] = $prodPrules;
    }

    $settings = OrderHelper::getOrderSettings($orderId);
    $catalogPrice = PriceruleHelper::getCatalogPrice($product, $settings);

    //Set some required attributes.
    $product['unit_price'] = $catalogPrice->final_price;
    $product['quantity'] = 1;
    //Add the new product to the order.
    $products[] = $product;
    //Subtract from the stock.
    ShopHelper::updateStock(array($product));
  }
}

//Used with the update task.
$addStockQty = $subtractStockQty = array();

//Set the order products.
foreach($products as $key => $product) {
  if($task == 'update') {
    //Set both the product and option ids for this product.
    $ids = OrderHelper::separateIds($product['ids']);
    //Set required id attributes.
    $products[$key]['prod_id'] = $ids['prod_id'];
    $products[$key]['id'] = $ids['prod_id'];
    $products[$key]['opt_id'] = $ids['opt_id'];

    //Sanitize and check the values passed through the form.
    if(($products[$key]['unit_price'] = filter_var($product['unit_price'], FILTER_VALIDATE_FLOAT)) === false || $product['unit_price'] == 0) {
      $data['message'] = JText::sprintf('COM_KETSHOP_ERROR_INVALID_UNIT_PRICE', $product['name']);
      echo json_encode($data);
      return;
    }

    if(($products[$key]['quantity'] = filter_var($product['quantity'], FILTER_VALIDATE_INT)) === false || $product['quantity'] == 0) {
      $data['message'] = JText::sprintf('COM_KETSHOP_ERROR_INVALID_QUANTITY', $product['name']);
      echo json_encode($data);
      return;
    }

    //Check for quantity limits.
    if($products[$key]['quantity'] < $product['min_quantity']) {
      $data['message'] = JText::sprintf('COM_KETSHOP_ERROR_MIN_QUANTITY', $product['name'], $product['min_quantity']);
      echo json_encode($data);
      return;
    }

    if($products[$key]['quantity'] > $product['max_quantity']) {
      $data['message'] = JText::sprintf('COM_KETSHOP_ERROR_MAX_QUANTITY', $product['name'], $product['max_quantity']);
      echo json_encode($data);
      return;
    }

    //Check for stock.
    if($product['stock_subtract'] && $products[$key]['quantity'] > $product['stock']) {
      $data['message'] = JText::sprintf('COM_KETSHOP_INSUFFICIENT_STOCK', $product['name']);
      echo json_encode($data);
      return;
    }

    //Set the stock according to the quantity change.
    if($products[$key]['quantity'] < $product['initial_quantity']) {
      //Add the id attributes previously set.
      $product = $products[$key];
      //Set the quantity to add.
      $product['quantity'] = $product['initial_quantity'] - $products[$key]['quantity']; 
      $addStockQty[] = $product;
    }

    if($products[$key]['quantity'] > $product['initial_quantity']) {
      $product = $products[$key];
      //Set the quantity to subtract.
      $product['quantity'] = $products[$key]['quantity'] - $product['initial_quantity'];
      $subtractStockQty[] = $product;
    }
  }
  elseif($task == 'remove' && $product['prod_id'] == $ids['prod_id'] && $product['opt_id'] == $ids['opt_id']) {
    OrderHelper::setProductPriceRules($orderId, $product, $task);
    //Add again in the stock.
    ShopHelper::updateStock(array($product), 'add');
    //Remove the product from the order.
    unset($products[$key]);
    //Move to the next product.
    continue;
  }

  //Set (or add) the cart_rules_impact attribute used with the cart price rules computation.
  $products[$key]['cart_rules_impact'] = $products[$key]['unit_price'];
}

if(!empty($subtractStockQty)) {
  ShopHelper::updateStock($subtractStockQty);
}

if(!empty($addStockQty)) {
  ShopHelper::updateStock($addStockQty, 'add');
}

//Start a specific session named after the order id.
$sessionGroup = OrderHelper::setOrderSession($orderId, $products);

//Get and check the initial cart price rules linked to the order.
$orderCartPrules = OrderHelper::getCartPriceRules($orderId);
$cartPriceRules = PriceruleHelper::checkCartPriceRuleConditions($orderCartPrules, $sessionGroup);

//Set the history attribute of the price rules according wether they are applied in the
//current order or not.
foreach($orderCartPrules as $key => $orderCartPrule) {
  $orderCartPrules[$key]['history'] = 0;
  foreach($cartPriceRules as $cartPriceRule) {
    if($cartPriceRule['id'] == $orderCartPrule['id']) {
      $orderCartPrules[$key]['history'] = 1;
      break;
    }
  }
}

//Price rules targeting the shipping cost are treated aside.
$shippingPrules = array();
foreach($cartPriceRules as $key => $cartPriceRule) {
  //Put aside the shipping price rules.
  if($cartPriceRule['target'] == 'shipping_cost') {
    $shippingPrules[] = $cartPriceRule;
    unset($cartPriceRules[$key]);
  }
}

//Set the new cart amounts. 
$totalProdAmt = PriceruleHelper::getTotalProductAmount(false, $sessionGroup);
$amounts = array('cart_amount' => $totalProdAmt->amt_excl_tax,
		 'crt_amt_incl_tax' => $totalProdAmt->amt_incl_tax,
		 'final_amount' => $totalProdAmt->amt_excl_tax,
		 'fnl_amt_incl_tax' => $totalProdAmt->amt_incl_tax);

//Compute the final cart amounts according to the applied cart price rules.
if(!empty($cartPriceRules)) {
  $result = PriceruleHelper::applyCartPriceRules($cartPriceRules, $totalProdAmt, $sessionGroup);
  $products = $result['products'];
  $amounts['final_amount'] = $result['final_amount'];
  $amounts['fnl_amt_incl_tax'] = $result['fnl_amt_incl_tax'];
}

//Process the order updating.
OrderHelper::setShippingCost($orderId, $shippingPrules);
OrderHelper::updateProducts($orderId, $products);
OrderHelper::updateCartPriceRules($orderId, $orderCartPrules);
OrderHelper::updateOrder($orderId, $amounts);

//The order must be modified "on the fly" so we delete the order session after processing.
OrderHelper::deleteOrderSession($orderId);

$render = OrderHelper::getRender($orderId, $products, $amounts, $cartPriceRules, $shippingPrules);
$data['render'] = $render;

echo json_encode($data);

