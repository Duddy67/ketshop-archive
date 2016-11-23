<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
 


class KetshopControllerOrdering extends JControllerForm
{
  public function checkUser()
  {
    $user = JFactory::getUser();

    //If SEF is enabled we must set the Itemid variable to zero in order to
    //avoid SEF to bind any previous menu item id to the address or registration view.  
    $Itemid = '';
    if(JFactory::getConfig()->get('sef', false)) {
      $Itemid = '&Itemid=0';
    }

    //If the user is logged we redirect him to the first ordering step.
    if($user->id > 1) {
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=address'.$Itemid, false));
    }
    else { //The user must login or registrate.
      $this->setRedirect(JRoute::_('index.php?option=com_users&view=login'.$Itemid, false));
    }

    return;
  }


  public function saveCart()
  {
    $user = JFactory::getUser();

    $Itemid = '';
    if(JFactory::getConfig()->get('config.sef', false)) {
      $Itemid = '&Itemid=0';
    }

    //If the user is logged we redirect him to the shipment process.
    if($user->id > 1) {
      $this->setRedirect('index.php?option=com_ketshop&task=store.storeCart');
    }
    else { //The user must log or registrate.
      $this->setRedirect(JRoute::_('index.php?option=com_users&view=login'.$Itemid, false));
    }

    return;
  }
}


