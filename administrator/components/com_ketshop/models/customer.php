<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelCustomer extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  public function getTable($type = 'Customer', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
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
    //We need to add several data to customer item. Those data comes from
    //different tables.  
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get user data.
    $query->select('u.name, u.username, u.email, u.registerDate, u.lastvisitDate');
    $query->from('#__users AS u');
    $query->join('INNER','#__ketshop_customer AS c ON c.id='.$pk);
    $query->where('u.id = c.user_id');
    $db->setQuery($query);
    $userData = $db->loadAssoc();

    //Add data to customer item.
    foreach($userData as $key => $value) {
      $item->$key = $value;
    }

    //Get the last shipping address set by the customer. 
    $query->clear();
    $query->select('a.street AS street_sh,a.postcode AS postcode_sh,'.
		   'a.city AS city_sh,a.region_code AS region_code_sh,'.
		   'a.country_code AS country_code_sh,a.phone AS phone_sh,'.
		   'a.note AS note_sh,co.lang_var AS country_lang_var_sh,r.lang_var AS region_lang_var_sh')
	  ->from('#__ketshop_address AS a')
	  ->join('INNER','#__ketshop_customer AS c ON c.id='.$pk)
	  ->join('LEFT','#__ketshop_country AS co ON co.alpha_2=a.country_code')
	  ->join('LEFT','#__ketshop_region AS r ON r.code=a.region_code')
	  ->where('a.item_id = c.user_id AND a.type="shipping" AND a.item_type="customer"')
	  ->order('a.created DESC LIMIT 1');
    $db->setQuery($query);
    $shippingAddress = $db->loadAssoc();

    //Add data to customer item.
    if(!is_null($shippingAddress)) {
      foreach($shippingAddress as $key => $value)
	$item->$key = $value;
    }

    //Get the last billing address set by the customer. 
    $query->clear();
    $query->select('a.street AS street_bi,a.postcode AS postcode_bi,'.
		   'a.city AS city_bi,a.region_code AS region_code_bi,'.
		   'a.country_code AS country_code_bi,a.phone AS phone_bi,'.
		   'a.note AS note_bi,co.lang_var AS country_lang_var_bi,r.lang_var AS region_lang_var_bi')
	  ->from('#__ketshop_address AS a')
	  ->join('INNER','#__ketshop_customer AS c ON c.id='.$pk)
	  ->join('LEFT','#__ketshop_country AS co ON co.alpha_2=a.country_code')
	  ->join('LEFT','#__ketshop_region AS r ON r.code=a.region_code')
	  ->where('a.item_id = c.user_id AND a.type="billing" AND item_type="customer"')
	  ->order('a.created DESC LIMIT 1');
    $db->setQuery($query);
    $billingAddress = $db->loadAssoc();

    //Add data to customer item.
    if(!is_null($billingAddress)) {
      foreach($billingAddress as $key => $value) {
	$item->$key = $value;
      }
    }
    //End override.

    return $item;
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.customer', 'customer', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.customer.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  public function getOrders() 
  {
    //Get the selected customer.
    $customer = $this->getItem();

    //Get the customer form.
    $form = $this->getForm();
    //Get the default limit_item value.
    $defaultLimitItem = $form->getFieldAttribute('limit_item', 'default');
    //Set the default limit_item value. 
    $limitItem = JFactory::getApplication()->input->post->get('limit_item', $defaultLimitItem, 'int');

    //Note: Default value set to zero means to get all of the items.
    $limit = '';
    if($limitItem) {
      $limit = 'LIMIT '.$limitItem;
    }

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('o.id,o.name,o.final_cart_amount,o.cart_status,o.order_status,'.
	           'o.currency_code,o.created,d.final_shipping_cost,'.
		   'd.status AS shipping_status,t.status AS payment_status');
    $query->from('#__ketshop_order AS o');
    $query->join('LEFT', '#__ketshop_delivery AS d ON d.order_id=o.id');
    $query->join('LEFT', '#__ketshop_transaction AS t ON t.order_id=o.id');
    $query->where('o.user_id='.(int)$customer->user_id);
    $query->order('o.created DESC '.$limit);
    $db->setQuery($query);
    $orders = $db->loadObjectList();
    //Compute the total amount of each customer's order.
    foreach($orders as $order) {
      $order->total = $order->final_cart_amount + $order->final_shipping_cost;
    }

    // Return the result
    return $orders;
  }
}

