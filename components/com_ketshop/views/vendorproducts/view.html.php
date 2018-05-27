<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
 

class KetshopViewVendorproducts extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  public $shopSettings;

  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Get the global settings of the shop.
    $this->shopSettings = ShopHelper::getShopSettings();

    //$this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
  }
}


