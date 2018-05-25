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
 * Translation table class
 */
class KetshopTableTranslation extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_translation', 'id', $db);
  }

  /**
   * Overloaded bind function
   *
   * @param       array           named array
   * @return      null|string     null is operation was satisfactory, otherwise returns an error
   * @see JTable:bind
   * @since 1.5
   */
  public function bind($array, $ignore = '') 
  {
    if($array['item_type'] == 'product') {
      if(isset($array['metadata']) && is_array($array['metadata'])) {
	// Convert the metadata field to a string.
	$metadata = new JRegistry;
	$metadata->loadArray($array['metadata']);
	$array['metadata'] = (string)$metadata;
      }

      // Search for the {readmore} tag and split the text up accordingly.
      if(isset($array['product_description'])) {
	$pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
	$tagPos = preg_match($pattern, $array['product_description']);

	if($tagPos == 0) {
	  $this->description = $array['product_description'];
	  $this->full_description = '';
	}
	else {
	  //Split description field data in 2 parts with the "readmore" tag as a
	  //separator.
	  //Note: The "readmore" tag is not included in either part.
	  list($this->description, $this->full_description) = preg_split($pattern, $array['product_description'], 2);
	}
      }
      //Empty information field just in case.
      $array['information'] = '';

    } //Empty unused fields, according to the item type, in case they've been previously set. 
    elseif($array['item_type'] == 'delivery_point' || $array['item_type'] == 'shipping' ||
	   $array['item_type'] == 'shipper' || $array['item_type'] == 'price_rule')
    {
      $array['alias'] = '';
      $array['information'] = '';
      $this->full_description = '';
    }
    elseif($array['item_type'] == 'payment_mode') {
      $array['alias'] = '';
      $this->full_description = '';
    }
    else { //Tax and attribute items just need a name field. 
      $array['alias'] = '';
      $array['description'] = '';
      $array['information'] = '';
      $this->full_description = '';
    }

    //All of the other item types don't use these fields.
    if($array['item_type'] != 'product') {
      $array['metadata'] = '';
      $array['metakey'] = '';
      $array['metadesc'] = '';
      $array['xreference'] = '';
    }

    return parent::bind($array, $ignore);
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

    return parent::store($updateNulls);
  }
}


