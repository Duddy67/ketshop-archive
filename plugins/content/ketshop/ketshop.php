<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/utility.php';
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/ketshop.php';
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/bundle.php';


class plgContentKetshop extends JPlugin
{

  public function onContentBeforeSave($context, $data, $isNew)
  {
    if(!$isNew && $context == 'com_ketshop.product') { 
      //The stock has been modified while the user was editing the product.
      if($data->stock_locked) {
	//Delete the stock attribute so that its new value is not taken into account.
	unset($data->stock);

	//The product has variants.
	if($data->attribute_group) {
	  //Get the current product variants.
	  $db = JFactory::getDbo();
	  $query = $db->getQuery(true);
	  $query->select('var_id, stock')
		->from('#__ketshop_product_variant')
		->where('prod_id='.(int)$data->id);
	  $db->setQuery($query);
	  $productVariants = $db->loadAssocList('var_id');

	  $jinput = JFactory::getApplication()->input;
	  $post = $jinput->post->getArray();
	  //Check the edited product variants and replace their stock value accordingly. 
	  foreach($post as $key => $value) {
	    if(preg_match('#^variant_id_([0-9]+)$#', $key, $matches)) {
	      $varNb = $matches[1];
	      $varId = $post['variant_id_'.$varNb];

	      if(isset($productVariants[$varId])) {
		//Replace the new stock value with the old one.
		$jinput->post->set('stock_'.$varNb, $productVariants[$varId]['stock']);
	      }
	    }
	  }
	}

	//Unlocks the stock value.
	$data->stock_locked = 0;
	//Informs the user.
	JFactory::getApplication()->enqueueMessage(JText::_('COM_KETSHOP_NOTICE_STOCK_LOCKED'), 'Notice');
      }
    }

    //Removes tags created on the fly from any component.
    if(!$this->params->get('tags_on_the_fly', 0)) {
      //Check we have tags before treating data.
      if(isset($data->newTags)) {
        KetshopHelper::removeTagsOnTheFly($data->newTags);
      }   
    }   

    return true;
  }


  public function onContentBeforeDelete($context, $data)
  {
    return true;
  }


  //Since the id of a new item is not known before being saved, the code which
  //links item ids to other item ids should be placed here.

