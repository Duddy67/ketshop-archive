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


  /**
   * Loads ContentHelper for filters before validating data.
   *
   * @param   object  $form   The form to validate against.
   * @param   array   $data   The data to validate.
   * @param   string  $group  The name of the group(defaults to null).
   *
   * @return  mixed  Array of filtered data if valid, false otherwise.
   *
   * @since   1.1
   */
  public function validate($form, $data, $group = null)
  {
    // Sets some fields to "required" according to the delivery type. 
    if($data['delivery_type'] == 'at_destination') {
      $form->setFieldAttribute('global_cost', 'required', 'true');
    }
    // at_delivery_point
    else {
      $fields = array('street', 'city', 'postcode', 'region_code', 'country_code', 'phone');
      foreach($fields as $field) {
	$form->setFieldAttribute($field, 'required', 'true');
      }
    }

    return parent::validate($form, $data, $group);
  }


  public function getDeliveryPointAddress($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('street, city, postcode, region_code, country_code, phone')
	  ->from('#__ketshop_address')
	  ->where('item_id='.(int)$pk)
	  ->where('item_type="delivery_point"')
	  // Gets the latest address as for history purpose older addresses can be linked to the same item.
	  ->order('created DESC');
    $db->setQuery($query);

    return $db->loadAssoc();
  }


  public function getDestinationData($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');
    $data = array();
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // N.B: The "from" and "to" fields MUST be "backticked" as they are reserved SQL words.
    $itemTypes = array('postcode' => array($db->quoteName('from'), $db->quoteName('to'), 'TRUNCATE(cost,2) AS cost'),
		       'city' => array('name', 'TRUNCATE(cost,2) AS cost'),
		       'region' => array('code', 'TRUNCATE(cost,2) AS cost'),
		       'country' => array('code', 'TRUNCATE(cost,2) AS cost'),
		       'continent' => array('code', 'TRUNCATE(cost,2) AS cost'));

    //Gets data for each item type.
    foreach($itemTypes as $key => $fields) {
      $query->clear()
	    ->select(implode(',', $fields))
            ->from('#__ketshop_ship_'.$key)
            ->where('shipping_id='.(int)$pk);
      $db->setQuery($query);
      $data[$key] = $db->loadAssocList();
    }

    return $data;
  }

}

