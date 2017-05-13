<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';


class KetshopControllerShipment extends JControllerForm
{

  public function setShipment()
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $cartAmount = $session->get('cart_amount', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    //Create a shippers session array into which each shipper plugin is gonna
    //stores its data into a dedicated (sub) array. 
    if(!$session->has('shippers', 'ketshop')) {
      $session->set('shippers', array(), 'ketshop');
    }

    //Get the shippers session array and initialize it in case the user went back
    //shopping. Old shipping data should be removed.
    $shippers = $session->get('shippers', array(), 'ketshop'); 
    if(!empty($shippers)) {
      $session->set('shippers', array(), 'ketshop');
    }

    //Get a data array of the available shipper plugins
    $shipperPlugins = ShopHelper::getShipperPlugins();
    //and set it as shippers session variable.
    $session->set('shippers', $shipperPlugins, 'ketshop');

    //Search for shipping cost price rules.
    $shippingPriceRules = array();
    foreach($cartAmount['pricerules'] as $priceRule) {
      //Store the shipping cost rules.
      if($priceRule['target'] == 'shipping_cost') {
	$shippingPriceRules[] = $priceRule;
      }
    }

    //Create the name of the event.
    $event = 'onKetshopShipping';

    JPluginHelper::importPlugin('ketshopshipment');
    $dispatcher = JDispatcher::getInstance();

    //Trigger the event. This event will be catch by all the shipping plugins.
    $results = $dispatcher->trigger($event, array($shippingPriceRules, $settings));

    if(!$results[0]) { //An error has occured.
      //Retrieve the error message set by the plugin and display it.
      $message = $utility['error'];
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=shipment', false), $message, 'error');
      return false;
    }

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=shipment', false));

    return true;
  }

}


