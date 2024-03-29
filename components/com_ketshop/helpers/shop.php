<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
jimport('joomla.html.html');
//Note: JPATH_COMPONENT_SITE variable cannot be used here as it creates
//problem. It points to com_login component instead of com_ketshop.
require_once JPATH_ROOT.'/components/com_ketshop/helpers/pricerule.php';
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/ketshop.php';


class ShopHelper
{
  //Get product data from its id.
  //(Called from addToCart controller function).
  public static function getProduct($productId, $variantId)
  {
    //Used as first argument of the logEvent function.
    $codeLocation = 'helpers/ketshop.php';

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the required product data.
    $query->select('p.name,p.id,p.catid,p.type,p.base_price,p.sale_price,p.sale_price AS unit_sale_price,t.rate AS tax_rate,p.type,'.
	           'p.shippable,p.min_stock_threshold,p.allow_order,p.stock_subtract,p.min_quantity,p.max_quantity,'.
		   'p.nb_variants,p.alias,p.weight_unit,p.weight,p.dimensions_unit,p.length,p.width,p.height');


    //Get some data according to the variant id.
    if($variantId == 0) { //Get data from the product table (default).
      $query->select('p.code,p.stock,p.availability_delay,p.variant_name');
    }
    else { //Get data from the product variants table.
      $query->select('pv.code,pv.base_price AS var_base_price,pv.sale_price AS var_sale_price,'.
	             'pv.stock,pv.availability_delay,pv.name,pv.weight AS var_weight,'.
		     'pv.length AS var_length, pv.width AS var_width, pv.height AS var_height')
	    ->join('INNER', '#__ketshop_product_variant AS pv ON pv.prod_id='.(int)$productId.' AND pv.var_id='.(int)$variantId);
    }

    $query->from('#__ketshop_product AS p')
          ->join('LEFT', '#__ketshop_tax AS t ON t.id = p.tax_id')
          ->where('p.id='.(int)$productId);
    $db->setQuery($query);
    $product = $db->loadAssoc();

    //Check for errors.
    if($db->getErrorNum()) {
      self::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
      return false;
    }

    if($variantId) {
      //Check if variant has a different price
      if($product['var_sale_price'] > 0 && $product['var_base_price'] > 0) {
	$product['unit_sale_price'] = $product['var_sale_price'];
	$product['sale_price'] = $product['var_sale_price'];
	$product['base_price'] = $product['var_base_price'];
      }

      //a different weight
      if($product['var_weight'] > 0) {
	$product['weight'] = $product['var_weight'];
      }

      //a different dimension 
      if($product['var_length'] > 0 && $product['var_width'] > 0 && $product['var_height'] > 0) {
	$product['length'] = $product['var_length'];
	$product['width'] = $product['var_width'];
	$product['height'] = $product['var_height'];
      }

      //Remove unused variables
      unset($product['var_base_price']);
      unset($product['var_sale_price']);
      unset($product['var_weight']);
      unset($product['var_length']);
      unset($product['var_width']);
      unset($product['var_height']);
    }

    $product['var_id'] = $variantId;
    //Get the possible price rules linked to the product.
    $product['pricerules'] = PriceruleHelper::getCatalogPriceRules($product);

    return $product;
  }


  public static function callControllerFunction($controllerName, $function, $args = array())
  {
    $controllerName = strtolower($controllerName);
    require_once JPATH_ROOT.'/components/com_ketshop/controllers/'.$controllerName.'.php';
    $controllerName = ucfirst($controllerName);
    $className = 'KetshopController'.$controllerName;
    $controller = new $className();

    //Call the controller's function according to the arguments to pass.
    switch(count($args)) {
      case 0:
	return $controller->$function();

      case 1:
	return $controller->$function($args[0]);

      case 2:
	return $controller->$function($args[0], $args[1]);

      case 3:
	return $controller->$function($args[0], $args[1], $args[2]);

      case 4:
	return $controller->$function($args[0], $args[1], $args[2], $args[3]);

      default:
	return null;
    }
  }


