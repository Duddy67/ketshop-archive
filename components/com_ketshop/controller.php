<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.

jimport('joomla.application.component.controller');


class KetshopController extends JControllerLegacy
{
  /**
   * Constructor.
   *
   * @param   array  $config  An optional associative array of configuration settings.
   * Recognized key values include 'name', 'default_task', 'model_path', and
   * 'view_path' (this list is not meant to be comprehensive).
   *
   * @since   12.2
   */
  public function __construct($config = array())
  {
    $this->input = JFactory::getApplication()->input;

    //Product frontpage Editor product proxying:
    if($this->input->get('view') === 'products' && $this->input->get('layout') === 'modal') {
      JHtml::_('stylesheet', 'system/adminlist.css', array(), true);
      $config['base_path'] = JPATH_COMPONENT_ADMINISTRATOR;
    }

    parent::__construct($config);
  }


  public function display($cachable = false, $urlparams = false) 
  {

    // Set the default view name and format from the Request.
    // Note we are using p_id to avoid collisions with the router and the return page.
    // Frontend is a bit messier than the backend.
    $id = $this->input->getInt('p_id');
    //Set the view, (categories by default).
    $vName = $this->input->getCmd('view', 'categories');
    $this->input->set('view', $vName);

    // Check for edit form.
    if($vName == 'form' && !$this->checkEditId('com_ketshop.edit.product', $id)) {
      // Somehow the person just went to the form - we don't allow that.
      return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
    }

    //Make sure the parameters passed in the input by the component are safe.
    $safeurlparams = array('catid' => 'INT', 'id' => 'INT',
			    'cid' => 'ARRAY', 'limit' => 'UINT',
			    'limitstart' => 'UINT', 'return' => 'BASE64',
			    'filter' => 'STRING', 'filter-search' => 'STRING',
			    'filter-ordering' => 'STRING', 'lang' => 'CMD',
			    'Itemid' => 'INT');

    //Ensure the no logged-in users cannot access those views.
    if($vName == 'address' || $vName == 'shipment' || $vName == 'payment' || 
       $vName == 'orders' || $vName == 'order' || $vName == 'summary') {
      // If the user is a guest, redirect to the login page.
      $user = JFactory::getUser();
      if($user->get('guest') == 1) {
	// Redirect to login page.
	$this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));
	return;
      }
    }

    //Display the view.
    parent::display($cachable, $safeurlparams);
  }
}


