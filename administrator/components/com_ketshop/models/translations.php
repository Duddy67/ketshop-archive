<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelTranslations extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 't.id',
	      'name', 't.name',
	      'item_type', 't.item_type',
	      'created', 't.created',
	      'created_by', 't.created_by',
	      'user', 'user_id', 
	      'language', 't.language',
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
    if($layout = JRequest::getVar('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $language = $this->getUserStateFromRequest($this->context.'.filter.language', 'filter_language', '');
    $this->setState('filter.language', $language);

    $itemType = $app->getUserStateFromRequest($this->context.'.filter.item_type', 'filter_item_type');
    $this->setState('filter.item_type', $itemType);

    // List state information.
    parent::populateState('t.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.item_type');
    $id .= ':'.$this->getState('filter.language');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 't.id, t.name, t.created, '.
				   't.published, t.item_type, t.language, t.created_by,'.
				   't.checked_out, t.checked_out_time'));

    $query->from('#__ketshop_translation AS t');

    // Join over the language
    $query->select('l.title AS language_title');
    $query->join('LEFT', $db->quoteName('#__languages').' AS l ON l.lang_code = t.language');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('INNER', '#__users AS u ON u.id = t.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=t.checked_out');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('t.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(t.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('t.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(t.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('t.created_by'.$type.(int) $userId);
    }

    // Filter on the language.
    if($language = $this->getState('filter.language')) {
      $query->where('t.language = '.$db->quote($language));
    }

    //Filter by item type.
    $itemType = $this->getState('filter.item_type');
    if(!empty($itemType)) { 
      $query->where('t.item_type='.$db->Quote($itemType));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    //sqlsrv change
    if($orderCol == 'language') {
      $orderCol = 'l.title';
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  //Build the item types list for the filter.
  public function getItemTypes()
  {
    $product = new JObject;
    $product->value = 'product';
    $product->text = JText::_('COM_KETSHOP_OPTION_PRODUCT_ITEM_TYPE');

    $attribute = new JObject;
    $attribute->value = 'attribute';
    $attribute->text = JText::_('COM_KETSHOP_OPTION_ATTRIBUTE_ITEM_TYPE');

    $priceRule = new JObject;
    $priceRule->value = 'price_rule';
    $priceRule->text = JText::_('COM_KETSHOP_OPTION_PRICE_RULE_ITEM_TYPE');

    $tax = new JObject;
    $tax->value = 'tax';
    $tax->text = JText::_('COM_KETSHOP_OPTION_TAX_ITEM_TYPE');

    $shipping = new JObject;
    $shipping->value = 'shipping';
    $shipping->text = JText::_('COM_KETSHOP_OPTION_SHIPPING_ITEM_TYPE');

    $shipper = new JObject;
    $shipper->value = 'shipper';
    $shipper->text = JText::_('COM_KETSHOP_OPTION_SHIPPER_ITEM_TYPE');

    $deliveryPoint = new JObject;
    $deliveryPoint->value = 'delivery_point';
    $deliveryPoint->text = JText::_('COM_KETSHOP_OPTION_DELIVERY_POINT_ITEM_TYPE');

    $paymentMode = new JObject;
    $paymentMode->value = 'payment_mode';
    $paymentMode->text = JText::_('COM_KETSHOP_OPTION_PAYMENT_MODE_ITEM_TYPE');

    $itemTypes = array();
    $itemTypes[] = $product;
    $itemTypes[] = $attribute;
    $itemTypes[] = $priceRule;
    $itemTypes[] = $tax;
    $itemTypes[] = $shipping;
    $itemTypes[] = $shipper;
    $itemTypes[] = $deliveryPoint;
    $itemTypes[] = $paymentMode;

    return $itemTypes;
  }
}


