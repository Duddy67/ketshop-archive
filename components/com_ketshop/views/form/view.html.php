<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/javascript.php';


class KetshopViewForm extends JViewLegacy
{
  protected $form = null;
  protected $state = null;
  protected $item = null;
  protected $config = null;
  protected $return_page = null;
  protected $isNew = 0;
  protected $location = null;

  function display($tpl = null)
  {
    $user = JFactory::getUser();

    //Redirect unregistered users to the login page.
    if($user->guest) {
      $app = JFactory::getApplication();
      $app->redirect('index.php?option=com_users&view=login'); 
      return true;
    }

    // Initialise variables
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    $this->item = $this->get('Item');
    $this->return_page	= $this->get('ReturnPage');

    //Check if the user is allowed to create a new document.
    if(empty($this->item->id)) {
      $authorised = $user->authorise('core.create', 'com_ketshop') || (count($user->getAuthorisedCategories('com_ketshop', 'core.create')));
      $this->isNew = 1;
    }
    else { //Check if the user is allowed to edit this document. 
      $authorised = $this->item->params->get('access-edit');
    }

    if($authorised !== true) {
      JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
      return false;
    }

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    // Create a shortcut to the parameters.
    $params = &$this->state->params;
    //Get the possible extra class name.
    $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

    $this->params = $params;

    $this->config = JComponentHelper::getParams('com_ketshop');

    // Override global params with document specific params
    $this->params->merge($this->item->params);
    $this->user = $user;

    if($params->get('enable_category') == 1) {
      $this->form->setFieldAttribute('catid', 'default', $params->get('catid', 1));
      $this->form->setFieldAttribute('catid', 'readonly', 'true');
    }

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

    //Load Javascript functions.
    JavascriptHelper::getProductText();
    JavascriptHelper::getCommonText();
    JavascriptHelper::loadFunctions(array('user', 'shortcut', 'attribute_groups'));
    $this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
  }
}
