<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 

class KetshopViewShippings extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  protected $users;
  protected $config;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->users = $this->get('Users');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    $this->config = JComponentHelper::getParams('com_ketshop');

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
    JToolBarHelper::title(JText::_('COM_KETSHOP_SHIPPINGS_TITLE'), 'shop-truck');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the shipping items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('shipping.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('shipping.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('shippings.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('shippings.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('shippings.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('shippings.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('shippings.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'shippings.delete', 'JTOOLBAR_DELETE');
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


