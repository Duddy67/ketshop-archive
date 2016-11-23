<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_ketshop/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variables.
$shippingId = JFactory::getApplication()->input->get->get('shipping_id', 0, 'uint');
$itemType = JFactory::getApplication()->input->get->get('item_type', '', 'string');

$db = JFactory::getDbo();

if($itemType == 'deliverypoint') {
  //Get the address of the delivery point.
  $query = 'SELECT street, city, postcode, region_code, country_code, phone FROM #__ketshop_address '.
	   'WHERE item_id='.$shippingId.' AND item_type="delivery_point"';
  $db->setQuery($query);
  //Get results as an associative array.
  $address = $db->loadAssoc();

  echo json_encode($address);
}
else { //at_destination

  //Get data of each item type.
  $data = array();
  $query = 'SELECT `from`,`to`,cost FROM #__ketshop_ship_postcode WHERE shipping_id='.$shippingId;
  $db->setQuery($query);
  //Get results as a list of associative arrays and put them into the data array.
  $data['postcode'] = $db->loadAssocList();

  $query = 'SELECT name,cost FROM #__ketshop_ship_city WHERE shipping_id='.$shippingId;
  $db->setQuery($query);
  $data['city'] = $db->loadAssocList();

  $query = 'SELECT code,cost FROM #__ketshop_ship_region WHERE shipping_id='.$shippingId;
  $db->setQuery($query);
  $data['region'] = $db->loadAssocList();

  $query = 'SELECT code,cost FROM #__ketshop_ship_country WHERE shipping_id='.$shippingId;
  $db->setQuery($query);
  $data['country'] = $db->loadAssocList();

  $query = 'SELECT code,cost FROM #__ketshop_ship_continent WHERE shipping_id='.$shippingId;
  $db->setQuery($query);
  $data['continent'] = $db->loadAssocList();

  echo json_encode($data);
}

