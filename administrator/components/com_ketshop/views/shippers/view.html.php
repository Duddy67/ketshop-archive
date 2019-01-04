<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 

class KetshopViewShippers extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  protected $missingPlugins;
  protected $users;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');
    $this->missingPlugins = $this->get('MissingPlugins');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //If one or more plugins are missing we display a warning message.
    if(count($this->missingPlugins)) {
      $pluginNames = '';
      //Get the name of the missing plugin(s).
      foreach($this->missingPlugins as $missingPlugin) {
	$pluginNames .= $missingPlugin.', ';
      }

      //Remove the comma from the end of the string.
      $pluginNames = substr($pluginNames, 0,-2);

      //Display warning message.
      JError::raiseWarning(500, JText::sprintf('COM_KETSHOP_WARNING_MISSING_PLUGINS', $pluginNames));
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
    JToolBarHelper::title(JText::_('COM_KETSHOP_SHIPPERS_TITLE'), 'shop-move-up');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the shipper items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('shipper.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('shipper.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('shippers.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('shippers.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('shippers.archive','JTOOLBAR_ARCHIVE');

      if($canDo->get('core.edit.state')) {
	JToolBarHelper::custom('shippers.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      }

      JToolBarHelper::trash('shippers.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'shippers.delete', 'JTOOLBAR_DELETE');
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


