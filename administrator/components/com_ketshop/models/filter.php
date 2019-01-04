<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2018 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelFilter extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Filter', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.filter', 'filter', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.filter.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  public function getAttributes($pk = null) 
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Gets the group ids linked to the attribute.
    $query->select('fa.attrib_id AS id, a.name')
	  ->from('#__ketshop_filter_attrib AS fa')
	  ->join('LEFT', '#__ketshop_attribute AS a ON a.id=fa.attrib_id')
	  ->where('fa.filter_id='.(int)$pk)
	  ->order('fa.attrib_id');
    $db->setQuery($query);

    return $db->loadAssocList();
  }
}

