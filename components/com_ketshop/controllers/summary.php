<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
 


class KetshopControllerSummary extends JControllerForm
{
  public function setShipper()
  {
    //Get the settings session array.
    $session = JFactory::getSession();
    $settings = $session->get('settings', array(), 'ketshop'); 

    //If cart is shippable we search for the id of both shipper plugin
    //and shipping selected by the user.
    if(ShopHelper::isShippable()) {
      //Get the shippers session variables.
      $shippers = $session->get('shippers', array(), 'ketshop'); 

      //Get all of the POST data and get the selected shipper and shipping id.
      $post = $this->input->post->getArray();
      $shipperId = (int)$post['shipper_id'];
      $shippingId = (int)$post['shipping_id'];

      foreach($shippers as &$shipper) {
	//Initialize each flag in case user return back to 
	//choose another shipping.
	$shipper['selected'] = false;

	//Identify first the selected shipper and set it to true.
	if($shipperId === (int)$shipper['id']) {
	  $shipper['selected'] = true;

	  //Identify the selected shipping and set it to true.
	  foreach($shipper['shippings'] as &$shipping) {
	    //Initialize each flag in case user return back to 
	    //choose another shipping.
	    $shipping['selected'] = false;

	    if($shippingId === (int)$shipping['id']) {
	      $shipping['selected'] = true;
	    }
	  }
	}
      }

      $session->set('shippers', $shippers, 'ketshop'); 
    }

    //Reset submit flag in case cart has been previously saved.
    $session->set('submit', 0, 'ketshop'); 

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=summary', false));
    return;
  }
}


