<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelShippers extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 's.id',
	      'name', 's.name',
	      'plugin_element', 's.plugin_element',
	      'pluginName', 'pluginName',
	      'created', 's.created',
	      'created_by', 's.created_by',
	      'user',
	      'ordering', 's.ordering',
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

    // List state information.
    parent::populateState('s.name', 'asc');
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
    $query->select($this->getState('list.select', 's.id, s.name, s.created, s.plugin_element, s.published,'.
						  's.ordering, s.created_by, s.checked_out, s.checked_out_time'));

    $query->from('#__ketshop_shipper AS s');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = s.created_by');

    //Get the plugin name.
    $query->select('e.name AS pluginName');
    $query->join('LEFT', '#__extensions AS e ON e.element=s.plugin_element AND e.folder="ketshopshipment"');


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

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('s.published= '.(int)$published);
    }
    elseif($published === '') {
      $query->where('(s.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=s.checked_out');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('s.created_by'.$type.(int) $userId);
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  //Check if all the plugins currently used by KetShop are still installed
  //and/or enabled.
  public function getMissingPlugins()
  {
    $items = $this->getItems();

    //Store all of the plugins which are currently used by KetShop.
    $usedPlugins = array();
    foreach($items as $item) {
      $usedPlugins[] = $item->plugin_element;
    }

    //Get all the enabled ketshopshipment plugins.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('element')
	  ->from('#__extensions')
	  ->where('type="plugin" AND folder="ketshopshipment" AND enabled=1');
    $db->setQuery($query);
    $shipmentPlugins = $db->loadColumn();

    //Running the array test.
    $missingPlugins = array();
    foreach($usedPlugins as $usedPlugin) {
      //If a plugin is missing we store it into the missing plugins array.
      if(!in_array($usedPlugin, $shipmentPlugins)) {
	$missingPlugins[] = $usedPlugin;
      }
    }

    return $missingPlugins;
  }
}


