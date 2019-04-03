<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class JavascriptHelper
{
  //Functions which define all required language variables in order to be
  //used in Javascript throught the Joomla.JText._() method. 
  public static function getButtonText() 
  {
    JText::script('COM_KETSHOP_BUTTON_ADD_LABEL'); 
    JText::script('COM_KETSHOP_BUTTON_SELECT_LABEL'); 
    JText::script('COM_KETSHOP_BUTTON_REMOVE_LABEL'); 

    return;
  }


  public static function getCommonText() 
  {
    JText::script('COM_KETSHOP_ITEM_NAME_LABEL'); 
    JText::script('COM_KETSHOP_ITEM_NAME_TITLE'); 
    JText::script('COM_KETSHOP_ITEM_COST_LABEL'); 
    JText::script('COM_KETSHOP_ITEM_COST_TITLE'); 
    JText::script('COM_KETSHOP_ITEM_QUANTITY_LABEL'); 
    JText::script('COM_KETSHOP_ITEM_QUANTITY_TITLE'); 
    JText::script('COM_KETSHOP_ITEM_AMOUNT_LABEL'); 
    JText::script('COM_KETSHOP_ITEM_AMOUNT_TITLE'); 
    JText::script('COM_KETSHOP_OPTION_SELECT'); 
    JText::script('COM_KETSHOP_ERROR_EMPTY_VALUE'); 
    JText::script('COM_KETSHOP_ERROR_INCORRECT_OR_EMPTY_VALUE'); 
    JText::script('COM_KETSHOP_ERROR_INCORRECT_VALUE_TYPE'); 
    JText::script('COM_KETSHOP_EXPECTED_VALUE_TYPE'); 
    JText::script('COM_KETSHOP_YESNO_0'); 
    JText::script('COM_KETSHOP_YESNO_1'); 
    JText::script('COM_KETSHOP_ORDERING_LABEL'); 
    JText::script('COM_KETSHOP_ORDERING_TITLE'); 
    JText::script('COM_KETSHOP_VALUE_LABEL'); 
    JText::script('COM_KETSHOP_VALUE_TITLE'); 
    JText::script('COM_KETSHOP_TEXT_LABEL'); 
    JText::script('COM_KETSHOP_TEXT_TITLE'); 
    JText::script('COM_KETSHOP_ITEM_VALUE_LABEL'); 
    JText::script('COM_KETSHOP_ITEM_VALUE_TITLE'); 

    return;
  }


  public static function getProductText() 
  {
    JavascriptHelper::getButtonText();
    JavascriptHelper::getCommonText();
    JText::script('COM_KETSHOP_ATTRIBUTE_VALUES_LABEL'); 
    JText::script('COM_KETSHOP_ATTRIBUTE_VALUES_TITLE'); 
    JText::script('COM_KETSHOP_IMAGE_ALT_LABEL'); 
    JText::script('COM_KETSHOP_IMAGE_ALT_TITLE'); 
    JText::script('COM_KETSHOP_IMAGE_ORDERING_LABEL'); 
    JText::script('COM_KETSHOP_IMAGE_ORDERING_TITLE'); 
    JText::script('COM_KETSHOP_PRODUCT_STOCK_LABEL'); 
    JText::script('COM_KETSHOP_PRODUCT_STOCK_TITLE'); 
    JText::script('COM_KETSHOP_OPTION_NAME_LABEL'); 
    JText::script('COM_KETSHOP_OPTION_NAME_TITLE'); 
    JText::script('COM_KETSHOP_VARIANT_NAME_LABEL'); 
    JText::script('COM_KETSHOP_VARIANT_NAME_TITLE'); 
    JText::script('COM_KETSHOP_STOCK_LABEL'); 
    JText::script('COM_KETSHOP_STOCK_TITLE'); 
    JText::script('COM_KETSHOP_BASE_PRICE_LABEL'); 
    JText::script('COM_KETSHOP_BASE_PRICE_TITLE'); 
    JText::script('COM_KETSHOP_SALE_PRICE_LABEL'); 
    JText::script('COM_KETSHOP_SALE_PRICE_TITLE'); 
    JText::script('COM_KETSHOP_AVAILABILITY_DELAY_LABEL'); 
    JText::script('COM_KETSHOP_AVAILABILITY_DELAY_TITLE'); 
    JText::script('COM_KETSHOP_SALES_LABEL'); 
    JText::script('COM_KETSHOP_SALES_TITLE'); 
    JText::script('COM_KETSHOP_CODE_LABEL'); 
    JText::script('COM_KETSHOP_CODE_TITLE'); 
    JText::script('COM_KETSHOP_WEIGHT_LABEL'); 
    JText::script('COM_KETSHOP_WEIGHT_TITLE'); 
    JText::script('COM_KETSHOP_LENGTH_LABEL'); 
    JText::script('COM_KETSHOP_LENGTH_TITLE'); 
    JText::script('COM_KETSHOP_WIDTH_LABEL'); 
    JText::script('COM_KETSHOP_WIDTH_TITLE'); 
    JText::script('COM_KETSHOP_HEIGHT_LABEL'); 
    JText::script('COM_KETSHOP_HEIGHT_TITLE'); 
    JText::script('COM_KETSHOP_PUBLISHED_LABEL'); 
    JText::script('COM_KETSHOP_PUBLISHED_TITLE'); 
    JText::script('COM_KETSHOP_OPTION_NAME_MAIN_PRODUCT_EMPTY'); 

    return;
  }


  public static function getPriceRuleText() 
  {
    JavascriptHelper::getButtonText();
    JavascriptHelper::getCommonText();
    JText::script('COM_KETSHOP_COMPARISON_OPERATOR_LABEL'); 
    JText::script('COM_KETSHOP_COMPARISON_OPERATOR_TITLE'); 
    JText::script('COM_KETSHOP_OPTION_PRODUCT'); 
    JText::script('COM_KETSHOP_OPTION_PRODUCT_CAT'); 
    JText::script('COM_KETSHOP_OPTION_BUNDLE'); 
    JText::script('COM_KETSHOP_OPTION_CART_AMOUNT'); 
    JText::script('COM_KETSHOP_OPTION_SHIPPING_COST'); 
    JText::script('COM_KETSHOP_ERROR_RECIPIENT_MISSING'); 
    JText::script('COM_KETSHOP_ERROR_TARGET_MISSING'); 
    JText::script('COM_KETSHOP_ERROR_CONDITION_MISSING'); 

    return;
  }


  public static function getOrderText() 
  {
    JText::script('COM_KETSHOP_REMOVE_PRODUCT'); 

    return;
  }


  public static function getShippingText() 
  {
    JavascriptHelper::getButtonText();
    JavascriptHelper::getCommonText();
    JText::script('COM_KETSHOP_FROM_POSTCODE_LABEL'); 
    JText::script('COM_KETSHOP_FROM_POSTCODE_TITLE'); 
    JText::script('COM_KETSHOP_TO_POSTCODE_LABEL'); 
    JText::script('COM_KETSHOP_TO_POSTCODE_TITLE'); 
    JText::script('COM_KETSHOP_COUNTRY_SELECT_LABEL'); 
    JText::script('COM_KETSHOP_COUNTRY_SELECT_TITLE'); 
    JText::script('COM_KETSHOP_CONTINENT_SELECT_LABEL'); 
    JText::script('COM_KETSHOP_CONTINENT_SELECT_TITLE'); 

    return;
  }


  /**
   * Loads the field labels in order to use them with the dynamical items.
   *
   * @return  void
   */
  public static function loadFieldLabels() 
  {
    // Gets the language tag as well as the path to the language files. 
    $langTag = JFactory::getLanguage()->getTag();
    $path = JPATH_ADMINISTRATOR.'/components/com_ketshop/language';

    // Gets the ini language file matching the language tag.
    $langFile = parse_ini_file($path.'/'.$langTag.'/'.$langTag.'.com_ketshop.ini', true);
    // Loads language variables relating to Javascript.
    foreach($langFile['javascript_texts'] as $langVar => $name) {
      JText::script($langVar); 
    }
  }


  //Build and load Javascript functions which return different kind of data,
  //generaly as a JSON array.
  public static function loadFunctions($names, $data = '')
  {
    $js = array();
    //Create a name space in order put functions into it.
    $js = 'var ketshop = { '."\n";

    //Include the required functions.

    //Returns region names and codes used to build option tags.
    if(in_array('region', $names)) {
      $regions = JavascriptHelper::getRegions();
      $js .= 'getRegions: function() {'."\n";
      $js .= ' return '.$regions.';'."\n";
      $js .= '},'."\n";
    }

    //Returns country names and codes used to build option tags.
    if(in_array('country', $names)) {
      $countries = JavascriptHelper::getCountries();
      $js .= 'getCountries: function() {'."\n";
      $js .= ' return '.$countries.';'."\n";
      $js .= '},'."\n";
    }

    //Returns continent names and codes used to build option tags.
    if(in_array('continent', $names)) {
      $continents = JavascriptHelper::getContinents();
      $js .= 'getContinents: function() {'."\n";
      $js .= ' return '.$continents.';'."\n";
      $js .= '},'."\n";
    }

    //Returns the attributes used with the product.
    if(in_array('product_attributes', $names)) {
      $productAttributes = JavascriptHelper::getProductAttributes();
      $js .= 'getProductAttributes: function() {'."\n";
      $js .= ' return '.$productAttributes.';'."\n";
      $js .= '},'."\n";
    }

    //Returns the id of the current user.
    if(in_array('user', $names)) {
      $user = JFactory::getUser();
      $js .= 'getUserId: function() {'."\n";
      $js .= ' return '.$user->id.';'."\n";
      $js .= '},'."\n";
    }

    //Functions used to access an item directly from an other item.
    if(in_array('shortcut', $names)) {
      $js .= 'shortcut: function(itemId, task) {'."\n";
	       //Set the id of the item to edit.
      $js .= ' var shortcutId = document.getElementById("jform_shortcut_id");'."\n";
	       //This id will be retrieved in the overrided functions of the controller
	       //(ie: checkin and cancel functions).
      $js .= ' shortcutId.value = itemId;'."\n";
      $js .= ' Joomla.submitbutton(task);'."\n";
      $js .= '},'."\n";
    }

    //Build a generic Javascript function which return any data as a string.
    if(in_array('data', $names)) {
      $js .= 'getData: function() {'."\n";
      $js .= ' return "'.$data.'";'."\n";
      $js .= '},'."\n";
    }

    //Remove coma from the end of the string, (-2 due to the carriage return "\n").
    $js = substr($js, 0, -2); 

    $js .= '};'."\n\n";

    //Place the Javascript code into the html page header.
    $doc = JFactory::getDocument();
    $doc->addScriptDeclaration($js);
  }


  //Returns region codes and names as a JSON array.
  public static function getRegions()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get all the regions from the region list.
    $query->select('r.country_code, r.code, r.lang_var')
	  ->from('#__ketshop_region AS r')
	  //Get only regions which country they're linked with is published (to minimized
	  //the number of regions to display).
	  ->join('LEFT', '#__ketshop_country AS c ON r.country_code=c.alpha_2')
	  ->where('c.published=1');
    $db->setQuery($query);
    $results = $db->loadAssocList();

    //Build the regions array.
    $regions = array();
    //Set text value in the proper language.
    foreach($results as $result) {
      //Add the country code to the region name to get an easier search.
      $regions[] = array('code' => $result['code'], 'text' => $result['country_code'].' - '.JText::_($result['lang_var']));
    }

    return json_encode($regions);
  }


  //Returns country ids and names as a JSON array.
  public static function getCountries()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get all the countries from the country list.
    $query->select('alpha_2 AS code, lang_var AS text')
	  ->from('#__ketshop_country')
	  ->where('published=1');
    $db->setQuery($query);
    $countries = $db->loadAssocList();

    //Set text value in the proper language.
    foreach($countries as $key => $country) {
      $countries[$key]['text'] = JText::_($country['text']);
    }

    return json_encode($countries);
  }


  public static function getProductAttributes()
  {
    $prodId = JFactory::getApplication()->input->get('id', 0, 'uint');

    //Invokes the model's function.
    $model = JModelLegacy::getInstance('Product', 'KetshopModel');
    $attributes = $model->getProductAttributes($prodId);

    return json_encode($attributes);
  }


  //Return continent ids and names as a JSON array.
  public static function getContinents()
  {
    //Since continents are few in number we dont need to spend a db table for them. 
    //We simply store their data into an array.
    $continents = array();
    $continents[] = array('code'=>'AF','text'=>'COM_KETSHOP_LANG_CONTINENT_AF');
    $continents[] = array('code'=>'AN','text'=>'COM_KETSHOP_LANG_CONTINENT_AN');
    $continents[] = array('code'=>'AS','text'=>'COM_KETSHOP_LANG_CONTINENT_AS');
    $continents[] = array('code'=>'EU','text'=>'COM_KETSHOP_LANG_CONTINENT_EU');
    $continents[] = array('code'=>'OC','text'=>'COM_KETSHOP_LANG_CONTINENT_OC');
    $continents[] = array('code'=>'NA','text'=>'COM_KETSHOP_LANG_CONTINENT_NA');
    $continents[] = array('code'=>'SA','text'=>'COM_KETSHOP_LANG_CONTINENT_SA');

    //Set text value in the proper language.
    foreach($continents as &$continent) {
      $continent['text'] = JText::_($continent['text']);
    }

    return json_encode($continents);
  }
}


