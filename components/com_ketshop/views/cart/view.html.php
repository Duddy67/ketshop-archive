<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';


class KetshopViewCart extends JViewLegacy
{
  protected $state = null;
  protected $item = null;
  protected $items = null;
  protected $shippingAddress = null;


  function display($tpl = null)
  {
    // Initialise variables
    //$state = $this->get('State');
    //$items = $this->get('Items');
//var_dump($items);
    // Check for errors.
    /*if(count($errors = $this->get('Errors'))) {
	    JError::raiseWarning(500, implode("\n", $errors));
	    return false;
  }*/

    ShopHelper::javascriptUtilities();

    $this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
    $doc->addScript(JURI::base().'components/com_ketshop/js/utility.js');
  }
}
