<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

/**
 * Provides some utility functions relating to items linked to a product as attributes,
 * images and so on. 
 *
 */

trait ProductTrait
{

  /**
   * Returns the attributes bound to a given product.  
   *
   * @param   integer  $productId  The id of the product.
   *
   * @return  array    An array of attributes or an empty array.
   */
  public function getAttributeData($productId) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Fetches the option values of the attributes linked to this product.
    $query->select('attrib_id, option_value')
	  ->from('#__ketshop_prod_attrib')
	  ->where('prod_id='.(int)$productId)
	  ->order('attrib_id');
    $db->setQuery($query);
    $results = $db->loadAssocList();

    $attributes = array();

    foreach($results as $data) {
      //Gets and sets the attribute.
      $attribute = $this->getAttribute($data['attrib_id']);

      $attribute = $this->setAttribute($attribute, $data);
      $attributes[] = $attribute;
    }

    return $attributes;
  }


  /**
   * Sets the options of a given attribute.
   *
   * @param   array	The attribute to set.
   * @param   array	The attribute data.
   *
   * @return  array	The set attribute.
   */
  protected function setAttribute($attribute, $data) 
  {
    //Checks for multiselect values.
    if(substr($data['option_value'], 0, 1) === '[') {
      //Gets the multiselect array.
      $values = json_decode($data['option_value'], true);
    }
    else {
      //Single values are put into an array for more convenience.
      $values = array($data['option_value']);
    }

    foreach($attribute['options'] as $key => $option) {
      //Sets the selected option(s).
      if(in_array($option['option_value'], $values)) {
	$attribute['options'][$key]['selected'] = ' selected="selected"';
      }
    }

    return $attribute;
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
    //Gets the variants bound to the given product.
    $query->select('var_id, variant_name, base_price, sale_price, sales, code, stock,'.
		   'availability_delay, weight, length, width, height, published') 
	  ->from('#__ketshop_product_variant')
	  ->where('prod_id='.$productId)
	  ->order('ordering');
    $db->setQuery($query);
    $variants = $db->loadAssocList();

    if(!empty($variants)) {
      //Fetches the option values of the variant attributes linked to the given product.
      $query->clear();
      $query->select('var_id, attrib_id, option_value') 
	    ->from('#__ketshop_var_attrib')
	    ->where('prod_id='.$productId)
	    ->order('var_id');
      $db->setQuery($query);
      $results = $db->loadAssocList();

      $config = JComponentHelper::getParams('com_ketshop');
      //Gets the attributes linked to the product (in the attributes tab).
      //Note: These attributes are unset (ie: they have no option selected).
      $attributes = $this->getProductAttributes($productId);

      //Sets and stores the attributes linked to the variant.
      foreach($variants as $key => $variant) {
	//Adds the unset attributes to the variant.
	/*$variants[$key]['attributes'] = $attributes;

	foreach($results as $data) {
	  //The attribute is bound to the current variant.
	  if($data['var_id'] == $variant['var_id']) {
	    //Loops through the unset variant attributes and sets their option values.
	    foreach($variants[$key]['attributes'] as $k => $attribute) {
	      if($data['attrib_id'] == $attribute['attribute_id']) {
		$variants[$key]['attributes'][$k] = $this->setAttribute($attribute, $data);
	      }
	    }
	  }
	}*/

	//Format some numerical values.
	$variants[$key]['weight'] = UtilityHelper::formatNumber($variants[$key]['weight']);
	$variants[$key]['length'] = UtilityHelper::formatNumber($variants[$key]['length']);
	$variants[$key]['width'] = UtilityHelper::formatNumber($variants[$key]['width']);
	$variants[$key]['height'] = UtilityHelper::formatNumber($variants[$key]['height']);
	$variants[$key]['base_price'] = UtilityHelper::formatNumber($variants[$key]['base_price'], $config->get('digits_precision'));
	$variants[$key]['sale_price'] = UtilityHelper::formatNumber($variants[$key]['sale_price'], $config->get('digits_precision'));
      }
    }

    return $variants;
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


  /**
   * Returns the attributes bound to a given product.  
   *
   * @param   integer  $productId  The id of the product.
   *
   * @return  array	    An array of attributes or an empty array.
   */
  public function getProductAttributes($productId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //
    $query->select('attrib_id AS attribute_id, option_value AS selected_option, name AS attribute_name')
	  ->from('#__ketshop_prod_attrib')
	  ->join('LEFT', '#__ketshop_attribute ON id=attrib_id')
	  ->where('prod_id='.(int)$productId);
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
   * @param   array    $data		The POST array in which the variant data is stored.
   *
   * @return  void
   */
  public function setProductVariants($productId, $data)
  {
    $variants = $varIds = $varValues = $attribValues = array();
    $isEmpty = true;

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //First check if some variants have been set and store all the id of the already 
    //existing variants (ie: which are not new).
    foreach($data as $key => $value) {
      if(preg_match('#^variant_id_([0-9]+)$#', $key)) {
	if((int)$value) { //Variant already exists as an id has been set.
	  $varIds[] = $value;
	}

        //One or more variants have been set.
	$isEmpty = false;
      }
    }

    //First delete all the previous variants linked to the product.
    $query->delete('#__ketshop_product_variant')
	  ->where('prod_id='.(int)$productId);
    $db->setQuery($query);
    $db->execute();

    //Same for the previous attributes linked to the variants.
    $query->clear();
    $query->delete('#__ketshop_var_attrib')
	  ->where('prod_id='.(int)$productId);
    $db->setQuery($query);
    $db->execute();

    if($isEmpty) {
      //Resets the variant values.
      $fields = array('has_variants=0', 'variant_name=""'); 
      $query->clear();
      $query->update('#__ketshop_product')
	    ->set($fields)
	    ->where('id='.(int)$productId);
      $db->setQuery($query);
      $db->execute();

      //No need to go further.
      return;
    }

    foreach($data as $key => $value) {
      if(preg_match('#^variant_id_([0-9]+)$#', $key, $matches)) {
	$varNb = $matches[1];
	$varId = $data['variant_id_'.$varNb];

	//Variant is new. Generates a unique variant id.
	if(!$varId) {
	  $varId = 1;

	  while(in_array($varId, $varIds)) {
	    $varId++;
	  }

          //Store the new id.
	  $varIds[] = $varId;
	}

	//Store values to insert.
	$varValues[] = (int)$productId.','.(int)$varId.','.$db->Quote($data['variant_name_'.$varNb]).','.(int)$data['stock_'.$varNb].
			','.$data['base_price_'.$varNb].','.$data['sale_price_'.$varNb].','.$db->Quote($data['code_'.$varNb]).
			','.$db->Quote($data['published_'.$varNb]).','.(int)$data['availability_delay_'.$varNb].
			','.$data['weight_'.$varNb].','.$data['length_'.$varNb].','.$data['width_'.$varNb].
			','.$data['height_'.$varNb].','.$data['ordering_'.$varNb];

	//Now search for the attributes linked to this variant.
	foreach($data as $k => $val) {
	  if(preg_match('#^attribute_value_'.$varNb.'_([0-9]+)$#', $k, $matches)) {
	    $attribId = $matches[1];

	    //Check first for empty field.  
	    if(isset($data['attribute_value_'.$varNb.'_'.$attribId]) &&
	       !empty($data['attribute_value_'.$varNb.'_'.$attribId])) {
	      $value = $data['attribute_value_'.$varNb.'_'.$attribId];

	      //Checks for multiselect.
	      if(is_array($value)) {
		$value = json_encode($value);
	      }
	    }

	    //Stores the values to insert.
	    $attribValues[] = (int)$productId.','.(int)$varId.','.(int)$attribId.','.$db->Quote($value);
	  }
	}
      }
    }

    //Insert a new row for each variant linked to the product.
    $columns = array('prod_id', 'var_id', 'variant_name', 'stock',
		     'base_price', 'sale_price', 'code', 'published', 'availability_delay',
		     'weight', 'length', 'width', 'height', 'ordering');
    $query->clear();
    $query->insert('#__ketshop_product_variant')
	  ->columns($columns)
	  ->values($varValues);
    $db->setQuery($query);
    $db->execute();

    if(!empty($attribValues)) {
      //Insert a new row for each attribute linked to the product variants.
      $columns = array('prod_id', 'var_id', 'attrib_id', 'option_value');
      $query->clear();
      $query->insert('#__ketshop_var_attrib')
	    ->columns($columns)
	    ->values($attribValues);
      $db->setQuery($query);
      $db->execute();
    }

    $query->clear();
    $query->update('#__ketshop_product')
	  ->set('has_variants=1')
	  ->where('id='.(int)$productId);
    $db->setQuery($query);
    $db->execute();

    return;
  }
}

