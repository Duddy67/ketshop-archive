<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
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
    KetshopHelper::addSubmenu($this->input->get('view', 'products'));

    //Set the default view.
    $this->input->set('view', $this->input->get('view', 'ketshop'));

    //Display the view.
    parent::display();
  }
}


