<?php
/**
 * @package KetShop 
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelPricerules extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'pr.id',
	      'name', 'pr.name',
	      'published', 'pr.published',
	      'created', 'pr.created',
	      'created_by', 'pr.created_by',
	      'user', 'user_id',
	      'value', 'pr.value',
	      'behavior', 'pr.behavior',
	      'type', 'pr.type', 'prule_type', 
	      'ordering', 'pr.ordering',
	      //'ordering', 'prc.ordering',
      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $pruleType = $this->getUserStateFromRequest($this->context.'.filter.prule_type', 'filter_prule_type');
    $this->setState('filter.prule_type', $pruleType);

    $behavior = $this->getUserStateFromRequest($this->context.'.filter.behavior', 'filter_behavior');
    $this->setState('filter.behavior', $behavior);

    // List state information.
    parent::populateState('pr.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.prule_type');
    $id .= ':'.$this->getState('filter.behavior');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'pr.id, pr.name, pr.operation, pr.created, pr.type, pr.recipient, pr.target,'.
				   'pr.published, pr.behavior, pr.ordering, pr.value, pr.created_by, pr.publish_up,'.
				   'pr.publish_down, pr.checked_out, pr.checked_out_time'));

    $query->from('#__ketshop_price_rule AS pr');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = pr.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=pr.checked_out');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('pr.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(pr.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('pr.published= '.(int)$published);
    }
    elseif($published === '') {
      $query->where('(pr.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('pr.created_by'.$type.(int) $userId);
    }

    //Filter by price rule type.
    if($pruleType = $this->getState('filter.prule_type')) {
      $query->where('pr.type = '.$db->quote($pruleType));
    }

    //Filter by price rule behavior.
    if($behavior = $this->getState('filter.behavior')) {
      $query->where('pr.behavior = '.$db->quote($behavior));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'pr.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc
//file_put_contents('debog_pricerules_model.txt', print_r($query, true));
    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  /**
   * Method to get an array of data items.
   *
   * @return  mixed  An array of data items on success, false on failure.
   *
   * @since   11.1
   */
  public function getItems()
  {
    // Get a storage key.
    $store = $this->getStoreId();

    // Try to load the data from internal storage.
    if(isset($this->cache[$store])) {
      return $this->cache[$store];
    }

    // Load the list items.
    $query = $this->_getListQuery();
    $items = $this->_getList($query, $this->getStart(), $this->getState('list.limit'));

    // Check for a database error.
    if($this->_db->getErrorNum()) {
      $this->setError($this->_db->getErrorMsg());
      return false;
    }
/*
    $pruleCustomers = $pruleCustomerGroups = $pruleProducts = $pruleProductCats = array();

    foreach ($items as $item)
    {
      if($item->recipient == 'customer') {
	$pruleCustomers[] = (int) $item->id;
      }
      else { // customer_group
	$pruleCustomerGroups[] = (int) $item->id;
      }

      if($item->target == 'product' || $item->target == 'bundle') {
	$pruleProducts[] = (int) $item->id;
      }
      else { // product_cat
	$pruleProductCats[] = (int) $item->id;
      }
    }

    if(!empty($pruleCustomers)) {
      $query->select('name')
	    ->from('#__users')
	    ->join('#__ketshop_prule_recipient ON id = item_id')
	    ->where('prule_id IN('.implode(',', $pruleCustomers).')');
      $db->setQuery($query);
    }

    if(!empty($pruleCustomerGroups)) {
    }

    if(!empty($pruleProducts)) {
    }

    if(!empty($pruleProductCats)) {
    }
*/
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Place for each item the name of the linked recipients according to 
    //their type (customer, customer_group) into an array.   
    foreach($items as $item) {
      $query->clear();
      if($item->recipient == 'customer') {
	$query->select('name')
	      ->from('#__users')
	      ->join('LEFT', '#__ketshop_prule_recipient ON id = item_id')
	      ->where('prule_id='.$item->id);
      }
      else { // customer_group
	$query->select('title')
	      ->from('#__usergroups')
	      ->join('LEFT', '#__ketshop_prule_recipient ON id = item_id')
	      ->where('prule_id='.$item->id);
      }

      $db->setQuery($query);
      $recipients = $db->loadColumn();
      $item->recipients = $recipients;


      //Same than above but for products.
      $query->clear();
      if($item->target == 'product' || $item->target == 'bundle') {
	$query->select('name')
	      ->from('#__ketshop_product')
	      ->join('LEFT', '#__ketshop_prule_target ON id = item_id')
	      ->where('prule_id='.$item->id);
      }
      else { // product_cat
	$query->select('title')
	      ->from('#__categories')
	      ->join('LEFT', '#__ketshop_prule_target ON id = item_id')
	      ->where('prule_id='.$item->id);
      }

      $db->setQuery($query);
      $targets = $db->loadColumn();
      $item->targets = $targets;
    }

    // Add the items to the internal cache.
    $this->cache[$store] = $items;

    return $this->cache[$store];
  }


  //Build the price rule types list for the filter.
  public function getTypes()
  {
    $catalog = new JObject;
    $catalog->value = 'catalog';
    $catalog->text = JText::_('COM_KETSHOP_OPTION_CATALOG');

    $cart = new JObject;
    $cart->value = 'cart';
    $cart->text = JText::_('COM_KETSHOP_OPTION_CART');

    $types = array();
    $types[0] = $catalog;
    $types[1] = $cart;

    return $types;
  }


  //Build the price rule behaviors list for the filter.
  public function getBehaviors()
  {
    $and = new JObject;
    $and->value = 'AND';
    $and->text = JText::_('COM_KETSHOP_OPTION_CUMULATIVE');

    $xor = new JObject;
    $xor->value = 'XOR';
    $xor->text = JText::_('COM_KETSHOP_OPTION_EXCLUSIVE');

    $behaviors = array();
    $behaviors[0] = $and;
    $behaviors[1] = $xor;

    return $behaviors;
  }
}


