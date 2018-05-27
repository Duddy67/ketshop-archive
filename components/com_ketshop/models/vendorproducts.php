<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelVendorproducts extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'vp.id',
	      'name', 'vp.name',
	      'base_price', 'vp.base_price',
	      'sale_price', 'vp.sale_price',
	      'stock', 'vp.stock',
	      'sales', 'vp.sales',
	      'created', 'vp.created',
	      'modified', 'vp.modified',
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

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag');
    $this->setState('filter.tag', $tag);

    $productType = $this->getUserStateFromRequest($this->context.'.filter.product_type', 'filter_product_type');
    $this->setState('filter.product_type', $productType);

    // List state information.
    parent::populateState('vp.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.tag');
    $id .= ':'.$this->getState('filter.product_type');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Gets the user.
    $user = JFactory::getUser();

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'vp.id, vp.name, vp.type, vp.alias, vp.base_price, vp.sale_price, vp.stock,'.
				   'vp.sales, vp.catid, vp.main_tag_id, vp.created, vp.published, vp.modified'));

    $query->from('#__ketshop_product AS vp');

    //Join over delivery and transaction tables.
    //$query->join('INNER', '#__ketshop_delivery AS d ON o.id=d.order_id');

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('vp.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(vp.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('vp.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(vp.published IN (0, 1))');
    }

    // Filter by a single tag.
    $tagId = $this->getState('filter.tag');

    if(is_numeric($tagId)) {
      $query->where($db->quoteName('tagmap.tag_id').' = '.(int)$tagId)
	    ->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap').
		   ' ON '.$db->quoteName('tagmap.content_item_id').' = '.$db->quoteName('vp.id').
		   ' AND '.$db->quoteName('tagmap.type_alias').' = '.$db->quote('com_ketshop.product'));
    }

    //Filter by product type.
    $productType = $this->getState('filter.product_type');
    if(!empty($productType)) {
      $query->where('vp.type='.$db->Quote($productType));
    }

    //Fetches the products linked to the user.
    $query->where('vp.created_by='.(int)$user->id);

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


