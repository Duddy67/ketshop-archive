<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modelitem');
require_once JPATH_COMPONENT_SITE.'/helpers/pricerule.php';


class KetshopModelEndpurchase extends JModelItem
{

  /**
   * Model context string.
   *
   * @access	protected
   * @var		string
   */
  protected $_context = 'com_ketshop.endpurchase';

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
    $id = JFactory::getApplication()->input->get('order_id', 0);
    $this->setState('endpurchase.id', $id);

    // Load the parameters.
    $this->setState('params', $params);
  }

  /**
   * Method to get an ojbect.
   *
   * @param	integer	The id of the object to get.
   *
   * @return	mixed	Object on success, false on failure.
   */
  public function &getItem($id = null)
  {
    if($this->_item === null) {
      $this->_item = false;

      if(empty($id)) {
	$id = $this->getState('endpurchase.id');
      }

      // Get a level row instance.
      $table = JTable::getInstance('Order', 'KetshopTable');

      // Attempt to load the row.
      if($table->load($id)) {
	// Check published state.
	if($published = $this->getState('filter.published')) {
	  if($table->published != $published) {
	    return $this->_item;
	  }
	}

	// Convert the JTable to a clean JObject.
	$properties = $table->getProperties(1);
	$this->_item = JArrayHelper::toObject($properties, 'JObject');
      }
      elseif($error = $table->getError()) {
	$this->setError($error);
      }
    }

    return $this->_item;
  }


  public function getProducts()
  {
    //Get the order id.
    $id = $this->getState('endpurchase.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('op.prod_id,op.name,op.option_name,op.code,op.unit_sale_price,op.unit_price,'.
	           'op.cart_rules_impact,op.quantity,op.tax_rate,p.id,p.catid,p.alias,p.attribute_group')
	  ->from('#__ketshop_order_prod AS op')
	  ->join('LEFT', '#__ketshop_product AS p ON p.id=op.prod_id')
	  ->where('op.order_id = '.(int)$id);
    // Setup the query
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public function getPriceRules()
  {
    //Get the order id.
    $id = $this->getState('endpurchase.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('prule_id AS id,prod_id,name,type,target,operation,value,show_rule')
	  ->from('#__ketshop_order_prule')
	  ->where('order_id = '.(int)$id);
    // Setup the query
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public function getShippingData()
  {
    //Get the order id.
    $id = $this->getState('endpurchase.id');

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
    $id = $this->getState('endpurchase.id');

    //Get the proper shipping address (delivery point or customer shipping address).
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('a.street, a.city, a.postcode,a.region_code, a.phone, a.note,'.
	           'c.lang_var AS country_lang_var,r.lang_var AS region_lang_var,u.name AS recipient')
	  ->from('#__ketshop_address AS a')
	  ->join('LEFT','#__ketshop_country AS c ON c.alpha_2=a.country_code')
          ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code')
          ->join('LEFT', '#__users AS u ON u.id=a.item_id');

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
    $id = $this->getState('endpurchase.id');

    //Check if a billing address has been set by the customer.
    if($this->_item->billing_address_id) {
      $db = $this->getDbo();
      $query = $db->getQuery(true);
      $query->select('a.street,a.city,a.postcode,a.region_code,a.phone,a.note,'.
	             'c.lang_var AS country_lang_var,r.lang_var AS region_lang_var,u.name AS recipient')
	    ->from('#__ketshop_address AS a')
	    ->join('LEFT','#__ketshop_country AS c ON c.alpha_2 = a.country_code')
	    ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code')
	    ->join('LEFT', '#__users AS u ON u.id=a.item_id')
	    ->where('a.id='.(int)$this->_item->billing_address_id.' ORDER BY a.id DESC LIMIT 0, 1');
      // Setup the query
      $db->setQuery($query);

      return $db->loadAssoc();
    }

    return null;
  }
}

