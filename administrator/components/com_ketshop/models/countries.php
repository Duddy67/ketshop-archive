<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

JLoader::register('InilangTrait', JPATH_ADMINISTRATOR.'/components/com_ketshop/traits/inilang.php');


class KetshopModelCountries extends JModelList
{
  use InilangTrait;

  public $countryName = null;


  public function __construct($config = array())
  {
    //Gets the column name to use for the country name according to the current language.
    $this->countryName = $this->getColumnName('country');

    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'c.id',
	      $this->countryName, 'c.'.$this->countryName,
	      'published', 'c.published',
	      'numerical', 'c.numerical',
	      'alpha_3', 'c.alpha_3',
	      'created', 'c.created',
	      'created_by', 'c.created_by',
	      'continent_code', 'c.continent_code', 
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

    $continentCode = $app->getUserStateFromRequest($this->context.'.filter.continent_code', 'filter_continent_code');
    $this->setState('filter.continent_code', $continentCode);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    //Stores the name of the column containing the country name.
    $this->setState('country_name', $this->countryName);

    // List state information.
    parent::populateState('c.'.$this->countryName, 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.continent_code');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'c.id,c.numerical,c.created,c.alpha_2,c.alpha_3,c.continent_code,'.
				   'c.published,c.created_by,c.checked_out,c.checked_out_time,c.lang_var,c.'.$this->countryName));

    $query->from('#__ketshop_country AS c');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = c.created_by');


    //Filter by name or alpha 3 search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'alpha:') === 0) {
        $search = substr($search, 6);
	$query->where('c.alpha_3 LIKE '.$db->Quote('%'.$db->escape($search, true).'%'));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(c.'.$this->countryName.' LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('c.published= '.(int)$published);
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

    //Filter by continent.
    $continentCode = $this->getState('filter.continent_code');
    if($continentCode) {
      $query->where('c.continent_code='.$db->Quote($continentCode));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'c.'.$this->countryName);
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


