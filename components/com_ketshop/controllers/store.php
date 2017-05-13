<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
 


class KetshopControllerStore extends JControllerForm
{
  //Used as first argument of the logEvent function.
  protected $codeLocation = 'controllers/store.php';


  public function storeData()
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $locked = $session->get('locked', 0, 'ketshop'); 
    $endPurchase = $session->get('end_purchase', 0, 'ketshop'); 
    $submit = $session->get('submit', 0, 'ketshop'); 

    //Check the purchase and order states.

    //Purchase is done or cart is empty. Whatever the state is, customers has nothing to do here.
    if($endPurchase || empty($cart)) {
      //If the ketshop session still exists we unset all of its data.
      if($session->has('cart', 'ketshop')) {
	ShopHelper::clearPurchaseData();
      }

      //Redirect the customer to the Joomla front page. 
      $this->setRedirect(JRoute::_('index.php', false));

      return true;
    }

    //submit flag must be false as well as locked flag and cart must not be empty. 
    //Otherwise, order is not inserted into database.
    if(!$submit && !$locked && !empty($cart)) {
      //Set immediately submit flag to 1 to prevent the multiple clicks syndrome.
      $session->set('submit', 1, 'ketshop'); 

      //Run storage operations.
      $this->storeOrder();

      //From now on the user cannot order anymore.
      $session->set('locked', 1, 'ketshop'); 
    }

    $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=payment', false));

