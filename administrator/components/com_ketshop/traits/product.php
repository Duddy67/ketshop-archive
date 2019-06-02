<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

JLoader::register('BundleTrait', JPATH_ADMINISTRATOR.'/components/com_ketshop/traits/bundle.php');

/**
 * Provides some utility functions relating to items linked to a product as attributes,
 * images and so on. 
 *
 */

trait ProductTrait
{
  use BundleTrait;


  /**
   * Returns the selected values of the attributes bound to a given product.  
   *
   * @param   integer  $productId  The id of the product.
   *
   * @return  array    An array of attribute data or an empty array.
   */
  public function getAttributeData($productId, $variantId) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Gets all the attribute option values/texts as well as the selected option(s). 
    $query->select('a.name, a.multiselect, va.attrib_id, va.option_value AS selected_value, ao.option_value, ao.option_text')
	  ->from('#__ketshop_var_attrib AS va')
	  ->join('INNER', '#__ketshop_attrib_option AS ao ON ao.attrib_id=va.attrib_id')
	  ->join('INNER', '#__ketshop_attribute AS a ON a.id=va.attrib_id')
	  ->where('va.prod_id='.(int)$productId)
	  ->where('va.var_id='.(int)$variantId)
	  //
	  ->order('va.attrib_id');
    $db->setQuery($query);
    $data = $db->loadAssocList();

    // Restructures data.

    $attributes = array();
    $nbData = count($data);

    // Loops through the data.
    for($i = 0; $i < $nbData; $i++) {
      // Checks for regular attribute values (ie: single select drop down list).
      if($data[$i]['option_value'] == $data[$i]['selected_value']) {
	// Stores the needed attribute data.
	$attributes[] = array('attrib_id' => $data[$i]['attrib_id'], 
	                      'name' => $data[$i]['name'],
			      'multiselect' => 0,
			      'option_value' => $data[$i]['selected_value'],
			      'option_text' => $data[$i]['option_text']);
      }

      // Handles the multi data (ie: multiselect drop down list).
      if($data[$i]['multiselect'] == 1) {
	// For starters stores the global attribute data.
	$attributes[] = array('attrib_id' => $data[$i]['attrib_id'], 
	                      'name' => $data[$i]['name'],
			      'multiselect' => $data[$i]['multiselect'],
			      'options' => array());

	$lastElement = count($attributes) - 1;
	// Converts the Json data into an array of values.
	$selectedValues = json_decode($data[$i]['selected_value']);
	// Gets the multiselect attribute id.
	$attribId = $data[$i]['attrib_id'];

	// Runs a nested loop which starts from the first index of the multiselect attribute.
	for($j = $i; $j < $nbData ; $j++) {
	  // Stores each value/text for this attribute.
	  if($data[$j]['attrib_id'] == $attribId && in_array($data[$j]['option_value'], $selectedValues)) {
	    $attributes[$lastElement]['options'][] = array('option_value' => $data[$j]['option_value'],
							   'option_text' => $data[$j]['option_text']);
	  }
	  // It's no longer the same attribute.
	  elseif($data[$j]['attrib_id'] != $attribId) {
	    // Stops the nested loop and lets the parent loop taking over.
	    break;
	  }

	  // Increments the parent loop index.
	  $i++;
	}
      }
    }

