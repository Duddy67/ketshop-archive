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


class KetshopViewPayment extends JViewLegacy
{

  function display($tpl = null)
  {
    ShopHelper::javascriptUtilities();

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
