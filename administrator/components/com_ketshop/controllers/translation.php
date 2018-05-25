<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerTranslation extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Gets the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //Set some jform fields.

    if($data['item_type'] == 'product') {
      //product_description tag is used with product item instead of description
      //tag, so we must delete description tag to avoid having an empty
      //description field in database.
      unset($data['description']);

      //Use the product sanitized name for the alias, (see stringURLSafe function for details).
      $data['alias'] = JFilterOutput::stringURLSafe($data['name']);
    }

    //Saves the modified jform data array 
    $this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  //Overrided function.
  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    //Get the item owner id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('created_by') 
          ->from('#__ketshop_translation') 
	  ->where('id='.(int)$itemId);
    $db->setQuery($query);
    $createdBy = $db->loadResult();

    $canEdit = $user->authorise('core.edit', 'com_ketshop');
    $canEditOwn = $user->authorise('core.edit.own', 'com_ketshop') && $createdBy == $user->id;

    //Allow edition. 
    if($canEdit || $canEditOwn) {
      return 1;
    }

    //Hand over to the parent function.
    return parent::allowEdit($data = array(), $key = 'id');
  }
}

