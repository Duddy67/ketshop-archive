<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
//Note: JPATH_COMPONENT_ADMINISTRATOR variable cannot be used here as it creates
//problem. It points to com_login component instead of com_ketshop.
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
 


class KetshopControllerFinalize extends JControllerForm
{
  //Used as first argument of the logEvent function.
  protected $codeLocation = 'controllers/finalize.php';


  public function confirmPurchase()
  {
    //Get the needed session variables.
    $session = JFactory::getSession();
    $submit = $session->get('submit', 0, 'ketshop'); 
    $endPurchase = $session->get('end_purchase', 0, 'ketshop'); 
    $orderId = $session->get('order_id', 0, 'ketshop'); 

    //Check safety variables before running the code.
    if(!$submit && !$endpurchase && $session->has('cart', 'ketshop')) {
      //Set submit flag right away to prevent the double click effect.
      $session->set('submit', 1, 'ketshop'); 

      $utility = ShopHelper::getTemporaryData($orderId, true);

      //Run methods which make the purchase confirmed.
      $this->setOrderStatus($utility);
      $cart = $session->get('cart', array(), 'ketshop'); 
      ShopHelper::updateStock($cart);

      //Update product sales.
      $this->sales();

      $this->sendConfirmationMail($utility);

      //Everything went ok, we can set the flag.
      $session->set('end_purchase', 1, 'ketshop'); 
    }

    //Delete session variables.
    ShopHelper::clearPurchaseData();
    //As well as temporary data.
    ShopHelper::deleteTemporaryData($orderId);

    //Redirect the user to the order view.
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=order&order_id='.$orderId, false));

    return;
  }


