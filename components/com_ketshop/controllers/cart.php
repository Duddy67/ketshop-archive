<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
//Note: Using global variable like JPATH_COMPONENT_ADMINISTRATOR or
//JPATH_COMPONENT_SITE here causes weird path problems. So we build the path
//from the root.
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/utility.php';
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/order.php';
require_once JPATH_ROOT.'/components/com_ketshop/helpers/pricerule.php';
require_once JPATH_ROOT.'/components/com_ketshop/helpers/shop.php';
 


class KetshopControllerCart extends JControllerForm
{
  //The cart view url.
  protected $cartView = 'option=com_ketshop&view=cart';
  //Used as first argument of the logEvent function.
  protected $codeLocation = 'controllers/cart.php';


  public function addToCart($data = array())
  {
    //Grab the user session.
    $session = JFactory::getSession();

    //Check safety variables.

    //Purchase is done, all purchase session data must be deleted.
    if($session->get('end_purchase', 0, 'ketshop')) {
      ShopHelper::clearPurchaseData();
      return;
    }

    //Order is locked, the user cannot add products anymore.
    //The only thing left to do is to pay.
    if($session->get('locked', 0, 'ketshop')) {
      $this->setRedirect('index.php?option=com_ketshop&task=payment.setPayment');
      return;
    }

    //If the cart array doesn't exist we create it.
    if(!$session->has('cart', 'ketshop')) {
      $this->initializeCart();
    }

    $cart = $session->get('cart', array(), 'ketshop'); 
    //Retrieve the location the user comes from.
    $location = $session->get('location', '', 'ketshop'); 

    //Product comes from GET url, (ie: tag or product view).
    if(empty($data)) {
      //Sanitize sensitive data.
      $id = $this->input->get('prod_id', 0, 'uint');
      $varId = $this->input->get('var_id', 0, 'uint');

      //Check for a valid id.
      if(!(int)$id) {
	return;
      }

      $catid = $this->input->get('catid', 0, 'uint');
      $slug = $this->input->get('slug', '', 'str');
      //Build the link leading to the product page.
      $url = JRoute::_(KetshopHelperRoute::getProductRoute($slug, (int)$catid));
      //Make the link safe.
      $url = addslashes($url);
      $quantity = 1;
    }
    else { //Product comes from loadCart function.
      $id = (int)$data['id'];
      $varId = (int)$data['var_id'];
      $quantity = (int)$data['quantity'];
      $url = $data['url'];
    }

    //Get product according to its product and variant id.
    $product = ShopHelper::getProduct($id, $varId);
    $app = JFactory::getApplication();

    //Check for duplicate product.
    foreach($cart as $cartProduct) {
      if($cartProduct['id'] == $product['id'] && $cartProduct['var_id'] == $product['var_id']) {
	//Check for variant name.
	$variantName = '';
	if(!empty($cartProduct['variant_name'])) {
	  $variantName = ' : '.$cartProduct['variant_name'];
	}

	$app->enqueueMessage(JText::sprintf('COM_KETSHOP_DUPLICATE_PRODUCT', $cartProduct['name'].$variantName));
	$this->setRedirect(JRoute::_('index.php?'.$location, false));
	return;
      }
    }

    $product['quantity'] = $quantity;
    //Add the link leading to product page. 
    $product['url'] = $url;
    //Add the product to the cart.
    $cart[] = $product;
    $session->set('cart', $cart, 'ketshop'); 

    $this->updateProductPrices();
    $this->updateCartAmount();

    //Reset submit flag in case cart has been previously saved.
    $session->set('submit', 0, 'ketshop'); 

    //Avoid to redirect each time a product sent from loadCart function
    //is added to the cart.
    if(!empty($data)) {
      return;
    }

    $app->enqueueMessage(JText::sprintf('COM_KETSHOP_PRODUCT_ADDED_TO_CART', $product['name']));
    $this->setRedirect(JRoute::_('index.php?'.$location, false));

    return;
  }


