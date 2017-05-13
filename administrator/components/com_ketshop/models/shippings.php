<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelShippings extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 's.id',
	      'name', 's.name',
	      'created', 's.created',
	      'created_by', 's.created_by',
	      'user', 'user_id',
	      'ordering', 's.ordering',
	      'delivery_type', 's.delivery_type',
	      'min_weight', 's.min_weight',
	      'max_weight', 's.max_weight',
	      'min_product', 's.min_product',
	      'max_product', 's.max_product',
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

    $deliveryType = $app->getUserStateFromRequest($this->context.'.filter.delivery_type', 'filter_delivery_type');
    $this->setState('filter.delivery_type', $deliveryType);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    // List state information.
    parent::populateState('s.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.delivery_type');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 's.id, s.delivery_type, s.name, s.min_weight, s.max_weight, '.
	                           's.min_product, s.max_product, s.created, s.published, '.
				   's.ordering, s.created_by, s.checked_out, s.checked_out_time'));

    $query->from('#__ketshop_shipping AS s');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('INNER', '#__users AS u ON u.id = s.created_by');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('s.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(s.name LIKE '.$search.')');
      }
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('s.created_by'.$type.(int) $userId);
    }

    //Filter by delivery type.
    $deliveryType = $this->getState('filter.delivery_type');
    if(!empty($deliveryType)) {
      $query->where('s.delivery_type='.$db->Quote($deliveryType));
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=s.checked_out');

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('s.published= '.(int)$published);
    }
    elseif($published === '') {
      $query->where('(s.published IN (0, 1))');
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


