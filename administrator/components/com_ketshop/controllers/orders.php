<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
 

class KetshopControllerOrders extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Order', $prefix = 'KetshopModel', $config = array('ignore_request' => true))
  {
    $model = parent::getModel($name, $prefix, $config);
    return $model;
  }

}



