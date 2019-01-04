<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
 

class KetshopControllerShipping extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //Set some jform fields.
    
    //Weight and cost values are set to 2 digits.
    $data['min_weight'] = UtilityHelper::formatNumber($data['min_weight']);
    $data['max_weight'] = UtilityHelper::formatNumber($data['max_weight']);
    $data['delivpnt_cost'] = UtilityHelper::formatNumber($data['delivpnt_cost']);
    $data['global_cost'] = UtilityHelper::formatNumber($data['global_cost']);

    if($data['id']) { //Existing item
      if($data['delivery_type'] == 'at_destination') {
	//It's safe to set unused field to zero.
	$data['delivpnt_cost'] = 0;
      }
      else { //at_delivery_point
	//It's safe to set unused field to zero.
	$data['global_cost'] = 0;
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
          ->from('#__ketshop_shipping') 
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

