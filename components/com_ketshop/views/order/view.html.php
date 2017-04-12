<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
 

class KetshopViewOrder extends JViewLegacy
{
  /**
   * Display the view
   *
   * @return	mixed	False on error, null otherwise.
   */
  function display($tpl = null)
  {
    // Initialise variables
    $item = $this->get('Item');
    $form = $this->get('Form');
    $products = $this->get('Products');
    $priceRules = $this->get('PriceRules');
    $shippingData = $this->get('ShippingData');
    //Invoke function in a slitghly different way here as we need to pass arguments.
    $shippingAddress = $this->getModel()->getShippingAddress((int)$shippingData['address_id'], (int)$shippingData['delivpnt_id']);
    $billingAddress = $this->get('BillingAddress');

    //Prepare price rules (if any) for each product.
    foreach($products as $key => $product) {
      $products[$key]['pricerules'] = array();

      $slug = $product['id'].':'.$product['alias'];
      //Build the link leading to the product page.
      $url = JRoute::_(KetshopHelperRoute::getProductRoute($slug, (int)$product['catid']));
      //Make the link safe.
      $url = addslashes($url);
      $products[$key]['url'] = $url;

      foreach($priceRules as $priceRule) {
	if($product['prod_id'] == $priceRule['prod_id']) {
	  $products[$key]['pricerules'][] = $priceRule;
	}
      }
    }

    //Prepare price rules (if any).
    $amountPriceRules = array();
    foreach($priceRules as $priceRule) {
      $amountPriceRules[] = $priceRule;
    }

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    $this->setDocument();

    $this->assignRef('item',$item);
    $this->assignRef('form',$form);
    $this->assignRef('products',$products);
    $this->assignRef('amountPriceRules',$amountPriceRules);
    $this->assignRef('shippingData',$shippingData);
    $this->assignRef('shippingPriceRules',$shippingPriceRules);
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


