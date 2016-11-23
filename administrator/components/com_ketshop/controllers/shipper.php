<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerShipper extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //Set some jform fields.
    
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    //Update the modification.
    $data['modified'] = $now;

    if($data['id'] == 0) { //New item
      //A shipment plugin can only be assigned one time.
      $db =& JFactory::getDbo();
      $query = 'SELECT COUNT(*) FROM #__ketshop_shipper '.
	       'WHERE plugin_element='.$db->Quote($data['plugin_element']);
      $db->setQuery($query);
      $count = $db->loadResult();

      //Display a warning message if the plugin is already used.
      if($count) {
	JError::raiseWarning(500, JText::sprintf('COM_JOOSHOP_WARNING_PLUGIN_ALREADY_USED', $data['plugin_element']));
	$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item, false));
	return false;
      }
      else {
	//Set the possible undefined parameters.
	if(empty($data['created'])) {
	  $data['created'] = $now;
	}

	if(empty($data['created_by'])) {
	  //Get the current user id.
	  $user =& JFactory::getUser();
	  $data['created_by'] = $user->id;
	}
      }
    }

    //Reset the jform data array 
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
    $db =& JFactory::getDbo();
    $query = 'SELECT created_by FROM #__ketshop_shipper WHERE id='.$itemId;
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

