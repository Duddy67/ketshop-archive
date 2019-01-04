<?php
/**
 * @package KetShop 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 

class KetshopViewKetshop extends JViewLegacy
{
  //Display the view.
  public function display($tpl = null)
  {
    //Display the tool bar.
    $this->addToolBar();

    //Display the top left title in the browser.
    $this->setDocument();
    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_KETSHOP_KETSHOP_TITLE'), 'shop-home');

    //Get the allowed actions list
    $canDo = KetshopHelper::getActions();
    $user = JFactory::getUser();

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_ketshop', 550);
    }
  }


  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
    //$document->setTitle(JText::_('COM_KETSHOP_ADMINISTRATION_ATTRIBUTES'));
  }
}


