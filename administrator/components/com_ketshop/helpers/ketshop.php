<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class KetshopHelper
{
  //Create the tabs bar ($viewName = name of the active view).
  public static function addSubmenu($viewName)
  {
    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_PRODUCTS'),
				      'index.php?option=com_ketshop&view=products', $viewName == 'products');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_ATTRIBUTES'),
				      'index.php?option=com_ketshop&view=attributes', $viewName == 'attributes');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_TAXES'),
				      'index.php?option=com_ketshop&view=taxes', $viewName == 'taxes');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_CURRENCIES'),
				      'index.php?option=com_ketshop&view=currencies', $viewName == 'currencies');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_COUNTRIES'),
				      'index.php?option=com_ketshop&view=countries', $viewName == 'countries');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_PRICE_RULES'),
				      'index.php?option=com_ketshop&view=pricerules', $viewName == 'pricerules');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_COUPONS'),
				      'index.php?option=com_ketshop&view=coupons', $viewName == 'coupons');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_ORDERS'),
				      'index.php?option=com_ketshop&view=orders', $viewName == 'orders');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_CUSTOMERS'),
				      'index.php?option=com_ketshop&view=customers', $viewName == 'customers');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_PAYMENT_MODES'),
				      'index.php?option=com_ketshop&view=paymentmodes', $viewName == 'paymentmodes');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_SHIPPINGS'),
				      'index.php?option=com_ketshop&view=shippings', $viewName == 'shippings');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_SHIPPERS'),
				      'index.php?option=com_ketshop&view=shippers', $viewName == 'shippers');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_TRANSLATIONS'),
				      'index.php?option=com_ketshop&view=translations', $viewName == 'translations');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_FILTERS'),
				      'index.php?option=com_ketshop&view=filters', $viewName == 'filters');

    JHtmlSidebar::addEntry(JText::_('COM_KETSHOP_SUBMENU_CATEGORIES'),
				      'index.php?option=com_categories&extension=com_ketshop', $viewName == 'categories');

    if($viewName == 'categories') {
      $document = JFactory::getDocument();
      $document->setTitle(JText::_('COM_KETSHOP_ADMINISTRATION_CATEGORIES'));
    }
  }


  //Get the list of the allowed actions for the user.
  public static function getActions($catIds = array())
  {
    $user = JFactory::getUser();
    $result = new JObject;

    $actions = array('core.admin', 'core.manage', 'core.create', 'core.edit',
		     'core.edit.own', 'core.edit.state', 'core.delete');

    //Get from the core the user's permission for each action.
    foreach($actions as $action) {
      //Check permissions against the component. 
      if(empty($catIds)) { 
	$result->set($action, $user->authorise($action, 'com_ketshop'));
      }
      else {
	//Check permissions against the component categories.
	foreach($catIds as $catId) {
	  if($user->authorise($action, 'com_ketshop.category.'.$catId)) {
	    $result->set($action, $user->authorise($action, 'com_ketshop.category.'.$catId));
	    break;
	  }

	  $result->set($action, $user->authorise($action, 'com_ketshop.category.'.$catId));
	}
      }
    }

    return $result;
  }


  //Build the user list for the filter.
  public static function getUsers($itemName)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('u.id AS value, u.name AS text');
    $query->from('#__users AS u');
    //Get only the names of users who have created items, this avoids to
    //display all of the users in the drop down list.
    $query->join('INNER', '#__ketshop_'.$itemName.' AS i ON i.created_by = u.id');
    $query->group('u.id');
    $query->order('u.name');

    // Setup the query
    $db->setQuery($query);

    // Return the result
    return $db->loadObjectList();
  }


  public static function checkSelectedFilter($filterName, $unique = false)
  {
    $post = JFactory::getApplication()->input->post->getArray();

    //Ensure the given filter has been selected.
    if(isset($post['filter'][$filterName]) && !empty($post['filter'][$filterName])) {
      //Ensure that only the given filter has been selected.
      if($unique) {
	$filter = 0;
	foreach($post['filter'] as $value) {
	  if(!empty($value)) {
	    $filter++;
	  }
	}

	if($filter > 1) {
	  return false;
	}
      }

      return true;
    }

    return false;
  }


  public static function mappingTableOrder($pks, $tagId, $limitStart)
  {
    //Check first the user can edit state.
    $user = JFactory::getUser();
    if(!$user->authorise('core.edit.state', 'com_ketshop')) {
      return false;
    }

    //Start ordering from 1 by default.
    $ordering = 1;

    //When pagination is used set ordering from limitstart value.
    if($limitStart) {
      $ordering = (int)$limitStart + 1;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Update the ordering values of the mapping table. 
    foreach($pks as $pk) {
      $query->clear();
      $query->update('#__ketshop_product_tag_map')
	    //Update the item ordering via the mapping table.
	    ->set('ordering='.$ordering)
	    ->where('product_id='.(int)$pk)
	    ->where('tag_id='.(int)$tagId);
      $db->setQuery($query);
      $db->query();

      $ordering++;
    }

    return true;
  }


  public static function removeTagsOnTheFly(&$newTags)
  {
    foreach($newTags as $key => $tagId) {
      //Check for newly created tags (ie: id=#new#Title of the tag)
      if(substr($tagId, 0, 5) == '#new#') {
	//Remove the new tag from the tag data.
	unset($newTags[$key]);
      }
    }

    //Don't return an empty array. Return null instead.
    if(empty($newTags)) {
      $newTags = null;
    }

    return;
  }


  public static function checkMainTags($pks)
  {

    $ids = array();
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    foreach($pks as $pk) {
      // Find node and all children keys
      $query->clear();
      $query->select('c.id')
	    ->from('#__tags AS node')
	    ->leftJoin('#__tags AS c ON node.lft <= c.lft AND c.rgt <= node.rgt')
	    ->where('node.id = '.(int)$pk);
      $db->setQuery($query);
      $results = $db->loadColumn();

      $ids = array_unique(array_merge($ids,$results), SORT_REGULAR);
    }

    //Checks that no product item is using one of the tags as main tag.
    $query->clear();
    $query->select('COUNT(*)')
	  ->from('#__ketshop_product')
	  ->where('main_tag_id IN('.implode(',', $ids).')');
    $db->setQuery($query);

    if($db->loadResult()) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_WARNING_TAG_USED_AS_MAIN_TAG'), 'warning');
      return false;
    }

    return true;
  }


  /**
   * Updates a mapping table according to the variables passed as arguments.
   *
   * @param string  $table   The name of the table to update (eg: #__table_name).
   * @param array   $columns Array of table's column. Important: Primary key name must be set as the first array's element.
   * @param array   $data    Array of JObject containing the column values, (values order must match the column order).
   * @param integer $pkId    The common id which hold the data rows together.
   *
   * @return void
   */
  public static function updateMappingTable($table, $columns, $data, $pkId)
  {
    // Ensures first we have a valid primary key.
    if(isset($columns[0]) && !empty($columns[0])) {
      $pk = $columns[0];
    }
    else {
      return;
    }

    // Creates a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    // Deletes all the previous items linked to the primary key.
    $query->delete($db->quoteName($table));
    $query->where($pk.'='.(int)$pkId);
    $db->setQuery($query);
    $db->execute();

    // If no item has been defined no need to go further. 
    if(count($data)) {
      // List of the numerical fields for which no quotes must be used.
      $unquoted = array('id','prod_id','attrib_id','filter_id','shipping_id',
	                'cost','bundle_id','quantity','ordering','published','ordering');

      // Builds the VALUES clause of the INSERT MySQL query.
      $values = array();

      foreach($data as $itemValues) {
	$row = '';
	foreach($itemValues as $key => $value) {
	  if(in_array($key, $unquoted)) {
	    // Don't quote the numerical values.
	    $row .= $value.',';
	  }
	  else { 
	    $row .= $db->Quote($value).',';
	  }
	}

	// Removes comma from the end of the string.
	$row = substr($row, 0, -1);
	// Inserts a new row in the "values" clause.
	$values[] = $row;
      }

      // Inserts a new row for each item linked to the primary id(s).
      $query->clear();
      $query->insert($db->quoteName($table));
      $query->columns($columns);
      $query->values($values);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }
}


