<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';


class KetshopViewEndpurchase extends JViewLegacy
{
  protected $item = null;
  protected $products = null;
  protected $priceRules = null;
  protected $amountPriceRules = null;
  protected $shippingData = null;
  protected $shippingAddress = null;
  protected $billingAddress = null;

  function display($tpl = null)
  {
    // Initialise variables
    $item = $this->get('Item');
    $products = $this->get('Products');
    $priceRules = $this->get('PriceRules');
    $shippingData = $this->get('ShippingData');
    //Invoke function in a slitghly different way here as we need to pass arguments.
    $shippingAddress = $this->getModel()->getShippingAddress((int)$shippingData['address_id'], (int)$shippingData['delivpnt_id']);
    $billingAddress = $this->get('BillingAddress');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    //Prepare price rules (if any) for each product.
    foreach($products as $key => $product) {
      $products[$key]['rules_info'] = array();

      $slug = $product['id'].':'.$product['alias'];
      //Build the link leading to the product page.
      $url = JRoute::_(KetshopHelperRoute::getProductRoute($slug, (int)$product['catid']));
      //Make the link safe.
      $url = addslashes($url);
      $products[$key]['url'] = $url;

      foreach($priceRules as $priceRule) {
	if($product['prod_id'] == $priceRule['prod_id']) {
	  $products[$key]['rules_info'][] = $priceRule;
	}
      }
    }

    //Prepare price rules (if any).
    $amountPriceRules = array();
    foreach($priceRules as $priceRule) {
      $amountPriceRules[] = $priceRule;
    }

//var_dump($shippingAddress);
    //$item->shipping_cost = $shippingAddress['shipping_cost'];
    //$item->final_shipping_cost = $shippingAddress['final_shipping_cost'];

    $this->setDocument();

    $this->assignRef('item',$item);
    $this->assignRef('products',$products);
    $this->assignRef('amountPriceRules',$amountPriceRules);
    $this->assignRef('shippingData',$shippingData);
    $this->assignRef('shippingAddress',$shippingAddress);
    $this->assignRef('billingAddress',$billingAddress);

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include the css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
  }

}