  public function onContentAfterSave($context, $data, $isNew)
  {
    //Filter the sent event.

    if($context == 'com_ketshop.product' || $context == 'com_ketshop.form') { //PRODUCT
      //Check for product order.
      $this->setOrderByTag($context, $data, $isNew);

      //Get all of the POST data.
      $post = JFactory::getApplication()->input->post->getArray();

      // Create a new query object.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //
      if($data->type == 'bundle') {
	//Retrieve all the new set bundle products from the POST array.
	$bundleProducts = array();
	foreach($post as $key=>$val) {
	  if(preg_match('#^bundleproduct_id_([0-9]+)$#', $key, $matches)) {
	    $bundleProductNb = $matches[1];
	    $bundleProductId = $post['bundleproduct_id_'.$bundleProductNb];
	    $bundleProductQty = $post['bundleproduct_quantity_'.$bundleProductNb];

	    $bundleProduct = new JObject;
	    $bundleProduct->id = $bundleProductId;
	    $bundleProduct->quantity = $bundleProductQty;
	    $bundleProducts[] = $bundleProduct; //
	  }
	}

	//Set fields.
	$columns = array('bundle_id','prod_id','quantity');

	//Set or update the products of the bundle.
	KetshopHelper::updateMappingTable('#__ketshop_prod_bundle', $columns, $bundleProducts, array($data->id), 'bundle_id');

	//Set or update the attributes of the bundle which require a specific treatment.
	BundleHelper::updateBundle('all', array($data->id));
      }

      //Now bundles are set we can move on to attributes.

      //Retrieve all the new set attributes from the POST array then save them as
      //objects and put them into an array.
      $attributes = $attribIds = array();

      foreach($post as $key => $val) {
	if(preg_match('#^attribute_id_([0-9]+)$#', $key, $matches)) {
	  $attribNb = $matches[1];
	  $attribId = $post['attribute_id_'.$attribNb];
	  //Value lists are either an array (when they're coming from a multiselect
	  //list) or a single value (when they're coming from an input field).
	  $fieldValue1 = $post['attribute_field_value_1_'.$attribNb];

	  $attribFieldValue1 = '';
	  if(is_array($fieldValue1)) {
	    //Concatenate values as a string separated with |.
	    foreach($fieldValue1 as $value) {
	      $attribFieldValue1 .= $value.'|';
	    }

	    //Remove the last | from the end of the string.
	    $attribFieldValue1 = substr($attribFieldValue1, 0, -1);
	  }
	  else {
	    $attribFieldValue1 = $fieldValue1;
	  }

	  //Same thing for value list 2 if any
	  $fieldValue2 = $post['attribute_field_value_2_'.$attribNb];
	  $attribFieldValue2 = '';

	  if(is_array($fieldValue2)) {
	    foreach($fieldValue2 as $value) {
	      $attribFieldValue2 .= $value.'|';
	    }
	    //Remove the last | from the end of the string.
	    $attribFieldValue2 = substr($attribFieldValue2, 0, -1);
	  }
	  else {
	    $attribFieldValue2 = $fieldValue2;
	  }

	  if(!empty($attribFieldValue1)) { //Check for empty field.  
	    $attribute = new JObject;
	    $attribute->id = $attribId;
	    $attribute->field_value_1 = $attribFieldValue1;
	    $attribute->field_text_1 = '';
	    $attribute->field_value_2 = $attribFieldValue2;
	    $attribute->field_text_2 = '';
	    $attributes[] = $attribute;
	    $attribIds[] = $attribId;
	  }
	}
      }

      if(!empty($attribIds)) {
	//In case of attributes used as closed list (drop down list) we must find out 
	//what is the corresponding text for each selected value.
	//It will be more convenient in frontend to retrieve text directly from db rather
	//than using functions and ressources to figure it out.

	//Get data of all the attributes linked to the product and used as closed list.
	$query->clear();
	$query->select('id, field_value_1, field_text_1, field_value_2, field_text_2')
	      ->from('#__ketshop_attribute')
	      ->where('id IN('.implode(',', $attribIds).')')
	      ->where('(field_type_1="closed_list" OR (field_type_2!="open_field" && field_type_2!="none"))');
	$db->setQuery($query);
	//Set the array index with the attribute ids.
	$fieldData = $db->loadAssocList('id');

	foreach($attributes as $key => $attribute) {
	  //Check out from the array index if the current attribute is 
	  //defined in the list.
	  if(!@is_null($fieldData[$attribute->id])) {
	    //A no empty value means that we're dealing with a closed list.
	    if(!empty($fieldData[$attribute->id]['field_value_1'])) {
	      //Turn the value and text data of the drop down list into arrays.
	      $fieldVal1 = explode('|', $fieldData[$attribute->id]['field_value_1']);
	      $fieldText1 = explode('|', $fieldData[$attribute->id]['field_text_1']); 
	      //The first field might be a multiselect drop down list so we need to 
	      //turn it into an array just in case.
	      $selectedVals = explode('|', $attribute->field_value_1); 
	      //Search for the position of the selected value(s).
	      foreach($fieldVal1 as $pos => $value) {
		if(in_array($value, $selectedVals)) {
		  //Set to the corresponding text value.
		  $attributes[$key]->field_text_1 = $fieldText1[$pos].'|';
		}
	      }
	      //Remove the last | from the end of the string.
	      $attributes[$key]->field_text_1 = substr($attributes[$key]->field_text_1, 0, -1);
	    }

	    //Same thing for the second field.
	    if(!empty($fieldData[$attribute->id]['field_value_2'])) {
	      $fieldVal2 = explode('|', $fieldData[$attribute->id]['field_value_2']);
	      $fieldText2 = explode('|', $fieldData[$attribute->id]['field_text_2']); 
	      foreach($fieldVal2 as $pos => $value) {
		if($value == $attribute->field_value_2) {
		  $attributes[$key]->field_text_2 = $fieldText2[$pos];
		  //No need to go further as the second field cannot be multiselect.
		  break;
		}
	      }
	    }
	  }
	}
      }

      //Set fields.
      $columns = array('prod_id','attrib_id','field_value_1','field_text_1','field_value_2','field_text_2');
      //Update attributes.
      KetshopHelper::updateMappingTable('#__ketshop_prod_attrib', $columns, $attributes, array($data->id));

      //At last we end with images.

      $images = array();
      foreach($post as $key=>$val) {
	if(preg_match('#^image_src_([0-9]+)$#', $key, $matches)) {
	  $imageNb = $matches[1];

	  if(JFactory::getApplication()->isAdmin()) {
	    //Remove "../" from src path in case images come from the administrator area.
	    $src = preg_replace('#^\.\.\/#', '', $post['image_src_'.$imageNb]);
	  }
	  else { //We're on front-end. Remove the domain url.
	    $src = preg_replace('#^'.JURI::root().'#', '', $post['image_src_'.$imageNb]);
	  }

	  $width = $post['image_width_'.$imageNb];
	  $height = $post['image_height_'.$imageNb];
	  $ordering = $post['image_ordering_'.$imageNb];
	  $alt = trim($post['image_alt_'.$imageNb]); //Clean out the value.

	  if(!empty($src)) { //Check for empty field.
	    $image = new JObject;
	    $image->src = $src;
	    $image->width = $width;
	    $image->height = $height;
	    $image->ordering = $ordering;
	    $image->alt = $alt;
	    $images[] = $image;
	  }
	}
      }

      //Set fields.
      $columns = array('prod_id','src','width','height','ordering','alt');
      //Update images.
      KetshopHelper::updateMappingTable('#__ketshop_prod_image', $columns, $images, array($data->id));

      if(!$isNew) {
	//If the product is part of a bundle we must update some bundle attributes.
	$bundleIds = BundleHelper::isBundleProduct((int)$data->id); 
	if(!empty($bundleIds)) {
	  BundleHelper::updateBundle('all', $bundleIds);
	}

	//Check for product variants.
	//Note: Only existing products can set variants.
	KetshopHelper::setProductVariants($data->id, $post);
      }

      return true;
    }
    elseif($context == 'com_ketshop.pricerule') { //PRICE RULE
      //Get all of the POST data.
      $post = JFactory::getApplication()->input->post->getArray();

      $ruleType = $data->type;
      $targetType = $data->target;       // product, bundle, product group (ie: category).
      $recipientType = $data->recipient; // customer,customer group

      //
      if($ruleType == 'cart') {
	//Retrieve all the new set conditions from the POST array.
	$conditionType = $data->condition; // product, bundle, category, cart amount, product quantity.
	$conditions = array();

	foreach($post as $key => $val) {
	  //
	  if(preg_match('#^condition_id_([0-9]+)$#', $key, $matches)) {
	    $conditionNb = $matches[1];
	    $conditionId = $post['condition_id_'.$conditionNb];
	    $operator = $post['operator_'.$conditionNb];

	    $condition = new JObject;
	    $condition->id = $conditionId;
	    $condition->operator = $operator;

	    if($conditionType == 'product_cat_amount' || $conditionType == 'total_prod_amount') {
	      $condition->amount = $post['condition_item_amount_'.$conditionNb];
	    }
	    else {
	      $conditionQty = $post['condition_item_qty_'.$conditionNb];
	      $condition->quantity = $conditionQty;
	    }

	    $conditions[] = $condition;
	  }
	}
      }

      //Retrieve all the ids of the new set targets from the POST array.
      $targetIds = array();

      //Note: There is no item dynamicaly added in target when cart rule is selected. So
      //we don't have to store anything into database.

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      if($ruleType == 'catalog') {
	foreach($post as $key => $val) {
	  if(preg_match('#^target_id_([0-9]+)$#', $key, $matches)) {
	    $targetNb = $matches[1];
	    //Store target ids.
	    $targetIds[] = $post['target_id_'.$targetNb];
	  }
	}

	//Don't go further if no values has been set. 
	if(empty($targetIds)) {
	  return true;
	}

	//Remove duplicate ids in case an item has been set twice or more.
	$targetIds = array_unique($targetIds);
      }

      //Retrieve all the new set recipients from the POST array.
      $recipientIds = array();
      foreach($post as $key=>$val) {
	if(preg_match('#^recipient_id_([0-9]+)$#', $key, $matches)) {
	  $recipientNb = $matches[1];
	  //Store recipient ids.
	  $recipientIds[] = $post['recipient_id_'.$recipientNb];
	}
      }

      //Delete all the previous targets, recipients, and conditions linked to
      //the price rule.
      $query->clear();
      $query->delete('#__ketshop_prule_target')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_prule_recipient')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_prule_condition')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      //Insert the new targets, recipients, and conditions which has been set.

      if(count($conditions)) {
	$values = array();
	foreach($conditions as $condition) {
	  //Build the values SQL according to the selected condition type.
	  if($conditionType == 'total_prod_amount') {
	    $values[] = $data->id.', 0,'.$db->Quote($condition->operator).','.$condition->amount.', NULL';
	  }
	  elseif($conditionType == 'total_prod_qty') {
	    $values[] = $data->id.', 0,'.$db->Quote($condition->operator).', NULL,'.$condition->quantity;
	  }
	  elseif($conditionType == 'product_cat_amount') {
	    $values[] = $data->id.','.$condition->id.','.$db->Quote($condition->operator).','.$condition->amount.', NULL';
	  }
	  else { //product, bundle or product cat quantity.
	    $values[] = $data->id.','.$condition->id.','.$db->Quote($condition->operator).', NULL,'.$condition->quantity;
	  }
	}

	//Insert a new row for each condition item linked to the price rule.
	$columns = array('prule_id', 'item_id', 'operator', 'item_amount', 'item_qty');
	$query->clear();
	$query->insert('#__ketshop_prule_condition')
	      ->columns($columns)
	      ->values($values);
	$db->setQuery($query);
	$db->query();
      }

      $columns = array('prule_id', 'item_id');

      if(count($targetIds)) {
	$values = array();
	foreach($targetIds as $targetId) {
	  $values[] = $data->id.','.$targetId;
	}

	//Insert a new row for each target item linked to the price rule.
	$query->clear();
	$query->insert('#__ketshop_prule_target')
	      ->columns($columns)
	      ->values($values);
	$db->setQuery($query);
	$db->query();
      }

      if(count($recipientIds)) {
	$values = array();
	foreach($recipientIds as $recipientId) {
	  $values[] = $data->id.','.$recipientId;
	}

	//Insert a new row for each recipient item linked to the price rule.
	$query->clear();
	$query->insert('#__ketshop_prule_recipient')
	      ->columns($columns)
	      ->values($values);
	$db->setQuery($query);
	$db->query();
      }

      return true;
    }
    elseif($context == 'com_ketshop.shipping') { //SHIPPING
      //Get all of the POST data.
      $post = JFactory::getApplication()->input->post->getArray();

      //Retrieve all the new set postcodes, cities, regions, countries,  
      //or continents (if any) from the POST array.
      $postcodes = array();
      $cities = array();
      $regions = array();
      $countries = array();
      $continents = array();

      foreach($post as $key => $val) {
	if(preg_match('#^postcode_from_([0-9]+)$#', $key, $matches)) {
	  $postcodeNb = $matches[1];

	  $postcode = new JObject;
	  $postcode->from = $post['postcode_from_'.$postcodeNb];
	  $postcode->to = $post['postcode_to_'.$postcodeNb];
	  $postcode->cost = $post['postcode_cost_'.$postcodeNb];
	  $postcodes[] = $postcode; //
	}

	if(preg_match('#^city_name_([0-9]+)$#', $key, $matches)) {
	  $cityNb = $matches[1];

	  $city = new JObject;
	  $city->name = $post['city_name_'.$cityNb];
	  $city->cost = $post['city_cost_'.$cityNb];
	  $cities[] = $city; //
	}

	if(preg_match('#^region_code_([0-9]+)$#', $key, $matches)) {
	  $regionNb = $matches[1];

	  $region = new JObject;
	  $region->code = $post['region_code_'.$regionNb];
	  $region->cost = $post['region_cost_'.$regionNb];
	  $regions[] = $region; //
	}

	if(preg_match('#^country_code_([0-9]+)$#', $key, $matches)) {
	  $countryNb = $matches[1];

	  $country = new JObject;
	  $country->code = $post['country_code_'.$countryNb];
	  $country->cost = $post['country_cost_'.$countryNb];
	  $countries[] = $country; //
	}

	if(preg_match('#^continent_code_([0-9]+)$#', $key, $matches)) {
	  $continentNb = $matches[1];

	  $continent = new JObject;
	  $continent->code = $post['continent_code_'.$continentNb];
	  $continent->cost = $post['continent_cost_'.$continentNb];
	  $continents[] = $continent; //
	}
      }

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the previous postcodes, cities, regions, countries
      //continents and delivery points linked to the shipping.
      $query->delete('#__ketshop_ship_postcode')
	    ->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_city')
	    ->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_region')
	    ->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_country')
	    ->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_continent')
	    ->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      //Store items according to the delivery type chosen by the user.
      if($data->delivery_type == 'at_destination') {
	//Store postcodes if any.
	if(count($postcodes)) {
	  $values = array();
	  foreach($postcodes as $postcode) {
	    $values[] = $data->id.','.$db->Quote($postcode->from).','.$db->Quote($postcode->to).','.$postcode->cost;
	  }

	  //Note: The "from" and "to" fields MUST be "backticked" as they are
	  //reserved SQL words.
	  $columns = array('shipping_id', $db->quoteName('from'), $db->quoteName('to'), 'cost');
	  //Insert a new row for each zip codes item linked to the shipping.
	  $query->clear();
	  $query->insert('#__ketshop_ship_postcode')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->query();
	}

	//Store cities if any.
	if(count($cities)) {
	  $values = array();
	  foreach($cities as $city) {
	    $values[] = $data->id.','.$db->Quote($city->name).','.$city->cost;
	  }

	  $columns = array('shipping_id', 'name', 'cost');
	  //Insert a new row for each city item linked to the shipping.
	  $query->clear();
	  $query->insert('#__ketshop_ship_city')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->query();
	}

	//Store regions if any.
	if(count($regions)) {
	  $values = array();
	  foreach($regions as $region) {
	    $values[] = $data->id.','.$db->Quote($region->code).','.$region->cost;
	  }

	  $columns = array('shipping_id', 'code', 'cost');
	  //Insert a new row for each region item linked to the shipping.
	  $query->clear();
	  $query->insert('#__ketshop_ship_region')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->query();
	}

	//Store countries if any.
	if(count($countries)) {
	  $values = array();
	  foreach($countries as $country) {
	    $values[] = $data->id.','.$db->Quote($country->code).','.$country->cost;
	  }

	  $columns = array('shipping_id', 'code', 'cost');
	  //Insert a new row for each country item linked to the shipping.
	  $query->clear();
	  $query->insert('#__ketshop_ship_country')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->query();
	}

	//Store continents if any.
	if(count($continents)) {
	  $values = array();
	  foreach($continents as $continent) {
	    $values[] = $data->id.','.$db->Quote($continent->code).','.$continent->cost;
	  }

	  $columns = array('shipping_id', 'code', 'cost');
	  //Insert a new row for each continent item linked to the shipping.
	  $query->clear();
	  $query->insert('#__ketshop_ship_continent')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->query();
	}
      }
      else { //at_delivery_point
	//Retrieve jform to get the needed extra fields.
	$jform = $post['jform'];

	//Store the address data.
	$address = array('street' => $jform['street'],
			 'city' => $jform['city'],
			 'region_code' => $jform['region_code'],
			 'postcode' => $jform['postcode'],
			 'country_code' => $jform['country_code'],
			 'phone' => $jform['phone'],
			 //The shipping description is used as note, so we set the note
			 //address field to empty.
			 'note' => '');

	//Get the proper query to use for this address. 
	$query = UtilityHelper::getAddressQuery($address, 'shipping', 'delivery_point', $data->id);
	//Execute the query.
	$db = JFactory::getDbo();
	$db->setQuery($query);
	$db->query();
      }

      return true;
    }
    elseif($context == 'com_ketshop.order') { //ORDER
      $post = JFactory::getApplication()->input->post->getArray();
      $deliveryId = $post['delivery_id'];

      //Update delivery table.
      if($deliveryId) {
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$now = JFactory::getDate()->toSql();

	$fields = array('status='.$db->quote($post['delivery_status']),
			'delivery_date='.$db->quote($post['delivery_date']),
			'note='.$db->quote($post['delivery_note']),
			'modified='.$db->quote($now));
	$query->update('#__ketshop_delivery');
	$query->set($fields);
	$query->where('id='.(int)$deliveryId);
	$db->setQuery($query);
	$db->query();
      }

    }
    elseif($context == 'com_ketshop.deliverypoint') { //DELIVERY POINT
      //Get all of the POST data.
      $post = JFactory::getApplication()->input->post->getArray();
      //Retrieve jform to get the needed extra fields.
      $jform = $post['jform'];

      //Store the address data.
      $address = array('street' => $jform['street'],
		       'city' => $jform['city'],
		       'region_code' => $jform['region_code'],
		       'postcode' => $jform['postcode'],
		       'country_code' => $jform['country_code'],
		       'phone' => $jform['phone'],
		       'note' => $jform['note']);

      //Get the proper query to use for this address. 
      $query = UtilityHelper::getAddressQuery($address, 'shipping', 'delivery_point', $data->id);
      //Execute the query.
      $db = JFactory::getDbo();
      $db->setQuery($query);
      $db->query();

      return true;
    }
    elseif($context == 'com_ketshop.attribute') { //ATTRIBUTE
      //Get all of the POST data.
      $post = JFactory::getApplication()->input->post->getArray();
      $groupIds = $values = array();

      //Search for possible groups linked to the attribute.
      foreach($post as $key => $groupId) {
	if(preg_match('#^group_([0-9]+)$#', $key)) {

	  //Prevent duplicate group id.
	  if(!in_array($groupId, $groupIds)) {
	    $groupIds[] = $groupId;
	    $values[] = $data->id.','.(int)$groupId;
	  }
	}
      }

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the previous mappings. 
      $query->delete('#__ketshop_attrib_group')
	    ->where('attrib_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      if(!empty($values)) {
	$columns = array('attrib_id', 'group_id');
	//Insert a new row for each group linked to the attribute.
	$query->clear();
	$query->insert('#__ketshop_attrib_group')
	      ->columns($columns)
	      ->values($values);
	$db->setQuery($query);
	$db->query();
      }

      return true;
    }
    elseif($context == 'com_categories.category' && $data->extension == 'com_ketshop') { //COMPONENT CATEGORIES
      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  public function onContentAfterDelete($context, $data)
  {
    //Filter the sent event.

    if($context == 'com_ketshop.product') {
      // Create a new query object.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the rows linked to the item id (ordering). 
      $query->delete('#__ketshop_product_tag_map')
	    ->where('product_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      //Remove the product id from the product attribute mapping table.
      $query->clear();
      $query->delete('#__ketshop_prod_attrib');
      $query->where('prod_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      //Remove the product id from the product variant mapping table.
      $query->clear();
      $query->delete('#__ketshop_product_variant');
      $query->where('prod_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      //Remove the product id from the variant attribute mapping table.
      $query->clear();
      $query->delete('#__ketshop_var_attrib');
      $query->where('prod_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      //Remove the product id from the product image mapping table.
      $query->clear();
      $query->delete('#__ketshop_prod_image');
      $query->where('prod_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      if($data->type == 'bundle') {
	//Remove the product id from the product bundle mapping table.
	$query->clear();
	$query->delete('#__ketshop_prod_bundle');
	$query->where('bundle_id='.(int)$data->id);
	$db->setQuery($query);
	$db->query();
      }

    }
    elseif($context == 'com_ketshop.pricerule') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      //Delete all the previous targets, recipients, and conditions linked to
      //the deleted price rule item.
      $query->delete('#__ketshop_prule_target');
      $query->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_prule_recipient');
      $query->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_prule_condition');
      $query->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

    }
    elseif($context == 'com_ketshop.shipping') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the item type linked to the deleted shipping.
      $query->delete('#__ketshop_ship_postcode');
      $query->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_city');
      $query->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_region');
      $query->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_country');
      $query->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_ship_continent');
      $query->where('shipping_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_address');
      $query->where('item_id='.(int)$data->id).' AND item_type = "delivery_point"';
      $db->setQuery($query);
      $db->query();

    }
    elseif($context == 'com_ketshop.order') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      $query->delete('#__ketshop_order_prod');
      $query->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_order_prule');
      $query->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_order_transaction');
      $query->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $query->clear();
      $query->delete('#__ketshop_delivery');
      $query->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();
    }
    elseif($context == 'com_ketshop.deliverypoint') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      $query->delete('#__ketshop_address');
      $query->where('item_id='.(int)$data->id.' AND item_type = "delivery_point"');
      $db->setQuery($query);
      $db->query();
    }
    elseif($context == 'com_ketshop.attribute') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      //Remove the attribute from the product attribute mapping table.
      $query->delete('#__ketshop_prod_attrib');
      $query->where('attrib_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();
    }
    elseif($context == 'com_tags.tag') {
      //
    }
  }


  public function onContentChangeState($context, $pks, $value)
  {
    //Filter the sent event.

    if($context == 'com_ketshop.product') {
      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  /**
   * Create (or update) a row whenever a product is tagged.
   * The product/tag mapping allows to order the products against a given tag. 
   *
   * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
   * @param   object   $data     A JTableContent object
   * @param   boolean  $isNew    If the content is just about to be created
   *
   * @return  void
   *
   */
  private function setOrderByTag($context, $data, $isNew)
  {
    //Get the jform data.
    $jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');

    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Check we have tags before treating data.
    if(isset($data->newTags)) {
      //Retrieve all the rows matching the item id.
      $query->select('product_id, tag_id, main_tag_id, IFNULL(ordering, "NULL") AS ordering')
	    ->from('#__ketshop_product_tag_map')
	    ->where('product_id='.(int)$data->id);
      $db->setQuery($query);
      $tags = $db->loadObjectList();

      $values = array();
      foreach($data->newTags as $tagId) {
	$newTag = true; 
	//In order to preserve the ordering of the old tags we check if 
	//they match those newly selected.
	foreach($tags as $tag) {
	  if($tag->tag_id == $tagId) {
	    $values[] = $tag->product_id.','.$tag->tag_id.','.$data->main_tag_id.','.$tag->ordering;
	    $newTag = false; 
	    break;
	  }
	}

	if($newTag) {
	  $values[] = $data->id.','.$tagId.','.$data->main_tag_id.',NULL';
	}
      }

      //Delete all the rows matching the item id.
      $query->clear();
      $query->delete('#__ketshop_product_tag_map')
	    ->where('product_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $columns = array('product_id', 'tag_id', 'main_tag_id', 'ordering');
      //Insert a new row for each tag linked to the item.
      $query->clear();
      $query->insert('#__ketshop_product_tag_map')
	    ->columns($columns)
	    ->values($values);
      $db->setQuery($query);
      $db->query();
    }
    else { //No tags selected or tags removed.
      //Delete all the rows matching the item id.
      $query->delete('#__ketshop_product_tag_map')
	    ->where('product_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();
    }

    return;
  }

}

