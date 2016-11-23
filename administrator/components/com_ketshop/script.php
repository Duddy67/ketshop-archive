<?php
/**
 * @package KetShop 1.x
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access to this file
defined('_JEXEC') or die;
 // import joomla's filesystem classes
jimport('joomla.filesystem.folder');



class com_ketshopInstallerScript
{
  /**
   * method to run before an install/update/uninstall method
   *
   * @return void
   */
  function preflight($type, $parent) 
  {
    $jversion = new JVersion();

    // Installing component manifest file version
    $this->release = $parent->get('manifest')->version;

    // Show the essential information at the install/update back-end
    echo '<p>'.JText::_('COM_KETSHOP_INSTALLING_COMPONENT_VERSION').$this->release;
    echo '<br />'.JText::_('COM_KETSHOP_CURRENT_JOOMLA_VERSION').$jversion->getShortVersion().'</p>';

    //Abort if the component being installed is not newer than the
    //currently installed version.
    if($type == 'update') {
      $oldRelease = $this->getParam('version');
      $rel = ' v-'.$oldRelease.' -> v-'.$this->release;

      if(version_compare($this->release, $oldRelease, 'le')) {
	Jerror::raiseWarning(null, JText::_('COM_KETSHOP_UPDATE_INCORRECT_VERSION').$rel);
	return false;
      }
    }

    if($type == 'install') {
      //
    }
  }


  /**
   * method to install the component
   *
   * @return void
   */
  function install($parent) 
  {
    // Create a category for our component.
    $basePath = JPATH_ADMINISTRATOR.'/components/com_categories';
    require_once $basePath.'/models/category.php';
    $config = array('table_path' => $basePath.'/tables');
    $catModel = new CategoriesModelCategory($config);
    $catData = array('id' => 0, 'parent_id' => 1, 'level' => 1, 'path' => 'ketshop',
		     'extension' => 'com_ketshop', 'title' => 'KetShop',
		     'alias' => 'ketshop', 'description' => '<p>Default category</p>',
		     'published' => 1, 'language' => '*');
    $status = $catModel->save($catData);
 
    if(!$status) {
      JError::raiseWarning(500, JText::_('Unable to create default content category!'));
    }
  }


  /**
   * method to uninstall the component
   *
   * @return void
   */
  function uninstall($parent) 
  {
    //
  }


  /**
   * method to update the component
   *
   * @return void
   */
  function update($parent) 
  {
    //
  }


  /**
   * method to run after an install/update/uninstall method
   *
   * @return void
   */
  function postflight($type, $parent) 
  {
    if($type == 'install') {
      //The component parameters are not inserted into the table until the user open up the Options panel then click on the save button.
      //The workaround is to update manually the extensions table with the parameters just after the component is installed. 
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      //$query->update('#__extensions');
      //$query->set('params='.$db->Quote('{}'));
      //$query->where('element='.$db->Quote('com_ketshop').' AND type='.$db->Quote('component'));
      //$db->setQuery($query);
      //$db->query();

      //In order to use the Joomla's tagging system we have to give to Joomla some
      //informations about the component items we want to tag.
      //Those informations should be inserted into the #__content_types table.

      //Informations about the KetShop product items.
      $columns = array('type_title', 'type_alias', $db->quoteName('table'), 'field_mappings', 'router');
      $query->clear();
      $query->insert('#__content_types');
      $query->columns($columns);
      $query->values($db->Quote('KetShop').','.$db->Quote('com_ketshop.product').','.
$db->Quote('{"special":{"dbtable":"#__ketshop_product","key":"id","type":"Product","prefix":"KetshopTable","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}').','.
$db->Quote('{"common":{"core_content_item_id":"id","core_title":"name","core_state":"published","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"intro_text","core_hits":"hits","core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access","core_params":"null","core_featured":"null","core_metadata":"null","core_language":"language","core_images":"null","core_urls":"null","core_version":"null","core_ordering":"ordering","core_metakey":"null","core_metadesc":"null","core_catid":"catid","core_xreference":"null","asset_id":"asset_id"},"special": {}}').','.
$db->Quote('KetshopHelperRoute::getProductRoute'));
      $db->setQuery($query);
      $db->query();

      //Informations about the KetShop category items.
      $query->clear();
      $query->insert('#__content_types');
      $query->columns($columns);
      $query->values($db->Quote('KetShop Category').','.$db->Quote('com_ketshop.category').','.
$db->Quote('{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"JTable","config":"array()"},"common"{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}').','.
$db->Quote('{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created_time","core_modified_time":"modified_time","core_body":"description","core_hits":"hits","core_publish_up":"null","core_publish_down":"null","core_access":"access","core_params":"params","core_featured":"null","core_metadata":"metadata","core_language":"language","core_images":"null","core_urls":"null","core_version":"version","core_ordering":"null","core_metakey":"metakey","core_metadesc":"metadesc","core_catid":"parent_id","core_xreference":"null","asset_id":"asset_id"},"special":{"parent_id":"parent_id","lft":"lft","rgt":"rgt","level":"level","path":"path","extension":"extension","note":"note"}}').','.
$db->Quote('KetshopHelperRoute::getCategoryRoute'));
      $db->setQuery($query);
      $db->query();
    }
  }


  /*
   * get a variable from the manifest file (actually, from the manifest cache).
   */
  function getParam($name)
  {
    $db = JFactory::getDbo();
    $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = "ketshop"');
    $manifest = json_decode($db->loadResult(), true);

    return $manifest[$name];
  }
}

