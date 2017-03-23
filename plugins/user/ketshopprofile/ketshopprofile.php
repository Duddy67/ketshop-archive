<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */



// No direct access
defined('_JEXEC') or die;

//jimport('joomla.plugin.plugin');
//jimport('joomla.utilities.simplexml');
//jimport('joomla.application.component.controller'); 
//require_once JPATH_ROOT.DS.'components'.DS.'com_ketshop'.DS.'helpers'.DS.'ketshop.php';
//require_once JPATH_ROOT.DS.'components'.DS.'com_ketshop'.DS.'helpers'.DS.'pricerule.php';
//Note: JPATH_COMPONENT_ADMINISTRATOR variable cannot be used here as it creates
//problem. It points to com_login component instead of com_ketshop.
require_once JPATH_ROOT.'/administrator/components/com_ketshop/helpers/utility.php';
require_once JPATH_ROOT.'/components/com_ketshop/controllers/cart.php';



class plgUserKetshopProfile extends JPlugin
{
  /**
   * Constructor
   *
   * @access      protected
   * @param       object  $subject The object to observe
   * @param       array   $config  An array that holds the plugin configuration
   * @since       1.5
   */
  public function __construct(& $subject, $config)
  {
    parent::__construct($subject, $config);
    JFormHelper::addFieldPath(__DIR__ . '/fields');
    $this->loadLanguage();
  }


  /**
   * @param	string	$context	The context for the data
   * @param	int		$data		The user id
   * @param	object
   *
   * @return	boolean
   * @since	1.6
   */
  function onContentPrepareData($context, $data)
  {
    // Check we are manipulating a valid form.
    //If context doesn't match we leave the function.
    //Note: Definitly no logic here. com_users.user is supposed to get data for the
    //users manager in admin, but it doesn't matter wether it's removed from the
    //array or not. It's com_users.profile which actualy does the job (????). 
    if(!in_array($context, array('com_admin.profile', 'com_users.profile',
				 'com_ketshop.address.shipping',
				 'com_ketshop.address.no_shipping'))) 
    {
      return true;
    }

    //Because of the illogical behavior above, we need to parse view to avoid
    //some warning messages in the admin users manager.
    $jinput = JFactory::getApplication()->input;
    $view = $jinput->get('view', '', 'string');
    //if($view == 'user')
      //return true;

    if(is_object($data)) {
      //Get the user id if it's defined, or set it to zero if it doesn't.
      $userId = isset($data->id) ? $data->id : 0;

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      if(!isset($data->profile) and $userId > 0) {
	$layout = $jinput->get('layout', '', 'string');

	$fields = array();
	$leftJoin = true;

	if($context == 'com_ketshop.address.shipping' || $context == 'com_users.profile') {
	  //Get the country and region names.
	  $country = 'c.lang_var AS country_code_sh,'; 
	  $region = 'r.lang_var AS region_code_sh,'; 
	  //Query is slighly different when editing.
	  if($layout == 'edit' || $context == 'com_ketshop.address.shipping') {
	    //Get both the country and region codes.
	    $country = 'a.country_code AS country_code_sh,';
	    $region = 'a.region_code AS region_code_sh,'; 
	    $leftJoin = false; //No need to join over the country or region tables.
	  }

	  //Get the last shipping address set by the customer. 
	  $query->select('a.street AS street_sh, '.$region.
			 'a.postcode AS postcode_sh, a.city AS city_sh,'.
			  $country.'a.note AS note_sh,a.phone AS phone_sh')
		->from('#__ketshop_address AS a');

	  if($leftJoin) {
	    //Join over the country and region tables.
	    $query->join('LEFT', '#__ketshop_country AS c ON c.alpha_2=a.country_code');
            $query->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code');
	  }

	  $query->where('a.item_id='.(int)$userId.' AND a.type="shipping" AND a.item_type="customer"')
		->order('a.created DESC')
		->setLimit(1);
	  $db->setQuery($query);
	  $results = $db->loadAssoc();
	  // Check for a database error.
	  if($db->getErrorNum()) {
	    $this->_subject->setError($db->getErrorMsg());
	    return false;
	  }
	  //Copy the shipping address array into the "fields" array.
	  $fields = $results;
	}

	//Get the country and region names.
	$country = 'c.lang_var AS country_code_bi,';
	$region = 'r.lang_var AS region_code_bi,'; 
	//Query is slighly different when editing.
	if($layout == 'edit' || $context == 'com_ketshop.address.shipping') {
	  //Get both the country and region ids.
	  $country = 'a.country_code AS country_code_bi,';
	  $region = 'a.region_code AS region_code_bi,'; 
	  $leftJoin = false; //No need to join over the country table.
	}

	//Get the last billing address set by the customer. 
	$query->clear();
	$query->select('a.street AS street_bi, '.$region.
		       'a.postcode AS postcode_bi, a.city AS city_bi,'.
			$country.'a.note AS note_bi,a.phone AS phone_bi')
	      ->from('#__ketshop_address AS a');

	if($leftJoin) {
	  //Join over the country and region tables.
	  $query->join('LEFT', '#__ketshop_country AS c ON c.alpha_2=a.country_code');
	  $query->join('LEFT', '#__ketshop_region AS r ON r.code=a.region_code');
	}

	$query->where('a.item_id='.(int)$userId.' AND a.type="billing" AND a.item_type="customer"')
	      ->order('a.created DESC')
	      ->setLimit(1);
	$db->setQuery($query);
	$results = $db->loadAssoc();
	// Check for a database error.
	if($db->getErrorNum()) {
	  $this->_subject->setError($db->getErrorMsg());
	  return false;
	}

	//Merge billing and shipping address arrays.
        if(!is_null($results)) {
	  $fields = array_merge($fields, $results);
	}

	// Merge the profile data.
        if(!is_null($results)) {
	  $data->ketshopprofile = array();
	  foreach($fields as $key => $value) {
	    //Get the country name in the appropriate language.
	    if(($key == 'country_code_sh' || $key == 'country_code_bi') && !preg_match('#^[A-Z]{2}$#', $value)) {
	      $value = JText::_($value);
	    }

	    //Get the region name in the appropriate language.
	    if(($key == 'region_code_sh' || $key == 'region_code_bi') && !preg_match('#^[A-Z]{2}\-[0-9A-Z]{1,3}$#', $value)) {
	      $value = JText::_($value);
	    }

	    $data->ketshopprofile[$key] = $value;
	  }
	}
      }
    }
  }


