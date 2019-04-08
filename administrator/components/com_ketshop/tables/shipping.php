<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
 
/**
 * Shipping table class
 */
class KetshopTableShipping extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_shipping', 'id', $db);
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
    // Gets the current date and time (UTC).
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

    // Weight and cost values are set to 2 digits.
    $this->min_weight = UtilityHelper::formatNumber($this->min_weight);
    $this->max_weight = UtilityHelper::formatNumber($this->max_weight);
    $this->delivpnt_cost = UtilityHelper::formatNumber($this->delivpnt_cost);
    $this->global_cost = UtilityHelper::formatNumber($this->global_cost);

    // It's safer to set unused field to zero.
    if($this->delivery_type == 'at_destination') {
      $this->delivpnt_cost = 0;
    }
    // at_delivery_point
    else { 
      $this->global_cost = 0;
    }

    return parent::store($updateNulls);
  }
}


