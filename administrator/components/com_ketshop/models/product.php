<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/ketshop.php';


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
	return JError::raiseWarning(500, JText::_($this->text_prefix.'_ERROR_NO_ITEMS_SELECTED'));
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
}

