<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 

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
      $session = JFactory::getSession();
      //Set the current location so that the user will be redirect to the address view after log in.
      $session->set('location', 'address', 'ketshop');

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