  /**
   * @param	JForm	$form	The form to be altered.
   * @param	array	$data	The associated data for the form.
   *
   * @return	boolean
   * @since	1.6
   */
  function onContentPrepareForm($form, $data)
  {
    //Check we are manipulating a form object.
    if(!($form instanceof JForm)) {
      $this->_subject->setError('JERROR_NOT_A_FORM');
      return false;
    }

    // Check we are manipulating a valid form.
    $name = $form->getName();
    //We don't want addresses being displayed in the admin users manager nor in
    //the registration form.
    if(!in_array($name, array('com_admin.profile', 'com_users.profile',
	                      'com_ketshop.address.shipping',
			      'com_ketshop.address.no_shipping'))) 
    {
      return true;
    }

    //Load our profile form (street, city etc...) and merge it with the
    //standard form (username, password etc...).
    JForm::addFormPath(__DIR__.'/profiles');

    if($name == 'com_ketshop.address.shipping' || $name == 'com_users.profile') {
      $fields = array('street_sh', 'city_sh', 'region_code_sh', 'postcode_sh',
		      'country_code_sh', 'phone_sh', 'note_sh',
		      'street_bi', 'city_bi', 'region_code_bi', 'postcode_bi',
		      'country_code_bi', 'phone_bi', 'note_bi');

      //Load the appropriate form.
      if($name == 'com_ketshop.address.shipping') {
	//IMPORTANT: Don't forget to set the reset argument flag to true or the "required" attribute won't be toggled.
	$form->loadFile('shipping', true);
      }
      else {
	$form->loadFile('profile', true);
      }
    }
    else {
      $fields = array('street_bi', 'city_bi', 'region_code_bi', 'postcode_bi',
		      'country_code_bi', 'phone_bi', 'note_bi');

      //Load the appropriate form.
      $form->loadFile('no_shipping', true);
    }

    foreach($fields as $field) {
      // Case profile in site or admin
      if($name == 'com_users.profile' || $name == 'com_admin.profile') {
	// Toggle whether the field is required.
	if($this->params->get('profile-require_' . $field, 1) > 0) {
	  $form->setFieldAttribute($field, 'required', ($this->params->get('profile-require_'.$field) == 2) ? 'required' : '', 'ketshopprofile');
	}
	else { 
	  $form->removeField($field, 'ketshopprofile');
	}
      }
      else { //Case address before ordering (com_ketshop.address.shipping or com_ketshop.address.no_shipping).
	// Toggle whether the field is required.
	if($this->params->get('profile-require_' . $field, 1) > 0) {
	  $form->setFieldAttribute($field, 'required', ($this->params->get('profile-require_'.$field) == 2) ? 'required' : '', 'ketshopprofile');
	}
	else { 
	  $form->removeField($field, 'ketshopprofile');
	}
      }
    }

/*echo '<pre>';
var_dump($form);
echo '</pre>';*/
    return true;
  }


