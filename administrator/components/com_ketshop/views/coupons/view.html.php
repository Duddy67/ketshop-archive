<?php
/**
 * @package Ketshop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/ketshop.php';
require_once JPATH_COMPONENT.'/helpers/utility.php';
 

class KetshopViewCoupons extends JViewLegacy
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

    $this->setDocument();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_KETSHOP_COUPONS_TITLE'), 'barcode');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the coupon items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('coupon.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('coupon.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('coupons.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('coupons.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('coupons.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('coupons.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('coupons.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'coupons.delete', 'JTOOLBAR_DELETE');
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


