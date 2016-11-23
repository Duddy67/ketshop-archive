<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
 


class KetshopControllerPayment extends JControllerForm
{
  public function setPayment()
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    //Reset the safety variable, (previously used to store the order), to zero. 
    $session->set('submit', 0, 'ketshop'); 

    //Get all the amounts (cart, shipping etc...).
    $amounts = $this->getAmounts();

    //Plugins are going to need some persistent way to keep the different steps
    //of the payment as well as few extra variables (eg: token) during the payment
    //process.
    //We also  provide a dedicated variable into which plugins can store their
    //html output in order to display it in the payment view.  
    //So we create a session utility array in which plugins are able to store
    //or create if necessary any needed variable.
    if(!$session->has('utility', 'ketshop')) {
      //Create indexes which are going to use by the controller. 
      $utility = array('payment_name'=> '',
	               'plugin_result'=> false,
	               'payment_result'=> 0,
	               'payment_details'=> '',
	               'redirect_url'=> '',
	               'plugin_output'=> '',     //Html code to display in the payment view.
	               'offline_id'=> 0,         //Only used with KetShop offline plugin.
		       'error'=>'');
      $session->set('utility', $utility, 'ketshop');
    }

    //Get all of the POST data.
    $post = $this->input->post->getArray();
    
    //Get the name of the payment/plugin chosen by the user.
    $paymentName = $post['payment'];

    $offlineId = 0; //Only used for offline method payments. 

    //If an offline payment has been chosen we extract its id which is passed at
    //the end of the payment name (separated with an underscore).
    if(preg_match('#^offline_([0-9]+)$#', $paymentName, $matches)) {
      $paymentName = 'offline';
      $offlineId = $matches[1];
    }

    //Get the utility session array.
    $utility = $session->get('utility', array(), 'ketshop'); 
    //Store the needed data for the payment process.
    $utility['payment_name'] = $paymentName; 
    $utility['offline_id'] = (int)$offlineId; 
    $session->set('utility', $utility, 'ketshop');

    //Build the name of the event to trigger according to the payment name of
    //the plugin.
    //Note: The first letter of the payment name is uppercased.
    $event = 'onKetshopPayment'.ucfirst($paymentName);
    JPluginHelper::importPlugin('ketshoppayment');
    $dispatcher = JDispatcher::getInstance();

    //Trigger the event.
    //Note: Parameters are not passed by reference (using an &) cause we don't
    //allow plugins to modify directly session variables, except for the utility array.
    //Warning: Plugins MUST NOT use the session variables to get or modify data. 
    $results = $dispatcher->trigger($event, array($amounts, $cart, $settings, &$utility));

    //Store the utility array modified by the plugin in the session.
    $session->set('utility', $results[0], 'ketshop');

    if(!$results[0]['plugin_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['error'];
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


  public function response()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 
    $utility = $session->get('utility', array(), 'ketshop'); 

    $amounts = $this->getAmounts();

    $payment = $this->input->get->get('payment', '', 'string');
    $event = 'onKetshopPayment'.ucfirst($payment).'Response';

    JPluginHelper::importPlugin('ketshoppayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array($amounts, $cart, $settings, &$utility));

    //Store the utility array modified by the plugin in the session.
    $session->set('utility', $results[0], 'ketshop');

    if(!$results[0]['plugin_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['error'];
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


  public function cancel()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'ketshop'); 

    $payment = $this->input->get->get('payment', '', 'string');
    $event = 'onKetshopPayment'.ucfirst($payment).'Cancel';

    JPluginHelper::importPlugin('ketshoppayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array(&$utility));

    //Store the utility array modified by the plugin in the session.
    $session->set('utility', $results[0], 'ketshop');

    if(!$results[0]['plugin_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['error'];
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
}


