<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class UtilityHelper
{
  //Return the admin default language which is considered as the shop default
  //language.
  public static function getLanguage($tag = false) 
  {
    //Get the default language code of the admin.
    $params = JComponentHelper::getParams('com_languages');
    $langTag = $params->get('administrator');

    //Get the xml file path then parse it to get the language name.
    $file = JPATH_BASE.'/language/'.$langTag.'/'.$langTag.'.xml';
    $info = JApplicationHelper::parseXMLLangMetaFile($file);
    $langName = $info['name'];

    if($tag) {
      return $langTag;
    }

    //In case the xml parse has failed we display the language code.
    if(empty($langName)) {
      return $langTag;
    }
    else {
      return $langName;
    }
  }


  //Return the requested currency or the currency set by default for 
  //the shop if the id argument is not defined.
  public static function getCurrency($currencyCode = 0) 
  {
    $config = JComponentHelper::getParams('com_ketshop');
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if(!$currencyCode) { //Get the required currency.
      $currencyCode = $config->get('currency_code');
    }

    $query->select('alpha,symbol')
	  ->from('#__ketshop_currency')
	  ->where('alpha='.$db->quote($currencyCode));
    $db->setQuery($query);
    $currency = $db->loadObject();

    //Return currency in the correct display.
    if($config->get('currency_display') == 'symbol') {
      return $currency->symbol;
    }

    return $currency->alpha;
  }


  public static function getPriceWithTaxes($price, $taxRate)
  {
    if($price == 0 || $taxRate == 0) {
      return $price;
    }

    $taxValue = $price * ($taxRate / 100);
    $priceWithTaxes = $price + $taxValue;

    return $priceWithTaxes;
  }


  //Extract a given tax rate from a price.
  //In order to achieve this we use the following formula:
  //Example 1: For a given tax rate of 19.6 % an a price with taxes of 15 €
  //           Price without taxes: 15/1.196 = 12.54 €
  //
  //Example 2: For a given tax rate of 5.5 % an a price with taxes of 15 €
  //           Price without taxes: 15/1.055 = 14.22 €
  //
  //Source: http://vosdroits.service-public.fr/professionnels-entreprises/F24271.xhtml
  public static function getPriceWithoutTaxes($price, $taxRate)
  {
    if($price == 0 || $taxRate == 0) {
      return $price;
    }

    $dotPosition = strpos($taxRate, '.');
    $dotlessNb = preg_replace('#\.#', '', $taxRate);

    if($dotPosition == 1) {
      $divisor = '1.0'.$dotlessNb;
    }
    else { //$dotPosition == 2
      $divisor = '1.'.$dotlessNb;
    }

    //Retrieve product price without taxes.
    $priceWithoutTaxes = $price / $divisor;

    return $priceWithoutTaxes;
  }


  public static function roundNumber($float, $roundingRule = 'down', $digitPrecision = 2)
  {
    //In case variable passed in argument is undefined.
    if($float == '') {
      return 0;
    }

    switch($roundingRule) {
      case 'up':
	return round($float, $digitPrecision, PHP_ROUND_HALF_UP);

      case 'down':
	return round($float, $digitPrecision, PHP_ROUND_HALF_DOWN);

     default: //Unknown value.
	return $float;
    }
  }


  public static function formatNumber($float, $digits = 2)
  {
    //In case variable passed in argument is undefined.
    if($float == '') {
      return 0;
    }

    if(preg_match('#^-?[0-9]+\.[0-9]{'.$digits.'}#', $float, $matches)) {
      $formatedNumber = $matches[0]; 
    }
    else { //In case float number is truncated (for instance: 18.5 or 18).
      $dot = $padding = '';
      //Dot is added if there's only the left part of the float. 
      if(!preg_match('#\.#', $float)) {
	$missingDigits = $digits;
	$dot = '.';
      }

      //Compute how many digits are missing.
      if(preg_match('#^-?[0-9]+\.([0-9]+)#', $float, $matches)) {
	$missingDigits =  $digits - strlen($matches[1]);
      }

      //Replace missing digits with zeros. 
      for($i = 0; $i < $missingDigits; $i++) {
	$padding .= '0';
      }

      $formatedNumber = $float.$dot.$padding;
    }

    return $formatedNumber;
  }


  public static function formatPriceRule($operation, $value, $currencyId = 0)
  {
    //Price rule operation is expressed as a percentage (-% or +%).
    if(preg_match('#(-|\+)%$#', $operation, $matches)) {
      //Return the price rule operation well formatted, (eg: -10 %)
      return $matches[1][0].UtilityHelper::formatNumber($value).' %';
    }

    //Price rule operation is expressed as an absolute value.

    //Get the currency and return the price rule operation well
    //formatted, (eg: -30 USD)
    $currency = UtilityHelper::getCurrency($currencyId);

    return $operation.UtilityHelper::formatNumber($value).' '.$currency;
  }


  //Compare 2 strings by taking account the encoding.
  public static function mbStrcasecmp($str1, $str2, $encoding = null)
  {
    if(is_null($encoding)) {
      $encoding = mb_internal_encoding();
    }

    //Take advantage of a multibyte string function to use encoding.
    return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
  }


  //Create and return a INSERT or UPDATE query according to the given arguments.
  //The choice of the query to use allows to manage an address history.
  public static function getAddressQuery($data, $type, $itemType, $itemId)
  {
    //A suffix might be used.
    $suffix = '';

    //A suffix is needed when we deal with a customer address.
    if($itemType == 'customer') {
      //Create the proper suffix according to the type.
      $suffix = '_sh';
      if($type == 'billing') {
	$suffix = '_bi';
      }
    }

    //Remove possible spaces.
    foreach($data as $key => $value) {
      //Replace all contiguous space characters with one space character.
      $value = preg_replace('#\s{2,}#', ' ', $value);
      //Remove space characters before and after the string.
      $data[$key] = trim($value);
    }

    //Get the last address set by the customer. 
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id, street, city, postcode, region_code, country_code')
	  ->from('#__ketshop_address')
	  ->where('type='.$db->Quote($type).' AND item_type='.$db->Quote($itemType).' AND item_id='.$itemId)
	  ->order('created DESC')
	  ->setLimit(1);
    $db->setQuery($query);
    $address = $db->loadAssoc();

    //Get the database encoding.
    //TODO: Figure out how to set this query with the JDatabaseQuery class.
    $db->setQuery('SHOW VARIABLES LIKE "character_set_database"');
    $result = $db->loadObject();
    $encoding = $result->Value;

    //Get the continent code for the shipping address according to the chosen country.
    if(!empty($data['country_code'.$suffix])) {
      $query->clear();
      $query->select('continent_code')
	    ->from('#__ketshop_country')
	    ->where('alpha_2='.$db->Quote($data['country_code'.$suffix]));
      $db->setQuery($query);
      $continentCode = $db->loadResult();
    }

    //Run the test.

    //One or more address rows have been previouly stored.
    if(!is_null($address)) {
      //If street, city, postcode region and country fields are equal to
      //their equivalent in database, we assume that the customer has still the same address.
      //So we just update data. 
      if(!UtilityHelper::mbStrcasecmp($address['street'], $data['street'.$suffix], $encoding) &&
	 !UtilityHelper::mbStrcasecmp($address['city'], $data['city'.$suffix], $encoding) &&
	 !UtilityHelper::mbStrcasecmp($address['postcode'], $data['postcode'.$suffix], $encoding) && 
	 $address['region_code'] === $data['region_code'.$suffix] && 
	 $address['country_code'] === $data['country_code'.$suffix])
      {
	$fields = array('street='.$db->Quote($data['street'.$suffix]),
			'city='.$db->Quote($data['city'.$suffix]),
			'postcode='.$db->Quote($data['postcode'.$suffix]),
			'region_code='.$db->Quote($data['region_code'.$suffix]),
			'country_code='.$db->Quote($data['country_code'.$suffix]),
			'continent_code='.$db->Quote($continentCode),
			'note='.$db->Quote($data['note'.$suffix]),
			'phone='.$db->Quote($data['phone'.$suffix]));

	$query->clear();
	$query->update('#__ketshop_address')
	      ->set($fields)
	      ->where('id='.(int)$address['id']);

	return $query;
      }
    }

    //In all other cases a new address row must be inserted.

    //Get current date and time (equal to NOW() in SQL).
    //A date stamp allows to keep an address history.
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    $columns = array('item_id','street','city','region_code','postcode',
		     'phone','country_code','continent_code','type',
		     'item_type','created','note');
    $query->clear();
    $query->insert('#__ketshop_address')
	  ->columns($columns)
	  ->values($itemId.','.$db->Quote($data['street'.$suffix]).','.$db->Quote($data['city'.$suffix]).','.
		   $db->Quote($data['region_code'.$suffix]).','.$db->Quote($data['postcode'.$suffix]).','.
		   $db->Quote($data['phone'.$suffix]).','.$db->Quote($data['country_code'.$suffix]).','.$db->Quote($continentCode).','.
		   $db->Quote($type).','.$db->Quote($itemType).','.$db->Quote($now).','.$db->Quote($data['note'.$suffix]));

    return $query;
  }
}


