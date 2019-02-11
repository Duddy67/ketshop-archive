<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
 


class KetshopControllerPayment extends JControllerForm
{
  //Create indexes which are going to be used by the plugin. 
  private $utility = array('payment_mode' => '',
			   'payment_result' => true,
			   'reply_and_exit' => '',     //In case of data remotely returned by the bank platform.
			   'payment_detail' => '',
			   'transaction_data' => '',
			   'redirect_url' => '',
			   'plugin_output' => '',     //Html code to display in the payment view.
			   'offline_id' => 0          //Only used with KetShop offline plugin.
			   );


  public function setPayment()
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    //Reset the safety variable, (previously used to store the order), to zero. 
    $session->set('submit', 0, 'ketshop'); 
    //Get the id of the order previously saved.
    $orderId = $session->get('order_id', 0, 'ketshop'); 

    //Plugins are going to need some persistent way to keep the different steps
    //of the payment as well as few extra variables (eg: token) during the payment
    //process.
    //So we create a utility array in which plugins are able to store
    //or create if necessary any needed variable.
    if(is_null(ShopHelper::getTemporaryData($orderId))) {
      $this->createTemporaryData();
    }

    //Get all the amounts (cart, shipping etc...).
    $amounts = $this->getAmounts();

    //Get all of the POST data.
    $post = $this->input->post->getArray();
    
    //Get the name of the payment/plugin chosen by the user.
    $paymentMode = $post['payment'];

    $offlineId = 0; //Only used for offline method payments. 

    //If an offline payment has been chosen we extract its id which is passed at
    //the end of the payment name (separated with an underscore).
    if(preg_match('#^offline_([0-9]+)$#', $paymentMode, $matches)) {
      $paymentMode = 'offline';
      $offlineId = $matches[1];
    }

    //Get (then set) the utility temporary data.
    $utility = ShopHelper::getTemporaryData($orderId, true);
    //Store the needed data for the payment process.
    $utility['payment_mode'] = $paymentMode; 
    $utility['offline_id'] = (int)$offlineId; 
    $this->updateUtility($orderId, $utility);

    //Build the name of the event to trigger according to the payment name of
    //the plugin.
    //Note: The first letter of the payment name is uppercased.
    $event = 'onKetshopPayment'.ucfirst($paymentMode);
    JPluginHelper::importPlugin('ketshoppayment');
    $dispatcher = JDispatcher::getInstance();

    //Trigger the event.
    //Note: Parameters are not passed by reference (using an &) cause we don't
    //allow plugins to modify directly the variables, except for the utility array.
    //Warning: Plugins MUST NOT use the session variables to get or modify data. 
    $results = $dispatcher->trigger($event, array($amounts, $cart, $settings, &$utility));

    //Store the utility array modified by the plugin.
    $this->updateUtility($orderId, $results[0]);

