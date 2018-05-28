<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelAttribute extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Attribute', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.attribute', 'attribute', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) { 
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.attribute.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }

  //Check if the current attribute is used as a product variant.
  public function isUsed($asVariant = false) 
  {
    $attribute = $this->getItem();

    if($attribute->id) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      $query->select('COUNT(*)');

      if($asVariant) {
	$query->from('#__ketshop_var_attrib');
      }
      else {
	$query->from('#__ketshop_prod_attrib');
      }

      $query->where('attrib_id='.$attribute->id);
      $db->setQuery($query);

      if($db->loadResult()) {
	return true;
      }
    }

    return false;
  }

}

