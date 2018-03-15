<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
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


  /**
   * Update a mapping table according to the variables passed as arguments.
   *
   * @param string $table The name of the table to update (eg: #__table_name).
   * @param array $columns Array of table's column, (primary key name must be set as the first array's element).
   * @param array $data Array of JObject containing the column values, (values order must match the column order).
   * @param array $ids Array containing the ids of the items to update.
   *
   * @return void
   */
  public static function updateMappingTable($table, $columns, $data, $ids)
  {
    //Ensure we have a valid primary key.
    if(isset($columns[0]) && !empty($columns[0])) {
      $pk = $columns[0];
    }
    else {
      return;
    }

    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Delete all the previous items linked to the primary id(s).
    $query->delete($db->quoteName($table));
    $query->where($pk.' IN('.implode(',', $ids).')');
    $db->setQuery($query);
    $db->execute();

    //If no item has been defined no need to go further. 
    if(count($data)) {
      //List of the numerical fields (no quotes must be used).
      $integers = array('id','prod_id','bundle_id','quantity','ordering');

      //Build the VALUES clause of the INSERT MySQL query.
      $values = array();
      foreach($ids as $id) {
	foreach($data as $itemValues) {
	  //Set the primary id to link the item with.
	  $row = $id.',';

	  foreach($itemValues as $key => $value) {
	    //No integer values must be quoted.
	    if(!in_array($key, $integers)) {
	      $row .= $db->Quote($value).',';
	    }
	    else { //Don't quote the numerical values.
	      $row .= $value.',';
	    }
	  }

	  //Remove comma from the end of the string.
	  $row = substr($row, 0, -1);
	  //Insert a new row in the "values" clause.
	  $values[] = $row;
	}
      }

      //Insert a new row for each item linked to the primary id(s).
      $query->clear();
      $query->insert($db->quoteName($table));
      $query->columns($columns);
      $query->values($values);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  public static function setProductOptions($prodId, $prodData)
  {
    $options = $optIds = $optValues = $attribValues = array();
    $isEmpty = true;
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //First check if some options have been set and store all the id of the already 
    //existing options (eg: which are not new).
    foreach($prodData as $key => $optId) {
      if(preg_match('#^option_id_([0-9]+)$#', $key)) {
	if($optId) { //Option already exists.
	  $optIds[] = $optId;
	}
        //One or more options have been set.
	$isEmpty = false;
      }
    }

    //If no option has been set we reset values (just in case).
    if($isEmpty) {
      $fields = array('attribute_group=0', 'option_name=""'); 
      $query->update('#__ketshop_product')
	    ->set($fields)
	    ->where('id='.(int)$prodId);
      $db->setQuery($query);
      $db->query();
    }

    //First delete all the previous options linked to the product.
    $query->clear();
    $query->delete('#__ketshop_product_option')
	  ->where('prod_id='.(int)$prodId);
    $db->setQuery($query);
    $db->query();

    //Same for the previous attributes linked to the options.
    $query->clear();
    $query->delete('#__ketshop_opt_attrib')
	  ->where('prod_id='.(int)$prodId);
    $db->setQuery($query);
    $db->query();

    //No need to go further.
    if($isEmpty) {
      return;
    }

    //Get field data of the attributes of the group to figure out the text corresponding
    //to the selected value.
    $query->clear();
    $query->select('attrib_id, field_value_1, field_text_1')
	  ->from('#__ketshop_attrib_group')
	  ->join('INNER', '#__ketshop_attribute ON id=attrib_id')
	  ->where('group_id='.(int)$prodData['jform']['attribute_group'])
	  //Ensure we're dealing with a drop down list.
	  ->where('field_type_1="closed_list"');
    $db->setQuery($query);
    //Set the array index with the attribute ids.
    $fieldData = $db->loadAssocList('attrib_id');

    foreach($prodData as $key => $value) {
      if(preg_match('#^option_id_([0-9]+)$#', $key, $matches)) {
	$optNb = $matches[1];
	$optId = $prodData['option_id_'.$optNb];

	//Option is new.
	if(!$optId) {
	  //Search for a unique option id.
	  $optId = 1;
	  while(in_array($optId, $optIds)) {
	    $optId++;
	  }
          //Store the new id.
	  $optIds[] = $optId;
	}

	//Store values to insert.
	$optValues[] = (int)$prodId.','.(int)$optId.','.$db->Quote($prodData['option_name_'.$optNb]).','.(int)$prodData['stock_'.$optNb].
			','.$prodData['base_price_'.$optNb].','.$prodData['sale_price_'.$optNb].','.$db->Quote($prodData['code_'.$optNb]).
			','.$db->Quote($prodData['published_'.$optNb]).','.(int)$prodData['availability_delay_'.$optNb].
			','.$prodData['weight_'.$optNb].','.$prodData['length_'.$optNb].','.$prodData['width_'.$optNb].
			','.$prodData['height_'.$optNb].','.$prodData['ordering_'.$optNb];

	//Now search for the attributes linked to this option.
	foreach($prodData as $k => $val) {
	  if(preg_match('#^attribute_([0-9]+)_'.$optNb.'$#', $k, $matches)) {
	    $attribId = $matches[1];

	    $text = '';
	    if(!@is_null($fieldData[$attribId])) {
	      //Turn the value and text data of the drop down list into arrays.
	      $fieldVal1 = explode('|', $fieldData[$attribId]['field_value_1']);
	      $fieldText1 = explode('|', $fieldData[$attribId]['field_text_1']); 
	      //Search for the position of the selected value.
	      foreach($fieldVal1 as $pos => $v) {
		if($v == $val) {
		  //Set the corresponding text.
		  $text = $fieldText1[$pos];
		  break;
		}
	      }
	    }

	    //Store values to insert.
	    $attribValues[] = (int)$prodId.','.(int)$optId.','.(int)$attribId.','.$db->Quote($val).','.$db->Quote($text);
	  }
	}
      }
    }

    //Insert a new row for each option linked to the product.
    $columns = array('prod_id', 'opt_id', 'option_name', 'stock',
		     'base_price', 'sale_price', 'code', 'published', 'availability_delay',
		     'weight', 'length', 'width', 'height', 'ordering');
    $query->clear();
    $query->insert('#__ketshop_product_option')
	  ->columns($columns)
	  ->values($optValues);
    $db->setQuery($query);
    $db->query();

    //Insert a new row for each attribute linked to the product options.
    $columns = array('prod_id', 'opt_id', 'attrib_id', 'attrib_value', 'attrib_text');
    $query->clear();
    $query->insert('#__ketshop_opt_attrib')
	  ->columns($columns)
	  ->values($attribValues);
    $db->setQuery($query);
    $db->query();

    return;
  }


  public static function checkProductOptions()
  {
    //Get all of the POST data.
    $post = JFactory::getApplication()->input->post->getArray();
    $prodOpt = false;
    //Detect if at least one product option has been set.
    foreach($post as $key => $val) {
      if(preg_match('#^option_id_([0-9]+)$#', $key)) {
	$prodOpt = true;
	break;
      }
    }

    if($prodOpt) {
      $attribIds = array();
      //Get all the attribute ids set for the product.
      foreach($post as $key => $value) {
	if(preg_match('#^attribute_id_([0-9]+)$#', $key) && $value) {
	  $attribIds[] = $value;
	}
      }

      //If no attribute is present no option can be set.
      if(empty($attribIds)) {
	return false;
      }

      //Get the id of the selected option attribute group.
      $attribGroupId = $post['jform']['attribute_group'];

      //Get the id of the option attributes 
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('attrib_id')
	    ->from('#__ketshop_attrib_group')
	    ->where('group_id='.(int)$attribGroupId);
      $db->setQuery($query);
      $optAttribIds = $db->loadColumn();

      //Check that all option attributes are also present as attribute of the main product.
      foreach($optAttribIds as $optAttribId) {
	if(!in_array($optAttribId, $attribIds)) {
	  return false;
	}
      }
    }

    return true;
  }
}


