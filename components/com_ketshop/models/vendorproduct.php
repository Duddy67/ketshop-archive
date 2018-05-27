<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';

// Base this model on the product frontend version.
JLoader::register('KetshopModelProduct', JPATH_SITE.'/components/com_ketshop/models/product.php');


class KetshopModelVendorproduct extends KetshopModelProduct
{
}

