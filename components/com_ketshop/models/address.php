<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;


class KetshopModelAddress extends JModelForm
{
  protected $data;
  protected $form;

  //Retrieve form and form data from the ketshopprofile plugin.
  public function getForm($data = array(), $loadData = true) 
  {
    //Define the context to send to the plugin depending on whether the cart is
    //shippable or not.
    $context = 'com_ketshop.address.no_shipping';
    if(ShopHelper::isShippable()) {
      $context = 'com_ketshop.address.shipping';
    }

    //Create a form object initialized with context.
    $this->form = new JForm($context); 
    
    $data = $this->loadFormData();

    // Allow for additional modification of the form, and events to be triggered.
    // We pass the data because plugins may require it.
    $this->preprocessForm($this->form, $data, 'user');
    // Load the data into the form after the plugins have operated.
    $this->form->bind($data);

    return $this->form;
  }


  //Get the form data from the ketshopprofile plugin.
  public function getData()
  {
    $user = JFactory::getUser();
    //Create a data object
    $this->data = new JObject; 
    //then add the user id to it.
    $this->data->id = $user->id;

    //Define the context to send to the plugin depending on whether the cart is
    //shippable or not.
    $context = 'com_ketshop.address.no_shipping';
    if(ShopHelper::isShippable()) {
      $context = 'com_ketshop.address.shipping';
    }

    // Get the dispatcher and load the users plugins.
    $dispatcher	= JDispatcher::getInstance();
    JPluginHelper::importPlugin('user');

    // Trigger the data preparation event.
    $results = $dispatcher->trigger('onContentPrepareData', array($context, $this->data));

    // Check for errors encountered while preparing the data.
    if(count($results) && in_array(false, $results, true)) {
      $this->setError($dispatcher->getError());
      $this->data = false;
    }

    return $this->data;
  }


  /**
   * Overrided method to get the data that should be injected in the form.
   *
   * @return	mixed	The data for the form.
   * @since	1.6
   */
  protected function loadFormData()
  {
    return $this->getData();
  }
}
