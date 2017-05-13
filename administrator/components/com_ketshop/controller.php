<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.

jimport('joomla.application.component.controller');


class KetshopController extends JControllerLegacy
{
  public function display($cachable = false, $urlparams = false) 
  {
    require_once JPATH_COMPONENT.'/helpers/ketshop.php';

    //Display the submenu.
    KetshopHelper::addSubmenu(JRequest::getCmd('view', 'products'));

    //Set the default view.
    JRequest::setVar('view', JRequest::getCmd('view', 'ketshop'));

    //Display the view.
    parent::display();
  }
}