    return;
  }


  public function saveLockedCart()
  {
    //The customer have saved his cart because either something wrong occured
    //during the payment step or he chosed to pay later.
    //So we must reinitialized the cart state as if it has been saved in a
    //normal way.

    $session = JFactory::getSession();
    //Unlock the order to allow the user to update the cart again.
    $session->set('locked', 0, 'ketshop'); 
    //Get the id of the order previously saved. 
    $orderId = $session->get('order_id', 0, 'ketshop'); 

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Delete all the possible price rules linked to the order.
    $query->delete('#__ketshop_order_prule')
          ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

    //Remove delivery data linked to this order.
    if(ShopHelper::isShippable()) {
      $query->clear()
	    ->delete('#__ketshop_delivery')
	    ->where('order_id='.(int)$orderId);
      $db->setQuery($query);
      $db->query();
    }

    //Bring back cart status to "pending" and reset the amounts.
    $fields = array('cart_status='.$db->Quote('pending'),
		    'cart_amount=0',
		    'crt_amt_incl_tax=0',
		    'final_cart_amount=0',
		    'fnl_amt_incl_tax=0');
    $query->clear()
	  ->update('#__ketshop_order')
	  ->set($fields)
	  ->where('id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

    $this->saveCart();

    return;
  }


  public function saveCart()
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $locked = $session->get('locked', 0, 'ketshop'); 
    $submit = $session->get('submit', 0, 'ketshop'); 

    $user = JFactory::getUser();

    if($user->id < 1) {
      $this->setRedirect(JRoute::_('index.php?option=com_users&view=registration', false));
      return;
    }

    if(!$submit && !$locked && !empty($cart)) {
      //Set immediately submit flag to 1 to prevent the multiple clicks syndrome.
      $session->set('submit', 1, 'ketshop'); 

      if($this->storeOrder(true)) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_CART_SAVED'));
      }
    }

    $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=cart', false));

    return;
  }


  protected function storeOrder($pendingCart = false)
  {
    //Gather all the needed variables.
    $user = JFactory::getUser();
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $session = JFactory::getSession();
    //$cart = $session->get('cart', array(), 'ketshop'); 
    $cartAmount = $session->get('cart_amount', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    $shippable = ShopHelper::isShippable();

    //If the user has just saved his cart the order already exists and its id
    //has been previously set into the session either by storeOrder or loadCart
    //functions.
    $orderId = $session->get('order_id', 0, 'ketshop'); 

    //Flagged the order id variable to know if the order is new or not.
    $isNew = true;
    if($orderId) {
      $isNew = false;
    }

    //Initialize some variables.
    $crtAmt = $crtAmtInclTax = $fnlCrtAmt = $fnlCrtAmtInclTax = $billingAddressId = 0;
    //Set the statuses.
    $cartStatus = $orderStatus = $paymentStatus = $shippingStatus = 'pending';

    //We're saving the final order.
    if(!$pendingCart) {
      $crtAmt = $cartAmount['amount'];
      $crtAmtInclTax = $cartAmount['amt_incl_tax'];
      $fnlCrtAmt = $cartAmount['final_amount'];
      $fnlCrtAmtInclTax = $cartAmount['fnl_amt_incl_tax'];
      $billingAddressId = $session->get('billing_address_id', 0, 'ketshop'); 
      //The order is now confirmed.
      $cartStatus = 'completed';
    }

    jimport('joomla.utilities.date');
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    //If the order already exists (ie: we're dealing with a pending cart) we just
    //update the row.
    if(!$isNew) {
      //Update the order row previously stored by the user as pending cart. 
      $fields = array('cart_status='.$db->Quote($cartStatus),
		      'order_status='.$db->Quote($orderStatus),
		      'payment_status='.$db->Quote($paymentStatus),
		      'cart_amount='.$crtAmt,
		      'crt_amt_incl_tax='.$crtAmtInclTax,
		      'final_cart_amount='.$fnlCrtAmt,
		      'fnl_crt_amt_incl_tax='.$fnlCrtAmtInclTax,
		      'shippable='.$shippable,
		      'billing_address_id='.$billingAddressId,
		      'tax_method='.$db->Quote($settings['tax_method']),
		      'currency_code='.$db->Quote($settings['currency_alpha']),
		      'rounding_rule='.$db->Quote($settings['rounding_rule']),
		      'digits_precision='.$settings['digits_precision'],
		      'created='.$db->Quote($now));
      $query->update('#__ketshop_order')
	    ->set($fields)
	    ->where('id='.(int)$orderId);
    }
    else { //Insert a new order record.

      //Build the VALUES clause.
      $values = $user->id.','.$db->Quote($cartStatus).','.$db->Quote($orderStatus).','.
		$db->Quote($paymentStatus).','.$crtAmt.','.$crtAmtInclTax.','.$fnlCrtAmt.','.$fnlCrtAmtInclTax.','.$shippable.','. 
		$billingAddressId.','.$db->Quote($settings['tax_method']).','.$db->Quote($settings['currency_alpha']).','.
		$db->Quote($settings['rounding_rule']).','.$settings['digits_precision'].','.$db->Quote($now);

      $columns = array('user_id','cart_status','order_status','payment_status',
	               'cart_amount','crt_amt_incl_tax','final_cart_amount',
		       'fnl_crt_amt_incl_tax','shippable','billing_address_id',
		       'tax_method','currency_code','rounding_rule','digits_precision','created');
      //Store the order.
      $query->insert('#__ketshop_order')
	    ->columns($columns)
	    ->values($values);
    }

    //Execute the query (update or insert).
    $db->setQuery($query);
    $db->query();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //If the order is new we get the last insert id
    if($isNew) {
      $orderId = $db->insertid();

      //Create the order number.
      $Ymd = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->format('Y-m-d');
      $orderNb = $orderId.'-'.$Ymd;
      //Store the order number in the session cause it will be needed later in
      //sendConfirmationMail function. 
      $session->set('order_nb', $orderNb, 'ketshop'); 

      $query->clear();
      $query->update('#__ketshop_order')
	    ->set('name='.$db->Quote($orderNb))
	    ->where('id='.(int)$orderId);
      $db->setQuery($query);
      $db->execute();
    }

    //We're saving the final order.
    if(!$pendingCart) {
      //Create or update the shipping and transaction rows linked to this order.
      $finalShippingCost = $this->setDelivery($orderId, $now, $shippable);
      //Compute the total amount of the transaction.
      $totalAmount = $fnlCrtAmtInclTax + $finalShippingCost;
    }

    //Put order id into a session variable cause it is gonna be used later. 
    $session->set('order_id', $orderId, 'ketshop'); 

    //Now we have to store all the products and price rules linked to this order.
    $this->addProductsToOrder($orderId, $cartAmount, $pendingCart, $isNew);

    return true;
  }


  protected function setDelivery($orderId, $now, $shippable)
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    $shippingCost = $finalShippingCost = $addressId = $delivpntId = 0;
    $deliveryType = 'none';
    $status = 'pending';

    //Check that cart is shippable.
    if($shippable && (int)$orderId) {
      $shippers = $session->get('shippers', array(), 'ketshop'); 

      //Search for the selected shipper. 
      foreach($shippers as $shipper) {
	if((bool)$shipper['selected']) {
	  //Search for the selected shipping. 
	  foreach($shipper['shippings'] as $shipping) {
	    if((bool)$shipping['selected']) {
	      $shippingName = $shipping['name'];
	      $shippingId = $shipping['id'];
	      //Get the shipping delivery type.
	      $deliveryType = $shipping['delivery_type'];

	      if($deliveryType == 'at_destination') {
		//Get the id of the customer shipping address.
		$user = JFactory::getUser();
		$addresses = ShopHelper::getAddresses();
		$addressId = $addresses['shipping']['id'];
	      }
	      else { //Get the id of the delivery point address as well as the id of the delivery_point item.
		$addressId = $shipping['address_id']; 
		//
		$delivpntId = $shipping['id']; 
	      }

	      //Get the shipping costs.
	      $shippingCost = $shipping['cost'];
	      $finalShippingCost = $shipping['final_cost'];

	      //No need to go further.
	      break;
	    }
	  }
	}
      }
    }
    else { // No shipping is required.
      //$status = 'no_shipping';
      return 0;
    }

    //Get the needed data from the order.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Create a new shipping row for this order.
    $values = (int)$orderId.','.$db->Quote($deliveryType).','.$delivpntId.','.$addressId.','.
	      $db->Quote($status).','.$shippingCost.','.$finalShippingCost.','.
	      $db->Quote($shippingName).','.$shippingId.','.$db->Quote($now);

    $columns = array('order_id','delivery_type','delivpnt_id',
		     'address_id','status','shipping_cost',
		     'final_shipping_cost','shipping_name','shipping_id','created');
    $query->insert('#__ketshop_delivery')
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);
    $db->query();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    return $finalShippingCost;
  }


  protected function addProductsToOrder($orderId, $cartAmount, $pendingCart, $isNew)
  {
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $products = $priceRules = array();
    for($i = 0; $i < count($cart); $i++) {
      $products[$i] = array($orderId,
			    $cart[$i]['id'],
			    $cart[$i]['opt_id'],
			    $db->Quote($cart[$i]['name']),
			    $db->Quote($cart[$i]['option_name']),
			    $db->Quote($cart[$i]['code']),
			    $cart[$i]['unit_sale_price'],
			    $cart[$i]['unit_price'],
			    $cart[$i]['cart_rules_impact'],
			    $cart[$i]['quantity'],
			    $cart[$i]['tax_rate']);

      //We're saving the final order.
      if(!$pendingCart) {
	//Check if there's any catalog rule for this product .
	if(!empty($cart[$i]['pricerules'])) {
	  $j = count($priceRules); //Get the last index number.
	  foreach($cart[$i]['pricerules'] as $priceRule) {
	    $priceRules[$j] = array($orderId, (int)$priceRule['id'],
				    $cart[$i]['id'],
				    $db->Quote($priceRule['name']),
				    $db->Quote($priceRule['type']),
				    $db->Quote($priceRule['target']),
				    $db->Quote($priceRule['operation']),
				    $db->Quote(''), //condition attribute is not used with catalog price rules.
				    $db->Quote(''), //logical_opr attribute is not used with catalog price rules.
				    $db->Quote($priceRule['behavior']),
				    $db->Quote($priceRule['modifier']),
				    $db->Quote($priceRule['application']),
				    $priceRule['value'],
				    (int)$priceRule['ordering'],
				    $priceRule['show_rule']);
	    $j++; //Don't forget to increment.
	  }
	}
      }
    }

    //If the order is not new we first need to remove the previous records
    //linked to it.
    if(!$isNew) {
      $query->delete('#__ketshop_order_prod')
	    ->where('order_id='.(int)$orderId);
      $db->setQuery($query);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    //Now we can insert the order products and price rules.

    //We're saving the final order.
    if(!$pendingCart) {
      //Check if there is any cart rules.
      //If it does we add them to the rules array.
      if(!empty($cartAmount['pricerules'])) {
	$j = count($priceRules); //Get the last index number.
	foreach($cartAmount['pricerules'] as $priceRule) {
	  $priceRules[$j] = array($orderId, (int)$priceRule['id'],
				  0, //No product id for cart price rule type.
				  $db->Quote($priceRule['name']),
				  $db->Quote($priceRule['type']),
				  $db->Quote($priceRule['target']),
				  $db->Quote($priceRule['operation']),
				  $db->Quote($priceRule['condition']),
				  $db->Quote($priceRule['logical_opr']),
				  $db->Quote($priceRule['behavior']),
				  $db->Quote(''), //modifier attribute is not used with cart price rules.
				  $db->Quote(''), //application attribute is not used with cart price rules.
				  $priceRule['value'],
				  (int)$priceRule['ordering'],
				  $priceRule['show_rule']);
	  $j++; //Don't forget to increment.
	}
      }
    }

    $values = array();
    foreach($products as $product) {
      $values[] = implode(',', $product); //Build SQL values for each product.
    }

    $columns = array('order_id','prod_id','opt_id','name','option_name','code','unit_sale_price',
	             'unit_price','cart_rules_impact','quantity','tax_rate');

    //Insert the products linked to the order.
    $query->clear();
    $query->insert('#__ketshop_order_prod')
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);
    $db->query();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //We're saving the final order.
    if(!$pendingCart) {
      if(!empty($priceRules)) {
	$values = array();
	foreach($priceRules as $priceRule) {
	  $values[] = implode(',', $priceRule); //Build SQL values for each price rule.
	}

	$columns = array('order_id','prule_id','prod_id','name','type','target',
	                 'operation','condition','logical_opr','behavior',
			 'modifier','application','value','ordering','show_rule');

	//Insert the price rules linked to the products and to the cart (total,
	//shipping).
	$query->clear();
	$query->insert('#__ketshop_order_prule')
	      //Note: Use quoteName function as "condition" is a reserved MySQL word.
	      ->columns($db->quoteName($columns))
	      ->values($values);
	$db->setQuery($query);
	$db->query();

	//Check for errors.
	if($db->getErrorNum()) {
	  ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	  return false;
	}
      }
    }

    return true;
  }
}



