<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/ketshop.php';
require_once JPATH_COMPONENT.'/helpers/utility.php';
require_once JPATH_COMPONENT.'/helpers/javascript.php';


class KetshopViewCustomer extends JViewLegacy
{
  protected $item;
  protected $orders;
  protected $form;
  protected $state;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->orders = $this->get('Orders');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Load Javascript functions.
    JavascriptHelper::loadFunctions(array('region', 'country', 'continent'));
    JavascriptHelper::getShippingText();

    //Display the toolbar.
    $this->addToolBar();

    //Load css and script files.
    $this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JRequest::setVar('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions($this->state->get('filter.category_id'));
    $isNew = $this->item->id == 0;

    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_KETSHOP_EDIT_CUSTOMER'), 'shop-user');

    if($canDo->get('core.edit') || (count($user->getAuthorisedCategories('com_ketshop', 'core.edit'))) > 0 || $this->item->created_by == $userId) {
      // We can save the new record
      JToolBarHelper::apply('customer.apply', 'JTOOLBAR_APPLY');
      JToolBarHelper::save('customer.save', 'JTOOLBAR_SAVE');
    }

    JToolBarHelper::cancel('customer.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include the Javascript file and the css file as well.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
  }
}



