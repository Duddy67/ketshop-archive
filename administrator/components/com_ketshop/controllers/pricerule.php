<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerPricerule extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //Remove the unwanted field from jform according to the rule type selected.
    //In this way the unused fields will be NULL in the database table.
    //We also set the show_rule value according to the chosen options.
    if($data['type'] == 'catalog') {
      unset($data['condition']);
      unset($data['logical_opr']);

      //Price rules based on profit margin cannot be shown.
      if($data['modifier'] == 'profit_margin_modifier') {
	$data['show_rule'] = 0;
      }

      //Reset children_cat value just in case. 
      $data['children_cat'] = 0;
    }
    else { // cart
      unset($data['modifier']);

      //Cart rules with cart amount target cannot be hidden.
      if($data['target'] == 'cart_amount') {
	$data['show_rule'] = 1;
      }

      //Reset children_cat value just in case. 
      if($data['condition'] != 'product_cat' && $data['condition'] != 'product_cat_amount') {
	$data['children_cat'] = 0;
      }
    }

    //Set some jform fields.
    
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    //Update the modification.
    $data['modified'] = $now;

    if($data['id'] == 0) { //New item
      //Set the possible undefined parameters.
      if(empty($data['created'])) {
	$data['created'] = $now;
      }

      if(empty($data['created_by'])) {
	//Get the current user id.
	$user = JFactory::getUser();
	$data['created_by'] = $user->id;
      }

      if($data['publish_up'] == '') {
	$data['publish_up'] =  $now;
      }
    }

    //Update the jform data array 
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
	  ->from('#__ketshop_price_rule')
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

