<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/components/com_ketshop/helpers/shop.php';



class plgKetshoppaymentPaypal extends JPlugin
{

  /**
   * Load the language file on instantiation.
   *
   * @var    boolean
   * @since  3.1
   */
  protected $autoloadLanguage = true;


  //Grab the event triggered by the payment controller.
  public function onKetshopPaymentPaypal($amounts, $cart, $settings, $utility)
  {
    //Get the SetExpressCheckout query.
    $paypalQuery = $this->setExpressCheckout($amounts, $cart, $settings);

    //Execute the query and get the result.
    $curl = $this->cURLSession('SetExpressCheckout', $paypalQuery);

    //Load Paypal plugin language from the backend.
    $lang = JFactory::getLanguage();
    $lang->load('plg_ketshoppayment_paypal', JPATH_ADMINISTRATOR);

    if(!$curl[0]) { //curl failed
      //Display an error message.
      $utility['error'] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_CURL', $curl[1]);
      $utility['plugin_result'] = false;
      return $utility;
    }
    else { //curl succeeded.
      //Retrieve all the paypal result into an array.
      $paypalParamsArray = $this->buildPaypalParamsArray($curl[1]); 

      //Paypal query has succeeded
      if($paypalParamsArray['ACK'] === 'Success') {
	//Add the token value sent back by Paypal to the ketshop session before redirect the 
	//user on the Paypal web site. 
	$utility['paypal_token'] = $paypalParamsArray['TOKEN'];
	//Before redirect the user on the Paypal web site we must set the name 
	//of the step we are now taking. In this way, the
	//onKetshopPaymentPaypalResponse function will be able to know what
	//is the next operation.  
	//Note: Utility data is set in the session by the setPayment controller function.
	$utility['paypal_step'] = 'setExpressCheckout';

	//Get the Paypal server url from the plugin parameters.
	$paypalServerUrl = $this->params->get('server_url');

	//Remove slash from the end of the string if any.
	if(preg_match('#\/$#', $paypalServerUrl)) {
	  $paypalServerUrl = substr($paypalServerUrl, 0, -1);
	}

	//Redirect the user on the Paypal web site (add the token into url).
	//Note: Redirection is perform by the setPayment controller function.
	$utility['redirect_url'] = $paypalServerUrl.'/webscr&cmd=_express-checkout&token='.$paypalParamsArray['TOKEN'];
	$utility['plugin_result'] = true;
	return $utility;
      }
      else { //Paypal query has failed.
	//Display the Paypal error message.
	$utility['error'] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_PAYPAL', 
	                     $paypalParamsArray['L_SHORTMESSAGE0'], $paypalParamsArray['L_LONGMESSAGE0']);
	$utility['plugin_result'] = false;
	return $utility;
      }		
    }
  }


  public function onKetshopPaymentPaypalResponse($amounts, $cart, $settings, $utility)
  {
    //Carry on with the Paypal payment procedure according to the current step.

    //Load Paypal plugin language from the backend.
    $lang = JFactory::getLanguage();
    $lang->load('plg_ketshoppayment_paypal', JPATH_ADMINISTRATOR);

    if($utility['paypal_step'] === 'setExpressCheckout') {
      //Empty the redirect_url variable to prevent payment controller to
      //redirect the user.
      $utility['redirect_url'] = '';

      //Paypal server has redirected the user on our site and sent us back the
      //token previously created and the payer id.
      $token = JFactory::getApplication()->input->get('token', '', 'str');

      //Check the token previously created against the one just passed by Paypal.
      if($token !== $utility['paypal_token']) {
	//Display the Paypal error message.
	$utility['error'] = JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_TOKEN'); 
	$utility['plugin_result'] = false;
	return $utility;
      }

      //Once Paypal query has succeeded, we might want more details about the 
      //transaction. We can get them with the GetExpressCheckoutDetails method.

      //Set the GetExpressCheckoutDetails query.
      $paypalQuery = array('TOKEN' => $utility['paypal_token']);

      //Execute the query and get the result.
      $curl = $this->cURLSession('GetExpressCheckoutDetails', $paypalQuery);

      if(!$curl[0]) { //curl failed
	//Display an error message.
	$utility['error'] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_CURL', $curl[1]);
	$utility['plugin_result'] = false;
	return $utility;
      }
      else { //curl succeeded.
	//Retrieve all the Paypal result into an array.
	$paypalParamsArray = $this->buildPaypalParamsArray($curl[1]); 

	//Paypal query has succeeded
	if($paypalParamsArray['ACK'] === 'Success') {
	  //Store the paypal params array as we gonna use it later (for payerID
	  //variable).
	  $utility['transaction_data'] = $paypalParamsArray;
	}
	else { //Paypal query has failed.
	  //Display the Paypal error message.
	  $utility['error'] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_PAYPAL', 
			       $paypalParamsArray['L_SHORTMESSAGE0'], $paypalParamsArray['L_LONGMESSAGE0']);
	  $utility['plugin_result'] = false;
	  return $utility;
	}		
      }

      //So far all the Paypal payment steps have been successfull, the only step
      //left is the final transaction details performed by the
      //DoExpressCheckoutPayment method.

      //The translated fields of a payment mode.
      $translatedFields = 'pm.name,pm.information';
      //Check if a translation is needed.
      if(ShopHelper::switchLanguage()) {
	//Get the SQL query parts needed for the translation of the payment modes.
	$paymentTranslation = ShopHelper::getTranslation('payment_mode', 'id', 'pm', 'pm');
	//Translation field are now defined by the SQL conditions.
	$translatedFields = $paymentTranslation->translated_fields;
	//Build the left join SQL clause.
	$leftJoinTranslation = 'LEFT OUTER JOIN '.$paymentTranslation->left_join;
      }

      $db = JFactory::getDbo();
      $query = 'SELECT '.$translatedFields.' FROM #__ketshop_payment_mode AS pm '.
	       $leftJoinTranslation.
	       'WHERE pm.plugin_element="paypal"';
      $db->setQuery($query);
      $paypalPayment = $db->loadObject();
    
      //Set the name of the step we are now taking.
      $utility['paypal_step'] = 'getExpressCheckoutDetails';

      //Now we ask the user to proceed with the final transaction by pressing the
      //form button, (Note: payment can still be cancelled).
      $output = '<form action="index.php?option=com_ketshop&view=payment&task=payment.response&payment=paypal" '.
		 'method="post" id="payment_modes">';
      $output .= '<div class="paypal-payment">';
      $output .= '<h1>'.$paypalPayment->name.'</h1>';
      $output .= $paypalPayment->information;
      $output .= '<div id="action-buttons">';
      $output .= '<span class="button">'.
		 '<a href="index.php?option=com_ketshop&view=payment&task=payment.cancel&payment=paypal" onclick="hideButton(\'action-buttons\')">'.
			  JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_CANCEL').'</a></span>';
      $output .= '<span class="button-separation">&nbsp;</span>';
      $output .= '<input id="submit-button" type="submit" onclick="hideButton(\'action-buttons\')" value="'
	          .JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_VALIDATE').'" />';
      $output .= '</div>';
      $output .= '</div>';
      $output .= '</form>';

      //Store the output into the utility array in order to be displayed
      //in the payment view.
      $utility['plugin_output'] = $output;

      $utility['plugin_result'] = true;
      return $utility;
    }
    elseif($utility['paypal_step'] === 'getExpressCheckoutDetails') {
      //The user has confirmed the payment. We can proceed with the final
      //transaction with the DoExpressCheckoutPayment method which is the 
      //last step of the Paypal payment procedure. 

      //Get the DoExpressCheckoutPayment query.
      $paypalQuery = $this->doExpressCheckoutPayment($amounts, $cart, $settings, $utility);

      //Execute the query and get the result.
      $curl = $this->cURLSession('DoExpressCheckoutPayment', $paypalQuery);

      if(!$curl[0]) { //curl failed
	//Display an error message.
	$utility['error'] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_CURL', $curl[1]);
	$utility['plugin_result'] = false;
	return $utility;
      }
      else { //curl succeeded.
	//Retrieve all the Paypal result into an array.
	$paypalParamsArray = $this->buildPaypalParamsArray($curl[1]); 
	$utility['payment_detail'] = 'PayPal Payment';

	//Paypal query has succeeded
	if($paypalParamsArray['ACK'] === 'Success') {
	  //Paypal payment is now complete. We can redirect the user on the
	  //finalize page where order and transaction are gonna be stored into
	  //database.

	  //Notify that payment has succeded
	  $utility['redirect_url'] = JRoute::_('index.php?option=com_ketshop&task=finalize.confirmPurchase', false);
	  $utility['payment_result'] = 1;
	  $utility['plugin_result'] = true;
	  //Serialize the Paypal data to store it into database.
	  $utility['transaction_data'] = serialize($paypalParamsArray);

	  ShopHelper::createTransaction($amounts, $utility, $settings); 

	  return $utility;
	}
	else { //Paypal query has failed.
	  //Before going further we check the Paypal error code. 
	  //11607 is Paypal error code for "Duplicate Request" which means that
	  //we're dealing with the double click effect.
	  //Since Paypal transaction went ok (Long message: A successful transaction has already 
	  //been completed for this token.), we can confirm the purchase. 
          if($paypalParamsArray['L_ERRORCODE0'] == 11607) {
	    //Notify that payment has succeded
	    $utility['redirect_url'] = JRoute::_('index.php?option=com_ketshop&task=finalize.confirmPurchase', false);
	    $utility['payment_result'] = 1;
	    $utility['plugin_result'] = true;

	    return $utility;
	  }

	  //Display the Paypal error message.
	  $utility['error'] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_PAYPAL', 
			       $paypalParamsArray['L_SHORTMESSAGE0'], $paypalParamsArray['L_LONGMESSAGE0']);
	  $utility['plugin_result'] = false;
	  $utility['redirect_url'] = JRoute::_('index.php?option=com_ketshop&view=payment&task=payment.cancel&payment=paypal', false);

	  JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_PAYPAL', 
								     $paypalParamsArray['L_SHORTMESSAGE0'],
								     $paypalParamsArray['L_LONGMESSAGE0']), 'error');

	  $utility['transaction_data'] = serialize($paypalParamsArray);
	  ShopHelper::createTransaction($amounts, $utility, $settings); 

	  return $utility;
	}		
      }
    }
    else { //Something odd happened.
      //Display an error message.
      $utility['error'] = JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_ERROR_NO_STEP');
      $utility['plugin_result'] = false;
      return $utility;
    }
  }


  public function onKetshopPaymentPaypalCancel($utility)
  {

    //Remove the specific variables
    unset($utility['paypal_token']);
    unset($utility['paypal_step']);
    //then empty the generic variables.
    $utility['redirect_url'] = '';
    $utility['plugin_output'] = '';
    $utility['error'] = '';
    $utility['plugin_result'] = false;

    return $utility;
  }


  //Create a cURL session and execute the query passed in argument.
  //Return an array where:
  //id 0 = boolean (true: succeeded, false: failed).
  //id 1 = string (result of the query if succeed or error message).
  protected function cURLSession($method, $paypalQuery)
  {
    //Our request parameters
    $requestParams = array('METHOD' => $method,
			   'VERSION' => $this->params->get('api_version'));

    $credentials = array('USER' => $this->params->get('user'), 
	                 'PWD' => $this->params->get('password'), 
			 'SIGNATURE' => $this->params->get('signature'));

    //Concatenates the whole request.
    $request = array_merge($requestParams, $credentials, $paypalQuery);

    //Building our NVP string
    $request = http_build_query($request);

    //cURL settings
    $curlOptions = array (CURLOPT_URL => $this->params->get('api_endpoint'),
			  CURLOPT_VERBOSE => 1,
			  CURLOPT_SSL_VERIFYPEER => true,
			  CURLOPT_SSL_VERIFYHOST => 2,
			  //CURLOPT_CAINFO => dirname(__FILE__) . '/cacert.pem', //CA cert file
			  CURLOPT_RETURNTRANSFER => 1,
			  CURLOPT_POST => 1,
			  CURLOPT_POSTFIELDS => $request);

    $ch = curl_init();

    curl_setopt_array($ch, $curlOptions);

    //Sending our request - $response will hold the API response
    $response = curl_exec($ch);

    $result = array();
    //Store result.
    if(curl_errno($ch)) { //curl failed
      $result[] = false;
      $result[] = curl_error($ch);
    }
    else {
      $result[] = true;
      $result[] = $response;
    }

    //Close the cURL session.
    curl_close($ch);

    return $result;
  }


  protected function setExpressCheckout($amounts, $cart, $settings)
  {
    //Initialize some variables.
    $currencyCode = $settings['currency_alpha'];
    $countryCode = $settings['country_alpha_2'];
    //Load Paypal plugin language.
    $lang = JFactory::getLanguage();
    $lang->load('plg_ketshoppayment_paypal', dirname(__FILE__));

    //We can add custom parameter to the query, but we need 
    //GetExpressCheckoutDetails to recover it.
    $query = array('CANCELURL' => JUri::base().'index.php?option=com_ketshop&view=payment&task=payment.cancelPayment&payment=paypal',
		   'RETURNURL' => JUri::base().'index.php?option=com_ketshop&view=payment&task=payment.response&payment=paypal');
    //Get the query for the detail order.
    $detailOrder = $this->buildPaypalDetailOrder($amounts, $cart, $settings);
    $query = array_merge($query, $detailOrder);

    $query['PAYMENTREQUEST_0_CURRENCYCODE'] = $currencyCode;
    $query['PAYMENTREQUEST_0_DESC'] = JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_SHOP_DESC');
    $query['NOSHIPPING'] = 1;
    $query['LOCALECODE'] = $countryCode;
    $query['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale'; 
    $query['PAYMENTREQUEST_0_CUSTOM'] = '123456789';

    return $query;
  }


  //This is the call to Paypal for payment confirmation. We send a query with required 
  //parameters plus the optional parameters we need. If DoExpressCheckoutPayment method 
  //has succeeded, Paypal return a list of parameters value we can use during 
  //our transaction. 
  protected function doExpressCheckoutPayment($amounts, $cart, $settings, $utility)
  {
    $currencyCode = $settings['currency_alpha'];
    $query = array('TOKEN' => $utility['paypal_token']);
    //Get the query for the detail order.
    $detailOrder = $this->buildPaypalDetailOrder($amounts, $cart, $settings);
    $query = array_merge($query, $detailOrder);

    $query['PAYMENTREQUEST_0_CURRENCYCODE'] = $currencyCode;
    //Add payment id sent back by Paypal.
    $query['PayerID'] = $utility['transaction_data']['PAYERID'];
    $query['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';

    return $query;
  }


  //Return the detail order which is include into a Paypal query.
  protected function buildPaypalDetailOrder($amounts, $cart, $settings)
  {
    //initialize some variables.
    $taxMethod = $settings['tax_method'];
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];
    $cartAmount = $cartOperation = $shippingOperation = $id = 0;
    $detailOrder = array();
    //Load Paypal plugin language from the backend.
    $lang = JFactory::getLanguage();
    $lang->load('plg_ketshoppayment_paypal', JPATH_ADMINISTRATOR);

    foreach($cart as $product) {
      //Compute the amount including tax.
      if($taxMethod == 'excl_tax') {
	//Compute the product including tax
	$sum = $product['unit_price'] * $product['quantity'];
        $inclTaxResult = UtilityHelper::roundNumber(UtilityHelper::getPriceWithTaxes($sum, $product['tax_rate']), $rounding, $digits);
	//then the amount including tax.
	$cartAmount += $inclTaxResult;
      }
      else { //No need to calculate as taxes are already included.
	$cartAmount += $product['unit_price'] * $product['quantity'];
      }

      //Display the product detail.

      $detailOrder['L_PAYMENTREQUEST_0_NAME'.$id] = $product['name'];
      $detailOrder['L_PAYMENTREQUEST_0_QTY'.$id] = $product['quantity']; 

      //Display the proper description according to the tax method.
      if($taxMethod == 'excl_tax') {
	$detailOrder['L_PAYMENTREQUEST_0_DESC'.$id] = JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_EXCL_TAX_PRICE');
      }
      else {
	$detailOrder['L_PAYMENTREQUEST_0_DESC'.$id] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_INCL_TAX', $product['tax_rate']);
      }

      $detailOrder['L_PAYMENTREQUEST_0_AMT'.$id] = UtilityHelper::formatNumber($product['unit_price']);

      if($taxMethod == 'excl_tax') {
	//Increment the id.
	$id = $id + 1;
        //Calculate the result of the product taxes.
	$inclTax = $inclTaxResult - ($product['unit_price'] * $product['quantity']);
	//Display the product taxes as an item. 
	$detailOrder['L_PAYMENTREQUEST_0_NAME'.$id] = JText::sprintf('PLG_KETSHOP_PAYMENT_PAYPAL_INCL_TAX_PRODUCT',
	                                                              $product['tax_rate'],$product['quantity'],$product['name']);
	$detailOrder['L_PAYMENTREQUEST_0_QTY'.$id] = 1;
	$detailOrder['L_PAYMENTREQUEST_0_AMT'.$id] = UtilityHelper::formatNumber($inclTax);
      }

      $id++;
    } 

    //Check for discount.
    if($cartAmount > $amounts['fnl_crt_amt_incl_tax']) {
      $cartOperation = $cartAmount - $amounts['fnl_crt_amt_incl_tax'];
      $cartAmount = $cartAmount - $cartOperation;
      //Convert positive value into negative.
      $cartOperation = $cartOperation * -1;
    }
    elseif($cartAmount < $amounts['fnl_crt_amt_incl_tax']) { //Check for raise.
      $cartOperation = $amounts['fnl_crt_amt_incl_tax'] - $cartAmount;
      $cartAmount = $cartAmount + $cartOperation;
    }

    //Add the sum of the operation applied to the cart as an item.
    //Paypal will substract or add this value.
    if($cartOperation) {
      $detailOrder['L_PAYMENTREQUEST_0_NAME'.$id] = JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_CART_OPERATION');
      $detailOrder['L_PAYMENTREQUEST_0_QTY'.$id] = 1; 
      $detailOrder['L_PAYMENTREQUEST_0_AMT'.$id] = UtilityHelper::formatNumber($cartOperation);
      $detailOrder['L_PAYMENTREQUEST_0_DESC'.$id] = JText::_('PLG_KETSHOP_PAYMENT_PAYPAL_CART_OPERATION_DESC');
    }

    //Display the cart amount.
    $detailOrder['PAYMENTREQUEST_0_ITEMAMT'] = UtilityHelper::formatNumber($cartAmount);

    //Check for shipping.
    if(ShopHelper::isShippable()) {
      //Check for shipping discount.
      if($amounts['shipping_cost'] > $amounts['final_shipping_cost']) {
	$shippingOperation = $amounts['shipping_cost'] - $amounts['final_shipping_cost'];
	//Convert positive value into negative.
	$shippingOperation = $shippingOperation * -1;
      }
      //Note: We don't check for possible shipping raise since Paypal doesn't
      //provide variable for that.

      if($shippingOperation) {
	$detailOrder['PAYMENTREQUEST_0_SHIPDISCAMT'] = UtilityHelper::formatNumber($shippingOperation);
	$detailOrder['PAYMENTREQUEST_0_SHIPPINGAMT'] = UtilityHelper::formatNumber($amounts['shipping_cost']);
      }
      else {
	$detailOrder['PAYMENTREQUEST_0_SHIPPINGAMT'] = UtilityHelper::formatNumber($amounts['final_shipping_cost']);
      }
    }

    //Display the final total amount.
    $totalAmount = $amounts['fnl_crt_amt_incl_tax'] + $amounts['final_shipping_cost'];
    $detailOrder['PAYMENTREQUEST_0_AMT'] = UtilityHelper::formatNumber($totalAmount);

    return $detailOrder;
  }


  //Retrieve the Paypal result parameters then turn it into an array for more convenience.
  protected function buildPaypalParamsArray($paypalResult)
  {
    //Create an array of parameters.
    $parametersList = explode("&",$paypalResult);

    //Separate name and value of each parameter.
    foreach($parametersList as $paypalParam) {
      list($name, $value) = explode("=", $paypalParam);
      $paypalParamArray[$name]=urldecode($value); //Create final array.
    }

    return $paypalParamArray; //Return the array.
  }
}

