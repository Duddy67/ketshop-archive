<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access.
defined('_JEXEC') or die; 
//Allows to keep the tab state identical in edit form after saving.
JHtml::_('behavior.tabstate');

//Check against the user permissions.
if(!JFactory::getUser()->authorise('core.manage', 'com_ketshop')) {
  JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
  return false;
}

//Registers the component helper files. They will be loaded automatically later as soon
//as a class is instantiate.
JLoader::register('KetshopHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/ketshop.php');
JLoader::register('UtilityHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php');
JLoader::register('JavascriptHelper', JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/javascript.php');
JLoader::register('PriceruleHelper', JPATH_SITE.'/components/com_ketshop/helpers/pricerule.php');
JLoader::register('KetshopHelperRoute', JPATH_SITE.'/components/com_ketshop/helpers/route.php');
JLoader::register('ShopHelper', JPATH_SITE.'/components/com_ketshop/helpers/shop.php');

// Include dependancies
jimport('joomla.application.component.controller');

$controller = JControllerLegacy::getInstance('Ketshop');

//Execute the requested task (set in the url).
//If no task is set then the "display' task will be executed.
$controller->execute(JFactory::getApplication()->input->get('task'));

$controller->redirect();



