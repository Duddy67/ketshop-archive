<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/ketshop.php';
 
use Joomla\Registry\Registry;

/**
 * Product table class
 */
class KetshopTableProduct extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__ketshop_product', 'id', $db);
    //Needed to use the Joomla tagging system with the product items.
    JTableObserverTags::createObserver($this, array('typeAlias' => 'com_ketshop.product'));
  }


  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   mixed  $array   An associative array or object to bind to the JTable instance.
   * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
   *
   * @return  boolean  True on success.
   *
   * @see     JTable:bind
   * @since   1.5
   */
  public function bind($array, $ignore = '')
  {
    if(isset($array['params']) && is_array($array['params'])) {
      // Convert the params field to a string.
      $registry = new JRegistry;
      $registry->loadArray($array['params']);
      $array['params'] = (string) $registry;
    }

    if(isset($array['metadata']) && is_array($array['metadata'])) {
      $registry = new JRegistry;
      $registry->loadArray($array['metadata']);
      $array['metadata'] = (string) $registry;
    }

    // Search for the {readmore} tag and split the text up accordingly.
    if(isset($array['producttext'])) {
      $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
      $tagPos = preg_match($pattern, $array['producttext']);

      if($tagPos == 0) {
	$this->intro_text = $array['producttext'];
	$this->full_text = '';
      }
      else {
	//Split producttext field data in 2 parts with the "readmore" tag as a separator.
	//Note: The "readmore" tag is not included in either part.
	list($this->intro_text, $this->full_text) = preg_split($pattern, $array['producttext'], 2);
      }
    }

    // Bind the rules. 
    if(isset($array['rules']) && is_array($array['rules'])) {
      $rules = new JAccessRules($array['rules']);
      $this->setRules($rules);
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

      //Check if "new until" date is still valid. If it's not, reinitialize date to zero.
      if($this->new_until < $now) {
	$this->new_until = '0000-00-00 00:00:00';
      }
    }
    else {
      // New product. A product created and created_by field can be set by the user,
      // so we don't touch either of these if they are set.
      if(!(int)$this->created) {
	$this->created = $now;
      }

      if(empty($this->created_by)) {
	$this->created_by = $user->get('id');
      }
    }

    //Set the alias of the product.
    
    //Create a sanitized alias, (see stringURLSafe function for details).
    $this->alias = JFilterOutput::stringURLSafe($this->alias);
    //In case no alias has been defined, create a sanitized alias from the name field.
    if(empty($this->alias)) {
      $this->alias = JFilterOutput::stringURLSafe($this->name);
    }

    // Verify that the alias is unique.
    $table = JTable::getInstance('Product', 'KetshopTable', array('dbo', $this->getDbo()));

    if($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_KETSHOP_DATABASE_ERROR_PRODUCT_UNIQUE_ALIAS'));
      return false;
    }

    //Check we have tags before setting the main tag id.
    if(isset($this->newTags)) {
      //Creating tags on the fly is not allowed in our component.
      KetshopHelper::removeTagsOnTheFly($this->newTags);
      $this->newTags = $this->cleanNewTags($this->newTags);

      if(!empty($this->newTags)) {
	//Check that the selected main tag is still part of the current tags.
	if(!in_array($this->main_tag_id, $this->newTags)) {
	  //By default set the first current tag as the main tag.
	  $this->main_tag_id = reset($this->newTags);
	}
      }
    }
    else {
      $this->main_tag_id = 0;
    }

    return parent::store($updateNulls);
  }


  /** 
   * Checks that meanwhile none of the new selected tags has been archived or trashed from
   * the com_tags component. If so, the tag is removed from the tag array.
   *
   * @param   mixed  $array   An array filled with the new tag ids or null if the array is empty.
   *
   * @return  mixed           The cleaned tag array or null if the array is empty.
   */
  protected function cleanNewTags($newTags)
  {
    if($newTags === null) {
      return $newTags;
    }   

    $query = $this->_db->getQuery(true)
            ->select($this->_db->quoteName('id'))
            ->from($this->_db->quoteName('#__tags'))
            ->where($this->_db->quoteName('published').' NOT IN(2, -2)')
            ->where($this->_db->quoteName('id').' IN('.implode(',', $newTags).')');
    $this->_db->setQuery($query);
    $tagIds = $this->_db->loadColumn();

    foreach($newTags as $key => $tagId) {
      if(!in_array($tagId, $tagIds)) {
        //Remove the new tag from the tag data.
        unset($newTags[$key]);
      }   
    }   

    //Don't return an empty array. Return null instead.
    if(empty($newTags)) {
      return null;
    }   

    return $newTags;
  }


  /**
   * Method to return the name to use for the asset table.
   *
   * @return  string
   *
   * @since   11.1
   */
  protected function _getAssetTitle()
  {
    return $this->name;
  }


  /**
   * Method to compute the default name of the asset.
   * The default name is in the form table_name.id
   * where id is the value of the primary key of the table.
   *
   * @return  string
   *
   * @since   11.1
   */
  protected function _getAssetName()
  {
    $k = $this->_tbl_key;
    return 'com_ketshop.product.'.(int) $this->$k;
  }


  /**
   * We provide our global ACL as parent
   * @see JTable::_getAssetParentId()
   */

  //Note: The component categories ACL override the items ACL, (whenever the ACL of a
  //      category is modified, changes are spread into the items ACL).
  //      This is the default com_content behavior. see: libraries/legacy/table/content.php
  protected function _getAssetParentId(JTable $table = null, $id = null)
  {
    $assetId = null;

    // This is a product under a category.
    if($this->catid) {
      // Build the query to get the asset id for the parent category.
      $query = $this->_db->getQuery(true)
              ->select($this->_db->quoteName('asset_id'))
              ->from($this->_db->quoteName('#__categories'))
              ->where($this->_db->quoteName('id').' = '.(int) $this->catid);

      // Get the asset id from the database.
      $this->_db->setQuery($query);

      if($result = $this->_db->loadResult()) {
        $assetId = (int) $result;
      }
    }

    // Return the asset id.
    if($assetId) {
      return $assetId;
    }
    else {
      return parent::_getAssetParentId($table, $id);
    }
  }
}


