<?php
/**
 * @package Ketshop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
 
/**
 * Coupon table class
 */
class KetshopTableCoupon extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_coupon', 'id', $db);
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
    //Check again some values in case Javascript has failed.
    if(empty($this->prule_id)) {
      $this->setError(JText::_('COM_KETSHOP_ERROR_NO_PRICERULE_SELECTED'));
      return false;
    }

    if(!preg_match('#^[a-zA-Z0-9-_]{5,}$#', $this->code)) {
      $this->setError(JText::_('COM_KETSHOP_ERROR_COUPON_CODE_NOT_VALID'));
      return false;
    }

    //Verify that the code is unique
    $table = JTable::getInstance('Coupon', 'KetshopTable', array('dbo', $this->getDbo()));

    if($table->load(array('code' => $this->code)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_KETSHOP_DATABASE_ERROR_COUPON_UNIQUE_CODE'));
      return false;
    }

    //Verify that the price rule is not used elsewhere.
    if($table->load(array('prule_id' => $this->prule_id)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_KETSHOP_DATABASE_ERROR_PRICERULE_ALREADY_USED'));
      return false;
    }

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

    return parent::store($updateNulls);
  }
}


