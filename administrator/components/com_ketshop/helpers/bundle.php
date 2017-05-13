<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class BundleHelper
{
  //Check if a given product is a part of one or more bundles.
  //Return an array filled with the bundle ids the product is linked to.
  public static function isBundleProduct($itemId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('bundle_id')
	  ->from('#__ketshop_prod_bundle')
	  ->where('prod_id='.(int)$itemId);
    $db->setQuery($query);

    return $db->loadColumn();
  }


  //Compute what is the stock_subtract or shippable state of a given bundle by checking
  //the value of each product of the bundle.
  public static function checkBundleState($fieldName, $itemId)
  {
    //Safe the function.
    if($fieldName !== 'stock_subtract' && $fieldName !== 'shippable') {
      return 0;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get stock_subtract or shippable flag value of each product of the bundle.
    $query->select($fieldName)
	  ->from('#__ketshop_prod_bundle')
	  ->join('INNER', '#__ketshop_product ON id=prod_id')
	  ->where('bundle_id='.(int)$itemId);
    $db->setQuery($query);
    $results = $db->loadRow();

    //Check results array. If one of the flags is set to 1, bundle should
    //set stock_subtract or shippable to "yes".
    if(in_array(1, $results)) {
      return 1;
    }

    return 0;
  }


  //Compute the stock value for a bundle.
  public static function getBundleStock($itemId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get stock and quantity of each bundle product which is stock
    //substracted.
    $query->select('quantity, stock')
	  ->from('#__ketshop_prod_bundle')
	  ->join('INNER', '#__ketshop_product ON id=prod_id')
	  ->where('bundle_id='.(int)$itemId.' AND stock_subtract=1')
	  ->order('prod_id');
    $db->setQuery($query);
    $products = $db->loadObjectList();

    //No product has been found.
    if(empty($products)) {
      return 0;
    }

    $bundleStock = $smaller = 0;
    //Compute the stock value for each product of the bundle.
    foreach($products as $product) {
      //The bundle is out of stock.
      if($product->stock < $product->quantity) {
	return 0;
      }

      //Calculate how many bundles are available according to the stock and
      //the quantity needed.
      $bundleStock = floor($product->stock / $product->quantity);

      //This is the first looping.
      if($smaller == 0) {
	$smaller = $bundleStock;
      }
      else {
	//Get the smallest number between the current one and $smaller which
	//contains the smallest number as far.
	//IMPORTANT: numbers into condition MUST be casted or the result will be
	//unpredictable.
	$bundleStock = ((int)$smaller <= (int)$bundleStock) ? $smaller : $bundleStock;
	$smaller = $bundleStock;
      }
    }

    return $bundleStock;
  }


  //Compute the availability delay of a bundle according to its products.
  public static function getBundleDelay($itemId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get availability delay of each product of the bundle.
    $query->select('availability_delay')
	  ->from('#__ketshop_prod_bundle')
	  ->join('INNER', '#__ketshop_product ON id=prod_id')
	  ->where('bundle_id='.(int)$itemId);
    $db->setQuery($query);
    $productDelays = $db->loadColumn();

    $bundleDelay = 0;
    //Get the highest product delay.
    foreach($productDelays as $productDelay) {
      if($productDelay > $bundleDelay) {
	$bundleDelay = $productDelay;
      }
    }

    return $bundleDelay;
  }


  //Update the bundle attributes which require a specific treatment.
  //Note: If no bundle id is given the function update all of the bundles.
  public static function updateBundle($fieldName, $bundleIds = array())
  {
    //Safe the function.
    if($fieldName !== 'stock' && $fieldName !== 'availability_delay' &&
       $fieldName !== 'stock_subtract' && $fieldName !== 'shippable' &&
       $fieldName !== 'all') {
      return;
    }

    //Put the field name into an array for more convenience.

    if($fieldName === 'all') {
      //All the attributes will be update.
      $fieldNames = array('stock', 'availability_delay', 'stock_subtract', 'shippable');
    }
    else {
      $fieldNames = array($fieldName);
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if(empty($bundleIds)) {
      //Get all of the bundle ids. 
      $query->select('id')
	    ->from('#__ketshop_product')
	    ->where('type="bundle"')
	    ->order('id');
      $db->setQuery($query);
      $bundleIds = $db->loadColumn();
    }

    //Build the CASE statement(s).
    $case = '';
    foreach($fieldNames as $fieldName) {
      $case .= $fieldName.' = CASE ';
      foreach($bundleIds as $bundleId) {
	//Get and set the value of the given attribute for the given bundle id.
	if($fieldName == 'stock') {
	  $updateValue = BundleHelper::getBundleStock($bundleId);
	}
	elseif($fieldName == 'availability_delay') {
	  $updateValue = BundleHelper::getBundleDelay($bundleId);
	}
	elseif($fieldName == 'stock_subtract') {
	  $updateValue = BundleHelper::checkBundleState('stock_subtract', $bundleId);
	}
	else { //shippable
	  $updateValue = BundleHelper::checkBundleState('shippable', $bundleId);
	}

	//Update the bundle row.
	$case .= 'WHEN id = '.$bundleId.' THEN '.$updateValue.' ';
      }
      //Close the CASE statement.
      $case .= ' ELSE '.$fieldName.' END, ';
    }

    //Remove both comma and space from the end of the string.
    $case = substr($case, 0, -2);

    $query->clear();
    $query->update('#__ketshop_product')
	  //Update all the given bundles at once thanks to the CASE WHEN structure.
	  ->set($case)
	  ->where('id IN('.implode(',', $bundleIds).')');
    $db->setQuery($query);
    $db->query();

    return;
  }


  /**
   * Returns the products contained in the given bundles. 
   * Note: The function checks for duplicate products.
   *
   * @param array  The id and quantity of the bundles. The bundle's id is set as the array's key
   *               (ie: array[id] => quantity)
   *
   * @return array  The products contained in the given bundles. 
   */
  public static function getBundleProducts($bundleData)
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
        //Note: For now a product option cannot be part of a bundle, but the opt_id attribute is
	//      required in the updateStock function. 
	$bundleProduct['opt_id'] = 0;
	//Update the product quantity (ie: multiplied it with the quantity of the bundle itself).
	$bundleProduct['quantity'] = $bundleProduct['quantity'] * $bundleData[$bundleProduct['bundle_id']];
	//The bundle ids are needed in the updateStock function.
	$bundleProduct['bundle_ids'] = $bundleIds;
	//Store the product with the set attributes.
	$products[$bundleProduct['id']] = $bundleProduct;
      }
      else { //The product is already into one of the given bundle.
        //Just update the quantity for this product.
	$products[$bundleProduct['id']]['quantity'] += $bundleProduct['quantity'] * $bundleData[$bundleProduct['bundle_id']];
      }
    }

    return $products;
  }
}


