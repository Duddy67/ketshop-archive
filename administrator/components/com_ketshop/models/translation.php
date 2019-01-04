<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class KetshopModelTranslation extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_KETSHOP';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Translation', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_ketshop.translation', 'translation', array('control' => 'jform', 'load_data' => $loadData));

    if (empty($form)) 
      return false;

    return $form;
  }


  //Overrided function.
  public function getItem($pk = null)
  {
    // Initialise variables.
    $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
    $table = $this->getTable();

    if ($pk > 0)
    {
      // Attempt to load the row.
      $return = $table->load($pk);

      // Check for a table object error.
      if ($return === false && $table->getError())
      {
	$this->setError($table->getError());
	return false;
      }
    }

    // Convert to the JObject before adding other data.
    $properties = $table->getProperties(1);
    $item = JArrayHelper::toObject($properties, 'JObject');

    //Override.
    if($item->item_type == 'product')
    {
      //Build the whole product description with the "readmore" tag as separator.
      $item->product_description = trim($item->full_description) != '' ? $item->description."<hr id=\"system-readmore\" />".
									 $item->full_description : $item->description;

      // Convert the metadata field to an array.
      $registry = new JRegistry;
      $registry->loadString($item->metadata);
      $item->metadata = $registry->toArray();
    }

    return $item;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data =
      JFactory::getApplication()->getUserState('com_ketshop.edit.translation.data', array());

    if(empty($data)) 
      $data = $this->getItem();

    return $data;
  }

}