  protected function setOrderStatus($utility)
  {
    //Get the needed session variables.
    $session = JFactory::getSession();
    $settings = $session->get('settings', array(), 'ketshop'); 
    $orderId = $session->get('order_id', 0, 'ketshop'); 

    //By default statuses are set to pending.
    $paymentStatus = $orderStatus = 'pending';

    //Payment has succeeded
    if((int)$utility['payment_result']) {
      //If the user choose an offline payment mode, payment status can only set to
      //"pending" since it could take some time before the user payment comes to
      //the vendor (postmail etc...).
      //In case the user choose an online payment mode, payment status is completed.
      //Note: For now the shop doesn't handle multiple instalment payment but it will in the futur.
      if($utility['payment_mode'] !== 'offline') {
	$paymentStatus = 'completed';
      }
    }
    else { //Payment has failed.
      $paymentStatus = 'error';
    }

    //If payment succeeded and shipping is not needed, the order is completed.
    if($paymentStatus == 'completed' && !ShopHelper::isShippable()) {
      $orderStatus = 'completed';
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $fields = array('order_status='.$db->Quote($orderStatus), 'payment_status='.$db->Quote($paymentStatus));
    //Set statuses.
    $query->update('#__ketshop_order')
	  ->set($fields)
	  ->where('id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    return true;
  }


  //Update the "sales" field of each product of the cart.
  protected function sales()
  {
    //Get the cart session array.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    $products = $bundleIdQty = $bundleIds = array();
    //Get products.
    foreach($cart as $product) {
      //Bundle products will be treated separately as we need to get the products it is made of.
      if($product['type'] == 'bundle') {
	//Store the bundle ids. 
	$bundleIds[] = $product['id'];
	//We need to set a specific array which take the bundle id as index and its quantity as value.
	$bundleIdQty[(int)$product['id']] = (int)$product['quantity'];
      }

      //Store product.
      //Note: Bundles are also stored as a product.
      $products[] = $product;
    }

    if(!empty($bundleIds)) {
      $model = JModelLegacy::getInstance('Product', 'KetshopModel');
      //Get the products contained in the bundles. 
      $bundleProducts = $model->getBundleProducts($bundleIdQty);

      //Check for duplicates. A normal product can be part of one or more bundles.
      foreach($bundleProducts as $key1 => $bundleProduct) {
	$duplicate = false;
	foreach($products as $key2 => $product) {
	  //A normal product is also part of a bundle.
	  if($product['id'] == $bundleProduct['id']) {
	    //Add to the normal product its quantity set in the bundle.
	    $products[$key2]['quantity'] = $product['quantity'] + $bundleProduct['quantity'];
	    $duplicate = true;
	  }
	}
	//Remove the possible duplicate bundle product from the array.
	if($duplicate) {
	  unset($bundleProducts[$key1]);
	}
      }

      //Add the bundle products to the normal product array.
      foreach($bundleProducts as $bundleProduct) {
	$products[] = $bundleProduct;
      }
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Build the WHEN part of the query.
    $WHEN1 = $WHEN2 = '';
    
    foreach($products as $product) {
      //Check for product variants. 
      if($product['var_id']) { //Set the sales of the product variant.
	$WHEN1 .= 'WHEN prod_id='.$product['id'].' AND var_id = '.$product['var_id'].' THEN sales + '.$product['quantity'].' ';
      }
      else { //Product without variants (set the product sales).
	$WHEN2 .= 'WHEN id='.$product['id'].' THEN sales + '.$product['quantity'].' ';
      }
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if(!empty($WHEN1)) {
      $query->update('#__ketshop_product_variant')
	    ->set('sales = CASE '.$WHEN1.' ELSE sales END ');
      $db->setQuery($query);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    if(!empty($WHEN2)) {
      $query->clear();
      $query->update('#__ketshop_product')
	    ->set('sales = CASE '.$WHEN2.' ELSE sales END ');
      $db->setQuery($query);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    return true;
  }


  protected function sendConfirmationMail($utility)
  {
    //Get all the session variables needed for building the order.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $cartAmount = $session->get('cart_amount', 0, 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    $shippingData = $session->get('shipping_data', array(), 'ketshop'); 
    $billingAddressId = $session->get('billing_address_id', 0, 'ketshop'); 
    $addresses = ShopHelper::getAddresses();

    $shippable = ShopHelper::isShippable();
    $currency = $settings['currency'];
    $taxMethod = $settings['tax_method'];
    $orderNb = $session->get('order_nb', '', 'ketshop'); 
    $paymentMode = $utility['payment_mode'];
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];

    $user = JFactory::getUser();

    //Start creating the body message of the email.
    $body = JText::sprintf('COM_KETSHOP_EMAIL_ORDER_SUMMARY', $user->name, $orderNb);

    //Build a brief order summary. Catalog and shipping rules are not displayed
    //and prices and amounts are including taxes.

    for($i = 0; $i < count($cart); $i++) {
      //Make short alias names from some variable for more convenience.
      $unitPrice = $cart[$i]['unit_price']; 
      $quantity = $cart[$i]['quantity']; 
      $taxRate = $cart[$i]['tax_rate']; 

      if($taxMethod == 'excl_tax') {
        $sum = $unitPrice * $quantity;
        $inclTaxPrice = UtilityHelper::roundNumber(UtilityHelper::getPriceWithTaxes($sum, $taxRate), $rounding, $digits);
      }
      else {
	$inclTaxPrice = $unitPrice * $quantity;
      }

      $variantName = '';
      if($cart[$i]['has_variants']) {
	$variantName = ' : '.$cart[$i]['variant_name'];
      }

      $body .= JText::sprintf('COM_KETSHOP_EMAIL_PRODUCT_ROW',$cart[$i]['name'].$variantName, $quantity,
			       $inclTaxPrice, $currency, JText::_('COM_KETSHOP_INCLUDING_TAXES'), $taxRate);
    }

    $body .= "\n\n";

    //Check for the cart rules.
    $body .= JText::_('COM_KETSHOP_EMAIL_CART_RULE');
    foreach($cartAmount['pricerules'] as $priceRule) {
      if($priceRule['target'] == 'cart_amount') {
	$body .= JText::sprintf('COM_KETSHOP_EMAIL_CART_RULE_ROW', $priceRule['name'],
				 UtilityHelper::formatPriceRule($priceRule['operation'], $priceRule['value']));
      }
    }

    $body .= "\n\n";

    $body .= JText::sprintf('COM_KETSHOP_EMAIL_CART_AMOUNT', 
	              UtilityHelper::formatNumber($cartAmount['fnl_amt_incl_tax'], $digits),
		      $currency, JText::_('COM_KETSHOP_INCLUDING_TAXES'));

    if($shippable) {
      $body .= JText::sprintf('COM_KETSHOP_EMAIL_SHIPPING_COST', 
	                      UtilityHelper::formatNumber($shippingData['final_cost'], $digits),
			      $currency, JText::_('COM_KETSHOP_INCLUDING_TAXES'));
      $body .= "\n\n";
    }

    $totalAmount = $cartAmount['fnl_amt_incl_tax'] + $shippingData['final_cost'];
    $body .= JText::sprintf('COM_KETSHOP_EMAIL_TOTAL_AMOUNT', 
			    UtilityHelper::formatNumber($totalAmount, $digits),
			    $currency, JText::_('COM_KETSHOP_INCLUDING_TAXES'));

    if($shippable) {
      if($shippingData['delivery_type'] == 'at_delivery_point') { //Display the delivery point address.
	$body .= JText::_('COM_KETSHOP_CAPTION_DELIVERY_POINT_ADDRESS');
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_NAME_LABEL').' '.$shippingData['name'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_STREET_SH_LABEL').''.$shippingData['street'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_POSTCODE_SH_LABEL').''.$shippingData['postcode'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_CITY_SH_LABEL').''.$shippingData['city'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_REGION_SH_LABEL').''.$shippingData['region'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_COUNTRY_SH_LABEL').''.JText::_($shippingData['country']);
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_INFORMATION_SH_LABEL').''.$shippingData['information'];
	$body .= "\n\n";
      }
      else {
	$body .= JText::_('COM_KETSHOP_CAPTION_SHIPPING_ADDRESS');
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_STREET_SH_LABEL').''.$addresses['shipping']['street'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_CITY_SH_LABEL').''.$addresses['shipping']['city'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_POSTCODE_SH_LABEL').''.$addresses['shipping']['postcode'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_REGION_SH_LABEL').''.$addresses['shipping']['region'];
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_COUNTRY_SH_LABEL').''.JText::_($addresses['shipping']['country_lang_var']);
	$body .= "\n";
	$body .= JText::_('COM_KETSHOP_FIELD_PHONE_SH_LABEL').''.$addresses['shipping']['phone'];
	$body .= "\n\n";
      }
    }

    if($billingAddressId) { //Check for displaying customer billing address.
      $body .= JText::_('COM_KETSHOP_CAPTION_BILLING_ADDRESS');
      $body .= "\n";
      $body .= JText::_('COM_KETSHOP_FIELD_STREET_BI_LABEL').''.$addresses['billing']['street'];
      $body .= "\n";
      $body .= JText::_('COM_KETSHOP_FIELD_CITY_BI_LABEL').''.$addresses['billing']['city'];
      $body .= "\n";
      $body .= JText::_('COM_KETSHOP_FIELD_POSTCODE_BI_LABEL').''.$addresses['billing']['postcode'];
      $body .= "\n";
      $body .= JText::_('COM_KETSHOP_FIELD_REGION_BI_LABEL').''.$addresses['billing']['region'];
      $body .= "\n";
      $body .= JText::_('COM_KETSHOP_FIELD_COUNTRY_BI_LABEL').''.JText::_($addresses['billing']['country_lang_var']);
      $body .= "\n";
      $body .= JText::_('COM_KETSHOP_FIELD_PHONE_BI_LABEL').''.$addresses['billing']['phone'];
      $body .= "\n\n";
    }

    if($paymentMode == 'offline') {
      $body .= JText::_('COM_KETSHOP_EMAIL_OFFLINE_PAYMENT');
    }

    //A reference to the global mail object (JMail) is fetched through the JFactory object. 
    //This is the object creating our mail.
    $mailer = JFactory::getMailer();

    $config = JFactory::getConfig();
    $sender = array($config->get('mailfrom'),
		    $config->get('fromname'));
 
    $mailer->setSender($sender);

    $recipient = $user->email;

    $mailer->addRecipient($recipient);

    $body .= JText::_('COM_KETSHOP_EMAIL_BODY_THANKS');
    //Add the name and the username of the user.
    $mailer->setSubject(JText::sprintf('COM_KETSHOP_EMAIL_ORDER_CONFIRMATION_SUBJECT', $user->name));
    $mailer->setBody($body);

    $send = $mailer->Send();

    //Check for error.
    if($send !== true) {
      JError::raiseWarning(500, JText::_('COM_KETSHOP_ORDERING_CONFIRMATION_FAILED'));
      //Log the error.
      ShopHelper::logEvent($this->codeLocation, 'sendmail_error', 0, 0, $send->get('message'));
      return false;
    }
    else {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_ORDERING_CONFIRMATION_SUCCESS'));
    }

    return true;
  }
}


