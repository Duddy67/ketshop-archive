<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelShipping extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Shipping', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.shipping', 'shipping', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.shipping.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  public function getDeliveryPointAddress($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('street, city, postcode, region_code, country_code, phone')
	  ->from('#__ketshop_address')
	  ->where('item_id='.(int)$pk)
	  ->where('item_type="delivery_point"');
    $db->setQuery($query);

    return $db->loadAssoc();
  }


  public function getDestinationData($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');
    $data = array();
    $itemTypes = array('postcode' => array('from', 'to', 'cost'), 'city' => array('name', 'cost'),
		       'region' => array('code', 'cost'), 'country' => array('code', 'cost'),
		       'continent' => array('code', 'cost'));

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Gets data for each item type.
    foreach($itemTypes as $key => $fields) {
      $query->clear()
	    ->select(implode(',', $db->quoteName($fields)))
            ->from('#__ketshop_ship_'.$key)
            ->where('shipping_id='.(int)$pk);
      $db->setQuery($query);
      $data[$key] = $db->loadAssocList();
    }

    return $data;
  }

}

