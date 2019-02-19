<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

/**
 * The folowing functions are used by items which have a multilingual
 * feature managed with the ini language files (eg: country, currency).
 */

trait InilangTrait
{
  /**
   * Returns the name of the column to use with the current language.
   *
   * @param   string   $itemName The name of the item.
   *
   * @return  string   The name of the column to use with the current language.
   */
  public function getColumnName($itemName)
  {
    //Gets the current language tag.
    $langTag = JFactory::getLanguage()->getTag();
    $suffix = preg_replace('#\-#', '_', $langTag);
    //Gets the column names of the table. (note: column names are stored as keys of the array.)
    $db = JFactory::getDbo();
    $columns = $db->getTableColumns('#__ketshop_'.$itemName);

    if(array_key_exists('name_'.$suffix, $columns)) {
      return 'name_'.$suffix;
    }

    //English is the default language.
    return 'name_en_GB';
  }


  /**
   * Checks the available ini language files in the component then updates them
   * accordingly.
   *
   * @param   string   $itemName The name of the item.
   *
   * @return  boolean  True if everything went fine, false otherwise.
   */
  public function updateLanguages($itemName)
  {
    //Gets the names of the folders available in the language directory.
    //Note: Folders are named after the language tags (eg: en-GB, fr-FR etc...).
    $path = JPATH_ADMINISTRATOR.'/components/com_ketshop/language';
    $langTags = scandir($path);
    //Gets the column names of the table. (note: column names are stored as keys of the array.)
    $db = JFactory::getDbo();
    $columns = $db->getTableColumns('#__ketshop_'.$itemName);

    foreach($langTags as $key => $langTag) {
      //Rules out possible files or folders which have nothing to do with language (eg: index.html etc...).
      if(!preg_match('#^[a-z]{2}\-[A-Z]{2}$#', $langTag)) {
	unset($langTags[$key]);
	continue;
      }

      //Gets the ini language file matching the language tag.
      $langFile = parse_ini_file($path.'/'.$langTag.'/'.$langTag.'.com_ketshop.ini', true);
      //Replaces hyphen by underscore as it's unsafe to use hyphen with MySQL column names. 
      $suffix = preg_replace('#\-#', '_', $langTag);

      //Checks if the corresponding column exists in the table.
      if(!array_key_exists('name_'.$suffix, $columns)) {
	//Creates a new column in the table named after the language tag.  
	$query = '';
	$query = 'ALTER TABLE '.$db->quoteName('#__ketshop_'.$itemName).' ADD COLUMN '.$db->quoteName('name_'.$suffix).' VARCHAR(255) AFTER '.$db->quoteName('lang_var');
	$db->setQuery($query);

	try {
	  $db->execute();
	}
	catch(RuntimeException $e) {
	  JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_MESSAGE', $e->getMessage()), 'error');
	  return false;
	}
      }

      //Builds the CASE argument for the query.
      $case = 'name_'.$suffix.' = CASE ';
      //Updates the item name from the ini file.
      foreach($langFile[$itemName] as $langVar => $name) {
	$case .= ' WHEN lang_var='.$db->Quote($langVar).' THEN '.$db->Quote($name);
      }

      $case .= ' END ';

      //Updates the newly created column.
      $query = $db->getQuery(true);
      $query->clear();
      $query->update('#__ketshop_'.$itemName)
	    ->set($case);
      $db->setQuery($query);
      $db->setQuery($query);

      try {
	$db->execute();
      }
      catch(RuntimeException $e) {
	JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_KETSHOP_ERROR_MESSAGE', $e->getMessage()), 'error');
	return false;
      }
    }

    JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_KETSHOP_UPDATED_LANGUAGES', implode(', ', $langTags)), 'message');

    return true;
  }
}

