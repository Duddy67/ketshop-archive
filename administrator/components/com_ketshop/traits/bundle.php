<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

/**
 * Provides some utility functions relating to bundles.
 */

trait BundleTrait
{
  /**
   * Checks if a given product is a part of one or more bundles.
   * Return an array filled with the bundle ids to which the product is linked to.
   *
   * @param   integer  $productId	The id of the product to check.
   *
   * @return  array  			The bundle ids to which the product is linked to.
   */
  public function isBundleProduct($productId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('bundle_id')
	  ->from('#__ketshop_prod_bundle')
	  ->where('prod_id='.(int)$productId);
    $db->setQuery($query);

    return $db->loadColumn();
  }


  /**
   * Computes the stock value of a given bundle according to the linked products.
   *
   * @param   integer  $bundleId	The id of the bundle.
   *
   * @return  integer  			The stock value.
   */
  public function getBundleStock($bundleId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Gets stock and quantity of each bundle product which is substracted from stock.
    $query->select('pb.quantity, pv.stock')
	  ->from('#__ketshop_prod_bundle AS pb')
	  ->join('INNER', '#__ketshop_product_variant AS pv ON pv.prod_id=pb.prod_id AND pv.var_id=pb.var_id')
	  ->where('pb.bundle_id='.(int)$bundleId)
	  ->where('pv.stock_subtract=1')
	  ->order('pv.prod_id');
    $db->setQuery($query);
    $products = $db->loadObjectList();

    // All the bundle products are not substracted from stock.
    // The stock is then considerated as infinite.
    if(empty($products)) {
      return 0;
    }

    $bundleStock = $smaller = 0;
    // Computes the stock value for each product of the bundle.
    foreach($products as $product) {
      $productStock = $product->stock;

      // The bundle is out of stock.
      if($productStock < $product->quantity) {
	return 0;
      }

      // Calculates how many bundles are available according to the stock and
      // the quantity needed.
      $bundleStock = floor($productStock / $product->quantity);

      // This is the first looping.
      if($smaller == 0) {
	$smaller = $bundleStock;
      }
      else {
	// Gets the smallest number between the current one and $smaller which
	// contains the smallest number as far.
	// IMPORTANT: numbers into condition MUST be casted or the result will be
	// unpredictable.
	$bundleStock = ((int)$smaller <= (int)$bundleStock) ? $smaller : $bundleStock;
	$smaller = $bundleStock;
      }
    }

    return $bundleStock;
  }


  /**
   * Computes the availability delay of a bundle according to the linked products.
   *
   * @param   integer  $bundleId	The id of the bundle.
   *
   * @return  integer  			The availability delay value.
   */
  public function getBundleDelay($bundleId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Gets the availability delay of each product of the bundle.
    $query->select('availability_delay')
	  ->from('#__ketshop_prod_bundle AS pb')
	  ->join('INNER', '#__ketshop_product_variant AS pv ON pv.prod_id=pb.prod_id AND pv.var_id=pb.var_id')
	  ->where('bundle_id='.(int)$bundleId);
    $db->setQuery($query);
    $productDelays = $db->loadColumn();

    $bundleDelay = 0;
    // Gets the highest product delay.
    foreach($productDelays as $productDelay) {
      if($productDelay > $bundleDelay) {
	$bundleDelay = $productDelay;
      }
    }

    return $bundleDelay;
  }


