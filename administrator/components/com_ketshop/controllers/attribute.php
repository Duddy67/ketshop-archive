<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerAttribute extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get all of the POST data.
    $post = JFactory::getApplication()->input->post->getArray();
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
    $context = "$this->option.edit.$this->context";
    $groups = false;

    //Detect whether the attribute has been added to a group.
    foreach($post as $key => $val) {
      if(preg_match('#^group_([0-9]+)$#', $key)) {
	$groups = true;
        break;
      }
    }

    //Check whether the attribute can be part of a group.
    if($groups && ($data['field_type_1'] != 'closed_list' || $data['field_type_2'] != 'none' || $data['multiselect'] == 1)) {
      $this->setMessage(JText::_('COM_KETSHOP_ATTRIBUTE_CANNOT_BE_IN_A_GROUP'), 'warning');
      //Save the data in the session (prevent the user to loose his data after redirect).
      JFactory::getApplication()->setUserState($context.'.data', $data);
      $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=attribute'.$this->getRedirectToItemAppend($data['id']), false));
      return false;
    }

    //If field type 1 is set to open_field value and text lists are emptied
    //just in case previous data is still remaining.
    if($data['field_type_1'] == 'open_field') {
      $data['field_value_1'] = '';
      $data['field_text_1'] = '';
      $data['multiselect'] = 0;
    }
    else { //closed_list: Check text and value lists.
      if(!$this->checkClosedListData($data['field_value_1'], $data['field_text_1'], 1)) {
	//Save the data in the session (prevent the user to loose his data after redirect).
	JFactory::getApplication()->setUserState($context.'.data', $data);
	$this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=attribute'.$this->getRedirectToItemAppend($data['id']), false));
	return false;
      }

      //Reset value_type to its default value.
      $data['value_type'] = 'string';
    }

    //If field type 2 is set to none or open_field value and text lists are emptied
    //just in case previous data is still remaining.
    if($data['field_type_2'] == 'none' || $data['field_type_2'] == 'open_field') {
      $data['field_value_2'] = '';
      $data['field_text_2'] = '';
    }
    elseif($data['field_type_2'] == 'closed_list') { //Check text and value lists.
      if(!$this->checkClosedListData($data['field_value_2'], $data['field_text_2'], 2)) {
	//Save the data in the session (prevent the user to loose his data after redirect).
	JFactory::getApplication()->setUserState($context.'.data', $data);
	$this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=attribute'.$this->getRedirectToItemAppend($data['id']), false));
	return false;
      }
    }
    elseif($data['field_type_2'] != 'none') { //Multiselect is not available when field 2 is used.
      $data['multiselect'] = 0;
    }

    //Saves the modified jform data array 
    $this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  public function checkClosedListData($fieldValue, $fieldText, $fieldNb)
  {
    //Check value list.
    if(!preg_match('#^[a-zA-Z0-9_-]+(?:\|[a-zA-Z0-9_-]+)*$#',$fieldValue)) {
      $this->setMessage(JText::_('COM_KETSHOP_VALUE_LIST_NOT_VALID_FIELD_'.$fieldNb), 'warning');
      return false;
    }

    //Check text list.
    //Note: Don't forget the u UTF-8 modifier to match accented characters. 
    if(!preg_match('#^[\p{L}\s0-9_-]+(?:\|[\p{L}\s0-9_-]+)*$#u',$fieldText)) {
      $this->setMessage(JText::_('COM_KETSHOP_TEXT_LIST_NOT_VALID_FIELD_'.$fieldNb), 'warning');
      return false;
    }

    //Turn value and text lists into arrays.
    $values = explode('|', $fieldValue);
    $texts = explode('|', $fieldText);

    //Check texts and values arrays have the same number of elements.
    if(count($values) != count($texts)) {
      $this->setMessage(JText::_('COM_KETSHOP_VALUES_TEXTS_DO_NOT_MATCH_FIELD_'.$fieldNb), 'warning');
      return false;
    }

    return true;
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
	  ->from('#__ketshop_attribute')
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

