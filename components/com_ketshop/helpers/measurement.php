<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.


class MeasurementHelper
{
  //Return the total weight of all the products within the cart.
  //If it's set, the volumetric weight is computed.
  public static function getTotalWeight($defaultUnit = '')
  {
    //Grab the user session.
    $session = JFactory::getSession();
    //Get the cart and the number of products it contains.
    $cart = $session->get('cart', array(), 'ketshop'); 
    $settings = $session->get('settings', array(), 'ketshop'); 

    //If the default unit parameter is unset we use the default unit set for the shop.
    if(empty($defaultUnit)) {
      $defaultUnit = $settings['shipping_weight_unit'];
    }

    //Check if the volumetric weight should be used.
    $volumetricWeight = $settings['volumetric_weight'];

    $totalWeight = 0;
    //Compute the total weight.
    foreach($cart as $product) {
      //Only shippable products are taking into account.
      if($product['shippable']) {
	//Make sure that all the dimensions are correctly set before computing the volumetric weight.
	if($volumetricWeight && $product['length'] && $product['width'] && $product['height']) {
	  //Get the volumetric weight (into kilogram).
	  $vlmtWeight = MeasurementHelper::getVolumetricWeight($product['length'], $product['width'],
							     $product['height'], $product['dimensions_unit']);

	  //Get the normal weight of the product converted into kilogram.
	  $weight = MeasurementHelper::weightConverter($product['weight'],
							  $product['weight_unit'], 'kg');
	  //Compare volumetric weight to weight. If volumetric weight is greater
	  //we use it, if it's not we use normal weight.
	  if($vlmtWeight > $weight) {
	    $prodWeight = $vlmtWeight;
	  }
	  else {
	    $prodWeight = $weight;
	  }
	}
	else { //Standard calculation.
	  //Means that dimensions have not been correctly set for product
	  //so we get the product weight converted into kilogram.
	  if($volumetricWeight) {
	    $prodWeight = MeasurementHelper::weightConverter($product['weight'],
							      $product['weight_unit'], 'kg');
	  }
	  else { //Volumetric weight is not flaged, just get the product weight converted into unit by default.
	    $prodWeight = MeasurementHelper::weightConverter($product['weight'],
							      $product['weight_unit'], $defaultUnit);
	  }
	}

	//Store the product weight taking into account its quantity
	$totalWeight += $prodWeight * $product['quantity'];
      }
    }

    //Since the volumetric weight is into kilogram, we must convert it into the
    //weight unit by default.
    if($volumetricWeight) {
      $totalWeight = MeasurementHelper::weightConverter($totalWeight, 'kg', $defaultUnit);
    }

    return $totalWeight;
  }


  public static function weightConverter($value, $unit, $unitOutput)
  {
    //Check parameters before start the convertion.
    if($unit === $unitOutput || $value === 0) {
      return $value;
    }

    $result = 0;

    switch($unitOutput) {
      case 'mg' :
	if($unit === 'g') {
	  $result = $value * 1000;
	}

	if($unit === 'kg') {
	  $result = $value * 1000000;
	}

	if($unit === 'lb') {
	  $result = $value * 453592.370000;
	}

	if($unit === 'oz') {
	  $result = $value * 28349;
	}

	break;

      case 'g' :
	if($unit === 'mg') {
	  $result = $value / 1000;
	}

	if($unit === 'kg') {
	  $result = $value * 1000;
	}

	if($unit === 'lb') {
	  $result = $value * 453.592370;
	}

	if($unit === 'oz') {
	  $result = $value * 28.349000;
	}

	break;

      case 'kg' :
	if($unit === 'mg') {
	  $result = $value / 1000000;
	}

	if($unit === 'g') {
	  $result = $value / 1000;
	}

	if($unit === 'lb') {
	  $result = $value * 0.453592;  //(0.45359237) Not enought float numbers. Rounded.
	}

	if($unit === 'oz') {
	  $result = $value * 0.028349;
	}

	break;

      case 'lb' :
	if($unit === 'mg') {
	  $result = $value * 0.000002;
	}

	if($unit === 'g') {
	  $result = $value * 0.002204;
	}

	if($unit === 'kg') {
	  $result = $value * 2.204622;
	}

	if($unit === 'oz') {
	  $result = $value * 0.624988;
	}

	break;

      case 'oz' :
	if($unit === 'mg') {
	  $result = $value * 0.000035;
	}

	if($unit === 'g') {
	  $result = $value * 0.035274;
	}

	if($unit === 'kg') {
	  $result = $value * 35.274612;
	}

	if($unit === 'lb') {
	  $result = $value * 16.000295;
	}

	break;
    }

    return $result;
  }


  public static function dimensionConverter($value, $unit, $unitOutput)
  {
    //Check parameters before start the convertion.
    if($unit === $unitOutput || $value === 0) {
      return $value;
    }

    $result = 0;

    switch($unitOutput) {
      case 'mm' :
	if($unit === 'cm') {
	  $result = $value * 10;
	}

	if($unit === 'm') {
	  $result = $value * 1000;
	}

	break;

      case 'cm' :
	if($unit === 'mm') {
	  $result = $value / 10;
	}

	if($unit === 'm') {
	  $result = $value * 100;
	}

	break;

      case 'm' :
	if($unit === 'mm') {
	  $result = $value / 1000;
	}

	if($unit === 'cm') {
	  $result = $value / 100;
	}

	break;
    }

    return $result;
  }


  //Return the volumetric weight of a product.
  public static function getVolumetricWeight($length, $width, $height, $unit)
  {
    //We need dimensions in centimeters.
    if($unit !== 'cm') {
      $length = MeasurementHelper::dimensionConverter($length, $unit, 'cm'); 
      $width = MeasurementHelper::dimensionConverter($width, $unit, 'cm'); 
      $height = MeasurementHelper::dimensionConverter($height, $unit, 'cm'); 
    }

    //Compute the volume (cm3).
    $volume = $length * $width * $height;
    //Get the global settings.
    $session = JFactory::getSession();
    $settings = $session->get('settings', array(), 'ketshop'); 
    //Get the volumetric ratio set for the shop. 
    $volumetricRatio = $settings['volumetric_ratio'];

    //Return the volumetric weight.
    return $volume / $volumetricRatio;
  }
}


