<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 * @contact team@codamigo.com
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';


class KetshopViewSummary extends JViewLegacy
{
  protected $state = null;
  protected $item = null;
  protected $items = null;
  protected $addresses = null;

  function display($tpl = null)
  {
    // Initialise variables
    $state = $this->get('State');
    $items = $this->get('Items');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    $addresses = ShopHelper::getAddresses();

    ShopHelper::javascriptUtilities();

    $this->assignRef('addresses',$addresses);

    $this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css and Javascript files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
    $doc->addScript(JURI::base().'components/com_ketshop/js/utility.js');
  }
}
