<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/ketshop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';


class KetshopModelProduct extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in tables/itemname.php file.
  public function getTable($type = 'Product', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.product', 'product', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.product.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if($item = parent::getItem($pk)) {
      //Get both intro_text and full_text together as producttext
      $item->producttext = trim($item->full_text) != '' ? $item->intro_text."<hr id=\"system-readmore\" />".$item->full_text : $item->intro_text;

      //Get tags for this item.
      if(!empty($item->id)) {
	$item->tags = new JHelperTags;
	$item->tags->getTagIds($item->id, 'com_ketshop.product');
      }
    }

    return $item;
  }


  /**
   * Prepare and sanitise the table data prior to saving.
   *
   * @param   JTable  $table  A JTable object.
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function prepareTable($table)
  {
    // Set the publish date to now
    if($table->published == 1 && (int)$table->publish_up == 0) {
      $table->publish_up = JFactory::getDate()->toSql();
    }

    if($table->published == 1 && intval($table->publish_down) == 0) {
      $table->publish_down = $this->getDbo()->getNullDate();
    }
  }


  /**
   * Saves the manually set order of records.
   *
   * @param   array    $pks    An array of primary key ids.
   * @param   integer  $order  +1 or -1
   *
   * @return  mixed
   *
   * @since   12.2
   */
  public function saveorder($pks = null, $order = null)
  {
    //First ensure only the tag filter has been selected.
    if(KetshopHelper::checkSelectedFilter('tag', true)) {

      if(empty($pks)) {
	JFactory::getApplication()->enqueueMessage(JText::_($this->text_prefix.'_ERROR_NO_ITEMS_SELECTED'), 'warning');
	return false;
      }

      //Get the id of the selected tag and the limitstart value.
      $post = JFactory::getApplication()->input->post->getArray();
      $tagId = $post['filter']['tag'];
      $limitStart = $post['limitstart'];

      //Set the mapping table ordering.
      KetshopHelper::mappingTableOrder($pks, $tagId, $limitStart);

      return true;
    }

    //Hand over to the parent function.
    return parent::saveorder($pks, $order);
  }


  //Overrided function.
  protected function canDelete($record)
  {
    //Check if the product is a part of a bundle, if it does the product cannot be
    //deleted.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('COUNT(*)')
	  ->from('#__ketshop_prod_bundle')
	  ->where('prod_id='.(int)$record->id);
    $db->setQuery($query);
    $prodId = $db->loadResult();

    //If the product is  linked to a bundle we display an error message.
    if($prodId) {
      JError::raiseWarning(403, JText::sprintf('COM_KETSHOP_ERROR_DELETE_LINKED_BUNDLE_PRODUCT', $record->name));
      return false;
    }

    return parent::canDelete($record);
  }


  //Overrided function.
  protected function canEditState($record)
  {
    //Check for product dependencies.
    //Note: No need to check when ordering.
    if($this->state->task != 'saveOrderAjax') {
      //Check if the product is a part of a bundle, if it does the status edition
      //of the product cannot be done.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('COUNT(*)')
	    ->from('#__ketshop_prod_bundle')
	    ->where('prod_id='.(int)$record->id);
      $db->setQuery($query);
      $prodId = $db->loadResult();

      //If the product is linked to a bundle we display an error message.
      if($prodId) {
	JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_EDIT_STATE_LINKED_BUNDLE_PRODUCT', $record->name), 'warning');
	return false;
      }
    }

    return parent::canEditState($record);
  }


  public function getAttributeData($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Get fields and values of the attributes linked to a given product. 
    //Note: Conditions are used here to assign values to the correct field.
    //In case of a drop down list, data from "pa" table is set as
    //the selected value(s) and data from "a" table is set as the values of the drop down list.
    //In case of an input field, data from "pa" table is set as the field value
    //and the selected value is empty.
    $query->select('pa.attrib_id AS id,a.name,a.published,a.field_type_1,a.value_type,a.field_text_1,a.field_type_2,a.field_text_2,'.
		   'IF(a.field_type_1 != "open_field",pa.field_value_1,"") AS selected_value_1,'.
		   'IF(a.field_type_2 != "open_field",pa.field_value_2,"") AS selected_value_2,'.
		   'IF(a.field_type_1 != "open_field",a.field_value_1,pa.field_value_1) AS field_value_1,'.
		   'IF(a.field_type_2 != "open_field",a.field_value_2,pa.field_value_2) AS field_value_2')
	  ->from('#__ketshop_prod_attrib AS pa ')
	  ->join('INNER', '#__ketshop_attribute AS a ON a.id = pa.attrib_id')
	  ->where('pa.prod_id='.$pk)
	  ->order('a.ordering');
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public function getImageData($pk = null, $isAdmin) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('src, width, height, alt, ordering') 
	  ->from('#__ketshop_prod_image')
	  ->where('prod_id='.$pk)
	  ->order('ordering');
    $db->setQuery($query);
    $images = $db->loadAssocList();

    if($isAdmin) {
      //Add "../" to the path of each image as we are in the administrator area.
      foreach($images as $key => $image) {
	$image['src'] = '../'.$image['src'];
	$images[$key] = $image;
      }
    }
    else {
      //On front-end we must set src with the absolute path or SEF will add a wrong url path.  
      $length = strlen('administrator/components/com_ketshop/js/ajax/');
      $length = $length - ($length * 2);
      $url = substr(JURI::root(), 0, $length);

      foreach($images as $key => $image) {
	$image['src'] = $url.$image['src'];
	$images[$key] = $image;
      }
    }

    return $images;
  }


  public function getVariantData($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('var_id, variant_name, base_price, sale_price, sales, code, stock,'.
		   'availability_delay, weight, length, width, height, published, ordering') 
	  ->from('#__ketshop_product_variant')
	  ->where('prod_id='.$pk)
	  ->order('var_id');
    $db->setQuery($query);
    $variants = $db->loadAssocList();

    if(!empty($variants)) {
      //Get attributes linked to the variants.
      $query->clear();
      $query->select('var_id, attrib_id, attrib_value') 
	    ->from('#__ketshop_var_attrib')
	    ->where('prod_id='.$pk)
	    ->order('var_id');
      $db->setQuery($query);
      $varAttribs = $db->loadAssocList();

      $config = JComponentHelper::getParams('com_ketshop');

      //Store the attributes linked to the given variant.
      foreach($variants as $key => $variant) {
	$variants[$key]['attributes'] = array();
	foreach($varAttribs as $varAttrib) {
	  if($varAttrib['var_id'] == $variant['var_id']) {
	    $variants[$key]['attributes'][] = $varAttrib;
	  }
	}

	//Format some numerical values.
	$variants[$key]['weight'] = UtilityHelper::formatNumber($variants[$key]['weight']);
	$variants[$key]['length'] = UtilityHelper::formatNumber($variants[$key]['length']);
	$variants[$key]['width'] = UtilityHelper::formatNumber($variants[$key]['width']);
	$variants[$key]['height'] = UtilityHelper::formatNumber($variants[$key]['height']);
	$variants[$key]['base_price'] = UtilityHelper::formatNumber($variants[$key]['base_price'], $config->get('digits_precision'));
	$variants[$key]['sale_price'] = UtilityHelper::formatNumber($variants[$key]['sale_price'], $config->get('digits_precision'));
      }
    }

    return $variants;
  }


  public function getBundleProducts($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('prod_id AS id, name, quantity, stock') 
	  ->from('#__ketshop_prod_bundle')
	  ->join('INNER', '#__ketshop_product ON id=prod_id')
	  ->where('bundle_id='.$pk)
	  ->order('prod_id');
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  //The aim of this Ajax function is to simulate the checking for an unique alias in the table file. 
  //This avoid the users to loose the attributes and images they've just set in case of
  //error (handle in tables/product.php).
  public function checkAlias($pk = null, $name, $alias) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');
    $return = 1;

    //Create a sanitized alias, (see stringURLSafe function for details).
    $alias = JFilterOutput::stringURLSafe($alias);
    //In case no alias has been defined, create a sanitized alias from the name field.
    if(empty($alias)) {
      $alias = JFilterOutput::stringURLSafe($name);
    }

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Check for unique alias.
    $query->select('COUNT(*)')
	  ->from('#__ketshop_product')
	  ->where('alias='.$db->Quote($alias).' AND id!='.(int)$pk);
    $db->setQuery($query);

    if($db->loadResult()) {
      $return = 0;
    }

    return $return;
  }


  public function getAttributeFields($attributeId) 
  {
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get the fields and their values of the selected attribute.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('field_type_1,value_type,field_value_1,field_text_1,multiselect,field_type_2,field_value_2,field_text_2,published')
	  ->from('#__ketshop_attribute')
	  ->where('id='.(int)$attributeId);
    $db->setQuery($query);
    //Get results as an associative array.
    $attributeFields = $db->loadAssoc();
    //Add empty selected value for each fields as no value has been selected yet.
    $attributeFields['selected_value_1'] = '';
    $attributeFields['selected_value_2'] = '';

    return $attributeFields;
  }
}

