<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerPricerule extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    // Gets the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    // Resets the unwanted field from jform according to the rule type selected.
    // Sets also the show_rule value according to the chosen options.
    if($data['type'] == 'catalog') {
      $data['condition'] = '';
      $data['logical_opr'] = '';
      $data['comparison_opr'] = '';
      $data['condition_qty'] = 0;
      $data['condition_amount'] = 0;

      // Price rules based on profit margin cannot be shown.
      if($data['modifier'] == 'profit_margin_modifier') {
	$data['show_rule'] = 0;
      }
    }
    else { // cart
      $data['modifier'] = '';

      // Cart rules with cart amount target cannot be hidden.
      if($data['target'] == 'cart_amount') {
	$data['show_rule'] = 1;
      }

      // Those conditions are unique so there is no need to use a logical operator.
      if($data['condition'] == 'total_prod_amount' || $data['condition'] == 'total_prod_qty') {
	$data['logical_opr'] = '';
      }
      else {
	// Used only with the "total_prod" conditions.
	$data['comparison_opr'] = '';
	$data['condition_qty'] = 0;
	$data['condition_amount'] = 0;
      }
    }

    // Updates the jform data array 
    $this->input->post->set('jform', $data);

    // Hands over to the parent function.
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

