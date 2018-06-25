<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
 

/**
 * JSON Shipping View class. Mainly used for Ajax request. 
 */
class KetshopViewShipping extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    //Collects the required variables.
    $shippingId = $jinput->get('shipping_id', 0, 'uint');
    $itemType = $jinput->get('item_type', '', 'string');
    $model = $this->getModel();
    $results = array();

    //Calls the corresponding function.
    if($itemType == 'deliverypoint') {
      $results = $model->getDeliveryPointAddress($shippingId);
    }
    else {
      $results = $model->getDestinationData($shippingId);
    }

    echo new JResponseJson($results);
  }
}



