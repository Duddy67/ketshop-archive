<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');

JLoader::register('ShopHelper', JPATH_SITE.'/components/com_ketshop/helpers/shop.php');
require_once JPATH_COMPONENT_SITE.'/helpers/pricerule.php';
require_once JPATH_COMPONENT_SITE.'/helpers/measurement.php';


class plgKetshopshipmentShipping extends JPlugin
{
  //Grab the event triggered by the shipment controller.
  public function onKetshopShipping($priceRules, $settings)
  {
    //Grab the user session and get the shippers array previously set by the
    //setShipment controller method.
    $session = JFactory::getSession();
    $shippers = $session->get('shippers', array(), 'ketshop'); 

    //First we check against the published status set for our plugin in the site
    //backend. 
    //Note: We take advantage of the foreach loop to get the id of our plugin/shipper.
    $published = false;
    $shipperId = 0;
    foreach($shippers as $shipper) {
      if($shipper['plugin_element'] == 'shipping' && $shipper['published'] == 1) {
	$published = true;
	break;
      }

      $shipperId++;
    }

    //Make sure there is at least one product in the cart which is shippable.
    if($published && ShopHelper::isShippable()) {
      //Get the weight of all of the products into the cart.
      $totalWeight = MeasurementHelper::getTotalWeight();

      //Get the quantity of all of the products into the cart.
      $totalQuantity = ShopHelper::getTotalQuantity();

      //The translated fields of a shipping.
      $translatedFields = 's.name,s.description,';
      $shipTranslation = null;
      //Check if a translation is needed.
      if(ShopHelper::switchLanguage()) {
	//Get the SQL query parts needed for the translation of the shippings.
	$shipTranslation = ShopHelper::getTranslation('shipping', 'id', 's', 's');
	//Translation fields are now defined by the SQL conditions.
	$translatedFields = $shipTranslation->translated_fields.',';
      }

      //Retrieve the component shipping items matching with both total weight and quantity. 
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('s.id,'.$translatedFields.'s.delivery_type,s.global_cost,s.delivpnt_cost,s.min_delivery_delay')
	    ->from('#__ketshop_shipping AS s');

      if(!is_null($shipTranslation)) {
	//Join over the translation table.
	$query->join('LEFT OUTER', $shipTranslation->left_join);
      }

      $query->where($totalWeight.' >= s.min_weight AND '.$totalWeight.' <= s.max_weight')
	    ->where($totalQuantity.' >= s.min_product AND '.$totalQuantity.' <= s.max_product')
	    ->where('s.published = 1')
	    ->order('s.ordering');
      $db->setQuery($query);
      $shippings = $db->loadObjectList();

      //No shipping has been found.
      if(empty($shippings)) {
	//Return an empty array. Customers won't be able to purchase products.
	$results = array();
      }
      else {
	//Get the last shipping address set by the customer.
	$user = JFactory::getUser();
	$query->clear();
	$query->select('postcode,city,region_code,country_code,continent_code')
	      ->from('#__ketshop_address')
	      ->where('item_id='.$user->id.' AND type="shipping" AND item_type="customer"')
	      ->order('created DESC')
	      ->setLimit(1);
	$db->setQuery($query);
	$shippingAddress = $db->loadObject();

        //Get the shippings matching with customer address.
	$results = $this->getShippingData($shippings, $shippingAddress, $priceRules);
      }
//file_put_contents('/var/www/web/debug_files/debog_shipping.txt', print_r($shippingAddress, true));
      $shippers[$shipperId]['shippings'] = $results;
      //Will be checked later by the setShipper function.
      $shippers[$shipperId]['selected'] = false; 

      $session->set('shippers', $shippers, 'ketshop'); 
    }

    return true;
  }


