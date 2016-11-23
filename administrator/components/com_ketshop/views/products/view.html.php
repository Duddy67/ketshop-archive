<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/ketshop.php';
require_once JPATH_COMPONENT.'/helpers/bundle.php';
 

class KetshopViewProducts extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Get the stock value of the bundle items.
    foreach($this->items as $item) {
      if($item->type == 'bundle') {
	$item->stock = BundleHelper::getBundleStock($item->id);
      }
    }

    //Display the tool bar.
    $this->addToolBar();

    $this->setDocument();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_KETSHOP_PRODUCTS_TITLE'), 'shop-star-empty');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();
    $user = JFactory::getUser();

    //The user is allowed to create or is able to create in one of the component categories.
    if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_product', 'core.create'))) > 0) {
      //JToolBarHelper::addNew('product.add', 'JTOOLBAR_NEW');
      JToolbarHelper::addNew('product.normal', 'COM_KETSHOP_PRODUCT_NORMAL_LABEL');
      JToolbarHelper::custom('product.bundle', 'shop-gift', '', 'COM_KETSHOP_PRODUCT_BUNDLE_LABEL', false);
      JToolbarHelper::divider();
    }

    if($canDo->get('core.edit') || $canDo->get('core.edit.own') || 
       (count($user->getAuthorisedCategories('com_product', 'core.edit'))) > 0 || 
       (count($user->getAuthorisedCategories('com_product', 'core.edit.own'))) > 0) {
      JToolBarHelper::editList('product.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('products.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('products.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('products.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('products.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('products.trash','JTOOLBAR_TRASH');
    }

    //Check for delete permission.
    if($canDo->get('core.delete') || count($user->getAuthorisedCategories('com_product', 'core.delete'))) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'products.delete', 'JTOOLBAR_DELETE');
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_ketshop', 550);
    }
  }


  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
  }
}