  /**
   * Determine what are the yes/no value of the stock_subtract and shippable fields of a given
   * bundle according to its products. 
   *
   * @param  integer $bundleId	 The id of the bundle. 
   * @param  string  $fieldName	 The name of the field to check. 
   *
   * @return integer
   */
  public function getReadOnlyYesNoValues($bundleId, $fieldName)
  {
    // Safes the function.
    if($fieldName !== 'stock_subtract' && $fieldName !== 'shippable') {
      return 0;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    // Gets the stock_subtract and shippable attributes of each bundle product.
    $query->select('pv.stock_subtract, p.shippable')
	  ->from('#__ketshop_prod_bundle AS pb')
	  ->join('INNER', '#__ketshop_product_variant AS pv ON pv.prod_id=pb.prod_id AND pv.var_id=pb.var_id')
	  ->join('INNER', '#__ketshop_product AS p ON p.id=pb.prod_id')
	  ->where('pb.bundle_id='.(int)$bundleId);
    $db->setQuery($query);
    $fields = $db->loadAssocList();

    foreach($fields as $field) {
      if((int)$field[$fieldName]) {
	// As soon as a product from the bundle is substracted from the stock or is
	// shippable, this value type of the bundle must be set to 1 (ie: yes).
	return 1;
      }
    }

    return 0;
  }


  /**
   * Updates some bundle attributes which require a specific treatment.
   *
   * @param  integer $bundleId	 The id of the bundle to update. 
   *
   * @return void
   */
  public function updateBundle($bundleId)
  {
    $this->updateBundles(array($bundleId));
  }


  /**
   * Updates some bundle attributes which require a specific treatment.
   * N.B: If no bundle id is given the function updates all of the bundles.
   *
   * @param  array $bundleIds	The ids of the bundles to update. 
   *
   * @return void
   */
  public function updateBundles($bundleIds = array())
  {
    // The bundle fields to update.
    $fieldNames = array('stock', 'availability_delay', 'stock_subtract', 'shippable');

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if(empty($bundleIds)) {
      // Gets all of the bundle ids. 
      $query->select('id')
	    ->from('#__ketshop_product')
	    ->where('type="bundle"')
	    ->order('id');
      $db->setQuery($query);
      $bundleIds = $db->loadColumn();
    }

    // Builds the CASE statements for each table.
    $cases = array('product' => '', 'product_variant' => '');

    foreach($fieldNames as $fieldName) {
      if($fieldName == 'shippable') {
	$cases['product'] .= 'shippable = CASE ';
      }
      else {
	$cases['product_variant'] .= $fieldName.' = CASE ';
      }

      foreach($bundleIds as $bundleId) {
	// Gets and set the value of the given attribute for the given bundle id.

	if($fieldName == 'stock') {
	  // In case the product is not subtracted from the stock.
	  $updatedValue = 0;

	  if($this->getReadOnlyYesNoValues($bundleId, 'stock_subtract')) { 
	    $updatedValue = $this->getBundleStock($bundleId);
	  }
	}
	elseif($fieldName == 'availability_delay') {
	  $updatedValue = $this->getBundleDelay($bundleId);
	}
	elseif($fieldName == 'stock_subtract') {
	  $updatedValue = $this->getReadOnlyYesNoValues($bundleId, 'stock_subtract'); 
	}
	// shippable
	else { 
	  $updatedValue = $this->getReadOnlyYesNoValues($bundleId, 'shippable'); 
	  // The shippable attribute lies in the product table.
	  $cases['product'] .= 'WHEN id = '.$bundleId.' THEN '.$updatedValue.' ';
	}

	if($fieldName != 'shippable') {
	  // Updates the bundle row.
	  $cases['product_variant'] .= 'WHEN prod_id = '.$bundleId.' THEN '.$updatedValue.' ';
	}
      }

      // Closes the CASE statements.

      if($fieldName == 'shippable') {
	$cases['product'] .= ' ELSE shippable END, ';
      }
      else {
	$cases['product_variant'] .= ' ELSE '.$fieldName.' END, ';
      }
    }

    foreach($cases as $key => $case) { 
      // Removes both comma and space from the end of the string.
      $case = substr($case, 0, -2);

      // Sets the primary key according to the table to update.
      $pk = 'prod_id';
      if($key == 'product') {
	$pk = 'id';
      }

      // Updates the bundle attributes in both #__ketshop_product_variant and #__ketshop_product tables.
      // N.B: No need to mention the var_id in the ketshop_product_variant table as the
      //      variant is unique (and always set to 1) when it comes to  a bundle product.
      $query->clear();
      $query->update('#__ketshop_'.$key)
	    ->set($case)
	    ->where($pk.' IN('.implode(',', $bundleIds).')');
      $db->setQuery($query);
      $db->query();
    }
  }


  /**
   * Returns the products contained in a given bundle. 
   *
   * @param integer $productId	 The id of the bundle (Note: A bundle is a product containing products).
   *
   * @return array  		 The products contained in the given bundle. 
   */
  public function getBundleProducts($productId) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('prod_id AS product_id, var_id, name AS product_name, quantity, stock') 
	  ->from('#__ketshop_prod_bundle')
	  ->join('INNER', '#__ketshop_product ON id=prod_id')
	  ->where('bundle_id='.$productId)
	  ->order('name');
    $db->setQuery($query);

    return $db->loadAssocList();
  }


  /**
   * Returns the products contained in the given bundles. 
   * Note: The function checks for duplicate products and sets their quantity accordingly.
   *
   * @param array  The id and quantity of the bundles. The bundle's id is set as the array's key
   *               (ie: array[id] => quantity)
   *
   * @return array  The products contained in the given bundles. 
   */
  //public function getBundleProducts($bundleData)
  public function getProductsOfBundles($bundleData)
  {
    $ids = implode(',', array_keys($bundleData));

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('prod_id AS id, bundle_id, quantity, stock_subtract, type')
	  ->from('#__ketshop_prod_bundle')
	  ->join('LEFT', '#__ketshop_product ON id=prod_id')
	  ->where('bundle_id IN('.$ids.')');
    $db->setQuery($query);
    $bundleProducts = $db->loadAssocList();

    $bundleIds = explode(',', $ids);
    //Store all of the products of the given bundles into a product array.
    $products = array();
    foreach($bundleProducts as $bundleProduct) {
      if(!array_key_exists($bundleProduct['id'], $products)) {
        //Note: For now a product variant cannot be part of a bundle, but the var_id attribute is
	//      required in the updateStock function. 
	$bundleProduct['var_id'] = 0;
	//Update the product quantity (ie: multiplied it with the quantity of the bundle itself).
	$bundleProduct['quantity'] = $bundleProduct['quantity'] * $bundleData[$bundleProduct['bundle_id']];
	//The bundle ids are needed in the updateStock function.
	$bundleProduct['bundle_ids'] = $bundleIds;
	//Store the product with the set attributes.
	$products[$bundleProduct['id']] = $bundleProduct;
      }
      else { //The product is already into one of the given bundles.
        //Just update the quantity for this product.
	$products[$bundleProduct['id']]['quantity'] += $bundleProduct['quantity'] * $bundleData[$bundleProduct['bundle_id']];
      }
    }

    return $products;
  }
}


