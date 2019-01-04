<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerPaymentmode extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //Set some jform fields.
    
    if($data['id'] == 0 && $data['plugin_element'] != 'offline') { //New item
      //Note: Only offline plugin can be assigned to several modes.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('COUNT(*)')
	    ->from('#__ketshop_payment_mode')
	    ->where('plugin_element='.$db->Quote($data['plugin_element']));
      $db->setQuery($query);
      $count = $db->loadResult();

      if($count) {
	JError::raiseWarning(500, JText::sprintf('COM_JOOSHOP_WARNING_PLUGIN_ALREADY_USED', $data['plugin_element']));
	$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item, false));
	return false;
      }
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
	  ->from('#__ketshop_payment_mode')
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