    return $attributes;
  }


  public function getImageData($productId, $isAdmin) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('src, width, height, alt, ordering') 
	  ->from('#__ketshop_prod_image')
	  ->where('prod_id='.$productId)
	  ->order('ordering');
    $db->setQuery($query);
    $images = $db->loadAssocList();

    if($isAdmin) {
      //Add "../" to the path of each image as we are in the administrator area.
      foreach($images as $key => $image) {
	$image['src'] = '../'.$image['src'];
	$images[$key] = $image;
      }
    }
    else {
      //On front-end we must set src with the absolute path or SEF will add a wrong url path.  
      $length = strlen('administrator/components/com_ketshop/js/ajax/');
      $length = $length - ($length * 2);
      $url = substr(JURI::root(), 0, $length);

      foreach($images as $key => $image) {
	$image['src'] = $url.$image['src'];
	$images[$key] = $image;
      }
    }

    return $images;
  }


  /**
   * Returns the variants bound to a given product.  
   *
   * @param   integer  $productId  The id of the product.
   *
   * @return  array    An array of variants or an empty array.
   */
  public function getVariantData($productId) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Gets the variants bound to the given product.
    // N.B: The field names must match the specific order of the dynamic item. 
    //      The var_id field has to be named id_nb in order to be used with dynamic items.
    $query->select('prod_id, var_id, var_id AS id_nb, name, TRUNCATE(base_price,2) AS base_price, TRUNCATE(sale_price,2) AS sale_price,'.
                   'stock, sales, published, stock_subtract, allow_order, min_stock_threshold, max_stock_threshold, min_quantity,'.
                   'max_quantity, TRUNCATE(weight,2) AS weight, TRUNCATE(length,2) AS length, TRUNCATE(width,2) AS width,'.
		   'TRUNCATE(height,2) AS height, code, availability_delay') 
	  ->from('#__ketshop_product_variant')
	  ->where('prod_id='.$productId)
	  ->order('ordering');
    $db->setQuery($query);
    $variants = $db->loadAssocList();

    if(!empty($variants)) {
      //Fetches the option values of the variant attributes linked to the given product.
      $query->clear();
      $query->select('var_id, attrib_id, option_value AS selected_option') 
	    ->from('#__ketshop_var_attrib')
	    ->where('prod_id='.$productId)
	    ->order('var_id');
      $db->setQuery($query);
      $attributes = $db->loadAssocList();

      //Sets and stores the attributes linked to the variant.
      foreach($variants as $key => $variant) {
	//Adds the unset attributes to the variant.
	$variants[$key]['attributes'] = array();

	foreach($attributes as $attribute) {
	  if($attribute['var_id'] == $variant['var_id']) {
	    $variants[$key]['attributes'][] = $attribute;
	  }
	}
      }
    }

    return $variants;
  }


  /**
   * Returns the attributes bound to a given product.  
   *
   * @param   integer  $productId  The id of the product.
   *
   * @return  array	           An array of attributes or an empty array.
   */
  public function getProductAttributes($productId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //
    $query->select('attrib_id AS attribute_id, name AS attribute_name')
	  ->from('#__ketshop_prod_attrib')
	  ->join('LEFT', '#__ketshop_attribute ON id=attrib_id')
	  ->where('prod_id='.(int)$productId)
          ->order('name');
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  /**
   * Returns the attribute item structure for a given id.  
   *
   * @param   integer  $attributeId	The id of the attribute.
   *
   * @return  array	                The attribute item structure. 
   */
  public function getAttribute($attributeId) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Gets the attribute data as option values.
    $query->select('a.multiselect, a.name, a.published AS attribute_published, ao.option_value, ao.option_text, ao.published')
	  ->from('#__ketshop_attribute AS a')
	  ->join('INNER', '#__ketshop_attrib_option AS ao ON ao.attrib_id=a.id')
	  ->where('a.id='.(int)$attributeId)
	  ->order('ao.ordering');
    $db->setQuery($query);
    //Get results as an associative array.
    $options = $db->loadAssocList();

    $multiselect = $published = 0;
    $name = '';

    foreach($options as $key => $option) {
      //Adds empty selected value to each option as no value has been selected yet.
      $options[$key]['selected'] = '';
      //Sets the attribute data.
      $multiselect = (int)$option['multiselect'];
      $published = (int)$option['attribute_published'];
      $name = $option['name'];
      //Removes the attribute data from the options as it's confusing and useless.
      unset($options[$key]['multiselect']);
      unset($options[$key]['name']);
      unset($options[$key]['attribute_published']);
    }

    $attribute = array('id' => $attributeId, 'multiselect' => $multiselect,
		       'name' => $name, 'published' => $published, 'options' => $options);

    return $attribute;
  }


  /**
   * Stores the product variants currently set.
   *
   * @param   integer  $productId	The id of the product which the variants are linked to.
   * @param   array    $data		The POST array in which the variant data is passing.
   *
   * @return  void
   */
  public function setProductVariants($productId, $data)
  {
    $varValues = $attribValues = array();
    $hasVariant = 0;

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    // First deletes all the previous variants linked to the product.
    $query->delete('#__ketshop_product_variant')
	  ->where('prod_id='.(int)$productId);
    $db->setQuery($query);
    $db->execute();

    // Same for the previous attributes linked to the variants.
    $query->clear();
    $query->delete('#__ketshop_var_attrib')
	  ->where('prod_id='.(int)$productId);
    $db->setQuery($query);
    $db->execute();

    foreach($data as $key => $value) {
      if(preg_match('#^variant_id_nb_([0-9]+)$#', $key, $matches)) {
	$varNb = $matches[1];
	$varId = $data['variant_id_nb_'.$varNb];

	$published = 0;
	// N.B: Checkbox variable is not passed through POST when unchecked.
	//      The basic variant (ie: the first on the list) cannot be unpublished.
	if(isset($data['variant_published_'.$varNb]) || $data['variant_ordering_'.$varNb] == 1) {
	  $published = 1;
	}

	if($data['jform']['type'] == 'bundle') {
	  // Relies on the bundle functions to set the stock and availability_delay values.
	  $data['variant_stock_'.$varNb] = $this->getBundleStock($productId);
	  $data['variant_availability_delay_'.$varNb] = $this->getBundleDelay($productId);
	  // stock_subtract field is disabled for bundle and thus its value is not sent through the form. 
	  $stockSubtract = $this->getReadOnlyYesNoValues($productId, 'stock_subtract');
	}
	// normal
	else {
	  $stockSubtract = $data['variant_stock_subtract_'.$varNb];
	}

	if(!(int)$stockSubtract) {
	  // Stock is infinite. Replaces the infinite sign '∞' with zero.
	  $data['variant_stock_'.$varNb] = 0;
	}

	// Stores variant values to insert.
	$varValues[] = (int)$productId.','.(int)$varId.','.$db->Quote($data['variant_name_'.$varNb]).
                        ','.(int)$data['variant_stock_'.$varNb].
			','.UtilityHelper::floatFormat($data['variant_base_price_'.$varNb]).
			','.UtilityHelper::floatFormat($data['variant_sale_price_'.$varNb]).
			','.$db->Quote($data['variant_code_'.$varNb]).','.(int)$published.
			','.(int)$data['variant_availability_delay_'.$varNb].
			','.(int)$stockSubtract.
			','.(int)$data['variant_allow_order_'.$varNb].
			','.(int)$data['variant_min_stock_threshold_'.$varNb].
			','.(int)$data['variant_max_stock_threshold_'.$varNb].
			','.(int)$data['variant_min_quantity_'.$varNb].
			','.(int)$data['variant_max_quantity_'.$varNb].
			','.UtilityHelper::floatFormat($data['variant_weight_'.$varNb]).
			','.UtilityHelper::floatFormat($data['variant_length_'.$varNb]).
			','.UtilityHelper::floatFormat($data['variant_width_'.$varNb]).
			','.UtilityHelper::floatFormat($data['variant_height_'.$varNb]).','.(int)$data['variant_ordering_'.$varNb];

	// Now searches for the attributes linked to this variant.
	foreach($data as $k => $val) {
	  if(preg_match('#^variant_attribute_value_([0-9]+)_'.$varNb.'$#', $k, $matches)) {
	    $attribId = $matches[1];

	    // Checks for empty field.  
	    if(!empty($data['variant_attribute_value_'.$attribId.'_'.$varNb])) {
	      $value = $data['variant_attribute_value_'.$attribId.'_'.$varNb];

	      // Checks for multiselect.
	      if(is_array($value)) {
		$value = json_encode($value);
	      }

	      // Stores the variant attribute values to insert.
	      $attribValues[] = (int)$productId.','.(int)$varId.','.(int)$attribId.','.$db->Quote($value);
	    }
	  }
	}
      }
    }

    if(!empty($varValues)) {
      // Inserts a new row for each variant linked to the product.
      $columns = array('prod_id', 'var_id', 'name', 'stock',
		       'base_price', 'sale_price', 'code', 'published', 'availability_delay',
		       'stock_subtract', 'allow_order', 'min_stock_threshold', 'max_stock_threshold', 
		       'min_quantity', 'max_quantity', 'weight', 'length', 'width', 'height', 'ordering');
      $query->clear();
      $query->insert('#__ketshop_product_variant')
	    ->columns($columns)
	    ->values($varValues);
      $db->setQuery($query);
      $db->execute();

      $hasVariant = 1;

      if(!empty($attribValues)) {
	// Inserts a new row for each attribute linked to the product variants.
	$columns = array('prod_id', 'var_id', 'attrib_id', 'option_value');
	$query->clear();
	$query->insert('#__ketshop_var_attrib')
	      ->columns($columns)
	      ->values($attribValues);
	$db->setQuery($query);
	$db->execute();
      }
    }

    // Updates the number of variants.
    $query->clear();
    $query->update('#__ketshop_product')
	  ->set('nb_variants='.(int)count($varValues))
	  ->where('id='.(int)$productId);
    $db->setQuery($query);
    $db->execute();

    return;
  }


  //The aim of this Ajax function is to simulate the checking for an unique alias in the table file. 
  //This avoid the users to loose the attributes and images they've just set in case of
  //error (handle in tables/product.php).
  public function checkAlias($productId, $name, $alias) 
  {
    $return = 1;

    //Create a sanitized alias, (see stringURLSafe function for details).
    $alias = JFilterOutput::stringURLSafe($alias);
    //In case no alias has been defined, create a sanitized alias from the name field.
    if(empty($alias)) {
      $alias = JFilterOutput::stringURLSafe($name);
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Check for unique alias.
    $query->select('COUNT(*)')
	  ->from('#__ketshop_product')
	  ->where('alias='.$db->Quote($alias).' AND id!='.(int)$productId);
    $db->setQuery($query);

    if($db->loadResult()) {
      $return = 0;
    }

    return $return;
  }
}

