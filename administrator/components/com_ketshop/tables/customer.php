<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
 
/**
 * Customer table class
 */
class KetshopTableCustomer extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_customer', 'id', $db);
  }
}



