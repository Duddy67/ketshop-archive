<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';


class KetshopViewShipment extends JViewLegacy
{

  function display($tpl = null)
  {
    $this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include the css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
  }
}
