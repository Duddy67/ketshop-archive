<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_ADMINISTRATOR.'/components/com_ketshop/helpers/utility.php';
 


class KetshopControllerAddress extends JControllerForm
{
  //Used as first argument of the logEvent function.
  protected $codeLocation = 'controllers/address.php';


  public function updateAddress()
  {
    //Get the data address from the form.
    $data = $this->input->post->get('ketshopprofile', array(), 'array');
    //Get the data user.
    $user = JFactory::getUser();
    //Check if the cart is shippable or not.
    $shippable = ShopHelper::isShippable();
    //All address fields must be passed to the getAddressQuery function.
    $fields = array('street','city','postcode','region_code','country_code','phone','note');

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Shipping address is used only if cart is shippable.
    if($shippable) {
      //Check all address fields are defined. If some are missing (disabled in the
      //ketshopprofile plugin) we create them and set them to empty.
      foreach($fields as $field) {
	if(!array_key_exists($field.'_sh', $data)) {
	  $data[$field.'_sh'] = '';
	}
      }

      //Get only shipping address.
      $shipping = array('street_sh' => $data['street_sh'],
			'city_sh' => $data['city_sh'],
			'region_code_sh' => $data['region_code_sh'],
			'postcode_sh' => $data['postcode_sh'],
			'country_code_sh' => $data['country_code_sh'],
			'phone_sh' => $data['phone_sh'],
			'note_sh' => $data['note_sh']);

      //Get the proper query to use for this address. 
      $addressQuery = UtilityHelper::getAddressQuery($shipping, 'shipping', 'customer', $user->id);

      //Note: query object is not used here.
      $db->setQuery($addressQuery);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    //Do the same for the user billing address.

    //Check for possible missing fields.
    foreach($fields as $field) {
      if(!array_key_exists($field.'_bi', $data)) {
	$data[$field.'_bi'] = '';
      }
    }

    //Get only billing address.
    $billing = array('street_bi' => $data['street_bi'],
		     'city_bi' => $data['city_bi'],
		     'region_code_bi' => $data['region_code_bi'],
		     'postcode_bi' => $data['postcode_bi'],
		     'country_code_bi' => $data['country_code_bi'],
		     'phone_bi' => $data['phone_bi'],
		     'note_bi' => $data['note_bi']);

    //Get the proper query to use for this address. 
    $addressQuery = UtilityHelper::getAddressQuery($billing, 'billing', 'customer', $user->id);

    //Note: query object is not used here.
    $db->setQuery($addressQuery);
    $db->query();

    //Check for errors.
    if($db->getErrorNum()) {
      ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    //Remove the possible spaces.
    $street = trim($data['street_bi']);
    $city = trim($data['city_bi']);
    $postcode = trim($data['postcode_bi']);

    //Since billing address is optional (most of the time) we need to check
    //street, city, postcode and country fields. If they have been set, billing
    //address is considerate as valid.
    if(!empty($street) && !empty($city) && !empty($postcode) && !empty($data['country_code_bi'])) {
      //Get the id of the last billing address set by the customer. 
      $query->select('id')
	    ->from('#__ketshop_address')
	    ->where('type="billing" AND item_type="customer" AND item_id='.$user->id)
	    ->order('created DESC')
	    ->setLimit(1);
      $db->setQuery($query);
      $billingAddressId = $db->loadResult();

      //Check for errors.
      if($db->getErrorNum()) {
	ShopHelper::logEvent($this->codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    //Store the billing address id in a session variable.
    $session = JFactory::getSession();
    $session->set('billing_address_id', $billingAddressId, 'ketshop'); 

    //Redirect the user depending on whether the cart is shippable or not.
    if($shippable) {
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&task=shipment.setShipment', false));
    }
    else {
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=summary', false));
    }

    return true;
  }
}


