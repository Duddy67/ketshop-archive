<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');
require_once JPATH_COMPONENT_SITE.'/helpers/pricerule.php';


class KetshopModelOrder extends JModelAdmin
{

  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Order', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.order', 'order', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.order.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since	1.6
   */
  protected function populateState()
  {
    $app = JFactory::getApplication();
    $params = $app->getParams();

    // Load the object state.
    $id = JFactory::getApplication()->input->get('order_id', 0, 'uint');
    $this->setState('order.id', $id);

    // Load the parameters.
    $this->setState('params', $params);
  }


  //Overrided function.
  public function getItem($pk = null)
  {
    $item = parent::getItem($pk);

    //Override:
    //Get currency into which products has been sold.
    $currency = UtilityHelper::getCurrency($item->currency_code);
    //Add currency as an attribute to the order item.
    $item->currency = $currency;

    if($item->shippable) {
      $db = $this->getDbo();
      $query = $db->getQuery(true);
      //Get the delivery linked to the order.
      $query->select('id AS delivery_id, status AS shipping_status, shipping_cost,final_shipping_cost')
	    ->from('#__ketshop_delivery')
	    ->where('order_id='.$item->id);
      $db->setQuery($query);
      $delivery = $db->loadAssoc();

      $item->delivery_id = $delivery['delivery_id'];
      $item->shipping_status = $delivery['shipping_status'];
      $item->shipping_cost = $delivery['shipping_cost'];
      $item->final_shipping_cost = $delivery['final_shipping_cost'];
    }

    return $item;
  }


  public function getPriceRules()
  {
    //Get the order id.
    $id = $this->getState('order.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__ketshop_order_prule')
	  ->where('(history=1 OR history=2)')
	  ->where('order_id='.(int)$id);
    // Setup the query
    $db->setQuery($query);

    //var_dump($db->loadAssocList());
    return $db->loadAssocList();
  }


  public function getShippingData()
  {
    //Get the order id.
    $id = $this->getState('order.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('delivery_type, shipping_cost AS cost, final_shipping_cost AS final_cost,'.
	           'shipping_name AS name, address_id, delivpnt_id')
	  ->from('#__ketshop_delivery')
	  ->where('order_id='.(int)$id);
    // Setup the query
    $db->setQuery($query);

    return $db->loadAssoc();
  }


  public function getShippingAddress($addressId, $delivPntId = 0)
  {
    //Get the order id.
    $id = $this->getState('order.id');

    //Get the proper shipping address (delivery point or customer shipping address).
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('a.street, a.city, a.postcode,a.region_code, a.phone, a.note,'.
	           'c.lang_var AS country_lang_var,r.lang_var AS region_lang_var, u.name AS recipient')
	  ->from('#__ketshop_address AS a')
	  ->join('LEFT','#__ketshop_country AS c ON c.alpha_2=a.country_code')
	  ->join('LEFT','#__users AS u ON u.id=a.item_id')
          ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code');

    //We need the name and the information fields of the delivery point.
    if($delivPntId) {
      $query->select('s.name, s.description AS information')
	    ->join('LEFT', '#__ketshop_shipping AS s ON s.id='.(int)$delivPntId.' AND s.delivery_type="at_delivery_point"');
    }


    $query->where('a.id='.(int)$addressId);
    // Setup the query
    $db->setQuery($query);

    return $db->loadAssoc();
  }


  public function getBillingAddress()
  {
    //Get the order id.
    $id = $this->getState('order.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('a.street,a.city,a.postcode,a.region_code,a.phone,a.note,'.
	           'c.lang_var AS country_lang_var,r.lang_var AS region_lang_var, u.name AS recipient')
	  ->from('#__ketshop_address AS a')
	  ->join('INNER','#__ketshop_order AS o ON o.id='.$id)
	  ->join('LEFT','#__ketshop_country AS c ON c.alpha_2 = a.country_code')
          ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code')
	  ->join('LEFT','#__users AS u ON u.id=a.item_id')
	  ->where('a.id=o.billing_address_id ORDER BY a.id DESC LIMIT 0, 1');
    // Setup the query
    $db->setQuery($query);

    return $db->loadAssoc();
  }
}