  public function getShippingData($shippings, $shippingAddress, $priceRules)
  {
    $results = array();
    $shippingId = 1;

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //First get all of the shipping attributes and delivery point addresses (to prevent to run SLQ queries in a foreach loop).
    $query->select('p.*')
	  ->from('#__ketshop_shipping AS s')
	  ->join('LEFT', '#__ketshop_ship_postcode AS p ON p.shipping_id=s.id')
	  ->where('s.published=1 AND s.delivery_type="at_destination"');
    $db->setQuery($query);
    $postcodes = $db->loadObjectList();

    $query->clear();
    $query->select('c.*')
	  ->from('#__ketshop_shipping AS s')
	  ->join('LEFT', '#__ketshop_ship_city AS c ON c.shipping_id=s.id')
	  ->where('s.published=1 AND s.delivery_type="at_destination"');
    $db->setQuery($query);
    $cities = $db->loadObjectList();

    $query->clear();
    $query->select('r.*')
	  ->from('#__ketshop_shipping AS s')
	  ->join('LEFT', '#__ketshop_ship_region AS r ON r.shipping_id=s.id')
	  ->where('s.published=1 AND s.delivery_type="at_destination"');
    $db->setQuery($query);
    $regions  = $db->loadObjectList();

    $query->clear();
    $query->select('c.*')
	  ->from('#__ketshop_shipping AS s')
	  ->join('LEFT', '#__ketshop_ship_country AS c ON c.shipping_id=s.id')
	  ->where('s.published=1 AND s.delivery_type="at_destination"');
    $db->setQuery($query);
    $countries = $db->loadObjectList();

    $query->clear();
    $query->select('c.*')
	  ->from('#__ketshop_shipping AS s')
	  ->join('LEFT', '#__ketshop_ship_continent AS c ON c.shipping_id=s.id')
	  ->where('s.published=1 AND s.delivery_type="at_destination"');
    $db->setQuery($query);
    $continents = $db->loadObjectList();

    $query->clear();
    $query->select('id AS shipping_id, global_cost AS cost')
	  ->from('#__ketshop_shipping')
	  ->where('published=1 AND delivery_type="at_destination"');
    $db->setQuery($query);
    $globals = $db->loadObjectList();

    $query->clear();
    $query->select('a.id, a.item_id, a.street, a.postcode, a.city, r.lang_var AS region, c.lang_var AS country')
	  ->from('#__ketshop_address AS a')
	  ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code')
	  ->join('LEFT', '#__ketshop_country AS c ON c.alpha_2=a.country_code')
	  ->where('a.item_type="delivery_point"');
    $db->setQuery($query);
    $addresses = $db->loadObjectList('item_id');

    //Search for shipping cost.
    foreach($shippings as $shipping) {
      //Set the shipping cost to null for comparisons.
      $shippingCost = null;

      if($shipping->delivery_type == 'at_destination') {
	//Note: No shipping address result means that the user has probably been
	//created before ketshopprofile plugin is installed (eg: super user).
	//It also means that no shipping address has been set yet for this user.
	//So we just look for global cost. On the next step we will ask user to 
	//properly set his address.
	if(!is_null($shippingAddress)) {
	  //Check against the postcode, city, region, country then continent to define
	  //how much will be the shipping cost.
	  //Note: Each shipping address attribute is checked before running query.

	  //Start with postcode.
	  if($shippingAddress->postcode) {
//file_put_contents('debog_shipment.txt', print_r($shipping, true));
	    foreach($postcodes as $postcode) {
	      if($postcode->shipping_id == $shipping->id &&
		 $shippingAddress->postcode >= $postcode->from &&
		 $shippingAddress->postcode <= $postcode->to) {
	        $shippingCost = $postcode->cost;
	        break;
	      }
	    }
	  }

	  //If it doesn't match we go on with the city name 
	  if(is_null($shippingCost) && $shippingAddress->city) {
	    foreach($cities as $city) {
	      if($city->shipping_id == $shipping->id && $shippingAddress->city == $city->name) {
	        $shippingCost = $city->cost;
	        break;
	      }
	    }
	  }

	  //then the region
	  if(is_null($shippingCost) && $shippingAddress->region_code) {
	    foreach($regions as $region) {
	      if($region->shipping_id == $shipping->id && $shippingAddress->region_code == $region->code) {
	        $shippingCost = $region->cost;
	        break;
	      }
	    }
	  }

	  //then the country
	  if(is_null($shippingCost) && $shippingAddress->country_code) {
	    foreach($countries as $country) {
	      if($country->shipping_id == $shipping->id && $shippingAddress->country_code == $country->code) {
	        $shippingCost = $country->cost;
	        break;
	      }
	    }
	  }

	  //then the continent
	  if(is_null($shippingCost) && $shippingAddress->continent) {
	    foreach($continents as $continent) {
	      if($continent->shipping_id == $shipping->id && $shippingAddress->continent_code == $continent->code) {
	        $shippingCost = $continent->cost;
	        break;
	      }
	    }
	  }
	}

	//and at last the global shipping cost.
	if(is_null($shippingCost)) {
	  foreach($globals as $global) {
	    if($global->shipping_id == $shipping->id) {
	      $shippingCost = $global->cost;
	      break;
	    }
	  }
	}

	if(is_null($shippingCost)) {
	  $shippingCost = 0;
	}

	//If there are any shipping price rules we apply them.
	if(!empty($priceRules)) {
	  $finalShippingCost = PriceruleHelper::applyShippingPriceRules($shippingCost, $priceRules);
	}
	else {
	  $finalShippingCost = $shippingCost;
	}

	//Store the shipping data.
	$shippingData = array();
	$shippingData['id'] = $shippingId;
	$shippingData['delivery_type'] = 'at_destination';
	$shippingData['cost'] = $shippingCost;
	$shippingData['final_cost'] = $finalShippingCost;
	$shippingData['name'] = $shipping->name;
	$shippingData['min_delivery_delay'] = $shipping->min_delivery_delay;
	$shippingData['description'] = $shipping->description;
	$shippingData['selected'] = false; //Will be checked later by the setShipper function.

	//Add the shipping data to the results.
	$results[] = $shippingData;

	//Increment the shipping id.
	$shippingId++;
      }
      else { //at_delivery_point
	//Get the cost for all the delivery points linked to this shipping.
	$shippingCost = $shipping->delivpnt_cost;

	//If there are any shipping price rules we apply them.
	if(!empty($priceRules)) {
	  $finalShippingCost = PriceruleHelper::applyShippingPriceRules($shippingCost, $priceRules);
	}
	else {
	  $finalShippingCost = $shippingCost;
	}

	//Get the address linked to the delivery point.
	$address;
	foreach($addresses as $key => $value) {
	  if($key == $shipping->id) {
	    $address = $value;
	    break;
	  }
	}

	//Store the shipping data.
	$shippingData = array();
	$shippingData['id'] = $shippingId;
	$shippingData['delivery_type'] = 'at_delivery_point';
	$shippingData['cost'] = $shippingCost;
	$shippingData['final_cost'] = $finalShippingCost;
	$shippingData['name'] = $shipping->name;
	$shippingData['min_delivery_delay'] = $shipping->min_delivery_delay;
	$shippingData['selected'] = false; //Will be checked later by the setShipper function.
	$shippingData['address_id'] = $address->id;
	$shippingData['street'] = $address->street;
	$shippingData['postcode'] = $address->postcode;
	$shippingData['city'] = $address->city;
	$shippingData['region'] = $address->region;
	$shippingData['country'] = $address->country;
	$shippingData['phone'] = $address->phone;
	$shippingData['information'] = $shipping->description;

	//Add the shipping data to the results.
	$results[] = $shippingData;

	//Increment the shipping id.
	$shippingId++;
      }
    } 

    return $results;
  }
}
