<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access


class KetshopViewProduct extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;
  protected $config;

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

    $this->config = JComponentHelper::getParams('com_ketshop');

    //Load Javascript functions.
    //JavascriptHelper::getProductText();
    //JavascriptHelper::getCommonText();
    JavascriptHelper::loadFieldLabels();
    //JavascriptHelper::loadFunctions(array('user', 'shortcut', 'product_attributes', 'attribute_options'));
    JavascriptHelper::loadFunctions(array('user', 'shortcut', 'attribute_options'));

    //New item.
    if($this->form->getValue('id') == 0) {
      //Get the product type value passed in GET url. 
      $type = JFactory::getApplication()->input->get->get('type', '', 'string');
      //Set the type of the product.
      $this->form->setValue('type', null, $type);
    }
    else { //Existing item.
      //Set the digits format.
      $digits = $this->config->get('digits_precision');
      $this->form->setValue('base_price', null, UtilityHelper::formatNumber($this->item->base_price, $digits));
      $this->form->setValue('sale_price', null, UtilityHelper::formatNumber($this->item->sale_price, $digits));
      $this->form->setValue('weight', null, UtilityHelper::formatNumber($this->item->weight, $digits));
      $this->form->setValue('length', null, UtilityHelper::formatNumber($this->item->length, $digits));
      $this->form->setValue('width', null, UtilityHelper::formatNumber($this->item->width, $digits));
      $this->form->setValue('height', null, UtilityHelper::formatNumber($this->item->height, $digits));
    }

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
    JToolBarHelper::title($isNew ? JText::_('COM_KETSHOP_NEW_PRODUCT') : JText::_('COM_KETSHOP_EDIT_PRODUCT'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('product.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('product.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('product.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('product.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('product.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_ketshop', 'core.create'))) > 0) {
	    JToolBarHelper::custom('product.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }

      // If checked out, we can still save
      if($canDo->get('core.create')) {
	//JToolBarHelper::save2copy('product.save2copy');
      }
    }

    JToolBarHelper::cancel('product.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include css and javascript files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
    $doc->addScript(JURI::base().'components/com_ketshop/js/check.js');
  }
}