  //Return the billing and the shipping addresses of the user in a
  //multidimensional associative array.
  //Note: The parent array is indexed with the address type for more
  //convenience.
  public static function getAddresses()
  {
    $user = JFactory::getUser();
    $addresses = array();
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get the last shipping address set by the customer. 
    $query->select('a.id,a.street,a.postcode,a.city,a.region_code,a.country_code,a.type,'.
	           'a.phone,a.note,u.name AS recipient,c.lang_var AS country_lang_var,r.lang_var AS region_lang_var')
          ->from('#__ketshop_address AS a')
          ->join('INNER', '#__users AS u ON u.id=a.item_id')
          ->join('LEFT', '#__ketshop_country AS c ON c.alpha_2=a.country_code')
          ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code')
          ->where('a.item_type="customer" AND a.type ="shipping"')
          ->where('a.item_id='.$user->id.' ORDER BY a.created DESC LIMIT 1');
    $db->setQuery($query);
    $shipping = $db->loadAssoc();

    $addresses['shipping'] = $shipping; 

    //Get the last billing address set by the customer. 
    $query->clear();
    $query->select('a.id,a.street,a.postcode,a.city,a.region_code,a.country_code,a.type,'.
	           'a.phone,a.note,u.name AS recipient,c.lang_var AS country_lang_var,r.lang_var AS region_lang_var')
          ->from('#__ketshop_address AS a')
          ->join('INNER', '#__users AS u ON u.id=a.item_id')
          ->join('LEFT', '#__ketshop_country AS c ON c.alpha_2=a.country_code')
          ->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code')
          ->where('a.item_type="customer" AND a.type ="billing"')
          ->where('a.item_id='.$user->id.' ORDER BY a.created DESC LIMIT 1');
    $db->setQuery($query);
    $billing = $db->loadAssoc();

    $addresses['billing'] = $billing; 

    return $addresses;
  }


  //Retrieve the global data set for all the shop.
  public static function getShopSettings()
  {
    $config = JComponentHelper::getParams('com_ketshop');
    $langTag = JFactory::getLanguage()->getTag();
    $suffix = preg_replace('#\-#', '_', $langTag);

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('cu.alpha AS currency_alpha,cu.symbol AS currency_symbol,co.name_'.$suffix.' AS country_name,'.
		   'co.alpha_2 AS country_alpha_2,co.alpha_3 AS country_alpha_3,'.
		   'co.lang_var AS country_lang_var') 
	  ->from('#__ketshop_currency AS cu')
	  ->join('INNER', '#__ketshop_country AS co ON co.alpha_2='.$db->quote($config->get('country_code')))
	  ->where('cu.alpha='.$db->quote($config->get('currency_code')));
    $db->setQuery($query);
    $settings = $db->loadAssoc();
    //var_dump($settings);
    $attribs = array('shop_name','vendor_name','site_url','tax_method','shipping_weight_unit','volumetric_weight',
		     'redirect_url_1','rounding_rule','digits_precision','volumetric_ratio','currency_display','gts_article_ids');

    foreach($attribs as $attrib) {
      $settings[$attrib] = $config->get($attrib);
    }

    //Create a currency attribute which contains currency in the correct display.
    $settings['currency'] = $settings['currency_alpha'];
    if($settings['currency_display'] == 'symbol') {
      $settings['currency'] = $settings['currency_symbol'];
    }

    return $settings;
  }


  public static function getShipperPlugins()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //The translated fields of a shipper.
    $translatedFields = 's.name,s.description,';
    $leftJoinTranslation = '';
    //Check if a translation is needed.
    if(self::switchLanguage()) {
      //Get the SQL query parts needed for the translation of the shippers.
      $shipTranslation = self::getTranslation('shipper', 'id', 's', 's');
      //Translation field are now defined by the SQL conditions.
      $translatedFields = $shipTranslation->translated_fields.',';
      //Build the left join SQL clause.
      $leftJoinTranslation = $shipTranslation->left_join;
    }

    //Get the shippers set into the backend component.
    $query->select('s.id, s.plugin_element,'.$translatedFields.' s.published')
          ->from('#__ketshop_shipper AS s');

