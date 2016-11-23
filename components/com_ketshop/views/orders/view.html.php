<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');
 

class KetshopViewOrders extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $orderStatus;
  protected $paymentStatus;
  protected $shippingStatus;
  protected $pagination;

  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->orderStatus = $this->get('OrderStatus');
    $this->paymentStatus = $this->get('PaymentStatus');
    $this->shippingStatus = $this->get('ShippingStatus');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //$this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
  }
}