  public function updateQuantity()
  {
    //Get the cart session array.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    $app = JFactory::getApplication();

    //Get the cart form.
    $data = $this->input->post->getArray();
    //Create an array where the key is corresponding to the product id and
    //the value to the product quantity.
    $newQty = array();

    foreach($data as $key => $value) {
      //Extract the product and variant ids from the variable name.
      if(preg_match('#^quantity_([1-9][0-9]*)_([0-9]+)$#', $key, $matches)) {
	$productId = $matches[1];
	$variantId = $matches[2];
	//Retrieve some needed variables  
	$minQty = $data['min_quantity_'.$productId.'_'.$variantId];
	$maxQty = $data['max_quantity_'.$productId.'_'.$variantId];

	//then check them out. 
	if(!preg_match('#^[0-9]+$#', $minQty) || !preg_match('#^[0-9]+$#', $maxQty)) {
	  $app->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_RANGE_QUANTITY', $data['name_'.$productId.'_'.$variantId]));
	  $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
	  return false;
	}

	//Now check the quantity value. 
	if(!preg_match('#^[0-9]+$#', $value) || $value == 0) {
	  $app->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_INVALID_QUANTITY', $data['name_'.$productId.'_'.$variantId]));
	  $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
	  return false;
	}

	//Verify that the value is between min and max quantity.
	if((int)$value < (int)$minQty) {
	  $app->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_MIN_QUANTITY', $data['name_'.$productId.'_'.$variantId], $minQty));
	  $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
	  return false;
	}

	if((int)$value > (int)$maxQty) {
	  $app->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_MAX_QUANTITY', $data['name_'.$productId.'_'.$variantId], $maxQty));
	  $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
	  return false;
	}

	//Store the new quantity value.
	$newQty[$productId] = (int)$value;
      }
    }

    //Check if new quantity value is valid.
    if(empty($newQty)) {
      $app->enqueueMessage(JText::_('COM_KETSHOP_ERROR_QUANTITIES_UNDEFINED'));
      $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
      return false;
    }

    //Update quantity for each product.
    for($i = 0; $i < count($cart); $i++) {
      //Compute stock state after the new quantity is applied.
      $stockState = (int)$cart[$i]['stock'] - (int)$newQty[$cart[$i]['id']];

      //New quantity is higher or equal than stock capacity or minimum stock threshold.
      if($stockState < 0 || ($stockState <= $cart[$i]['min_stock_threshold'] && !$cart[$i]['allow_order'])) {
	$app->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_QUANTITY_TOO_HIGHT',$newQty[$cart[$i]['id']],
										      $data['name_'.$cart[$i]['id'].'_'.$cart[$i]['var_id']]));
	$this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
	return false;
      }

