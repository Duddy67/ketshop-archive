<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class KetshopModelProducts extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array('id', 'p.id',
				       'name', 'p.name', 
				       'alias', 'p.alias',
				       'created', 'p.created', 
				       'created_by', 'p.created_by',
				       'published', 'p.published', 
			               'access', 'p.access', 'access_level',
				       'type', 'p.type', 'product_type', 'product_prop',
				       'user', 'user_id',
				       'stock', 'p.stock',
				       'ordering', 'p.ordering', 'tm.ordering', 'tm_ordering',
				       'hits', 'p.hits',
				       'catid', 'p.catid', 'category_id',
                                       'p.main_tag_id', 'main_tag_id',
				       'tag'
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

    $access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access');
    $this->setState('filter.access', $access);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $categoryId = $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id');
    $this->setState('filter.category_id', $categoryId);

    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag');
    $this->setState('filter.tag', $tag);

    $mainTagId = $this->getUserStateFromRequest($this->context . '.filter.main_tag_id', 'filter_main_tag_id');
    $this->setState('filter.main_tag_id', $mainTagId);

    $productType = $this->getUserStateFromRequest($this->context.'.filter.product_type', 'filter_product_type');
    $this->setState('filter.product_type', $productType);

    // List state information.
    parent::populateState('p.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.access');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.category_id');
    $id .= ':'.$this->getState('filter.tag');
    $id .= ':'.$this->getState('filter.main_tag_id');
    $id .= ':'.$this->getState('filter.product_type');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $user = JFactory::getUser();

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'p.id,p.name,p.alias,p.created,p.published,p.catid,p.hits,'.
				   'p.main_tag_id,p.base_price,p.sale_price,p.type,p.stock,p.variant_name,'. 
				   'p.access,p.ordering,p.created_by,p.checked_out,p.checked_out_time'))
	  ->from('#__ketshop_product AS p');

    //Get the user name.
    $query->select('us.name AS user')
	  ->join('LEFT', '#__users AS us ON us.id = p.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor')
	  ->join('LEFT', '#__users AS uc ON uc.id=p.checked_out');

    // Join over the categories.
    $query->select('ca.title AS category_title')
	  ->join('LEFT', '#__categories AS ca ON ca.id = p.catid');

    // Join over the main tags.
    $query->select('t.title AS main_tag_title')
          ->join('LEFT', '#__tags AS t ON t.id = p.main_tag_id');

    // Join over the asset groups.
    $query->select('al.title AS access_level')
	  ->join('LEFT', '#__viewlevels AS al ON al.id = p.access');

    //Filter by component category.
    $categoryId = $this->getState('filter.category_id');
    if(is_numeric($categoryId)) {
      $query->where('p.catid = '.(int)$categoryId);
    }
    elseif(is_array($categoryId)) {
      JArrayHelper::toInteger($categoryId);
      $categoryId = implode(',', $categoryId);
      $query->where('p.catid IN ('.$categoryId.')');
    }

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('p.id = '.(int) substr($search, 3));
      }
      elseif(stripos($search, 'al:') === 0) { //Searches by alias
        $search = $db->Quote('%'.$db->escape(substr($search, 3), true).'%');
        $query->where('(p.alias LIKE '.$search.')');
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(p.name LIKE '.$search.')');
      }
    }

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('p.access='.(int) $access);
    }

    // Filter by access level on categories.
    if(!$user->authorise('core.admin')) {
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('p.access IN ('.$groups.')');
      $query->where('ca.access IN ('.$groups.')');
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('p.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(p.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('p.created_by'.$type.(int) $userId);
    }

    // Filter by a single tag.
    $tagId = $this->getState('filter.tag');

    if(is_numeric($tagId)) {
      $query->where($db->quoteName('tagmap.tag_id').' = '.(int)$tagId)
	    ->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap').
		   ' ON '.$db->quoteName('tagmap.content_item_id').' = '.$db->quoteName('p.id').
		   ' AND '.$db->quoteName('tagmap.type_alias').' = '.$db->quote('com_ketshop.product'));
    }

    // Filter by main tag.
    if($mainTagId = $this->getState('filter.main_tag_id')) {
      $query->where('p.main_tag_id= '.(int)$mainTagId);
    }

    //Variable sent from both price rule and bundle views in order to display only 
    //the selected product type in the product modal window (ie: normal or bundle).
    $productTypeModal = JFactory::getApplication()->input->get->get('product_type', '', 'string');

    //Set the SQL WHERE clause according to the defined variable.
    //Note: If both variables are defined, it's $productTypeModal that will be
    //treated. 
    if($productTypeModal || ($productType = $this->getState('filter.product_type'))) {
      if($productTypeModal) {
	$query->where('p.type='.$db->Quote($productTypeModal));
	$query->where('p.published=1'); //Display only published products.

	//Check for bundle product calling.
        if(JFactory::getApplication()->input->get->get('type', '', 'string') == 'bundleproduct') {
	  $query->where('p.has_variants=0'); //We don't use products with variants as bundle products.
	}
      }
      else { //Filter by type.
	$query->where('p.type='.$db->Quote($productType));
      }
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'p.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    //In case only the tag filter is selected we want the products to be displayed according
    //to the mapping table ordering.
    if(is_numeric($tagId) && KetshopHelper::checkSelectedFilter('tag', true) && $orderCol == 'p.ordering') {
      //Join over the product/tag mapping table.
      $query->select('ISNULL(tm.ordering), tm.ordering AS tm_ordering')
	    ->join('LEFT', '#__ketshop_product_tag_map AS tm ON p.id=tm.product_id AND tm.tag_id='.(int)$tagId);

      //Switch to the mapping table ordering.
      //Note: Products with a NULL ordering value are placed at the end of the list.
      $orderCol = 'ISNULL(tm.ordering) ASC, tm_ordering';
    }

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  public function getProductVariants($id)
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('prod_id, var_id, variant_name, base_price, sale_price, code, stock')
	  ->from('#__ketshop_product_variant')
	  ->where('prod_id='.(int)$id);
    $db->setQuery($query);

    return $db->loadAssocList();
  }
}


