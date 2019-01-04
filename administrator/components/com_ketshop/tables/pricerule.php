<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
 
/**
 * Price Rule table class
 */
class KetshopTablePricerule extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_price_rule', 'id', $db);
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

      //Get the number of rows in the table.
      $query = $this->_db->getQuery(true)
              ->select('COUNT(*)')
              ->from('#__ketshop_price_rule');
      $this->_db->setQuery($query);
      $result = $this->_db->loadResult();

      //Increment of 1 the order of the new item.
      $this->ordering = $result + 1;
    }

    return parent::store($updateNulls);
  }
}