      //Everything is ok. We can update quantity.
      $cart[$i]['quantity'] = $newQty[$cart[$i]['id']];
    }

    $session->set('cart', $cart, 'ketshop'); 

    $this->updateProductPrices();
    $this->updateCartAmount();

    //Reset submit flag in case cart has been previously saved.
    $session->set('submit', 0, 'ketshop'); 

    $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));

    return true;
  }


  public function removeFromCart()
  {
    //Retrieve both the id and variant id of the product to remove from the GET url.
    $id = $this->input->get('prod_id', 0, 'uint');
    $varId = $this->input->get('var_id', 0, 'uint');
    //Get the cart view url.

    //Check if it's a valid id.
    if(!$id) {
      $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
      return;
    }

    //Get the cart.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    //If there just one product left in the cart, we empty the cart (session 
    //variables will be removed).
    if(count($cart) == 1) {
      $this->emptyCart();
      $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
      return;
    }

    //Search for the product id to remove.
    for($i = 0; $i < count($cart); $i++) {
      //Check for the array row to remove.
      if($cart[$i]['id'] == $id && $cart[$i]['var_id'] == $varId) {
	unset($cart[$i]);
	//resort the array keys numericaly.
	$cart = array_values($cart);
	break;
      }
    }

    $session->set('cart', $cart, 'ketshop'); 

    $this->updateProductPrices();
    $this->updateCartAmount();

    //Reset submit flag in case cart has been previously saved.
    $session->set('submit', 0, 'ketshop'); 

    $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));

    return;
  }


  public function emptyCart()
  {
    //Clear data.
    ShopHelper::clearPurchaseData();

    //If it's a previously saved cart, we remove it from datatbase.
    //$orderId = $session->get('order_id', 0, 'ketshop'); 
    //if($orderId)
      //$this->removePendingCart($orderId);

    $this->setRedirect(JRoute::_('index.php?'.$this->cartView, false));
    return;
  }


  public function updateProductPrices($reloadPriceRules = false)
  {
    //Get the cart and settings session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    foreach($cart as $key => $product) {
      if($reloadPriceRules) {
	$product['pricerules'] = PriceruleHelper::getCatalogPriceRules($product);
      }

      //Set the catalog price rules for this product.
      $catalogPrice = PriceruleHelper::getCatalogPrice($product, $settings);
      //Add product price data to the product array.
      $cart[$key]['unit_price'] = $catalogPrice->final_price;
      $cart[$key]['pricerules'] = $catalogPrice->pricerules;

      //Variable used to store the result of the cart rule operations applied on each product of
      //the cart. Only used when cart rules are applied to cart amount.
      //cart_rules_impact is a specific variable used to compute the impact of the
      //cart rules on each product within the cart then to calculate the final amounts.
      $cart[$key]['cart_rules_impact'] = $catalogPrice->final_price;
    }

    $session->set('cart', $cart, 'ketshop');

    return;
  }


  public function updateCartAmount()
  {
    //Get cart and settings session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    //If the cart amount array doesn't exist we create it.
    if(!$session->has('cart_amount', 'ketshop')) {
      $session->set('cart_amount', array(), 'ketshop');
    }

    //Just in case cart array is empty.
    if(empty($cart)) {
      $session->set('cart_amount', array(), 'ketshop');
      return;
    }

    //Get cart amount modified by cart price rules if any.
    $cartAmount = PriceruleHelper::getCartAmount();

    $session->set('cart_amount', $cartAmount, 'ketshop');

    return;
  }


  public function cancelCart()
  {
    //Get the order id previously set in storeOrder controller function.
    $session = JFactory::getSession();
    $orderId = $session->get('order_id', 0, 'ketshop'); 

    //Set the cart status to cancelled
    if($orderId) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->update('#__ketshop_order')
	    ->set('cart_status="cancelled"')
	    ->where('id='.(int)$orderId);
      $db->setQuery($query);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    //then remove all of the session data. 
    ShopHelper::clearPurchaseData();

    return;
  }


  //Empty the current cart then reload it. 
  public function loadCart($pendingOrderId)
  {
    //Grab the user session and get the possible current cart.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    //Get current cart products (if any) in a temporary array then empty
    //(delete) the cart. 
    $cartProducts = array();
    if($session->has('cart', 'ketshop')) {
      foreach($cart as $product) {
	$cartProducts[] = $product;
      }

      //Empty the current cart.
      $this->emptyCart();
    }

    $products = OrderHelper::getProducts($pendingOrderId);

    //Check if products are still available and if their quantity is ok.
    foreach($products as $product) {
      //Flag
      $available = true;

      //Product is no longer online.
      if($product['published'] != 1) {
	$this->storeUnavailableProduct($product);
	$available = false;
      }

      //Set the product quantity.
      $prodQty = $product['quantity'];

      //Check if the previously saved product is already into the cart.
      //If it is we just add both of the quantity values.
      foreach($cartProducts as $id => $cartProduct) {
	if(in_array($product['id'], $cartProduct['ids'])) {
	  $prodQty = $product['quantity'] + $cartProduct['quantity'];
	  //Remove the product from the array.
	  unset($cartProducts[$id]);
	  //resort the array keys numericaly.
	  $cart = array_values($cart);
	  break;
	}
      }

      //Check against product quantity.
      if($available && $product['stock_subtract']) {
	//Stock is empty.
	if($product['stock'] == 0) {
	  $this->storeUnavailableProduct($product);
	  $available = false;
	}
	else { //We have something in stock, here we go...
	  //Vendor allows ordering until stock is empty.
	  if($product['allow_order'] && $prodQty > $product['stock']) {
	    $prodQty = $product['stock']; //Readjust quantity set by the user.
	  }

	  //Vendor has defined a safety limit from which customers cannot
	  //ordering anymore. This limit is set in min_stock_threshold variable. 
	  if(!$product['allow_order']) {
	    if($product['stock'] > $product['min_stock_threshold']) {
	      //Compute stock state after product quantity set by the user is applied. 
	      $stockState = $product['stock'] - $prodQty;

	      //quantity is higher than the safety limit.
	      if($stockState <= $product['min_stock_threshold']) {
		//Readjust quantity until it fits with the allowed limit.
		while($stockState < $product['min_stock_threshold']) {
		  $prodQty--;
		  $stockState = $product['stock'] - $prodQty;
		}
	      }
	    }
	    else { //Stock product is lower or equal to min_stock_threshold.
	      $this->storeUnavailableProduct($product);
	      $available = false;
	    }
	  }
	}
      }

      //Set the new quantity.
      $product['quantity'] = $prodQty;

      if($available) {
	$this->addToCart($product);
      }
    }

    //Now cart is populate with the previously stored products, we create and set 
    //the order_id session variable.
    $session->set('order_id', $pendingOrderId, 'ketshop'); 

    //Add to the cart the possible unsaved products which were previously in the
    //cart before loadCart function is launched.
    foreach($cartProducts as $cartProduct) {
      $this->addToCart($cartProduct);
    }

    return;
  }


  private function storeUnavailableProduct($product)
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $unavailable = $session->get('unavailable', array(), 'ketshop'); 

    $unavailable[] = array('id'=>$product['id'], 
	                   'name'=>$product['name'], 
			   'code'=>$product['code']);

    $session->set('unavailable', $unavailable, 'ketshop');

    return;
  }


  private function initializeCart()
  {
    //Grab the user session.
    $session = JFactory::getSession();

    //Create the cart array.
    $session->set('cart', array(), 'ketshop');

    //We also need a settings array where all of the global needed 
    //data of the shop is stored.
    if(!$session->has('settings', 'ketshop')) {
      $settings = ShopHelper::getShopSettings();

      //Set the label of the current tax method for more convenience.
      $settings['tax_method_label'] = 'COM_KETSHOP_FIELD_INCLUDING_TAXES';
      if($settings['tax_method'] === 'excl_tax') {
	$settings['tax_method_label'] = 'COM_KETSHOP_FIELD_EXCLUDING_TAXES';
      }

      $session->set('settings', $settings, 'ketshop');
    }

    //Safety variables.
    if(!$session->has('end_purchase', 'ketshop')) {
      $session->set('end_purchase', 0, 'ketshop');
    }

    if(!$session->has('submit', 'ketshop')) {
      $session->set('submit', 0, 'ketshop');
    }

    //Variable used to lock the order once it has been validated. 
    //It's also used to avoid the user to order again (after the order   
    //has been validated) by using the backspace key.   
    if(!$session->has('locked', 'ketshop')) {
      $session->set('locked', 0, 'ketshop');
    }

    return;
  }
}