  function onUserAfterSave($data, $isNew, $result, $error)
  {
    //Get the user id.
    $userId = JArrayHelper::getValue($data, 'id', 0, 'int');

    //Make sure the user data storage has been succesfuly.
    if($userId && $result) {
      try
      {
	if($isNew) { //A new user has been added.
	  $db = JFactory::getDbo();
	  $query = $db->getQuery(true);
	  //Add the new user into the ketshop_customer table
	  $query->insert($db->quoteName('#__ketshop_customer'))
		->columns('user_id')
		->values((int)$userId);
	  // Set the query
	  $db->setQuery($query);

	  if(!$db->query()) {
	    throw new Exception($db->getErrorMsg());
	  }
	}
      }
      catch(JException $e)
      {
	$this->_subject->setError($e->getMessage());
	return false;
      }
    }
    else { //User data storage has failed.
      return false;
    }

    //Get the cart session array.
    $session = JFactory::getSession();
    $cart = $session->get('cart', array(), 'ketshop'); 
    //Initialize some variables.
    $app = JFactory::getApplication();
    $uParams = JComponentHelper::getParams('com_users');

    //Customers are automaticaly logged in to avoid session losses in closing tab/window browser or
    //possible mistakes during login phase.
    //Note: This is performed only if certain conditions are gathered.
    if($isNew && !empty($cart) && $app->isSite() && $uParams->get('useractivation') == 0) {
      JRequest::checkToken('post') or jexit(JText::_('JInvalid_Token'));

      //Since onUserAfterSave function is triggered before any email is send to
      //the user by the register function (component/com_users/models/registration.php from line 341) 
      //we can take advantage of this to override this function.
      //So we send a registration email to the customer, perform log in than
      //redirect the user (which it causes the cancellation of the sending email
      //by the register function).

      //A reference to the global mail object (JMail) is fetched through the JFactory object. 
      //This is the object creating our mail.
      $mailer = JFactory::getMailer();

      $config = JFactory::getConfig();
      $sender = array($config->getValue('config.mailfrom'),
		      $config->getValue('config.fromname'));
   
      $mailer->setSender($sender);

      $recipient = $data['email'];
       
      $mailer->addRecipient($recipient);

      $mailer->setSubject(JText::sprintf('PLG_USER_EMAIL_ACCOUNT_DETAILS', $data['name'], $config->getValue('config.sitename')));
      $mailer->setBody(JText::sprintf('PLG_USER_EMAIL_REGISTERED_BODY', $data['name'],
									$config->getValue('config.sitename'), JURI::root()));
      //Send the confirmation email to the customer.
      $send = $mailer->Send();

      //Check for error.
      if($send !== true) {
	JError::raiseWarning(500, JText::_('PLG_USER_REGISTRATION_SEND_MAIL_FAILED'));
      }
      else {
        JFactory::getApplication()->enqueueMessage(JText::_('PLG_USER_REGISTRATION_SAVE_SUCCESS'));
      }

      // Get the log in credentials.
      $credentials = array();
      $credentials['username'] = $data['username'];
      $credentials['password'] = $data['password_clear'];

      // Perform the log in.
      if(true === $app->login($credentials)) {
	// Success
	$app->setUserState('users.login.form.data', array());
	$app->redirect(JRoute::_('index.php?option=com_ketshop&view=cart', false));
      }
      else {
	// Login failed !
	$app->setUserState('users.login.form.data', $data);
	$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
      }
    }

    //The user has just modified his profile from the site.
    if(!$isNew && $app->isSite() && isset($data['ketshopprofile'])) {
      $db = JFactory::getDbo();

      //Get shipping and billing addresses.
      $addresses = $data['ketshopprofile'];
      //Sort addresses.
      $shipping = array('street_sh' => $addresses['street_sh'],
			'city_sh' => $addresses['city_sh'],
			'region_code_sh' => $addresses['region_code_sh'],
			'postcode_sh' => $addresses['postcode_sh'],
			'country_code_sh' => $addresses['country_code_sh'],
			'phone_sh' => $addresses['phone_sh'],
			'note_sh' => $addresses['note_sh']);

      $billing = array('street_bi' => $addresses['street_bi'],
		       'city_bi' => $addresses['city_bi'],
		       'region_code_bi' => $addresses['region_code_bi'],
		       'postcode_bi' => $addresses['postcode_bi'],
		       'country_code_bi' => $addresses['country_code_bi'],
		       'phone_bi' => $addresses['phone_bi'],
		       'note_bi' => $addresses['note_bi']);

      //Get the proper query to use with this address. 
      $query = UtilityHelper::getAddressQuery($shipping, 'shipping', 'customer', $userId);

      try
      {
	$db->setQuery($query);

	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(JException $e)
      {
	$this->_subject->setError($e->getMessage());
	return false;
      }

      //Get the proper query to use for with address. 
      $query = UtilityHelper::getAddressQuery($billing, 'billing', 'customer', $userId);

      try
      {
	$db->setQuery($query);

	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(JException $e)
      {
	$this->_subject->setError($e->getMessage());
	return false;
      }
    }

    return true;
  }



  /**
   * Remove all user profile information for the given user ID
   *
   * Method is called after user data is deleted from the database
   *
   * @param	array		$user		Holds the user data
   * @param	boolean		$success	True if user was succesfully stored in the database
   * @param	string		$msg		Message
   */
  function onUserAfterDelete($user, $success, $msg)
  {
    if(!$success) {
      return false;
    }

    $userId = JArrayHelper::getValue($user, 'id', 0, 'int');

    if($userId) {
      try
      {
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	//Remove the user from the ketshop_customer table 
	$query->delete('#__ketshop_customer');
	$query->where('user_id='.(int)$userId);
	$db->setQuery($query);

	if(!$db->query()) { 
	  throw new Exception($db->getErrorMsg());
	}

	//and all his addresses.
	$query->clear();
	$query->delete('#__ketshop_address');
	$query->where('item_id='.(int)$userId.' AND item_type="customer"');
	$db->setQuery($query);

	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(JException $e)
      {
	$this->_subject->setError($e->getMessage());
	return false;
      }
    }

    return true;
  }


  public function onUserAfterLogin($options)
  {
    $app = JFactory::getApplication();
    //Make sure the user logs in from the site side (ie: frontend).
    if($app->isSite()) {
      //Check if the user has a pending cart.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('id')
	    ->from('#__ketshop_order')
	    ->where('user_id='.(int)$options['user']->id.' AND cart_status="pending"');
      $db->setQuery($query);
      $pendingOrderId = $db->loadResult();

      //Instanciate then load the model in order to use the loadCart
      //function from this plugin.
      $cartModel = JModelLegacy::getInstance('cart', 'KetshopController');

      if(!is_null($pendingOrderId)) {
	//Load the cart previousely saved.
	$cartModel->loadCart($pendingOrderId);
	//Inform the customer that products of the pending cart have been loaded. 
	$app->enqueueMessage(JText::_('PLG_USER_KETSHOP_PROFILE_CART_PREVIOUSLY_SAVED'));
      }
      else {
	$cartModel = JModelLegacy::getInstance('cart', 'KetshopController');
	//We just reload the current cart (passing no argument to the function).
	$cartModel->loadCart();
      }

      //Grab the user session.
      $session = JFactory::getSession();
      $location = $session->get('location', '', 'ketshop'); 

      //Redirect the user to the location he was before log in (when purchasing).
      if(!empty($location)) {
	JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_ketshop&view='.$location, false));
	return true;
      }
    }

    return true;
  }
}

