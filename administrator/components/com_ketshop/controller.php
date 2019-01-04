<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access.


class KetshopController extends JControllerLegacy
{
  public function display($cachable = false, $urlparams = false) 
  {
    //Loads the component helpers.
    JLoader::register('KetshopHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/ketshop.php');
    JLoader::register('UtilityHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php');
    JLoader::register('JavascriptHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/javascript.php');

    //Display the submenu.
    KetshopHelper::addSubmenu($this->input->get('view', 'products'));

    //Set the default view.
    $this->input->set('view', $this->input->get('view', 'ketshop'));

    //Display the view.
    parent::display();
  }


  /**
   * Checks whether the token is valid before sending the Ajax request to the corresponding Json view.
   *
   * @return  mixed	The Ajax request result or an error message if the token is
   * 			invalid.  
   */
  public function ajax() 
  {
    if(!JSession::checkToken('get')) {
      echo new JResponseJson(null, JText::_('JINVALID_TOKEN'), true);
    }
    else {
      parent::display();
    }
  }
}


