<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT.'/helpers/ketshop.php';
 

class KetshopControllerProduct extends JControllerForm
{
  //functions which set the product type.
  public function normal()
  {
    $this->input->get->set('type', 'normal');
    $this->add();
    return;
  }

  public function bundle()
  {
    $this->input->get->set('type', 'bundle');
    $this->add();
    return;
  }


  //Overrided function.
  public function add()
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $context = "$this->option.edit.$this->context";

    //Override: Get the product type.
    $type = $this->input->get->get('type', '', 'string');

    // Access check.
    if (!$this->allowAdd()) {
      // Set the internal error and also the redirect error.
      $this->setError(JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'));
      $this->setMessage($this->getError(), 'error');

      $this->setRedirect( JRoute::_( 'index.php?option='.$this->option.'&view='.$this->view_list
		      .$this->getRedirectToListAppend(), false));

      return false;
    }

    // Clear the record edit information from the session.
    $app->setUserState($context . '.data', null);

    // Redirect to the edit screen.
    //Override: Place the product type variable into the url.
    $this->setRedirect( JRoute::_( 'index.php?option=' . $this->option . '&view=' . $this->view_item
		    .'&type='.$type. $this->getRedirectToItemAppend(), false));

    return true;
  }


  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
    $recordId = $this->input->getInt($urlVar);

    // Populate the row id from the session.
    $data[$key] = $recordId;

    if(!KetshopHelper::checkProductOptions()) {
      $this->setMessage(JText::_('COM_KETSHOP_OPTION_ATTRIBUTES_MISSING'), 'warning');
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item.$this->getRedirectToItemAppend($recordId, $urlVar), false));
      return false;
    }
var_dump($data);
//return;

    //Get current date and time (equal to NOW() in SQL).
    //$now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    //Reset the jform data array 
    //$this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key, $urlVar);
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
	  ->from('#__ketshop_product')
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


  /**
   * Gets the URL arguments to append to an item redirect.
   *
   * @param   integer  $recordId  The primary key id for the item.
   * @param   string   $urlVar    The name of the URL variable for the id.
   *
   * @return  string  The arguments to append to the redirect URL.
   *
   * @since   12.2
   */
  protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
  {
    $tmpl = $this->input->get('tmpl');
    $layout = $this->input->get('layout', 'edit', 'string');
    $append = '';

    // Setup redirect info.
    if($tmpl) {
      $append .= '&tmpl=' . $tmpl;
    }

    if($layout) {
      $append .= '&layout=' . $layout;
    }

    if($recordId) {
      $append .= '&' . $urlVar . '=' . $recordId;
    }
    //Override: In case of error while saving a new item, the type value must be set and added to the url.
    else {
      $data = $this->input->post->get('jform', array(), 'array');
      if(!empty($data) && isset($data['type'])) {
	$append .= '&type='.$data['type'];
      }
    }

    return $append;
  }
}

