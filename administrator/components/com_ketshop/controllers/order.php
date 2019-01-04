<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerOrder extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    //$data = $this->input->post->get('jform', array(), 'array');

    //Saves the modified jform data array 
    //$this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    //Get the item owner id.
    /*$db =& JFactory::getDbo();
    $query = 'SELECT created_by FROM #__ketshop_order WHERE id='.$itemId;
    $db->setQuery($query);
    $createdBy = $db->loadResult();*/

    $canEdit = $user->authorise('core.edit', 'com_ketshop');
    //$canEditOwn = $user->authorise('core.edit.own', 'com_ketshop') && $createdBy == $user->id;

    //Allow edition. 
    if($canEdit || $canEditOwn) {
      return 1;
    }

    //Hand over to the parent function.
    return parent::allowEdit($data = array(), $key = 'id');
  }
}