    if(!$results[0]['payment_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['payment_detail'];
      //Reset the temporary data.
      $this->updateUtility($orderId, $this->utility);
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false), $message, 'error');
      return false;
    }

    //Plugin needs to redirect the user.
    if(!empty($results[0]['redirect_url'])) {
      $this->setRedirect($results[0]['redirect_url']);
      return true;
    }

    //Display plugin result in the payment view or display available payment 
    //plugins if output is empty.
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));
    return true;
  }


  //During the exchanges with the payment plugin the user's session might be unavailable,
  //so the response() method uses only the temporary data.
  public function response()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $payment = $this->input->get('payment', '', 'string');

    //Check out whether the user's session is available then get the order id accordingly. 
    if(empty($orderId = $session->get('order_id', 0, 'ketshop'))) {
      $orderId = $this->getOrderIdFromBankData($payment);
    }

    //Get the required variables from the temporary data.
    $tmpData = ShopHelper::getTemporaryData($orderId);
    $amounts = $tmpData['amounts']; 
    $cart = $tmpData['cart']; 
    $settings = $tmpData['settings']; 
    $utility = $tmpData['utility']; 

    $event = 'onKetshopPayment'.ucfirst($payment).'Response';

    JPluginHelper::importPlugin('ketshoppayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array($amounts, $cart, $settings, &$utility));

    //Update the temporary utility data sent back by the plugin.
    $this->updateUtility($orderId, $results[0]);

    //Some bank platforms send a bunch of data to be checked by the component (security
    //token etc..).
    //The response is generaly a boolean value informing the bank platform whether 
    //the sent data is correct or not.
    //As the user is on the bank platform (and not on the website) when the data is sent,
    //there is nothing else to do but exit the program after replying.
    if(!empty($results[0]['reply_and_exit'])) { 
      $reply = $results[0]['reply_and_exit'];
      //Empty the field to prevent the script to exit again on the next triggered event.
      $results[0]['reply_and_exit'] = '';
      $this->updateUtility($orderId, $results[0]);

      echo $reply;
      exit;
    }

    //Plugin needs to redirect the user.
    if(!empty($results[0]['redirect_url'])) {
      $this->setRedirect($results[0]['redirect_url']);
      return true;
    }

    //Display plugin result in the payment view or display available payment 
    //plugins if output is empty.
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));
    return true;
  }


  public function cancelPayment()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $payment = $this->input->get('payment', '', 'string');

    //Check out whether the user's session is available then get the order id accordingly. 
    if(empty($orderId = $session->get('order_id', 0, 'ketshop'))) {
      $orderId = $this->getOrderIdFromBankData($payment);
    }

    //Get the required variables from the temporary data.
    $tmpData = ShopHelper::getTemporaryData($orderId);
    $amounts = $tmpData['amounts']; 
    $cart = $tmpData['cart']; 
    $settings = $tmpData['settings']; 
    $utility = $tmpData['utility']; 

    $event = 'onKetshopPayment'.ucfirst($payment).'Cancel';
    JPluginHelper::importPlugin('ketshoppayment');
    $dispatcher = JDispatcher::getInstance();
    //Sends a cancel event to the plugin in case it has some internal data to set. 
    //Note: The result returned by the plugin is not treated here.
    $result = $dispatcher->trigger($event, array($amounts, $cart, $settings, $utility));

    //Reset the utility array.
    $this->updateUtility($orderId, $this->utility);

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));
    return true;
  }


  protected function getAmounts()
  {
    //Get the cartAmount session variable.
    $session = JFactory::getSession();
    $cartAmount = $session->get('cart_amount', array(), 'ketshop'); 

    //Store the different amounts in an array.
    $amounts = array();
    $amounts['cart_amount'] = $cartAmount['amount'];
    $amounts['crt_amt_incl_tax'] = $cartAmount['amt_incl_tax'];
    $amounts['final_cart_amount'] = $cartAmount['final_amount'];
    $amounts['fnl_crt_amt_incl_tax'] = $cartAmount['fnl_amt_incl_tax'];
    $amounts['shipping_cost'] = 0;
    $amounts['final_shipping_cost'] = 0;

    //Check the cart is shippable before searching any selected shipper.
    if(ShopHelper::isShippable()) {
      $shippers = $session->get('shippers', array(), 'ketshop'); 

      foreach($shippers as $shipper) {
	//Get the selected shipper.
	if((bool)$shipper['selected']) {
	  foreach($shipper['shippings'] as $shipping) {
	    //Store the shipping amounts.
	    if((bool)$shipping['selected']) {
	      $amounts['shipping_cost'] = $shipping['cost'];
	      $amounts['final_shipping_cost'] = $shipping['final_cost'];
	      break 2;
	    }
	  }
        }
      }
    }

    return $amounts;
  }


  protected function createTemporaryData()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    $orderId = $session->get('order_id', 0, 'ketshop'); 
    //Get all the amounts (cart, shipping etc...).
    $amounts = $this->getAmounts();
    //Store the order id temporarily in the settings array as the plugin is gonna need it.
    $settings['order_id'] = $orderId;

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $now = JFactory::getDate()->toSql();

    $columns = array('order_id', 'amounts', 'cart', 'settings', 'utility', 'created');
    $values = (int)$orderId.','.$db->quote(serialize($amounts)).','.$db->quote(serialize($cart)).
              ','.$db->quote(serialize($settings)).','.$db->quote(serialize($this->utility)).','.$db->quote($now);

    $query->insert('#__ketshop_tmp_data')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return 0;
    }
  }


  protected function updateUtility($orderId, $utility)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->update('#__ketshop_tmp_data')
	  ->set('utility='.$db->Quote(serialize($utility)))
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->execute();
  }


  //Almost all of the bank platforms provide a dedicated field to store a specific data.
  //The KetShop payment plugins use this field to store the id of the current order. The
  //name of this dedicated field is set in the plugin parameters through the
  //order_id_field variable. 
  private function getOrderIdFromBankData($payment)
  {
    //Get data sent by the bank platform through GET or POST.
    $data = $this->input->getArray();
    $orderId = 0;

    //Get the plugin name (remove the possible part after the underscore).
    preg_match('#^([0-9a-z]+)(_[0-9a-z]+)*#', $payment, $matches);
    //Get the plugin params.
    $plugin = JPluginHelper::getPlugin('ketshoppayment', $matches[1]);
    $pluginParams = new JRegistry($plugin->params);
    //Get the field name where the order id is stored.
    $fieldName = $pluginParams->get('order_id_field');
    $fieldName = trim($fieldName);

    //Retrieve the order id.
    $orderId = $data[$fieldName];

    return $orderId;
  }
}


