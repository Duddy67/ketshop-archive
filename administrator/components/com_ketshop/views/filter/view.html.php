<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2018 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 

class KetshopViewFilter extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage($errors, 'error');
      return false;
    }

    JavascriptHelper::getProductText();
    JavascriptHelper::getButtonText();

    //Display the toolbar.
    $this->addToolBar();

    $this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JFactory::getApplication()->input->set('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions($this->state->get('filter.category_id'));
    $isNew = $this->item->id == 0;
    $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

    //Display the view title (according to the user action) and the icon.
    JToolBarHelper::title($isNew ? JText::_('COM_KETSHOP_NEW_FILTER') : JText::_('COM_KETSHOP_EDIT_FILTER'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('filter.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('filter.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('filter.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('filter.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('filter.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_ketshop', 'core.create'))) > 0) {
	    JToolBarHelper::custom('filter.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }
    }

    JToolBarHelper::cancel('filter.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include the css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
  }
}



