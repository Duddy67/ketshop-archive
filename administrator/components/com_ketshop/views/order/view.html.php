<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/ketshop.php';
require_once JPATH_COMPONENT.'/helpers/utility.php';
require_once JPATH_COMPONENT_SITE.'/helpers/route.php';
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
 

class KetshopViewOrder extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;
  protected $products;
  protected $transaction;
  protected $delivery;
  protected $priceRules;
  protected $billingAddress;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    //Get products from the cart controller function.
    $this->products = ShopHelper::callControllerFunction('cart', 'getProductsFromOrder', array($this->item->id));
    $this->priceRules = $this->get('PriceRules');
    $this->billingAddress = $this->get('BillingAddress');

    if($this->item->cart_status == 'completed') {
      $this->transaction = $this->get('Transaction');
      $this->delivery = $this->get('Delivery');
      //Needed for the layouts.
      $this->transaction->currency = $this->item->currency;
      $this->delivery->currency = $this->item->currency;
    }

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Prepare price rules (if any) for each product.
    foreach($this->products as $key => $product) {
      $this->products[$key]['pricerules'] = array();

      $slug = $product['id'].':'.$product['alias'];
      //Build the link leading to the product page.
      $url = JRoute::_(KetshopHelperRoute::getProductRoute($slug, (int)$product['catid']));
      //
      $url = preg_replace('#administrator/#', '', $url);
      //Make the link safe.
      $url = addslashes($url);
      $this->products[$key]['url'] = $url;

      foreach($this->priceRules as $priceRule) {
	if($product['id'] == $priceRule['prod_id']) {
	  $this->products[$key]['pricerules'][] = $priceRule;
	}
      }
    }

    //Prepare price rules (if any).
    $amountPriceRules = array();
    foreach($this->priceRules as $priceRule) {
      $amountPriceRules[] = $priceRule;
    }

    //Display the toolbar.
    $this->addToolBar();

    $this->setDocument();

    $this->assignRef('products', $this->products);
    $this->assignRef('amountPriceRules',$amountPriceRules);
    $this->assignRef('transaction', $this->transaction);
    $this->assignRef('delivery', $this->delivery);
    $this->assignRef('billingAddress', $this->billingAddress);
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
    JToolBarHelper::title($isNew ? JText::_('COM_KETSHOP_NEW_ORDER') :
	JText::_('COM_KETSHOP_EDIT_ORDER'), 'pencil-2');

    // Can't save the record if it's checked out.
    if(!$checkedOut) {
      // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
      if($canDo->get('core.edit')) {
	// We can save the new record
	JToolBarHelper::apply('order.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('order.save', 'JTOOLBAR_SAVE');
      }
    }

    JToolBarHelper::cancel('order.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include css and js files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/ketshop.css');
    $doc->addStyleSheet(JURI::root().'components/com_ketshop/css/ketshop.css');
  }
}