    if(!empty($leftJoinTranslation)) {
      $query->join('LEFT', $leftJoinTranslation);
    }

    $query->where('s.published=1')
	  ->order('s.ordering');
    $db->setQuery($query);

    //Return result as an indexed array of associated arrays.
    return $db->loadAssocList();
  }


  public static function getPaymentModes()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //The translated fields of a payment mode.
    $translatedFields = 'pm.name,pm.description,';
    $leftJoinTranslation = '';
    //Check if a translation is needed.
    if(self::switchLanguage()) {
      //Get the SQL query parts needed for the translation of the payment modes.
      $paymentTranslation = self::getTranslation('payment_mode', 'id', 'pm', 'pm');
      //Translation field are now defined by the SQL conditions.
      $translatedFields = $paymentTranslation->translated_fields.',';
      //Build the left join SQL clause.
      $leftJoinTranslation = $paymentTranslation->left_join;
    }

    //Get the payment modes set into the backend component.
    $query->select('pm.id,'.$translatedFields.'pm.plugin_element')
          ->from('#__ketshop_payment_mode AS pm');

    if(!empty($leftJoinTranslation)) {
      $query->join('LEFT', $leftJoinTranslation);
    }

    $query->where('pm.published=1')
	  ->order('pm.ordering');
    $db->setQuery($query);
    $modes = $db->loadObjectList();

    //Get all the enabled ketshoppayment plugins.
    $query->clear();
    $query->select('element')
          ->from('#__extensions')
	  ->where('type="plugin" AND folder="ketshoppayment" AND enabled=1');
    $db->setQuery($query);
    $paymentPlugins = $db->loadColumn();

    //Store each found mode as an object into an array.
    $paymentModes = array();
    foreach($modes as $mode) {
      //First we check that the payment plugin which is assigned to the mode
      //item is installed and enabled.
      if(in_array($mode->plugin_element, $paymentPlugins)) {
	//The offline plugin can have several payment modes, so we need to 
	//slighly modified the plugin_element attribute of the object. 
	if($mode->plugin_element == 'offline') {
	  //The offline payment plugin is going to need an id for each offline
	  //payment mode found. So we pass the id at the end of the
	  //plugin_element attribute separated by an underscore. 
	  $mode->plugin_element = 'offline_'.$mode->id;
	  //Add the offline payment mode to the array.
	  $paymentModes[] = $mode;
	}
	else { //For "standard" plugins we just add the object as it is to the array.
	  $paymentModes[] = $mode;
	}
      }
    }

    return $paymentModes;
  }


  //Return width and height of an image according to its reduction rate.
  public static function getThumbnailSize($width, $height, $reductionRate)
  {
    $size = array();

    if($reductionRate == 0) {
      //Just return the original values.
      $size['width'] = $width;
      $size['height'] = $height;
    }
    else { //Compute the new image size.
      $widthReduction = ($width / 100) * $reductionRate;
      $size['width'] = $width - $widthReduction;

      $heightReduction = ($height / 100) * $reductionRate;
      $size['height'] = $height - $heightReduction;
    }

    return $size;
  }


  //Check if cart is shippable or not.
  public static function isShippable()
  {
    //Get the cart.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    $shippable = 0; //Flag

    foreach($cart as $product) {
      //As soon as one product of the cart is shippable, we set flag to true and
      //leave the loop.
      if($product['shippable']) {
	$shippable = 1;
        break;	
      }
    }

    return $shippable;
  }


  //Delete all of the session data which has been used during the purchase.
  public static function clearPurchaseData()
  {
    //Store the name of all the variables which should be deleted.
    $variables = array('cart','cart_amount','settings','utility',
	               'billing_address_id','locked','end_purchase',
		       'shippers','location','order_id','submit',
		       'unavailable', 'shipping_data', 'order_nb', 'coupons');

    $session = JFactory::getSession();
    foreach($variables as $variable) {
      //Check if variable exists. If it does we delete it.
      if($session->has($variable, 'ketshop')) {
	$session->clear($variable, 'ketshop');
      }
    }

    return;
  }


  //Return the total quantity of the products which are in the cart.
  public static function getTotalQuantity($onlyShippable = true, $sessionGroup = 'ketshop')
  {
    //Get the cart.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), $sessionGroup); 

    $totalQuantity = 0;
    foreach($cart as $product) {
      //Only shippable products are taking into account.
      if($onlyShippable && $product['shippable']) {
	$totalQuantity += (int)$product['quantity'];
      }
      //All of the products are taking into account.
      elseif(!$onlyShippable) {
	$totalQuantity += (int)$product['quantity'];
      }
    }

    return $totalQuantity;
  }


  //Return the total delay (if any) of the products which are in the cart.
  public static function getTotalDelay()
  {
    //Get the cart.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 

    $totalDelay = 0;
    foreach($cart as $product) {
      //Only shippable products are taking into account.
      if($product['shippable']) {
	$totalDelay += (int)$product['availability_delay'];
      }
    }

    return $totalDelay;
  }


  //Return the stock state of a product.
  public static function getStockState($minStockThreshold, $maxStockThreshold, $stock, $allowOrder)
  {
    if($stock == 0) {
      return 'minimum';
    }
    elseif($stock <= $minStockThreshold && !$allowOrder) {
      return 'minimum';
    }
    elseif($stock >= $maxStockThreshold) {
      return 'maximum';
    }
    else {
      return 'middle';
    }
  }


  public static function getLocation()
  {
    //Remove previously set variables (if any) from the url query.
    $location = self::getUrlQuery(array('limitstart','start','filter_order','language'));

    $jinput = JFactory::getApplication()->input;

    //limitstart and filter_order need to be updated in category view.
    if($jinput->get('view', '', 'string') == 'category') {
      //Get the current values from POST or GET.
      $userStates = array('limitstart' => $jinput->get('limitstart', 0, 'int'),
			  'filter_order' => $jinput->get('filter_order', ''));

      $location = $location.'&limitstart='.$userStates['limitstart'];

      if(!empty($userStates['filter_order'])) {
	$location = $location.'&filter_order='.$userStates['filter_order'];
      }
    }

    return $location;
  }


  //Retrieve the current url query and 
  //remove possible unwanted variables from it.
  public static function getUrlQuery($unwanted = array())
  {
    //Get the current GET query as an associative array.
    $GETQuery = JFactory::getApplication()->input->getArray();
    //Variable to store the url query as a string.
    $urlQuery = '';

    foreach($GETQuery as $key => $value) {
      if(!in_array($key, $unwanted)) {

	if(is_array($value)) {
	  //
	  foreach($value as $val) {
	    $urlQuery .= $key.'[]='.$val.'&';
	  }
	}
	else { //string value
	  $urlQuery .= $key.'='.$value.'&';
	}
      }
    }

    //Remove & from the end of the string.
    $urlQuery = substr($urlQuery, 0, -1);

    return $urlQuery;
  }


  /**
   * Modifies the stock value for each product according to their quantity.
   *
   * @param array  The products.
   * @param string  The action to perform on the stock.
   *
   * @return void
   */
  public static function updateStock($products, $action = 'subtract')
  {
    $bundleData = $prodIds = array();
    $when1 = $when2 = $when3 = '';
    $codeLocation = 'helpers/shop.php';

    //Note: $key value is needed farther in case of bundle product type.
    foreach($products as $key => $product) {
      //Check first that the stock can be modified for this product.
      if($product['stock_subtract']) {

	//Ensure the query won't fail when subtracting quantity.
	$operation = ' - IF('.$product['quantity'].' >= stock, stock, '.$product['quantity'].') ';
	if($action === 'add') {
	  $operation = ' + '.$product['quantity'].' ';
	}

	if($product['type'] === 'bundle') {
	  //Set the array's key as the id of the bundle.
	  $bundleData[$product['id']] = $product['quantity'];
	  //The bundle products will be treated later on.
	  continue;
	}
	elseif($product['var_id']) { //Product variant
	  $when1 .= 'WHEN prod_id='.$product['id'].' AND var_id = '.$product['var_id'].' THEN stock '.$operation;
	}
	else { //Normal product
	  $when2 .= 'WHEN id='.$product['id'].' THEN stock '.$operation;
	}

	//Collect the product ids.
	$prodIds[] = $product['id'];
      }
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Check if someone is editing the updated products.
    if(!empty($prodIds)) {
      $query->select('id, checked_out')
	    ->from('#__ketshop_product')
	    ->where('id IN('.implode(',', $prodIds).')');
      $db->setQuery($query);
      $results = $db->loadAssocList();

      foreach($results as $result) {
	//Someone (in backend) is editing the product.  
	if($result['checked_out']) {
	  //Lock the new stock value to prevent this value to be modified by an admin
	  //when saving in backend.
	  $when3 .= 'WHEN id='.$result['id'].' THEN 1 ';
	}
      }
    }

    //Update the stock according to the product (normal or product variant).

    if(!empty($when1)) {
      $query->clear();
      $query->update('#__ketshop_product_variant')
	    ->set('stock = CASE '.$when1.' ELSE stock END ')
	    ->where('prod_id IN('.implode(',', $prodIds).')');
      $db->setQuery($query);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	self::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    $cases = $comma = '';
    if(!empty($when2)) {
      $cases .= 'stock = CASE '.$when2.' ELSE stock END ';
      $comma = ',';
    }

    if(!empty($when3)) {
      $cases .= $comma.' stock_locked = CASE '.$when3.' ELSE stock_locked END ';
    }

    if(!empty($cases)) {
      $query->clear();
      $query->update('#__ketshop_product')
	    ->set($cases)
	    ->where('id IN('.implode(',', $prodIds).')');
      $db->setQuery($query);
      $db->query();

      //Check for errors.
      if($db->getErrorNum()) {
	self::logEvent($codeLocation, 'sql_error', 1, $db->getErrorNum(), $db->getErrorMsg());
	return false;
      }
    }

    $model = JModelLegacy::getInstance('Product', 'KetshopModel');
    //The bundle products have been treated. 
    //Now the stock of the bundles themself must be updated.
    //Note: This condition must be checked before the recursive call or weird things occure.
    if(isset($products[$key]['bundle_ids'])) {
      $model->updateBundles($products[$key]['bundle_ids']);
    }

    if(!empty($bundleData)) {
      $bundleProducts = $model->getBundleProducts($bundleData);
      //Call the function one more time (recursively) to treat the bundle products.
      self::updateStock($bundleProducts, $action);
    }
  }


  public static function canOrderProduct($product)
  {
    if(empty($product->variants)) { //Regular product.
      $stock = $product->stock;
      $stockState = $product->stock_state;
    }
    else { //Product with variants.
      $stock = 0;
      $stockState = 'minimum';
      foreach($product->variants as $variant) {
	$stock += $variant['stock'];
	if($variant['stock_state'] != 'minimum') {
	  $stockState = $variant['stock_state'];
	}
      }
    }

    if(($product->stock_subtract && $product->shippable) && $stockState == 'minimum'
       && (!$product->allow_order || $stock == 0)) {
      return false;
    }

    return true;
  }


  public static function deleteTemporaryData($orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->delete('#__ketshop_tmp_data')
          ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->execute();
  }


  public static function getTemporaryData($orderId, $utility = false)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select('amounts, cart, settings, utility')
          ->from('#__ketshop_tmp_data')
          ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $result = $db->loadObject();

    if(is_null($result)) {
      return $result;
    }

    if($utility) {
      return unserialize($result->utility);
    }

    $data = array();
    $data['amounts'] = unserialize($result->amounts);
    $data['cart'] = unserialize($result->cart);
    $data['settings'] = unserialize($result->settings);
    $data['utility'] = unserialize($result->utility);

    return $data;
  }


  public static function createTransaction($amounts, $utility, $settings)
  {
    //Set the amount value which has been paid.
    //Note: For now the shop doesn't handle multiple instalment payment but it will in the futur.
    $amount = $amounts['fnl_crt_amt_incl_tax'] + $amounts['final_shipping_cost'];

    //Set the result of the transaction.
    $result = 'success';
    $detail = $utility['payment_detail'];
    if(!$utility['payment_result']) {
      $result = 'error';
    }

    //Create the transaction.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $nowDate = $db->quote(JFactory::getDate()->toSql());
    $columns = array('order_id','payment_mode','amount','result','detail','transaction_data','created');
    $values = (int)$settings['order_id'].','.$db->quote($utility['payment_mode']).','.(float)$amount.','.  
              $db->quote($result).','.$db->quote($detail).','.$db->quote($utility['transaction_data']).','.$nowDate;

    $query->insert('#__ketshop_order_transaction')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return false;
    }

    return true;
  }


  //Function used to trace possible errors and report them into a log file.
  public static function logEvent($location, $type, $criticity, $code = 0, $message = '')
  {
    jimport('joomla.utilities.date');
    $now = JFactory::getDate()->toSql();
    //Get the user data.
    $user = JFactory::getUser();

    //Set the proper carriage return to use.
    $crRt = "\n"; //Linux/Unix or Mac
    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $crRt = "\r\n"; //Windows
    }

    //Use regular spaces rather tabulations (\t) as they cause weird problems.
    $space = '        ';  //8 spaces.

    //The log line to write in the log file.
    $logContent = $crRt.$crRt.$now.$space.'location: '.$location.$space.
                  'type: '.$type.$space.'criticity: '.$criticity.$space.
		  'userid: '.$user->id.$space.'code: '.$code.$space.'message: '.$message;
    //the file name with its absolute path.
    $fileName = JPATH_SITE.'/components/com_ketshop/logs/ketshop.log';

    if(!$handle = fopen($fileName, 'a')) {
       echo 'Cannot open file '.$fileName;
       return false;
    }

    // Write $logContent to our opened file.
    if(fwrite($handle, $logContent) === FALSE) {
      echo 'Cannot write to file '.$fileName;
      fclose($handle);
      return false;
    }

    fclose($handle);

    //Redirect user on error page if criticity is 1.
    if($criticity == 1) {
      self::clearPurchaseData();

      $app = JFactory::getApplication();
      $app->redirect(JRoute::_('index.php?option=com_ketshop&view=error', false),
	             JText::sprintf('COM_KETSHOP_CRITICAL_ERROR', $code, $message), 'error'); 
      return false;
    }

    return true;
  }


  //Check if a translation is needed or return the required language tag 
  //if langTag parameter is true.
  public static function switchLanguage($langTag = false)
  {
    $lg = JFactory::getLanguage();

    if($langTag) {
      return $lg->get('tag');
    }

    if($lg->get('tag') === UtilityHelper::getLanguage(true)) {
      return false;
    }
    
    return true;
  }


  //If the system languagefilter plugin is enabled we assume that the site is
  //multilingual.
  public static function isSiteMultilingual()
  {
    if(JPluginHelper::isEnabled('system', 'languagefilter')) {
      return true;
    }

    return false;
  }


  //Return the SQL query parts needed for the translation of a given item.
  public static function getTranslation($itemType, $joinField, $joinPrefix, $itemPrefix, $aliasName = 'name')
  {
    //Build the translation SQL prefix by adding "tr" to the item prefix.
    $prefix = $itemPrefix.'tr';

    //Get the required language.
    $language = self::switchLanguage(true);

    //Just used for the Quote function.
    $db = JFactory::getDbo();

    //Check for an item translation in the required language. 
    //If a translation exists (ie: a name translation has been defined) we use the translated 
    //fields, otherwise we use the untranslated fields (ie: fields write in the backend language).

    //Build the SQL conditions according to the item type.

    //name field is used in all cases.
    $translatedFields ='IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.name,'.$itemPrefix.'.name) AS '.$aliasName;

    if($itemType == 'product') {
      $translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.alias,'.$itemPrefix.'.alias) AS alias';
      $translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.description,'.$itemPrefix.'.intro_text) AS intro_text';

      //Full_text field as well as all of the meta data are only displayed in product view.
      if(JFactory::getApplication()->input->get->get('view', '', 'string') == 'product') {
	$translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.full_description,'.$itemPrefix.'.full_text) AS full_text';
	$translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.metakey,'.$itemPrefix.'.metakey) AS metakey';
	$translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.metadesc,'.$itemPrefix.'.metadesc) AS metadesc';
	$translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.metadata,'.$itemPrefix.'.metadata) AS metadata';
	$translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.xreference,'.$itemPrefix.'.xreference) AS xreference';
      }
    }
    elseif($itemType == 'shipping' || $itemType == 'shipper' || $itemType == 'price_rule') {
       $translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.description,'.$itemPrefix.'.description) AS description';
    }
    elseif($itemType == 'payment_mode') {
       $translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.description,'.$itemPrefix.'.description) AS description';
       $translatedFields .= ',IF('.$prefix.'.name IS NOT NULL,'.$prefix.'.information,'.$itemPrefix.'.information) AS information';
    }

    //Note: tax and attribute items use only the name field.

    //Build the left join clause.
    //Note: The clause itself (ie: LEFT OUTER JOIN) must be added (or not) at
    //the location where this function is called.
    $leftJoin = '#__ketshop_translation AS '.$prefix.' ON '.$prefix.'.item_id='.$joinPrefix.'.'.$joinField.
			   ' AND '.$prefix.'.published=1 AND '.$prefix.'.item_type='.$db->Quote($itemType).
			   ' AND '.$prefix.'.language='.$db->Quote($language).' ';

    //
    $translation = new JObject;
    $translation->translated_fields = $translatedFields;
    $translation->left_join = $leftJoin;

    return $translation;
  }


  //Build Javascript utility functions:
  //getMessage function display a given message through a confirm box. If an extra
  //argument is passed (a button id) then hideButton function is invoked before
  //returning true. 
  public static function javascriptUtilities()
  {
    $emptyCart = JText::_('COM_KETSHOP_MESSAGE_EMPTY_CART');
    $cancelCart = JText::_('COM_KETSHOP_MESSAGE_CANCEL_CART');
    $db = JFactory::getDbo(); //For the Quote function.

    $js = 'function getMessage(msgType) {'."\n";
    $js .= '    var message = "";'."\n";
    $js .= '  switch(msgType) {'."\n";
    $js .= '      case "empty_cart":'."\n";
    $js .= '        message = '.$db->Quote($emptyCart).';'."\n";
    $js .= '        break;'."\n";
    $js .= '      case "cancel_cart":'."\n";
    $js .= '        message = '.$db->Quote($cancelCart).';'."\n";
    $js .= '        break;'."\n";
    $js .= '    }'."\n";
    $js .= '    if(confirm(message))'."\n";
    $js .= '    {'."\n";
    $js .= '      if(arguments[1])'."\n";
    $js .= '        hideButton(arguments[1]);'."\n";
    $js .= '      return true;'."\n";
    $js .= '    }'."\n";
    $js .= '    else'."\n";
    $js .= '      return false;'."\n";
    $js .= '}'."\n\n";
    $js .= ''."\n\n";
    $js .= 'function hideButton(buttonId) {'."\n";
    $js .= '    var elements = document.getElementsByClassName(buttonId);'."\n";
    $js .= '    for(var i = 0; i < elements.length; i++) {'."\n";
    $js .= '      elements[i].style.visibility="hidden";'."\n";
    $js .= '    }'."\n";
    //$js .= '    document.getElementById(buttonId).style.visibility="hidden";'."\n";
    $js .= '    var messagePanel = getMessagePanel("waiting-message",'.$db->Quote(JText::_('COM_KETSHOP_MESSAGE_WAITING_MESSAGE')).');'."\n";
    $js .= '    parentTag = document.getElementById(buttonId+"-message").parentNode;'."\n";
    $js .= '    parentTag.insertBefore(messagePanel, document.getElementById(buttonId+"-message"))'."\n";
    $js .= '    return;'."\n";
    $js .= '}'."\n\n";

    //Place the Javascript function into the html page header.
    $doc = JFactory::getDocument();
    $doc->addScriptDeclaration($js);

    return;
  }
}


