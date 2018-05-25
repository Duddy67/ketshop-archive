<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
/**
 * Attribute table class
 */
class KetshopTableAttribute extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_attribute', 'id', $db);
  }


  /**
   * Overrides JTable::store to set modified data and user id.
   *
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   11.1
   */
  public function store($updateNulls = false)
  {
    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();

    if($this->id) { // Existing item
      $this->modified = $now;
      $this->modified_by = $user->get('id');
    }
    else {
      // New item. An item created and created_by field can be set by the user,
      // so we don't touch either of these if they are set.
      if(!(int)$this->created) {
	$this->created = $now;
      }

      if(empty($this->created_by)) {
	$this->created_by = $user->get('id');
      }
    }

    //Load a predefined set of unit measurement data from the database. 
    if($this->field_type_2 != 'none' && $this->field_type_2 != 'open_field' && $this->field_type_2 != 'closed_list') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('name,symbol,code')
	    ->from('#__ketshop_unit_measurement')
	    ->where('measure_type='.$db->Quote($this->field_type_2));
      $db->setQuery($query);
      $measurement = $db->loadObject();

      //Set the unit measurement data.
      $this->field_value_2 = $measurement->code;
      $this->field_text_2 = $measurement->symbol;
    }

    return parent::store($updateNulls);
  }
}


