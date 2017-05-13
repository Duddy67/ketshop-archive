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
    if(!$this->id) { // New item
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


