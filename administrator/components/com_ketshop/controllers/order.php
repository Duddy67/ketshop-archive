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

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
$post = JFactory::getApplication()->input->post->getArray();
echo '<pre>';
var_dump($post);
echo '</pre>';
//return;

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
    }

    //Reset the jform data array 
    $this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  public function addProduct()
  {
    $prodId = $this->input->get('prod_id', 0, 'uint');
    $orderId = $this->input->get('order_id', 0, 'uint');
    //$this->setError(JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));
    //$this->setMessage($this->getError(), 'error');
    $this->setRedirect('index.php?option=com_ketshop&view=order&layout=edit&id='.$orderId);
    return;
  }


  public function removeProduct()
  {
  }


  public function updateOrder($orderId = 0)
  {
    if(!$orderId) {
      $orderId = $this->input->get('order_id', 0, 'uint');
    }
//file_put_contents('debog_file.txt', print_r($, true)); 
    $this->setRedirect('index.php?option=com_ketshop&view=order&layout=edit&id='.$orderId);
    return;
  }


  private function checkDuplicateProduct($prodId)
  {
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

