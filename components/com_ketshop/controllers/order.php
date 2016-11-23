<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class KetshopControllerOrder extends JControllerForm
{
  //Check the edit.own permission for this user.
  protected function allowEdit($data = array(), $key = 'id')
  {
    // Initialise variables.
    $recordId = (int) isset($data[$key]) ? $data[$key] : 0;

    //Since order table has no created_by field (cause orders are created
    //automaticaly), we need to get the user_id field value to check it against
    //the user id.

    //Get the user id.
    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the model.
    $record = $this->getModel()->getItem($recordId);

    if(empty($record)) {
      return false;
    }

    // If the owner matches 'me' then do the test.
    if($record->user_id == $userId) {
      return true;
    }

    //Hand over to the parent function.
    return parent::allowEdit($data = array(), $key = 'id');
  }


  //We need to deactivate the check out/check in procedure when an order is
  //edited in frontend by the customer to prevent rights conflict if the
  //vendor tries to edit the same order at the same time.
  //Since customers are only allowed to edit the "customer note" field, which is
  //the only field the vendor is not allowed to edit, there is very few risks
  //that any order data is overwrited.
  //As the check out/check in procedure is set whithin standard functions like
  //edit or save and since overriding is not enough, we just create our own
  //functions to do the job.

  //Allow the customer to get the edit view without triggered the checkout
  //standard function.
  public function editCustomerNote()
  {
    $orderId = $this->input->get->get('order_id', 0, 'uint');

    //Check for permissions.
    if(!$this->allowEdit(array('id' => $orderId))) {
      $this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
      $this->setMessage($this->getError(), 'error');
      $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=orders', false));
      return false;
    }

    $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=order&order_id='.$orderId, false));
    return true;
  }


  //Allow the customer to save his note without triggered the checkin
  //standard function.
  public function saveCustomerNote()
  {
    $orderId = $this->input->get->get('order_id', 0, 'uint');
    $Itemid = $this->input->get('Itemid', 0, 'uint');

    //Check for permissions.
    if(!$this->allowSave(array('id' => (int)$orderId))) {
      $this->setError(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
      $this->setMessage($this->getError(), 'error');
      $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=orders', false));
      return false;
    }

    //Get the form data and the customer note.
    $data = $this->input->post->get('jform', array(), 'array');
    $customerNote = $data['customer_note'];

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->update('#__ketshop_order')
	  ->set('customer_note='.$db->Quote($customerNote))
	  ->where('id='.(int)$orderId);
    $db->setQuery($query);
    $db->query();

    //Check for errors.
    if($db->getErrorNum()) {
      $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $db->getErrorMsg()));
      $this->setMessage($this->getError(), 'error');
      $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=orders&Itemid='.$Itemid, false));
      return false;
    }

    $this->setMessage(JText::_('JLIB_APPLICATION_SAVE_SUCCESS'));
    $this->setRedirect(JRoute::_('index.php?option=com_ketshop&view=orders&Itemid='.$Itemid, false));
    return true;
  }
}

