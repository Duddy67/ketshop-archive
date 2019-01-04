<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelCurrencies extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'c.id',
	      'name', 'c.name',
	      'published', 'c.published',
	      'numerical', 'c.numerical',
	      'alpha', 'c.alpha',
	      'created', 'c.created',
	      'created_by', 'c.created_by',
	      'user', 'user_id'
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

    // List state information.
    parent::populateState('c.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'c.id,c.name,c.created,c.numerical,c.alpha,'.
				   'c.published,c.created_by,c.checked_out,c.checked_out_time,c.lang_var'));

    $query->from('#__ketshop_currency AS c');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = c.created_by');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('c.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(c.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('c.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(c.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=c.checked_out');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('c.created_by'.$type.(int) $userId);
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'c.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


