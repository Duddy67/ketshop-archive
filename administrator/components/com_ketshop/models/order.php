<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

JLoader::register('OrderTrait', JPATH_ADMINISTRATOR.'/components/com_ketshop/traits/order.php');


class KetshopModelOrder extends JModelAdmin
{
  use OrderTrait;

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


  //Overrided function.
  public function getItem($pk = null)
  {
    // Initialise variables.
    $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
    $table = $this->getTable();

    if($pk > 0) {
      // Attempt to load the row.
      $return = $table->load($pk);

      // Check for a table object error.
      if($return === false && $table->getError()) {
	$this->setError($table->getError());
	return false;
      }
    }


    // Convert to the JObject before adding other data.
    $properties = $table->getProperties(1);
    $item = JArrayHelper::toObject($properties, 'JObject');

    if(property_exists($item, 'params')) {
      $registry = new JRegistry;
      $registry->loadString($item->params);
      $item->params = $registry->toArray();
    }

    //Override:
    //Get currency into which products has been sold.
    $currency = UtilityHelper::getCurrency($item->currency_code);
    //Add currency as an attribute to the order item.
    $item->currency = $currency;

    //Get some data about the customer.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('u.name, u.username, c.id')
	  ->from('#__users AS u')
	  ->join('INNER', '#__ketshop_customer AS c ON c.user_id='.(int)$item->user_id)
	  ->where('u.id='.(int)$item->user_id);
    $db->setQuery($query);
    $customer = $db->loadObject();

    $item->cust_id = $customer->id;
    $item->cust_name = $customer->name;
    $item->cust_username = $customer->username;

    //Delivery data is only available when cart status is completed.
    if($item->cart_status == 'completed') {
      //Get the id of the delivery linked to the order.
      $query->clear();
      $query->select('id');
      $query->from('#__ketshop_delivery');
      $query->where('order_id='.$item->id);
      $db->setQuery($query);
      $item->delivery_id = $db->loadResult();
    }

    return $item;
  }


  public function getPriceRules() 
  {
    //Get the selected order.
    $order = $this->getItem();

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('*')
	  ->from('#__ketshop_order_prule')
	  ->where('order_id='.$order->id)
	  ->where('(history=1 OR history=2)')
	  ->order('ordering');
    $db->setQuery($query);

    // Return the result
    return $db->loadAssocList();
  }


  public function getTransaction() 
  {
    //Get the selected order.
    $order = $this->getItem();

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Note: For now the shop doesn't handle multiple instalment payment but it will in the futur.
    $query->select('payment_mode, amount, result, created, detail')
	  ->from('#__ketshop_order_transaction')
	  ->where('order_id='.$order->id);
    $db->setQuery($query);

    return $db->loadObject();
  }


  public function getDelivery() 
  {
    //Get the selected order.
    $order = $this->getItem();

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('id, shipping_cost, final_shipping_cost, status, delivery_date,'.
	           'delivery_type, delivpnt_id, shipping_name, address_id, note, created')
	  ->from('#__ketshop_delivery')
	  ->where('order_id='.$order->id);
    $db->setQuery($query);
    $delivery = $db->loadObject();

    //Get the proper shipping address (delivery point or customer shipping address).
    $query->clear();
    $query->select('d.delivpnt_id,a.street,a.city,a.postcode,a.phone,a.note,'.
	           'c.lang_var AS country_lang_var,r.lang_var AS region_lang_var,'.
		   's.name AS delivpnt_name,s.description AS information')
	  ->from('#__ketshop_delivery AS d')
	  ->join('INNER','#__ketshop_address AS a ON a.id=d.address_id')
	  ->join('LEFT','#__ketshop_country AS c ON c.alpha_2=a.country_code')
	  ->join('LEFT','#__ketshop_region AS r ON r.code=a.region_code')
	  ->join('LEFT', '#__ketshop_shipping AS s ON s.id=d.delivpnt_id AND s.delivery_type="at_delivery_point"')
	  ->where('d.id='.$delivery->id);
    // Setup the query
    $db->setQuery($query);
    $address = $db->loadAssoc();

    $delivery->address = $address;

    // Return the result
    return $delivery;
  }


  public function getBillingAddress()
  {
    //Get the selected order.
    $order = $this->getItem();

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('a.street,a.city,a.postcode,a.phone,a.note,c.lang_var AS country_lang_var,r.lang_var AS region_lang_var')
	  ->from('#__ketshop_address AS a')
	  ->join('LEFT','#__ketshop_country AS c ON c.alpha_2= a.country_code')
	  ->join('LEFT','#__ketshop_region AS r ON r.code=a.region_code')
	  ->where('a.id='.$order->billing_address_id);
    // Setup the query
    $db->setQuery($query);

    return $db->loadAssoc();
  }
}

