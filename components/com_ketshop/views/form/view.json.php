<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
// Base this view on the backend version.
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/views/product/view.json.php';


class KetshopViewForm extends KetshopViewProduct
{

  function display($tpl = null)
  {
    parent::display($tpl);
  }
}
