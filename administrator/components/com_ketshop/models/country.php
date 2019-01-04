<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelCountry extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Country', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.country', 'country', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_ketshop.edit.country.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }
}

