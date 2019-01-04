<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 

class KetshopViewOrders extends JViewLegacy
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

    //Display the tool bar.
    $this->addToolBar();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);

    $this->setDocument();
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_KETSHOP_ORDERS_TITLE'), 'shop-cart');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('order.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('orders.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('orders.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('orders.archive','JTOOLBAR_ARCHIVE');

      if($canDo->get('core.edit.state')) {
	JToolBarHelper::custom('orders.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      }

      JToolBarHelper::trash('orders.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'orders.delete', 'JTOOLBAR_DELETE');
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


