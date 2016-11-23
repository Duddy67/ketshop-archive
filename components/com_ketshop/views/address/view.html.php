<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/javascript.php';


class KetshopViewAddress extends JViewLegacy
{
  protected $form = null;


  function display($tpl = null)
  {
    // Initialise variables
    $form = $this->get('Form');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
	    JError::raiseWarning(500, implode("\n", $errors));
	    return false;
    }

    //Load Javascript functions.
    JavascriptHelper::loadFunctions(array('region'));
    JavascriptHelper::getCommonText();

    //$this->setDocument();

    $this->assignRef('form',$form);

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include the css file.
    //$doc = JFactory::getDocument();
    //$doc->addScript(JURI::root().'components/com_ketshop/js/setregions.js');
  }

}
