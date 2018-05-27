<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');

// Base this view on the product frontend version.
JLoader::register('KetshopViewProduct', JPATH_SITE.'/components/com_ketshop/views/product/view.html.php');
 

class KetshopViewVendorproduct extends KetshopViewProduct
{
  function display($tpl = null)
  {
    //Checks first that the user owns the product.
    $user = JFactory::getUser();
    $item = $this->get('Item');

    if($user->get('id') != $item->created_by) {
      return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $item->created_by));
    }

    //Display the parent template.
    parent::display($tpl);
  }
}


