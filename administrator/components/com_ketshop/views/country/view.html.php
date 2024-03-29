<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
JLoader::register('InilangTrait', JPATH_ADMINISTRATOR.'/components/com_ketshop/traits/inilang.php');


class KetshopViewCountry extends JViewLegacy
{
  use InilangTrait;

  protected $item;
  protected $form;
  protected $state;
  public $countryName;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    //Gets the column name to use for the country name according to the current language.
    $this->countryName = $this->getColumnName('country');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Display the toolbar.
    $this->addToolBar();

    //Display the template.
    parent::display($tpl);

    $this->setDocument();
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JFactory::getApplication()->input->set('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();
    $isNew = $this->item->id == 0;
    $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

    //Display the view title (according to the user action) and the icon.
    JToolBarHelper::title($isNew ? JText::_('COM_KETSHOP_NEW_COUNTRY') : JText::_('COM_KETSHOP_EDIT_COUNTRY'), 'shop-flag');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('country.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('country.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('country.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('country.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('country.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create')) {
	    JToolBarHelper::custom('country.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }

      // If checked out, we can still save
      if($canDo->get('core.create')) {
	JToolBarHelper::save2copy('country.save2copy');
      }
    }

    JToolBarHelper::cancel('country.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include the css and Javascript files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
    $doc->addScript(JURI::base().'components/com_ketshop/js/check.js');
  }
}



